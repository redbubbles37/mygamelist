<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Auth, HttpException, Request, Response};
use App\Services\DroppedService;

final class DroppedController
{
    private DroppedService $dropped;

    public function __construct()
    {
        $this->dropped = new DroppedService();
    }

    public function index(Request $req): Response
    {
        return Response::json($this->dropped->list(Auth::require()['id']));
    }

    public function show(Request $req, array $p): Response
    {
        return Response::json(['data' => $this->dropped->find(Auth::require()['id'], (int) $p['id'])]);
    }

    /** JSON ou multipart/form-data (campo opcional "cover"). */
    public function store(Request $req): Response
    {
        return Response::json(['data' => $this->dropped->create(Auth::require()['id'], $req->input(), $req->file('cover'))], 201);
    }

    public function update(Request $req, array $p): Response
    {
        return Response::json(['data' => $this->dropped->update(Auth::require()['id'], (int) $p['id'], $req->input())]);
    }

    public function resume(Request $req, array $p): Response
    {
        return Response::json(['data' => $this->dropped->resume(Auth::require()['id'], (int) $p['id'])]);
    }

    public function chance(Request $req, array $p): Response
    {
        return Response::json(['data' => $this->dropped->setChance(Auth::require()['id'], (int) $p['id'], $req->input()['return_chance'] ?? null)]);
    }

    public function destroy(Request $req, array $p): Response
    {
        $this->dropped->delete(Auth::require()['id'], (int) $p['id']);
        return Response::noContent();
    }

    public function setCover(Request $req, array $p): Response
    {
        $file = $req->file('cover');
        if ($file === null) {
            throw new HttpException(422, 'Envie o arquivo no campo "cover".', ['cover' => 'Obrigatório.']);
        }
        return Response::json(['data' => $this->dropped->setCover(Auth::require()['id'], (int) $p['id'], $file)]);
    }

    public function removeCover(Request $req, array $p): Response
    {
        return Response::json(['data' => $this->dropped->removeCover(Auth::require()['id'], (int) $p['id'])]);
    }
}
