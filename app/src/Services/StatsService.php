<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/** Estatísticas gerais e curiosidades sobre os jogos zerados. Calcula a partir de uma lista de jogos (a mesma serve ao exemplo). */
final class StatsService
{
    private const DIFFS = ['C', 'B', 'A', 'AA', 'AAA'];
    private const WEEKDAYS = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
    private const MONTHS = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];

    /** @return list<array> jogos no formato da API (com xp e tasks) */
    public function gamesFor(int $userId): array
    {
        $rows = Database::all(
            'SELECT g.id, g.title, g.finished_at, g.release_year, g.playtime_minutes, g.rating, g.difficulty, g.cover_path,
                    p.name AS platform, d.name AS developer, pb.name AS publisher, ge.name AS genre,
                    COALESCE((SELECT SUM(e.amount) FROM xp_events e WHERE e.game_id = g.id), 0) AS xp,
                    (SELECT COUNT(*) FROM game_tasks t WHERE t.game_id = g.id) AS task_count
             FROM games g
             JOIN platforms p ON p.id = g.platform_id
             LEFT JOIN companies d  ON d.id  = g.developer_id
             LEFT JOIN companies pb ON pb.id = g.publisher_id
             LEFT JOIN genres ge    ON ge.id  = g.genre_id
             WHERE g.user_id = ? ORDER BY g.finished_at, g.id',
            [$userId]
        );
        $uploads = new UploadService();
        return array_map(fn(array $r) => [
            'id' => (int) $r['id'], 'title' => $r['title'], 'platform' => $r['platform'], 'developer' => $r['developer'], 'publisher' => $r['publisher'],
            'genre' => $r['genre'], 'finished_at' => $r['finished_at'], 'release_year' => (int) $r['release_year'],
            'playtime_minutes' => (int) $r['playtime_minutes'], 'rating' => (int) $r['rating'], 'difficulty' => $r['difficulty'],
            'xp' => (int) $r['xp'], 'task_count' => (int) $r['task_count'], 'cover_url' => $uploads->url($r['cover_path']),
        ], $rows);
    }

    public function compute(array $games): array
    {
        $n = count($games);
        if ($n === 0) {
            return ['empty' => true];
        }
        $minutes = array_sum(array_column($games, 'playtime_minutes'));
        $tasks = array_sum(array_map(fn($g) => $g['task_count'] ?? count($g['tasks'] ?? []), $games));
        $dates = array_column($games, 'finished_at');
        sort($dates);

        $totals = [
            'games' => $n, 'minutes' => $minutes, 'days' => round($minutes / 1440, 1),
            'avg_rating' => round(array_sum(array_column($games, 'rating')) / $n, 2),
            'avg_minutes' => (int) round($minutes / $n),
            'xp' => array_sum(array_column($games, 'xp')), 'tasks' => $tasks,
            'platforms' => count(array_unique(array_column($games, 'platform'))),
            'genres' => count(array_unique(array_filter(array_column($games, 'genre')))),
            'first' => $dates[0], 'last' => $dates[$n - 1],
        ];

        return [
            'empty' => false,
            'totals' => $totals,
            'records' => $this->records($games),
            'weekdays' => $this->weekdays($games),
            'rating_dist' => $this->ratingDist($games),
            'difficulty' => $this->difficulty($games),
            'decades' => $this->decades($games),
            'finish_years' => $this->finishYears($games),
            'genres' => $this->group($games, 'genre', 10),
            'platforms' => $this->group($games, 'platform', 10),
            'developers' => $this->group($games, 'developer', 10),
            'publishers' => $this->group($games, 'publisher', 10),
            'insights' => $this->insights($games, $totals),
        ];
    }

    // ---------- Recordes ----------

    private function records(array $games): array
    {
        $pick = function (callable $better) use ($games): array {
            $best = null;
            foreach ($games as $g) {
                if ($best === null || $better($g, $best)) {
                    $best = $g;
                }
            }
            return $best;
        };
        $entry = fn(string $label, array $g, string $detail) => ['label' => $label, 'title' => $g['title'], 'platform' => $g['platform'], 'detail' => $detail];

        $out = [];
        $out[] = $entry('Jogo mais longo', $l = $pick(fn($a, $b) => $a['playtime_minutes'] > $b['playtime_minutes']), $this->dur($l['playtime_minutes']));
        $withTime = array_values(array_filter($games, fn($g) => $g['playtime_minutes'] > 0));
        if ($withTime) {
            $s = $withTime[0];
            foreach ($withTime as $g) {
                if ($g['playtime_minutes'] < $s['playtime_minutes']) {
                    $s = $g;
                }
            }
            $out[] = $entry('Jogo mais curto', $s, $this->dur($s['playtime_minutes']));
        }
        $out[] = $entry('Melhor nota (mais horas entre empates)', $b = $pick(fn($a, $c) => [$a['rating'], $a['playtime_minutes']] > [$c['rating'], $c['playtime_minutes']]), 'Nota ' . $b['rating']);
        $out[] = $entry('Mais XP', $x = $pick(fn($a, $c) => $a['xp'] > $c['xp']), number_format($x['xp'], 0, ',', '.') . ' XP');
        $out[] = $entry('Mais antigo (lançamento)', $o = $pick(fn($a, $c) => $a['release_year'] < $c['release_year']), 'Lançado em ' . $o['release_year']);
        $out[] = $entry('Mais recente (lançamento)', $r = $pick(fn($a, $c) => $a['release_year'] > $c['release_year']), 'Lançado em ' . $r['release_year']);
        $late = $pick(fn($a, $c) => $this->lag($a) > $this->lag($c));
        if ($this->lag($late) > 0) {
            $out[] = $entry('Zerado mais tarde', $late, $this->lag($late) . ' anos depois do lançamento');
        }
        return $out;
    }

    private function lag(array $g): int
    {
        return (int) substr($g['finished_at'], 0, 4) - (int) $g['release_year'];
    }

    // ---------- Distribuições ----------

    private function weekdays(array $games): array
    {
        $rows = array_map(fn($i) => ['label' => self::WEEKDAYS[$i], 'n' => 0, 'minutes' => 0], range(0, 6));
        foreach ($games as $g) {
            $w = (int) date('w', strtotime($g['finished_at']));
            $rows[$w]['n']++;
            $rows[$w]['minutes'] += $g['playtime_minutes'];
        }
        return $rows;
    }

    private function ratingDist(array $games): array
    {
        $rows = array_map(fn($r) => ['rating' => $r, 'n' => 0], range(1, 11));
        foreach ($games as $g) {
            $rows[$g['rating'] - 1]['n']++;
        }
        return $rows;
    }

    private function difficulty(array $games): array
    {
        $rows = [];
        foreach (self::DIFFS as $d) {
            $set = array_values(array_filter($games, fn($g) => $g['difficulty'] === $d));
            $c = count($set);
            $rows[] = [
                'name' => $d, 'n' => $c,
                'avg_rating' => $c ? round(array_sum(array_column($set, 'rating')) / $c, 2) : null,
                'avg_minutes' => $c ? (int) round(array_sum(array_column($set, 'playtime_minutes')) / $c) : null,
            ];
        }
        return $rows;
    }

    private function decades(array $games): array
    {
        $by = [];
        foreach ($games as $g) {
            $d = intdiv($g['release_year'], 10) * 10;
            $by[$d] = ($by[$d] ?? 0) + 1;
        }
        ksort($by);
        return array_map(fn($d, $c) => ['name' => $d . 's', 'n' => $c], array_keys($by), $by);
    }

    private function finishYears(array $games): array
    {
        $by = [];
        foreach ($games as $g) {
            $y = (int) substr($g['finished_at'], 0, 4);
            $by[$y]['n'] = ($by[$y]['n'] ?? 0) + 1;
            $by[$y]['minutes'] = ($by[$y]['minutes'] ?? 0) + $g['playtime_minutes'];
        }
        ksort($by);
        return array_map(fn($y, $v) => ['year' => $y, 'n' => $v['n'], 'minutes' => $v['minutes']], array_keys($by), $by);
    }

    /** Agrupa por um campo de texto: quantidade, nota média e horas, do maior para o menor. */
    private function group(array $games, string $field, int $limit): array
    {
        $by = [];
        foreach ($games as $g) {
            $name = $g[$field] ?? null;
            if ($name === null || $name === '') {
                continue;
            }
            $by[$name]['n'] = ($by[$name]['n'] ?? 0) + 1;
            $by[$name]['rating'] = ($by[$name]['rating'] ?? 0) + $g['rating'];
            $by[$name]['minutes'] = ($by[$name]['minutes'] ?? 0) + $g['playtime_minutes'];
        }
        $rows = array_map(fn($name, $v) => [
            'name' => (string) $name, 'n' => $v['n'], 'avg_rating' => round($v['rating'] / $v['n'], 2), 'minutes' => $v['minutes'],
        ], array_keys($by), $by);
        usort($rows, fn($a, $b) => $b['n'] <=> $a['n'] ?: $b['avg_rating'] <=> $a['avg_rating'] ?: strcasecmp($a['name'], $b['name']));
        return array_slice($rows, 0, $limit);
    }

    // ---------- Curiosidades em texto ----------

    private function insights(array $games, array $totals): array
    {
        $out = [];
        $n = count($games);

        $wd = $this->weekdays($games);
        usort($wd, fn($a, $b) => $b['n'] <=> $a['n']);
        if ($wd[0]['n'] > 0 && $n >= 5) {
            $out[] = ['icon' => 'calendar', 'text' => 'Você mais zera jogos ' . $this->weekdayPhrase($wd[0]['label']) . ': ' . $wd[0]['n'] . ' de ' . $n . ' (' . round($wd[0]['n'] / $n * 100) . '%).'];
        }

        // melhor mês do histórico
        $months = [];
        foreach ($games as $g) {
            $k = substr($g['finished_at'], 0, 7);
            $months[$k] = ($months[$k] ?? 0) + 1;
        }
        arsort($months);
        $mk = array_key_first($months);
        if ($months[$mk] >= 2) {
            [$y, $m] = explode('-', $mk);
            $out[] = ['icon' => 'trophy', 'text' => 'Seu mês mais produtivo foi ' . self::MONTHS[(int) $m - 1] . ' de ' . $y . ', com ' . $months[$mk] . ' jogos zerados.'];
        }

        // gênero e sistema com maior nota média (pelo menos 3 jogos)
        foreach (['genre' => 'gênero', 'platform' => 'sistema'] as $field => $label) {
            $best = null;
            foreach ($this->group($games, $field, 1000) as $row) {
                if ($row['n'] >= 3 && ($best === null || $row['avg_rating'] > $best['avg_rating'])) {
                    $best = $row;
                }
            }
            if ($best) {
                $out[] = ['icon' => 'star', 'text' => 'O ' . $label . ' que você avalia melhor é ' . $best['name'] . ': nota média ' . $this->num($best['avg_rating']) . ' em ' . $best['n'] . ' jogos.'];
            }
        }

        $lags = array_map(fn($g) => max(0, $this->lag($g)), $games);
        $avgLag = array_sum($lags) / $n;
        $out[] = ['icon' => 'clock', 'text' => 'Em média, você zera um jogo ' . $this->num($avgLag) . ' anos depois do lançamento; ' . round(count(array_filter($lags, fn($l) => $l >= 10)) / $n * 100) . '% já tinham 10 anos ou mais.'];

        $out[] = ['icon' => 'clock', 'text' => 'No total são ' . $this->num($totals['days']) . ' dias inteiros de jogo (' . number_format((int) round($totals['minutes'] / 60), 0, ',', '.') . ' horas).'];

        if ($n >= 8) {
            $r1 = $this->pearson(array_map(fn($g) => array_search($g['difficulty'], self::DIFFS), $games), array_column($games, 'rating'));
            $r2 = $this->pearson(array_column($games, 'playtime_minutes'), array_column($games, 'rating'));
            if ($r1 !== null) {
                $out[] = ['icon' => 'flame', 'text' => 'Dificuldade e nota: ' . $this->strength($r1) . ($r1 > 0.15 ? ' (jogos mais difíceis tendem a ganhar notas maiores).' : ($r1 < -0.15 ? ' (jogos mais difíceis tendem a ganhar notas menores).' : '.'))];
            }
            if ($r2 !== null) {
                $out[] = ['icon' => 'flame', 'text' => 'Duração e nota: ' . $this->strength($r2) . ($r2 > 0.15 ? ' (jogos mais longos tendem a ganhar notas maiores).' : ($r2 < -0.15 ? ' (jogos mais longos tendem a ganhar notas menores).' : '.'))];
            }
        }
        return $out;
    }

    private function weekdayPhrase(string $label): string
    {
        return in_array($label, ['Sábado', 'Domingo'], true) ? 'no ' . strtolower($label) : 'na ' . mb_strtolower($label) . '-feira';
    }

    private function strength(float $r): string
    {
        $a = abs($r);
        return $a < 0.15 ? 'quase nenhuma relação' : ($a < 0.35 ? 'relação fraca' : ($a < 0.6 ? 'relação moderada' : 'relação forte'));
    }

    private function pearson(array $x, array $y): ?float
    {
        $n = count($x);
        $mx = array_sum($x) / $n;
        $my = array_sum($y) / $n;
        $sxy = $sxx = $syy = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $dx = $x[$i] - $mx;
            $dy = $y[$i] - $my;
            $sxy += $dx * $dy;
            $sxx += $dx * $dx;
            $syy += $dy * $dy;
        }
        return ($sxx > 0 && $syy > 0) ? $sxy / sqrt($sxx * $syy) : null;
    }

    private function num(float $v): string
    {
        return number_format($v, 1, ',', '.');
    }

    private function dur(int $m): string
    {
        return $m >= 60 ? intdiv($m, 60) . 'h' . ($m % 60 ? str_pad((string) ($m % 60), 2, '0', STR_PAD_LEFT) : '') : $m . ' min';
    }
}
