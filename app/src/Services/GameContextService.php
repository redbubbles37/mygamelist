<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Contexto da página de um jogo dentro do catálogo do usuário: honras (GOTY e best of), curiosidades
 * ("seu 3º jogo mais longo"), anterior/próximo na ordem em que foram zerados, relacionados e dados faltando.
 */
final class GameContextService
{
    public function __construct(
        private UploadService $uploads = new UploadService(),
    ) {}

    /**
     * @param array $game       jogo de GameService::find
     * @param array $franchises franquias em que ele aparece (FranchiseService::forGame)
     */
    public function for(int $userId, array $game, array $franchises): array
    {
        $id = (int) $game['id'];
        $ref = Database::one('SELECT platform_id, developer_id, publisher_id, genre_id FROM games WHERE id = ? AND user_id = ?', [$id, $userId]);

        return [
            'honors'   => $this->honors($userId, $game, $franchises),
            'facts'    => $this->facts($userId, $game, $ref),
            'prev'     => $this->neighbor($userId, $game, 'prev'),
            'next'     => $this->neighbor($userId, $game, 'next'),
            'related'  => $this->related($userId, $game, $ref, $franchises),
            'story'    => $this->story($userId, $game),
            'compare'  => $this->compare($userId, $game, $ref),
            'missing'  => array_values(array_filter([
                $game['cover_url'] ? null : 'capa',
                $game['genre'] ? null : 'gênero',
                $game['release_year'] ? null : 'ano de lançamento',
                $game['developer'] ? null : 'desenvolvedora',
                $game['publisher'] ? null : 'publisher',
            ])),
        ];
    }

    /** GOTY e best of deste jogo (o GOTY traz o comentário escrito na escolha). */
    private function honors(int $userId, array $game, array $franchises): array
    {
        $out = [];
        foreach ((new GotyService($this->uploads))->picks($userId) as $p) {
            if ((int) $p['game']['id'] === (int) $game['id']) {
                $out[] = ['kind' => 'goty', 'label' => 'GOTY ' . $p['year'], 'href' => '/goty#' . $p['year'], 'note' => $p['note']];
            }
        }
        $pages = ['platform' => '/sistemas', 'genre' => '/generos', 'developer' => '/desenvolvedoras', 'publisher' => '/publishers'];
        foreach ((new BestOfService($this->uploads))->forGame($userId, $game, $franchises) as $b) {
            $out[] = ['kind' => 'best', 'label' => 'Best of ' . $b['name'], 'href' => $b['kind'] === 'franchise' ? '/franquias/' . $b['ref'] : $pages[$b['kind']], 'note' => null];
        }
        return $out;
    }

