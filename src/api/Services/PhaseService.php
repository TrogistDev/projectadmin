<?php

declare(strict_types=1);

namespace Api\Services;

use Api\Database\Database;
use InvalidArgumentException;
use PDO;

/**
 * Service rígido para gerenciamento e manipulação de fases de projetos
 */
class PhaseService
{
    /**
     * Retorna uma fase específica por ID para sanar a validação do Controller
     */
    public function find(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM fases WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    /**
     * Lista todas as fases vinculadas a um projeto específico ordenadas
     */
    public function listByProject(int $projectId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM fases WHERE proyecto_id = :id ORDER BY orden ASC');
        $stmt->execute(['id' => $projectId]);

        return $stmt->fetchAll();
    }

    /**
     * Cria uma nova fase de forma defensiva e retorna o ID inserido
     */
    public function create(int $projectId, array $data): int
    {
        $this->validatePhaseData($data);

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO fases (nombre, descripcion, orden, completada, proyecto_id)
             VALUES (:nombre, :descripcion, :orden, 0, :proyecto_id)'
        );
        $stmt->execute([
            'nombre'      => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? '',
            'orden'       => $data['orden'] ?? 1,
            'proyecto_id' => $projectId,
        ]);

        return (int)$pdo->lastInsertId();
    }

    /**
     * Atualiza dinamicamente as colunas enviadas mitigando mass assignment indesejado
     */
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
    }

    /**
     * Remove uma fase do banco através do ID
     */
    public function delete(int $id): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM fases WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /**
     * Recupera o ID do projeto associado de forma performática
     */
    public function getProjectId(int $phaseId): ?int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT proyecto_id FROM fases WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $phaseId]);
        $result = $stmt->fetch();

        return $result ? (int)$result['proyecto_id'] : null;
    }

    /**
     * Verifica de forma rígida se o estado do projeto está marcado como concluído
     */
    public function isProjectFinalized(?int $projectId): bool
    {
        if ($projectId === null) {
            return false;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT estado FROM proyectos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $projectId]);
        $project = $stmt->fetch();

        return $project && $project['estado'] === 'finalizado';
    }

    /**
     * Validação interna defensiva para integridade de dados das fases
     */
    private function validatePhaseData(array $data): void
    {
        if (empty($data['nombre']) || trim((string)$data['nombre']) === '') {
            throw new InvalidArgumentException('El nombre de la fase es obligatorio.');
        }
    }
}