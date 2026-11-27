<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Auth, HttpException, Request, Response};
use App\Services\BacklogService;

final class BacklogController
{
    private BacklogService $backlog;

    public function __construct()
    {
        $this->backlog = new BacklogService();
    }

    public function index(Request $req): Response
    {
        return Response::json($this->backlog->list(Auth::require()['id']));
    }

    public function show(Request $req, array $p): Response
    {
        return Response::json(['data' => $this->backlog->find(Auth::require()['id'], (int) $p['id'])]);
    }

    /** JSON ou multipart/form-data (campo opcional "cover"). */
    public function store(Request $req): Response
    {
        return Response::json(['data' => $this->backlog->create(Auth::require()['id'], $req->input(), $req->file('cover'))], 201);
    }

    public function update(Request $req, array $p): Response
    {
        return Response::json(['data' => $this->backlog->update(Auth::require()['id'], (int) $p['id'], $req->input())]);
    }

    public function start(Request $req, array $p): Response
    {
        return Response::json(['data' => $this->backlog->start(Auth::require()['id'], (int) $p['id'], $req->input()['started_at'] ?? null)]);
    }

    public function queue(Request $req, array $p): Response
    {
        return Response::json(['data' => $this->backlog->queue(Auth::require()['id'], (int) $p['id'])]);
    }

    public function priority(Request $req, array $p): Response
    {
        return Response::json(['data' => $this->backlog->setPriority(Auth::require()['id'], (int) $p['id'], $req->input()['priority'] ?? null)]);
    }

    public function destroy(Request $req, array $p): Response
    {
        $this->backlog->delete(Auth::require()['id'], (int) $p['id']);
        return Response::noContent();
    }

    public function setCover(Request $req, array $p): Response
    {
        $file = $req->file('cover');
        if ($file === null) {
            throw new HttpException(422, 'Envie o arquivo no campo "cover".', ['cover' => 'Obrigatório.']);
        }
        return Response::json(['data' => $this->backlog->setCover(Auth::require()['id'], (int) $p['id'], $file)]);
    }

    public function removeCover(Request $req, array $p): Response
    {
        return Response::json(['data' => $this->backlog->removeCover(Auth::require()['id'], (int) $p['id'])]);
    }
}
