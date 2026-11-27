<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\HttpException;

/**
 * Leitor mínimo de .xlsx (planilha do Excel) sem depender da extensão zip do PHP:
 * um .xlsx é um ZIP; lemos o diretório central, descompactamos só o necessário (gzinflate) e interpretamos o XML.
 * Lê a primeira planilha e devolve as linhas como listas de texto.
 */
final class XlsxReader
{
    private const MAX_UNCOMPRESSED = 60 * 1024 * 1024;   // proteção contra "zip bomb"
    private const MAX_ROWS = 6000;
    private const MAX_COLS = 60;

    /** @return list<list<string>> */
    public static function rows(string $bytes): array
    {
        $files = self::entries($bytes);
        $sheetName = self::firstSheet($files);
        if ($sheetName === null) {
            throw new HttpException(422, 'Não encontrei nenhuma planilha dentro do arquivo .xlsx.');
        }

        $shared = [];
        if (isset($files['xl/sharedStrings.xml'])) {
            $sst = self::xml(self::read($bytes, $files['xl/sharedStrings.xml']));
            foreach ($sst->si ?? [] as $si) {
                $text = '';
                foreach ($si->xpath('.//*[local-name()="t"]') ?: [] as $t) {
                    $text .= (string) $t;
                }
                $shared[] = $text;
            }
        }

        $sheet = self::xml(self::read($bytes, $files[$sheetName]));
        $rows = [];
        foreach ($sheet->sheetData->row ?? [] as $row) {
            $cells = [];
            foreach ($row->c as $c) {
                $col = self::columnIndex((string) $c['r']);
                if ($col >= self::MAX_COLS) {
                    continue;
                }
                $type = (string) $c['t'];
                $value = match ($type) {
                    's'         => $shared[(int) $c->v] ?? '',
                    'inlineStr' => (string) ($c->is->t ?? ''),
                    'b'         => ((string) $c->v) === '1' ? 'sim' : 'não',
                    default     => (string) $c->v,   // número, texto de fórmula, data em número de série
                };
                $cells[$col] = trim($value);
            }
            if ($cells) {
                $max = max(array_keys($cells));
                $line = [];
                for ($i = 0; $i <= $max; $i++) {
                    $line[] = $cells[$i] ?? '';
                }
                $rows[] = $line;
            }
            if (count($rows) >= self::MAX_ROWS) {
                break;
            }
        }
        return $rows;
    }

    /** Diretório central do ZIP: nome => [método, tamanho compactado, tamanho real, posição do cabeçalho local]. */
    private static function entries(string $z): array
    {
        $len = strlen($z);
        $eocd = strrpos($z, "PK\x05\x06");
        if ($eocd === false || $len < 22) {
            throw new HttpException(422, 'O arquivo não parece ser um .xlsx válido.');
        }
        $count = unpack('v', substr($z, $eocd + 10, 2))[1];
        $pos = unpack('V', substr($z, $eocd + 16, 4))[1];

        $u16 = fn(int $o): int => unpack('v', substr($z, $o, 2))[1];
        $u32 = fn(int $o): int => unpack('V', substr($z, $o, 4))[1];

        $out = [];
        for ($i = 0; $i < $count && $pos + 46 <= $len; $i++) {
            if (substr($z, $pos, 4) !== "PK") {
                break;
            }
            // layout do cabeçalho do diretório central (deslocamentos a partir do início dele)
            $nlen = $u16($pos + 28);
            $out[substr($z, $pos + 46, $nlen)] = [
                'method' => $u16($pos + 10), 'csize' => $u32($pos + 20), 'usize' => $u32($pos + 24), 'off' => $u32($pos + 42),
            ];
            $pos += 46 + $nlen + $u16($pos + 30) + $u16($pos + 32);
        }
        return $out;
    }

    private static function read(string $z, array $e): string
    {
        if ($e['usize'] > self::MAX_UNCOMPRESSED) {
            throw new HttpException(422, 'O arquivo é grande demais para importar.');
        }
        if (substr($z, $e['off'], 4) !== "PK\x03\x04") {
            throw new HttpException(422, 'O arquivo .xlsx está corrompido.');
        }
        $n = unpack('v', substr($z, $e['off'] + 26, 2))[1];
        $m = unpack('v', substr($z, $e['off'] + 28, 2))[1];
        $raw = substr($z, $e['off'] + 30 + $n + $m, $e['csize']);
        $data = match ($e['method']) {
            0 => $raw,
            8 => @gzinflate($raw, self::MAX_UNCOMPRESSED),
            default => false,
        };
        if ($data === false) {
            throw new HttpException(422, 'Não consegui ler o conteúdo do .xlsx.');
        }
        return $data;
    }

    /** A planilha de menor número (sheet1.xml, normalmente). */
    private static function firstSheet(array $files): ?string
    {
        $best = null;
        $bestN = PHP_INT_MAX;
        foreach (array_keys($files) as $name) {
            if (preg_match('#^xl/worksheets/sheet(\d+)\.xml$#', $name, $m) && (int) $m[1] < $bestN) {
                $best = $name;
                $bestN = (int) $m[1];
            }
        }
        return $best;
    }

    private static function xml(string $xml): \SimpleXMLElement
    {
        $prev = libxml_use_internal_errors(true);
        $el = simplexml_load_string($xml, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_COMPACT);
        libxml_use_internal_errors($prev);
        if ($el === false) {
            throw new HttpException(422, 'O conteúdo do .xlsx não pôde ser interpretado.');
        }
        return $el;
    }

    /** "C7" => 2 (coluna C, índice 0-based). */
    private static function columnIndex(string $ref): int
    {
        $n = 0;
        foreach (str_split(strtoupper(preg_replace('/[^A-Za-z]/', '', $ref))) as $ch) {
            $n = $n * 26 + (ord($ch) - 64);
        }
        return max(0, $n - 1);
    }
}
