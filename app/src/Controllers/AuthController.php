<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Auth, Csrf, Database, HttpException, Request, Response, Str};
use App\Services\XpService;

final class AuthController
{
    public function csrf(Request $req): Response
    {
        return Response::json(['csrf_token' => Csrf::token()]);
    }

    /** Programa local sem senha: criar um usuário pede só o nome (e, se quiser, a imagem). */
    public function register(Request $req): Response
    {
        $in = $req->input();
        $username = Str::clean($in['username'] ?? '');
        if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $username)) {
            throw new HttpException(422, 'Dados inválidos.', ['username' => '3 a 30 caracteres: letras, números e _.']);
        }

        // a imagem é opcional e conferida antes de criar a conta
        $avatars = new \App\Services\AvatarService();
        $avatar = $avatars->prepare($req->file('avatar'), $in['preset'] ?? null);

        try {
            // password_hash não é mais usado (a coluna continua no banco)
            Database::query("INSERT INTO users (username, password_hash) VALUES (?, '')", [$username]);
        } catch (\PDOException $ex) {
            if (Database::isDuplicate($ex)) {
                throw new HttpException(409, 'Cadastro indisponível.', ['username' => 'Esse nome já está em uso neste computador.']);
            }
            throw $ex;
        }

        $userId = Database::insertId();
        if ($avatar !== null) {
            $avatars->save($userId, $avatar);
        }
        Auth::login($userId);
        return Response::json(['user' => $this->payload(), 'csrf_token' => Csrf::token()], 201);
    }

    /** Entrar é escolher o usuário na tela "Quem vai jogar?". */
    public function login(Request $req): Response
    {
        $username = Str::clean($req->input()['login'] ?? '');
        $id = Database::value('SELECT id FROM users WHERE username = ?', [$username]);
        if ($id === null) {
            throw new HttpException(404, 'Usuário não encontrado.');
        }
        Auth::login((int) $id);
        return Response::json(['user' => $this->payload(), 'csrf_token' => Csrf::token()]);
    }

    public function logout(Request $req): Response
    {
        Auth::logout();
        return Response::noContent();
    }

    public function me(Request $req): Response
    {
        return Response::json(['user' => $this->payload(), 'csrf_token' => Csrf::token()]);
    }

    private function payload(): array
    {
        $u = Auth::user();
        return [
            'id'       => $u['id'],
            'username' => $u['username'],
            'xp'       => (new XpService())->progress($u['total_xp']),
        ];
    }
}
