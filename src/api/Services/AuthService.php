<?php

declare(strict_types=1);

namespace Api\Services;

use Api\Repositories\UserRepository;

class AuthService
{
    private UserRepository $userRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
    }

    public function authenticate(string $correo, string $password): array|false
    {
        $correo = trim(mb_strtolower($correo));
        $user = $this->userRepository->findByCorreo($correo);

        if (!$user) {
            return false;
        }


        if (!password_verify($password, $user['contrasena'])) {
            return false;
        }

        unset($user['contrasena']);

        return $user;
    }
}