<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Auth, Database, Request, Response};
use App\Services\{LookupService, XpService};

final class MeController
{
    /**
     * Dados do dashboard. ?year=AAAA (padrão: ano atual).
     * Totais e rankings vêm em duas versões: "year" (ano escolhido) e "all" (sempre).
     */
    public function stats(Request $req): Response
    {
        $u = Auth::require();
        $id = $u['id'];

        $year = filter_var($req->query['year'] ?? null, FILTER_VALIDATE_INT);
        if ($year === false || $year < 1970 || $year > 2100) {
            $year = (int) date('Y');
        }

        $years = array_map('intval', array_column(
            Database::all('SELECT DISTINCT YEAR(finished_at) AS y FROM games WHERE user_id = ? ORDER BY y DESC', [$id]),
            'y'
        ));
        if (!in_array((int) date('Y'), $years, true)) {
            array_unshift($years, (int) date('Y'));
        }
        if (!in_array($year, $years, true)) {
            $years[] = $year;
            rsort($years);
        }

        // Escopos: "all" sem filtro de ano; "year" restrito ao ano escolhido.
        $scopes = ['all' => ['', []], 'year' => [' AND YEAR(g.finished_at) = ?', [$year]]];

        $totals = $genres = $platforms = [];
        foreach ($scopes as $name => [$cond, $extra]) {
            $row = Database::one(
                "SELECT COUNT(*) AS games, COALESCE(SUM(g.playtime_minutes),0) AS minutes, AVG(g.rating) AS avg_rating
                 FROM games g WHERE g.user_id = ?$cond",
                [$id, ...$extra]
            );
            $totals[$name] = [
                'games'      => (int) $row['games'],
                'hours'      => round($row['minutes'] / 60, 1),
                'avg_rating' => $row['avg_rating'] === null ? null : round((float) $row['avg_rating'], 1),
            ];
            $genres[$name] = Database::all(
                "SELECT ge.name, COUNT(*) AS n FROM games g
                 JOIN genres ge ON ge.id = g.genre_id
                 WHERE g.user_id = ?$cond GROUP BY ge.id, ge.name ORDER BY n DESC, ge.name LIMIT 10",
                [$id, ...$extra]
            );
            $platforms[$name] = Database::all(
                "SELECT p.name, COUNT(*) AS n FROM games g JOIN platforms p ON p.id = g.platform_id
                 WHERE g.user_id = ?$cond GROUP BY p.id, p.name ORDER BY n DESC, p.name LIMIT 10",
                [$id, ...$extra]
            );
        }

        // 12 meses do ano escolhido: jogos, horas e XP (XP atribuído ao mês da finalização).
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[$m] = ['month' => $m, 'games' => 0, 'hours' => 0.0, 'xp' => 0];
        }
        foreach (Database::all(
            'SELECT MONTH(finished_at) AS m, COUNT(*) AS games, SUM(playtime_minutes) AS minutes
             FROM games WHERE user_id = ? AND YEAR(finished_at) = ? GROUP BY m',
            [$id, $year]
        ) as $r) {
            $months[$r['m']]['games'] = (int) $r['games'];
            $months[$r['m']]['hours'] = round($r['minutes'] / 60, 1);
        }
        foreach (Database::all(
            'SELECT MONTH(g.finished_at) AS m, SUM(e.amount) AS xp
             FROM xp_events e JOIN games g ON g.id = e.game_id
             WHERE g.user_id = ? AND YEAR(g.finished_at) = ? GROUP BY m',
            [$id, $year]
        ) as $r) {
            $months[$r['m']]['xp'] = (int) $r['xp'];
        }

