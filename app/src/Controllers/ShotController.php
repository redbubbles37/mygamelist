<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Auth, Request, Response};
use App\Services\ShotService;

final class ShotController
{
    private ShotService $shots;

    public function __construct()
    {
        $this->shots = new ShotService();
    }

    private function kind(Request $req): string
    {
        return str_starts_with($req->path, '/api/backlog/') ? 'backlog' : (str_starts_with($req->path, '/api/dropped/') ? 'dropped' : 'games');
    }

    public function index(Request $req, array $p): Response
    {
        return Response::json(['data' => $this->shots->list(Auth::require()['id'], $this->kind($req), (int) $p['id'])]);
    }

    /** multipart: "shots" (um arquivo) ou "shots[]" (vários), "note_id" e "caption" opcionais. */
    public function store(Request $req, array $p): Response
    {
        $in = $req->input();
        $result = $this->shots->upload(Auth::require()['id'], $this->kind($req), (int) $p['id'], $_FILES['shots'] ?? [], $in['note_id'] ?? null, $in['caption'] ?? '');
        if (!$result['saved']) { // nenhuma imagem passou: devolve o motivo da primeira
            throw new \App\Core\HttpException(422, $result['errors'][0]['message'] ?? 'Imagem inválida.', ['shots' => $result['errors'][0]['message'] ?? 'Imagem inválida.']);
        }
        return Response::json(['data' => $result], 201);
    }

    public function update(Request $req, array $p): Response
    {
        return Response::json(['data' => $this->shots->update(Auth::require()['id'], (int) $p['id'], $req->input())]);
    }

    public function destroy(Request $req, array $p): Response
    {
        $this->shots->delete(Auth::require()['id'], (int) $p['id']);
        return Response::noContent();
    }
}
