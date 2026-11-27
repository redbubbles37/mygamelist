<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Auth, Database, Request, Response};
use App\Services\{FranchiseService, GameService, GotyService, RetrospectiveService, UploadService, XpService};

/** Parte social: ranking entre usuários e perfil (visível só para quem está logado, respeitando a privacidade). */
final class CommunityController
{
    private const METRICS = ['xp', 'games', 'hours'];

    /**
     * GET /api/ranking?metric=xp|games|hours&period=all|AAAA
     * Mostra os perfis públicos (e você mesmo, mesmo se estiver oculto).
     */
    public function ranking(Request $req): Response
    {
        $me = Auth::require();
        $metric = in_array($req->query['metric'] ?? '', self::METRICS, true) ? $req->query['metric'] : 'xp';
        $year = filter_var($req->query['period'] ?? null, FILTER_VALIDATE_INT);
        $year = ($year !== false && $year >= 1970 && $year <= 2100) ? $year : null;

        $params = [];
        $gamesWhere = $xpWhere = '';
        if ($year !== null) {
            $gamesWhere = ' WHERE YEAR(finished_at) = ?';
            $xpWhere = ' WHERE YEAR(g.finished_at) = ?';
        }
        $sql = 'SELECT u.id, u.username, u.level, u.total_xp, u.profile_public,
                       COALESCE(gs.games, 0) AS games, COALESCE(gs.minutes, 0) AS minutes, gs.avg_rating,
                       ' . ($year !== null ? 'COALESCE(xs.xp, 0)' : 'u.total_xp') . ' AS xp
                FROM users u
                LEFT JOIN (SELECT user_id, COUNT(*) AS games, SUM(playtime_minutes) AS minutes, AVG(rating) AS avg_rating
                           FROM games' . $gamesWhere . ' GROUP BY user_id) gs ON gs.user_id = u.id';
        if ($year !== null) {
            $params[] = $year;
            $sql .= ' LEFT JOIN (SELECT g.user_id, SUM(e.amount) AS xp FROM xp_events e JOIN games g ON g.id = e.game_id'
                  . $xpWhere . ' GROUP BY g.user_id) xs ON xs.user_id = u.id';
            $params[] = $year;
        }
        $sql .= ' WHERE u.profile_public = 1 OR u.id = ? LIMIT 500';
        $params[] = $me['id'];

        $found = Database::all($sql, $params);

        // o último jogo zerado de cada um e quantos zerou nos últimos 30 dias ("em alta")
        $ids = array_column($found, 'id');
        $last = $hot = [];
        if ($ids) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            $uploads = new UploadService();
            foreach (Database::all(
                "SELECT g.user_id, g.title, g.cover_path, g.finished_at FROM games g
                 WHERE g.user_id IN ($in) AND g.id = (SELECT g2.id FROM games g2 WHERE g2.user_id = g.user_id ORDER BY g2.finished_at DESC, g2.id DESC LIMIT 1)",
                $ids
            ) as $g) {
                $last[$g['user_id']] = ['title' => $g['title'], 'cover_url' => $uploads->url($g['cover_path']), 'finished_at' => $g['finished_at']];
            }
            $hot = array_column(Database::all(
                "SELECT user_id, COUNT(*) AS n FROM games WHERE user_id IN ($in) AND finished_at >= date('now', 'localtime', '-30 days') GROUP BY user_id",
                $ids
            ), 'n', 'user_id');
        }

        $rows = array_map(fn(array $r) => [
            'username'   => $r['username'],
            'last'       => $last[$r['id']] ?? null,
            'month'      => (int) ($hot[$r['id']] ?? 0),
            'level'      => (int) $r['level'],
            'xp'         => (int) $r['xp'],
            'games'      => (int) $r['games'],
            'hours'      => round($r['minutes'] / 60, 1),
            'avg_rating' => $r['avg_rating'] === null ? null : round((float) $r['avg_rating'], 1),
            'is_me'      => $r['id'] === $me['id'],
            'hidden'     => !$r['profile_public'], // só pode ser true para você mesmo
        ], $found);

        $value = fn(array $r) => $r[$metric];
        usort($rows, fn($a, $b) => $value($b) <=> $value($a) ?: strcasecmp($a['username'], $b['username']));

        // empates dividem a mesma posição (1, 2, 2, 4...)
        foreach ($rows as $i => &$r) {
            $r['rank'] = ($i > 0 && $value($r) === $value($rows[$i - 1])) ? $rows[$i - 1]['rank'] : $i + 1;
        }
        unset($r);

