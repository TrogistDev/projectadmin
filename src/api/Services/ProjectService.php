<?php

declare(strict_types=1);

namespace Api\Services;

use Api\Database\Database;
use PDO;
use Throwable;
use InvalidArgumentException;

class ProjectService
{
    public function list(array $filter = [], array $user = []): array
    {
        $pdo = Database::getConnection();

        if (($user['rol'] ?? '') === 'colaborador') {
            $stmt = $pdo->prepare(
                'SELECT p.*, u.nombre AS responsable_nombre, u.apellidos AS responsable_apellidos
                 FROM proyectos p
                 JOIN usuarios u ON u.id = p.responsable_id
                 JOIN proyecto_miembro pm ON pm.proyecto_id = p.id
                 WHERE pm.usuario_id = :user_id'
            );
            $stmt->execute(['user_id' => $user['id']]);
        } else {
            $stmt = $pdo->prepare(
                'SELECT p.*, u.nombre AS responsable_nombre, u.apellidos AS responsable_apellidos
                 FROM proyectos p
                 JOIN usuarios u ON u.id = p.responsable_id'
            );
            $stmt->execute();
        }

        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($filter['search'])) {
            $search = mb_strtolower($filter['search']);
            $projects = array_filter($projects, fn($project) => mb_stripos($project['nombre'], $search) !== false);
        }

        if (!empty($filter['estado'])) {
            $projects = array_filter($projects, fn($project) => $project['estado'] === $filter['estado']);
        }

