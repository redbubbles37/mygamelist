<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\{Config, Database, Str};

/**
 * "Próximos passos" do dashboard: até 4 sugestões do que fazer a seguir, montadas com o que já existe no site
 * (vaga em "jogando agora", franquia quase completa, best of e GOTY ainda sem escolha).
 * Cada item: kind, icon, title, text, href, cta.
 */
final class NextStepsService
{
    private const MAX = 4;

    public function forUser(int $userId): array
    {
        $base = (string) Config::get('base_path', '');
        $out = [];

        // 1) vaga livre em "jogando agora" e alguém esperando na fila
        $playing = (int) Database::value("SELECT COUNT(*) FROM backlog_games WHERE user_id = ? AND status = 'playing'", [$userId]);
        if ($playing < BacklogService::MAX_PLAYING) {
            $next = Database::one(
                "SELECT b.title, p.name AS platform FROM backlog_games b LEFT JOIN platforms p ON p.id = b.platform_id
                 WHERE b.user_id = ? AND b.status = 'want'
                 ORDER BY FIELD(b.priority, 'high', 'medium', 'low'), COALESCE(b.queued_at, DATE(b.created_at)), b.id LIMIT 1",
                [$userId]
            );
            if ($next) {
                $free = BacklogService::MAX_PLAYING - $playing;
                $out[] = [
                    'kind' => 'backlog', 'icon' => '🎮',
                    'title' => $free === 1 ? '1 vaga livre em "jogando agora"' : "$free vagas livres em \"jogando agora\"",
                    'text' => 'Que tal começar ' . $next['title'] . ($next['platform'] ? ' (' . $next['platform'] . ')' : '') . '? É o próximo da sua fila.',
                    'href' => $base . '/backlog', 'cta' => 'Abrir o backlog',
                ];
            }
        }

        // 2) franquia mais perto de ficar completa
        $franchises = (new FranchiseService())->list($userId);
        $near = array_values(array_filter($franchises, fn($f) => $f['done'] > 0 && $f['total'] > $f['done'] && $f['total'] - $f['done'] <= 3));
        usort($near, fn($a, $b) => ($a['total'] - $a['done']) <=> ($b['total'] - $b['done']) ?: $b['done'] / $b['total'] <=> $a['done'] / $a['total']);
        if ($near) {
            $f = $near[0];
            $miss = $f['total'] - $f['done'];
            $out[] = [
                'kind' => 'franchise', 'icon' => '🧩',
                'title' => $f['name'] . ': ' . ($miss === 1 ? 'falta 1 jogo' : "faltam $miss jogos"),
                'text' => "Você já zerou {$f['done']} de {$f['total']}. Mais um pouco e a franquia fica completa.",
                'href' => $base . '/franquias/' . $f['id'], 'cta' => 'Ver o que falta',
            ];
        }

        // 3) GOTY ainda sem escolha (o ano mais recente que já pode ter)
        $goty = Database::one(
            'SELECT g.release_year AS y, COUNT(*) AS n FROM games g
             WHERE g.user_id = ? AND g.release_year IS NOT NULL
               AND NOT EXISTS (SELECT 1 FROM goty_picks p JOIN games pg ON pg.id = p.game_id AND pg.release_year = p.year
                               WHERE p.user_id = g.user_id AND p.year = g.release_year)
             GROUP BY g.release_year HAVING n >= ? ORDER BY g.release_year DESC LIMIT 1',
            [$userId, GotyService::MIN_GAMES]
        );
        if ($goty) {
            $out[] = [
                'kind' => 'goty', 'icon' => '⭐',
                'title' => 'GOTY de ' . $goty['y'] . ' ainda sem escolha',
                'text' => "Você zerou {$goty['n']} jogos lançados em {$goty['y']}. Qual foi o melhor?",
                'href' => $base . '/goty#' . $goty['y'], 'cta' => 'Escolher o GOTY',
            ];
        }

        // 4) best of liberado e ainda não escolhido: a categoria com mais jogos
        $best = $this->bestPending($userId, $franchises);
        if ($best) {
            $out[] = [
                'kind' => 'best', 'icon' => '🏆',
                'title' => $best['name'] . ' ainda sem best of',
                'text' => "São {$best['n']} jogos zerados ali. Qual é o melhor deles?",
                'href' => $base . $best['href'], 'cta' => 'Escolher o best of',
            ];
        }

        return array_slice($out, 0, self::MAX);
    }

    /** Categoria (sistema, gênero, empresa ou franquia) com mais jogos que já pode ter best of e ainda não tem. */
    private function bestPending(int $userId, array $franchises): ?array
    {
        $dims = [
            'platform'  => ['platforms', 'platform_id', '/sistemas'],
            'genre'     => ['genres', 'genre_id', '/generos'],
            'developer' => ['companies', 'developer_id', '/desenvolvedoras'],
            'publisher' => ['companies', 'publisher_id', '/publishers'],
        ];
        $bestOf = new BestOfService();
        $top = null;
        foreach ($dims as $kind => [$table, $col, $href]) {
            $picked = $bestOf->forDim($userId, $kind);
            foreach (Database::all(
                "SELECT t.name, COUNT(*) AS n FROM games g JOIN $table t ON t.id = g.$col
                 WHERE g.user_id = ? GROUP BY t.id, t.name HAVING n >= ? ORDER BY n DESC LIMIT 20",
                [$userId, BestOfService::MIN_GAMES]
            ) as $r) {
                if (!isset($picked[Str::key($r['name'])]) && ($top === null || (int) $r['n'] > $top['n'])) {
                    $top = ['name' => $r['name'], 'n' => (int) $r['n'], 'href' => $href];
                }
                if (!isset($picked[Str::key($r['name'])])) {
                    break; // a lista vem ordenada: o primeiro sem escolha é o maior desta dimensão
                }
            }
        }
        foreach ($franchises as $f) {
            $games = $f['games'] ?? $f['done'];
            if (!$f['best'] && $games >= BestOfService::MIN_GAMES && ($top === null || $games > $top['n'])) {
                $top = ['name' => $f['name'], 'n' => $games, 'href' => '/franquias/' . $f['id']];
            }
        }
        return $top;
    }
}
