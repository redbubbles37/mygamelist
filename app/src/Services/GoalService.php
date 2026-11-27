<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\{Database, HttpException};

/** Metas do ano: zerar N jogos e/ou jogar N horas. O progresso vem dos jogos zerados no ano (data de finalização). */
final class GoalService
{
    private const MAX_GAMES = 5000;
    private const MAX_HOURS = 100000;

    /** Tudo o que a tela de metas precisa para um ano. */
    public function show(int $userId, int $year): array
    {
        $goals = $this->goalsOf($userId, $year);

        $months = ['games' => array_fill(0, 12, 0), 'hours' => array_fill(0, 12, 0.0)];
        foreach (Database::all(
            'SELECT MONTH(finished_at) AS m, COUNT(*) AS n, COALESCE(SUM(playtime_minutes), 0) AS minutes
             FROM games WHERE user_id = ? AND YEAR(finished_at) = ? GROUP BY m',
            [$userId, $year]
        ) as $r) {
            $months['games'][$r['m'] - 1] = (int) $r['n'];
            $months['hours'][$r['m'] - 1] = round($r['minutes'] / 60, 1);
        }

        // histórico: outros anos com meta, comparando com o que foi feito
        $history = [];
        foreach (Database::all('SELECT DISTINCT year FROM goals WHERE user_id = ? AND year <> ? ORDER BY year DESC', [$userId, $year]) as $r) {
            $y = (int) $r['year'];
            $totals = $this->totals($userId, $y);
            $g = $this->goalsOf($userId, $y);
            $history[] = ['year' => $y, 'goals' => $g, 'done' => $totals];
        }

        $years = array_map('intval', array_column(Database::all(
            'SELECT DISTINCT YEAR(finished_at) AS y FROM games WHERE user_id = ? UNION SELECT DISTINCT year FROM goals WHERE user_id = ? ORDER BY y DESC',
            [$userId, $userId]
        ), 'y'));
        foreach ([(int) date('Y'), $year] as $must) {
            if (!in_array($must, $years, true)) {
                $years[] = $must;
            }
        }
        rsort($years);

        return [
            'year'    => $year,
            'years'   => $years,
            'today'   => date('Y-m-d'),
            'goals'   => $goals,
            'done'    => ['games' => array_sum($months['games']), 'hours' => round(array_sum($months['hours']), 1)],
            'months'  => $months,
            'history' => $history,
        ];
    }

    /** Define (ou remove, com null/vazio) as metas de um ano. */
    public function set(int $userId, int $year, mixed $games, mixed $hours): array
    {
        if ($year < 2000 || $year > (int) date('Y') + 1) {
            throw new HttpException(422, 'Ano inválido.', ['year' => 'Escolha um ano entre 2000 e ' . ((int) date('Y') + 1) . '.']);
        }
        $values = [];
        $errors = [];
        foreach (['games' => [$games, self::MAX_GAMES, 'jogos'], 'hours' => [$hours, self::MAX_HOURS, 'horas']] as $metric => [$raw, $max, $unit]) {
            if ($raw === null || $raw === '') {
                $values[$metric] = null;
                continue;
            }
            $n = filter_var($raw, FILTER_VALIDATE_INT);
            if ($n === false || $n < 1 || $n > $max) {
                $errors[$metric] = "Informe um número de $unit entre 1 e $max.";
            } else {
                $values[$metric] = $n;
            }
        }
        if ($errors) {
            throw new HttpException(422, 'Meta inválida.', $errors);
        }

        Database::transaction(function () use ($userId, $year, $values) {
            foreach ($values as $metric => $target) {
                if ($target === null) {
                    Database::query('DELETE FROM goals WHERE user_id = ? AND year = ? AND metric = ?', [$userId, $year, $metric]);
                } else {
                    Database::query(
                        'INSERT INTO goals (user_id, year, metric, target) VALUES (?,?,?,?) ON CONFLICT(user_id, year, metric) DO UPDATE SET target = excluded.target',
                        [$userId, $year, $metric, $target]
                    );
                }
            }
        });
        return $this->show($userId, $year);
    }

    /** Resumo compacto do ano atual (cartão do dashboard). */
    public function summary(int $userId): ?array
    {
        $year = (int) date('Y');
        $goals = $this->goalsOf($userId, $year);
        if ($goals['games'] === null && $goals['hours'] === null) {
            return null;
        }
        $t = $this->totals($userId, $year);
        $out = ['year' => $year];
        foreach (['games', 'hours'] as $m) {
            $out[$m] = $goals[$m] === null ? null : ['target' => $goals[$m], 'done' => $t[$m]];
        }
        return $out;
    }

    private function goalsOf(int $userId, int $year): array
    {
        $out = ['games' => null, 'hours' => null];
        foreach (Database::all('SELECT metric, target FROM goals WHERE user_id = ? AND year = ?', [$userId, $year]) as $r) {
            $out[$r['metric']] = (int) $r['target'];
        }
        return $out;
    }

    private function totals(int $userId, int $year): array
    {
        $r = Database::one(
            'SELECT COUNT(*) AS n, COALESCE(SUM(playtime_minutes), 0) AS minutes FROM games WHERE user_id = ? AND YEAR(finished_at) = ?',
            [$userId, $year]
        );
        return ['games' => (int) $r['n'], 'hours' => round($r['minutes'] / 60, 1)];
    }
}
