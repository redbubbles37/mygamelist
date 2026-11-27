<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\{Database, HttpException};

/**
 * Notas altas são raras de propósito: 1 nota 10 a cada 10 jogos zerados e 1 nota 11 a cada 50.
 * As cotas são independentes. Notas que já existem nunca são mexidas; só novas notas 10/11 são barradas.
 */
final class RatingQuotaService
{
    /** nota => quantos jogos zerados liberam uma */
    public const RULES = [10 => 10, 11 => 50];

    /** @return array{games:int, used:array<int,int>, per:array<int,int>} */
    public function status(int $userId): array
    {
        $r = Database::one('SELECT COUNT(*) total, SUM(rating = 10) r10, SUM(rating = 11) r11 FROM games WHERE user_id = ?', [$userId]);
        return ['games' => (int) $r['total'], 'used' => [10 => (int) $r['r10'], 11 => (int) $r['r11']], 'per' => self::RULES];
    }

    public static function allowed(int $rating, int $games): int
    {
        return intdiv($games, self::RULES[$rating]);
    }

    /**
     * Confere se cabe mais $adding notas $rating.
     * @param int $newGames jogos que entram junto (1 ao cadastrar: o próprio jogo conta)
     */
    public function check(int $userId, int $rating, int $newGames = 0, int $adding = 1): void
    {
        if (!isset(self::RULES[$rating]) || $adding <= 0) {
            return;
        }
        $s = $this->status($userId);
        $games = $s['games'] + $newGames;
        $allowed = self::allowed($rating, $games);
        $used = $s['used'][$rating];
        if ($used + $adding > $allowed) {
            $per = self::RULES[$rating];
            $missing = max(1, ($used + $adding) * $per - $s['games']);   // jogos que ainda faltam zerar (o que está sendo cadastrado conta)
            throw new HttpException(422, 'Limite de notas ' . $rating . ' atingido.', ['rating' => sprintf(
                'Nota %d: 1 a cada %d jogos zerados. Você já usou %d de %d; faltam %d %s para liberar.',
                $rating, $per, $used, $allowed, $missing, $missing === 1 ? 'jogo' : 'jogos'
            )]);
        }
    }
}
