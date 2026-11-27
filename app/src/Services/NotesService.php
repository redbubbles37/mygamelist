<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\{Database, HttpException, Str};

/** Diário de bordo (notas datadas) e a visão "com quem / onde" sobre as memórias dos jogos. */
final class NotesService
{
    private const OWNERS = ['games' => ['games', 'game_id'], 'backlog' => ['backlog_games', 'backlog_id'], 'dropped' => ['dropped_games', 'dropped_id']];

    /** @return list<array{id:int,note_date:string,body:string}> */
    public function list(int $userId, string $kind, int $ownerId): array
    {
        [, $col] = $this->owner($userId, $kind, $ownerId);
        $notes = array_map(fn(array $r) => $this->format($r), Database::all(
            "SELECT id, note_date, body FROM game_notes WHERE user_id = ? AND `$col` = ? ORDER BY note_date DESC, id DESC",
            [$userId, $ownerId]
        ));
        $shots = (new ShotService())->forNotes(array_column($notes, 'id'));
        foreach ($notes as &$n) {
            $n['shots'] = $shots[$n['id']] ?? [];
        }
        return $notes;
    }

    public function add(int $userId, string $kind, int $ownerId, array $in): array
    {
        [, $col] = $this->owner($userId, $kind, $ownerId);
        [$date, $body] = $this->validate($in);
        $count = (int) Database::value("SELECT COUNT(*) FROM game_notes WHERE `$col` = ?", [$ownerId]);
        if ($count >= 500) {
            throw new HttpException(422, 'Limite de 500 notas por jogo.');
        }
        Database::query("INSERT INTO game_notes (user_id, `$col`, note_date, body) VALUES (?,?,?,?)", [$userId, $ownerId, $date, $body]);
        return $this->format(Database::one('SELECT id, note_date, body FROM game_notes WHERE id = ?', [Database::insertId()]));
    }

    public function update(int $userId, int $id, array $in): array
    {
        $this->ownedNote($userId, $id);
        [$date, $body] = $this->validate($in);
        Database::query('UPDATE game_notes SET note_date = ?, body = ? WHERE id = ? AND user_id = ?', [$date, $body, $id, $userId]);
        return $this->format(Database::one('SELECT id, note_date, body FROM game_notes WHERE id = ?', [$id]));
    }

    public function delete(int $userId, int $id): void
    {
        $this->ownedNote($userId, $id);
        Database::query('DELETE FROM game_notes WHERE id = ? AND user_id = ?', [$id, $userId]);
    }

    /**
     * Pessoas e lugares: para cada nome, os jogos ligados a ele.
     * @return array{people: list<array>, places: list<array>}
     */
    public function circles(int $userId): array
    {
        $people = [];
        foreach (Database::all(
            'SELECT gp.name, g.id, g.title, p.name AS platform, g.finished_at, g.cover_path
             FROM game_people gp JOIN games g ON g.id = gp.game_id JOIN platforms p ON p.id = g.platform_id
             WHERE g.user_id = ? ORDER BY g.finished_at DESC', [$userId]
        ) as $r) {
            $people[Str::key($r['name'])]['name'] ??= $r['name'];
            $people[Str::key($r['name'])]['games'][] = $this->gameRow($r);
        }

        $places = [];
        foreach (Database::all(
            'SELECT g.played_where AS name, g.id, g.title, p.name AS platform, g.finished_at, g.cover_path
             FROM games g JOIN platforms p ON p.id = g.platform_id
             WHERE g.user_id = ? AND g.played_where IS NOT NULL ORDER BY g.finished_at DESC', [$userId]
        ) as $r) {
            $places[Str::key($r['name'])]['name'] ??= $r['name'];
            $places[Str::key($r['name'])]['games'][] = $this->gameRow($r);
        }

        $sort = function (array $groups): array {
            $groups = array_values($groups);
            usort($groups, fn($a, $b) => count($b['games']) <=> count($a['games']) ?: strcasecmp($a['name'], $b['name']));
            return $groups;
        };
        return ['people' => $sort($people), 'places' => $sort($places)];
    }

    // ---------- Internos ----------

    private function gameRow(array $r): array
    {
        return ['id' => (int) $r['id'], 'title' => $r['title'], 'platform' => $r['platform'], 'finished_at' => $r['finished_at'], 'cover_url' => (new UploadService())->url($r['cover_path'])];
    }

    private function owner(int $userId, string $kind, int $ownerId): array
    {
        $o = self::OWNERS[$kind] ?? throw new HttpException(404, 'Não encontrado.');
        if (Database::one("SELECT id FROM `{$o[0]}` WHERE id = ? AND user_id = ?", [$ownerId, $userId]) === null) {
            throw new HttpException(404, 'Jogo não encontrado.');
        }
        return $o;
    }

    private function ownedNote(int $userId, int $id): void
    {
        if (Database::one('SELECT id FROM game_notes WHERE id = ? AND user_id = ?', [$id, $userId]) === null) {
            throw new HttpException(404, 'Nota não encontrada.');
        }
    }

    /** @return array{0: string, 1: string} */
    private function validate(array $in): array
    {
        $e = [];
        $body = trim(str_replace("\r\n", "\n", (string) ($in['body'] ?? '')));
        if ($body === '' || mb_strlen($body) > 2000) {
            $e['body'] = 'Escreva a nota (até 2000 caracteres).';
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

    private function format(array $r): array
    {
        return ['id' => (int) $r['id'], 'note_date' => $r['note_date'], 'body' => $r['body']];
    }
}
