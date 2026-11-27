<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Auth, Config, Request, Response};
use App\Services\{BookService, DemoCatalog};

final class BookController
{
    public function show(Request $req): Response
    {
        $user = Auth::require();
        return Response::json(['data' => (new BookService())->forUser($user['id'], $user['username'])]);
    }

    /** O mesmo livro sobre o catálogo fictício (não exige login). */
    public function demo(Request $req): Response
    {
        $games = DemoCatalog::games((string) Config::get('base_path', ''));
        usort($games, fn($a, $b) => [$a['finished_at'], $a['id']] <=> [$b['finished_at'], $b['id']]);
        return Response::json(['data' => (new BookService())->fromGames($games, DemoCatalog::notes(), 'Jogador Exemplo')]);
    }
}
