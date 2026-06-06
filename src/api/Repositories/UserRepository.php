<?php

declare(strict_types=1);

namespace Api\Repositories;

use Api\Database\Database;
use PDO;

class UserRepository
{
    public function getFilteredUsers(?string $search, int $limit, int $offset): array
    {
        $pdo = Database::getConnection();
        $where = [];
        $params = [];

        if (!empty($search)) {
            $where[] = '(nombre LIKE :search OR apellidos LIKE :search OR correo LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $sql = 'SELECT id, nombre, apellidos, correo, rol, departamento FROM usuarios';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY nombre ASC LIMIT :limit OFFSET :offset';

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function emailExists(string $email): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE correo = :correo LIMIT 1');
        $stmt->execute(['correo' => $email]);
        return (bool)$stmt->fetch();
    }

    public function insert(array $data, string $passwordHash): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO usuarios (nombre, apellidos, correo, contrasena, rol, departamento) 
             VALUES (:nombre, :apellidos, :correo, :contrasena, :rol, :departamento)'
        );
        $stmt->execute([
            ':nombre'       => $data['nombre'],
            ':apellidos'    => $data['apellidos'] ?? '',
            ':correo'       => $data['correo'],
            ':contrasena'   => $passwordHash,
            ':rol'          => $data['rol'],
            ':departamento' => $data['departamento'] ?? null,
        ]);

        return (int)$pdo->lastInsertId();
    }

    public function update(int $id, array $fields, array $params): void
    {
        $pdo = Database::getConnection();
        $sql = 'UPDATE usuarios SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge($params, ['id' => $id]));
    }

    public function countProjectsByResponsible(int $id): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM proyectos WHERE responsable_id = :id');
        $stmt->execute(['id' => $id]);
        return (int)$stmt->fetchColumn();
    }

    public function countProjectMemberships(int $id): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM proyecto_miembro WHERE usuario_id = :id');
        $stmt->execute(['id' => $id]);
        return (int)$stmt->fetchColumn();
    }

    public function delete(int $id): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM usuarios WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
    public function findByCorreo(string $correo): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, nombre, apellidos, correo, contrasena, rol FROM usuarios WHERE correo = :correo');
        $stmt->execute(['correo' => $correo]);
        
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $user ?: null;
    }
}