<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Jogos fictícios usados pelo gerador de dados de exemplo (bin/seed_demo.php) e pela página /jogos/exemplo.
 */
final class DemoCatalog
{
    /** @return list<array{0:string,1:string,2:string,3:string,4:string}> [título, sistema, desenvolvedora, publisher, gêneros separados por vírgula] */
    public static function entries(): array
    {
        return [
            ['Resident Evil 4', 'GameCube', 'Capcom', 'Capcom', 'Survival Horror,Ação'],
            ['Resident Evil 4', 'Wii', 'Capcom', 'Capcom', 'Survival Horror,Ação'],
            ['Resident Evil 2 Remake', 'PS4', 'Capcom', 'Capcom', 'Survival Horror'],
            ['Silent Hill 2', 'PS2', 'Konami', 'Konami', 'Survival Horror'],
            ['God of War', 'PS4', 'Santa Monica Studio', 'Sony Interactive Entertainment', 'Ação,Aventura'],
            ['God of War Ragnarök', 'PS5', 'Santa Monica Studio', 'Sony Interactive Entertainment', 'Ação,Aventura'],
            ['The Last of Us', 'PS3', 'Naughty Dog', 'Sony Computer Entertainment', 'Ação,Aventura'],
            ['The Last of Us Part II', 'PS4', 'Naughty Dog', 'Sony Interactive Entertainment', 'Ação,Aventura'],
            ['Uncharted 4', 'PS4', 'Naughty Dog', 'Sony Interactive Entertainment', 'Ação,Aventura'],
            ['Bloodborne', 'PS4', 'FromSoftware', 'Sony Computer Entertainment', 'RPG de Ação'],
            ['Dark Souls', 'PS3', 'FromSoftware', 'Namco Bandai', 'RPG de Ação'],
            ['Dark Souls III', 'PC', 'FromSoftware', 'Bandai Namco', 'RPG de Ação'],
            ['Sekiro: Shadows Die Twice', 'PC', 'FromSoftware', 'Activision', 'Ação,Aventura'],
            ['Elden Ring', 'PC', 'FromSoftware', 'Bandai Namco', 'RPG de Ação'],
            ['The Legend of Zelda: Ocarina of Time', 'Nintendo 64', 'Nintendo EAD', 'Nintendo', 'Aventura'],
            ['The Legend of Zelda: Breath of the Wild', 'Switch', 'Nintendo EPD', 'Nintendo', 'Aventura'],
            ['The Legend of Zelda: Tears of the Kingdom', 'Switch', 'Nintendo EPD', 'Nintendo', 'Aventura'],
            ['Super Mario 64', 'Nintendo 64', 'Nintendo EAD', 'Nintendo', 'Plataforma'],
            ['Super Mario Odyssey', 'Switch', 'Nintendo EPD', 'Nintendo', 'Plataforma'],
            ['Metroid Prime', 'GameCube', 'Retro Studios', 'Nintendo', 'Aventura,Tiro'],
            ['Metroid Dread', 'Switch', 'MercurySteam', 'Nintendo', 'Plataforma,Ação'],
            ['Hollow Knight', 'PC', 'Team Cherry', 'Team Cherry', 'Metroidvania'],
            ['Hades', 'PC', 'Supergiant Games', 'Supergiant Games', 'Roguelike,Ação'],
            ['Celeste', 'Switch', 'Maddy Makes Games', 'Maddy Makes Games', 'Plataforma'],
            ['Cuphead', 'PC', 'Studio MDHR', 'Studio MDHR', 'Plataforma,Ação'],
            ['Persona 5 Royal', 'PS4', 'Atlus', 'Atlus', 'JRPG'],
            ['Final Fantasy VII', 'PS1', 'Square', 'Sony Computer Entertainment', 'JRPG'],
            ['Final Fantasy X', 'PS2', 'Square', 'Square', 'JRPG'],
            ['Chrono Trigger', 'SNES', 'Square', 'Square', 'JRPG'],
            ['Dragon Quest XI', 'PS4', 'Square Enix', 'Square Enix', 'JRPG'],
            ['The Witcher 3: Wild Hunt', 'PC', 'CD Projekt Red', 'CD Projekt', 'RPG'],
            ['Cyberpunk 2077', 'PC', 'CD Projekt Red', 'CD Projekt', 'RPG,Tiro'],
            ['Red Dead Redemption 2', 'PS4', 'Rockstar Games', 'Rockstar Games', 'Ação,Mundo Aberto'],
            ['Grand Theft Auto V', 'PS4', 'Rockstar North', 'Rockstar Games', 'Ação,Mundo Aberto'],
            ['Grand Theft Auto: San Andreas', 'PS2', 'Rockstar North', 'Rockstar Games', 'Ação,Mundo Aberto'],
            ['Metal Gear Solid 3', 'PS2', 'Konami', 'Konami', 'Stealth,Ação'],
            ['Metal Gear Solid V: The Phantom Pain', 'PC', 'Kojima Productions', 'Konami', 'Stealth,Ação'],
            ['Death Stranding', 'PS4', 'Kojima Productions', 'Sony Interactive Entertainment', 'Aventura'],
            ['Devil May Cry 5', 'PC', 'Capcom', 'Capcom', 'Hack and Slash,Ação'],
            ['Bayonetta', 'Xbox 360', 'PlatinumGames', 'Sega', 'Hack and Slash,Ação'],
            ['Halo: Combat Evolved', 'Xbox', 'Bungie', 'Microsoft', 'Tiro'],
            ['Halo 3', 'Xbox 360', 'Bungie', 'Microsoft Game Studios', 'Tiro'],
            ['Portal 2', 'PC', 'Valve', 'Valve', 'Puzzle'],
            ['Half-Life 2', 'PC', 'Valve', 'Valve', 'Tiro'],
            ['DOOM Eternal', 'PC', 'id Software', 'Bethesda', 'Tiro'],
            ['Control', 'PS5', 'Remedy Entertainment', '505 Games', 'Ação,Aventura'],
            ['Alan Wake 2', 'PS5', 'Remedy Entertainment', 'Epic Games Publishing', 'Survival Horror'],
            ['Stardew Valley', 'PC', 'ConcernedApe', 'ConcernedApe', 'Simulação'],
            ['Disco Elysium', 'PC', 'ZA/UM', 'ZA/UM', 'RPG'],
            ['Outer Wilds', 'PC', 'Mobius Digital', 'Annapurna Interactive', 'Aventura,Puzzle'],
            ['Sonic Mania', 'Switch', 'Headcannon', 'Sega', 'Plataforma'],
            ['Castlevania: Symphony of the Night', 'PS1', 'Konami', 'Konami', 'Metroidvania'],
            ['Street Fighter II', 'SNES', 'Capcom', 'Capcom', 'Luta'],
            ['Tekken 3', 'PS1', 'Namco', 'Namco', 'Luta'],
            ['Mortal Kombat 11', 'PS4', 'NetherRealm Studios', 'Warner Bros. Interactive', 'Luta'],
            ['Gran Turismo 4', 'PS2', 'Polyphony Digital', 'Sony Computer Entertainment', 'Corrida'],
            ['Forza Horizon 5', 'Xbox Series', 'Playground Games', 'Xbox Game Studios', 'Corrida'],
            ['Pokémon Emerald', 'Game Boy Advance', 'Game Freak', 'Nintendo', 'JRPG'],
            ['Kirby and the Forgotten Land', 'Switch', 'HAL Laboratory', 'Nintendo', 'Plataforma'],
            ['Astro Bot', 'PS5', 'Team Asobi', 'Sony Interactive Entertainment', 'Plataforma'],
        ];
    }

