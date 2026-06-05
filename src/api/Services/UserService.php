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

        $limit = (int)($filter['limit'] ?? 50);
        $page = max(1, (int)($filter['page'] ?? 1));
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
        $this->validateUserData($data);

        $db = Database::getConnection();

        if ($this->emailExists($db, $data['correo'])) {
            throw new InvalidArgumentException('Este e-mail já está cadastrado no sistema.');
        }

        $passwordHash = password_hash($data['contrasena'], PASSWORD_BCRYPT);

        $stmt = $db->prepare(
            'INSERT INTO usuarios (nombre, apellidos, correo, contrasena, rol, departamento) 
             VALUES (:nombre, :apellidos, :correo, :contrasena, :rol, :departamento)'
        );
        $stmt->execute([
            ':nombre'     => $data['nombre'],
            ':apellidos'  => $data['apellidos'] ?? '',
            ':correo'     => $data['correo'],
            ':contrasena' => $passwordHash,
            ':rol'        => $data['rol'],
            ':departamento' => $data['departamento'] ?? null,
        ]);

        return [
            'id'        => (int)$db->lastInsertId(),
            'nombre'    => $data['nombre'],
            'apellidos' => $data['apellidos'] ?? '',
            'correo'    => $data['correo'],
            'rol'       => $data['rol'],
            'departamento' => $data['departamento'] ?? null
        ];
    }

    public function update(int $id, array $data): void
    {
        $this->validateUpdateData($data);

        $db = Database::getConnection();
        $updates = $this->buildUpdateFields($data);

        if (empty($updates['fields'])) {
            return;
        }

        $sql = 'UPDATE usuarios SET ' . implode(', ', $updates['fields']) . ' WHERE id = :id';
        $stmt = $db->prepare($sql);
        $stmt->execute(array_merge($updates['params'], ['id' => $id]));
    }

    public function delete(int $id): void
    {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM proyectos WHERE responsable_id = :id');
        $stmt->execute(['id' => $id]);
        if ((int)$stmt->fetchColumn() > 0) {
            throw new InvalidArgumentException('Não é possível excluir: usuário é responsável por projetos.');
        }

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM proyecto_miembro WHERE usuario_id = :id');
        $stmt->execute(['id' => $id]);
        if ((int)$stmt->fetchColumn() > 0) {
            throw new InvalidArgumentException('Não é possível excluir: usuário está em projetos.');
        }

        $stmt = $pdo->prepare('DELETE FROM usuarios WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private function validateUserData(array $data): void
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
    }

    private function validateUpdateData(array $data): void
    {
        if (isset($data['contrasena']) && strlen($data['contrasena']) < 8) {
            throw new InvalidArgumentException('A senha deve ter no mínimo 8 caracteres.');
        }
    }

    private function emailExists(PDO $db, string $email): bool
    {
        $stmt = $db->prepare("SELECT id FROM usuarios WHERE correo = :correo LIMIT 1");
        $stmt->execute([':correo' => $email]);
        return (bool)$stmt->fetch();
    }

    private function buildUpdateFields(array $data): array
    {
        $fields = [];
        $params = ['id' => null];

        $mapping = [
            'nombre' => 'nombre',
            'apellidos' => 'apellidos',
            'rol' => 'rol',
            'departamento' => 'departamento',
            'contrasena' => function($v) {
                return password_hash($v, PASSWORD_BCRYPT);
            }
        ];

        foreach ($mapping as $field => $handler) {
            if (isset($data[$field])) {
                $value = is_callable($handler) ? $handler($data[$field]) : $data[$field];
                $fields[] = "$field = :$field";
                $params[$field] = $value;
            }
        }

        if (empty($fields)) {
            return ['fields' => [], 'params' => []];
        }

        $params['id'] = null;

        return ['fields' => $fields, 'params' => array_filter($params, fn($k) => $k !== 'id')];
    }
}