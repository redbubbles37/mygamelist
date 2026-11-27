<?php
declare(strict_types=1);

namespace App\Core;

final class Auth
{
    private static ?array $user = null;

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        session_name('mgl_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public static function login(int $userId): void
    {
        self::start();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['sv'] = (int) Database::value('SELECT session_version FROM users WHERE id = ?', [$userId]);
        self::$user = null;
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        session_destroy();
        self::$user = null;
    }

    public static function user(): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }
        self::start();
        $id = $_SESSION['user_id'] ?? null;
        if (!$id) {
            return null;
        }
        $row = Database::one(
            'SELECT id, username, total_xp, level, theme, theme_fx, profile_public, session_version, created_at, calm_mode, catalog_layout, dashboard_layout, avatar FROM users WHERE id = ?',
            [$id]
        );
        if ($row === null) {
            return null;
        }
        // Se a versão da sessão mudou no banco, esta sessão deixa de valer.
        // Sessões antigas, sem versão, são adotadas na primeira requisição.
        if (!isset($_SESSION['sv'])) {
            $_SESSION['sv'] = (int) $row['session_version'];
        } elseif ((int) $_SESSION['sv'] !== (int) $row['session_version']) {
            $_SESSION = [];
            session_destroy();
            return null;
        }
        return self::$user = $row;
    }

    public static function require(): array
    {
        $user = self::user();
        if ($user === null) {
            throw new HttpException(401, 'Autenticação necessária.');
        }
        return $user;
    }
}
