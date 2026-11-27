<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\{Database, HttpException, Str};

/** Backlog: jogos na fila ("quero jogar") e o que o usuário está jogando agora. */
final class BacklogService
{
    public const STATUSES = ['want', 'playing'];
    public const PRIORITIES = ['low', 'medium', 'high'];
    /** Quantos jogos podem estar em "jogando agora" ao mesmo tempo. */
    public const MAX_PLAYING = 4;

    public function __construct(
        private LookupService $lookup = new LookupService(),
        private UploadService $uploads = new UploadService(),
    ) {}

    // ---------- Leitura ----------

    /** @return array{data: list<array>, counts: array<string,int>} */
    public function list(int $userId): array
    {
        $rows = Database::all($this->select() . ' WHERE b.user_id = ? ORDER BY b.id DESC LIMIT 1000', [$userId]);
        $counts = ['want' => 0, 'playing' => 0];
        foreach ($rows as $r) {
            $counts[$r['status']]++;
        }
        $data = array_map(fn(array $r) => $this->format($r), $rows);

        // contexto: a última nota do diário de quem está em andamento e a franquia de que cada jogo faz parte
        $ids = array_column($data, 'id');
        $notes = $this->lastNotes($ids, 160);
        $franchise = [];
        foreach (Database::all(
            'SELECT e.title_key, f.id, f.name FROM franchise_entries e
             JOIN franchise_sections s ON s.id = e.section_id JOIN franchises f ON f.id = s.franchise_id
             WHERE f.user_id = ? AND e.title_key IN (SELECT title_key FROM backlog_games WHERE user_id = ?)',
            [$userId, $userId]
        ) as $f) {
            $franchise[$f['title_key']] ??= ['id' => (int) $f['id'], 'name' => $f['name']];
        }
        $keys = array_column($rows, 'title_key', 'id');
        foreach ($data as &$g) {
            $g['last_note'] = $notes[$g['id']] ?? null;
            $g['franchise'] = $franchise[$keys[$g['id']] ?? ''] ?? null;
        }
        unset($g);

        return ['data' => $data, 'counts' => $counts, 'limit' => self::MAX_PLAYING];
    }

    /**
     * Os jogos em andamento (cartão do dashboard), com a última nota do diário de cada um (last_note).
     * Quem recebeu uma anotação mais recentemente vem primeiro; quem não tem nota vem depois, do que começou há menos tempo.
     */
    public function playing(int $userId, int $limit = 6): array
    {
        $rows = Database::all(
            $this->select() . " WHERE b.user_id = ? AND b.status = 'playing' ORDER BY b.started_at DESC, b.id DESC",
            [$userId]
        );
        $data = array_map(fn(array $r) => $this->format($r), $rows);
        $notes = $this->lastNotes(array_column($data, 'id'));
        foreach ($data as &$g) {
            $g['last_note'] = $notes[$g['id']] ?? null;
        }
        unset($g);
        usort($data, fn($a, $b) => ($b['last_note']['id'] ?? 0) <=> ($a['last_note']['id'] ?? 0));   // estável: o resto mantém a ordem de início
        return array_slice($data, 0, max(1, min($limit, 50)));
    }

