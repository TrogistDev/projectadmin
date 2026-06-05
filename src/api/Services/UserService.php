<?php

declare(strict_types=1);

namespace Api\Services;

use Api\Database\Database;
use InvalidArgumentException;
use PDO;

class UserService
{
    public function list(array $filter = []): array
    {
        $pdo = Database::getConnection();

        $limit = isset($filter['limit']) && is_numeric($filter['limit']) ? (int)$filter['limit'] : 50;
        $page = isset($filter['page']) && is_numeric($filter['page']) && (int)$filter['page'] > 0 ? (int)$filter['page'] : 1;
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];

        if (!empty($filter['search'])) {
            $where[] = '(nombre LIKE :search OR apellidos LIKE :search OR correo LIKE :search)';
            $params['search'] = '%' . $filter['search'] . '%';
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

        return $stmt->fetchAll();
    }

    public function create(array $data): array
    {
        if (empty($data['nombre']) || empty($data['correo']) || empty($data['rol']) || empty($data['contrasena'])) {
            throw new InvalidArgumentException('Todos os campos obrigatórios devem ser preenchidos.');
        }

        if (!filter_var($data['correo'], FILTER_VALIDATE_EMAIL) || !preg_match('/\\.[a-zA-Z]{2,}$/', $data['correo'])) {
            throw new InvalidArgumentException('Informe um e-mail válido (ex: usuario@empresa.com).');
        }

        if (strlen($data['contrasena']) < 8) {
            throw new InvalidArgumentException('A senha deve ter no mínimo 8 caracteres.');
        }

        $db = Database::getConnection();

        $stmtCheck = $db->prepare("SELECT id FROM usuarios WHERE correo = :correo LIMIT 1");
        $stmtCheck->execute([':correo' => $data['correo']]);
        if ($stmtCheck->fetch()) {
            throw new InvalidArgumentException('Este e-mail já está cadastrado no sistema.');
        }

        $passwordHash = password_hash($data['contrasena'], PASSWORD_BCRYPT);

        $sql = "INSERT INTO usuarios (nombre, apellidos, correo, contrasena, rol, departamento) 
                VALUES (:nombre, :apellidos, :correo, :contrasena, :rol, :departamento)";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':nombre'     => $data['nombre'],
            ':apellidos'  => $data['apellidos'] ?? '',
            ':correo'     => $data['correo'],
            ':contrasena' => $passwordHash,
            ':rol'        => $data['rol'],
            ':departamento' => $data['departamento'] ?? null,
        ]);

        $newId = (int)$db->lastInsertId();
        
        return [
            'id'        => $newId,
            'nombre'    => $data['nombre'],
            'apellidos' => $data['apellidos'] ?? '',
            'correo'    => $data['correo'],
            'rol'       => $data['rol'],
            'departamento' => $data['departamento'] ?? null
        ];
    }

    public function update(int $id, array $data): void
    {
        $db = Database::getConnection();
        
        $fields = [];
        $params = ['id' => $id];

        if (isset($data['nombre'])) {
            $fields[] = 'nombre = :nombre';
            $params['nombre'] = $data['nombre'];
        }

        if (isset($data['apellidos'])) {
            $fields[] = 'apellidos = :apellidos';
            $params['apellidos'] = $data['apellidos'];
        }

        if (isset($data['rol'])) {
            $fields[] = 'rol = :rol';
            $params['rol'] = $data['rol'];
        }

        if (isset($data['departamento'])) {
            $fields[] = 'departamento = :departamento';
            $params['departamento'] = $data['departamento'];
        }

        if (isset($data['contrasena'])) {
            if (strlen($data['contrasena']) < 8) {
                throw new InvalidArgumentException('A senha deve ter no mínimo 8 caracteres.');
            }
            $fields[] = 'contrasena = :contrasena';
            $params['contrasena'] = password_hash($data['contrasena'], PASSWORD_BCRYPT);
        }

        if (empty($fields)) {
            return;
        }

        $sql = 'UPDATE usuarios SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
    }

    public function delete(int $id): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('DELETE FROM usuarios WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
