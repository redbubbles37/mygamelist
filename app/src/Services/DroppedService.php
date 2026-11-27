<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\{Database, HttpException, Str};

/** Jogos dropados (começados e abandonados) com a chance de o usuário voltar a jogá-los. */
final class DroppedService
{
    public const CHANCES = ['low', 'medium', 'high'];

    public function __construct(
        private LookupService $lookup = new LookupService(),
        private UploadService $uploads = new UploadService(),
    ) {}

    // ---------- Leitura ----------

    /** @return array{data: list<array>, counts: array<string,int>} */
    public function list(int $userId): array
    {
        $rows = Database::all($this->select() . ' WHERE d.user_id = ? ORDER BY d.dropped_at DESC, d.id DESC LIMIT 1000', [$userId]);
        $counts = ['low' => 0, 'medium' => 0, 'high' => 0];
        foreach ($rows as $r) {
            $counts[$r['return_chance']]++;
        }
        return ['data' => array_map(fn(array $r) => $this->format($r), $rows), 'counts' => $counts];
    }

    public function find(int $userId, int $id): array
    {
        $row = Database::one($this->select() . ' WHERE d.user_id = ? AND d.id = ?', [$userId, $id]);
        if ($row === null) {
            throw new HttpException(404, 'Jogo não encontrado na lista de dropados.');
        }
        return $this->format($row);
    }

    // ---------- Escrita ----------

