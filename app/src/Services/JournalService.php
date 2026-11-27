<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\{Config, Database, HttpException};

/**
 * Diário: uma linha do tempo única com tudo o que aconteceu na vida gamer.
 * Junta jogos que entraram na fila, começados, zerados, dropados, notas do diário de bordo,
 * screenshots soltas e registros livres do dia (journal_entries).
 *
 * Formato devolvido: { games: {ref: jogo}, events: [...], targets: [...] }
 *   ref = "g<id>" (catálogo), "b<id>" (backlog) ou "d<id>" (dropados)
 *   evento = { key, type, date, ref|null, ...campos do tipo }
 *   type: queued | started | finished | dropped | note | shots | entry
 */
final class JournalService
{
    /** Ordem dentro do mesmo dia (o maior aparece primeiro). */
    private const WEIGHT = ['queued' => 1, 'started' => 2, 'note' => 3, 'shots' => 4, 'entry' => 5, 'dropped' => 6, 'finished' => 7];

    // ====================== Dados reais ======================

    public function forUser(int $userId): array
    {
        $uploads = new UploadService();
        $games = (new GameService())->allChronological($userId);
        $queued = array_column(Database::all('SELECT id, queued_at FROM games WHERE user_id = ? AND queued_at IS NOT NULL', [$userId]), 'queued_at', 'id');
        foreach ($games as &$g) {
            $g['queued_at'] = $queued[$g['id']] ?? null;
        }
        unset($g);

        $backlog = [];
        foreach (Database::all(
            'SELECT b.id, b.title, p.name AS platform, b.status, b.started_at, b.note, b.cover_path, COALESCE(b.queued_at, DATE(b.created_at)) AS added_at
             FROM backlog_games b LEFT JOIN platforms p ON p.id = b.platform_id WHERE b.user_id = ? ORDER BY b.id', [$userId]
        ) as $r) {
            $backlog[] = ['id' => (int) $r['id'], 'title' => $r['title'], 'platform' => $r['platform'], 'status' => $r['status'],
                'started_at' => $r['started_at'], 'note' => $r['note'], 'added_at' => $r['added_at'], 'cover_url' => $uploads->url($r['cover_path'])];
        }

        $dropped = [];
        foreach (Database::all(
            'SELECT d.id, d.title, p.name AS platform, d.dropped_at, d.queued_at, d.started_at, d.return_chance, d.reason, d.playtime_minutes, d.cover_path
             FROM dropped_games d JOIN platforms p ON p.id = d.platform_id WHERE d.user_id = ? ORDER BY d.id', [$userId]
        ) as $r) {
            $dropped[] = ['id' => (int) $r['id'], 'title' => $r['title'], 'platform' => $r['platform'], 'dropped_at' => $r['dropped_at'], 'queued_at' => $r['queued_at'], 'started_at' => $r['started_at'],
                'return_chance' => $r['return_chance'], 'reason' => $r['reason'], 'playtime_minutes' => (int) $r['playtime_minutes'], 'cover_url' => $uploads->url($r['cover_path'])];
        }

        $rows = Database::all('SELECT id, game_id, backlog_id, dropped_id, note_date, body FROM game_notes WHERE user_id = ? ORDER BY note_date, id LIMIT 10000', [$userId]);
        $byNote = (new ShotService())->forNotes(array_map(fn($r) => (int) $r['id'], $rows));
        $notes = [];
        foreach ($rows as $r) {
            $notes[] = ['id' => (int) $r['id'], 'ref' => $this->ref($r),
                'note_date' => $r['note_date'], 'body' => $r['body'], 'shots' => array_map([$this, 'slimShot'], $byNote[(int) $r['id']] ?? [])];
        }

        $base = (string) Config::get('base_path', '');
        $loose = [];
        foreach (Database::all(
            'SELECT id, game_id, backlog_id, dropped_id, path, width, height, caption, DATE(created_at) AS day FROM game_shots WHERE user_id = ? AND note_id IS NULL ORDER BY id LIMIT 5000', [$userId]
        ) as $r) {
            $loose[] = ['ref' => $this->ref($r), 'day' => $r['day'],
                'shot' => ['id' => (int) $r['id'], 'url' => $base . '/' . $r['path'], 'thumb' => $base . '/' . preg_replace('/\.webp$/', '_t.webp', $r['path']),
                    'width' => (int) $r['width'], 'height' => (int) $r['height'], 'caption' => $r['caption']]];
        }

        $entries = array_map(
            fn($r) => ['id' => (int) $r['id'], 'entry_date' => $r['entry_date'], 'body' => $r['body']],
            Database::all('SELECT id, entry_date, body FROM journal_entries WHERE user_id = ? ORDER BY entry_date, id LIMIT 10000', [$userId])
        );

        return $this->build($games, $backlog, $dropped, $notes, $loose, $entries, $base);
    }

