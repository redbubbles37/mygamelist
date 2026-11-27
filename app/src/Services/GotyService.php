<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\{Database, HttpException, Str};

/**
 * GOTY (Game of the Year) pessoal. "Ano" = ano de LANÇAMENTO do jogo (o RE9, lançado em 2026, concorre ao GOTY 2026).
 * Um ano só é liberado quando o usuário zerou pelo menos MIN_GAMES jogos lançados nele.
 */
final class GotyService
{
    public const MIN_GAMES = 5;

    public function __construct(private UploadService $uploads = new UploadService()) {}

    /** Anos liberados (mais recente primeiro), com candidatos e a escolha atual de cada um. */
    public function years(int $userId): array
    {
        $stats = Database::all(
            'SELECT release_year AS y, COUNT(*) AS n, COALESCE(SUM(playtime_minutes), 0) AS minutes
             FROM games WHERE user_id = ? AND release_year IS NOT NULL GROUP BY release_year HAVING n >= ? ORDER BY y DESC',
            [$userId, self::MIN_GAMES]
        );
        if (!$stats) {
            return [];
        }
        $years = array_map('intval', array_column($stats, 'y'));
        $in = implode(',', array_fill(0, count($years), '?'));

        $byYear = [];
        foreach (Database::all(
            "SELECT g.id, g.title, g.finished_at, g.playtime_minutes, g.rating, g.difficulty, g.cover_path,
                    p.name AS platform, ge.name AS genre, g.release_year AS y
             FROM games g JOIN platforms p ON p.id = g.platform_id LEFT JOIN genres ge ON ge.id = g.genre_id
             WHERE g.user_id = ? AND g.release_year IN ($in)
             ORDER BY g.rating DESC, g.finished_at DESC, g.title",
            [$userId, ...$years]
        ) as $g) {
            $byYear[$g['y']][] = $this->card($g);
        }
        // só jogos com nota 9, 10 ou 11 podem ser eleitos GOTY; "eligible" conta os que concorrem
        foreach ($byYear as $y => $list) {
            $byYear[$y] = array_values(array_filter($list, fn($c) => $c['rating'] >= RetrospectiveService::MIN_RATING_TOP));
        }

        $picks = [];
        foreach (Database::all('SELECT year, game_id, note FROM goty_picks WHERE user_id = ?', [$userId]) as $p) {
            $picks[$p['year']] = $p;
        }

        $out = [];
        foreach ($stats as $s) {
            $y = (int) $s['y'];
            $candidates = $byYear[$y] ?? [];
            $pick = null;
            // a escolha só vale se o jogo ainda existe e ainda é de um jogo lançado naquele ano
            if (isset($picks[$y])) {
                foreach ($candidates as $c) {
                    if ($c['id'] === $picks[$y]['game_id']) {
                        $pick = ['note' => $picks[$y]['note'], 'game' => $c];
                        break;
                    }
                }
            }
            $out[] = [
                'year'       => $y,
                'games'      => (int) $s['n'],
                'hours'      => round($s['minutes'] / 60, 1),
                'pick'       => $pick,
                'eligible'   => count($candidates),
                'min_rating' => RetrospectiveService::MIN_RATING_TOP,
                'candidates' => $candidates,
            ];
        }
        return $out;
    }

    /** Um ano específico (ou erro se ainda não estiver liberado). */
    public function year(int $userId, int $year): array
    {
        foreach ($this->years($userId) as $y) {
            if ($y['year'] === $year) {
                return $y;
            }
        }
        throw new HttpException(404, 'Este ano ainda não está liberado.');
    }