    public function create(int $userId, array $input, ?array $coverFile): array
    {
        // um dropado só nasce de um jogo do backlog ("Dropei")
        $from = ctype_digit((string) ($input['from_backlog_id'] ?? '')) ? Database::one(
            'SELECT id, cover_path, started_at, COALESCE(queued_at, DATE(created_at)) AS queued_at FROM backlog_games WHERE id = ? AND user_id = ?',
            [(int) $input['from_backlog_id'], $userId]
        ) : null;
        if ($from === null) {
            throw new HttpException(422, 'Um jogo só pode ser dropado a partir do backlog.');
        }
        $d = $this->validate($input);
        $cover = $coverFile ? $this->uploads->process($coverFile) : null;
        $keepBacklogCover = !filter_var($input['skip_backlog_cover'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $coverPath = null;

        try {
            $id = Database::transaction(function () use ($userId, $d, $cover, $from, $keepBacklogCover, &$coverPath) {
                try {
                    Database::query(
                        'INSERT INTO dropped_games (user_id, title, title_key, platform_id, genre_id, release_year, playtime_minutes, dropped_at, queued_at, started_at, return_chance, reason)
                         VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
                        [$userId, $d['title'], Str::key($d['title']), $this->lookup->findOrCreate('platforms', $d['platform']),
                         $d['genre'] !== null ? $this->lookup->findOrCreate('genres', $d['genre']) : null,
                         $d['release_year'], $d['playtime_minutes'], $d['dropped_at'], $from['queued_at'], $from['started_at'], $d['return_chance'], $d['reason']]
                    );
                } catch (\PDOException $e) {
                    $this->rethrowDuplicate($e);
                }
                $id = Database::insertId();
                // capa nova, ou uma cópia própria da capa do backlog (o item do backlog é apagado logo abaixo)
                $coverPath = $cover ? $this->uploads->store($cover) : ($keepBacklogCover ? $this->uploads->copy($from['cover_path']) : null);
                if ($coverPath) {
                    Database::query('UPDATE dropped_games SET cover_path = ? WHERE id = ?', [$coverPath, $id]);
                }
                // o diário (notas e screenshots) acompanha o jogo
                Database::query('UPDATE game_notes SET dropped_id = ?, backlog_id = NULL WHERE backlog_id = ? AND user_id = ?', [$id, $from['id'], $userId]);
                Database::query('UPDATE game_shots SET dropped_id = ?, backlog_id = NULL WHERE backlog_id = ? AND user_id = ?', [$id, $from['id'], $userId]);
                Database::query('DELETE FROM backlog_games WHERE id = ? AND user_id = ?', [$from['id'], $userId]);
                return $id;
            });
        } catch (\Throwable $e) {
            $this->uploads->delete($coverPath);
            throw $e;
        }
        $this->uploads->delete($from['cover_path']);
        return $this->find($userId, $id);
    }

    public function update(int $userId, int $id, array $input): array
    {
        $this->owned($userId, $id);
        $d = $this->validate($input);
        try {
            Database::query(
                'UPDATE dropped_games SET title=?, title_key=?, platform_id=?, genre_id=?, release_year=?, playtime_minutes=?, dropped_at=?, return_chance=?, reason=?
                 WHERE id = ? AND user_id = ?',
                [$d['title'], Str::key($d['title']), $this->lookup->findOrCreate('platforms', $d['platform']),
                 $d['genre'] !== null ? $this->lookup->findOrCreate('genres', $d['genre']) : null,
                 $d['release_year'], $d['playtime_minutes'], $d['dropped_at'], $d['return_chance'], $d['reason'], $id, $userId]
            );
        } catch (\PDOException $e) {
            $this->rethrowDuplicate($e);
        }
        return $this->find($userId, $id);
    }

    /** "Voltei a jogar": o dropado vira "jogando agora" no backlog (mantém a capa) e sai da lista de dropados. */
    public function resume(int $userId, int $id): array
    {
        $row = Database::one(
            'SELECT id, title, title_key, platform_id, genre_id, release_year, playtime_minutes, reason, cover_path, queued_at FROM dropped_games WHERE id = ? AND user_id = ?',
            [$id, $userId]
        );
        if ($row === null) {
            throw new HttpException(404, 'Jogo não encontrado na lista de dropados.');
        }
        (new BacklogService())->assertRoom($userId);
        $hours = (int) round($row['playtime_minutes'] / 60);
        $note = 'Retomado depois de dropar' . ($hours > 0 ? " (já tinha $hours h)" : '') . '.';

        $backlogId = Database::transaction(function () use ($userId, $row, $note, $id) {
            try {
                Database::query(
                    "INSERT INTO backlog_games (user_id, title, title_key, platform_id, genre_id, release_year, status, priority, started_at, queued_at, note, cover_path)
                     VALUES (?,?,?,?,?,?, 'playing', 'medium', CURDATE(), ?, ?, ?)",
                    [$userId, $row['title'], $row['title_key'], $row['platform_id'], $row['genre_id'], $row['release_year'], $row['queued_at'], $note, $row['cover_path']]
                );
            } catch (\PDOException $e) {
                if (Database::isDuplicate($e)) {
                    throw new HttpException(409, 'Este jogo já está no seu backlog.');
                }
                throw $e;
            }
            $new = Database::insertId();
            // o diário volta junto para o backlog
            Database::query('UPDATE game_notes SET backlog_id = ?, dropped_id = NULL WHERE dropped_id = ? AND user_id = ?', [$new, $id, $userId]);
            Database::query('UPDATE game_shots SET backlog_id = ?, dropped_id = NULL WHERE dropped_id = ? AND user_id = ?', [$new, $id, $userId]);
            Database::query('DELETE FROM dropped_games WHERE id = ? AND user_id = ?', [$id, $userId]); // a capa passou para o backlog: não apaga o arquivo
            return $new;
        });
        return (new BacklogService())->find($userId, $backlogId);
    }

    /** Troca só a chance de voltar (atalho do cartão). */
    public function setChance(int $userId, int $id, mixed $chance): array
    {
        $this->owned($userId, $id);
        if (!in_array($chance, self::CHANCES, true)) {
            throw new HttpException(422, 'Chance inválida.', ['return_chance' => 'Use baixa, média ou alta.']);
        }
        Database::query('UPDATE dropped_games SET return_chance = ? WHERE id = ? AND user_id = ?', [$chance, $id, $userId]);
        return $this->find($userId, $id);
    }

    public function delete(int $userId, int $id): void
    {
        $row = $this->owned($userId, $id);
        $shots = ShotService::pathsOf('dropped_id', $id);
        Database::query('DELETE FROM dropped_games WHERE id = ? AND user_id = ?', [$id, $userId]);
        $this->uploads->delete($row['cover_path']);
        (new ShotService())->deleteFiles($shots);
    }

    public function setCover(int $userId, int $id, array $file): array
    {
        $row = $this->owned($userId, $id);
        $path = $this->uploads->store($this->uploads->process($file));
        Database::query('UPDATE dropped_games SET cover_path = ? WHERE id = ?', [$path, $id]);
        $this->uploads->delete($row['cover_path']);
        return $this->find($userId, $id);
    }

    public function removeCover(int $userId, int $id): array
    {
        $row = $this->owned($userId, $id);
        Database::query('UPDATE dropped_games SET cover_path = NULL WHERE id = ?', [$id]);
        $this->uploads->delete($row['cover_path']);
        return $this->find($userId, $id);
    }

    // ---------- Internos ----------

    private function owned(int $userId, int $id): array
    {
        $row = Database::one('SELECT id, cover_path FROM dropped_games WHERE id = ? AND user_id = ?', [$id, $userId]);
        if ($row === null) {
            throw new HttpException(404, 'Jogo não encontrado na lista de dropados.');
        }
        return $row;
    }

    private function select(): string
    {
        return 'SELECT d.id, d.title, d.release_year, d.playtime_minutes, d.dropped_at, d.return_chance, d.reason, d.cover_path,
                       p.name AS platform, ge.name AS genre
                FROM dropped_games d
                JOIN platforms p ON p.id = d.platform_id
                LEFT JOIN genres ge ON ge.id = d.genre_id';
    }

    private function format(array $r): array
    {
        return [
            'id' => (int) $r['id'], 'title' => $r['title'], 'platform' => $r['platform'], 'genre' => $r['genre'],
            'release_year' => $r['release_year'] === null ? null : (int) $r['release_year'],
            'playtime_minutes' => (int) $r['playtime_minutes'], 'dropped_at' => $r['dropped_at'],
            'return_chance' => $r['return_chance'], 'reason' => $r['reason'], 'cover_url' => $this->uploads->url($r['cover_path']),
        ];
    }

    private function rethrowDuplicate(\PDOException $e): never
    {
        if (Database::isDuplicate($e)) {
            throw new HttpException(409, 'Este jogo já está na sua lista de dropados neste sistema.');
        }
        throw $e;
    }

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
        $genre = Str::clean($in['genre'] ?? '');
        if (mb_strlen($genre) > 50) {
            $e['genre'] = 'Máximo de 50 caracteres.';
        }

        $yearRaw = $in['release_year'] ?? null;
        $year = ($yearRaw === null || $yearRaw === '') ? null : filter_var($yearRaw, FILTER_VALIDATE_INT);
        if ($year === false || ($year !== null && ($year < 1950 || $year > (int) date('Y') + 1))) {
            $e['release_year'] = 'Ano de lançamento inválido.';
            $year = null;
        }

        if (isset($in['playtime_minutes']) && $in['playtime_minutes'] !== '') {
            $minutes = filter_var($in['playtime_minutes'], FILTER_VALIDATE_INT);
        } elseif (isset($in['playtime_hours']) && is_numeric($in['playtime_hours'])) {
            $minutes = (int) round((float) $in['playtime_hours'] * 60);
        } else {
            $minutes = 0; // não lembra / não anotou
        }
        if ($minutes === false || $minutes < 0 || $minutes > 1_000_000) {
            $e['playtime_minutes'] = 'Tempo de jogo inválido.';
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) ($in['dropped_at'] ?? ''));
        if ($date === false || $date->format('Y-m-d') !== $in['dropped_at'] || $date > new \DateTimeImmutable('today')) {
            $e['dropped_at'] = 'Data inválida (AAAA-MM-DD, não pode ser futura).';
        }

        $chance = (string) ($in['return_chance'] ?? '');
        if (!in_array($chance, self::CHANCES, true)) {
            $e['return_chance'] = 'Escolha a chance de voltar a jogar: baixa, média ou alta.';
        }

        $reason = Str::clean($in['reason'] ?? '');
        if (mb_strlen($reason) > 300) {
            $e['reason'] = 'O motivo pode ter até 300 caracteres.';
        }

        if ($e) {
            throw new HttpException(422, 'Dados inválidos.', $e);
        }
        return [
            'title' => $title, 'platform' => $platform, 'genre' => $genre === '' ? null : $genre, 'release_year' => $year,
            'playtime_minutes' => $minutes, 'dropped_at' => $date->format('Y-m-d'), 'return_chance' => $chance,
            'reason' => $reason === '' ? null : $reason,
        ];
    }
}
