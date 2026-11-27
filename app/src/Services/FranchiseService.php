<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\{Config, Database, HttpException, Str};

/**
 * Franquias: nome + seções ordenadas + itens (jogos). O progresso é calculado contra o catálogo do usuário.
 */
final class FranchiseService
{
    private const MAX_SECTIONS = 20;
    private const MAX_ENTRIES_PER_SECTION = 100;
    private const MAX_ENTRIES_TOTAL = 300;

    public function __construct(
        private UploadService $uploads = new UploadService(),
    ) {}

    // ---------- Leitura ----------

    /** Resumo de todas as franquias do usuário (cartões da listagem). */
    public function list(int $userId): array
    {
        $out = [];
        $bestOf = new BestOfService($this->uploads);
        $picks = $bestOf->franchisePicks($userId);
        foreach ($this->load($userId, null) as $f) {
            $covers = [];
            foreach ($f['sections'] as $s) {
                foreach ($s['entries'] as $e) {
                    if ($e['game']['cover_url'] ?? null) {
                        $covers[] = $e['game']['cover_url'];
                    }
                }
            }
            $out[] = [
                'id'       => $f['id'],
                'name'     => $f['name'],
                'description' => $f['description'],
                'sections' => count($f['sections']),
                'total'    => $f['total'],
                'done'     => $f['done'],
                'cover_url' => $f['cover_url'] ?? $f['hero_url'], // sem imagem própria, o card usa a do banner
                'cover_focus' => $f['cover_focus'],
                'covers'   => array_slice($covers, 0, 4),
                'games'    => $f['games'],
                'best'     => $bestOf->forFranchise($userId, $f, $picks),
            ] + self::extras($f);
        }
        return $out;
    }

    /**
     * Dados extras do resumo de uma franquia (lista e exemplo): tempo gasto, o próximo item que falta,
     * o último zerado e a "trilha" de feitos/faltando na ordem das seções.
     */
    public static function extras(array $f): array
    {
        $next = null;
        $last = null;
        $track = [];
        foreach ($f['sections'] as $s) {
            foreach ($s['entries'] as $e) {
                $track[] = ['d' => (bool) $e['done'], 't' => $e['title'] . ($e['release_year'] ? ' (' . $e['release_year'] . ')' : '')];
                if (!$e['done'] && $next === null) {
                    $next = ['title' => $e['title'], 'release_year' => $e['release_year'], 'section' => $s['name']];
                }
                $at = $e['game']['finished_at'] ?? null;
                if ($e['done'] && $at && (!$last || $at > $last['finished_at'])) {
                    $last = ['title' => $e['game']['title'], 'finished_at' => $at];
                }
            }
        }
        return ['minutes' => (int) $f['minutes'], 'next' => $next, 'last_done' => $last, 'track' => $track];
    }

    /**
     * Franquias do usuário em que um jogo aparece (por vínculo manual ou pelo mesmo título/ano de algum item),
     * com as seções e o progresso de cada franquia.
     */
    public function forGame(int $userId, int $gameId): array
    {
        $rows = Database::all(
            'SELECT f.id AS fid, s.name AS section
             FROM franchise_entries e
             JOIN franchise_sections s ON s.id = e.section_id
             JOIN franchises f ON f.id = s.franchise_id AND f.user_id = ?
             JOIN games g ON g.id = ? AND g.user_id = f.user_id
             WHERE e.game_id = g.id
                OR (e.game_id IS NULL AND e.title_key = g.title_key
                    AND (e.release_year IS NULL OR g.release_year IS NULL OR e.release_year = g.release_year))
             ORDER BY f.name, s.position, e.position',
            [$userId, $gameId]
        );
        if (!$rows) {
            return [];
        }
        $sections = [];
        foreach ($rows as $r) {
            $sections[$r['fid']][$r['section']] = true;
        }
        $out = [];
        foreach ($this->list($userId) as $f) {
            if (isset($sections[$f['id']])) {
                $out[] = [
                    'id' => $f['id'], 'name' => $f['name'], 'done' => $f['done'], 'total' => $f['total'],
                    'sections' => array_keys($sections[$f['id']]),
                ];
            }
        }
        return $out;
    }

