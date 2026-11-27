<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\HttpException;

/**
 * Máquina do tempo: o catálogo "como era" em uma data e o que você zerava em dias parecidos de anos anteriores.
 * Tudo é calculado das datas de finalização dos jogos que existem hoje (jogos apagados não aparecem).
 */
final class TimeService
{
    public function __construct(
        private XpService $xp = new XpService(),
        private StatsService $stats = new StatsService(),
    ) {}

    public function snapshot(int $userId, string $date): array
    {
        return $this->snapshotFrom($this->stats->gamesFor($userId), $date);
    }

    public function onThisDay(int $userId): array
    {
        return $this->onThisDayFrom($this->stats->gamesFor($userId), date('Y-m-d'));
    }

    /** @param list<array> $games jogos no formato de StatsService::gamesFor / DemoCatalog::games */
    public function snapshotFrom(array $games, string $date, ?string $today = null): array
    {
        $today ??= date('Y-m-d');
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if ($d === false || $d->format('Y-m-d') !== $date || $date < '1970-01-01') {
            throw new HttpException(422, 'Data inválida (AAAA-MM-DD).', ['date' => 'Data inválida.']);
        }
        if ($date > $today) {
            $date = $today;
        }

        $dates = array_column($games, 'finished_at');
        $upto = array_values(array_filter($games, fn($g) => $g['finished_at'] <= $date));
        usort($upto, fn($a, $b) => [$b['finished_at'], $b['id']] <=> [$a['finished_at'], $a['id']]);

        $xp = array_sum(array_column($upto, 'xp'));
        $ts = strtotime($date);
        $around = array_values(array_filter($games, fn($g) => abs(strtotime($g['finished_at']) - $ts) <= 45 * 86400));
        usort($around, fn($a, $b) => abs(strtotime($a['finished_at']) - $ts) <=> abs(strtotime($b['finished_at']) - $ts));

        $row = fn(array $g) => [
            'id' => $g['id'], 'title' => $g['title'], 'platform' => $g['platform'], 'finished_at' => $g['finished_at'],
            'rating' => $g['rating'], 'cover_url' => $g['cover_url'] ?? null,
        ];

        return [
            'date' => $date, 'today' => $today,
            'first' => $dates ? min($dates) : null,
            'games' => count($upto),
            'minutes' => array_sum(array_column($upto, 'playtime_minutes')),
            'platforms' => count(array_unique(array_column($upto, 'platform'))),
            'xp' => $xp, 'level' => $this->xp->levelFor($xp),
            'recent' => array_map($row, array_slice($upto, 0, 24)),
            'around' => array_map($row, array_slice($around, 0, 12)),
        ];
    }

    /** Quantos jogos de outros anos a aba "Em outros anos" traz (os mais perto da data de hoje, sem limite de dias). */
    public const ON_THIS_DAY_LIMIT = 10;
    /** Regra de proximidade: valem os jogos zerados até este número de dias antes ou depois de hoje (naquele ano). */
    public const SEASON_DAYS = 5;

    /**
     * Até ON_THIS_DAY_LIMIT jogos zerados em anos anteriores, dentro de SEASON_DAYS dias antes ou depois de hoje, os mais perto
     * da data primeiro. Se nenhum cair nessa janela, a lista não fica vazia: traz os mais perto de hoje, a qualquer distância
     * (só fica vazia se não houver nenhum jogo zerado em anos anteriores).
     * days_diff: diferença em dias para "hoje" naquele ano (negativo = antes, positivo = depois, 0 = mesmo dia).
     */
    public function onThisDayFrom(array $games, string $today): array
    {
        $year = (int) substr($today, 0, 4);
        [$m, $d] = array_map('intval', explode('-', substr($today, 5)));
        $ref = function (int $y) use ($m, $d): int {   // "hoje" no ano y (29/02 vira 28/02 se o ano não for bissexto)
            return gmmktime(0, 0, 0, $m, ($m === 2 && $d === 29 && !checkdate(2, 29, $y)) ? 28 : $d, $y);
        };
        $out = [];
        foreach ($games as $g) {
            [$gy, $gm, $gd] = array_map('intval', explode('-', $g['finished_at']));
            $t = gmmktime(0, 0, 0, $gm, $gd, $gy);
            if ($gy >= $year) {
                continue;   // só jogos de anos anteriores: a aba é "Em outros anos"
            }
            $best = null;
            foreach ([$gy - 1, $gy, $gy + 1] as $y) {   // a data de hoje no ano anterior, no mesmo e no seguinte (atravessa a virada do ano)
                $diff = (int) round(($t - $ref($y)) / 86400);
                if ($best === null || abs($diff) < abs($best[1])) {
                    $best = [$y, $diff];
                }
            }
            if ($best !== null) {
                $out[] = [
                    'id' => $g['id'], 'title' => $g['title'], 'platform' => $g['platform'], 'finished_at' => $g['finished_at'],
                    'rating' => $g['rating'], 'cover_url' => $g['cover_url'] ?? null, 'years_ago' => $year - $gy, 'days_diff' => $best[1],
                ];
            }
        }
        // mais perto de hoje primeiro; empate: o ano mais recente, depois a maior nota
        usort($out, fn($a, $b) => abs($a['days_diff']) <=> abs($b['days_diff']) ?: $a['years_ago'] <=> $b['years_ago'] ?: $b['rating'] <=> $a['rating']);
        $near = array_values(array_filter($out, fn($g) => abs($g['days_diff']) <= self::SEASON_DAYS));
        return array_slice($near ?: $out, 0, self::ON_THIS_DAY_LIMIT);
    }
}
