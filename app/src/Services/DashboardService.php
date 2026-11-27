<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\{Database, Str};

/**
 * Dados dos blocos "de história" do dashboard: estante, hoje na sua história, carta de jogador,
 * trilha do ano, pessoas, constelação e rio de gêneros. O cálculo recebe listas prontas, então a página de exemplo
 * usa exatamente o mesmo código sobre os jogos fictícios.
 */
final class DashboardService
{
    /** Blocos do painel, na ordem padrão. 'numbers' = só mostram números/XP (somem no modo calmo). */
    public const WIDGETS = [
        'numbers'       => ['label' => 'Números e meta do ano', 'numbers' => true,  'span' => 'full', 'group' => 'numbers'],
        'shelf'         => ['label' => 'Estante: o que está jogando e os últimos zerados', 'numbers' => false, 'span' => 'full', 'group' => 'now'],
        'week'          => ['label' => 'Resumo da semana', 'numbers' => false, 'span' => 'full', 'group' => 'now'],
        'next'          => ['label' => 'Próximos passos', 'numbers' => false, 'span' => 'full', 'group' => 'now'],
        'today'         => ['label' => 'Hoje na sua história', 'numbers' => false, 'span' => 'full', 'group' => 'story'],
        'card'          => ['label' => 'Carta de jogador', 'numbers' => false, 'span' => 'full', 'group' => 'story'],
        'constellation' => ['label' => 'Constelação: lançamento × quando zerou', 'numbers' => false, 'span' => 'half', 'group' => 'story'],
        'river'         => ['label' => 'Rio de gêneros', 'numbers' => false, 'span' => 'half', 'group' => 'story'],
        'people'        => ['label' => 'Com quem você jogou', 'numbers' => false, 'span' => 'half', 'group' => 'story'],
        'soundtrack'    => ['label' => 'Trilha do ano', 'numbers' => false, 'span' => 'half', 'group' => 'story'],
        'months'        => ['label' => 'Jogos por mês', 'numbers' => true,  'span' => 'half', 'group' => 'numbers'],
        'breakdown'     => ['label' => 'Gêneros e sistemas', 'numbers' => true,  'span' => 'half', 'group' => 'numbers'],
    ];

    private const WEEKDAYS = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'];

    public function __construct(
        private GameService $games = new GameService(),
        private TimeService $time = new TimeService(),
        private XpService $xp = new XpService(),
    ) {}

    // ====================== Dados reais ======================

    public function forUser(int $userId, string $username, int $year): array
    {
        $games = $this->games->allChronological($userId);

        $notes = [];
        foreach (Database::all('SELECT game_id, note_date, body FROM game_notes WHERE user_id = ? AND game_id IS NOT NULL ORDER BY note_date DESC, id DESC LIMIT 3000', [$userId]) as $r) {
            $notes[] = ['game_id' => (int) $r['game_id'], 'note_date' => $r['note_date'], 'body' => $r['body']];
        }

        $playing = (new BacklogService())->playing($userId, 4);   // já vem ordenado pela anotação mais recente, com last_note

        // honras: GOTY de cada ano e best of de sistemas, gêneros, empresas e franquias
        $honors = [];
        foreach ((new GotyService())->picks($userId) as $p) {
            $honors[] = ['game_id' => (int) $p['game']['id'], 'kind' => 'goty', 'label' => 'GOTY ' . $p['year'], 'note' => $p['note']];
        }
        $honors = [...$honors, ...(new BestOfService())->honors($userId)];

        return $this->compute($games, $notes, $playing, $year, $username, date('Y-m-d'), $honors, (new NextStepsService())->forUser($userId));
    }

    // ====================== Cálculo (compartilhado com o exemplo) ======================

