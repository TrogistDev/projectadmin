<?php

declare(strict_types=1);

namespace Api\Services;

use Api\Database\Database;
use Api\Services\MemberService;
use Api\Services\PhaseService;
use PDO;
use Throwable;
use InvalidArgumentException;

class ProjectService
{
    private MemberService $memberService;
    private PhaseService $phaseService;

    public function __construct()
    {
        $this->memberService = new MemberService();
        $this->phaseService = new PhaseService();
    }

    public function list(array $filter = [], array $user = []): array
    {
        $pdo = Database::getConnection();

        $limit = isset($filter['limit']) && is_numeric($filter['limit']) ? (int)$filter['limit'] : 10;
        $page = isset($filter['page']) && is_numeric($filter['page']) && (int)$filter['page'] > 0 ? (int)$filter['page'] : 1;
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];

        $sql = 'SELECT p.*, u.nombre AS responsable_nombre, u.apellidos AS responsable_apellidos
                FROM proyectos p
                JOIN usuarios u ON u.id = p.responsable_id';

        if (($user['rol'] ?? '') === 'colaborador') {
            $sql .= ' JOIN proyecto_miembro pm ON pm.proyecto_id = p.id';
            $where[] = 'pm.usuario_id = :user_id';
            $params['user_id'] = $user['id'];
        }

        if (!empty($filter['search'])) {
            $where[] = 'LOWER(p.nombre) LIKE :search';
            $params['search'] = '%' . mb_strtolower($filter['search']) . '%';
        }

        if (!empty($filter['estado'])) {
            $where[] = 'p.estado = :estado';
            $params['estado'] = $filter['estado'];
        }

        if (!empty($filter['responsable_id'])) {
            $where[] = 'p.responsable_id = :responsable_id';
            $params['responsable_id'] = (int)$filter['responsable_id'];
        }

        if (!empty($filter['fecha_inicio'])) {
            $where[] = 'p.fecha_inicio >= :fecha_inicio';
            $params['fecha_inicio'] = $filter['fecha_inicio'];
        }

        if (!empty($filter['fecha_entrega'])) {
            $where[] = 'p.fecha_entrega <= :fecha_entrega';
            $params['fecha_entrega'] = $filter['fecha_entrega'];
        }

        // Filtro de intervalo de datas: projetos que começam OU terminam dentro do intervalo
        if (!empty($filter['date_start']) || !empty($filter['date_end'])) {
            $dateConditions = [];
            $dsIndex = 1;
            $deIndex = 1;

            if (!empty($filter['date_start']) && !empty($filter['date_end'])) {
                $dateConditions[] = "(p.fecha_inicio >= :ds1 AND p.fecha_inicio <= :de1)";
                $dateConditions[] = "(p.fecha_entrega >= :ds2 AND p.fecha_entrega <= :de2)";
                $params['ds1'] = $filter['date_start'];
                $params['de1'] = $filter['date_end'];
                $params['ds2'] = $filter['date_start'];
                $params['de2'] = $filter['date_end'];
            } elseif (!empty($filter['date_start'])) {
                $dateConditions[] = "(p.fecha_inicio >= :ds OR p.fecha_entrega >= :ds)";
                $params['ds'] = $filter['date_start'];
            } elseif (!empty($filter['date_end'])) {
                $dateConditions[] = "(p.fecha_inicio <= :de OR p.fecha_entrega <= :de)";
                $params['de'] = $filter['date_end'];
            }

            if (!empty($dateConditions)) {
                $where[] = '(' . implode(' OR ', $dateConditions) . ')';
            }
        }

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY p.fecha_creacion DESC LIMIT :limit OFFSET :offset';

