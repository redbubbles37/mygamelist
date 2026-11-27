<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Auth, Request, Response};
use App\Services\NotesService;

final class NotesController
{
    private NotesService $notes;

    public function __construct()
    {
        $this->notes = new NotesService();
    }

    public function index(Request $req, array $p): Response
    {
        return Response::json(['data' => $this->notes->list(Auth::require()['id'], $this->kind($req), (int) $p['id'])]);
    }

    public function store(Request $req, array $p): Response
    {
        return Response::json(['data' => $this->notes->add(Auth::require()['id'], $this->kind($req), (int) $p['id'], $req->input())], 201);
    }

    private function kind(Request $req): string
    {
        return str_starts_with($req->path, '/api/backlog/') ? 'backlog' : (str_starts_with($req->path, '/api/dropped/') ? 'dropped' : 'games');
    }

    public function update(Request $req, array $p): Response
    {
        return Response::json(['data' => $this->notes->update(Auth::require()['id'], (int) $p['id'], $req->input())]);
    }

    public function destroy(Request $req, array $p): Response
    {
        $this->notes->delete(Auth::require()['id'], (int) $p['id']);
        return Response::noContent();
    }

    /** Pessoas e lugares ligados aos jogos. */
    public function circles(Request $req): Response
    {
        return Response::json(['data' => $this->notes->circles(Auth::require()['id'])]);
    }
}