    /** Ano de lançamento de cada título. */
    public static function releaseYears(): array
    {
        return [
            'Resident Evil 4' => 2005, 'Resident Evil 2 Remake' => 2019, 'Silent Hill 2' => 2001, 'God of War' => 2018, 'God of War Ragnarök' => 2022,
            'The Last of Us' => 2013, 'The Last of Us Part II' => 2020, 'Uncharted 4' => 2016, 'Bloodborne' => 2015, 'Dark Souls' => 2011,
            'Dark Souls III' => 2016, 'Sekiro: Shadows Die Twice' => 2019, 'Elden Ring' => 2022, 'The Legend of Zelda: Ocarina of Time' => 1998,
            'The Legend of Zelda: Breath of the Wild' => 2017, 'The Legend of Zelda: Tears of the Kingdom' => 2023, 'Super Mario 64' => 1996,
            'Super Mario Odyssey' => 2017, 'Metroid Prime' => 2002, 'Metroid Dread' => 2021, 'Hollow Knight' => 2017, 'Hades' => 2020, 'Celeste' => 2018,
            'Cuphead' => 2017, 'Persona 5 Royal' => 2019, 'Final Fantasy VII' => 1997, 'Final Fantasy X' => 2001, 'Chrono Trigger' => 1995,
            'Dragon Quest XI' => 2017, 'The Witcher 3: Wild Hunt' => 2015, 'Cyberpunk 2077' => 2020, 'Red Dead Redemption 2' => 2018,
            'Grand Theft Auto V' => 2013, 'Grand Theft Auto: San Andreas' => 2004, 'Metal Gear Solid 3' => 2004, 'Metal Gear Solid V: The Phantom Pain' => 2015,
            'Death Stranding' => 2019, 'Devil May Cry 5' => 2019, 'Bayonetta' => 2009, 'Halo: Combat Evolved' => 2001, 'Halo 3' => 2007,
            'Portal 2' => 2011, 'Half-Life 2' => 2004, 'DOOM Eternal' => 2020, 'Control' => 2019, 'Alan Wake 2' => 2023, 'Stardew Valley' => 2016,
            'Disco Elysium' => 2019, 'Outer Wilds' => 2019, 'Sonic Mania' => 2017, 'Castlevania: Symphony of the Night' => 1997, 'Street Fighter II' => 1991,
            'Tekken 3' => 1997, 'Mortal Kombat 11' => 2019, 'Gran Turismo 4' => 2004, 'Forza Horizon 5' => 2021, 'Pokémon Emerald' => 2004,
            'Kirby and the Forgotten Land' => 2022, 'Astro Bot' => 2024,
        ];
    }

    /** Nome de arquivo (sem extensão) da capa de demonstração de um jogo. */
    public static function slug(string $title, string $platform): string
    {
        $s = strtr(mb_strtolower($title . ' ' . $platform), ['é' => 'e', 'ö' => 'o', 'ã' => 'a', 'á' => 'a', 'ç' => 'c', 'ó' => 'o', 'í' => 'i', 'ê' => 'e', 'ô' => 'o', 'â' => 'a', 'ú' => 'u']);
        return trim((string) preg_replace('/[^a-z0-9]+/', '-', $s), '-');
    }

