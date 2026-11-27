<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Auth, Request, Response};
use App\Services\NamesService;

final class NamesController
{
    private NamesService $names;

    public function __construct()
    {
        $this->names = new NamesService();
    }

    public function index(Request $req, array $p): Response
    {
        return Response::json(['data' => $this->names->list(Auth::require()['id'], (string) $p['kind'])]);
    }

    public function merge(Request $req, array $p): Response
    {
        $in = $req->input();
        return Response::json(['data' => $this->names->merge(Auth::require()['id'], (string) $p['kind'], (array) ($in['from'] ?? []), $in['to'] ?? '')]);
    }
}
