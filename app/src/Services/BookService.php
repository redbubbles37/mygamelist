<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/** "Livro da minha vida gamer": todos os jogos por ano, com memórias e diário, prontos para imprimir ou salvar em PDF. */
final class BookService
{
    public function __construct(private GameService $games = new GameService()) {}

    public function forUser(int $userId, string $username): array
    {
        $games = $this->games->allChronological($userId);
        $notes = [];
        foreach (Database::all('SELECT game_id, note_date, body FROM game_notes WHERE user_id = ? AND game_id IS NOT NULL ORDER BY note_date, id', [$userId]) as $r) {
            $notes[(int) $r['game_id']][] = ['note_date' => $r['note_date'], 'body' => $r['body']];
        }
        return $this->fromGames($games, $notes, $username, (new ShotService())->forGames($userId, 4));
    }

    /**
     * @param list<array> $games  em ordem cronológica
     * @param array<int,list<array{note_date:string,body:string}>> $notes por id do jogo
     */
    public function fromGames(array $games, array $notes, string $username, array $shots = []): array
    {
        $years = [];
        foreach ($games as $g) {
            $y = (int) substr($g['finished_at'], 0, 4);
            $g['notes'] = $notes[$g['id']] ?? [];
            $g['shots'] = $shots[$g['id']] ?? [];
            $years[$y]['year'] = $y;
            $years[$y]['games'][] = $g;
        }
        ksort($years);
        $years = array_values($years);
        foreach ($years as &$y) {
            $y['minutes'] = array_sum(array_column($y['games'], 'playtime_minutes'));
        }
        unset($y);

        return [
            'username' => $username,
            'generated' => date('Y-m-d'),
            'totals' => [
                'games' => count($games),
                'minutes' => array_sum(array_column($games, 'playtime_minutes')),
                'platforms' => count(array_unique(array_column($games, 'platform'))),
                'notes' => array_sum(array_map('count', $notes)),
            ],
            'years' => $years,
        ];
    }
}