        $stmt = $pdo->prepare($sql);

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }

        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'page' => $page,
            'limit' => $limit,
            'total' => (int)$this->countTotal($filter, $user),
            'totais_por_estado' => $this->countByStatus($filter, $user),
        ];
    }

    private function countByStatus(array $filter = [], array $user = []): array
    {
        $pdo = Database::getConnection();

        $where = [];
        $params = [];

        $sql = 'SELECT estado, COUNT(*) as qtd FROM proyectos p
                JOIN usuarios u ON u.id = p.responsable_id';

        if (($user['rol'] ?? '') === 'colaborador') {
            $sql .= ' JOIN proyecto_miembro pm ON pm.proyecto_id = p.id';
            $where[] = 'pm.usuario_id = :user_id';
            $params['user_id'] = $user['id'];
        }

        if (!empty($filter['search'])) {
            $where[] = 'LOWER(p.nombre) LIKE :search';
            $params['search'] = '%' . mb_strtolower($filter['search']) . '%';
        }

        if (!empty($filter['estado'])) {
            $where[] = 'p.estado = :estado';
            $params['estado'] = $filter['estado'];
        }

        if (!empty($filter['responsable_id'])) {
            $where[] = 'p.responsable_id = :responsable_id';
            $params['responsable_id'] = (int)$filter['responsable_id'];
        }

        if (!empty($filter['fecha_inicio'])) {
            $where[] = 'p.fecha_inicio >= :fecha_inicio';
            $params['fecha_inicio'] = $filter['fecha_inicio'];
        }

        if (!empty($filter['fecha_entrega'])) {
            $where[] = 'p.fecha_entrega <= :fecha_entrega';
            $params['fecha_entrega'] = $filter['fecha_entrega'];
        }

        // Filtro de intervalo de datas
        if (!empty($filter['date_start']) || !empty($filter['date_end'])) {
            $dateConditions = [];

            if (!empty($filter['date_start']) && !empty($filter['date_end'])) {
                $dateConditions[] = "(p.fecha_inicio >= :ds1 AND p.fecha_inicio <= :de1)";
                $dateConditions[] = "(p.fecha_entrega >= :ds2 AND p.fecha_entrega <= :de2)";
                $params['ds1'] = $filter['date_start'];
                $params['de1'] = $filter['date_end'];
                $params['ds2'] = $filter['date_start'];
                $params['de2'] = $filter['date_end'];
            } elseif (!empty($filter['date_start'])) {
                $dateConditions[] = "(p.fecha_inicio >= :ds OR p.fecha_entrega >= :ds)";
                $params['ds'] = $filter['date_start'];
            } elseif (!empty($filter['date_end'])) {
                $dateConditions[] = "(p.fecha_inicio <= :de OR p.fecha_entrega <= :de)";
                $params['de'] = $filter['date_end'];
            }

            if (!empty($dateConditions)) {
                $where[] = '(' . implode(' OR ', $dateConditions) . ')';
            }
        }

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' GROUP BY estado';

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->execute();

        $result = [
            'planificacion' => 0,
            'en_curso' => 0,
            'pausado' => 0,
            'finalizado' => 0,
        ];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['estado']] = (int)$row['qtd'];
        }

        return $result;
    }

    private function countTotal(array $filter = [], array $user = []): int
    {
        $pdo = Database::getConnection();

        $where = [];
        $params = [];

        $sql = 'SELECT COUNT(*) FROM proyectos p
                JOIN usuarios u ON u.id = p.responsable_id';

        if (($user['rol'] ?? '') === 'colaborador') {
            $sql .= ' JOIN proyecto_miembro pm ON pm.proyecto_id = p.id';
            $where[] = 'pm.usuario_id = :user_id';
            $params['user_id'] = $user['id'];
        }

        if (!empty($filter['search'])) {
            $where[] = 'LOWER(p.nombre) LIKE :search';
            $params['search'] = '%' . mb_strtolower($filter['search']) . '%';
        }

        if (!empty($filter['estado'])) {
            $where[] = 'p.estado = :estado';
            $params['estado'] = $filter['estado'];
        }

        if (!empty($filter['responsable_id'])) {
            $where[] = 'p.responsable_id = :responsable_id';
            $params['responsable_id'] = (int)$filter['responsable_id'];
        }

        if (!empty($filter['fecha_inicio'])) {
            $where[] = 'p.fecha_inicio >= :fecha_inicio';
            $params['fecha_inicio'] = $filter['fecha_inicio'];
        }

        if (!empty($filter['fecha_entrega'])) {
            $where[] = 'p.fecha_entrega <= :fecha_entrega';
            $params['fecha_entrega'] = $filter['fecha_entrega'];
        }

        // Filtro de intervalo de datas
        if (!empty($filter['date_start']) || !empty($filter['date_end'])) {
            $dateConditions = [];

            if (!empty($filter['date_start']) && !empty($filter['date_end'])) {
                $dateConditions[] = "(p.fecha_inicio >= :ds1 AND p.fecha_inicio <= :de1)";
                $dateConditions[] = "(p.fecha_entrega >= :ds2 AND p.fecha_entrega <= :de2)";
                $params['ds1'] = $filter['date_start'];
                $params['de1'] = $filter['date_end'];
                $params['ds2'] = $filter['date_start'];
                $params['de2'] = $filter['date_end'];
            } elseif (!empty($filter['date_start'])) {
                $dateConditions[] = "(p.fecha_inicio >= :ds OR p.fecha_entrega >= :ds)";
                $params['ds'] = $filter['date_start'];
            } elseif (!empty($filter['date_end'])) {
                $dateConditions[] = "(p.fecha_inicio <= :de OR p.fecha_entrega <= :de)";
                $params['de'] = $filter['date_end'];
            }

            if (!empty($dateConditions)) {
                $where[] = '(' . implode(' OR ', $dateConditions) . ')';
            }
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

    public function find(int $id): array|false
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT p.*, u.nombre AS responsable_nombre, u.apellidos AS responsable_apellidos FROM proyectos p JOIN usuarios u ON u.id = p.responsable_id WHERE p.id = :id');
        $stmt->execute(['id' => $id]);

        $projectData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$projectData) {
            return false;
        }

        $projectData['phases'] = $this->phaseService->listByProject($id);
        $projectData['members'] = $this->memberService->listByProject($id);

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
        $stmt = $pdo->prepare(
            'SELECT p.estado, COUNT(f.id) AS total, SUM(f.completada) AS completed
             FROM proyectos p
             LEFT JOIN fases f ON f.proyecto_id = p.id
             WHERE p.id = :id
             GROUP BY p.estado'
        );
        $stmt->execute(['id' => $projectId]);
        $row = $stmt->fetch();

        $total = (int)$row['total'];
        $completed = (int)$row['completed'];
        $currentStatus = $row['estado'] ?? 'planificacion';

        if ($currentStatus === 'pausado') {
            $status = 'pausado';
        } elseif ($completed === $total && $total > 0) {
            $status = 'finalizado';
        } elseif ($completed === 0) {
            $status = 'planificacion';
        } else {
            $status = 'en_curso';
        }

        $percentage = $total > 0 ? (int)floor($completed * 100 / $total) : 0;
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
}