<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\{Database, HttpException, Str};

final class GameService
{
    private const DIFFICULTIES = ['C', 'B', 'A', 'AA', 'AAA'];
    private const SORTS = [
        'finished_at' => 'g.finished_at',
        'title'       => 'g.title',
        'rating'      => 'g.rating',
        'playtime'    => 'g.playtime_minutes',
        'difficulty'  => "FIELD(g.difficulty,'C','B','A','AA','AAA')",
        'created'     => 'g.id',
        'release'     => 'g.release_year',
    ];

    public function __construct(
        private XpService $xp = new XpService(),
        private LookupService $lookup = new LookupService(),
        private UploadService $uploads = new UploadService(),
    ) {}

    // ---------- Leitura ----------

    public function list(int $userId, array $q): array
    {
        $where  = ['g.user_id = ?'];
        $params = [$userId];

        if (($t = Str::clean($q['q'] ?? '')) !== '') {
            // busca no título e também no sistema, gênero, desenvolvedora e publisher
            $like = '%' . addcslashes(Str::key($t), '%_\\') . '%';
            $where[]  = '(g.title_key LIKE ? OR p.name_key LIKE ? OR ge.name_key LIKE ? OR d.name_key LIKE ? OR pb.name_key LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like);
        }
        if (($v = Str::clean($q['platform'] ?? '')) !== '') {
            $where[]  = 'p.name_key = ?';
            $params[] = Str::key($v);
        }
        if (($v = Str::clean($q['genre'] ?? '')) !== '') {
            $where[]  = 'ge.name_key = ?';
            $params[] = Str::key($v);
        }
        if (in_array($q['difficulty'] ?? null, self::DIFFICULTIES, true)) {
            $where[]  = 'g.difficulty = ?';
            $params[] = $q['difficulty'];
        }
        if (ctype_digit((string) ($q['year'] ?? ''))) {
            $where[]  = 'YEAR(g.finished_at) = ?';
            $params[] = (int) $q['year'];
        }
        if (ctype_digit((string) ($q['rating'] ?? '')) && (int) $q['rating'] >= 1 && (int) $q['rating'] <= 11) {
            $where[]  = 'g.rating = ?';
            $params[] = (int) $q['rating'];
        }
        if (ctype_digit((string) ($q['release_year'] ?? ''))) {
            $where[]  = 'g.release_year = ?';
            $params[] = (int) $q['release_year'];
        }
        foreach (['developer' => 'd', 'publisher' => 'pb'] as $field => $alias) {
            if (($v = Str::clean($q[$field] ?? '')) !== '') {
                $where[]  = "$alias.name_key = ?";
                $params[] = Str::key($v);
            }
        }
        // jogos com dado faltando (para completar em massa)
        $missing = self::MISSING[$q['missing'] ?? ''] ?? null;
        if ($missing !== null) {
            $where[] = $missing;
        }
        // hall da fama: GOTY ou best of
        $honors = null;
        if (!empty($q['honors']) || ($q['view'] ?? '') === 'grid') {
            $honors = $this->honorMap($userId);
        }
        if (!empty($q['honors'])) {
            $ids = array_map('intval', array_keys($honors));
            $where[] = $ids ? 'g.id IN (' . implode(',', $ids) . ')' : '1 = 0';
        }

        $sort    = self::SORTS[$q['sort'] ?? ''] ?? self::SORTS['finished_at'];
        $dir     = strtolower((string) ($q['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
        // a lista enxuta da grade é leve: aceita lotes maiores (menos requisições para catálogos grandes)
        $perPage = max(1, min(($q['view'] ?? '') === 'grid' ? 250 : 100, (int) ($q['per_page'] ?? 24)));
        $page    = max(1, (int) ($q['page'] ?? 1));
        $whereSql = implode(' AND ', $where);

        $total = (int) Database::value(
            "SELECT COUNT(*) FROM games g JOIN platforms p ON p.id = g.platform_id LEFT JOIN genres ge ON ge.id = g.genre_id
             LEFT JOIN companies d ON d.id = g.developer_id LEFT JOIN companies pb ON pb.id = g.publisher_id WHERE $whereSql",
            $params
        );
        $grid = ($q['view'] ?? '') === 'grid';
        $rows = Database::all(
            ($grid ? $this->gridSelect() : $this->baseSelect()) . " WHERE $whereSql ORDER BY $sort $dir, g.id DESC LIMIT $perPage OFFSET " . (($page - 1) * $perPage),
            $params
        );

        $meta = ['page' => $page, 'per_page' => $perPage, 'total' => $total, 'pages' => (int) ceil($total / $perPage)];
        if ($page === 1) {
            // resumo do recorte inteiro (não só desta página) e o best of da categoria filtrada
            $sum = Database::one(
                "SELECT COUNT(*) AS n, COALESCE(SUM(g.playtime_minutes), 0) AS minutes, AVG(g.rating) AS avg_rating
                 FROM games g JOIN platforms p ON p.id = g.platform_id LEFT JOIN genres ge ON ge.id = g.genre_id
                 LEFT JOIN companies d ON d.id = g.developer_id LEFT JOIN companies pb ON pb.id = g.publisher_id WHERE $whereSql",
                $params
            );
            $meta['summary'] = ['games' => (int) $sum['n'], 'minutes' => (int) $sum['minutes'], 'avg_rating' => $sum['avg_rating'] === null ? null : round((float) $sum['avg_rating'], 1)];
            foreach (['platform', 'genre', 'developer', 'publisher'] as $dim) {
                if (($v = Str::clean($q[$dim] ?? '')) !== '') {
                    $best = (new BestOfService($this->uploads))->forDim($userId, $dim)[Str::key($v)] ?? null;
                    $meta['best'] = ['kind' => $dim, 'value' => $v, 'game' => $best];
                    break;
                }
            }
        }

        return [
            'data' => $grid ? $this->hydrateGrid($rows, $honors ?? []) : $this->hydrate($rows),
            'meta' => $meta,
        ];
    }

    /** Filtros "faltando dados" do catálogo. */
    public const MISSING = [
        'developer'    => 'g.developer_id IS NULL',
        'publisher'    => 'g.publisher_id IS NULL',
        'genre'        => 'g.genre_id IS NULL',
        'cover'        => 'g.cover_path IS NULL',
        'release_year' => 'g.release_year IS NULL',
    ];

    /** @return array<int, list<string>> id do jogo => honras ("GOTY 2015", "Best of PS2"...) */
    public function honorMap(int $userId): array
    {
        $map = [];
        foreach ((new GotyService($this->uploads))->picks($userId) as $p) {
            $map[(int) $p['game']['id']][] = 'GOTY ' . $p['year'];
        }
        foreach ((new BestOfService($this->uploads))->honors($userId) as $h) {
            $map[$h['game_id']][] = $h['label'];
        }
        return $map;
    }

    /** Só o que a grade do catálogo usa (sem diário, tarefas, memórias e XP). */
    private function gridSelect(): string
    {
        return 'SELECT g.id, g.title, g.finished_at, g.release_year, g.playtime_minutes, g.rating, g.difficulty, g.cover_path,
                       p.name AS platform, ge.name AS genre
                FROM games g
                JOIN platforms p ON p.id = g.platform_id
                LEFT JOIN companies d  ON d.id  = g.developer_id
                LEFT JOIN companies pb ON pb.id = g.publisher_id
                LEFT JOIN genres ge    ON ge.id  = g.genre_id';
    }

    private function hydrateGrid(array $rows, array $honors): array
    {
        return array_map(fn(array $r) => [
            'id' => (int) $r['id'], 'title' => $r['title'], 'platform' => $r['platform'], 'genre' => $r['genre'],
            'finished_at' => $r['finished_at'], 'release_year' => $r['release_year'] === null ? null : (int) $r['release_year'],
            'playtime_minutes' => (int) $r['playtime_minutes'], 'rating' => (int) $r['rating'], 'difficulty' => $r['difficulty'],
            'cover_url' => $this->uploads->url($r['cover_path']),
            'honors' => $honors[(int) $r['id']] ?? [],
        ], $rows);
    }

    /** Todos os jogos do usuário em ordem cronológica (livro e exportações). */
    public function allChronological(int $userId): array
    {
        return $this->hydrate(Database::all($this->baseSelect() . ' WHERE g.user_id = ? ORDER BY g.finished_at, g.id LIMIT 5000', [$userId]));
    }

    public function find(int $userId, int $id): array
    {
        $row = Database::one($this->baseSelect() . ' WHERE g.user_id = ? AND g.id = ?', [$userId, $id]);
        if ($row === null) {
            throw new HttpException(404, 'Jogo não encontrado.');
        }
        return $this->hydrate([$row])[0];
    }

    // ---------- Escrita ----------

    public function create(int $userId, array $input, ?array $coverFile): array
    {
        $d = $this->validate($input);
        (new RatingQuotaService())->check($userId, $d['rating'], 1);   // o próprio jogo entra na conta
        $cover = $coverFile ? $this->uploads->process($coverFile) : null;
        $coverPath = null;

        try {
            $id = Database::transaction(function () use ($userId, $input, $d, $cover, &$coverPath) {
                $ids = $this->resolveLookups($d);
                try {
                    Database::query(
                        'INSERT INTO games (user_id, title, title_key, platform_id, developer_id, publisher_id, genre_id, finished_at, started_at, release_year, playtime_minutes, rating, difficulty,
                                          intro_by, intro_where, intro_age, played_where, music_title, music_url, used_save_states)
                         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                        [$userId, $d['title'], Str::key($d['title']), $ids['platform'], $ids['developer'], $ids['publisher'], $ids['genre'],
                         $d['finished_at'], $d['started_at'], $d['release_year'], $d['playtime_minutes'], $d['rating'], $d['difficulty'],
                         $d['intro_by'], $d['intro_where'], $d['intro_age'], $d['played_where'], $d['music_title'], $d['music_url'], $d['save_states'] ? 1 : 0]
                    );
                } catch (\PDOException $e) {
                    $this->rethrowDuplicate($e);
                }
                $gameId = Database::insertId();
                $this->syncTasks($gameId, $d);
                $this->syncPeople($gameId, $d['people']);
                if (ctype_digit((string) ($input['from_backlog_id'] ?? ''))) { // o diário de "jogando agora" passa a ser do jogo zerado
                    Database::query('UPDATE game_notes SET game_id = ?, backlog_id = NULL WHERE backlog_id = ? AND user_id = ?', [$gameId, (int) $input['from_backlog_id'], $userId]);
                    Database::query('UPDATE game_shots SET game_id = ?, backlog_id = NULL WHERE backlog_id = ? AND user_id = ?', [$gameId, (int) $input['from_backlog_id'], $userId]);
                    // o Diário continua sabendo quando o jogo entrou na fila, mesmo depois de o item do backlog ser apagado
                    Database::query('UPDATE games SET queued_at = (SELECT COALESCE(queued_at, DATE(created_at)) FROM backlog_games WHERE id = ? AND user_id = ?) WHERE id = ?',
                        [(int) $input['from_backlog_id'], $userId, $gameId]);
                }
                if ($cover) {
                    $coverPath = $this->uploads->store($cover);
                    Database::query('UPDATE games SET cover_path = ? WHERE id = ?', [$coverPath, $gameId]);
                } elseif (ctype_digit((string) ($input['from_backlog_id'] ?? '')) && !filter_var($input['skip_backlog_cover'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                    // sem capa nova: reaproveita a do backlog (cópia própria, porque o item do backlog é apagado em seguida)
                    $coverPath = $this->uploads->copy(Database::value('SELECT cover_path FROM backlog_games WHERE id = ? AND user_id = ?', [(int) $input['from_backlog_id'], $userId]));
                    if ($coverPath) {
                        Database::query('UPDATE games SET cover_path = ? WHERE id = ?', [$coverPath, $gameId]);
                    }
                }
                $this->xp->rebuildGame($userId, $gameId, $d['difficulty'], $d['tasks'], $d['save_states']);
                return $gameId;
            });
        } catch (\Throwable $e) {
            $this->uploads->delete($coverPath);
            throw $e;
        }

        return $this->find($userId, $id);
    }

    public function update(int $userId, int $id, array $input): array
    {
        $this->owned($userId, $id);
        $d = $this->validate($input);
        $before = (int) Database::value('SELECT rating FROM games WHERE id = ? AND user_id = ?', [$id, $userId]);
        if ($d['rating'] !== $before) {   // quem já tinha a nota continua com ela
            (new RatingQuotaService())->check($userId, $d['rating']);
        }

        Database::transaction(function () use ($userId, $id, $d) {
            $ids = $this->resolveLookups($d);
            try {
                Database::query(
                    'UPDATE games SET title=?, title_key=?, platform_id=?, developer_id=?, publisher_id=?, genre_id=?, finished_at=?, started_at=?, release_year=?, playtime_minutes=?, rating=?, difficulty=?,
                            intro_by=?, intro_where=?, intro_age=?, played_where=?, music_title=?, music_url=?, used_save_states=?
                     WHERE id = ? AND user_id = ?',
                    [$d['title'], Str::key($d['title']), $ids['platform'], $ids['developer'], $ids['publisher'], $ids['genre'],
                     $d['finished_at'], $d['started_at'], $d['release_year'], $d['playtime_minutes'], $d['rating'], $d['difficulty'],
                     $d['intro_by'], $d['intro_where'], $d['intro_age'], $d['played_where'], $d['music_title'], $d['music_url'], $d['save_states'] ? 1 : 0, $id, $userId]
                );
            } catch (\PDOException $e) {
                $this->rethrowDuplicate($e);
            }
            $this->syncTasks($id, $d);
            $this->syncPeople($id, $d['people']);
            $this->xp->rebuildGame($userId, $id, $d['difficulty'], $d['tasks'], $d['save_states']);
        });

        return $this->find($userId, $id);
    }

    public function delete(int $userId, int $id): void
    {
        $game = $this->owned($userId, $id);
        $shots = ShotService::pathsOf('game_id', $id);
        Database::transaction(function () use ($userId, $id) {
            Database::query('DELETE FROM games WHERE id = ? AND user_id = ?', [$id, $userId]); // cascata: gêneros, tarefas, xp, notas, screenshots
            $this->xp->refreshUser($userId);
        });
        $this->uploads->delete($game['cover_path']);
        (new ShotService())->deleteFiles($shots);
    }

    public function setCover(int $userId, int $id, array $file): array
    {
        $game = $this->owned($userId, $id);
        $path = $this->uploads->store($this->uploads->process($file));
        Database::query('UPDATE games SET cover_path = ? WHERE id = ?', [$path, $id]);
        $this->uploads->delete($game['cover_path']);
        return $this->find($userId, $id);
    }

    public function removeCover(int $userId, int $id): array
    {
        $game = $this->owned($userId, $id);
        Database::query('UPDATE games SET cover_path = NULL WHERE id = ?', [$id]);
        $this->uploads->delete($game['cover_path']);
        return $this->find($userId, $id);
    }

    // ---------- Internos ----------

    private function owned(int $userId, int $id): array
    {
        $row = Database::one('SELECT id, cover_path FROM games WHERE id = ? AND user_id = ?', [$id, $userId]);
        if ($row === null) {
            throw new HttpException(404, 'Jogo não encontrado.');
        }
        return $row;
    }

    private function baseSelect(): string
    {
        return 'SELECT g.id, g.title, g.finished_at, g.started_at, g.release_year, g.playtime_minutes, g.rating, g.difficulty, g.used_save_states, g.cover_path,
                       g.intro_by, g.intro_where, g.intro_age, g.played_where, g.music_title, g.music_url,
                       p.name AS platform, d.name AS developer, pb.name AS publisher, ge.name AS genre,
                       COALESCE((SELECT SUM(e.amount) FROM xp_events e WHERE e.game_id = g.id), 0) AS xp
                FROM games g
                JOIN platforms p ON p.id = g.platform_id
                LEFT JOIN companies d  ON d.id  = g.developer_id
                LEFT JOIN companies pb ON pb.id = g.publisher_id
                LEFT JOIN genres ge    ON ge.id  = g.genre_id';
    }

    /** Anexa gêneros e tarefas e formata o payload público. */
    private function hydrate(array $rows): array
    {
        if (!$rows) {
            return [];
        }
        $ids = array_column($rows, 'id');
        $in  = implode(',', array_fill(0, count($ids), '?'));

        $tasks = [];
        foreach (Database::all("SELECT id, game_id, description, tier FROM game_tasks WHERE game_id IN ($in) ORDER BY id", $ids) as $r) {
            $tasks[$r['game_id']][] = ['id' => $r['id'], 'description' => $r['description'], 'tier' => $r['tier']];
        }

        $people = [];
        foreach (Database::all("SELECT game_id, name FROM game_people WHERE game_id IN ($in) ORDER BY id", $ids) as $r) {
            $people[$r['game_id']][] = $r['name'];
        }

        return array_map(fn(array $r) => [
            'id'               => $r['id'],
            'title'            => $r['title'],
            'platform'         => $r['platform'],
            'developer'        => $r['developer'],
            'publisher'        => $r['publisher'],
            'genre'            => $r['genre'],
            'finished_at'      => $r['finished_at'],
            'started_at'       => $r['started_at'],
            'release_year'     => $r['release_year'],
            'playtime_minutes' => $r['playtime_minutes'],
            'rating'           => $r['rating'],
            'difficulty'       => $r['difficulty'],
            'save_states'      => (bool) $r['used_save_states'],
            'cover_url'        => $this->uploads->url($r['cover_path']),
            'tasks'            => $tasks[$r['id']] ?? [],
            'memory'           => [
                'intro_by' => $r['intro_by'], 'intro_where' => $r['intro_where'],
                'intro_age' => $r['intro_age'] === null ? null : (int) $r['intro_age'],
                'played_where' => $r['played_where'], 'music_title' => $r['music_title'], 'music_url' => $r['music_url'],
                'people' => $people[$r['id']] ?? [],
            ],
            'xp'               => (int) $r['xp'],
        ], $rows);
    }

    private function resolveLookups(array $d): array
    {
        return [
            'platform'  => $this->lookup->findOrCreate('platforms', $d['platform']),
            'developer' => $d['developer'] !== null ? $this->lookup->findOrCreate('companies', $d['developer']) : null,
            'publisher' => $d['publisher'] !== null ? $this->lookup->findOrCreate('companies', $d['publisher']) : null,
            'genre'     => $this->lookup->findOrCreate('genres', $d['genre']),
        ];
    }

    private function syncTasks(int $gameId, array $d): void
    {
        Database::query('DELETE FROM game_tasks WHERE game_id = ?', [$gameId]);
        foreach ($d['tasks'] as $t) {
            Database::query('INSERT INTO game_tasks (game_id, description, tier) VALUES (?,?,?)', [$gameId, $t['description'], $t['tier']]);
        }
    }

    private function syncPeople(int $gameId, array $people): void
    {
        Database::query('DELETE FROM game_people WHERE game_id = ?', [$gameId]);
        foreach ($people as $name) {
            Database::query('INSERT OR IGNORE INTO game_people (game_id, name) VALUES (?, ?)', [$gameId, $name]);
        }
    }

    private function rethrowDuplicate(\PDOException $e): never
    {
        if (Database::isDuplicate($e)) {
            throw new HttpException(409, 'Você já registrou este jogo neste sistema com este ano de lançamento.');
        }
        throw $e;
    }

    /** Valida e normaliza o payload (criação e edição). */
    private function validate(array $in): array
    {
        $e = [];

        $title = Str::clean($in['title'] ?? '');
        if ($title === '' || mb_strlen($title) > 200) {
            $e['title'] = 'Informe o título (até 200 caracteres).';
        }

        $platform = Str::clean($in['platform'] ?? '');
        if ($platform === '' || mb_strlen($platform) > 60) {
            $e['platform'] = 'Informe o sistema (até 60 caracteres).';
        }

        $company = function (string $field) use ($in, &$e): ?string {
            $v = Str::clean($in[$field] ?? '');
            if (mb_strlen($v) > 100) {
                $e[$field] = 'Máximo de 100 caracteres.';
            }
            return $v === '' ? null : $v;
        };
        $developer = $company('developer');
        $publisher = $company('publisher');

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) ($in['finished_at'] ?? ''));
        if ($date === false || $date->format('Y-m-d') !== $in['finished_at'] || $date > new \DateTimeImmutable('today')) {
            $e['finished_at'] = 'Data inválida (AAAA-MM-DD, não pode ser futura).';
        }

        $startRaw = trim((string) ($in['started_at'] ?? ''));
        $started = null;
        if ($startRaw !== '') {
            $sd = \DateTimeImmutable::createFromFormat('!Y-m-d', $startRaw);
            if ($sd === false || $sd->format('Y-m-d') !== $startRaw || $sd > new \DateTimeImmutable('today')) {
                $e['started_at'] = 'Data de início inválida (não pode ser futura).';
            } elseif ($date !== false && $sd > $date) {
                $e['started_at'] = 'O início não pode ser depois da finalização.';
            } else {
                $started = $sd->format('Y-m-d');
            }
        }

        if (isset($in['playtime_minutes'])) {
            $minutes = filter_var($in['playtime_minutes'], FILTER_VALIDATE_INT);
        } elseif (isset($in['playtime_hours']) && is_numeric($in['playtime_hours'])) {
            $minutes = (int) round((float) $in['playtime_hours'] * 60);
        } else {
            $minutes = false;
        }
        if ($minutes === false || $minutes < 0 || $minutes > 1_000_000) {
            $e['playtime_minutes'] = 'Informe o tempo de jogo (minutos ou horas).';
        }

        $rating = filter_var($in['rating'] ?? null, FILTER_VALIDATE_INT);
        if ($rating === false || $rating < 1 || $rating > 11) {
            $e['rating'] = 'Nota de 1 a 11.';
        }

        $difficulty = (string) ($in['difficulty'] ?? '');
        if (!in_array($difficulty, self::DIFFICULTIES, true)) {
            $e['difficulty'] = 'Dificuldade deve ser C, B, A, AA ou AAA.';
        }

        $year = filter_var($in['release_year'] ?? null, FILTER_VALIDATE_INT);
        if ($year === false || $year < 1950 || $year > (int) date('Y')) {
            $e['release_year'] = 'Informe um ano de lançamento válido (de 1950 até o ano atual).';
        } elseif ($date !== false && $year > (int) $date->format('Y')) {
            $e['release_year'] = 'O ano de lançamento não pode ser depois da finalização.';
        }

        $genre = Str::clean($in['genre'] ?? '');
        if ($genre === '' || mb_strlen($genre) > 50) {
            $e['genre'] = 'Informe o gênero (até 50 caracteres).';
        }

        $tasks = [];
        foreach ((array) ($in['tasks'] ?? []) as $t) {
            $desc = Str::clean(is_array($t) ? ($t['description'] ?? '') : '');
            $tier = filter_var(is_array($t) ? ($t['tier'] ?? null) : null, FILTER_VALIDATE_INT);
            if ($desc === '' || mb_strlen($desc) > 200 || $tier === false || $tier < 1 || $tier > 3) {
                $e['tasks'] = 'Cada tarefa precisa de descrição (até 200 caracteres) e tier de 1 a 3.';
                break;
            }
            $tasks[] = ['description' => $desc, 'tier' => $tier];
        }
        if (count($tasks) > 20) {
            $e['tasks'] = 'Máximo de 20 tarefas.';
        }

        $saveStates = filter_var($in['used_save_states'] ?? false, FILTER_VALIDATE_BOOLEAN);

        // memórias (tudo opcional)
        $memory = function (string $field) use ($in, &$e): ?string {
            $v = Str::clean($in[$field] ?? '');
            if (mb_strlen($v) > 100) {
                $e[$field] = 'Máximo de 100 caracteres.';
            }
            return $v === '' ? null : $v;
        };
        $introBy = $memory('intro_by');
        $introWhere = $memory('intro_where');
        $playedWhere = $memory('played_where');
        $musicTitle = Str::clean($in['music_title'] ?? '');
        if (mb_strlen($musicTitle) > 150) {
            $e['music_title'] = 'Máximo de 150 caracteres.';
        }
        $musicUrl = trim((string) ($in['music_url'] ?? ''));
        if ($musicUrl !== '' && (mb_strlen($musicUrl) > 300 || !filter_var($musicUrl, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $musicUrl))) {
            $e['music_url'] = 'Use um link completo que comece com http:// ou https:// (até 300 caracteres).';
        }
        $ageRaw = $in['intro_age'] ?? null;
        $introAge = ($ageRaw === null || $ageRaw === '') ? null : filter_var($ageRaw, FILTER_VALIDATE_INT);
        if ($introAge === false || ($introAge !== null && ($introAge < 0 || $introAge > 120))) {
            $e['intro_age'] = 'Idade de 0 a 120.';
            $introAge = null;
        }
        $people = [];
        $seen = [];
        foreach ((array) ($in['people'] ?? []) as $p) {
            $name = Str::clean($p);
            if ($name === '' || isset($seen[Str::key($name)])) {
                continue;
            }
            if (mb_strlen($name) > 60) {
                $e['people'] = 'Cada nome pode ter até 60 caracteres.';
                break;
            }
            $seen[Str::key($name)] = true;
            $people[] = $name;
        }
        if (count($people) > 15) {
            $e['people'] = 'Máximo de 15 pessoas.';
        }

        if ($e) {
            throw new HttpException(422, 'Dados inválidos.', $e);
        }

        return [
            'save_states' => $saveStates,
            'intro_by' => $introBy, 'intro_where' => $introWhere, 'intro_age' => $introAge, 'played_where' => $playedWhere, 'people' => $people,
            'music_title' => $musicTitle === '' ? null : $musicTitle, 'music_url' => $musicUrl === '' ? null : $musicUrl,
            'title' => $title, 'platform' => $platform, 'developer' => $developer, 'publisher' => $publisher,
            'finished_at' => $date->format('Y-m-d'), 'started_at' => $started, 'release_year' => $year, 'playtime_minutes' => $minutes, 'rating' => $rating,
            'difficulty' => $difficulty, 'genre' => $genre, 'tasks' => $tasks,
        ];
    }
}