    /** Franquia completa, com o status de cada item. */
    public function find(int $userId, int $id): array
    {
        $list = $this->load($userId, $id);
        if (!$list) {
            throw new HttpException(404, 'Franquia não encontrada.');
        }
        $f = $list[0];
        $f['best'] = (new BestOfService($this->uploads))->forFranchise($userId, $f);
        $f['best_min'] = BestOfService::MIN_GAMES;
        return $f;
    }

    /**
     * Situação dos itens que faltam fora do catálogo: na fila do backlog, jogando ou dropado (pelo título).
     * @return array<int, array{state: string, href: string}> por id do item
     */
    public function queueStatus(int $userId, array $f): array
    {
        $want = [];
        foreach ($f['sections'] as $s) {
            foreach ($s['entries'] as $e) {
                if (!$e['done']) {
                    $want[Str::key($e['title'])][] = (int) $e['id'];
                }
            }
        }
        if (!$want) {
            return [];
        }
        $base = (string) Config::get('base_path', '');
        $found = [];
        $in = implode(',', array_fill(0, count($want), '?'));
        foreach (Database::all("SELECT id, title_key, status FROM backlog_games WHERE user_id = ? AND title_key IN ($in)", [$userId, ...array_keys($want)]) as $r) {
            $found[$r['title_key']] = ['state' => $r['status'] === 'playing' ? 'playing' : 'want', 'href' => $base . '/backlog/' . $r['id'] . '/editar'];
        }
        foreach (Database::all('SELECT id, title FROM dropped_games WHERE user_id = ?', [$userId]) as $r) {
            $k = Str::key($r['title']);
            if (isset($want[$k]) && !isset($found[$k])) {
                $found[$k] = ['state' => 'dropped', 'href' => $base . '/dropados/' . $r['id'] . '/editar'];
            }
        }
        $out = [];
        foreach ($found as $k => $v) {
            foreach ($want[$k] as $id) {
                $out[$id] = $v;
            }
        }
        return $out;
    }

    /** Franquia anterior e seguinte em ordem alfabética (para navegar entre elas). */
    public function neighbors(int $userId, int $id): array
    {
        $rows = Database::all('SELECT id, name FROM franchises WHERE user_id = ? ORDER BY name, id', [$userId]);
        $i = array_search($id, array_map('intval', array_column($rows, 'id')), true);
        $pick = fn($r) => $r ? ['id' => (int) $r['id'], 'name' => $r['name']] : null;
        return $i === false ? ['prev' => null, 'next' => null]
            : ['prev' => $pick($rows[$i - 1] ?? null), 'next' => $pick($rows[$i + 1] ?? null)];
    }

    /** @return list<array> franquias com seções, itens, contagem total/feitos. */
    private function load(int $userId, ?int $franchiseId): array
    {
        $params = [$userId];
        $filter = '';
        if ($franchiseId !== null) {
            $filter = ' AND f.id = ?';
            $params[] = $franchiseId;
        }
        // match_id: vínculo manual (game_id) ou o jogo do catálogo com mesmo título
        // (+ ano de lançamento quando o item o informa; ano ausente no jogo vale como curinga). O sistema não importa.
        $rows = Database::all(
            "SELECT f.id AS fid, f.name AS fname, f.description AS fdesc, f.cover_path AS fcover, f.hero_path AS fhero, f.cover_focus AS ffocus,
                    s.id AS sid, s.name AS sname,
                    e.id AS eid, e.title, e.release_year, e.game_id AS linked_game_id,
                    COALESCE(lg.id, (
                        SELECT g2.id FROM games g2
                        WHERE g2.user_id = f.user_id AND g2.title_key = e.title_key
                          AND (e.release_year IS NULL OR g2.release_year IS NULL OR g2.release_year = e.release_year)
                        ORDER BY g2.finished_at DESC, g2.id DESC LIMIT 1
                    )) AS match_id
             FROM franchises f
             LEFT JOIN franchise_sections s ON s.franchise_id = f.id
             LEFT JOIN franchise_entries e ON e.section_id = s.id
             LEFT JOIN games lg ON lg.id = e.game_id AND lg.user_id = f.user_id
             WHERE f.user_id = ?$filter
             ORDER BY f.name, f.id, s.position, s.id, e.position, e.id",
            $params
        );

        // detalhes dos jogos que cumpriram algum item
        $ids = array_values(array_unique(array_filter(array_column($rows, 'match_id'))));
        $games = [];
        if ($ids) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            foreach (Database::all(
                "SELECT g.id, g.title, g.cover_path, g.rating, g.finished_at, g.playtime_minutes, p.name AS platform
                 FROM games g JOIN platforms p ON p.id = g.platform_id WHERE g.id IN ($in)",
                $ids
            ) as $g) {
                $games[$g['id']] = [
                    'id' => $g['id'], 'title' => $g['title'], 'platform' => $g['platform'],
                    'rating' => $g['rating'], 'finished_at' => $g['finished_at'],
                    'playtime_minutes' => (int) $g['playtime_minutes'],
                    'cover_url' => $this->uploads->url($g['cover_path']),
                ];
            }
        }

