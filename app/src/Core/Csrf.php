<?php
declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        Auth::start();
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    /** Exigido em toda requisição que não seja GET: header X-CSRF-Token. */
    public static function verify(Request $req): void
    {
        $sent = $req->header('X-CSRF-Token') ?? '';
        if (!hash_equals(self::token(), $sent)) {
            throw new HttpException(419, 'Token CSRF inválido ou ausente.');
        }
    }
}