    public function set(int $userId, int $year, mixed $gameId, mixed $note): array
    {
        $count = (int) Database::value('SELECT COUNT(*) FROM games WHERE user_id = ? AND release_year = ?', [$userId, $year]);
        if ($count < self::MIN_GAMES) {
            throw new HttpException(422, 'Ano não liberado.', ['year' => 'Você precisa ter zerado pelo menos ' . self::MIN_GAMES . " jogos lançados em $year (tem $count)."]);
        }
        $gameId = filter_var($gameId, FILTER_VALIDATE_INT);
        $row = $gameId === false ? null : Database::one(
            'SELECT title, rating FROM games WHERE id = ? AND user_id = ? AND release_year = ?', [$gameId, $userId, $year]
        );
        if ($row === null) {
            throw new HttpException(422, 'Jogo inválido.', ['game_id' => "Escolha um jogo lançado em $year."]);
        }
        if ((int) $row['rating'] < RetrospectiveService::MIN_RATING_TOP) {
            throw new HttpException(422, 'Nota baixa demais para o GOTY.', ['game_id' => "{$row['title']} tem nota {$row['rating']}: só jogos com nota 9, 10 ou 11 podem ser eleitos GOTY."]);
        }
        $note = Str::clean($note ?? '');
        if (mb_strlen($note) > 300) {
            throw new HttpException(422, 'Texto muito longo.', ['note' => 'O comentário pode ter até 300 caracteres.']);
        }

        Database::query(
            'INSERT INTO goty_picks (user_id, year, game_id, note) VALUES (?,?,?,?)
             ON CONFLICT(user_id, year) DO UPDATE SET game_id = excluded.game_id, note = excluded.note',
            [$userId, $year, $gameId, $note === '' ? null : $note]
        );
        return $this->year($userId, $year);
    }

    public function clear(int $userId, int $year): void
    {
        Database::query('DELETE FROM goty_picks WHERE user_id = ? AND year = ?', [$userId, $year]);
    }

    /** Escolhas válidas de um usuário (para o perfil público). */
    public function picks(int $userId): array
    {
        $rows = Database::all(
            'SELECT p.year, p.note, g.id, g.title, g.finished_at, g.playtime_minutes, g.rating, g.difficulty, g.cover_path,
                    pl.name AS platform, ge.name AS genre
             FROM goty_picks p
             JOIN games g ON g.id = p.game_id AND g.user_id = p.user_id AND g.release_year = p.year
             JOIN platforms pl ON pl.id = g.platform_id
             LEFT JOIN genres ge ON ge.id = g.genre_id
             WHERE p.user_id = ? AND g.rating >= ' . RetrospectiveService::MIN_RATING_TOP . '
               AND (SELECT COUNT(*) FROM games x WHERE x.user_id = p.user_id AND x.release_year = p.year) >= ?
             ORDER BY p.year DESC',
            [$userId, self::MIN_GAMES]
        );
        return array_map(fn(array $r) => ['year' => (int) $r['year'], 'note' => $r['note'], 'game' => $this->card($r)], $rows);
    }

    private function card(array $g): array
    {
        return [
            'id'               => (int) $g['id'],
            'title'            => $g['title'],
            'platform'         => $g['platform'],
            'genre'            => $g['genre'],
            'finished_at'      => $g['finished_at'],
            'playtime_minutes' => (int) $g['playtime_minutes'],
            'rating'           => (int) $g['rating'],
            'difficulty'       => $g['difficulty'],
            'cover_url'        => $this->uploads->url($g['cover_path']),
        ];
    }

