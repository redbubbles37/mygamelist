<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Auth, HttpException, Request, Response};
use App\Services\BestOfService;

final class BestOfController
{
    /** GET /api/best/candidates?kind=platform&value=PS2 — jogos que podem ser escolhidos. */
    public function candidates(Request $req): Response
    {
        $kind = (string) ($req->query['kind'] ?? '');
        $value = (string) ($req->query['value'] ?? '');
        if (!in_array($kind, BestOfService::KINDS, true) || $value === '') {
            throw new HttpException(422, 'Categoria inválida.');
        }
        $svc = new BestOfService();
        $uid = Auth::require()['id'];
        return Response::json([
            'data'       => $svc->candidates($uid, $kind, $value),            // só nota 9, 10 ou 11
            'total'      => count($svc->candidates($uid, $kind, $value, false)), // todos os jogos da categoria
            'min'        => BestOfService::MIN_GAMES,
            'min_rating' => BestOfService::MIN_RATING,
        ]);
    }

    /** PUT /api/best {kind, value, game_id} — game_id nulo desfaz a escolha. */
    public function choose(Request $req): Response
    {
        $in = $req->input();
        $gameId = ($in['game_id'] ?? null) === null ? null : filter_var($in['game_id'], FILTER_VALIDATE_INT);
        if ($gameId === false) {
            throw new HttpException(422, 'Jogo inválido.');
        }
        return Response::json(['data' => (new BestOfService())->choose(
            Auth::require()['id'], (string) ($in['kind'] ?? ''), (string) ($in['value'] ?? ''), $gameId
        )]);
    }
}