    /**
     * Os jogos no mesmo formato da API (/api/games), prontos para a página de exemplo do catálogo.
     * Tudo determinístico: datas espalhadas pelos últimos ~22 meses, notas, horas, tarefas e XP.
     */
    public static function games(string $basePath): array
    {
        $years = self::releaseYears();
        $diffs = ['C', 'C', 'B', 'B', 'B', 'A', 'A', 'AA', 'AA', 'AAA'];
        $ratings = [6, 7, 7, 8, 8, 8, 9, 9, 9, 10, 10, 11];
        $bonus = ['C' => 0, 'B' => 15, 'A' => 40, 'AA' => 80, 'AAA' => 150];
        $tierXp = [1 => 25, 2 => 60, 3 => 120];
        $today = strtotime('2026-10-15');

        $out = [];
        foreach (self::entries() as $i => [$title, $platform, $dev, $pub, $genres]) {
            $diff = $diffs[($i * 3) % count($diffs)];
            $tasks = [];
            if ($i % 4 === 0) {
                $tasks[] = ['id' => $i * 10 + 1, 'description' => 'Terminei no modo mais difícil', 'tier' => 3];
            }
            if ($i % 7 === 0) {
                $tasks[] = ['id' => $i * 10 + 2, 'description' => 'Todos os colecionáveis', 'tier' => 2];
            }
            $xp = 100 + $bonus[$diff] + array_sum(array_map(fn($t) => $tierXp[$t['tier']], $tasks));
            $saveStates = $i % 11 === 3;
            if ($saveStates) {
                $xp = (int) round($xp / 2);
            }
            $out[] = [
                'id'               => $i + 1,
                'title'            => $title,
                'platform'         => $platform,
                'developer'        => $dev,
                'publisher'        => $pub,
                'genre'            => explode(',', $genres)[0],
                'finished_at'      => date('Y-m-d', $today - (int) ($i * 11.3 * 86400)),
                'release_year'     => $years[$title] ?? 2010,
                'playtime_minutes' => 240 + (($i * 1237) % 4800) + ($i % 9 === 0 ? 3000 : 0),
                'rating'           => $ratings[($i * 7) % count($ratings)],
                'difficulty'       => $diff,
                'cover_url'        => $basePath . '/assets/demo/covers/' . self::slug($title, $platform) . '.webp',
                'tasks'            => $tasks,
                'save_states'      => $saveStates,
                'memory'           => self::memory($i),
                'xp'               => $xp,
            ];
        }
        // respeita a cota de notas altas (1 nota 10 a cada 10 jogos, 1 nota 11 a cada 50): as que sobram descem um degrau
        $left = [11 => intdiv(count($out), 50), 10 => intdiv(count($out), 10)];
        foreach ([11, 10] as $n) {
            foreach ($out as &$g) {
                if ($g['rating'] === $n && $left[$n]-- <= 0) {
                    $g['rating'] = $n - 1;
                }
            }
            unset($g);
        }
        return $out;
    }

    /** Memórias fictícias, espalhadas de forma determinística (nem todo jogo tem). */
    private static function memory(int $i): array
    {
        $people = [['Irmão'], ['Ana'], ['Irmão', 'Primo Bruno'], ['Ana', 'Carol'], ['Pai'], ['Bruno'], [], []];
        $places = ['Sala de casa', 'Casa do primo', 'Quarto', 'Casa da vó', null, null, 'Ônibus', 'Lan house'];
        $by = ['Meu irmão', 'Uma revista', 'Um amigo da escola', 'Meu pai', null, null, 'Uma indicação online', null];
        $where = ['Locadora', 'Presente de Natal', 'Promoção', 'Emprestado', null, null, 'Feira de games', null];
        return [
            'intro_by' => $by[($i * 3) % 8], 'intro_where' => $where[($i * 5) % 8], 'intro_age' => $i % 3 === 0 ? 8 + ($i % 20) : null,
            'played_where' => $places[($i * 7) % 8], 'people' => $people[($i * 5) % 8],
            'music_title' => $i % 4 === 1 ? ['Se não fosse tão tarde, Lou Garcia', 'Trilha da novela das oito', 'Aquele CD do meu irmão'][$i % 3] : null,
            'music_url' => $i % 8 === 1 ? 'https://example.com/musica' : null,
        ];
    }

    /** Notas de diário fictícias para alguns jogos do exemplo (por id do jogo). */
    public static function notes(): array
    {
        $texts = [
            'Comecei hoje, ainda sem saber o que esperar. A música de abertura já valeu o preço.',
            'Travei no chefe do meio por duas noites. Desisti de reclamar e fui tentar de novo.',
            'Terminei. Fiquei um tempo olhando os créditos sem querer fechar.',
            'Joguei com o Irmão até de madrugada, cada um com um controle.',
        ];
        $out = [];
        foreach (self::games('') as $g) {
            if ($g['id'] % 5 === 0) {
                $out[$g['id']] = [
                    ['note_date' => date('Y-m-d', strtotime($g['finished_at'] . ' -20 days')), 'body' => $texts[$g['id'] % 2]],
                    ['note_date' => $g['finished_at'], 'body' => $texts[2 + $g['id'] % 2]],
                ];
            }
        }
        return $out;
    }