        $years = array_map('intval', array_column(Database::all(
            'SELECT DISTINCT YEAR(g.finished_at) AS y FROM games g JOIN users u ON u.id = g.user_id
             WHERE u.profile_public = 1 OR u.id = ? ORDER BY y DESC', [$me['id']]), 'y'));
        if (!in_array((int) date('Y'), $years, true)) {
            array_unshift($years, (int) date('Y'));
        }

        return Response::json(['data' => $rows, 'metric' => $metric, 'period' => $year, 'years' => $years]);
    }

    /** GET /api/users/{username}: perfil de um usuário (ou o seu). */
    public function profile(Request $req, array $p): Response
    {
        $viewer = Auth::require();
        $u = Database::one(
            'SELECT id, username, level, total_xp, profile_public, created_at FROM users WHERE username = ?',
            [$p['username']]
        );
        // perfil oculto some para os outros, como se não existisse
        if ($u === null || (!$u['profile_public'] && $u['id'] !== $viewer['id'])) {
            return Response::error(404, 'Perfil não encontrado.');
        }
        $uid = $u['id'];
        $isMe = $uid === $viewer['id'];

        $totals = Database::one(
            'SELECT COUNT(*) AS games, COALESCE(SUM(playtime_minutes), 0) AS minutes, AVG(rating) AS avg_rating FROM games WHERE user_id = ?',
            [$uid]
        );
        $rank = 1 + (int) Database::value('SELECT COUNT(*) FROM users WHERE profile_public = 1 AND total_xp > ?', [$u['total_xp']]);
        $players = (int) Database::value('SELECT COUNT(*) FROM users WHERE profile_public = 1');

        $games = new GameService();
        $card = fn(array $g) => [
            'id'               => $isMe ? $g['id'] : null, // só o dono pode abrir o jogo
            'title'            => $g['title'],
            'platform'         => $g['platform'],
            'genre'            => $g['genre'],
            'finished_at'      => $g['finished_at'],
            'playtime_minutes' => $g['playtime_minutes'],
            'rating'           => $g['rating'],
            'difficulty'       => $g['difficulty'],
            'cover_url'        => $g['cover_url'],
        ];
        $recent = array_map($card, $games->list($uid, ['sort' => 'finished_at', 'dir' => 'desc', 'per_page' => 12])['data']);
        $favorites = array_map($card, $games->list($uid, ['sort' => 'rating', 'dir' => 'desc', 'per_page' => 6])['data']);

        $franchises = array_map(fn(array $f) => [
            'name' => $f['name'], 'done' => $f['done'], 'total' => $f['total'],
        ], (new FranchiseService())->list($uid));
        usort($franchises, fn($a, $b) => ($b['total'] ? $b['done'] / $b['total'] : 0) <=> ($a['total'] ? $a['done'] / $a['total'] : 0));

        $common = null;
        if (!$isMe) {
            $common = [
                'count' => (int) Database::value(
                    'SELECT COUNT(DISTINCT a.title_key) FROM games a JOIN games b ON b.title_key = a.title_key AND b.user_id = ? WHERE a.user_id = ?',
                    [$viewer['id'], $uid]
                ),
                'titles' => array_column(Database::all(
                    'SELECT MIN(a.title) AS title FROM games a JOIN games b ON b.title_key = a.title_key AND b.user_id = ?
                     WHERE a.user_id = ? GROUP BY a.title_key ORDER BY MAX(a.rating) DESC, title LIMIT 8',
                    [$viewer['id'], $uid]
                ), 'title'),
            ];
        }

        // o "DNA gamer": gêneros, sistemas, dificuldades e ritmo por ano
        $top = fn(string $join, string $col) => array_map(fn($r) => ['name' => $r['name'], 'n' => (int) $r['n']], Database::all(
            "SELECT $col AS name, COUNT(*) AS n FROM games g $join WHERE g.user_id = ? GROUP BY $col ORDER BY n DESC, name LIMIT 5",
            [$uid]
        ));
        $dna = [
            'genres'    => $top('JOIN genres x ON x.id = g.genre_id', 'x.name'),
            'platforms' => $top('JOIN platforms x ON x.id = g.platform_id', 'x.name'),
            'difficulty' => array_column(Database::all('SELECT difficulty AS d, COUNT(*) AS n FROM games WHERE user_id = ? GROUP BY difficulty', [$uid]), 'n', 'd'),
            'years'     => array_map(fn($r) => ['year' => (int) $r['y'], 'n' => (int) $r['n']], Database::all(
                'SELECT YEAR(finished_at) AS y, COUNT(*) AS n FROM games WHERE user_id = ? GROUP BY y ORDER BY y', [$uid]
            )),
        ];

        // lado a lado com quem está vendo (só no perfil dos outros)
        $versus = null;
        if (!$isMe) {
            $v = Database::one(
                'SELECT COUNT(*) AS games, COALESCE(SUM(playtime_minutes), 0) AS minutes, AVG(rating) AS avg_rating FROM games WHERE user_id = ?',
                [$viewer['id']]
            );
            $vu = Database::one('SELECT username, total_xp FROM users WHERE id = ?', [$viewer['id']]);
            $versus = [
                'username' => $vu['username'],
                'level' => (new XpService())->progress((int) $vu['total_xp'])['level'],
                'total_xp' => (int) $vu['total_xp'],
                'games' => (int) $v['games'],
                'hours' => round($v['minutes'] / 60, 1),
                'avg_rating' => $v['avg_rating'] === null ? null : round((float) $v['avg_rating'], 1),
            ];
        }

        return Response::json(['data' => [
            'username'     => $u['username'],
            'is_me'        => $isMe,
            'profile_public' => $isMe ? (bool) $u['profile_public'] : null,
            'member_since' => substr((string) $u['created_at'], 0, 10),
            'xp'           => (new XpService())->progress((int) $u['total_xp']),
            'rank'         => $rank,
            'players'      => $players,
            'games'        => (int) $totals['games'],
            'hours'        => round($totals['minutes'] / 60, 1),
            'avg_rating'   => $totals['avg_rating'] === null ? null : round((float) $totals['avg_rating'], 1),
            'franchises'   => array_slice($franchises, 0, 6),
            'franchises_done' => count(array_filter($franchises, fn($f) => $f['total'] > 0 && $f['done'] === $f['total'])),
            'franchises_total' => count($franchises),
            'goty'         => (new GotyService())->picks($uid),
            'retro'        => (new RetrospectiveService())->tops($uid),
            'recent'       => $recent,
            'favorites'    => $favorites,
            'common'       => $common,
            'dna'          => $dna,
            'versus'       => $versus,
        ]]);
    }

    /** PUT /api/me/privacy {profile_public: bool} */
    public function privacy(Request $req): Response
    {
        $public = filter_var($req->input()['profile_public'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        Database::query('UPDATE users SET profile_public = ? WHERE id = ?', [$public, Auth::require()['id']]);
        return Response::json(['profile_public' => (bool) $public]);
    }
}
