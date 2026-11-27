<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\{Database, HttpException};

/**
 * Retrospectiva do ano: resumo do que o usuário ZEROU em cada ano (ano = data de finalização) e o Top 3 escolhido por ele.
 * Diferente do GOTY, o ano de lançamento não importa: um jogo antigo zerado este ano pode entrar no Top 3.
 */
final class RetrospectiveService
{
    public const MIN_FOR_TOP = 3;
    /** Só jogos com esta nota ou mais (9, 10 ou 11) podem ser eleitos para o top 3 do ano. */
    public const MIN_RATING_TOP = 9;

    private const MONTHS = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

    public function __construct(private UploadService $uploads = new UploadService()) {}

    /** Anos em que o usuário zerou algum jogo (mais recente primeiro). */
    public function years(int $userId): array
    {
        return array_map(fn(array $r) => [
            'year' => (int) $r['y'], 'games' => (int) $r['n'], 'hours' => round($r['minutes'] / 60, 1),
        ], Database::all(
            'SELECT YEAR(finished_at) AS y, COUNT(*) AS n, COALESCE(SUM(playtime_minutes), 0) AS minutes
             FROM games WHERE user_id = ? GROUP BY y ORDER BY y DESC',
            [$userId]
        ));
    }

    public function show(int $userId, int $year): array
    {
        $games = array_map(fn(array $g) => $this->card($g), Database::all(
            'SELECT g.id, g.title, g.finished_at, g.release_year, g.playtime_minutes, g.rating, g.difficulty, g.cover_path,
                    p.name AS platform, ge.name AS genre
             FROM games g JOIN platforms p ON p.id = g.platform_id LEFT JOIN genres ge ON ge.id = g.genre_id
             WHERE g.user_id = ? AND YEAR(g.finished_at) = ? ORDER BY g.finished_at, g.id',
            [$userId, $year]
        ));
        if (!$games) {
            throw new HttpException(404, "Você não zerou nenhum jogo em $year.");
        }

        $xp = (int) Database::value(
            'SELECT COALESCE(SUM(e.amount), 0) FROM xp_events e JOIN games g ON g.id = e.game_id WHERE g.user_id = ? AND YEAR(g.finished_at) = ?',
            [$userId, $year]
        );
        $prev = Database::one(
            'SELECT COUNT(*) AS n, COALESCE(SUM(playtime_minutes), 0) AS minutes FROM games WHERE user_id = ? AND YEAR(finished_at) = ?',
            [$userId, $year - 1]
        );
        $topIds = array_column(Database::all(
            'SELECT position, game_id FROM retro_top WHERE user_id = ? AND year = ? ORDER BY position', [$userId, $year]
        ), 'game_id', 'position');

        return $this->build($year, $games, $prev && $prev['n'] > 0 ? ['games' => (int) $prev['n'], 'hours' => round($prev['minutes'] / 60, 1)] : null, $xp, $topIds);
    }

    /** Define o Top 3 de uma vez: slots = [id do 1º, id do 2º, id do 3º] (nulls no fim para deixar vagas). */
    public function setTop(int $userId, int $year, mixed $slots): array
    {
        $count = (int) Database::value('SELECT COUNT(*) FROM games WHERE user_id = ? AND YEAR(finished_at) = ?', [$userId, $year]);
        if ($count < self::MIN_FOR_TOP) {
            throw new HttpException(422, 'Ano sem jogos suficientes.', ['year' => 'Zere pelo menos ' . self::MIN_FOR_TOP . " jogos em $year para montar o top 3 (você tem $count)."]);
        }
        if (!is_array($slots) || count($slots) !== 3) {
            throw new HttpException(422, 'Top 3 inválido.', ['slots' => 'Envie as 3 posições.']);
        }

        $ids = [];
        $gap = false;
        foreach (array_values($slots) as $i => $v) {
            if ($v === null || $v === '') {
                $gap = true;
                continue;
            }
            $id = filter_var($v, FILTER_VALIDATE_INT);
            if ($id === false) {
                throw new HttpException(422, 'Jogo inválido.', ['slots' => 'Jogo inválido.']);
            }
            if ($gap) {
                throw new HttpException(422, 'Posições fora de ordem.', ['slots' => 'Preencha as posições em ordem (1º, depois 2º, depois 3º).']);
            }
            if (in_array($id, $ids, true)) {
                throw new HttpException(422, 'Jogo repetido.', ['slots' => 'O mesmo jogo não pode ocupar duas posições.']);
            }
            $ids[$i + 1] = $id;
        }

        if ($ids) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            $found = Database::all(
                "SELECT id, title, rating FROM games WHERE user_id = ? AND YEAR(finished_at) = ? AND id IN ($in)", [$userId, $year, ...array_values($ids)]
            );
            if (count($found) !== count($ids)) {
                throw new HttpException(422, 'Jogo inválido.', ['slots' => "Escolha apenas jogos que você zerou em $year."]);
            }
            foreach ($found as $g) {
                if ((int) $g['rating'] < self::MIN_RATING_TOP) {
                    throw new HttpException(422, 'Nota baixa demais para o pódio.', ['slots' => "{$g['title']} tem nota {$g['rating']}: só jogos com nota 9, 10 ou 11 podem ser eleitos para o top 3."]);
                }
            }
        }