    // ====================== Montagem (compartilhada com o exemplo) ======================

    /**
     * @param list<array> $games    jogos hidratados (GameService / DemoCatalog)
     * @param list<array> $backlog  id, title, platform, status, started_at, note, added_at, cover_url
     * @param list<array> $dropped  id, title, platform, dropped_at, queued_at?, started_at?, return_chance, reason, playtime_minutes, cover_url
     * @param list<array> $notes    id, ref, note_date, body, shots
     * @param list<array> $loose    ref, day, shot (screenshots sem nota)
     * @param list<array> $entries  id, entry_date, body (registros livres)
     * @param string|null $base     caminho base para os links; null = página de exemplo (sem links)
     */
    public function build(array $games, array $backlog, array $dropped, array $notes, array $loose, array $entries, ?string $base): array
    {
        $index = [];
        $events = [];
        $add = function (string $type, string $date, ?string $ref, array $extra = [], string $id = '') use (&$events): void {
            $events[] = ['key' => $type . '-' . ($ref ?? 'x') . $id, 'type' => $type, 'date' => $date, 'ref' => $ref] + $extra;
        };

        foreach ($games as $g) {
            $ref = 'g' . $g['id'];
            $index[$ref] = ['title' => $g['title'], 'platform' => $g['platform'], 'cover_url' => $g['cover_url'] ?? null, 'state' => 'finished',
                'href' => $base === null ? null : $base . '/jogos/' . $g['id'], 'kind' => 'games', 'id' => (int) $g['id']];
            $started = $g['started_at'] ?? null;
            $queued = $g['queued_at'] ?? null;
            if ($queued && $queued < ($started ?: $g['finished_at'])) {
                $add('queued', $queued, $ref, ['text' => null]);
            }
            if ($started && $started < $g['finished_at']) {
                $add('started', $started, $ref);
            }
            $m = $g['memory'] ?? [];
            $add('finished', $g['finished_at'], $ref, [
                'rating' => (int) $g['rating'], 'difficulty' => $g['difficulty'], 'playtime_minutes' => (int) $g['playtime_minutes'],
                'days' => $started ? max(0, (int) round((strtotime($g['finished_at']) - strtotime($started)) / 86400)) : null,
                'people' => array_values($m['people'] ?? []), 'played_where' => $m['played_where'] ?? null,
                'music_title' => $m['music_title'] ?? null, 'music_url' => $m['music_url'] ?? null,
                'save_states' => !empty($g['save_states']),
            ]);
        }

        foreach ($backlog as $b) {
            $ref = 'b' . $b['id'];
            $playing = $b['status'] === 'playing';
            $index[$ref] = ['title' => $b['title'], 'platform' => $b['platform'], 'cover_url' => $b['cover_url'] ?? null, 'state' => $playing ? 'playing' : 'want',
                'href' => $base === null ? null : $base . '/backlog/' . $b['id'] . '/editar', 'kind' => 'backlog', 'id' => (int) $b['id']];
            $started = $playing ? ($b['started_at'] ?? null) : null;
            if ($b['added_at'] && (!$started || $b['added_at'] < $started)) {
                $add('queued', $b['added_at'], $ref, ['text' => $b['note'] ?? null]);
            }
            if ($started) {
                $add('started', $started, $ref, ['text' => $b['added_at'] && $b['added_at'] < $started ? null : ($b['note'] ?? null)]);
            }
        }

        foreach ($dropped as $d) {
            $ref = 'd' . $d['id'];
            $index[$ref] = ['title' => $d['title'], 'platform' => $d['platform'], 'cover_url' => $d['cover_url'] ?? null, 'state' => 'dropped',
                'href' => $base === null ? null : $base . '/dropados/' . $d['id'] . '/editar', 'kind' => 'dropped', 'id' => (int) $d['id']];
            $started = $d['started_at'] ?? null;
            $queued = $d['queued_at'] ?? null;
            if ($queued && $queued < ($started ?: $d['dropped_at'])) {
                $add('queued', $queued, $ref, ['text' => null]);
            }
            if ($started && $started < $d['dropped_at']) {
                $add('started', $started, $ref);
            }
            $add('dropped', $d['dropped_at'], $ref, ['text' => $d['reason'], 'playtime_minutes' => (int) $d['playtime_minutes'], 'return_chance' => $d['return_chance']]);
        }

        foreach ($notes as $n) {
            if (isset($index[$n['ref']])) {
                $add('note', $n['note_date'], $n['ref'], ['id' => (int) $n['id'], 'text' => $n['body'], 'shots' => $n['shots'] ?? []], '-' . $n['id']);
            }
        }

        $groups = [];
        foreach ($loose as $s) {
            if (isset($index[$s['ref']])) {
                $groups[$s['ref'] . '|' . $s['day']][] = $s['shot'];
            }
        }
        foreach ($groups as $k => $shots) {
            [$ref, $day] = explode('|', $k);
            $add('shots', $day, $ref, ['shots' => $shots], '-' . $day);
        }

        foreach ($entries as $e) {
            $add('entry', $e['entry_date'], null, ['id' => (int) $e['id'], 'text' => $e['body']], '-' . $e['id']);
        }

        usort($events, fn($a, $b) => strcmp($b['date'], $a['date']) ?: self::WEIGHT[$b['type']] <=> self::WEIGHT[$a['type']] ?: strcmp($b['key'], $a['key']));

        // onde dá para escrever: primeiro o que está sendo jogado, depois a fila, depois os zerados mais recentes
        $order = ['playing' => 0, 'want' => 1, 'finished' => 2];
        $targets = [];
        foreach ($index as $ref => $g) {
            if (isset($order[$g['state']])) {
                $targets[] = ['ref' => $ref, 'title' => $g['title'], 'platform' => $g['platform'], 'state' => $g['state']];
            }
        }
        $pos = array_flip(array_keys($index));
        usort($targets, fn($a, $b) => $order[$a['state']] <=> $order[$b['state']] ?: $pos[$b['ref']] <=> $pos[$a['ref']]);

        return ['games' => $index, 'events' => $events, 'targets' => $targets, 'today' => date('Y-m-d')];
    }

