<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\{Database, HttpException, Str};

/**
 * Best of: o melhor jogo de cada sistema, gênero, desenvolvedora, publisher e franquia, escolhido pelo usuário.
 *
 * Regras para ser justo:
 *  - só concorre quem é da categoria (jogo zerado do próprio catálogo com aquele sistema/gênero/empresa,
 *    ou que cumpre um item da franquia);
 *  - a categoria precisa ter pelo menos MIN_GAMES jogos (com um só não há o que escolher);
 *  - a escolha é revalidada na leitura: se o jogo mudar de categoria, ela deixa de aparecer.
 */
final class BestOfService
{
    public const MIN_GAMES = 2;
    /** Só jogos com esta nota ou mais (9, 10 ou 11) podem ser eleitos best of (a mesma regra do pódio da Retrospectiva). */
    public const MIN_RATING = RetrospectiveService::MIN_RATING_TOP;

    /** Tabela e coluna do jogo de cada categoria "de catálogo". */
    private const DIMS = [
        'platform'  => ['platforms', 'platform_id'],
        'genre'     => ['genres', 'genre_id'],
        'developer' => ['companies', 'developer_id'],
        'publisher' => ['companies', 'publisher_id'],
    ];

    public const KINDS = ['platform', 'genre', 'developer', 'publisher', 'franchise'];

    public function __construct(
        private UploadService $uploads = new UploadService(),
    ) {}

    /**
     * Escolhas válidas de um tipo de categoria, por chave (name_key do grupo).
     * @return array<string, array> chave => jogo
     */
    public function forDim(int $userId, string $kind): array
    {
        [$table, $col] = self::DIMS[$kind] ?? throw new HttpException(404, 'Categoria desconhecida.');
        $rows = Database::all(
            "SELECT b.ref_key, b.chosen_at, g.id, g.title, g.cover_path, g.rating, g.finished_at, g.playtime_minutes, p.name AS platform
             FROM best_picks b
             JOIN games g ON g.id = b.game_id AND g.user_id = b.user_id
             JOIN $table t ON t.id = g.$col AND t.name_key = b.ref_key
             JOIN platforms p ON p.id = g.platform_id
             WHERE b.user_id = ? AND b.kind = ? AND g.rating >= ?",
            [$userId, $kind, self::MIN_RATING]
        );
        $out = [];
        foreach ($rows as $r) {
            $out[$r['ref_key']] = $this->game($r) + ['chosen_at' => $r['chosen_at']];
        }
        return $out;
    }

    /**
     * Jogos que podem ser escolhidos numa categoria, da maior nota para a menor.
     * $eligibleOnly = false traz todos os jogos da categoria (para contar), mesmo os abaixo da nota mínima.
     */
    public function candidates(int $userId, string $kind, string $value, bool $eligibleOnly = true): array
    {
        if ($kind === 'franchise') {
            $all = $this->franchiseCandidates($userId, (int) $value);
            return $eligibleOnly ? array_values(array_filter($all, fn($g) => $g['rating'] >= self::MIN_RATING)) : $all;
        }
        [$table, $col] = self::DIMS[$kind] ?? throw new HttpException(404, 'Categoria desconhecida.');
        $rows = Database::all(
            "SELECT g.id, g.title, g.cover_path, g.rating, g.finished_at, g.playtime_minutes, p.name AS platform
             FROM games g
             JOIN $table t ON t.id = g.$col
             JOIN platforms p ON p.id = g.platform_id
             WHERE g.user_id = ? AND t.name_key = ? AND g.rating >= ?
             ORDER BY g.rating DESC, g.playtime_minutes DESC, g.finished_at DESC, g.id DESC",
            [$userId, Str::key($value), $eligibleOnly ? self::MIN_RATING : 0]
        );
        return array_map(fn($r) => $this->game($r), $rows);
    }

    /** Escolhe (ou, com game_id nulo, desfaz) o best of de uma categoria. Devolve a escolha atual. */
    public function choose(int $userId, string $kind, string $value, ?int $gameId): ?array
    {
        if (!in_array($kind, self::KINDS, true) || trim($value) === '') {
            throw new HttpException(422, 'Categoria inválida.');
        }
        $key = $kind === 'franchise' ? (string) (int) $value : Str::key($value);

        if ($gameId === null) {
            Database::query('DELETE FROM best_picks WHERE user_id = ? AND kind = ? AND ref_key = ?', [$userId, $kind, $key]);
            return null;
        }

        $all = $this->candidates($userId, $kind, $value, false);
        if (count($all) < self::MIN_GAMES) {
            throw new HttpException(422, 'O best of fica disponível quando a categoria tem pelo menos ' . self::MIN_GAMES . ' jogos zerados.');
        }
        $pick = null;
        $any = null;
        foreach ($all as $c) {
            if ($c['id'] === $gameId) {
                $any = $c;
                if ($c['rating'] >= self::MIN_RATING) {
                    $pick = $c;
                }
            }
        }
        if ($any === null) {
            throw new HttpException(422, 'Esse jogo não faz parte desta categoria.');
        }
        if ($pick === null) {
            throw new HttpException(422, 'Nota baixa demais para o best of.', ['game_id' => "{$any['title']} tem nota {$any['rating']}: só jogos com nota 9, 10 ou 11 podem ser eleitos best of."]);
        }
        Database::query(
            'INSERT INTO best_picks (user_id, kind, ref_key, game_id) VALUES (?,?,?,?)
             ON CONFLICT(user_id, kind, ref_key) DO UPDATE SET game_id = excluded.game_id, chosen_at = NOW()',
            [$userId, $kind, $key, $gameId]
        );
        return $pick + ['chosen_at' => date('Y-m-d H:i:s')];
    }