    /** Pessoas e lugares do catálogo de exemplo, no mesmo formato de NotesService::circles(). */
    public static function circles(string $basePath): array
    {
        $people = $places = [];
        foreach (self::games($basePath) as $g) {
            $row = ['id' => $g['id'], 'title' => $g['title'], 'platform' => $g['platform'], 'finished_at' => $g['finished_at'], 'cover_url' => $g['cover_url']];
            foreach ($g['memory']['people'] as $p) {
                $people[$p]['name'] = $p;
                $people[$p]['games'][] = $row;
            }
            if ($g['memory']['played_where']) {
                $places[$g['memory']['played_where']]['name'] = $g['memory']['played_where'];
                $places[$g['memory']['played_where']]['games'][] = $row;
            }
        }
        $sort = function (array $groups): array {
            $groups = array_values($groups);
            usort($groups, fn($a, $b) => count($b['games']) <=> count($a['games']) ?: strcasecmp($a['name'], $b['name']));
            return $groups;
        };
        return ['people' => $sort($people), 'places' => $sort($places)];
    }

    // ====================== Dashboard de exemplo ======================

    /** Estatísticas no formato de /api/me/stats, para cada ano de finalização dos jogos de exemplo. */
    public static function dashboard(): array
    {
        $games = self::games('');
        $years = array_values(array_unique(array_map(fn($g) => (int) substr($g['finished_at'], 0, 4), $games)));
        rsort($years);
        $xpTotal = array_sum(array_column($games, 'xp'));
        $progress = (new XpService())->progress($xpTotal);

        $top = function (array $list, callable $key): array {
            $count = [];
            foreach ($list as $g) {
                $k = $key($g);
                if ($k !== null && $k !== '') {
                    $count[$k] = ($count[$k] ?? 0) + 1;
                }
            }
            $rows = [];
            foreach ($count as $name => $n) {
                $rows[] = ['name' => (string) $name, 'n' => $n];
            }
            usort($rows, fn($a, $b) => $b['n'] <=> $a['n'] ?: strcasecmp($a['name'], $b['name']));
            return array_slice($rows, 0, 10);
        };
        $totals = fn(array $list) => [
            'games' => count($list),
            'hours' => round(array_sum(array_column($list, 'playtime_minutes')) / 60, 1),
            'avg_rating' => $list ? round(array_sum(array_column($list, 'rating')) / count($list), 1) : null,
        ];

        $byYear = [];
        foreach ($years as $year) {
            $inYear = array_values(array_filter($games, fn($g) => (int) substr($g['finished_at'], 0, 4) === $year));
            $months = [];
            for ($m = 1; $m <= 12; $m++) {
                $inMonth = array_filter($inYear, fn($g) => (int) substr($g['finished_at'], 5, 2) === $m);
                $months[] = [
                    'month' => $m, 'games' => count($inMonth),
                    'hours' => round(array_sum(array_column($inMonth, 'playtime_minutes')) / 60, 1),
                    'xp' => (int) array_sum(array_column($inMonth, 'xp')),
                ];
            }
            $byYear[$year] = [
                'xp' => $progress, 'year' => $year, 'years' => $years,
                'totals' => ['all' => $totals($games), 'year' => $totals($inYear)],
                'months' => $months,
                'genres' => ['all' => $top($games, fn($g) => $g['genre']), 'year' => $top($inYear, fn($g) => $g['genre'])],
                'platforms' => ['all' => $top($games, fn($g) => $g['platform']), 'year' => $top($inYear, fn($g) => $g['platform'])],
            ];
        }
        // cartões extras do dashboard: meta do ano e jogos em andamento
        $goal = ['year' => 2026, 'games' => ['target' => 32, 'done' => $byYear[2026]['totals']['year']['games']],
                 'hours' => ['target' => 1800, 'done' => (int) round($byYear[2026]['totals']['year']['hours'])]];
        $playing = array_values(array_filter(self::backlog('')['data'], fn($b) => $b['status'] === 'playing'));
        foreach ($byYear as $y => $payload) {
            $byYear[$y]['goal'] = $goal;
            $byYear[$y]['playing'] = array_map(fn($b) => ['cover_url' => (string) \App\Core\Config::get('base_path', '') . '/assets/demo/covers/' . self::slug($b['title'], $b['platform']) . '.webp'] + $b, $playing);
        }
        return ['years' => $years, 'byYear' => $byYear, 'xp' => $progress];
    }

    // ====================== Páginas "navegar por" ======================

