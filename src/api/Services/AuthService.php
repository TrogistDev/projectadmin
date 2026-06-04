<?php

declare(strict_types=1);

namespace Api\Services;

use Api\Database\Database;

class AuthService
{
    public function authenticate(string $correo, string $password): array|false
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, nombre, apellidos, correo, contrasena, rol FROM usuarios WHERE correo = :correo');
        $stmt->execute(['correo' => $correo]);
        $user = $stmt->fetch();

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
