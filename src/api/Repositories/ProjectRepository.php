<?php

declare(strict_types=1);

namespace Api\Repositories;

use Api\Database\Database;
use PDO;

class ProjectRepository
{
    /**
     * Busca os projetos filtrados montando o SQL internamente com base no papel do usuário
     */
    public function getFilteredProjects(array $where, array $params, int $limit, int $offset, string $rol, array $orderFilter = []): array
    {
        $pdo = Database::getConnection();
        
        $sql = 'SELECT p.*, u.nombre AS responsable_nombre, u.apellidos AS responsable_apellidos 
                FROM proyectos p 
                JOIN usuarios u ON u.id = p.responsable_id';
                
        if ($rol === 'colaborador') {
            $sql .= ' JOIN proyecto_miembro pm ON pm.proyecto_id = p.id';
        }

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $orderBy = 'p.fecha_creacion DESC';
        $dateOrder = $orderFilter['date_order'] ?? '';
        $deadlineOrder = $orderFilter['deadline_order'] ?? '';

        if ($deadlineOrder === 'deadline_asc') {
            $orderBy = 'p.fecha_entrega ASC';
        } elseif ($deadlineOrder === 'deadline_desc') {
            $orderBy = 'p.fecha_entrega DESC';
        } elseif ($dateOrder === 'start_asc') {
            $orderBy = 'p.fecha_inicio ASC';
        } elseif ($dateOrder === 'start_desc') {
            $orderBy = 'p.fecha_inicio DESC';
        }

        $sql .= ' ORDER BY ' . $orderBy . ' LIMIT :limit OFFSET :offset';

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna a contagem total de registros para paginação
     */
    public function countTotal(array $where, array $params, string $rol): int
    {
        $pdo = Database::getConnection();
        
        $sql = 'SELECT COUNT(*) FROM proyectos p JOIN usuarios u ON u.id = p.responsable_id';
        if ($rol === 'colaborador') {
            $sql .= ' JOIN proyecto_miembro pm ON pm.proyecto_id = p.id';
        }
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    /**
     * Agrupa e retorna o total de projetos por estado (Mapeamento Rígido)
     */
    public function countByStatus(array $where, array $params, string $rol): array
    {
        $pdo = Database::getConnection();
        
        $sql = 'SELECT p.estado, COUNT(*) as qtd 
                FROM proyectos p 
                JOIN usuarios u ON u.id = p.responsable_id';
        if ($rol === 'colaborador') {
            $sql .= ' JOIN proyecto_miembro pm ON pm.proyecto_id = p.id';
        }
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' GROUP BY p.estado';

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->execute();

        $result = ['planificacion' => 0, 'en_curso' => 0, 'pausado' => 0, 'finalizado' => 0];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['estado']] = (int)$row['qtd'];
        }

        return $result;
    }