    /** Agrupamentos no formato de /api/me/groups/{dim}. */
    public static function groups(string $dim, string $basePath): array
    {
        $games = self::games($basePath);
        $groups = [];
        foreach ($games as $g) {
            $v = $g[$dim] ?? null;
            if ($v !== null && $v !== '') {
                $groups[(string) $v][] = $g;
            }
        }
        if ($dim === 'difficulty') {
            foreach (['C', 'B', 'A', 'AA', 'AAA'] as $d) {
                $groups[$d] ??= [];
            }
        }
        if ($dim === 'rating') {
            foreach (range(1, 11) as $n) {
                $groups[(string) $n] ??= [];
            }
        }
        $out = [];
        foreach ($groups as $value => $list) {
            usort($list, fn($a, $b) => $b['rating'] <=> $a['rating'] ?: $b['id'] <=> $a['id']);
            $out[] = [
                'value'      => (string) $value,
                'games'      => count($list),
                'hours'      => round(array_sum(array_column($list, 'playtime_minutes')) / 60, 1),
                'avg_rating' => $list ? round(array_sum(array_column($list, 'rating')) / count($list), 1) : 0,
                'covers'     => array_slice(array_column($list, 'cover_url'), 0, 4),
                'years'      => $list ? [min(array_map(fn($g) => (int) substr($g['finished_at'], 0, 4), $list)), max(array_map(fn($g) => (int) substr($g['finished_at'], 0, 4), $list))] : [0, 0],
                'partners'   => self::partners($dim, $list),
                // exemplo de best of: o jogo de maior nota (com 2+ jogos no grupo, como no site de verdade)
                'best'       => in_array($dim, ['platform', 'genre', 'developer', 'publisher'], true) && count($list) >= BestOfService::MIN_GAMES ? self::bestGame($list[0]) : null,
            ];
        }
        if ($dim === 'difficulty') {
            $order = array_flip(['C', 'B', 'A', 'AA', 'AAA']);
            usort($out, fn($a, $b) => $order[$a['value']] <=> $order[$b['value']]);
        }
        if ($dim === 'rating') {
            usort($out, fn($a, $b) => (int) $b['value'] <=> (int) $a['value']);
        }
        $quota = $dim === 'rating' ? ['games' => count($games), 'used' => [10 => count(array_filter($games, fn($g) => $g['rating'] === 10)), 11 => count(array_filter($games, fn($g) => $g['rating'] === 11))], 'per' => [10 => 10, 11 => 50]] : null;
        return ['data' => $out, 'total' => count($games), 'missing' => 0, 'quota' => $quota, 'best_min' => BestOfService::MIN_GAMES];
    }

    /** Parceiras de exemplo: desenvolvedora -> publishers (e vice-versa), as 3 mais frequentes. */
    private static function partners(string $dim, array $list): array
    {
        if ($dim !== 'developer' && $dim !== 'publisher') {
            return [];
        }
        $other = $dim === 'developer' ? 'publisher' : 'developer';
        $n = [];
        foreach ($list as $g) {
            if (!empty($g[$other]) && $g[$other] !== $g[$dim]) {
                $n[$g[$other]] = ($n[$g[$other]] ?? 0) + 1;
            }
        }
        arsort($n);
        return array_map(fn($k, $v) => ['name' => $k, 'games' => $v], array_slice(array_keys($n), 0, 3), array_slice(array_values($n), 0, 3));
    }

    /** Jogo no formato do best of. */
    private static function bestGame(array $g): array
    {
        return [
            'id' => $g['id'], 'title' => $g['title'], 'cover_url' => $g['cover_url'], 'rating' => $g['rating'],
            'finished_at' => $g['finished_at'], 'playtime_minutes' => (int) $g['playtime_minutes'], 'platform' => $g['platform'],
        ];
    }

    // ====================== Franquias de exemplo ======================

    /** [nome, descrição, [[seção, [[título, ano?], ...]], ...]] */
    private static function franchiseDefs(): array
    {
        return [
            ['Resident Evil', 'A série de survival horror da Capcom.', [
                ['Principal', [['Resident Evil', 1996], ['Resident Evil 2', 1998], ['Resident Evil 3: Nemesis', 1999], ['Resident Evil 4', 2005], ['Resident Evil 5', 2009], ['Resident Evil 6', 2012], ['Resident Evil 7', 2017], ['Resident Evil Village', 2021]]],
                ['Remakes', [['Resident Evil', 2002], ['Resident Evil 2 Remake', 2019], ['Resident Evil 3 Remake', 2020], ['Resident Evil 4 Remake', 2023]]],
                ['Spin-offs', [['Resident Evil: Revelations', 2012], ['Resident Evil: Operation Raccoon City', 2012]]],
            ]],
            ['God of War', 'Do Olimpo à mitologia nórdica.', [
                ['Série grega', [['God of War', 2005], ['God of War II', 2007], ['God of War III', 2010]]],
                ['Série nórdica', [['God of War', 2018], ['God of War Ragnarök', 2022]]],
                ['Spin-offs', [['God of War: Chains of Olympus', 2008], ['God of War: Ghost of Sparta', 2010], ['God of War: Ascension', 2013]]],
            ]],
            ['The Legend of Zelda', 'Aventuras em Hyrule.', [
                ['3D', [['The Legend of Zelda: Ocarina of Time', 1998], ['The Legend of Zelda: Majora\'s Mask', 2000], ['The Legend of Zelda: The Wind Waker', 2002], ['The Legend of Zelda: Twilight Princess', 2006]]],
                ['Mundo aberto', [['The Legend of Zelda: Breath of the Wild', 2017], ['The Legend of Zelda: Tears of the Kingdom', 2023]]],
            ]],
            ['Metroid', 'Samus e a exploração em 2D e 3D.', [
                ['Principal', [['Metroid Prime', 2002], ['Metroid Dread', 2021]]],
            ]],
            ['Soulslike da FromSoftware', 'A linhagem que começou em Demon\'s Souls.', [
                ['Principal', [['Demon\'s Souls', 2009], ['Dark Souls', 2011], ['Dark Souls II', 2014], ['Dark Souls III', 2016], ['Elden Ring', 2022]]],
                ['Outros', [['Bloodborne', 2015], ['Sekiro: Shadows Die Twice', 2019]]],
            ]],
            ['Halo', 'A saga do Master Chief.', [
                ['Principal', [['Halo: Combat Evolved', 2001], ['Halo 2', 2004], ['Halo 3', 2007], ['Halo 4', 2012]]],
            ]],
            ['Final Fantasy', null, [
                ['Clássicos', [['Final Fantasy VII', 1997], ['Final Fantasy VIII', 1999], ['Final Fantasy IX', 2000], ['Final Fantasy X', 2001]]],
                ['Remakes', [['Final Fantasy VII Remake', 2020], ['Final Fantasy VII Rebirth', 2024]]],
            ]],
        ];
    }