    /**
     * @param list<array> $games    jogos hidratados (GameService / DemoCatalog), em ordem cronológica
     * @param list<array> $notes    notas de jogos do catálogo: game_id, note_date, body
     * @param list<array> $playing  jogos do backlog em andamento (com last_note opcional)
     * @param list<array> $honors   GOTY e best of do usuário: game_id, kind (goty|best), label, note?
     */
    public function compute(array $games, array $notes, array $playing, int $year, string $username, string $today, array $honors = [], array $next = []): array
    {
        $byId = [];
        foreach ($games as $g) {
            $byId[$g['id']] = $g;
        }
        $slim = fn(array $g) => [
            'id' => $g['id'], 'title' => $g['title'], 'platform' => $g['platform'], 'finished_at' => $g['finished_at'],
            'rating' => $g['rating'], 'difficulty' => $g['difficulty'] ?? null, 'cover_url' => $g['cover_url'] ?? null,
        ];

        $desc = $games;
        usort($desc, fn($a, $b) => [$b['finished_at'], $b['id']] <=> [$a['finished_at'], $a['id']]);
        $inYear = array_values(array_filter($games, fn($g) => (int) substr($g['finished_at'], 0, 4) === $year));

        $onThisDay = $this->time->onThisDayFrom($games, $today);

        return [
            'year'          => $year,
            'today'         => $today,
            'has_games'     => count($games) > 0,
            'recent'        => array_map($slim, array_slice($desc, 0, 10)),
            'playing'       => array_map(fn($p) => [
                'id' => $p['id'], 'title' => $p['title'], 'platform' => $p['platform'], 'started_at' => $p['started_at'] ?? null,
                'days_playing' => $p['days_playing'] ?? null, 'cover_url' => $p['cover_url'] ?? null,
                'last_note' => isset($p['last_note']) ? ['date' => $p['last_note']['date'], 'body' => $this->excerpt($p['last_note']['body'])] : null,
            ], $playing),
            'playing_max'   => BacklogService::MAX_PLAYING,
            'on_this_day'   => $onThisDay,
            'season_days'   => $onThisDay ? max(array_map(fn($g) => abs($g['days_diff']), $onThisDay)) : 0,   // a que distância de hoje fica o mais longe da lista
            'season_rule'   => TimeService::SEASON_DAYS,
            'memory_pick'   => $this->memoryPick($honors, $byId, $today),
            'soundtrack'    => $this->soundtrack($inYear),
            'people'        => $this->people($games),
            'constellation_count' => count(array_filter($games, fn($g) => !empty($g['release_year']))),
            'next'          => $next,
            'river'         => $this->river($games),
            'card'          => $this->card($games, $username),
            'hello'         => $this->hello($games, $playing, $today),
            'week'          => $this->week($games, $notes, $today),
        ];
    }

    // ---------- blocos ----------

    /** Só a trilha de um ano (o resto do painel não depende do ano escolhido). */
    public function soundtrackFor(int $userId, int $year): array
    {
        $games = $this->games->allChronological($userId);
        return $this->soundtrack(array_values(array_filter($games, fn($g) => (int) substr($g['finished_at'], 0, 4) === $year)));
    }

    /**
     * Resumo da semana: os últimos 7 dias (até hoje) contra os 7 anteriores,
     * as notas de diário escritas e a sequência de semanas seguidas zerando algo.
     */
    public function week(array $games, array $notes, string $today): array
    {
        $slim = fn(array $g) => [
            'id' => $g['id'], 'title' => $g['title'], 'platform' => $g['platform'], 'finished_at' => $g['finished_at'],
            'rating' => $g['rating'], 'cover_url' => $g['cover_url'] ?? null,
        ];
        $day = fn(string $d) => intdiv((int) strtotime(substr($d, 0, 10) . ' 12:00:00'), 86400);
        $t = $day($today);
        $in = fn(array $g, int $from, int $to) => ($d = $day($g['finished_at'])) >= $from && $d <= $to;
        $now = array_values(array_filter($games, fn($g) => $in($g, $t - 6, $t)));
        $prev = array_values(array_filter($games, fn($g) => $in($g, $t - 13, $t - 7)));
        $mins = fn(array $list) => array_sum(array_map(fn($g) => (int) $g['playtime_minutes'], $list));

        // semanas (janelas de 7 dias terminando hoje) com pelo menos um jogo zerado
        $windows = [];
        foreach ($games as $g) {
            $k = intdiv($t - $day($g['finished_at']), 7);
            if ($k >= 0) {
                $windows[$k] = true;
            }
        }
        $streak = 0;
        for ($k = isset($windows[0]) ? 0 : 1; isset($windows[$k]); $k++) {
            $streak++;
        }

        usort($now, fn($a, $b) => $b['rating'] <=> $a['rating']);
        return [
            'games'      => count($now),
            'minutes'    => $mins($now),
            'prev_games' => count($prev),
            'prev_minutes' => $mins($prev),
            'notes'      => count(array_filter($notes, fn($n) => ($d = $day($n['note_date'])) >= $t - 6 && $d <= $t)),
            'streak'     => $streak,
            'list'       => array_map($slim, array_slice($now, 0, 6)),
        ];
    }

