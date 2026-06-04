<?php

declare(strict_types=1);

namespace Api\Controllers;

use Api\Services\AuthService;
use Api\Utils\Auth;
use Api\Utils\Request;
use Api\Utils\Response;

class AuthController
{
    private AuthService $service;

    public function __construct()
    {
        $this->service = new AuthService();
    }

    public function login(Request $request): void
    {
        $data = $request->getBody();

        if (empty($data['correo']) || empty($data['contrasena'])) {
            Response::error('Correo y contraseña son obligatorios.', 422);
        }

        $user = $this->service->authenticate($data['correo'], $data['contrasena']);

        if (!$user) {
            Response::error('Credenciales inválidas.', 401);
        }

        Auth::login($user);

        Response::json([
            'user' => $user,
            'token' => session_id(),
        ]);
    }

    public function logout(): void
    {
        Auth::logout();
        Response::json(['message' => 'Cierre de sesión realizado.']);
    }
}
