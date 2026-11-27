<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Auth, HttpException, Request, Response};
use App\Services\AccountService;

final class AccountController
{
    private AccountService $account;

    public function __construct()
    {
        $this->account = new AccountService();
    }

    public function show(Request $req): Response
    {
        return Response::json($this->account->overview(Auth::require()['id']));
    }

    public function username(Request $req): Response
    {
        return Response::json($this->account->changeUsername(Auth::require()['id'], (string) ($req->input()['username'] ?? '')));
    }

    /** Troca a imagem do usuário: arquivo "avatar", "preset" (um ícone) ou nada (volta para a inicial). */
    public function avatar(Request $req): Response
    {
        $svc = new \App\Services\AvatarService();
        $choice = $svc->prepare($req->file('avatar'), $req->input()['preset'] ?? null);
        return Response::json(['avatar' => $svc->save(Auth::require()['id'], $choice)]);
    }

    /** GET /api/account/export?format=json|csv|dropped-csv: baixa um arquivo com os seus dados. */
    public function export(Request $req): Response
    {
        $id = Auth::require()['id'];
        $stamp = date('Y-m-d');
        return match ($req->query['format'] ?? 'json') {
            'csv' => Response::download($this->account->exportCsv($id, 'games'), "mygamelist-jogos-$stamp.csv", 'text/csv; charset=utf-8'),
            'dropped-csv' => Response::download($this->account->exportCsv($id, 'dropped'), "mygamelist-dropados-$stamp.csv", 'text/csv; charset=utf-8'),
            'json' => Response::download(
                json_encode($this->account->exportAll($id), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
                "mygamelist-tudo-$stamp.json", 'application/json; charset=utf-8'
            ),
            default => throw new HttpException(422, 'Formato inválido.'),
        };
    }

    /** POST (e não DELETE) porque leva a confirmação no corpo. */
    public function delete(Request $req): Response
    {
        $in = $req->input();
        $this->account->deleteAccount(Auth::require()['id'], (string) ($in['confirm'] ?? ''));
        Auth::logout();
        return Response::json(['ok' => true]);
    }
}