        return Response::json([
            'xp'        => (new XpService())->progress($u['total_xp']),
            'goal'      => (new \App\Services\GoalService())->summary($id),
            'playing'   => (new \App\Services\BacklogService())->playing($id, \App\Services\BacklogService::MAX_PLAYING),
            'year'      => $year,
            'years'     => $years,
            'totals'    => $totals,
            'months'    => array_values($months),
            'genres'    => $genres,
            'platforms' => $platforms,
        ]);
    }

    /**
     * GET /api/heatmap?year=AAAA: jogos zerados em cada dia do ano (para o mapa de calor).
     * days = { "AAAA-MM-DD": [{id, title, platform, rating, playtime_minutes, xp}, ...] }
     */
    public function heatmap(Request $req): Response
    {
        $id = Auth::require()['id'];
        $year = filter_var($req->query['year'] ?? null, FILTER_VALIDATE_INT);
        if ($year === false || $year < 1970 || $year > 2100) {
            $year = (int) date('Y');
        }
        $days = [];
        foreach (Database::all(
            'SELECT g.id, g.title, g.finished_at, g.rating, g.playtime_minutes, p.name AS platform,
                    COALESCE((SELECT SUM(e.amount) FROM xp_events e WHERE e.game_id = g.id), 0) AS xp
             FROM games g JOIN platforms p ON p.id = g.platform_id
             WHERE g.user_id = ? AND YEAR(g.finished_at) = ? ORDER BY g.finished_at, g.id',
            [$id, $year]
        ) as $g) {
            $days[$g['finished_at']][] = [
                'id' => (int) $g['id'], 'title' => $g['title'], 'platform' => $g['platform'],
                'rating' => (int) $g['rating'], 'playtime_minutes' => (int) $g['playtime_minutes'], 'xp' => (int) $g['xp'],
            ];
        }
        $years = array_map('intval', array_column(Database::all(
            'SELECT DISTINCT YEAR(finished_at) AS y FROM games WHERE user_id = ? ORDER BY y DESC', [$id]), 'y'));
        if (!in_array((int) date('Y'), $years, true)) {
            array_unshift($years, (int) date('Y'));
        }
        if (!in_array($year, $years, true)) {
            $years[] = $year;
            rsort($years);
        }
        return Response::json(['year' => $year, 'years' => $years, 'days' => (object) $days]);
    }

    /** GET /api/timeline: todos os jogos zerados em ordem cronológica (campos enxutos para a linha do tempo). */
    public function timeline(Request $req): Response
    {
        $uploads = new \App\Services\UploadService();
        $rows = Database::all(
            'SELECT g.id, g.title, g.finished_at, g.release_year, g.playtime_minutes, g.rating, g.difficulty, g.cover_path,
                    p.name AS platform, ge.name AS genre
             FROM games g JOIN platforms p ON p.id = g.platform_id LEFT JOIN genres ge ON ge.id = g.genre_id
             WHERE g.user_id = ? ORDER BY g.finished_at, g.id',
            [Auth::require()['id']]
        );
        return Response::json(['data' => array_map(fn(array $g) => [
            'id' => (int) $g['id'], 'title' => $g['title'], 'platform' => $g['platform'], 'genre' => $g['genre'],
            'finished_at' => $g['finished_at'], 'release_year' => $g['release_year'] === null ? null : (int) $g['release_year'],
            'playtime_minutes' => (int) $g['playtime_minutes'], 'rating' => (int) $g['rating'], 'difficulty' => $g['difficulty'],
            'cover_url' => $uploads->url($g['cover_path']),
        ], $rows)]);
    }

    /** Lista compacta de todos os jogos do usuário (id, título, sistema, ano de lançamento). */
    public function gameIndex(Request $req): Response
    {
        $uploads = new \App\Services\UploadService();
        return Response::json(['data' => array_map(function (array $g) use ($uploads) {
            $g['cover_url'] = $uploads->url($g['cover_path']);
            unset($g['cover_path']);
            return $g;
        }, Database::all(
            'SELECT g.id, g.title, p.name AS platform, g.release_year, g.cover_path FROM games g JOIN platforms p ON p.id = g.platform_id
             WHERE g.user_id = ? ORDER BY g.title, g.release_year, g.id',
            [Auth::require()['id']]
        ))]);
    }

    /** Opções dos filtros do catálogo: só o que o próprio usuário já usou. */
    public function filters(Request $req): Response
    {
        $id = Auth::require()['id'];
        return Response::json([
            'missing' => array_map('intval', Database::one(
                'SELECT SUM(developer_id IS NULL) AS developer, SUM(publisher_id IS NULL) AS publisher, SUM(genre_id IS NULL) AS genre,
                        SUM(cover_path IS NULL) AS cover, SUM(release_year IS NULL) AS release_year FROM games WHERE user_id = ?', [$id]) ?? []),
            'platforms' => array_column(Database::all(
                'SELECT DISTINCT p.name FROM games g JOIN platforms p ON p.id = g.platform_id WHERE g.user_id = ? ORDER BY p.name', [$id]), 'name'),
            'genres' => array_column(Database::all(
                'SELECT DISTINCT ge.name FROM games g JOIN genres ge ON ge.id = g.genre_id
                 WHERE g.user_id = ? ORDER BY ge.name', [$id]), 'name'),
            'years' => array_map('intval', array_column(Database::all(
                'SELECT DISTINCT YEAR(finished_at) AS y FROM games WHERE user_id = ? ORDER BY y DESC', [$id]), 'y')),
            'release_years' => array_map('intval', array_column(Database::all(
                'SELECT DISTINCT release_year AS y FROM games WHERE user_id = ? AND release_year IS NOT NULL ORDER BY y DESC', [$id]), 'y')),
            'developers' => array_column(Database::all(
                'SELECT DISTINCT c.name FROM games g JOIN companies c ON c.id = g.developer_id WHERE g.user_id = ? ORDER BY c.name', [$id]), 'name'),
            'publishers' => array_column(Database::all(
                'SELECT DISTINCT c.name FROM games g JOIN companies c ON c.id = g.publisher_id WHERE g.user_id = ? ORDER BY c.name', [$id]), 'name'),
        ]);
    }

    /**
     * Agrupamentos das páginas Sistemas, Gêneros, Dificuldades, Ano de lançamento, Desenvolvedoras e Publishers.
     * GET /api/me/groups/{platform|genre|difficulty|release_year|developer|publisher}
     */
    public function groups(Request $req, array $p): Response
    {
        $id = Auth::require()['id'];
        $dim = $p['dim'];

        // expressão do grupo e join necessário para cada dimensão
        $map = [
            'platform'     => ['p.name',         'JOIN platforms p ON p.id = g.platform_id'],
            'genre'        => ['ge.name',        'JOIN genres ge ON ge.id = g.genre_id'],
            'difficulty'   => ['g.difficulty',   ''],
            'rating'       => ['g.rating',       ''],
            'release_year' => ['g.release_year', ''],
            'developer'    => ['c.name',         'JOIN companies c ON c.id = g.developer_id'],
            'publisher'    => ['c.name',         'JOIN companies c ON c.id = g.publisher_id'],
        ];
        if (!isset($map[$dim])) {
            return Response::error(404, 'Agrupamento desconhecido.');
        }
        [$expr, $join] = $map[$dim];

        $rows = Database::all(
            "SELECT $expr AS name, COUNT(*) AS games, COALESCE(SUM(g.playtime_minutes),0) AS minutes, AVG(g.rating) AS avg_rating,
                    MIN(YEAR(g.finished_at)) AS first_year, MAX(YEAR(g.finished_at)) AS last_year
             FROM games g $join WHERE g.user_id = ? AND $expr IS NOT NULL GROUP BY $expr",
            [$id]
        );

        // até 4 capas por grupo (as de maior nota primeiro)
        $covers = [];
        foreach (Database::all(
            "SELECT $expr AS name, g.cover_path FROM games g $join
             WHERE g.user_id = ? AND g.cover_path IS NOT NULL AND $expr IS NOT NULL ORDER BY g.rating DESC, g.id DESC",
            [$id]
        ) as $r) {
            if (count($covers[$r['name']] ?? []) < 4) {
                $covers[$r['name']][] = $r['cover_path'];
            }
        }

        // parceiras: para cada desenvolvedora, as publishers com quem mais lançou (e vice-versa)
        $partners = [];
        if ($dim === 'developer' || $dim === 'publisher') {
            [$mine, $other] = $dim === 'developer' ? ['developer_id', 'publisher_id'] : ['publisher_id', 'developer_id'];
            foreach (Database::all(
                "SELECT a.name AS name, b.name AS partner, COUNT(*) AS n FROM games g
                 JOIN companies a ON a.id = g.$mine JOIN companies b ON b.id = g.$other
                 WHERE g.user_id = ? AND g.$mine <> g.$other GROUP BY a.name, b.name ORDER BY n DESC, b.name",
                [$id]
            ) as $p) {
                if (count($partners[$p['name']] ?? []) < 3) {
                    $partners[$p['name']][] = ['name' => $p['partner'], 'games' => (int) $p['n']];
                }
            }
        }

        $uploads = new \App\Services\UploadService();
        $groups = array_map(fn(array $r) => [
            'value'      => (string) $r['name'],
            'games'      => (int) $r['games'],
            'hours'      => round($r['minutes'] / 60, 1),
            'avg_rating' => round((float) $r['avg_rating'], 1),
            'covers'     => array_map(fn($c) => $uploads->url($c), $covers[$r['name']] ?? []),
            'years'      => [(int) $r['first_year'], (int) $r['last_year']],
            'partners'   => $partners[$r['name']] ?? [],
        ], $rows);

        // best of: a escolha do usuário em cada grupo (sistema, gênero, desenvolvedora, publisher)
        if (in_array($dim, ['platform', 'genre', 'developer', 'publisher'], true)) {
            $best = (new \App\Services\BestOfService())->forDim($id, $dim);
            foreach ($groups as &$gr) {
                $gr['best'] = $best[\App\Core\Str::key($gr['value'])] ?? null;
            }
            unset($gr);
        }

        // dificuldade: sempre mostra os 5 níveis, mesmo sem jogos
        if ($dim === 'difficulty') {
            $have = array_column($groups, null, 'value');
            $groups = array_map(
                fn($d) => $have[$d] ?? ['value' => $d, 'games' => 0, 'hours' => 0, 'avg_rating' => 0, 'covers' => []],
                ['C', 'B', 'A', 'AA', 'AAA']
            );
        }

        // nota: sempre as 11, da maior para a menor
        if ($dim === 'rating') {
            $have = array_column($groups, null, 'value');
            $groups = array_map(fn($n) => $have[(string) $n] ?? ['value' => (string) $n, 'games' => 0, 'hours' => 0, 'avg_rating' => 0, 'covers' => []], range(11, 1));
        }

        $withData = (int) Database::value("SELECT COUNT(*) FROM games g $join WHERE g.user_id = ? AND $expr IS NOT NULL", [$id]);
        $all = (int) Database::value('SELECT COUNT(*) FROM games WHERE user_id = ?', [$id]);

        return Response::json([
            'data'    => $groups,
            'total'   => $all,
            'missing' => $all - $withData, // jogos sem esse dado (ex.: sem ano de lançamento)
            'quota'   => $dim === 'rating' ? (new \App\Services\RatingQuotaService())->status($id) : null,
            'best_min' => \App\Services\BestOfService::MIN_GAMES,
        ]);
    }

    public const THEMES = ['arcade', 'gameboy', 'cyberpunk', 'synthwave', 'horror', 'rpg', 'abyss', 'cozy', 'terminal', 'paper',
        'sakura', 'dungeon', 'galaxy', 'wasteland', 'frost', 'magma', 'sumi', 'candy', 'contrast', 'forest'];

    /** Salva o tema escolhido: {theme, fx}. */
    public function setTheme(Request $req): Response
    {
        $in = $req->input();
        $theme = (string) ($in['theme'] ?? '');
        if (!in_array($theme, self::THEMES, true)) {
            throw new \App\Core\HttpException(422, 'Tema inválido.', ['theme' => 'Tema desconhecido.']);
        }
        $fx = filter_var($in['fx'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        Database::query('UPDATE users SET theme = ?, theme_fx = ? WHERE id = ?', [$theme, $fx, Auth::require()['id']]);
        return Response::json(['theme' => $theme, 'fx' => (bool) $fx]);
    }

    public const LAYOUTS = ['case', 'shelf', 'terminal', 'mosaic'];

    /** Salva o visual do catálogo: {layout}. */
    public function setLayout(Request $req): Response
    {
        $layout = (string) ($req->input()['layout'] ?? '');
        if (!in_array($layout, self::LAYOUTS, true)) {
            throw new \App\Core\HttpException(422, 'Visual inválido.', ['layout' => 'Visual desconhecido.']);
        }
        Database::query('UPDATE users SET catalog_layout = ? WHERE id = ?', [$layout, Auth::require()['id']]);
        return Response::json(['layout' => $layout]);
    }

    /** Salva o painel montável: {order: [ids], hidden: [ids]}. */
    public function setDashboard(Request $req): Response
    {
        $layout = \App\Services\DashboardService::normalizeLayout($req->input());
        Database::query('UPDATE users SET dashboard_layout = ? WHERE id = ?', [json_encode($layout), Auth::require()['id']]);
        return Response::json($layout);
    }

    /** Liga/desliga o modo calmo: {calm: bool}. */
    public function setCalm(Request $req): Response
    {
        $calm = filter_var($req->input()['calm'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        Database::query('UPDATE users SET calm_mode = ? WHERE id = ?', [$calm, Auth::require()['id']]);
        return Response::json(['calm' => (bool) $calm]);
    }

    /** Dados dos blocos de história do dashboard (estante, faixa do ano, carta, constelação…). */
    public function dashboard(Request $req): Response
    {
        $user = Auth::require();
        $year = (int) ($req->query['year'] ?? date('Y'));
        $year = ($year >= 1970 && $year <= (int) date('Y') + 1) ? $year : (int) date('Y');
        return Response::json(['data' => (new \App\Services\DashboardService())->forUser($user['id'], $user['username'], $year)]);
    }

    /** GET /api/dashboard/soundtrack?year=AAAA: só a trilha do ano (trocar o ano não recarrega o painel todo). */
    public function dashboardSoundtrack(Request $req): Response
    {
        $user = Auth::require();
        $year = (int) ($req->query['year'] ?? date('Y'));
        return Response::json(['data' => (new \App\Services\DashboardService())->soundtrackFor($user['id'], $year)]);
    }

    public function dashboardSoundtrackDemo(Request $req): Response
    {
        $year = (int) ($req->query['year'] ?? date('Y'));
        $games = \App\Services\DemoCatalog::games((string) \App\Core\Config::get('base_path', ''));
        $inYear = array_values(array_filter($games, fn($g) => (int) substr($g['finished_at'], 0, 4) === $year));
        usort($inYear, fn($a, $b) => [$a['finished_at'], $a['id']] <=> [$b['finished_at'], $b['id']]);
        return Response::json(['data' => (new \App\Services\DashboardService())->soundtrack($inYear)]);
    }

    /** Os mesmos blocos sobre o catálogo fictício (página de exemplo; não exige login). */
    public function dashboardDemo(Request $req): Response
    {
        $year = (int) ($req->query['year'] ?? date('Y'));
        $base = (string) \App\Core\Config::get('base_path', '');
        $games = \App\Services\DemoCatalog::games($base);
        usort($games, fn($a, $b) => [$a['finished_at'], $a['id']] <=> [$b['finished_at'], $b['id']]);
        $notes = [];
        foreach (\App\Services\DemoCatalog::notes() as $gameId => $list) {
            foreach ($list as $n) {
                $notes[] = ['game_id' => $gameId, 'note_date' => $n['note_date'], 'body' => $n['body']];
            }
        }
        $playing = array_values(array_filter(\App\Services\DemoCatalog::backlog($base)['data'], fn($b) => $b['status'] === 'playing'));
        $playing[0]['last_note'] = ['date' => date('Y-m-d', strtotime('-12 days')), 'body' => 'Cheguei no terceiro dungeon. A trilha continua incrível.'];

        // honras de exemplo: GOTY de cada ano de lançamento e best of de cada sistema (o de maior nota, com 2+ jogos)
        $honors = [];
        foreach (['release_year' => 'GOTY ', 'platform' => 'Best of '] as $field => $prefix) {
            $by = [];
            foreach ($games as $g) {
                if (!empty($g[$field])) {
                    $by[(string) $g[$field]][] = $g;
                }
            }
            foreach ($by as $value => $list) {
                if (count($list) >= 2) {
                    usort($list, fn($a, $b) => $b['rating'] <=> $a['rating'] ?: $b['id'] <=> $a['id']);
                    $honors[] = ['game_id' => $list[0]['id'], 'kind' => $field === 'release_year' ? 'goty' : 'best', 'label' => $prefix . $value,
                        'note' => $field === 'release_year' ? 'Joguei sem parar. Não tinha como ser outro.' : null];
                }
            }
        }
        // próximos passos de exemplo, montados com os dados fictícios e apontando para as páginas de exemplo
        $next = [];
        $want = array_values(array_filter(\App\Services\DemoCatalog::backlog($base)['data'], fn($b) => $b['status'] === 'want'));
        if ($want && count($playing) < \App\Services\BacklogService::MAX_PLAYING) {
            $free = \App\Services\BacklogService::MAX_PLAYING - count($playing);
            $next[] = ['kind' => 'backlog', 'icon' => '🎮', 'title' => "$free vagas livres em \"jogando agora\"",
                'text' => 'Que tal começar ' . $want[0]['title'] . '? É o próximo da sua fila.', 'href' => $base . '/backlog/exemplo', 'cta' => 'Abrir o backlog'];
        }
        $fr = array_values(array_filter(\App\Services\DemoCatalog::franchiseList($base), fn($f) => $f['done'] > 0 && $f['total'] > $f['done']));
        usort($fr, fn($a, $b) => ($a['total'] - $a['done']) <=> ($b['total'] - $b['done']));
        if ($fr) {
            $miss = $fr[0]['total'] - $fr[0]['done'];
            $next[] = ['kind' => 'franchise', 'icon' => '🧩', 'title' => $fr[0]['name'] . ': ' . ($miss === 1 ? 'falta 1 jogo' : "faltam $miss jogos"),
                'text' => "Você já zerou {$fr[0]['done']} de {$fr[0]['total']}. Mais um pouco e a franquia fica completa.", 'href' => $base . '/exemplo/franquias/' . $fr[0]['id'], 'cta' => 'Ver o que falta'];
        }
        $next[] = ['kind' => 'goty', 'icon' => '⭐', 'title' => 'GOTY de 2024 ainda sem escolha', 'text' => 'Você zerou 6 jogos lançados em 2024. Qual foi o melhor?', 'href' => $base . '/goty/exemplo', 'cta' => 'Escolher o GOTY'];
        $next[] = ['kind' => 'best', 'icon' => '🏆', 'title' => 'Plataforma ainda sem best of', 'text' => 'São 5 jogos zerados ali. Qual é o melhor deles?', 'href' => $base . '/exemplo/generos', 'cta' => 'Escolher o best of'];

        $svc = new \App\Services\DashboardService();
        $data = $svc->compute($games, $notes, $playing, $year, 'Jogador Exemplo', date('Y-m-d'), $honors, $next);
        // o catálogo de exemplo termina em 15/10/2026: a "semana" do exemplo é a última semana dele
        $data['week'] = $svc->week($games, $notes, '2026-10-15');
        return Response::json(['data' => $data]);
    }

    /**
     * O que o site já sabe sobre um título (formulários de jogo, backlog e dropados): se já foi zerado, se está no backlog
     * ou nos dropados e de que franquias faz parte. GET /api/me/title-check?title=...
     */
    public function titleCheck(Request $req): Response
    {
        $id = Auth::require()['id'];
        $key = \App\Core\Str::key((string) ($req->query['title'] ?? ''));
        if (mb_strlen($key) < 2) {
            return Response::json(['data' => ['catalog' => [], 'backlog' => [], 'dropped' => [], 'franchises' => []]]);
        }
        $uploads = new \App\Services\UploadService();
        $catalog = array_map(fn($r) => ['id' => (int) $r['id'], 'title' => $r['title'], 'platform' => $r['platform'], 'finished_at' => $r['finished_at'],
            'release_year' => $r['release_year'] === null ? null : (int) $r['release_year'], 'rating' => (int) $r['rating'], 'cover_url' => $uploads->url($r['cover_path'])], Database::all(
            'SELECT g.id, g.title, p.name AS platform, g.finished_at, g.release_year, g.rating, g.cover_path FROM games g JOIN platforms p ON p.id = g.platform_id
             WHERE g.user_id = ? AND g.title_key = ? ORDER BY g.finished_at DESC LIMIT 10', [$id, $key]));
        $backlog = array_map(fn($r) => ['id' => (int) $r['id'], 'title' => $r['title'], 'status' => $r['status'], 'platform' => $r['platform']], Database::all(
            'SELECT b.id, b.title, b.status, p.name AS platform FROM backlog_games b LEFT JOIN platforms p ON p.id = b.platform_id
             WHERE b.user_id = ? AND b.title_key = ? LIMIT 5', [$id, $key]));
        $dropped = array_map(fn($r) => ['id' => (int) $r['id'], 'title' => $r['title'], 'dropped_at' => $r['dropped_at']], Database::all(
            'SELECT id, title, dropped_at FROM dropped_games WHERE user_id = ? AND title_key = ? LIMIT 5', [$id, $key]));

        // franquias com um item de mesmo título: progresso atual e se este jogo completaria a franquia
        $franchises = [];
        $fids = array_map('intval', array_column(Database::all(
            'SELECT DISTINCT f.id FROM franchise_entries e JOIN franchise_sections s ON s.id = e.section_id JOIN franchises f ON f.id = s.franchise_id
             WHERE f.user_id = ? AND e.title_key = ?', [$id, $key]), 'id'));
        if ($fids) {
            foreach ((new \App\Services\FranchiseService())->list($id) as $f) {
                if (in_array((int) $f['id'], $fids, true)) {
                    $franchises[] = ['id' => (int) $f['id'], 'name' => $f['name'], 'done' => $f['done'], 'total' => $f['total']];
                }
            }
        }
        return Response::json(['data' => compact('catalog', 'backlog', 'dropped', 'franchises')]);
    }

    /** Pontos da constelação (o maior pedaço do dashboard), pedidos só quando o bloco aparece na tela. */
    public function constellation(Request $req): Response
    {
        return Response::json(['data' => (new \App\Services\DashboardService())->constellationFor(Auth::require()['id'])]);
    }

    public function constellationDemo(Request $req): Response
    {
        $games = \App\Services\DemoCatalog::games((string) \App\Core\Config::get('base_path', ''));
        return Response::json(['data' => (new \App\Services\DashboardService())->constellation($games)]);
    }

    /** Cota de notas altas: jogos zerados, notas 10/11 usadas e quantos jogos liberam cada uma. */
    public function ratingQuota(Request $req): Response
    {
        return Response::json(['data' => (new \App\Services\RatingQuotaService())->status(Auth::require()['id'])]);
    }

    public function xpRules(Request $req): Response
    {
        return Response::json((new XpService())->rules());
    }

    /** Extrato de XP do usuário (mais recentes primeiro). */
    public function xpEvents(Request $req): Response
    {
        $rows = Database::all(
            'SELECT e.id, e.game_id, g.title AS game, e.source, e.amount, e.description, e.created_at
             FROM xp_events e JOIN games g ON g.id = e.game_id WHERE e.user_id = ? ORDER BY e.id DESC LIMIT 100',
            [Auth::require()['id']]
        );
        return Response::json(['data' => $rows]);
    }

    /** Autocomplete: /api/lookups/{platforms|genres|companies}?q=... */
    public function lookup(Request $req, array $p): Response
    {
        if (!in_array($p['type'], LookupService::TABLES, true)) {
            return Response::error(404, 'Tipo desconhecido.');
        }
        return Response::json(['data' => (new LookupService())->search($p['type'], (string) ($req->query['q'] ?? ''))]);
    }
}