    /** Jogos de exemplo por ano de lançamento (usado pelas páginas de exemplo de GOTY e Retrospectiva). */
    public static function demoCatalog(): array
    {
        // ano de LANÇAMENTO => [título, sistema, gênero, nota]. A ordem do 1º item é o vencedor, quando o ano tem escolha.
        return [
            2026 => [['Resident Evil 9', 'PS5', 'Survival Horror', 11], ['Último Farol', 'PC', 'Aventura', 9], ['Forja Estelar', 'PC', 'RPG', 9], ['Neon Drifter', 'Switch', 'Corrida', 8], ['Vale das Sombras', 'PS5', 'RPG de Ação', 10], ['Crônicas de Aldeia', 'PC', 'Simulação', 7]],
            2025 => [['Clair Obscur: Expedition 33', 'PC', 'JRPG', 11], ['Hollow Knight: Silksong', 'Switch', 'Metroidvania', 10], ['Split Fiction', 'PS5', 'Aventura', 9], ['Doom: The Dark Ages', 'PC', 'Tiro', 9], ['Death Stranding 2', 'PS5', 'Ação', 8], ['Monster Hunter Wilds', 'PC', 'RPG de Ação', 7]],
            2024 => [['Astro Bot', 'PS5', 'Plataforma', 10], ['Black Myth: Wukong', 'PC', 'RPG de Ação', 9], ['Metaphor: ReFantazio', 'PS5', 'JRPG', 10], ['Balatro', 'PC', 'Roguelike', 9], ['Silent Hill 2', 'PS5', 'Survival Horror', 9]],
            2023 => [['Baldur\'s Gate 3', 'PC', 'RPG', 11], ['The Legend of Zelda: Tears of the Kingdom', 'Switch', 'Aventura', 10], ['Resident Evil 4', 'PS5', 'Survival Horror', 10], ['Alan Wake 2', 'PS5', 'Survival Horror', 9], ['Dave the Diver', 'Switch', 'Aventura', 8]],
            2022 => [['Elden Ring', 'PC', 'RPG de Ação', 11], ['God of War Ragnarök', 'PS5', 'Ação', 10], ['Stray', 'PS5', 'Aventura', 8], ['Horizon Forbidden West', 'PS5', 'Ação', 9], ['Kirby and the Forgotten Land', 'Switch', 'Plataforma', 8]],
            2021 => [['Metroid Dread', 'Switch', 'Plataforma', 9], ['Forza Horizon 5', 'Xbox Series', 'Corrida', 9], ['Resident Evil Village', 'PS5', 'Survival Horror', 8], ['Returnal', 'PS5', 'Roguelike', 9], ['It Takes Two', 'PC', 'Aventura', 10]],
            2020 => [['Hades', 'PC', 'Roguelike', 11], ['The Last of Us Part II', 'PS4', 'Ação', 10], ['Doom Eternal', 'PC', 'Tiro', 9], ['Ghost of Tsushima', 'PS4', 'Ação', 9], ['Half-Life: Alyx', 'PC', 'Tiro', 9]],
            2019 => [['Sekiro: Shadows Die Twice', 'PC', 'Ação', 10], ['Resident Evil 2', 'PS4', 'Survival Horror', 10], ['Disco Elysium', 'PC', 'RPG', 10], ['Outer Wilds', 'PC', 'Aventura', 10], ['Death Stranding', 'PS4', 'Ação', 8]],
            2018 => [['God of War', 'PS4', 'Ação', 11], ['Red Dead Redemption 2', 'PS4', 'Ação', 10], ['Celeste', 'Switch', 'Plataforma', 9], ['Marvel\'s Spider-Man', 'PS4', 'Ação', 9], ['Return of the Obra Dinn', 'PC', 'Puzzle', 9]],
            2017 => [['The Legend of Zelda: Breath of the Wild', 'Switch', 'Aventura', 11], ['Super Mario Odyssey', 'Switch', 'Plataforma', 10], ['Hollow Knight', 'PC', 'Metroidvania', 10], ['Cuphead', 'PC', 'Plataforma', 8], ['Persona 5', 'PS4', 'JRPG', 10]],
            2015 => [['The Witcher 3: Wild Hunt', 'PC', 'RPG', 11], ['Bloodborne', 'PS4', 'RPG de Ação', 10], ['Metal Gear Solid V: The Phantom Pain', 'PC', 'Stealth', 9], ['Undertale', 'PC', 'RPG', 9], ['Fallout 4', 'PC', 'RPG', 8]],
            2011 => [['Dark Souls', 'PS3', 'RPG de Ação', 10], ['The Elder Scrolls V: Skyrim', 'PC', 'RPG', 10], ['Portal 2', 'PC', 'Puzzle', 10], ['Batman: Arkham City', 'PS3', 'Ação', 9], ['Uncharted 3', 'PS3', 'Ação', 8]],
            2007 => [['BioShock', 'Xbox 360', 'Tiro', 10], ['Halo 3', 'Xbox 360', 'Tiro', 9], ['Portal', 'PC', 'Puzzle', 10], ['Mass Effect', 'Xbox 360', 'RPG', 9], ['Super Mario Galaxy', 'Wii', 'Plataforma', 10]],
            2005 => [['Resident Evil 4', 'GameCube', 'Survival Horror', 11], ['Shadow of the Colossus', 'PS2', 'Aventura', 10], ['God of War', 'PS2', 'Ação', 10], ['Psychonauts', 'PC', 'Plataforma', 8], ['Guitar Hero', 'PS2', 'Ritmo', 8]],
            2004 => [['Half-Life 2', 'PC', 'Tiro', 10], ['Grand Theft Auto: San Andreas', 'PS2', 'Ação', 10], ['Metal Gear Solid 3', 'PS2', 'Stealth', 10], ['Halo 2', 'Xbox', 'Tiro', 9], ['World of Warcraft', 'PC', 'RPG', 8]],
            1998 => [['The Legend of Zelda: Ocarina of Time', 'Nintendo 64', 'Aventura', 11], ['Metal Gear Solid', 'PS1', 'Stealth', 10], ['Half-Life', 'PC', 'Tiro', 10], ['Grim Fandango', 'PC', 'Aventura', 9], ['Resident Evil 2', 'PS1', 'Survival Horror', 9]],
        ];
    }

