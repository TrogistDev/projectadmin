<?php

declare(strict_types=1);

namespace Api\Services;

use Api\Database\Database;
use Api\Repositories\MemberRepository;
use InvalidArgumentException;

class MemberService
{
    private MemberRepository $repository;

    public function __construct()
    {
        $this->repository = new MemberRepository();
    }

    public function listByProject(int $projectId): array
    {
        return $this->repository->findByProject($projectId);
    }

    public function add(int $projectId, array $data): void
    {
        if (empty($data['usuario_id']) || empty($data['rol_especifico'])) {
            throw new InvalidArgumentException('usuario_id y rol_especifico son obligatorios.');
        }

        $userId = (int)$data['usuario_id'];
        $role = trim((string)$data['rol_especifico']);

        $pdo = Database::getConnection();
        $this->repository->insert($pdo, $projectId, $userId, $role);
    }

    public function remove(int $projectId, int $userId): void
    {
        $this->repository->delete($projectId, $userId);
    }
}