<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Auth, HttpException, Request, Response};
use App\Services\FranchiseService;

final class FranchiseController
{
    private FranchiseService $franchises;

    public function __construct()
    {
        $this->franchises = new FranchiseService();
    }

    public function index(Request $req): Response
    {
        return Response::json(['data' => $this->franchises->list(Auth::require()['id'])]);
    }

    public function show(Request $req, array $p): Response
    {
        return Response::json(['data' => $this->franchises->find(Auth::require()['id'], (int) $p['id'])]);
    }

    public function store(Request $req): Response
    {
        return Response::json(['data' => $this->franchises->save(Auth::require()['id'], null, $req->input())], 201);
    }

    public function update(Request $req, array $p): Response
    {
        return Response::json(['data' => $this->franchises->save(Auth::require()['id'], (int) $p['id'], $req->input())]);
    }

    /** Multipart com o arquivo em "cover", ou JSON {"game_id": N} para usar a capa de um jogo do catálogo. */
    public function setCover(Request $req, array $p): Response
    {
        $userId = Auth::require()['id'];
        $slot = $p['slot'] ?? 'card';
        $file = $req->file('cover');
        if ($file !== null) {
            return Response::json(['data' => $this->franchises->setCover($userId, (int) $p['id'], $file, $slot)]);
        }
        $gameId = filter_var($req->input()['game_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$gameId) {
            throw new HttpException(422, 'Envie uma imagem ou escolha um jogo.', ['cover' => 'Obrigatório.']);
        }
        return Response::json(['data' => $this->franchises->coverFromGame($userId, (int) $p['id'], $gameId, $slot)]);
    }

    /** Banner: mesmas ações da imagem do card, no slot "hero". */
    public function setHero(Request $req, array $p): Response { return $this->setCover($req, ['slot' => 'hero'] + $p); }
    public function removeHero(Request $req, array $p): Response { return $this->removeCover($req, ['slot' => 'hero'] + $p); }

    public function removeCover(Request $req, array $p): Response
    {
        return Response::json(['data' => $this->franchises->removeCover(Auth::require()['id'], (int) $p['id'], $p['slot'] ?? 'card')]);
    }

    public function destroy(Request $req, array $p): Response
    {
        $this->franchises->delete(Auth::require()['id'], (int) $p['id']);
        return Response::noContent();
    }
}
