<?php

declare(strict_types=1);

namespace Api\Services;

use Api\Database\Database;

class UserService
{
    public function list(array $filter = []): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, nombre, apellidos, correo, rol, departamento FROM usuarios');
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