    /** Franquias completas (formato de FranchiseService::find) já casadas com os jogos de exemplo. */
    public static function franchises(string $basePath): array
    {
        $games = self::games($basePath);
        $key = fn(string $t) => mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $t)));
        $byTitle = [];
        foreach ($games as $g) {
            $byTitle[$key($g['title'])][] = $g;
        }

        $out = [];
        $eid = 1;
        $sid = 1;
        foreach (self::franchiseDefs() as $i => [$name, $desc, $sections]) {
            $f = ['id' => $i + 1, 'name' => $name, 'description' => $desc, 'total' => 0, 'done' => 0, 'minutes' => 0, 'games' => 0, 'sections' => []];
            $counted = [];
            foreach ($sections as [$sname, $entries]) {
                $s = ['id' => $sid++, 'name' => $sname, 'total' => 0, 'done' => 0, 'entries' => []];
                foreach ($entries as [$title, $year]) {
                    $hit = null;
                    foreach ($byTitle[$key($title)] ?? [] as $g) {
                        if ($year === null || $g['release_year'] === null || $g['release_year'] === $year) {
                            $hit = $g;
                            break;
                        }
                    }
                    $s['entries'][] = [
                        'id' => $eid++, 'title' => $title, 'release_year' => $year, 'game_id' => null, 'linked' => false,
                        'done' => $hit !== null,
                        'game' => $hit ? [
                            'id' => $hit['id'], 'title' => $hit['title'], 'platform' => $hit['platform'], 'rating' => $hit['rating'],
                            'finished_at' => $hit['finished_at'], 'cover_url' => $hit['cover_url'],
                            'playtime_minutes' => $hit['playtime_minutes'],
                        ] : null,
                    ];
                    $s['total']++;
                    $f['total']++;
                    if ($hit) {
                        $s['done']++;
                        $f['done']++;
                        if (!isset($counted[$hit['id']])) {
                            $counted[$hit['id']] = true;
                            $f['minutes'] += (int) $hit['playtime_minutes'];
                            $f['games']++;
                        }
                    }
                }
                $f['sections'][] = $s;
            }
            $done = BestOfService::franchiseGames($f);
            $f['best'] = count($done) >= BestOfService::MIN_GAMES ? $done[0] : null;
            $f['best_min'] = BestOfService::MIN_GAMES;
            $out[] = $f;
        }
        return $out;
    }

    /** Resumo das franquias (formato de FranchiseService::list). */
    public static function franchiseList(string $basePath): array
    {
        return array_map(function (array $f) {
            $covers = [];
            foreach ($f['sections'] as $s) {
                foreach ($s['entries'] as $e) {
                    if ($e['game']['cover_url'] ?? null) {
                        $covers[] = $e['game']['cover_url'];
                    }
                }
            }
            return [
                'id' => $f['id'], 'name' => $f['name'], 'description' => $f['description'], 'sections' => count($f['sections']),
                'total' => $f['total'], 'done' => $f['done'], 'covers' => array_slice($covers, 0, 4), 'best' => $f['best'],
            ] + FranchiseService::extras($f);
        }, self::franchises($basePath));
    }

    // ====================== Mapa de calor de exemplo ======================

    /**
     * Dias com jogos zerados em 2025 e 2026 (até "hoje" = 18/10/2026), com padrão realista:
     * mais jogos nos fins de semana e nas férias, semanas fortes e algumas pausas longas. Determinístico.
     */
    public static function heatmap(): array
    {
        $entries = self::entries();
        $n = count($entries);
        $seed = 20260101;
        $rand = function () use (&$seed): float {
            $seed = ($seed * 1103515245 + 12345) & 0x7fffffff;
            return $seed / 0x7fffffff;
        };
        $today = strtotime('2026-10-18');
        $years = [2026, 2025];
        $byYear = [];
        $id = 1;
        $k = 0;
        foreach ($years as $year) {
            $days = [];
            for ($d = strtotime("$year-01-01"); (int) date('Y', $d) === $year && $d <= $today; $d = strtotime('+1 day', $d)) {
                $dow = (int) date('w', $d);
                $month = (int) date('n', $d);
                $week = (int) date('W', $d);
                $p = 0.15 + (($dow === 0 || $dow === 6) ? 0.24 : 0) + (in_array($month, [1, 7, 12], true) ? 0.12 : 0);
                if ($week % 6 === 2) {
                    $p += 0.18;   // semana "fogo"
                }
                if ($week % 11 === 5) {
                    $p = 0.01;    // pausa
                }
                if ($rand() >= $p) {
                    continue;
                }
                $count = 1 + ($rand() < 0.3 ? 1 : 0) + ($rand() < 0.08 ? 1 : 0);
                for ($i = 0; $i < $count; $i++) {
                    [$title, $platform] = $entries[$k++ % $n];
                    $minutes = 300 + (int) ($rand() * 3600);
                    $days[date('Y-m-d', $d)][] = [
                        'id' => $id++, 'title' => $title, 'platform' => $platform, 'rating' => 6 + (int) ($rand() * 6),
                        'playtime_minutes' => $minutes, 'xp' => 100 + (int) ($rand() * 4) * 40,
                    ];
                }
            }
            $byYear[$year] = ['year' => $year, 'years' => $years, 'days' => (object) $days];
        }
        return ['years' => $years, 'byYear' => $byYear, 'today' => '2026-10-18'];
    }

    // ====================== Dropados de exemplo ======================

    /** [título, sistema, gênero, ano de lançamento, horas jogadas, chance, motivo, dropado em] */
    public static function droppedEntries(): array
    {
        return [
            ['Baldur\'s Gate 3', 'PC', 'RPG', 2023, 38, 'high', 'Parei no ato 2: preciso de uma semana livre para jogar com calma.', '2026-08-02'],
            ['Final Fantasy XVI', 'PS5', 'JRPG', 2023, 21, 'high', 'Adorei a história, só perdi o ritmo depois de uma viagem.', '2026-05-09'],
            ['Divinity: Original Sin 2', 'PC', 'RPG', 2017, 25, 'high', 'Mecânicas profundas. Quero voltar com um build novo.', '2026-01-12'],
            ['Octopath Traveler', 'Switch', 'JRPG', 2018, 14, 'high', 'Quero zerar os oito caminhos, só falta tempo.', '2025-12-20'],
            ['Hogwarts Legacy', 'PS5', 'RPG de Ação', 2023, 17, 'medium', 'Bonito, mas as missões começaram a se repetir.', '2026-03-21'],
            ['Monster Hunter World', 'PS4', 'RPG de Ação', 2018, 12, 'medium', 'Curva de aprendizado alta. Quero voltar jogando com amigos.', '2025-02-17'],
            ['Armored Core VI', 'PS5', 'Ação', 2023, 9, 'medium', 'Um chefe me travou e faltou paciência no dia.', '2026-07-04'],
            ['Xenoblade Chronicles 3', 'Switch', 'JRPG', 2022, 28, 'medium', 'História ótima, mas os menus pesaram.', '2026-04-15'],
            ['Starfield', 'PC', 'RPG', 2023, 22, 'low', 'Exploração repetitiva, não me prendeu.', '2025-11-14'],
            ['Assassin\'s Creed Valhalla', 'PC', 'Ação', 2020, 31, 'low', 'Longo demais para o que oferece.', '2025-06-30'],
            ['Diablo IV', 'PC', 'RPG de Ação', 2023, 19, 'low', 'O fim de jogo ficou vazio.', '2025-09-05'],
            ['Fallout 4', 'PC', 'RPG', 2015, 44, 'low', 'Os mods quebraram o meu save.', '2024-10-03'],
            ['Pillars of Eternity', 'PC', 'RPG', 2015, 16, 'low', 'Muito texto e pouca ação para mim.', '2025-04-28'],
        ];
    }

    /** Dropados no formato da API (/api/dropped). */
    public static function dropped(string $basePath): array
    {
        $out = [];
        $counts = ['low' => 0, 'medium' => 0, 'high' => 0];
        foreach (self::droppedEntries() as $i => [$title, $platform, $genre, $year, $hours, $chance, $reason, $date]) {
            $counts[$chance]++;
            $out[] = [
                'id' => $i + 1, 'title' => $title, 'platform' => $platform, 'genre' => $genre, 'release_year' => $year,
                'playtime_minutes' => $hours * 60 + ($i * 17) % 50, 'dropped_at' => $date, 'return_chance' => $chance, 'reason' => $reason,
                'cover_url' => $basePath . '/assets/demo/covers/' . self::slug($title, $platform) . '.webp',
            ];
        }
        usort($out, fn($a, $b) => strcmp($b['dropped_at'], $a['dropped_at']));
        return ['data' => $out, 'counts' => $counts];
    }

    // ====================== Metas de exemplo ======================

    /** Metas e progresso de 2024 a 2026 (formato de /api/goals), calculados sobre os jogos de exemplo. */
    public static function goals(): array
    {
        $dash = self::dashboard();
        $targets = [
            2026 => ['games' => 32, 'hours' => 1800], // jogos: no ritmo; horas: atrasado
            2025 => ['games' => 30, 'hours' => 1500], // cumprida
            2024 => ['games' => 12, 'hours' => null], // não cumprida
        ];
        $base = [];
        foreach ($dash['years'] as $y) {
            $months = $dash['byYear'][$y]['months'];
            $base[$y] = [
                'goals'  => $targets[$y] ?? ['games' => null, 'hours' => null],
                'months' => ['games' => array_column($months, 'games'), 'hours' => array_column($months, 'hours')],
                'done'   => ['games' => array_sum(array_column($months, 'games')), 'hours' => round(array_sum(array_column($months, 'hours')), 1)],
            ];
        }
        $byYear = [];
        foreach ($dash['years'] as $y) {
            $history = [];
            foreach ($dash['years'] as $other) {
                if ($other !== $y && ($base[$other]['goals']['games'] || $base[$other]['goals']['hours'])) {
                    $history[] = ['year' => $other, 'goals' => $base[$other]['goals'], 'done' => $base[$other]['done']];
                }
            }
            $byYear[$y] = ['year' => $y, 'years' => $dash['years'], 'today' => '2026-10-18'] + $base[$y] + ['history' => $history];
        }
        return ['byYear' => $byYear, 'years' => $dash['years'], 'today' => '2026-10-18'];
    }

    // ====================== Backlog de exemplo ======================

    /** [título, sistema, gênero, ano, status, prioridade, anotação, desde] */
    public static function backlogEntries(): array
    {
        return [
            ['Metaphor: ReFantazio', 'PS5', 'JRPG', 2024, 'playing', 'high', 'Jogando uma hora por noite.', '2026-09-28'],
            ['Hollow Knight: Silksong', 'Switch', 'Metroidvania', 2025, 'playing', 'high', null, '2026-10-06'],
            ['Clair Obscur: Expedition 33', 'PC', 'JRPG', 2025, 'want', 'high', 'Todo mundo recomendou.', '2026-08-14'],
            ['Persona 3 Reload', 'PS5', 'JRPG', 2024, 'want', 'high', null, '2026-07-02'],
            ['Signalis', 'PC', 'Survival Horror', 2022, 'want', 'high', 'Para o fim de semana de terror.', '2026-05-20'],
            ['Lies of P', 'PS5', 'RPG de Ação', 2023, 'want', 'high', null, '2026-03-11'],
            ['Black Myth: Wukong', 'PC', 'RPG de Ação', 2024, 'want', 'medium', null, '2026-06-09'],
            ['Silent Hill f', 'PS5', 'Survival Horror', 2025, 'want', 'medium', 'Esperar uma promoção.', '2026-09-03'],
            ['Hades II', 'PC', 'Roguelike', 2025, 'want', 'medium', null, '2026-08-30'],
            ['Pentiment', 'PC', 'Aventura', 2022, 'want', 'medium', null, '2026-02-17'],
            ['Sifu', 'PC', 'Ação', 2022, 'want', 'low', null, '2025-11-25'],
            ['Tunic', 'PC', 'Aventura', 2022, 'want', 'low', null, '2025-10-08'],
            ['Inscryption', 'PC', 'Roguelike', 2021, 'want', 'low', 'Se sobrar tempo.', '2025-12-01'],
            ['Ghost of Tsushima', 'PS5', 'Ação', 2020, 'want', 'low', null, '2026-01-19'],
        ];
    }

    /** Backlog no formato da API (/api/backlog). */
    public static function backlog(string $basePath): array
    {
        $out = [];
        $counts = ['want' => 0, 'playing' => 0];
        $today = strtotime('2026-10-18');
        foreach (self::backlogEntries() as $i => [$title, $platform, $genre, $year, $status, $priority, $note, $since]) {
            $counts[$status]++;
            $out[] = [
                'id' => $i + 1, 'title' => $title, 'platform' => $platform, 'genre' => $genre, 'release_year' => $year,
                'status' => $status, 'priority' => $priority, 'started_at' => $status === 'playing' ? $since : null, 'note' => $note,
                'added_at' => $status === 'playing' ? date('Y-m-d', strtotime($since . ' -10 days')) : $since,
                'days_playing' => $status === 'playing' ? (int) floor(($today - strtotime($since)) / 86400) : null,
                'cover_url' => $basePath . '/assets/demo/covers/' . self::slug($title, $platform) . '.webp',
            ];
        }
        return ['data' => $out, 'counts' => $counts];
    }

    // ====================== Diário de exemplo ======================

    /** A linha do tempo completa do exemplo (formato de JournalService::build), sem links. */
    public static function journal(string $basePath): array
    {
        $games = self::games($basePath);
        foreach ($games as $i => &$g) {   // alguns jogos ganham uma data de início, para aparecer "começou" e "em N dias"
            $g['started_at'] = $i % 3 === 0 ? date('Y-m-d', strtotime($g['finished_at'] . ' -' . (6 + ($i * 7) % 40) . ' days')) : null;
        }
        unset($g);

        $notes = [];
        $id = 0;
        foreach (self::notes() as $gameId => $list) {
            foreach ($list as $n) {
                $notes[] = ['id' => ++$id, 'ref' => 'g' . $gameId, 'note_date' => $n['note_date'], 'body' => $n['body'], 'shots' => []];
            }
        }
        $backlog = self::backlog($basePath)['data'];
        foreach ([[1, '2026-10-02', 'Primeira masmorra feita. O sistema de arquétipos é mais profundo do que parecia.'],
                  [1, '2026-10-11', 'Uma hora por noite está rendendo. Parei antes do segundo chefe grande.'],
                  [2, '2026-10-09', 'Morri umas vinte vezes na mesma sala e ainda assim não consegui largar.']] as [$bid, $date, $body]) {
            $notes[] = ['id' => ++$id, 'ref' => 'b' . $bid, 'note_date' => $date, 'body' => $body, 'shots' => []];
        }

        $entries = [
            ['id' => 1, 'entry_date' => '2026-10-13', 'body' => 'Não joguei nada hoje. Passei a noite arrumando a estante e achei o cartão de memória antigo.'],
            ['id' => 2, 'entry_date' => '2026-09-20', 'body' => 'Feira de games com a Ana. Voltei com dois jogos usados e nenhuma culpa.'],
            ['id' => 3, 'entry_date' => '2026-06-14', 'body' => 'Controle novo chegou. O antigo aposentou depois de seis anos.'],
            ['id' => 4, 'entry_date' => '2025-12-25', 'body' => 'Natal na casa da vó: o videogame foi junto e o primo Bruno perdeu todas.'],
        ];

        $out = (new JournalService())->build($games, $backlog, self::dropped($basePath)['data'], $notes, [], $entries, null);
        $out['today'] = '2026-10-18';
        return $out;
    }
}
