<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Auth, HttpException, Request, Response};
use App\Services\ImportService;

final class ImportController
{
    private ImportService $import;

    public function __construct()
    {
        $this->import = new ImportService();
    }

    /** multipart: campo "file" (CSV, XLSX ou JSON de backup). Devolve cabeçalhos, linhas e o mapeamento sugerido. */
    public function parse(Request $req): Response
    {
        Auth::require();
        $file = $req->file('file');
        if ($file === null) {
            throw new HttpException(422, 'Envie o arquivo no campo "file".', ['file' => 'Obrigatório.']);
        }
        return Response::json(['data' => $this->import->parseUpload($file)]);
    }

    public function preview(Request $req): Response
    {
        $id = Auth::require()['id'];
        $in = $req->input();
        return Response::json(['data' => $this->import->preview($id, $this->rows($in), $this->mapping($in), $this->opts($in))]);
    }

    public function commit(Request $req): Response
    {
        $id = Auth::require()['id'];
        $in = $req->input();
        $rows = is_array($in['rows'] ?? null) ? $in['rows'] : [];
        return Response::json(['data' => $this->import->commit($id, $rows, $this->mapping($in), $this->opts($in))]);
    }

    public function template(Request $req): Response
    {
        return Response::download(ImportService::templateCsv(), 'mygamelist-modelo-importacao.csv', 'text/csv; charset=utf-8');
    }

    private function rows(array $in): array
    {
        $rows = is_array($in['rows'] ?? null) ? $in['rows'] : [];
        return array_values(array_map(fn($r) => array_map('strval', array_map(fn($c) => is_scalar($c) ? $c : '', (array) $r)), array_slice($rows, 0, ImportService::MAX_ROWS)));
    }

    private function mapping(array $in): array
    {
        $out = [];
        foreach ((array) ($in['mapping'] ?? []) as $col => $field) {
            if (is_scalar($field) && isset(ImportService::FIELDS[(string) $field]) && (int) $col >= 0 && (int) $col < 200) {
                $out[(int) $col] = (string) $field;
            }
        }
        return $out;
    }

    private function opts(array $in): array
    {
        $o = is_array($in['opts'] ?? null) ? $in['opts'] : [];
        $d = is_array($o['defaults'] ?? null) ? $o['defaults'] : [];
        return [
            'time_unit' => ($o['time_unit'] ?? '') === 'minutes' ? 'minutes' : 'hours',
            'defaults' => array_map(fn($v) => is_scalar($v) ? (string) $v : '', array_intersect_key($d, array_flip(['platform', 'genre', 'difficulty', 'rating']))),
        ];
    }
}