    /** Até 4 curiosidades sobre o lugar do jogo no catálogo, das mais marcantes para as mais comuns. */
    private function facts(int $userId, array $game, ?array $ref): array
    {
        $id = (int) $game['id'];
        $rating = (int) $game['rating'];
        $minutes = (int) $game['playtime_minutes'];
        $year = (int) substr($game['finished_at'], 0, 4);
        $facts = [];
        $ord = fn(int $n) => $n . 'º';

        // nota dentro do sistema
        if ($ref) {
            $p = Database::one(
                'SELECT COUNT(*) AS n, SUM(rating > ?) AS above, SUM(rating = ?) AS same FROM games WHERE user_id = ? AND platform_id = ?',
                [$rating, $rating, $userId, $ref['platform_id']]
            );
            $n = (int) $p['n'];
            if ($n >= 3) {
                $rank = (int) $p['above'] + 1;
                $ties = (int) $p['same'] - 1;
                if ($rank === 1) {
                    $facts[] = ['icon' => '👑', 'text' => 'A maior nota que você deu no ' . $game['platform'] . ($ties ? " (empatado com $ties)" : '') . ", entre $n jogos."];
                } elseif ($rank <= 5) {
                    $facts[] = ['icon' => '📈', 'text' => $ord($rank) . ' melhor nota no ' . $game['platform'] . ", entre $n jogos."];
                }
            }
        }

        // duração no catálogo inteiro
        $total = (int) Database::value('SELECT COUNT(*) FROM games WHERE user_id = ?', [$userId]);
        $longer = (int) Database::value('SELECT COUNT(*) FROM games WHERE user_id = ? AND playtime_minutes > ?', [$userId, $minutes]);
        if ($total >= 5 && $minutes > 0) {
            if ($longer < 10) {
                $facts[] = ['icon' => '⏳', 'text' => ($longer === 0 ? 'O jogo mais longo' : 'Seu ' . $ord($longer + 1) . ' jogo mais longo') . " de $total no catálogo."];
            } elseif ($total - $longer <= 10 && $total >= 20) {
                $facts[] = ['icon' => '⚡', 'text' => 'Um dos mais rápidos de zerar: só ' . ($total - $longer - 1) . ' foram mais curtos.'];
            }
        }

        // nota comparada com a média
        $avg = (float) Database::value('SELECT AVG(rating) FROM games WHERE user_id = ?', [$userId]);
        if ($total >= 5 && abs($rating - $avg) >= 1) {
            $facts[] = ['icon' => $rating > $avg ? '⭐' : '🌧', 'text' => 'Nota ' . ($rating > $avg ? 'acima' : 'abaixo') . ' da sua média (' . str_replace('.', ',', (string) round($avg, 1)) . ').'];
        }

        // lançamento × quando zerou
        if ($game['release_year']) {
            $gap = $year - (int) $game['release_year'];
            if ($gap === 0) {
                $facts[] = ['icon' => '🆕', 'text' => 'Zerado no mesmo ano em que foi lançado.'];
            } elseif ($gap >= 5) {
                $facts[] = ['icon' => '🕰', 'text' => "Zerado $gap anos depois do lançamento."];
            }
        }

        // ordem no ano
        $inYear = Database::one(
            'SELECT COUNT(*) AS n, SUM(finished_at < ? OR (finished_at = ? AND id < ?)) AS before_n FROM games WHERE user_id = ? AND YEAR(finished_at) = ?',
            [$game['finished_at'], $game['finished_at'], $id, $userId, $year]
        );
        if ((int) $inYear['n'] >= 2) {
            $facts[] = ['icon' => '📅', 'text' => $ord((int) $inYear['before_n'] + 1) . " jogo zerado em $year, de {$inYear['n']}."];
        }

        return array_slice($facts, 0, 4);
    }

    /**
     * O lugar do jogo na sua história: ordem e duração dentro do ano em que foi zerado e quantos foram zerados no mesmo mês.
     * (Os links para retrospectiva, mapa de calor e GOTY são montados na página.)
     */
    private function story(int $userId, array $game): array
    {
        $id = (int) $game['id'];
        $year = (int) substr($game['finished_at'], 0, 4);
        $month = substr($game['finished_at'], 0, 7);
        $y = Database::one(
            'SELECT COUNT(*) AS n,
                    SUM(finished_at < ? OR (finished_at = ? AND id < ?)) AS before_n,
                    SUM(playtime_minutes > ?) AS longer
             FROM games WHERE user_id = ? AND YEAR(finished_at) = ?',
            [$game['finished_at'], $game['finished_at'], $id, (int) $game['playtime_minutes'], $userId, $year]
        );
        $total = (int) Database::value('SELECT COUNT(*) FROM games WHERE user_id = ?', [$userId]);
        $overall = 1 + (int) Database::value(
            'SELECT COUNT(*) FROM games WHERE user_id = ? AND (finished_at < ? OR (finished_at = ? AND id < ?))',
            [$userId, $game['finished_at'], $game['finished_at'], $id]
        );
        $monthN = (int) Database::value('SELECT COUNT(*) FROM games WHERE user_id = ? AND substr(finished_at, 1, 7) = ?', [$userId, $month]);
        return [
            'year' => $year,
            'order' => (int) $y['before_n'] + 1,
            'count' => (int) $y['n'],
            'longest_rank' => (int) $y['longer'] + 1,
            'month_games' => $monthN,
            'overall' => $overall,
            'total' => $total,
            'milestone' => (in_array($overall, [1, 10, 25, 50], true) || ($overall >= 100 && $overall % 50 === 0)) ? $overall : null,
        ];
    }