    /**
     * Best of de uma franquia já carregada (FranchiseService): válido só se o jogo ainda cumpre algum item dela.
     */
    public function forFranchise(int $userId, array $franchise, ?array $picks = null): ?array
    {
        // $picks: escolhas de todas as franquias já lidas (evita uma consulta por franquia na listagem)
        $picks ??= $this->franchisePicks($userId);
        $gameId = $picks[(string) $franchise['id']] ?? null;
        if ($gameId === null) {
            return null;
        }
        foreach (self::franchiseGames($franchise) as $g) {
            if ((int) $g['id'] === (int) $gameId && (int) $g['rating'] >= self::MIN_RATING) {
                return $this->game($g);
            }
        }
        return null;
    }

    /**
     * Categorias em que um jogo é o best of (para os selos da página do jogo). Só conta escolha ainda válida:
     * o jogo precisa continuar naquele sistema/gênero/empresa ou naquela franquia.
     * @param array $game      jogo de GameService::find (com platform, genre, developer, publisher)
     * @param array $franchises franquias em que o jogo aparece (FranchiseService::forGame)
     */
    public function forGame(int $userId, array $game, array $franchises): array
    {
        $labels = ['platform' => 'sistema', 'genre' => 'gênero', 'developer' => 'desenvolvedora', 'publisher' => 'publisher', 'franchise' => 'franquia'];
        $names = array_column($franchises, 'name', 'id');
        $out = [];
        if ((int) $game['rating'] < self::MIN_RATING) {
            return $out;   // abaixo da nota mínima: escolhas antigas deste jogo não valem mais
        }
        foreach (Database::all('SELECT kind, ref_key FROM best_picks WHERE user_id = ? AND game_id = ?', [$userId, $game['id']]) as $r) {
            $kind = $r['kind'];
            if ($kind === 'franchise') {
                if (isset($names[(int) $r['ref_key']])) {
                    $out[] = ['kind' => $kind, 'unit' => $labels[$kind], 'name' => $names[(int) $r['ref_key']], 'ref' => (int) $r['ref_key']];
                }
            } elseif (isset($labels[$kind]) && ($game[$kind] ?? null) !== null && Str::key((string) $game[$kind]) === $r['ref_key']) {
                $out[] = ['kind' => $kind, 'unit' => $labels[$kind], 'name' => $game[$kind], 'ref' => $game[$kind]];
            }
        }
        $order = array_flip(array_keys($labels));
        usort($out, fn($a, $b) => $order[$a['kind']] <=> $order[$b['kind']]);
        return $out;
    }

    /**
     * Todas as escolhas válidas do usuário, como "honras" de um jogo (usado no "Hoje na sua história").
     * @return list<array{game_id:int, kind:string, label:string}>
     */
    public function honors(int $userId): array
    {
        $out = [];
        foreach (self::DIMS as $kind => [$table, $col]) {
            foreach (Database::all(
                "SELECT g.id, t.name FROM best_picks b
                 JOIN games g ON g.id = b.game_id AND g.user_id = b.user_id
                 JOIN $table t ON t.id = g.$col AND t.name_key = b.ref_key
                 WHERE b.user_id = ? AND b.kind = ? AND g.rating >= ?",
                [$userId, $kind, self::MIN_RATING]
            ) as $r) {
                $out[] = ['game_id' => (int) $r['id'], 'kind' => 'best', 'label' => 'Best of ' . $r['name']];
            }
        }
        foreach ((new FranchiseService())->list($userId) as $f) {
            if ($f['best']) {
                $out[] = ['game_id' => (int) $f['best']['id'], 'kind' => 'best', 'label' => 'Best of ' . $f['name']];
            }
        }
        return $out;
    }

    /** @return array<string,int> id da franquia => id do jogo escolhido */
    public function franchisePicks(int $userId): array
    {
        return array_map('intval', array_column(Database::all(
            "SELECT ref_key, game_id FROM best_picks WHERE user_id = ? AND kind = 'franchise'", [$userId]
        ), 'game_id', 'ref_key'));
    }

    /** Jogos zerados distintos que cumprem itens da franquia. */
    public static function franchiseGames(array $franchise): array
    {
        $out = [];
        foreach ($franchise['sections'] as $s) {
            foreach ($s['entries'] as $e) {
                if ($e['game'] ?? null) {
                    $out[$e['game']['id']] = $e['game'];
                }
            }
        }
        $out = array_values($out);
        usort($out, fn($a, $b) => [$b['rating'], $b['playtime_minutes'] ?? 0, $b['finished_at']] <=> [$a['rating'], $a['playtime_minutes'] ?? 0, $a['finished_at']]);
        return $out;
    }

    private function franchiseCandidates(int $userId, int $franchiseId): array
    {
        $f = (new FranchiseService())->find($userId, $franchiseId);
        // os jogos da franquia já vêm com a URL da capa pronta
        return array_map(fn($g) => [
            'id' => (int) $g['id'], 'title' => $g['title'], 'cover_url' => $g['cover_url'], 'rating' => (int) $g['rating'],
            'finished_at' => $g['finished_at'], 'playtime_minutes' => (int) ($g['playtime_minutes'] ?? 0), 'platform' => $g['platform'],
        ], self::franchiseGames($f));
    }

    /** Formato comum de um jogo no best of. */
    private function game(array $r): array
    {
        return [
            'id'               => (int) $r['id'],
            'title'            => $r['title'],
            'cover_url'        => array_key_exists('cover_path', $r) ? $this->uploads->url($r['cover_path']) : ($r['cover_url'] ?? null),
            'rating'           => (int) $r['rating'],
            'finished_at'      => $r['finished_at'],
            'playtime_minutes' => (int) ($r['playtime_minutes'] ?? 0),
            'platform'         => $r['platform'],
        ];
    }
}