    /**
     * Hoje na sua história: entre os jogos com honra (GOTY ou best of), o que foi zerado mais perto da data de hoje
     * (só dia e mês contam; o ano não importa). Empate: o mais antigo primeiro.
     */
    private function memoryPick(array $honors, array $byId, string $today): ?array
    {
        $labels = [];
        $note = [];
        foreach ($honors as $h) {
            if (!isset($byId[$h['game_id']])) {
                continue;
            }
            $labels[$h['game_id']][] = ['kind' => $h['kind'], 'label' => $h['label']];
            if (!empty($h['note'])) {
                $note[$h['game_id']] = $h['note'];
            }
        }
        if (!$labels) {
            return null;
        }

        $year = substr($today, 0, 4);
        // dia do ano de uma data, trazida para o ano de hoje (29/02 vira 01/03 em ano não bissexto)
        $dayOf = fn(string $d) => (int) date('z', strtotime($year . substr($d, 4)));
        $t = $dayOf($today);
        $best = null;
        foreach (array_keys($labels) as $id) {
            $g = $byId[$id];
            $diff = abs($dayOf($g['finished_at']) - $t);
            $dist = min($diff, 365 - $diff); // dezembro e janeiro são vizinhos
            $key = [$dist, $g['finished_at'], $id];
            if ($best === null || $key < $best[0]) {
                $best = [$key, $g, $dist];
            }
        }
        [, $g, $dist] = $best;
        $mine = $labels[$g['id']];
        usort($mine, fn($a, $b) => [$a['kind'] !== 'goty', $a['label']] <=> [$b['kind'] !== 'goty', $b['label']]); // GOTY primeiro
        return [
            'id' => $g['id'], 'title' => $g['title'], 'platform' => $g['platform'], 'rating' => $g['rating'],
            'cover_url' => $g['cover_url'] ?? null, 'finished_at' => $g['finished_at'],
            'years_ago' => (int) $year - (int) substr($g['finished_at'], 0, 4),
            'days_away' => $dist,
            'honors' => $mine,
            'note' => isset($note[$g['id']]) ? $this->excerpt($note[$g['id']], 300) : null,
        ];
    }

    public function soundtrack(array $inYear): array
    {
        $out = [];
        foreach ($inYear as $g) {
            $title = $g['memory']['music_title'] ?? null;
            if ($title) {
                $out[] = ['game_id' => $g['id'], 'game' => $g['title'], 'music_title' => $title, 'music_url' => $g['memory']['music_url'] ?? null, 'finished_at' => $g['finished_at']];
            }
        }
        return $out;
    }

    private function people(array $games): array
    {
        $by = [];
        foreach ($games as $g) {
            foreach ($g['memory']['people'] ?? [] as $name) {
                $k = Str::key($name);
                $by[$k]['name'] ??= $name;
                $by[$k]['games'][] = ['id' => $g['id'], 'title' => $g['title'], 'cover_url' => $g['cover_url'] ?? null];
            }
        }
        $rows = array_map(fn($p) => ['name' => $p['name'], 'n' => count($p['games']), 'games' => array_slice($p['games'], 0, 5)], array_values($by));
        usort($rows, fn($a, $b) => $b['n'] <=> $a['n'] ?: strcasecmp($a['name'], $b['name']));
        return array_slice($rows, 0, 12);
    }

