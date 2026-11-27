<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\{Database, HttpException, Str};

/** Tudo da página "Minha conta": nome de usuário, exportação e exclusão. */
final class AccountService
{
    // ---------- Validações ----------

    private function usernameProblem(string $username): ?string
    {
        return preg_match('/^[A-Za-z0-9_]{3,30}$/', $username) ? null : '3 a 30 caracteres: letras, números e _.';
    }

    // ---------- Visão geral ----------

    public function overview(int $userId): array
    {
        $u = Database::one('SELECT username, avatar, created_at, level, total_xp, profile_public FROM users WHERE id = ?', [$userId]);
        return [
            'user' => [
                'username' => $u['username'], 'avatar' => (new AvatarService())->present($u['avatar']), 'created_at' => $u['created_at'],
                'level' => (int) $u['level'], 'total_xp' => (int) $u['total_xp'], 'profile_public' => (bool) $u['profile_public'],
            ],
            'counts' => [
                'games'      => (int) Database::value('SELECT COUNT(*) FROM games WHERE user_id = ?', [$userId]),
                'dropped'    => (int) Database::value('SELECT COUNT(*) FROM dropped_games WHERE user_id = ?', [$userId]),
                'franchises' => (int) Database::value('SELECT COUNT(*) FROM franchises WHERE user_id = ?', [$userId]),
                'covers'     => (int) Database::value(
                    'SELECT (SELECT COUNT(*) FROM games WHERE user_id = ? AND cover_path IS NOT NULL) + (SELECT COUNT(*) FROM dropped_games WHERE user_id = ? AND cover_path IS NOT NULL)',
                    [$userId, $userId]
                ),
            ],
        ];
    }

    // ---------- Alterações ----------

    public function changeUsername(int $userId, string $username): array
    {
        $username = Str::clean($username);
        if ($msg = $this->usernameProblem($username)) {
            throw new HttpException(422, 'Nome de usuário inválido.', ['username' => $msg]);
        }
        try {
            Database::query('UPDATE users SET username = ? WHERE id = ?', [$username, $userId]);
        } catch (\PDOException $e) {
            if (Database::isDuplicate($e)) {
                throw new HttpException(409, 'Esse nome de usuário já está em uso.', ['username' => 'Já está em uso.']);
            }
            throw $e;
        }
        return ['username' => $username];
    }

    // ---------- Exclusão ----------

    public function deleteAccount(int $userId, string $confirm): void
    {
        $username = (string) Database::value('SELECT username FROM users WHERE id = ?', [$userId]);
        if (mb_strtolower(trim($confirm)) !== mb_strtolower($username)) {
            throw new HttpException(422, 'Confirmação incorreta.', ['confirm' => 'Digite o seu nome de usuário para confirmar.']);
        }

        $covers = array_column(Database::all(
            'SELECT cover_path FROM games WHERE user_id = ? AND cover_path IS NOT NULL
             UNION SELECT cover_path FROM dropped_games WHERE user_id = ? AND cover_path IS NOT NULL
             UNION SELECT cover_path FROM backlog_games WHERE user_id = ? AND cover_path IS NOT NULL
             UNION SELECT cover_path FROM franchises WHERE user_id = ? AND cover_path IS NOT NULL
             UNION SELECT hero_path FROM franchises WHERE user_id = ? AND hero_path IS NOT NULL', [$userId, $userId, $userId, $userId, $userId]
        ), 'cover_path');
        $shots = ShotService::pathsOf('user_id', $userId);
        $avatar = Database::value('SELECT avatar FROM users WHERE id = ?', [$userId]);

        Database::query('DELETE FROM users WHERE id = ?', [$userId]); // cascata: jogos, dropados, franquias, GOTY, retrospectiva, logins...
        $uploads = new UploadService();
        foreach ($covers as $path) {
            $uploads->delete($path);
        }
        (new ShotService())->deleteFiles($shots);
        (new AvatarService())->deleteFile(is_string($avatar) ? $avatar : null);
    }

    // ---------- Exportação ----------