    public function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT p.*, u.nombre AS responsable_nombre, u.apellidos AS responsable_apellidos 
             FROM proyectos p 
             JOIN usuarios u ON u.id = p.responsable_id 
             WHERE p.id = :id'
        );
        $stmt->execute(['id' => $id]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ?: null;
    }

    public function insert(PDO $pdo, array $data): int
    {
        $stmt = $pdo->prepare(
            'INSERT INTO proyectos (nombre, descripcion, fecha_inicio, fecha_entrega, estado, responsable_id, porcentaje_avance)
             VALUES (:nombre, :descripcion, :fecha_inicio, :fecha_entrega, :estado, :responsable_id, 0)'
        );
        $stmt->execute([
            'nombre'         => $data['nombre'],
            'descripcion'    => $data['descripcion'],
            'fecha_inicio'   => $data['fecha_inicio'],
            'fecha_entrega'  => $data['fecha_entrega'],
            'estado'         => 'planificacion',
            'responsable_id' => (int)$data['responsable_id'],
        ]);

        return (int)$pdo->lastInsertId();
    }

    public function update(int $id, array $fields, array $params): void
    {
        $sql = 'UPDATE proyectos SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge($params, ['id' => $id]));
    }

    /**
     * Centraliza a deleção rígida em cascata dentro de uma mesma transação controlada
     */
    public function deleteCascade(PDO $pdo, int $id): void
    {
        $stmt1 = $pdo->prepare('DELETE FROM proyecto_miembro WHERE proyecto_id = :id');
        $stmt1->execute(['id' => $id]);

        $stmt2 = $pdo->prepare('DELETE FROM fases WHERE proyecto_id = :id');
        $stmt2->execute(['id' => $id]);

        $stmt3 = $pdo->prepare('DELETE FROM proyectos WHERE id = :id');
        $stmt3->execute(['id' => $id]);
    }

    public function getStatusAndPhaseCounts(int $projectId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT p.estado, COUNT(f.id) AS total, SUM(f.completada) AS completed
             FROM proyectos p LEFT JOIN fases f ON f.proyecto_id = p.id
             WHERE p.id = :id GROUP BY p.estado'
        );
        $stmt->execute(['id' => $projectId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateStatusAndProgress(int $projectId, int $percentage, string $status): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE proyectos SET porcentaje_avance = :percentage, estado = :status WHERE id = :id');
        $stmt->execute(['percentage' => $percentage, 'status' => $status, 'id' => $projectId]);
    }

    public function isMember(int $projectId, int $userId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM proyecto_miembro WHERE proyecto_id = :project_id AND usuario_id = :user_id');
        $stmt->execute(['project_id' => $projectId, 'user_id' => $userId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function isResponsible(int $projectId, int $userId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM proyectos WHERE id = :id AND responsable_id = :user_id');
        $stmt->execute(['id' => $projectId, 'user_id' => $userId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function insertMember(PDO $pdo, int $projectId, int $userId, string $role): void
    {
        $stmt = $pdo->prepare(
            'INSERT INTO proyecto_miembro (proyecto_id, usuario_id, fecha_add, rol_especifico)
             VALUES (:proyecto_id, :usuario_id, :fecha_add, :rol_especifico)'
        );
        $stmt->execute([
            'proyecto_id'    => $projectId,
            'usuario_id'     => $userId,
            'fecha_add'      => date('Y-m-d H:i:s'),
            'rol_especifico' => $role
        ]);
    }

    public function insertPhase(PDO $pdo, int $projectId, string $name, string $description, int $order): void
    {
        $stmt = $pdo->prepare(
            'INSERT INTO fases (nombre, descripcion, orden, completada, proyecto_id) VALUES (:nombre, :descripcion, :orden, 0, :proyecto_id)'
        );
        $stmt->execute([
            'nombre'      => htmlspecialchars_decode($name, ENT_QUOTES),
            'descripcion' => htmlspecialchars_decode($description, ENT_QUOTES),
            'orden'       => $order,
            'proyecto_id' => $projectId
        ]);
    }

    // ==========================================
    // MÉTODOS DE SUPORTE ÀS FASES (PHASES)
    // ==========================================

    public function findPhaseById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM fases WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function listPhasesByProject(int $projectId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM fases WHERE proyecto_id = :id ORDER BY orden ASC');
        $stmt->execute(['id' => $projectId]);
        return $stmt->fetchAll();
    }

    public function createPhase(int $projectId, array $data): int
    {
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

    public function updatePhase(int $id, array $fields, array $params): void
    {
        $sql = 'UPDATE fases SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge($params, ['id' => $id]));
    }

    public function getPhaseOrder(PDO $pdo, int $id): int
    {
        $stmtOrder = $pdo->prepare('SELECT orden FROM fases WHERE id = :id LIMIT 1');
        $stmtOrder->execute(['id' => $id]);
        return (int)$stmtOrder->fetchColumn();
    }

    public function deletePhaseOnly(PDO $pdo, int $id): void
    {
        $stmtDelete = $pdo->prepare('DELETE FROM fases WHERE id = :id');
        $stmtDelete->execute(['id' => $id]);
    }

    public function reorderPhasesAfterDelete(PDO $pdo, int $projectId, int $deletedOrder): void
    {
        $stmtRenumber = $pdo->prepare('UPDATE fases SET orden = orden - 1 WHERE proyecto_id = :projectId AND orden > :deletedOrder');
        $stmtRenumber->execute(['projectId' => $projectId, 'deletedOrder' => $deletedOrder]);
    }

    public function getProjectIdByPhase(int $phaseId): ?int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT proyecto_id FROM fases WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $phaseId]);
        $result = $stmt->fetch();
        return $result ? (int)$result['proyecto_id'] : null;
    }

    public function getProjectStatus(int $projectId): ?string
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT estado FROM proyectos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $projectId]);
        $project = $stmt->fetch();
        return $project ? $project['estado'] : null;
    }
}