        $result = [];
        $counted = [];
        foreach ($rows as $r) {
            $fid = $r['fid'];
            $result[$fid] ??= [
                'id' => $fid, 'name' => $r['fname'], 'description' => $r['fdesc'],
                'cover_url' => $this->uploads->url($r['fcover']),
                'hero_url'  => $this->uploads->url($r['fhero']),
                'cover_focus' => self::focus(json_decode((string) $r['ffocus'], true)),
                'total' => 0, 'done' => 0, 'minutes' => 0, 'games' => 0, 'sections' => [],
            ];
            if ($r['sid'] === null) {
                continue;
            }
            $sid = $r['sid'];
            $result[$fid]['sections'][$sid] ??= ['id' => $sid, 'name' => $r['sname'], 'total' => 0, 'done' => 0, 'entries' => []];
            if ($r['eid'] === null) {
                continue;
            }
            $game = $r['match_id'] ? ($games[$r['match_id']] ?? null) : null;
            $done = $game !== null;
            $result[$fid]['sections'][$sid]['entries'][] = [
                'id'             => $r['eid'],
                'title'          => $r['title'],
                'release_year'   => $r['release_year'],
                'game_id'        => $r['linked_game_id'], // vínculo manual (se houver)
                'linked'         => $r['linked_game_id'] !== null && $done,
                'done'           => $done,
                'game'           => $game,
            ];
            $result[$fid]['sections'][$sid]['total']++;
            $result[$fid]['total']++;
            if ($done) {
                $result[$fid]['sections'][$sid]['done']++;
                $result[$fid]['done']++;
                // tempo gasto na franquia: cada jogo do catálogo conta uma vez, mesmo se cumprir itens de seções diferentes
                if (!isset($counted[$fid][$game['id']])) {
                    $counted[$fid][$game['id']] = true;
                    $result[$fid]['minutes'] += $game['playtime_minutes'];
                    $result[$fid]['games']++;
                }
            }
        }
        foreach ($result as &$f) {
            $f['sections'] = array_values($f['sections']);
        }
        return array_values($result);
    }

    // ---------- Escrita ----------

    /** Cria (id nulo) ou substitui por completo a estrutura de uma franquia. */
    public function save(int $userId, ?int $id, array $input): array
    {
        $d = $this->validate($input);
        if ($id !== null) {
            $this->owned($userId, $id);
        }

        $newId = Database::transaction(function () use ($userId, $id, $d) {
            try {
                if ($id === null) {
                    Database::query(
                        'INSERT INTO franchises (user_id, name, name_key, description, cover_focus) VALUES (?,?,?,?,?)',
                        [$userId, $d['name'], Str::key($d['name']), $d['description'], $d['cover_focus']]
                    );
                    $fid = Database::insertId();
                } else {
                    Database::query(
                        'UPDATE franchises SET name = ?, name_key = ?, description = ?, cover_focus = ? WHERE id = ? AND user_id = ?',
                        [$d['name'], Str::key($d['name']), $d['description'], $d['cover_focus'], $id, $userId]
                    );
                    Database::query('DELETE FROM franchise_sections WHERE franchise_id = ?', [$id]); // cascata: itens
                    $fid = $id;
                }
            } catch (\PDOException $e) {
                if (Database::isDuplicate($e)) {
                    throw new HttpException(409, 'Você já tem uma franquia com esse nome.', ['name' => 'Já existe uma franquia com esse nome.']);
                }
                throw $e;
            }

            // vínculos manuais só valem para jogos do próprio usuário
            $wanted = [];
            foreach ($d['sections'] as $s) {
                foreach ($s['entries'] as $e) {
                    if ($e['game_id']) {
                        $wanted[] = $e['game_id'];
                    }
                }
            }
            $mine = [];
            if ($wanted) {
                $in = implode(',', array_fill(0, count($wanted), '?'));
                $mine = array_flip(array_column(Database::all("SELECT id FROM games WHERE user_id = ? AND id IN ($in)", [$userId, ...$wanted]), 'id'));
            }

            foreach ($d['sections'] as $si => $s) {
                Database::query('INSERT INTO franchise_sections (franchise_id, name, position) VALUES (?,?,?)', [$fid, $s['name'], $si]);
                $sid = Database::insertId();
                foreach ($s['entries'] as $ei => $e) {
                    Database::query(
                        'INSERT INTO franchise_entries (section_id, title, title_key, release_year, game_id, position) VALUES (?,?,?,?,?,?)',
                        [
                            $sid, $e['title'], Str::key($e['title']),
                            $e['release_year'],
                            ($e['game_id'] && isset($mine[$e['game_id']])) ? $e['game_id'] : null,
                            $ei,
                        ]
                    );
                }
            }
            return $fid;
        });

        return $this->find($userId, $newId);
    }

    public function delete(int $userId, int $id): void
    {
        $row = $this->owned($userId, $id);
        Database::query('DELETE FROM franchises WHERE id = ? AND user_id = ?', [$id, $userId]);
        $this->uploads->delete($row['cover_path']);
        $this->uploads->delete($row['hero_path']);
    }

    /** Coluna de cada imagem: "card" (cartão da lista) ou "hero" (banner da página). */
    private const SLOTS = ['card' => 'cover_path', 'hero' => 'hero_path'];

    /** Imagem enviada pelo usuário para o card ou para o banner. */
    public function setCover(int $userId, int $id, array $file, string $slot = 'card'): array
    {
        return $this->replace($userId, $id, $slot, $this->uploads->store($this->uploads->process($file, 'upload.banner')));
    }

    /** Usa a capa de um jogo do próprio catálogo (copiada: apagar um não apaga o outro). */
    public function coverFromGame(int $userId, int $id, int $gameId, string $slot = 'card'): array
    {
        $this->owned($userId, $id);
        $src = Database::value('SELECT cover_path FROM games WHERE id = ? AND user_id = ?', [$gameId, $userId]);
        $path = $this->uploads->copy($src);
        if ($path === null) {
            throw new HttpException(422, 'Esse jogo não tem capa.', ['cover' => 'Esse jogo não tem capa.']);
        }
        return $this->replace($userId, $id, $slot, $path);
    }

    public function removeCover(int $userId, int $id, string $slot = 'card'): array
    {
        return $this->replace($userId, $id, $slot, null);
    }

    /** Troca a imagem de um slot e apaga o arquivo anterior. */
    private function replace(int $userId, int $id, string $slot, ?string $path): array
    {
        $col = self::SLOTS[$slot] ?? throw new HttpException(404, 'Imagem não encontrada.');
        $row = $this->owned($userId, $id);
        Database::query("UPDATE franchises SET $col = ? WHERE id = ?", [$path, $id]);
        $this->uploads->delete($row[$col]);
        return $this->find($userId, $id);
    }

    private function owned(int $userId, int $id): array
    {
        $row = Database::one('SELECT id, cover_path, hero_path FROM franchises WHERE id = ? AND user_id = ?', [$id, $userId]);
        if ($row === null) {
            throw new HttpException(404, 'Franquia não encontrada.');
        }
        return $row;
    }

    // ---------- Validação ----------

    /** Normaliza o payload; os erros usam caminhos ("sections.0.entries.1.title") para o front marcar o campo. */
    private function validate(array $in): array
    {
        $e = [];

        $name = Str::clean($in['name'] ?? '');
        if ($name === '' || mb_strlen($name) > 80) {
            $e['name'] = 'Informe o nome da franquia (até 80 caracteres).';
        }
        $desc = Str::clean($in['description'] ?? '');
        if (mb_strlen($desc) > 300) {
            $e['description'] = 'A descrição pode ter até 300 caracteres.';
        }

        $sectionsIn = is_array($in['sections'] ?? null) ? array_values($in['sections']) : [];
        if (!$sectionsIn) {
            $e['sections'] = 'Adicione pelo menos uma seção.';
        }
        if (count($sectionsIn) > self::MAX_SECTIONS) {
            $e['sections'] = 'No máximo ' . self::MAX_SECTIONS . ' seções.';
        }

        $maxYear = (int) date('Y') + 1; // permite jogos anunciados para o próximo ano
        $sections = [];
        $seenSections = [];
        $total = 0;

        foreach ($sectionsIn as $si => $s) {
            $s = is_array($s) ? $s : [];
            $sname = Str::clean($s['name'] ?? '');
            if ($sname === '' || mb_strlen($sname) > 80) {
                $e["sections.$si.name"] = 'Dê um nome à seção (até 80 caracteres).';
            } elseif (isset($seenSections[Str::key($sname)])) {
                $e["sections.$si.name"] = 'Já existe uma seção com esse nome.';
            }
            $seenSections[Str::key($sname)] = true;

            $entriesIn = is_array($s['entries'] ?? null) ? array_values($s['entries']) : [];
            if (count($entriesIn) > self::MAX_ENTRIES_PER_SECTION) {
                $e["sections.$si.entries"] = 'No máximo ' . self::MAX_ENTRIES_PER_SECTION . ' jogos por seção.';
            }

            $entries = [];
            $seen = [];
            foreach ($entriesIn as $ei => $x) {
                $x = is_array($x) ? $x : [];
                $title = Str::clean($x['title'] ?? '');
                $yearRaw = $x['release_year'] ?? null;
                $year = ($yearRaw === null || $yearRaw === '') ? null : filter_var($yearRaw, FILTER_VALIDATE_INT);
                $gameId = filter_var($x['game_id'] ?? null, FILTER_VALIDATE_INT);

                if ($title === '' || mb_strlen($title) > 200) {
                    $e["sections.$si.entries.$ei.title"] = 'Informe o título (até 200 caracteres).';
                }
                if ($year === false || ($year !== null && ($year < 1950 || $year > $maxYear))) {
                    $e["sections.$si.entries.$ei.release_year"] = "Ano entre 1950 e $maxYear.";
                    $year = null;
                }

                // título + ano idênticos na mesma seção = duplicado
                $sig = Str::key($title) . '|' . ($year ?? '');
                if ($title !== '' && isset($seen[$sig])) {
                    $e["sections.$si.entries.$ei.title"] = 'Item duplicado nesta seção (mesmo título e ano).';
                }
                $seen[$sig] = true;

                $entries[] = [
                    'title' => $title, 'release_year' => $year, 'game_id' => $gameId ?: null,
                ];
            }
            $total += count($entries);
            $sections[] = ['name' => $sname, 'entries' => $entries];
        }
        if ($total > self::MAX_ENTRIES_TOTAL) {
            $e['sections'] = 'No máximo ' . self::MAX_ENTRIES_TOTAL . ' jogos por franquia.';
        }

        if ($e) {
            throw new HttpException(422, 'Dados inválidos.', $e);
        }
        return [
            'name' => $name, 'description' => $desc === '' ? null : $desc, 'sections' => $sections,
            'cover_focus' => json_encode(self::focus($in['cover_focus'] ?? null)),
        ];
    }

    /** Enquadramento normalizado: ponto focal (x/y em %, 0–100) e zoom (1–3) do card e do banner. */
    private static function focus(mixed $in): array
    {
        $out = [];
        foreach (['card', 'hero'] as $k) {
            $p = is_array($in[$k] ?? null) ? $in[$k] : [];
            $num = fn(string $c, float $def, float $min, float $max) => is_numeric($p[$c] ?? null) ? round(max($min, min($max, (float) $p[$c])), 2) : $def;
            $out[$k] = ['x' => $num('x', 50, 0, 100), 'y' => $num('y', 50, 0, 100), 'z' => $num('z', 1, 1, 3)];
        }
        return $out;
    }
}
