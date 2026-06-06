<?php

declare(strict_types=1);

namespace Api\Services;

use Api\Repositories\UserRepository;
use InvalidArgumentException;

class UserService
{
    private UserRepository $repository;

    public function __construct()
    {
        $this->repository = new UserRepository();
    }

    public function list(array $filter = []): array
    {
        $limit = (int)($filter['limit'] ?? 50);
        $page = max(1, (int)($filter['page'] ?? 1));
        $offset = ($page - 1) * $limit;

        $search = !empty($filter['search']) ? (string)$filter['search'] : null;

        
        return $this->repository->getFilteredUsers($search, $limit, $offset);
    }

    public function create(array $data): array
    {
        $this->validateUserData($data);

        if ($this->repository->emailExists($data['correo'])) {
            throw new InvalidArgumentException('Este e-mail já está cadastrado no sistema.');
        }

        $passwordHash = password_hash($data['contrasena'], PASSWORD_BCRYPT);
        $userId = $this->repository->insert($data, $passwordHash);

        return [
            'id'           => $userId,
            'nombre'       => $data['nombre'],
            'apellidos'    => $data['apellidos'] ?? '',
            'correo'       => $data['correo'],
            'rol'          => $data['rol'],
            'departamento' => $data['departamento'] ?? null
        ];
    }

    public function update(int $id, array $data): void
    {
        $this->validateUpdateData($data);

        $updates = $this->buildUpdateFields($data);
        if (empty($updates['fields'])) {
            return;
        }

        $this->repository->update($id, $updates['fields'], $updates['params']);
    }

    public function delete(int $id): void
    {
        if ($this->repository->countProjectsByResponsible($id) > 0) {
            throw new InvalidArgumentException('Não é possível excluir: usuário é responsável por projetos.');
        }

        if ($this->repository->countProjectMemberships($id) > 0) {
            throw new InvalidArgumentException('Não é possível excluir: usuário está em projetos.');
        }

        $this->repository->delete($id);
    }

    // Métodos Defensivos Privados (Processamento de Dados e Validações Rígidas)

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

    private function buildUpdateFields(array $data): array
    {
        $fields = [];
        $params = [];

        $mapping = [
            'nombre'       => 'nombre',
            'apellidos'    => 'apellidos',
            'rol'          => 'rol',
            'departamento' => 'departamento',
            'contrasena'   => function($v) {
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

        return ['fields' => $fields, 'params' => $params];
    }
}