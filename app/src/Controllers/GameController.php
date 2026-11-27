<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Auth, HttpException, Request, Response};
use App\Services\{BulkService, GameService, XpService};

final class GameController
{
    private GameService $games;

    public function __construct()
    {
        $this->games = new GameService();
    }

    public function index(Request $req): Response
    {
        return Response::json($this->games->list(Auth::require()['id'], $req->query));
    }

    /** Edição em massa: {ids:[], action:'update', field, value} ou {ids:[], action:'delete'}. */
    public function bulk(Request $req): Response
    {
        $id = Auth::require()['id'];
        $in = $req->input();
        $bulk = new BulkService();
        $ids = is_array($in['ids'] ?? null) ? $in['ids'] : [];
        $data = ($in['action'] ?? '') === 'delete'
            ? $bulk->delete($id, $ids)
            : $bulk->update($id, $ids, (string) ($in['field'] ?? ''), $in['value'] ?? '');
        return Response::json(['data' => $data]);
    }

    public function show(Request $req, array $p): Response
    {
        return Response::json(['data' => $this->games->find(Auth::require()['id'], (int) $p['id'])]);
    }

    /** JSON ou multipart/form-data (campo opcional "cover"). */
    public function store(Request $req): Response
    {
        $user = Auth::require();
        $game = $this->games->create($user['id'], $req->input(), $req->file('cover'));
        return Response::json(['data' => $game, 'xp' => $this->xp($user['id'])], 201);
    }

    public function update(Request $req, array $p): Response
    {
        $user = Auth::require();
        $game = $this->games->update($user['id'], (int) $p['id'], $req->input());
        return Response::json(['data' => $game, 'xp' => $this->xp($user['id'])]);
    }

    public function destroy(Request $req, array $p): Response
    {
        $user = Auth::require();
        $this->games->delete($user['id'], (int) $p['id']);
        return Response::json(['xp' => $this->xp($user['id'])]);
    }

    public function setCover(Request $req, array $p): Response
    {
        $file = $req->file('cover');
        if ($file === null) {
            throw new HttpException(422, 'Envie o arquivo no campo "cover".', ['cover' => 'Obrigatório.']);
        }
        return Response::json(['data' => $this->games->setCover(Auth::require()['id'], (int) $p['id'], $file)]);
    }

    public function removeCover(Request $req, array $p): Response
    {
        return Response::json(['data' => $this->games->removeCover(Auth::require()['id'], (int) $p['id'])]);
    }

    private function xp(int $userId): array
    {
        $total = (int) \App\Core\Database::value('SELECT total_xp FROM users WHERE id = ?', [$userId]);
        return (new XpService())->progress($total);
    }
}
