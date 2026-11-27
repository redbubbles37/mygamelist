<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Auth, Request, Response};
use App\Services\GoalService;

final class GoalController
{
    private GoalService $goals;

    public function __construct()
    {
        $this->goals = new GoalService();
    }

    /** GET /api/goals?year=AAAA */
    public function show(Request $req): Response
    {
        $year = filter_var($req->query['year'] ?? null, FILTER_VALIDATE_INT);
        $year = ($year !== false && $year >= 2000 && $year <= 2100) ? $year : (int) date('Y');
        return Response::json($this->goals->show(Auth::require()['id'], $year));
    }

    /** PUT /api/goals/{year} {games, hours}: vazio/nulo remove a meta daquela métrica. */
    public function set(Request $req, array $p): Response
    {
        $in = $req->input();
        return Response::json($this->goals->set(Auth::require()['id'], (int) $p['year'], $in['games'] ?? null, $in['hours'] ?? null));
    }
}
