<?php

declare(strict_types=1);

namespace Api\Services;

use Api\Database\Database;
use Api\Repositories\ProjectRepository;
use Api\Utils\Validator;
use PDO;
use Throwable;

/**
 * Service rígido para gerenciamento e manipulação de fases de projetos
 */
class PhaseService
{
    private ProjectRepository $repository;

    public function __construct()
    {
        $this->repository = new ProjectRepository();
    }

    public function find(int $id): ?array
    {
        return $this->repository->findPhaseById($id);
    }

 
    public function listByProject(int $projectId): array
    {
        return $this->repository->listPhasesByProject($projectId);
    }

  
    public function create(int $projectId, array $data): int
    {
        Validator::assertPhaseData($data);
        return $this->repository->createPhase($projectId, $data);
    }

   
    public function update(int $id, array $data): void
    {
        $updateFields = [];
        $params = [];

        if (isset($data['nombre'])) {
            $updateFields[] = 'nombre = :nombre';
            $params['nombre'] = $data['nombre'];
        }

        if (isset($data['descripcion'])) {
            $updateFields[] = 'descripcion = :descripcion';
            $params['descripcion'] = $data['descripcion'];
        }

        if (isset($data['orden'])) {
            $updateFields[] = 'orden = :orden';
            $params['orden'] = $data['orden'];
        }

        if (isset($data['completada'])) {
            $updateFields[] = 'completada = :completada';
            $params['completada'] = $data['completada'] ? 1 : 0;
            $updateFields[] = 'fecha_completado = :fecha_completado';
            $params['fecha_completado'] = $data['completada'] ? date('Y-m-d H:i:s') : null;
        }

        if (empty($updateFields)) {
            return;
        }

        $this->repository->updatePhase($id, $updateFields, $params);
    }

  
    public function delete(int $id): void
    {
        Database::transaction(function (PDO $pdo) use ($id) {
            $projectId = $this->getProjectId($id);
            $deletedOrder = $this->repository->getPhaseOrder($pdo, $id);

            $this->repository->deletePhaseOnly($pdo, $id);

            if ($projectId !== null && $deletedOrder > 0) {
                $this->repository->reorderPhasesAfterDelete($pdo, $projectId, $deletedOrder);
            }
        });
    }

  
    public function getProjectId(int $phaseId): ?int
    {
        return $this->repository->getProjectIdByPhase($phaseId);
    }

   
    public function isProjectFinalized(?int $projectId): bool
    {
        if ($projectId === null) {
            return false;
        }

        $status = $this->repository->getProjectStatus($projectId);
        return $status === 'finalizado';
    }
}