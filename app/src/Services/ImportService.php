<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\{Database, HttpException, Str};

/**
 * Importação de jogos zerados a partir de planilha (CSV ou XLSX) ou do backup JSON exportado pelo próprio site.
 * Fluxo: parse (lê e sugere o mapeamento) -> preview (valida linha a linha, sem gravar) -> commit (grava, em lotes).
 */
final class ImportService
{
    public const MAX_ROWS = 2000;
    public const MAX_BYTES = 5 * 1024 * 1024;
    public const COMMIT_BATCH = 150;

    /** Campos que podem receber uma coluna da planilha. */
    public const FIELDS = [
        'title' => 'Título', 'platform' => 'Sistema', 'developer' => 'Desenvolvedora', 'publisher' => 'Publisher', 'genre' => 'Gênero',
        'release_year' => 'Ano de lançamento', 'finished_at' => 'Data de finalização', 'playtime' => 'Tempo de jogo',
        'rating' => 'Nota', 'difficulty' => 'Dificuldade', 'tasks' => 'Tarefas',
    ];

    /** Cabeçalhos (sem acento/símbolos) que reconhecemos automaticamente. */
    private const SYNONYMS = [
        'title'        => ['titulo', 'nome', 'jogo', 'game', 'title', 'name', 'nomedojogo'],
        'platform'     => ['sistema', 'plataforma', 'console', 'platform', 'system'],
        'developer'    => ['desenvolvedora', 'desenvolvedor', 'estudio', 'developer', 'studio'],
        'publisher'    => ['publisher', 'publicadora', 'distribuidora', 'editora'],
        'genre'        => ['genero', 'genre', 'categoria'],
        'release_year' => ['anodelancamento', 'lancamento', 'anolancamento', 'ano', 'year', 'releaseyear', 'datadelancamento', 'releasedate'],
        'finished_at'  => ['finalizadoem', 'datadefinalizacao', 'finalizacao', 'data', 'zeradoem', 'datazerado', 'terminadoem', 'finished', 'completed', 'completedon', 'datafinalizado', 'finalizado', 'datadezerada', 'zerado'],
        'playtime'     => ['tempodejogo', 'tempo', 'horas', 'horasjogadas', 'playtime', 'hours', 'tempojogado', 'tempodejogomin', 'tempojogadomin', 'minutos', 'duracao'],
        'rating'       => ['nota', 'rating', 'score', 'avaliacao', 'pontuacao'],
        'difficulty'   => ['dificuldade', 'difficulty', 'dificuldadepercebida'],
        'tasks'        => ['tarefas', 'tasks', 'extras', 'tarefasextras', 'conquistas'],
    ];

    public function __construct(private GameService $games = new GameService()) {}

    // ====================== 1) Leitura do arquivo ======================