    /**
     * Pontos da constelação em formato compacto (é o maior pedaço do dashboard): colunas fixas e a pasta das capas uma vez só.
     * cols: id, título, sistema, ano de lançamento, quando zerou (ano fracionado), horas, nota, arquivo da capa.
     * Jogos sem ano de lançamento ficam de fora (não há onde desenhá-los).
     */
    public function constellation(array $games): array
    {
        $games = array_values(array_filter($games, fn($g) => !empty($g['release_year'])));
        $covers = array_filter(array_map(fn($g) => $g['cover_url'] ?? null, $games));
        $dir = $covers ? substr((string) reset($covers), 0, (int) strrpos((string) reset($covers), '/') + 1) : '';
        $rows = array_map(function (array $g) use ($dir) {
            $d = strtotime($g['finished_at']);
            $cover = $g['cover_url'] ?? null;
            if ($cover !== null && $dir !== '' && str_starts_with($cover, $dir)) {
                $cover = substr($cover, strlen($dir));
            }
            return [
                $g['id'], $g['title'], $g['platform'], (int) $g['release_year'],
                round((int) date('Y', $d) + ((int) date('z', $d)) / 366, 3), round($g['playtime_minutes'] / 60, 1),
                $g['rating'], $cover,
            ];
        }, $games);
        return ['cover_dir' => $dir, 'points' => $rows];
    }

    /** Constelação dos jogos reais do usuário (GET /api/dashboard/constellation). */
    public function constellationFor(int $userId): array
    {
        return $this->constellation($this->games->allChronological($userId));
    }

    /** Gêneros ao longo do tempo: por ano (3+ anos) ou por trimestre (menos que isso). */
    private function river(array $games): array
    {
        if (!$games) {
            return ['labels' => [], 'series' => []];
        }
        $years = array_unique(array_map(fn($g) => (int) substr($g['finished_at'], 0, 4), $games));
        $byYear = count($years) >= 3;
        $period = fn(array $g) => $byYear ? substr($g['finished_at'], 0, 4) : substr($g['finished_at'], 0, 4) . ' T' . (intdiv((int) substr($g['finished_at'], 5, 2) - 1, 3) + 1);
        $labels = array_values(array_unique(array_map($period, $games)));
        sort($labels);

        $totals = [];
        foreach ($games as $g) {
            $totals[$g['genre'] ?: 'Sem gênero'] = ($totals[$g['genre'] ?: 'Sem gênero'] ?? 0) + 1;
        }
        arsort($totals);
        $top = array_slice(array_keys($totals), 0, 6);

        $series = [];
        foreach ($top as $name) {
            $series[$name] = array_fill_keys($labels, 0);
        }
        $series['Outros'] = array_fill_keys($labels, 0);
        foreach ($games as $g) {
            $name = $g['genre'] ?: 'Sem gênero';
            $series[in_array($name, $top, true) ? $name : 'Outros'][$period($g)]++;
        }
        if (array_sum($series['Outros']) === 0) {
            unset($series['Outros']);
        }
        return ['labels' => $labels, 'series' => array_map(fn($name, $row) => ['name' => $name, 'data' => array_values($row)], array_keys($series), $series)];
    }

