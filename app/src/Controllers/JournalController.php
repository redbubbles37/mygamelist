<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Auth, Config, Request, Response};
use App\Services\{DemoCatalog, JournalService};

/** Diário: a linha do tempo de tudo (fila, começados, zerados, dropados, notas, screenshots) e os registros livres do dia. */
final class JournalController
{
    private JournalService $journal;

    public function __construct()
    {
        $this->journal = new JournalService();
    }

    public function index(Request $req): Response
    {
        return Response::json(['data' => $this->journal->forUser(Auth::require()['id'])]);
    }

    /** O mesmo diário sobre o catálogo fictício (página de exemplo; não exige login). */
    public function demo(Request $req): Response
    {
        return Response::json(['data' => DemoCatalog::journal((string) Config::get('base_path', ''))]);
    }

    public function store(Request $req): Response
    {
        return Response::json(['data' => $this->journal->addEntry(Auth::require()['id'], $req->input())], 201);
    }

    public function update(Request $req, array $p): Response
    {
        return Response::json(['data' => $this->journal->updateEntry(Auth::require()['id'], (int) $p['id'], $req->input())]);
    }

    public function destroy(Request $req, array $p): Response
    {
        $this->journal->deleteEntry(Auth::require()['id'], (int) $p['id']);
        return Response::noContent();
    }
}
