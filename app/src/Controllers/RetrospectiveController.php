<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Auth, Request, Response};
use App\Services\RetrospectiveService;

final class RetrospectiveController
{
    private RetrospectiveService $retro;

    public function __construct()
    {
        $this->retro = new RetrospectiveService();
    }

    /** GET /api/retrospective: anos em que o usuário zerou algum jogo. */
    public function years(Request $req): Response
    {
        return Response::json(['data' => $this->retro->years(Auth::require()['id'])]);
    }

    /** GET /api/retrospective/{year}: resumo do ano, Top 3 e candidatos. */
    public function show(Request $req, array $p): Response
    {
        return Response::json(['data' => $this->retro->show(Auth::require()['id'], (int) $p['year'])]);
    }

    /** PUT /api/retrospective/{year}/top {slots: [id1, id2, id3]} (nulls no fim deixam vagas) */
    public function setTop(Request $req, array $p): Response
    {
        return Response::json(['data' => $this->retro->setTop(Auth::require()['id'], (int) $p['year'], $req->input()['slots'] ?? null)]);
    }
}
