<?php

declare(strict_types=1);

namespace Api\Services;

use Api\Database\Database;

class PhaseService
{
    public function listByProject(int $projectId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM fases WHERE proyecto_id = :id ORDER BY orden ASC');
        $stmt->execute(['id' => $projectId]);

        return $stmt->fetchAll();
    }

    public function create(int $projectId, array $data): int
    {
        $this->validatePhaseData($data);

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO fases (nombre, descripcion, orden, completada, proyecto_id)
             VALUES (:nombre, :descripcion, :orden, 0, :proyecto_id)'
        );
        $stmt->execute([
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? '',
            'orden' => $data['orden'] ?? 1,
            'proyecto_id' => $projectId,
        ]);

        $phaseId = (int)$pdo->lastInsertId();
        (new ProjectService())->recalculateStatus($projectId);

        return $phaseId;
    }

    public function update(int $id, array $data): void
    {
        $pdo = Database::getConnection();
        $updateFields = [];
        $params = ['id' => $id];

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

        $sql = 'UPDATE fases SET ' . implode(', ', $updateFields) . ' WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $projectId = $this->getProjectId($id);

        if ($projectId !== null) {
            (new ProjectService())->recalculateStatus($projectId);
        }
    }

    public function delete(int $id): void
    {
        $projectId = $this->getProjectId($id);
        if ($this->isProjectFinalized($projectId)) {
            throw new \RuntimeException('No se puede eliminar fases de un proyecto finalizado.');
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM fases WHERE id = :id');
        $stmt->execute(['id' => $id]);

        if ($projectId !== null) {
            (new ProjectService())->recalculateStatus($projectId);
        }
    }

    private function validatePhaseData(array $data): void
    {
        if (empty($data['nombre'])) {
            throw new \InvalidArgumentException('El nombre de la fase es obligatorio.');
        }
    }

    public function getProjectId(int $phaseId): ?int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT proyecto_id FROM fases WHERE id = :id');
        $stmt->execute(['id' => $phaseId]);
        $result = $stmt->fetch();

        return $result ? (int)$result['proyecto_id'] : null;
    }

    private function isProjectFinalized(?int $projectId): bool
    {
        if ($projectId === null) {
            return false;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT estado FROM proyectos WHERE id = :id');
        $stmt->execute(['id' => $projectId]);
        $project = $stmt->fetch();

        return $project && $project['estado'] === 'finalizado';
    }
}