    private function card(array $games, string $username): array
    {
        $n = count($games);
        if ($n === 0) {
            return ['username' => $username, 'empty' => true];
        }
        $minutes = array_sum(array_column($games, 'playtime_minutes'));
        $xp = array_sum(array_column($games, 'xp'));
        $top = function (string $field) use ($games): ?array {
            $c = [];
            foreach ($games as $g) {
                if (!empty($g[$field])) {
                    $c[$g[$field]] = ($c[$g[$field]] ?? 0) + 1;
                }
            }
            arsort($c);
            return $c ? ['name' => (string) array_key_first($c), 'n' => reset($c)] : null;
        };
        $genre = $top('genre');
        $platform = $top('platform');
        $lags = array_map(fn($g) => max(0, (int) substr($g['finished_at'], 0, 4) - (int) $g['release_year']), $games);
        $avgLag = array_sum($lags) / $n;
        $avgHours = $minutes / 60 / $n;
        $platforms = count(array_unique(array_column($games, 'platform')));
        $genres = count(array_unique(array_filter(array_column($games, 'genre'))));
        $hard = count(array_filter($games, fn($g) => in_array($g['difficulty'], ['A', 'AA', 'AAA'], true))) / $n;

        $title = match (true) {
            $n >= 5 && $avgLag >= 12                      => 'Arqueólogo gamer',
            $n >= 5 && $avgLag <= 2                       => 'Caçador de lançamentos',
            $avgHours >= 60                               => 'Maratonista',
            $platforms >= 8                               => 'Colecionador de sistemas',
            $avgHours <= 12 && $n >= 5                    => 'Velocista',
            $genre && $genre['n'] / $n >= 0.35 && $n >= 5 => 'Mestre de ' . $genre['name'],
            default                                       => 'Explorador',
        };

        $favorites = $games;
        usort($favorites, fn($a, $b) => [$b['rating'], $b['playtime_minutes']] <=> [$a['rating'], $a['playtime_minutes']]);

        $phrase = 'Você zera um jogo, em média, ' . number_format($avgLag, 1, ',', '.') . ' anos depois do lançamento'
            . ($genre ? ', gosta de ' . $genre['name'] : '') . ($platform ? ' e joga mais no ' . $platform['name'] : '') . '.';

        return [
            'username' => $username, 'empty' => false, 'title' => $title, 'phrase' => $phrase,
            'level' => $this->xp->levelFor($xp), 'xp' => $xp,
            'games' => $n, 'hours' => (int) round($minutes / 60),
            'avg_rating' => round(array_sum(array_column($games, 'rating')) / $n, 1),
            'top_genre' => $genre['name'] ?? null, 'top_platform' => $platform['name'] ?? null,
            'attributes' => [
                ['k' => 'Nostalgia', 'v' => (int) min(100, round($avgLag / 20 * 100))],
                ['k' => 'Fôlego', 'v' => (int) min(100, round($avgHours / 80 * 100))],
                ['k' => 'Exploração', 'v' => (int) min(100, $platforms * 7 + $genres * 4)],
                ['k' => 'Desafio', 'v' => (int) round($hard * 100)],
            ],
            'favorites' => array_map(fn($g) => ['title' => $g['title'], 'cover_url' => $g['cover_url'] ?? null], array_slice($favorites, 0, 3)),
        ];
    }

    /** Uma frase de contexto, sem cobrança. Null quando não há nada de relevante a dizer. */
    private function hello(array $games, array $playing, string $today): ?string
    {
        foreach ($playing as $p) {
            $since = $p['last_note']['date'] ?? ($p['started_at'] ?? null);
            if ($since) {
                $days = (int) floor((strtotime($today) - strtotime($since)) / 86400);
                if ($days >= 10) {
                    return $p['title'] . ' está esperando por você há ' . $days . ' dias. Sem pressa: quando quiser, anote como foi a última sessão.';
                }
            }
        }
        if (count($games) >= 8) {
            $by = array_fill(0, 7, 0);
            foreach ($games as $g) {
                $by[(int) date('w', strtotime($g['finished_at']))]++;
            }
            $fav = array_search(max($by), $by, true);
            if ($fav === (int) date('w', strtotime($today)) && max($by) / count($games) >= 0.18) {
                return 'Hoje é ' . self::WEEKDAYS[$fav] . ', o dia em que você mais zera jogos.';
            }
        }
        return null;
    }

    private function excerpt(string $text, int $max = 160): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
        return mb_strlen($text) > $max ? rtrim(mb_substr($text, 0, $max - 1)) . '…' : $text;
    }

    // ====================== Layout do painel ======================

    /** Normaliza {order, hidden}: só ids conhecidos, sem repetição; o que faltar entra no fim, na ordem padrão. */
    public static function normalizeLayout(mixed $in): array
    {
        $known = array_keys(self::WIDGETS);
        $order = [];
        foreach ((array) ($in['order'] ?? []) as $id) {
            if (is_string($id) && in_array($id, $known, true) && !in_array($id, $order, true)) {
                $order[] = $id;
            }
        }
        foreach ($known as $id) {
            if (!in_array($id, $order, true)) {
                $order[] = $id;
            }
        }
        $hidden = [];
        foreach ((array) ($in['hidden'] ?? []) as $id) {
            if (is_string($id) && in_array($id, $known, true) && !in_array($id, $hidden, true)) {
                $hidden[] = $id;
            }
        }
        return ['order' => $order, 'hidden' => $hidden];
    }

    public static function layoutFor(?string $json): array
    {
        return self::normalizeLayout($json ? json_decode($json, true) : []);
    }
}