        return array_values($projects);
    }

    public function find(int $id): array|false
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT p.*, u.nombre AS responsable_nombre, u.apellidos AS responsable_apellidos FROM proyectos p JOIN usuarios u ON u.id = p.responsable_id WHERE p.id = :id');
        $stmt->execute(['id' => $id]);
        
        $projectData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$projectData) {
            return false;
        }

        $phases = $this->getPhases($id);
        $members = $this->getMembers($id);

        $projectData['phases'] = $phases;
        $projectData['fases'] = $phases;

        $projectData['members'] = $members;
        $projectData['miembros'] = $members;

        return $projectData;
    }

    public function create(array $data): int
    {
        $this->validateProjectData($data);
        $this->ensureProjectManager((int)$data['responsable_id']);

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO proyectos (nombre, descripcion, fecha_inicio, fecha_entrega, estado, responsable_id, porcentaje_avance)
                 VALUES (:nombre, :descripcion, :fecha_inicio, :fecha_entrega, :estado, :responsable_id, 0)'
            );

            $stmt->execute([
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'],
                'fecha_inicio' => $data['fecha_inicio'],
                'fecha_entrega' => $data['fecha_entrega'],
                'estado' => 'planificacion',
                'responsable_id' => (int)$data['responsable_id'],
            ]);

            $projectId = (int)$pdo->lastInsertId();

            // RÍGIDO: Insere automaticamente o Jefe Responsável na equipe do projeto
            $stmtManager = $pdo->prepare(
                'INSERT INTO proyecto_miembro (proyecto_id, usuario_id, fecha_add, rol_especifico)
                 VALUES (:proyecto_id, :usuario_id, :fecha_add, :rol_especifico)'
            );
            $stmtManager->execute([
                'proyecto_id' => $projectId,
                'usuario_id' => (int)$data['responsable_id'],
                'fecha_add' => date('Y-m-d H:i:s'),
                'rol_especifico' => 'Jefe de Proyecto'
            ]);

            // Persistência das fases iniciais
            $rawPhases = $data['phases'] ?? ($data['fases'] ?? []);
            if (!empty($rawPhases) && is_array($rawPhases)) {
                $stmtPhase = $pdo->prepare(
                    'INSERT INTO fases (nombre, descripcion, orden, completada, proyecto_id)
                     VALUES (:nombre, :descripcion, :orden, 0, :proyecto_id)'
                );
                
                foreach ($rawPhases as $index => $phase) {
                    $nombre = is_array($phase) ? ($phase['nombre'] ?? '') : $phase;
                    $descripcion = is_array($phase) ? ($phase['descripcion'] ?? 'Fase inicial configurada na criação.') : 'Fase inicial configurada na criação.';
                    $orden = is_array($phase) ? (int)($phase['orden'] ?? ($index + 1)) : ($index + 1);

                    $nombre = htmlspecialchars_decode($nombre, ENT_QUOTES);
                    $descripcion = htmlspecialchars_decode($descripcion, ENT_QUOTES);

                    if (empty($nombre)) {
                        continue;
                    }

                    $stmtPhase->execute([
                        'nombre' => $nombre,
                        'descripcion' => $descripcion,
                        'orden' => $orden,
                        'proyecto_id' => $projectId
                    ]);
                }
            }

            // Persistência dos membros adicionais vindos do formulário
            $rawMembers = $data['members'] ?? ($data['miembros'] ?? ($data['membros'] ?? []));
            if (!empty($rawMembers) && is_array($rawMembers)) {
                $stmtMember = $pdo->prepare(
                    'INSERT INTO proyecto_miembro (proyecto_id, usuario_id, fecha_add, rol_especifico)
                     VALUES (:proyecto_id, :usuario_id, :fecha_add, :rol_especifico)'
                );
                
                foreach ($rawMembers as $member) {
                    $userId = isset($member['usuario_id']) ? (int)$member['usuario_id'] : (isset($member['id']) ? (int)$member['id'] : null);
                    
                    // Evita duplicar o próprio responsável que foi inserido acima
                    if (!$userId || $userId === (int)$data['responsable_id']) {
                        continue;
                    }

                    $role = $member['rol_especifico'] ?? ($member['rol_specifico'] ?? ($member['rol'] ?? 'Colaborador'));
                    $role = htmlspecialchars_decode($role, ENT_QUOTES);

                    $stmtMember->execute([
                        'proyecto_id' => $projectId,
                        'usuario_id' => $userId,
                        'fecha_add' => date('Y-m-d H:i:s'),
                        'rol_especifico' => $role
                    ]);
                }
            }

            $pdo->commit();
            return $projectId;

        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $data): void
    {
        $this->validateProjectData($data, true);

        if (isset($data['responsable_id'])) {
            $this->ensureProjectManager((int)$data['responsable_id']);
        }

        $fields = ['nombre' => ':nombre', 'descripcion' => ':descripcion', 'fecha_inicio' => ':fecha_inicio', 'fecha_entrega' => ':fecha_entrega', 'responsable_id' => ':responsable_id'];
        $params = ['id' => $id];
        $updates = [];

        foreach ($fields as $field => $placeholder) {
            if (array_key_exists($field, $data)) {
                $updates[] = "$field = $placeholder";
                $params[ltrim($placeholder, ':')] = $data[$field];
            }
        }

        if (isset($data['estado'])) {
            $updates[] = 'estado = :estado';
            $params['estado'] = $data['estado'];
        }

        if (empty($updates)) {
            return;
        }

        $sql = 'UPDATE proyectos SET ' . implode(', ', $updates) . ' WHERE id = :id';
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * Altera especificamente o estado do projeto de forma atômica (ex: pausado)
     */
    public function updateEstado(int $id, string $estado): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE proyectos SET estado = :estado WHERE id = :id');
        $stmt->execute([
            'estado' => $estado,
            'id' => $id
        ]);
    }

    /**
     * Deleção rígida com limpeza prévia de relacionamentos dependentes (Cascata controlada)
     */
    public function delete(int $id): void
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            // 1. Elimina vínculos de equipe
            $stmt1 = $pdo->prepare('DELETE FROM proyecto_miembro WHERE proyecto_id = :id');
            $stmt1->execute(['id' => $id]);

            // 2. Elimina fases vinculadas
            $stmt2 = $pdo->prepare('DELETE FROM fases WHERE proyecto_id = :id');
            $stmt2->execute(['id' => $id]);

            // 3. Elimina a entidade raiz do projeto
            $stmt3 = $pdo->prepare('DELETE FROM proyectos WHERE id = :id');
            $stmt3->execute(['id' => $id]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function recalculateStatus(int $projectId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COUNT(*) AS total, SUM(completada) AS completed FROM fases WHERE proyecto_id = :id');
        $stmt->execute(['id' => $projectId]);
        $result = $stmt->fetch();

        $stmt = $pdo->prepare('SELECT estado FROM proyectos WHERE id = :id');
        $stmt->execute(['id' => $projectId]);
        $project = $stmt->fetch();

        $total = (int)$result['total'];
        $completed = (int)$result['completed'];
        $currentStatus = $project['estado'] ?? 'planificacion';
        $percentage = $total > 0 ? (int)floor($completed * 100 / $total) : 0;

        // Se o projeto estiver pausado explicitamente, ele não deve mudar sozinho ao atualizar tarefas
        if ($currentStatus === 'pausado') {
            $status = 'pausado';
        } elseif ($completed === $total && $total > 0) {
            $status = 'finalizado';
        } elseif ($completed === 0) {
            $status = 'planificacion';
        } else {
            $status = 'en_curso';
        }

        $stmt = $pdo->prepare('UPDATE proyectos SET porcentaje_avance = :percentage, estado = :status WHERE id = :id');
        $stmt->execute([
            'percentage' => $percentage,
            'status' => $status,
            'id' => $projectId,
        ]);
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

    private function validateProjectData(array $data, bool $partial = false): void
    {
        if (!$partial) {
            if (empty($data['nombre']) || empty($data['descripcion']) || empty($data['fecha_inicio']) || empty($data['fecha_entrega']) || empty($data['responsable_id'])) {
                throw new InvalidArgumentException('Datos de proyecto incompletos.');
            }
        }

        if (!empty($data['fecha_inicio']) && !empty($data['fecha_entrega']) && $data['fecha_inicio'] > $data['fecha_entrega']) {
            throw new InvalidArgumentException('La fecha de entrega debe ser posterior a la fecha de inicio.');
        }
    }

    private function ensureProjectManager(int $responsableId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT rol FROM usuarios WHERE id = :id');
        $stmt->execute(['id' => $responsableId]);
        $user = $stmt->fetch();

        if (!$user || ($user['rol'] !== 'jefe_proyecto' && $user['rol'] !== 'administrador')) {
            throw new InvalidArgumentException('El responsable debe ser un jefe de proyecto o administrador.');
        }
    }

    private function getPhases(int $projectId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM fases WHERE proyecto_id = :id ORDER BY orden ASC');
        $stmt->execute(['id' => $projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getMembers(int $projectId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT pm.usuario_id, u.nombre, u.apellidos, u.correo, pm.rol_especifico, pm.fecha_add
             FROM proyecto_miembro pm
             JOIN usuarios u ON u.id = pm.usuario_id
             WHERE pm.proyecto_id = :id'
        );
        $stmt->execute(['id' => $projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}