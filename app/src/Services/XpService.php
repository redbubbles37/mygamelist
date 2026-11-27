<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class XpService
{
    private ?array $settings = null;

    private function s(string $name): float
    {
        if ($this->settings === null) {
            $this->settings = array_column(Database::all('SELECT name, value FROM settings'), 'value', 'name');
        }
        return (float) ($this->settings[$name] ?? 0);
    }

    /** Valores de XP vigentes (usados pelo front para a prévia do formulário). */
    public function rules(): array
    {
        $diff = [];
        foreach (['C', 'B', 'A', 'AA', 'AAA'] as $d) {
            $diff[$d] = (int) $this->s("xp_diff_$d");
        }
        return [
            'base'       => (int) $this->s('xp_base'),
            'difficulty' => $diff,
            'tier'       => [1 => (int) $this->s('xp_tier_1'), 2 => (int) $this->s('xp_tier_2'), 3 => (int) $this->s('xp_tier_3')],
        ];
    }

    /** Eventos de XP gerados por um jogo finalizado. */
    public function breakdown(string $difficulty, array $tasks, bool $saveStates = false): array
    {
        $events = [['base', (int) $this->s('xp_base'), 'Jogo finalizado']];

        $diff = (int) $this->s("xp_diff_$difficulty");
        if ($diff > 0) {
            $events[] = ['difficulty', $diff, "Dificuldade $difficulty"];
        }
        foreach ($tasks as $t) {
            $xp = (int) $this->s('xp_tier_' . $t['tier']);
            if ($xp > 0) {
                $events[] = ['task', $xp, 'Tarefa (tier ' . $t['tier'] . '): ' . $t['description']];
            }
        }
        return $saveStates ? $this->halve($events) : $events;
    }

    /** Save state: o jogo rende 50% do XP. Reparte a metade do total entre os eventos (sem perder arredondamento). */
    private function halve(array $events): array
    {
        $target = (int) round(array_sum(array_column($events, 1)) / 2);
        $out = [];
        $extra = $target - array_sum(array_map(fn($e) => intdiv($e[1], 2), $events));
        foreach ($events as [$source, $amount, $desc]) {
            $half = intdiv($amount, 2);
            if ($extra > 0 && $amount % 2 === 1) {
                $half++;
                $extra--;
            }
            if ($half > 0) {
                $out[] = [$source, $half, $desc . ' (save state: 50%)'];
            }
        }
        return $out;
    }

    /** Substitui os eventos do jogo e atualiza total/nível do usuário. */
    public function rebuildGame(int $userId, int $gameId, string $difficulty, array $tasks, bool $saveStates = false): void
    {
        Database::query('DELETE FROM xp_events WHERE game_id = ?', [$gameId]);
        foreach ($this->breakdown($difficulty, $tasks, $saveStates) as [$source, $amount, $desc]) {
            Database::query(
                'INSERT INTO xp_events (user_id, game_id, source, amount, description) VALUES (?,?,?,?,?)',
                [$userId, $gameId, $source, $amount, mb_substr($desc, 0, 255)]
            );
        }
        $this->refreshUser($userId);
    }

    public function refreshUser(int $userId): void
    {
        $total = (int) Database::value('SELECT COALESCE(SUM(amount),0) FROM xp_events WHERE user_id = ?', [$userId]);
        Database::query('UPDATE users SET total_xp = ?, level = ? WHERE id = ?', [$total, $this->levelFor($total), $userId]);
    }

    /** XP acumulado necessário para ALCANÇAR o nível $level. */
    public function xpForLevel(int $level): int
    {
        $max = (int) $this->s('level_max');
        if ($level <= 1) {
            return 0;
        }
        $ratio = min(1.0, ($level - 1) / ($max - 1));
        return (int) round($this->s('level_total_xp') * ($ratio ** $this->s('level_curve_exp')));
    }

    public function levelFor(int $xp): int
    {
        $max = (int) $this->s('level_max');
        $level = 1;
        for ($n = 2; $n <= $max; $n++) {
            if ($xp < $this->xpForLevel($n)) {
                break;
            }
            $level = $n;
        }
        return $level;
    }

    public function progress(int $xp): array
    {
        $max   = (int) $this->s('level_max');
        $level = $this->levelFor($xp);
        $from  = $this->xpForLevel($level);
        $to    = $level >= $max ? null : $this->xpForLevel($level + 1);

        return [
            'level'          => $level,
            'level_max'      => $max,
            'total_xp'       => $xp,
            'level_start_xp' => $from,
            'next_level_xp'  => $to,
            'progress_pct'   => $to === null ? 100.0 : round(($xp - $from) / ($to - $from) * 100, 1),
        ];
    }

    /** Recalcula todos os eventos (use após mudar a tabela settings). */
    public function rebuildAll(): int
    {
        $tasks = [];
        foreach (Database::all('SELECT game_id, description, tier FROM game_tasks') as $t) {
            $tasks[$t['game_id']][] = $t;
        }
        Database::transaction(function () use ($tasks) {
            foreach (Database::all('SELECT id, user_id, difficulty, used_save_states FROM games') as $g) {
                $this->rebuildGame($g['user_id'], $g['id'], $g['difficulty'], $tasks[$g['id']] ?? [], (bool) $g['used_save_states']);
            }
        });
        $users = Database::all('SELECT id FROM users');
        foreach ($users as $u) {
            $this->refreshUser($u['id']);
        }
        return count($users);
    }
}