    /** Tudo o que o usuário tem no site, em uma estrutura só. */
    public function exportAll(int $userId): array
    {
        $u = Database::one('SELECT username, created_at, level, total_xp, theme FROM users WHERE id = ?', [$userId]);

        $tasks = [];
        foreach (Database::all('SELECT t.game_id, t.description, t.tier FROM game_tasks t JOIN games g ON g.id = t.game_id WHERE g.user_id = ? ORDER BY t.id', [$userId]) as $t) {
            $tasks[$t['game_id']][] = ['description' => $t['description'], 'tier' => (int) $t['tier']];
        }
        $games = array_map(fn(array $g) => [
            'title' => $g['title'], 'platform' => $g['platform'], 'developer' => $g['developer'], 'publisher' => $g['publisher'], 'genre' => $g['genre'],
            'release_year' => $g['release_year'] === null ? null : (int) $g['release_year'], 'finished_at' => $g['finished_at'],
            'playtime_minutes' => (int) $g['playtime_minutes'], 'rating' => (int) $g['rating'], 'difficulty' => $g['difficulty'],
            'xp' => (int) $g['xp'], 'tasks' => $tasks[$g['id']] ?? [],
        ], $this->gameRows($userId));

        $franchises = [];
        $svc = new FranchiseService();
        foreach ($svc->list($userId) as $f) {
            $full = $svc->find($userId, $f['id']);
            $franchises[] = [
                'name' => $full['name'], 'description' => $full['description'],
                'sections' => array_map(fn($s) => [
                    'name' => $s['name'],
                    'entries' => array_map(fn($e) => [
                        'title' => $e['title'], 'release_year' => $e['release_year'], 'done' => $e['done'], 'matched_game' => $e['game']['title'] ?? null,
                    ], $s['entries']),
                ], $full['sections']),
            ];
        }

        return [
            'exported_at' => date('c'),
            'app' => 'MyGameList',
            'user' => ['username' => $u['username'], 'member_since' => $u['created_at'], 'level' => (int) $u['level'], 'total_xp' => (int) $u['total_xp'], 'theme' => $u['theme']],
            'games' => $games,
            'dropped' => array_map(fn($d) => [
                'title' => $d['title'], 'platform' => $d['platform'], 'genre' => $d['genre'], 'release_year' => $d['release_year'],
                'playtime_minutes' => $d['playtime_minutes'], 'dropped_at' => $d['dropped_at'], 'return_chance' => $d['return_chance'], 'reason' => $d['reason'],
            ], (new DroppedService())->list($userId)['data']),
            'franchises' => $franchises,
            'goty' => array_map(fn($r) => ['year' => (int) $r['year'], 'game' => $r['title'], 'note' => $r['note']], Database::all(
                'SELECT p.year, p.note, g.title FROM goty_picks p JOIN games g ON g.id = p.game_id WHERE p.user_id = ? ORDER BY p.year', [$userId])),
            'retrospective_top3' => array_map(fn($r) => ['year' => (int) $r['year'], 'position' => (int) $r['position'], 'game' => $r['title']], Database::all(
                'SELECT t.year, t.position, g.title FROM retro_top t JOIN games g ON g.id = t.game_id WHERE t.user_id = ? ORDER BY t.year, t.position', [$userId])),
        ];
    }

    private function gameRows(int $userId): array
    {
        return Database::all(
            'SELECT g.id, g.title, g.finished_at, g.release_year, g.playtime_minutes, g.rating, g.difficulty,
                    p.name AS platform, d.name AS developer, pb.name AS publisher, ge.name AS genre,
                    COALESCE((SELECT SUM(e.amount) FROM xp_events e WHERE e.game_id = g.id), 0) AS xp
             FROM games g JOIN platforms p ON p.id = g.platform_id
             LEFT JOIN companies d ON d.id = g.developer_id LEFT JOIN companies pb ON pb.id = g.publisher_id LEFT JOIN genres ge ON ge.id = g.genre_id
             WHERE g.user_id = ? ORDER BY g.finished_at, g.id',
            [$userId]
        );
    }

    /** CSV (UTF-8 com BOM, separado por ";" para abrir direto no Excel em português). */
    public function exportCsv(int $userId, string $kind): string
    {
        if ($kind === 'dropped') {
            $head = ['Título', 'Sistema', 'Gênero', 'Ano de lançamento', 'Tempo jogado (min)', 'Dropado em', 'Chance de voltar', 'Motivo'];
            $chance = ['low' => 'Baixa', 'medium' => 'Média', 'high' => 'Alta'];
            $rows = array_map(fn($d) => [$d['title'], $d['platform'], $d['genre'], $d['release_year'], $d['playtime_minutes'], $d['dropped_at'], $chance[$d['return_chance']], $d['reason']],
                (new DroppedService())->list($userId)['data']);
        } else {
            $head = ['Título', 'Sistema', 'Desenvolvedora', 'Publisher', 'Gênero', 'Ano de lançamento', 'Finalizado em', 'Tempo de jogo (min)', 'Nota', 'Dificuldade', 'XP', 'Tarefas'];
            $tasks = [];
            foreach (Database::all('SELECT t.game_id, t.description, t.tier FROM game_tasks t JOIN games g ON g.id = t.game_id WHERE g.user_id = ? ORDER BY t.id', [$userId]) as $t) {
                $tasks[$t['game_id']][] = $t['description'] . ' (tier ' . $t['tier'] . ')';
            }
            $rows = array_map(fn($g) => [
                $g['title'], $g['platform'], $g['developer'], $g['publisher'], $g['genre'], $g['release_year'], $g['finished_at'],
                $g['playtime_minutes'], $g['rating'], $g['difficulty'], $g['xp'], implode(' | ', $tasks[$g['id']] ?? []),
            ], $this->gameRows($userId));
        }

        $line = fn(array $cells) => implode(';', array_map([self::class, 'csvCell'], $cells)) . "\r\n";
        $out = "\xEF\xBB\xBF" . $line($head);
        foreach ($rows as $r) {
            $out .= $line($r);
        }
        return $out;
    }

    /** Escapa uma célula e neutraliza fórmulas (=, +, -, @) para não executarem ao abrir na planilha. */
    public static function csvCell(mixed $v): string
    {
        $s = (string) ($v ?? '');
        if ($s !== '' && in_array($s[0], ['=', '+', '-', '@', "\t", "\r"], true) && !is_numeric($s)) {
            $s = "'" . $s;
        }
        return '"' . str_replace('"', '""', $s) . '"';
    }
}
