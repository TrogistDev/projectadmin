<?php

declare(strict_types=1);

namespace Api\Utils;

use Api\Utils\Response;

class Auth
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function login(array $user): void
    {
        self::start();
        $userClean = $user;
        unset($userClean['contrasena']);
        $_SESSION['user'] = $userClean;
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function user(): ?array
    {
        self::start();

        return $_SESSION['user'] ?? null;
    }

    public static function requireLogin(): void
    {
        if (self::user() === null) {
            Response::error('Autenticación requerida.', 401);
        }
    }

    public static function currentUserId(): ?int
    {
        $user = self::user();
        return $user ? (int)$user['id'] : null;
    }

    public static function currentUserRole(): ?string
    {
        $user = self::user();
        return $user['rol'] ?? null;
    }
}
