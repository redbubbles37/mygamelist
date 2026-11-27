<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Auth, Request, Response};
use App\Services\GotyService;

final class GotyController
{
    private GotyService $goty;

    public function __construct()
    {
        $this->goty = new GotyService();
    }

    /** GET /api/goty: anos liberados (5+ jogos zerados), candidatos e escolhas. */
    public function index(Request $req): Response
    {
        return Response::json(['min' => GotyService::MIN_GAMES, 'data' => $this->goty->years(Auth::require()['id'])]);
    }

    /** PUT /api/goty/{year} {game_id, note} */
    public function set(Request $req, array $p): Response
    {
        $in = $req->input();
        return Response::json(['data' => $this->goty->set(Auth::require()['id'], (int) $p['year'], $in['game_id'] ?? null, $in['note'] ?? '')]);
    }

    /** DELETE /api/goty/{year} */
    public function destroy(Request $req, array $p): Response
    {
        $this->goty->clear(Auth::require()['id'], (int) $p['year']);
        return Response::noContent();
    }
}