    // ====================== Registros livres ======================

    public function addEntry(int $userId, array $in): array
    {
        [$date, $body] = $this->validate($in);
        if ((int) Database::value('SELECT COUNT(*) FROM journal_entries WHERE user_id = ?', [$userId]) >= 10000) {
            throw new HttpException(422, 'Limite de 10.000 registros no diário.');
        }
        Database::query('INSERT INTO journal_entries (user_id, entry_date, body) VALUES (?,?,?)', [$userId, $date, $body]);
        return ['id' => Database::insertId(), 'entry_date' => $date, 'body' => $body];
    }

    public function updateEntry(int $userId, int $id, array $in): array
    {
        $this->owned($userId, $id);
        [$date, $body] = $this->validate($in);
        Database::query('UPDATE journal_entries SET entry_date = ?, body = ? WHERE id = ? AND user_id = ?', [$date, $body, $id, $userId]);
        return ['id' => $id, 'entry_date' => $date, 'body' => $body];
    }

    public function deleteEntry(int $userId, int $id): void
    {
        $this->owned($userId, $id);
        Database::query('DELETE FROM journal_entries WHERE id = ? AND user_id = ?', [$id, $userId]);
    }

    // ---------- Internos ----------

    private function owned(int $userId, int $id): void
    {
        if (Database::one('SELECT id FROM journal_entries WHERE id = ? AND user_id = ?', [$id, $userId]) === null) {
            throw new HttpException(404, 'Registro não encontrado.');
        }
    }

    /** @return array{0: string, 1: string} */
    private function validate(array $in): array
    {
        $e = [];
        $body = trim(str_replace("\r\n", "\n", (string) ($in['body'] ?? '')));
        if ($body === '' || mb_strlen($body) > 2000) {
            $e['body'] = 'Escreva o registro (até 2000 caracteres).';
        }
        $raw = (string) ($in['note_date'] ?? date('Y-m-d'));
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $raw);
        if ($date === false || $date->format('Y-m-d') !== $raw || $date > new \DateTimeImmutable('today')) {
            $e['note_date'] = 'Data inválida (AAAA-MM-DD, não pode ser futura).';
        }
        if ($e) {
            throw new HttpException(422, 'Dados inválidos.', $e);
        }
        return [$date->format('Y-m-d'), $body];
    }

    /** Dono de uma nota ou screenshot: jogo zerado, item do backlog ou dropado. */
    private function ref(array $r): string
    {
        return $r['game_id'] !== null ? 'g' . $r['game_id'] : ($r['backlog_id'] !== null ? 'b' . $r['backlog_id'] : 'd' . $r['dropped_id']);
    }

    private function slimShot(array $s): array
    {
        return ['id' => $s['id'], 'url' => $s['url'], 'thumb' => $s['thumb'], 'width' => $s['width'], 'height' => $s['height'], 'caption' => $s['caption']];
    }
}