    /**
     * A última nota do diário de cada jogo do backlog: a escrita mais recentemente (maior id), mesmo que a data dela seja antiga.
     * @param list<int> $ids
     * @return array<int, array{id:int, date:string, body:string}> por id do backlog
     */
    private function lastNotes(array $ids, ?int $cut = null): array
    {
        $out = [];
        if (!$ids) {
            return $out;
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        foreach (Database::all("SELECT id, backlog_id, note_date, body FROM game_notes WHERE backlog_id IN ($in) ORDER BY id", array_values($ids)) as $n) {
            $body = $cut === null ? (string) $n['body'] : mb_strimwidth(trim((string) preg_replace('/\s+/', ' ', $n['body'])), 0, $cut, '…');
            $out[(int) $n['backlog_id']] = ['id' => (int) $n['id'], 'date' => $n['note_date'], 'body' => $body];   // a última da ordenação vence
        }
        return $out;
    }

    /** Quantos jogos estão em "jogando agora" (opcionalmente sem contar um deles). */
    public function playingCount(int $userId, ?int $exceptId = null): int
    {
        return (int) Database::value("SELECT COUNT(*) FROM backlog_games WHERE user_id = ? AND status = 'playing' AND id <> ?", [$userId, $exceptId ?? 0]);
    }

    /** Barra quem já está com todas as vagas de "jogando agora" ocupadas. */
    public function assertRoom(int $userId, ?int $exceptId = null): void
    {
        if ($this->playingCount($userId, $exceptId) >= self::MAX_PLAYING) {
            $n = self::MAX_PLAYING;
            throw new HttpException(422, "Você já está jogando $n jogos.", ['status' =>
                "Você já está jogando $n jogos ao mesmo tempo. Zere, drope ou devolva um à fila antes de começar outro."]);
        }
    }

    public function find(int $userId, int $id): array
    {
        $row = Database::one($this->select() . ' WHERE b.user_id = ? AND b.id = ?', [$userId, $id]);
        if ($row === null) {
            throw new HttpException(404, 'Jogo não encontrado no backlog.');
        }
        return $this->format($row);
    }

    // ---------- Escrita ----------

    public function create(int $userId, array $input, ?array $coverFile): array
    {
        $d = $this->validate($input);
        if ($d['status'] === 'playing') {
            $this->assertRoom($userId);
        }
        $cover = $coverFile ? $this->uploads->process($coverFile) : null;
        $coverPath = null;

        try {
            $id = Database::transaction(function () use ($userId, $d, $cover, &$coverPath) {
                try {
                    Database::query(
                        'INSERT INTO backlog_games (user_id, title, title_key, platform_id, genre_id, release_year, status, priority, started_at, note)
                         VALUES (?,?,?,?,?,?,?,?,?,?)',
                        [$userId, $d['title'], Str::key($d['title']), $this->platformId($d), $this->genreId($d),
                         $d['release_year'], $d['status'], $d['priority'], $d['started_at'], $d['note']]
                    );
                } catch (\PDOException $e) {
                    $this->rethrowDuplicate($e);
                }
                $id = Database::insertId();
                if ($cover) {
                    $coverPath = $this->uploads->store($cover);
                    Database::query('UPDATE backlog_games SET cover_path = ? WHERE id = ?', [$coverPath, $id]);
                }
                return $id;
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
        if ($d['status'] === 'playing') {
            $this->assertRoom($userId, $id);   // quem já estava jogando este não ocupa vaga nova
        }
        try {
            Database::query(
                'UPDATE backlog_games SET title=?, title_key=?, platform_id=?, genre_id=?, release_year=?, status=?, priority=?, started_at=?, note=?
                 WHERE id = ? AND user_id = ?',
                [$d['title'], Str::key($d['title']), $this->platformId($d), $this->genreId($d), $d['release_year'],
                 $d['status'], $d['priority'], $d['started_at'], $d['note'], $id, $userId]
            );
        } catch (\PDOException $e) {
            $this->rethrowDuplicate($e);
        }
        return $this->find($userId, $id);
    }

    /** "Comecei a jogar": vai para "jogando agora" com a data de hoje (ou a informada). */
    public function start(int $userId, int $id, mixed $date = null): array
    {
        $this->owned($userId, $id);
        $this->assertRoom($userId, $id);
        $day = $this->parseDate($date) ?? date('Y-m-d');
        Database::query("UPDATE backlog_games SET status = 'playing', started_at = ? WHERE id = ? AND user_id = ?", [$day, $id, $userId]);
        return $this->find($userId, $id);
    }

    /** Volta para a fila. */
    public function queue(int $userId, int $id): array
    {
        $this->owned($userId, $id);
        Database::query("UPDATE backlog_games SET status = 'want', started_at = NULL WHERE id = ? AND user_id = ?", [$id, $userId]);
        return $this->find($userId, $id);
    }

    public function setPriority(int $userId, int $id, mixed $priority): array
    {
        $this->owned($userId, $id);
        if (!in_array($priority, self::PRIORITIES, true)) {
            throw new HttpException(422, 'Prioridade inválida.', ['priority' => 'Use baixa, média ou alta.']);
        }
        Database::query('UPDATE backlog_games SET priority = ? WHERE id = ? AND user_id = ?', [$priority, $id, $userId]);
        return $this->find($userId, $id);
    }

    public function delete(int $userId, int $id): void
    {
        $row = $this->owned($userId, $id);
        $shots = ShotService::pathsOf('backlog_id', $id);   // ao zerar, as screenshots já passaram para o jogo
        Database::query('DELETE FROM backlog_games WHERE id = ? AND user_id = ?', [$id, $userId]);
        $this->uploads->delete($row['cover_path']);
        (new ShotService())->deleteFiles($shots);
    }

    public function setCover(int $userId, int $id, array $file): array
    {
        $row = $this->owned($userId, $id);
        $path = $this->uploads->store($this->uploads->process($file));
        Database::query('UPDATE backlog_games SET cover_path = ? WHERE id = ?', [$path, $id]);
        $this->uploads->delete($row['cover_path']);
        return $this->find($userId, $id);
    }

    public function removeCover(int $userId, int $id): array
    {
        $row = $this->owned($userId, $id);
        Database::query('UPDATE backlog_games SET cover_path = NULL WHERE id = ?', [$id]);
        $this->uploads->delete($row['cover_path']);
        return $this->find($userId, $id);
    }

    // ---------- Internos ----------

    private function owned(int $userId, int $id): array
    {
        $row = Database::one('SELECT id, cover_path FROM backlog_games WHERE id = ? AND user_id = ?', [$id, $userId]);
        if ($row === null) {
            throw new HttpException(404, 'Jogo não encontrado no backlog.');
        }
        return $row;
    }

    private function platformId(array $d): ?int
    {
        return $d['platform'] !== null ? $this->lookup->findOrCreate('platforms', $d['platform']) : null;
    }

    private function genreId(array $d): ?int
    {
        return $d['genre'] !== null ? $this->lookup->findOrCreate('genres', $d['genre']) : null;
    }

    private function select(): string
    {
        return 'SELECT b.id, b.title, b.title_key, b.release_year, b.status, b.priority, b.started_at, b.queued_at, b.note, b.cover_path, b.created_at,
                       p.name AS platform, ge.name AS genre
                FROM backlog_games b
                LEFT JOIN platforms p ON p.id = b.platform_id
                LEFT JOIN genres ge ON ge.id = b.genre_id';
    }

    private function format(array $r): array
    {
        $started = $r['started_at'];
        return [
            'id' => (int) $r['id'], 'title' => $r['title'], 'platform' => $r['platform'], 'genre' => $r['genre'],
            'release_year' => $r['release_year'] === null ? null : (int) $r['release_year'],
            'status' => $r['status'], 'priority' => $r['priority'], 'started_at' => $started, 'note' => $r['note'],
            'added_at' => substr((string) $r['created_at'], 0, 10),
            'queued_at' => $r['queued_at'] ?? substr((string) $r['created_at'], 0, 10), // desde quando espera na fila
            'days_playing' => $started ? max(0, (int) floor((strtotime('today') - strtotime($started)) / 86400)) : null,
            'cover_url' => $this->uploads->url($r['cover_path']),
        ];
    }

    private function rethrowDuplicate(\PDOException $e): never
    {
        if (Database::isDuplicate($e)) {
            throw new HttpException(409, 'Este jogo já está no seu backlog.');
        }
        throw $e;
    }

    private function parseDate(mixed $v): ?string
    {
        if (!is_string($v) || $v === '') {
            return null;
        }
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $v);
        if ($d === false || $d->format('Y-m-d') !== $v || $d > new \DateTimeImmutable('today')) {
            throw new HttpException(422, 'Data inválida.', ['started_at' => 'Data inválida (AAAA-MM-DD, não pode ser futura).']);
        }
        return $v;
    }

    private function validate(array $in): array
    {
        $e = [];

        $title = Str::clean($in['title'] ?? '');
        if ($title === '' || mb_strlen($title) > 200) {
            $e['title'] = 'Informe o título (até 200 caracteres).';
        }
        $platform = Str::clean($in['platform'] ?? '');
        if (mb_strlen($platform) > 60) {
            $e['platform'] = 'Máximo de 60 caracteres.';
        }
        $genre = Str::clean($in['genre'] ?? '');
        if (mb_strlen($genre) > 50) {
            $e['genre'] = 'Máximo de 50 caracteres.';
        }
        $yearRaw = $in['release_year'] ?? null;
        $year = ($yearRaw === null || $yearRaw === '') ? null : filter_var($yearRaw, FILTER_VALIDATE_INT);
        if ($year === false || ($year !== null && ($year < 1950 || $year > (int) date('Y') + 2))) {
            $e['release_year'] = 'Ano de lançamento inválido.';
            $year = null;
        }

        $status = (string) ($in['status'] ?? 'want');
        if (!in_array($status, self::STATUSES, true)) {
            $e['status'] = 'Escolha "quero jogar" ou "jogando agora".';
        }
        $priority = (string) ($in['priority'] ?? 'medium');
        if (!in_array($priority, self::PRIORITIES, true)) {
            $e['priority'] = 'Prioridade: baixa, média ou alta.';
        }

        $started = null;
        if ($status === 'playing') {
            try {
                $started = $this->parseDate($in['started_at'] ?? null) ?? date('Y-m-d');
            } catch (HttpException $ex) {
                $e += $ex->errors;
            }
        }

        $note = Str::clean($in['note'] ?? '');
        if (mb_strlen($note) > 300) {
            $e['note'] = 'A anotação pode ter até 300 caracteres.';
        }

        if ($e) {
            throw new HttpException(422, 'Dados inválidos.', $e);
        }
        return [
            'title' => $title, 'platform' => $platform === '' ? null : $platform, 'genre' => $genre === '' ? null : $genre,
            'release_year' => $year, 'status' => $status, 'priority' => $priority, 'started_at' => $started, 'note' => $note === '' ? null : $note,
        ];
    }
}
