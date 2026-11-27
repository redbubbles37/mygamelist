<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Auth, Config, Request, Response};
use App\Services\{DemoCatalog, TimeService};

final class TimeController
{
    private TimeService $time;

    public function __construct()
    {
        $this->time = new TimeService();
    }

    public function snapshot(Request $req): Response
    {
        return Response::json(['data' => $this->time->snapshot(Auth::require()['id'], (string) ($req->query['date'] ?? date('Y-m-d')))]);
    }

    public function onThisDay(Request $req): Response
    {
        return Response::json(['data' => $this->time->onThisDay(Auth::require()['id'])]);
    }

    /** Mesma máquina do tempo, sobre os jogos fictícios (página de exemplo; não exige login). */
    public function demo(Request $req): Response
    {
        $games = DemoCatalog::games((string) Config::get('base_path', ''));
        return Response::json(['data' => $this->time->snapshotFrom($games, (string) ($req->query['date'] ?? '2026-10-15'), '2026-10-15')]);
    }
}