    /** Médias do mesmo gênero e do mesmo sistema (com pelo menos 3 jogos), para comparar tempo e nota. */
    private function compare(int $userId, array $game, ?array $ref): array
    {
        if (!$ref) {
            return [];
        }
        $out = [];
        foreach ([['genre_id', 'Gênero', $game['genre']], ['platform_id', 'Sistema', $game['platform']]] as [$col, $kind, $name]) {
            if ($ref[$col] === null || !$name) {
                continue;
            }
            $r = Database::one(
                "SELECT COUNT(*) AS n, AVG(playtime_minutes) AS minutes, AVG(rating) AS rating, MAX(playtime_minutes) AS max_minutes
                 FROM games WHERE user_id = ? AND $col = ?",
                [$userId, $ref[$col]]
            );
            if ((int) $r['n'] >= 3) {
                $out[] = [
                    'kind' => $kind, 'name' => $name, 'n' => (int) $r['n'],
                    'avg_minutes' => (int) round((float) $r['minutes']), 'max_minutes' => (int) $r['max_minutes'],
                    'avg_rating' => round((float) $r['rating'], 1),
                ];
            }
        }
        return $out;
    }

    /** Jogo anterior/seguinte na ordem em que foram zerados. */
    private function neighbor(int $userId, array $game, string $dir): ?array
    {
        $prev = $dir === 'prev';
        $r = Database::one(
            'SELECT g.id, g.title, g.cover_path, g.finished_at FROM games g
             WHERE g.user_id = ? AND ' . ($prev
                ? '(g.finished_at < ? OR (g.finished_at = ? AND g.id < ?)) ORDER BY g.finished_at DESC, g.id DESC'
                : '(g.finished_at > ? OR (g.finished_at = ? AND g.id > ?)) ORDER BY g.finished_at ASC, g.id ASC') . ' LIMIT 1',
            [$userId, $game['finished_at'], $game['finished_at'], $game['id']]
        );
        return $r ? ['id' => (int) $r['id'], 'title' => $r['title'], 'cover_url' => $this->uploads->url($r['cover_path']), 'finished_at' => $r['finished_at']] : null;
    }

    /** Relacionados: da mesma franquia, da mesma desenvolvedora e do mesmo sistema (os de maior nota primeiro). */
    private function related(int $userId, array $game, ?array $ref, array $franchises): array
    {
        $id = (int) $game['id'];
        $card = fn(array $r) => ['id' => (int) $r['id'], 'title' => $r['title'], 'cover_url' => $this->uploads->url($r['cover_path']), 'rating' => (int) $r['rating']];
        $out = [];

        if ($franchises) {
            $f = (new FranchiseService($this->uploads))->find($userId, (int) $franchises[0]['id']);
            $games = array_values(array_filter(BestOfService::franchiseGames($f), fn($g) => (int) $g['id'] !== $id));
            if ($games) {
                $out[] = ['key' => 'franchise', 'label' => $f['name'], 'href' => '/franquias/' . $f['id'],
                    'games' => array_map(fn($g) => ['id' => (int) $g['id'], 'title' => $g['title'], 'cover_url' => $g['cover_url'], 'rating' => (int) $g['rating']], array_slice($games, 0, 12))];
            }
        }
        $by = [
            ['developer', 'developer_id', $game['developer']],
            ['platform', 'platform_id', $game['platform']],
        ];
        foreach ($by as [$key, $col, $name]) {
            if (!$ref || $ref[$col] === null || !$name) {
                continue;
            }
            $rows = Database::all(
                "SELECT id, title, cover_path, rating FROM games WHERE user_id = ? AND $col = ? AND id <> ? ORDER BY rating DESC, finished_at DESC LIMIT 12",
                [$userId, $ref[$col], $id]
            );
            if ($rows) {
                $out[] = ['key' => $key, 'label' => $name, 'href' => '/jogos?' . $key . '=' . rawurlencode($name), 'games' => array_map($card, $rows)];
            }
        }
        return $out;
    }
}