        Database::transaction(function () use ($userId, $year, $ids) {
            Database::query('DELETE FROM retro_top WHERE user_id = ? AND year = ?', [$userId, $year]);
            foreach ($ids as $pos => $gameId) {
                Database::query('INSERT INTO retro_top (user_id, year, position, game_id) VALUES (?,?,?,?)', [$userId, $year, $pos, $gameId]);
            }
        });
        return $this->show($userId, $year);
    }

    /** Top 3 de cada ano (para o perfil público): [{year, games: [card, ...]}] com os anos mais recentes primeiro. */
    public function tops(int $userId, int $limit = 6): array
    {
        $rows = Database::all(
            'SELECT t.year, t.position, g.id, g.title, g.finished_at, g.release_year, g.playtime_minutes, g.rating, g.difficulty, g.cover_path,
                    p.name AS platform, ge.name AS genre
             FROM retro_top t
             JOIN games g ON g.id = t.game_id AND g.user_id = t.user_id AND YEAR(g.finished_at) = t.year
             JOIN platforms p ON p.id = g.platform_id
             LEFT JOIN genres ge ON ge.id = g.genre_id
             WHERE t.user_id = ? AND g.rating >= ' . self::MIN_RATING_TOP . ' ORDER BY t.year DESC, t.position',
            [$userId]
        );
        $byYear = [];
        foreach ($rows as $r) {
            $byYear[(int) $r['year']][] = $this->card($r);
        }
        $out = [];
        foreach ($byYear as $year => $games) {
            $out[] = ['year' => $year, 'games' => $games];
        }
        return array_slice($out, 0, $limit);
    }

    // ---------- cálculo (usado pela API real e pela página de exemplo) ----------

    private function build(int $year, array $games, ?array $prev, int $xp, array $topIds): array
    {
        $n = count($games);
        $minutes = array_sum(array_column($games, 'playtime_minutes'));
        $months = array_fill(1, 12, ['games' => 0, 'minutes' => 0]);
        $genres = $platforms = [];
        $counts = ['elevens' => 0, 'triple_a' => 0, 'released_now' => 0];
        foreach ($games as $g) {
            $m = (int) substr($g['finished_at'], 5, 2);
            $months[$m]['games']++;
            $months[$m]['minutes'] += $g['playtime_minutes'];
            if ($g['genre']) {
                $genres[$g['genre']] = ($genres[$g['genre']] ?? 0) + 1;
            }
            $platforms[$g['platform']] = ($platforms[$g['platform']] ?? 0) + 1;
            $counts['elevens'] += $g['rating'] === 11 ? 1 : 0;
            $counts['triple_a'] += $g['difficulty'] === 'AAA' ? 1 : 0;
            $counts['released_now'] += $g['release_year'] === $year ? 1 : 0;
        }

        $rank = function (array $map): array {
            $rows = [];
            foreach ($map as $name => $c) {
                $rows[] = ['name' => (string) $name, 'n' => $c];
            }
            usort($rows, fn($a, $b) => $b['n'] <=> $a['n'] ?: strcasecmp($a['name'], $b['name']));
            return array_slice($rows, 0, 5);
        };

        $peak = null;
        foreach ($months as $m => $v) {
            if ($v['games'] > 0 && ($peak === null || $v['games'] > $months[$peak]['games'])) {
                $peak = $m;
            }
        }

        $by = fn(callable $cmp) => (function () use ($games, $cmp) {
            $copy = $games;
            usort($copy, $cmp);
            return $copy[0];
        })();
        $timed = array_values(array_filter($games, fn($g) => $g['playtime_minutes'] > 0)) ?: $games;
        $byId = array_column($games, null, 'id');

        // só jogos com nota 9, 10 ou 11 podem ser eleitos; se um jogo do top for excluído, mudar de ano ou não cumprir a regra, quem estava abaixo sobe: o pódio nunca fica com buraco
        $eligible = array_values(array_filter($games, fn($g) => $g['rating'] >= self::MIN_RATING_TOP));
        $ordered = [];
        foreach ([1, 2, 3] as $pos) {
            if (isset($topIds[$pos], $byId[$topIds[$pos]]) && $byId[$topIds[$pos]]['rating'] >= self::MIN_RATING_TOP) {
                $ordered[] = $byId[$topIds[$pos]];
            }
        }
        $top = [$ordered[0] ?? null, $ordered[1] ?? null, $ordered[2] ?? null];

        $byRating = fn($a, $b) => $b['rating'] <=> $a['rating'] ?: strcmp($b['finished_at'], $a['finished_at']) ?: strcmp($a['title'], $b['title']);
        $candidates = $games;            // todos os jogos do ano (gráficos, meses, busca)
        usort($candidates, $byRating);
        $electable = $eligible;          // só estes podem ir para o pódio
        usort($electable, $byRating);

        return [
            'year'       => $year,
            'games'      => $n,
            'hours'      => round($minutes / 60, 1),
            'avg_rating' => round(array_sum(array_column($games, 'rating')) / $n, 1),
            'xp'         => $xp,
            'prev'       => $prev,
            'months'     => array_map(fn($m) => [
                'm' => $m, 'label' => self::MONTHS[$m - 1], 'games' => $months[$m]['games'], 'hours' => round($months[$m]['minutes'] / 60, 1),
            ], range(1, 12)),
            'peak_month' => $peak === null ? null : ['label' => self::MONTHS[$peak - 1], 'games' => $months[$peak]['games']],
            'genres'     => $rank($genres),
            'platforms'  => $rank($platforms),
            'highlights' => [
                'longest'  => $by(fn($a, $b) => $b['playtime_minutes'] <=> $a['playtime_minutes']),
                'shortest' => (function () use ($timed) { usort($timed, fn($a, $b) => $a['playtime_minutes'] <=> $b['playtime_minutes']); return $timed[0]; })(),
                'best'     => $candidates[0],
                'first'    => $games[0],
                'last'     => $games[$n - 1],
            ] + $counts,
            'can_pick'   => $n >= self::MIN_FOR_TOP && $eligible !== [],
            'eligible'   => count($eligible),
            'min_rating' => self::MIN_RATING_TOP,
            'top'        => $top,
            'candidates' => $candidates,
            'electable'  => $electable,
        ];
    }

    private function card(array $g): array
    {
        return [
            'id'               => (int) $g['id'],
            'title'            => $g['title'],
            'platform'         => $g['platform'],
            'genre'            => $g['genre'],
            'finished_at'      => $g['finished_at'],
            'release_year'     => $g['release_year'] === null ? null : (int) $g['release_year'],
            'playtime_minutes' => (int) $g['playtime_minutes'],
            'rating'           => (int) $g['rating'],
            'difficulty'       => $g['difficulty'],
            'cover_url'        => $this->uploads->url($g['cover_path']),
        ];
    }

    /** Página de exemplo (/retrospectiva/exemplo): ~80 jogos fictícios zerados em 2026, passando pelo mesmo cálculo da versão real. */
    public function demo(): array
    {
        $pool = [];
        $seen = [];
        foreach (GotyService::demoCatalog() as $releaseYear => $list) {
            foreach ($list as [$title, $platform, $genre, $rating]) {
                if (isset($seen[$title])) {
                    continue;
                }
                $seen[$title] = true;
                $pool[] = [$title, $platform, $genre, $rating, $releaseYear];
            }
        }
        $diffs = ['B', 'A', 'C', 'AA', 'B', 'A', 'AAA'];
        $games = [];
        $total = count($pool);
        $start = strtotime('2026-01-03');
        $xp = 0;
        foreach ($pool as $i => [$title, $platform, $genre, $rating, $release]) {
            $minutes = $i % 11 === 4 ? 6000 + ($i * 131) % 3000 : 420 + ($i * 977) % 3600;
            $diff = $diffs[$i % count($diffs)];
            $games[] = [
                'id' => $i + 1, 'title' => $title, 'platform' => $platform, 'genre' => $genre, 'release_year' => $release,
                'finished_at' => date('Y-m-d', $start + (int) floor($i * (286 / max(1, $total - 1))) * 86400),
                'playtime_minutes' => $minutes, 'rating' => $rating, 'difficulty' => $diff, 'cover_url' => null,
            ];
            $xp += 100 + ['C' => 0, 'B' => 15, 'A' => 40, 'AA' => 80, 'AAA' => 150][$diff];
        }
        usort($games, fn($a, $b) => strcmp($a['finished_at'], $b['finished_at']) ?: $a['id'] <=> $b['id']);
        $byTitle = array_column($games, 'id', 'title');
        // Top 3 de exemplo: um lançamento do ano e dois jogos bem antigos (não precisa ser do ano)
        $topIds = [1 => $byTitle['Resident Evil 9'], 2 => $byTitle['Shadow of the Colossus'], 3 => $byTitle['The Legend of Zelda: Ocarina of Time']];

        return [
            'years' => [['year' => 2026, 'games' => count($games), 'hours' => round(array_sum(array_column($games, 'playtime_minutes')) / 60, 1)]],
            'data'  => $this->build(2026, $games, ['games' => count($games) - 17, 'hours' => 612.5], $xp, $topIds),
        ];
    }
}