    /** Dados fictícios para a página de exemplo (/goty/exemplo): mesma estrutura da API, vários anos para testar a navegação. */
    public function demo(): array
    {
        $catalog = self::demoCatalog();
        // anos com escolha (índice do vencedor) e comentário; os demais ficam pendentes para mostrar os dois estados
        $picks = [2026 => 0, 2025 => 0, 2023 => 0, 2022 => 0, 2020 => 0, 2019 => 2, 2018 => 0, 2017 => 0, 2015 => 0, 2011 => 1, 2007 => 2, 2005 => 0];
        $notes = [
            2026 => 'Tensão do começo ao fim. O melhor lançamento do ano, sem discussão.',
            2025 => 'Uma obra-prima: história, trilha e combate no mesmo nível.',
            2022 => 'Mil horas depois, ainda descobrindo coisa nova.',
            2017 => 'Mudou o que eu esperava de um mundo aberto.',
        ];
        $diffs = ['B', 'A', 'C', 'AA', 'B', 'A', 'AAA'];

        $id = 1;
        $out = [];
        foreach ($catalog as $year => $list) {
            $cands = [];
            foreach ($list as $i => [$title, $platform, $genre, $rating]) {
                $month = min(1 + ($i * 2) % 11, $year === 2026 ? 9 : 12);
                $cands[] = [
                    'id' => $id++, 'title' => $title, 'platform' => $platform, 'genre' => $genre,
                    'finished_at' => sprintf('%d-%02d-%02d', $year, $month, 10 + $i),
                    'playtime_minutes' => 600 + (($i * 977 + $year) % 5400), 'rating' => $rating,
                    'difficulty' => $diffs[($i + $year) % count($diffs)], 'cover_url' => null,
                ];
            }
            $winner = isset($picks[$year]) ? $list[$picks[$year]][0] : null;
            usort($cands, fn($a, $b) => $b['rating'] <=> $a['rating'] ?: strcmp($b['finished_at'], $a['finished_at']));
            $total = count($cands);
            $cands = array_values(array_filter($cands, fn($c) => $c['rating'] >= RetrospectiveService::MIN_RATING_TOP));
            $pick = null;
            foreach ($cands as $c) {
                if ($winner !== null && $c['title'] === $winner) {
                    $pick = ['note' => $notes[$year] ?? null, 'game' => $c];
                }
            }
            $out[] = [
                'year' => $year, 'games' => $total,
                'hours' => round(array_sum(array_column($cands, 'playtime_minutes')) / 60, 1),
                'pick' => $pick, 'eligible' => count($cands), 'min_rating' => RetrospectiveService::MIN_RATING_TOP, 'candidates' => $cands,
            ];
        }
        return $out;
    }
}