    /** @return array{format: string, headers: list<string>, rows: list<list<string>>, mapping: array<int,string>, time_unit: string, truncated: bool} */
    public function parseUpload(array $file): array
    {
        $err = $file['error'] ?? UPLOAD_ERR_OK;
        if ($err !== UPLOAD_ERR_OK) {
            throw new HttpException(422, match ($err) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Arquivo grande demais.',
                default => 'Falha no envio do arquivo.',
            }, ['file' => 'Falha no envio.']);
        }
        $tmp = $file['tmp_name'] ?? '';
        if (!is_uploaded_file($tmp)) {
            throw new HttpException(422, 'Envio inválido.', ['file' => 'Envio inválido.']);
        }
        if (filesize($tmp) > self::MAX_BYTES) {
            throw new HttpException(422, 'O arquivo passa de 5 MB.', ['file' => 'Máximo de 5 MB.']);
        }
        return $this->parseContent((string) file_get_contents($tmp));
    }

    public function parseContent(string $bytes): array
    {
        if (str_starts_with($bytes, "PK\x03\x04")) {
            $format = 'xlsx';
            $rows = XlsxReader::rows($bytes);
        } elseif (preg_match('/^\s*[\[{]/', substr($bytes, 0, 50)) === 1) {
            $format = 'json';
            $rows = $this->rowsFromJson($bytes);
        } else {
            $format = 'csv';
            $rows = $this->rowsFromCsv($bytes);
        }

        $rows = array_values(array_filter($rows, fn(array $r) => trim(implode('', $r)) !== ''));
        if (count($rows) < 2) {
            throw new HttpException(422, 'Não encontrei linhas de dados. A primeira linha deve ter os nomes das colunas.', ['file' => 'Arquivo sem dados.']);
        }
        $headers = array_map(fn($h) => trim((string) $h), array_shift($rows));
        $width = count($headers);
        $truncated = count($rows) > self::MAX_ROWS;
        $rows = array_slice($rows, 0, self::MAX_ROWS);
        $rows = array_map(fn(array $r) => array_slice(array_pad(array_map(fn($c) => trim((string) $c), $r), $width, ''), 0, $width), $rows);

        $mapping = $this->guessMapping($headers);
        $timeCol = array_search('playtime', $mapping, true);
        $unit = ($timeCol !== false && preg_match('/min/i', $headers[$timeCol]) === 1) ? 'minutes' : 'hours';   // "Tempo de jogo (min)" é o cabeçalho da exportação

        return ['format' => $format, 'headers' => $headers, 'rows' => $rows, 'mapping' => $mapping, 'time_unit' => $unit, 'truncated' => $truncated];
    }

    /** CSV com delimitador automático (; , tab |) e codificação UTF-8 ou Windows-1252 (o "CSV do Excel"). */
    private function rowsFromCsv(string $text): array
    {
        if (str_starts_with($text, "\xEF\xBB\xBF")) {
            $text = substr($text, 3);
        }
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'Windows-1252');
        }
        $firstLine = strtok($text, "\n") ?: '';
        $best = ';';
        $bestN = -1;
        foreach ([';', ',', "\t", '|'] as $d) {
            $n = substr_count($firstLine, $d);
            if ($n > $bestN) {
                [$best, $bestN] = [$d, $n];
            }
        }
        $fh = fopen('php://memory', 'r+');
        fwrite($fh, $text);
        rewind($fh);
        $rows = [];
        while (($r = fgetcsv($fh, 0, $best, '"', '')) !== false) {
            $rows[] = $r;
            if (count($rows) > self::MAX_ROWS + 5) {
                break;
            }
        }
        fclose($fh);
        return $rows;
    }

    /** Backup JSON gerado em Minha conta -> Seus dados: usa a lista "games". */
    private function rowsFromJson(string $bytes): array
    {
        $data = json_decode($bytes, true);
        $games = is_array($data) ? ($data['games'] ?? (array_is_list($data) ? $data : null)) : null;
        if (!is_array($games)) {
            throw new HttpException(422, 'Esse JSON não tem a lista "games" do backup do MyGameList.', ['file' => 'JSON inesperado.']);
        }
        $rows = [['Título', 'Sistema', 'Desenvolvedora', 'Publisher', 'Gênero', 'Ano de lançamento', 'Finalizado em', 'Tempo de jogo (min)', 'Nota', 'Dificuldade', 'Tarefas']];
        foreach ($games as $g) {
            if (!is_array($g)) {
                continue;
            }
            $tasks = implode(' | ', array_map(fn($t) => ($t['description'] ?? '') . ' (tier ' . ($t['tier'] ?? 1) . ')', is_array($g['tasks'] ?? null) ? $g['tasks'] : []));
            $rows[] = [$g['title'] ?? '', $g['platform'] ?? '', $g['developer'] ?? '', $g['publisher'] ?? '', $g['genre'] ?? '', $g['release_year'] ?? '',
                       $g['finished_at'] ?? '', $g['playtime_minutes'] ?? '', $g['rating'] ?? '', $g['difficulty'] ?? '', $tasks];
        }
        return $rows;
    }

    /** @return array<int,string> índice da coluna => campo */
    public function guessMapping(array $headers): array
    {
        $map = [];
        $used = [];
        foreach ($headers as $i => $h) {
            $key = $this->headerKey($h);
            foreach (self::SYNONYMS as $field => $names) {
                if (!isset($used[$field]) && in_array($key, $names, true)) {
                    $map[$i] = $field;
                    $used[$field] = true;
                    break;
                }
            }
        }
        return $map;
    }

    private function headerKey(string $h): string
    {
        $h = strtr(mb_strtolower($h), ['á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'é' => 'e', 'ê' => 'e', 'í' => 'i', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ú' => 'u', 'ç' => 'c']);
        return (string) preg_replace('/[^a-z0-9]+/', '', $h);
    }

    // ====================== 2) Validação (sem gravar) ======================

    /**
     * @param list<list<string>> $rows linhas de dados (sem o cabeçalho)
     * @param array<int,string>  $mapping índice da coluna => campo
     * @return array{rows: list<array>, counts: array<string,int>}
     */
    public function preview(?int $userId, array $rows, array $mapping, array $opts): array
    {
        $existing = $userId === null ? [] : $this->existingKeys($userId);
        $quota = $userId === null ? ['games' => 0, 'used' => [10 => 0, 11 => 0]] : (new RatingQuotaService())->status($userId);
        $seen = [];
        $out = [];
        $counts = ['ok' => 0, 'duplicate' => 0, 'error' => 0, 'total' => count($rows)];

        foreach ($rows as $i => $row) {
            $n = $i + 2; // número da linha na planilha (1 = cabeçalho)
            [$data, $errors, $warnings] = $this->normalize($this->assoc($row, $mapping), $opts);
            $status = 'ok';
            if ($errors) {
                $status = 'error';
            } else {
                $key = $this->dupKey($data);
                if (isset($existing[$key])) {
                    $status = 'duplicate';
                    $warnings[] = 'Já existe no seu catálogo (mesmo título, sistema e ano).';
                } elseif (isset($seen[$key])) {
                    $status = 'duplicate';
                    $warnings[] = 'Repetido na própria planilha (linha ' . $seen[$key] . ').';
                } else {
                    $seen[$key] = $n;
                    $r = $data['rating'];
                    if (isset(RatingQuotaService::RULES[$r]) && $quota['used'][$r] + 1 > RatingQuotaService::allowed($r, $quota['games'] + 1)) {
                        $status = 'error';
                        $per = RatingQuotaService::RULES[$r];
                        $errors[] = "Limite de notas $r: 1 a cada $per jogos (com " . ($quota['games'] + 1) . " jogos o limite é " . RatingQuotaService::allowed($r, $quota['games'] + 1) . '). Use outra nota.';
                        unset($seen[$key]);
                    } else {
                        $quota['games']++;
                        if (isset($quota['used'][$r])) {
                            $quota['used'][$r]++;
                        }
                    }
                }
            }
            $counts[$status]++;
            $out[] = ['n' => $n, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'data' => $errors ? null : $data];
        }
        return ['rows' => $out, 'counts' => $counts];
    }

    // ====================== 3) Gravação (em lotes) ======================

    /** Grava as linhas válidas e ainda inexistentes. Sempre revalida (nunca confia na pré-visualização). */
    public function commit(int $userId, array $rows, array $mapping, array $opts): array
    {
        if (count($rows) > self::COMMIT_BATCH) {
            throw new HttpException(422, 'Lote grande demais (máximo de ' . self::COMMIT_BATCH . ' linhas por vez).');
        }
        $existing = $this->existingKeys($userId);
        $imported = $skipped = 0;
        $errors = [];

        foreach ($rows as $row) {
            $n = (int) ($row['n'] ?? 0);
            [$data, $problems] = $this->normalize($this->assoc((array) ($row['cells'] ?? []), $mapping), $opts);
            if ($problems) {
                $errors[] = ['n' => $n, 'messages' => $problems];
                continue;
            }
            $key = $this->dupKey($data);
            if (isset($existing[$key])) {
                $skipped++;
                continue;
            }
            try {
                $this->games->create($userId, $data, null);
                $existing[$key] = true;
                $imported++;
            } catch (HttpException $e) {
                if ($e->status === 409) {
                    $skipped++;
                } else {
                    $errors[] = ['n' => $n, 'messages' => array_values($e->errors) ?: [$e->getMessage()]];
                }
            }
        }

        $total = (int) Database::value('SELECT total_xp FROM users WHERE id = ?', [$userId]);
        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors, 'xp' => (new XpService())->progress($total)];
    }

    // ====================== Normalização de uma linha ======================

    /** @return array<string,string> campo => texto cru da célula */
    private function assoc(array $cells, array $mapping): array
    {
        $out = [];
        foreach ($mapping as $col => $field) {
            if ($field !== '' && isset(self::FIELDS[$field])) {
                $out[$field] = trim((string) ($cells[$col] ?? ''));
            }
        }
        return $out;
    }

    /** @return array{0: array, 1: list<string>, 2: list<string>} [dados prontos para o GameService, erros, avisos] */
    private function normalize(array $raw, array $opts): array
    {
        $def = is_array($opts['defaults'] ?? null) ? $opts['defaults'] : [];
        $errors = $warnings = [];
        $pick = fn(string $f) => ($v = trim((string) ($raw[$f] ?? ''))) !== '' ? $v : trim((string) ($def[$f] ?? ''));

        $title = Str::clean($raw['title'] ?? '');
        if ($title === '') {
            $errors[] = 'Falta o título.';
        } elseif (mb_strlen($title) > 200) {
            $errors[] = 'Título com mais de 200 caracteres.';
        }

        $platform = Str::clean($pick('platform'));
        if ($platform === '') {
            $errors[] = 'Falta o sistema (mapeie a coluna ou defina um padrão).';
        } elseif (mb_strlen($platform) > 60) {
            $errors[] = 'Sistema com mais de 60 caracteres.';
        }

        $genre = Str::clean($pick('genre'));
        if ($genre === '') {
            $errors[] = 'Falta o gênero (mapeie a coluna ou defina um padrão).';
        } elseif (mb_strlen($genre) > 50) {
            $errors[] = 'Gênero com mais de 50 caracteres.';
        }

        $developer = Str::clean($raw['developer'] ?? '');
        $publisher = Str::clean($raw['publisher'] ?? '');
        if (mb_strlen($developer) > 100 || mb_strlen($publisher) > 100) {
            $errors[] = 'Desenvolvedora/publisher com mais de 100 caracteres.';
        }

        $finished = $this->parseDate((string) ($raw['finished_at'] ?? ''));
        if ($finished === null) {
            $errors[] = ($raw['finished_at'] ?? '') === '' ? 'Falta a data de finalização.' : 'Data de finalização não reconhecida: "' . $raw['finished_at'] . '".';
        } elseif ($finished > date('Y-m-d')) {
            $errors[] = 'A data de finalização está no futuro.';
        }

        $year = $this->parseYear((string) ($raw['release_year'] ?? ''));
        if ($year === null) {
            $errors[] = ($raw['release_year'] ?? '') === '' ? 'Falta o ano de lançamento.' : 'Ano de lançamento inválido: "' . $raw['release_year'] . '".';
        } elseif ($year < 1950 || $year > (int) date('Y')) {
            $errors[] = "Ano de lançamento fora do intervalo ($year).";
        } elseif ($finished !== null && $year > (int) substr($finished, 0, 4)) {
            $errors[] = 'O ano de lançamento é depois da finalização.';
        }

        $minutes = 0;
        $timeRaw = trim((string) ($raw['playtime'] ?? ''));
        if ($timeRaw === '') {
            $warnings[] = 'Sem tempo de jogo: será salvo como 0.';
        } else {
            $minutes = $this->parseMinutes($timeRaw, ($opts['time_unit'] ?? 'hours') === 'minutes' ? 'minutes' : 'hours');
            if ($minutes === null) {
                $errors[] = 'Tempo de jogo não reconhecido: "' . $timeRaw . '".';
                $minutes = 0;
            }
        }

        $rating = $this->parseRating($pick('rating'));
        if ($rating === null) {
            $errors[] = $pick('rating') === '' ? 'Falta a nota (mapeie a coluna ou defina um padrão).' : 'Nota inválida: "' . $pick('rating') . '" (use de 1 a 11).';
        }

        $diff = strtoupper(trim($pick('difficulty')));
        if (!in_array($diff, ['C', 'B', 'A', 'AA', 'AAA'], true)) {
            $errors[] = $diff === '' ? 'Falta a dificuldade (mapeie a coluna ou defina um padrão).' : 'Dificuldade inválida: "' . $diff . '" (use C, B, A, AA ou AAA).';
        }

        $tasks = $this->parseTasks((string) ($raw['tasks'] ?? ''), $warnings);

        return [[
            'title' => $title, 'platform' => $platform, 'developer' => $developer, 'publisher' => $publisher, 'genre' => $genre,
            'release_year' => $year, 'finished_at' => $finished, 'playtime_minutes' => $minutes, 'rating' => $rating,
            'difficulty' => $diff, 'tasks' => $tasks,
        ], $errors, $warnings];
    }

    private function dupKey(array $d): string
    {
        return Str::key($d['title']) . '|' . Str::key($d['platform']) . '|' . $d['release_year'];
    }

    private function existingKeys(int $userId): array
    {
        $out = [];
        foreach (Database::all(
            'SELECT g.title_key, p.name_key, g.release_year FROM games g JOIN platforms p ON p.id = g.platform_id WHERE g.user_id = ?', [$userId]
        ) as $r) {
            $out[$r['title_key'] . '|' . $r['name_key'] . '|' . $r['release_year']] = true;
        }
        return $out;
    }

    // ---------- conversores tolerantes ----------

    /** Aceita AAAA-MM-DD, DD/MM/AAAA, DD/MM/AA, DD-MM-AAAA, DD.MM.AAAA, com hora, e número de série do Excel. */
    public function parseDate(string $v): ?string
    {
        $v = trim($v);
        if ($v === '') {
            return null;
        }
        if (preg_match('/^\d{5}(\.\d+)?$/', $v) && (float) $v > 20000 && (float) $v < 80000) { // data em número de série (Excel)
            return gmdate('Y-m-d', (int) round(((float) $v - 25569) * 86400));
        }
        $v = preg_replace('/[ T]\d{1,2}:\d{2}(:\d{2})?.*$/', '', $v);
        foreach (['!Y-m-d', '!Y/m/d', '!d/m/Y', '!d-m-Y', '!d.m.Y', '!d/m/y', '!d-m-y', '!d.m.y'] as $fmt) {
            $d = \DateTimeImmutable::createFromFormat($fmt, $v);
            $err = \DateTimeImmutable::getLastErrors();
            if ($d !== false && (!$err || ($err['warning_count'] === 0 && $err['error_count'] === 0))) {
                return $d->format('Y-m-d');
            }
        }
        return null;
    }

    private function parseYear(string $v): ?int
    {
        $v = trim($v);
        if (preg_match('/^(\d{4})(\.0+)?$/', $v, $m)) {
            return (int) $m[1];
        }
        if (preg_match('/(?<!\d)(\d{4})(?!\d)/', $v, $m) && (strlen($v) > 4)) { // "15/03/2005" -> 2005
            return (int) $m[1];
        }
        if (preg_match('/^\d{5}$/', $v)) { // número de série do Excel
            $d = $this->parseDate($v);
            return $d === null ? null : (int) substr($d, 0, 4);
        }
        return null;
    }

    /** "12h", "12h30", "12h 30min", "12:30", "750 min", "12,5" (horas) ou "750" (minutos) conforme a opção. */
    private function parseMinutes(string $v, string $plainUnit): ?int
    {
        $v = mb_strtolower(trim($v));
        $num = fn(string $s) => (float) str_replace(',', '.', $s);
        if (preg_match('/^(\d+)\s*h(?:oras?)?\s*(?:e\s*)?(\d{1,2})?\s*(?:m(?:in(?:utos?)?)?)?$/u', $v, $m)) {
            return (int) $m[1] * 60 + (int) ($m[2] ?? 0);
        }
        if (preg_match('/^(\d+):(\d{1,2})(?::\d{2})?$/', $v, $m)) {
            return (int) $m[1] * 60 + (int) $m[2];
        }
        if (preg_match('/^(\d+(?:[.,]\d+)?)\s*(?:h|horas?)$/u', $v, $m)) {
            return (int) round($num($m[1]) * 60);
        }
        if (preg_match('/^(\d+(?:[.,]\d+)?)\s*(?:m|min|minutos?)$/u', $v, $m)) {
            return (int) round($num($m[1]));
        }
        if (preg_match('/^\d+(?:[.,]\d+)?$/', $v)) {
            $n = $num($v);
            return (int) round($plainUnit === 'minutes' ? $n : $n * 60);
        }
        return null;
    }

    private function parseRating(string $v): ?int
    {
        $v = trim(str_replace(',', '.', $v));
        if ($v === '' || !is_numeric($v)) {
            return null;
        }
        $n = (int) round((float) $v);
        return ($n >= 1 && $n <= 11) ? $n : null;
    }

    /** "Platina (tier 3) | Colecionáveis (tier 2)": separa por | ; ou quebra de linha. Sem tier, vira tier 1. */
    private function parseTasks(string $v, array &$warnings): array
    {
        $out = [];
        foreach (preg_split('/\s*[|;\n]\s*/', trim($v)) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $tier = 1;
            if (preg_match('/^(.*?)\s*\(\s*tier\s*([1-3])\s*\)\s*$/iu', $part, $m)) {
                $part = trim($m[1]);
                $tier = (int) $m[2];
            }
            $part = Str::clean($part);
            if ($part !== '' && mb_strlen($part) <= 200) {
                $out[] = ['description' => $part, 'tier' => $tier];
            }
        }
        if (count($out) > 20) {
            $warnings[] = 'Mais de 20 tarefas: só as 20 primeiras serão importadas.';
            $out = array_slice($out, 0, 20);
        }
        return $out;
    }

    // ====================== Modelo e exemplo ======================

    /** CSV de modelo, com o mesmo cabeçalho da exportação (assim dá para ir e voltar). */
    public static function templateCsv(): string
    {
        $rows = [
            ['Título', 'Sistema', 'Desenvolvedora', 'Publisher', 'Gênero', 'Ano de lançamento', 'Finalizado em', 'Tempo de jogo (min)', 'Nota', 'Dificuldade', 'Tarefas'],
            ['Resident Evil 4', 'GameCube', 'Capcom', 'Capcom', 'Survival Horror', '2005', '2026-03-14', '1140', '11', 'AA', 'Terminei no Profissional (tier 3)'],
            ['Hades', 'PC', 'Supergiant Games', 'Supergiant Games', 'Roguelike', '2020', '15/04/2026', '30h', '10', 'A', ''],
        ];
        $out = "\xEF\xBB\xBF";
        foreach ($rows as $r) {
            $out .= implode(';', array_map([AccountService::class, 'csvCell'], $r)) . "\r\n";
        }
        return $out;
    }

    /** Estado pronto para a página de exemplo: uma planilha fictícia (com erros de propósito) já lida e validada. */
    public function demo(): array
    {
        $games = array_slice(DemoCatalog::games(''), 0, 16);
        $csv = "Jogo;Console;Estúdio;Distribuidora;Categoria;Lançamento;Zerado em;Horas;Nota;Dificuldade;Extras\r\n";
        foreach ($games as $i => $g) {
            $tasks = implode(' | ', array_map(fn($t) => $t['description'] . ' (tier ' . $t['tier'] . ')', $g['tasks']));
            $date = $i % 3 === 0 ? date('d/m/Y', strtotime($g['finished_at'])) : $g['finished_at'];   // formatos misturados, como numa planilha real
            $hours = $i % 4 === 0 ? floor($g['playtime_minutes'] / 60) . 'h' . ($g['playtime_minutes'] % 60) : number_format($g['playtime_minutes'] / 60, 1, ',', '');
            $csv .= implode(';', [$g['title'], $g['platform'], $g['developer'], $g['publisher'], $g['genre'], $g['release_year'], $date, $hours, $g['rating'], $g['difficulty'], $tasks]) . "\r\n";
        }
        // linhas com problemas, para mostrar a revisão
        $csv .= "Jogo sem sistema;;Capcom;Capcom;Ação;2010;2026-01-10;10;8;B;\r\n";
        $csv .= "Data estranha;PC;;;RPG;2015;ontem;12;9;A;\r\n";
        $csv .= "Nota alta demais;PC;;;RPG;2015;2026-02-02;12;15;A;\r\n";
        $csv .= "Dificuldade errada;PS5;;;Ação;2021;2026-02-03;8;7;Z;\r\n";
        $csv .= $games[0]['title'] . ';' . $games[0]['platform'] . ';;;' . $games[0]['genre'] . ';' . $games[0]['release_year'] . ';2026-05-05;5;8;B;' . "\r\n";   // repetido na planilha

        $parsed = $this->parseContent($csv);
        $opts = ['time_unit' => 'hours', 'defaults' => ['platform' => '', 'genre' => '', 'difficulty' => '', 'rating' => '']];
        return [
            'fileName' => 'minha-lista-de-jogos.csv', 'format' => $parsed['format'], 'headers' => $parsed['headers'], 'rows' => $parsed['rows'],
            'mapping' => $parsed['mapping'], 'truncated' => false, 'opts' => $opts,
            'preview' => $this->preview(null, $parsed['rows'], $parsed['mapping'], $opts),
        ];
    }
}
