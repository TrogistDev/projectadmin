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
        [$where, $params] = $this->buildWhereClause($filter, $user);

        $limit = (int)($filter['limit'] ?? 10);
        $page = max(1, (int)($filter['page'] ?? 1));
        $offset = ($page - 1) * $limit;

        $sql = $this->buildSelectQuery($user);
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY p.fecha_creacion DESC LIMIT :limit OFFSET :offset';

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('Project list SQL: ' . $e->getMessage());
            throw $e;
        }

        return [
            'data' => $data,
            'page' => $page,
            'limit' => $limit,
            'total' => $this->countTotal($filter, $user),
            'totais_por_estado' => $this->countByStatus($filter, $user),
        ];
    }

    public function find(int $id): array|null
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT p.*, u.nombre AS responsable_nombre, u.apellidos AS responsable_apellidos FROM proyectos p JOIN usuarios u ON u.id = p.responsable_id WHERE p.id = :id');
        $stmt->execute(['id' => $id]);

        $projectData = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$projectData) {
            return null;
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
            $projectId = $this->insertProject($pdo, $data);
            $this->insertManagerAsMember($pdo, $projectId, (int)$data['responsable_id']);
            $this->insertInitialPhases($pdo, $projectId, $data);
            $this->insertInitialMembers($pdo, $projectId, $data);
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

        $updates = $this->buildUpdateFields($data);
        if (empty($updates['fields'])) {
            return;
        }

        $sql = 'UPDATE proyectos SET ' . implode(', ', $updates['fields']) . ' WHERE id = :id';
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge($updates['params'], ['id' => $id]));
    }

    public function delete(int $id): void
    {
        Database::transaction(function (PDO $pdo) use ($id) {
            $pdo->prepare('DELETE FROM proyecto_miembro WHERE proyecto_id = :id')->execute(['id' => $id]);
            $pdo->prepare('DELETE FROM fases WHERE proyecto_id = :id')->execute(['id' => $id]);
            $pdo->prepare('DELETE FROM proyectos WHERE id = :id')->execute(['id' => $id]);
        });
    }

    public function recalculateStatus(int $projectId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT p.estado, COUNT(f.id) AS total, SUM(f.completada) AS completed
             FROM proyectos p LEFT JOIN fases f ON f.proyecto_id = p.id
             WHERE p.id = :id GROUP BY p.estado'
        );
        $stmt->execute(['id' => $projectId]);
        $row = $stmt->fetch();

        $total = (int)$row['total'];
        $completed = (int)$row['completed'];
        $currentStatus = $row['estado'] ?? 'planificacion';

        $status = $this->determineProjectStatus($currentStatus, $total, $completed);
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

    // Private helper methods

    private function buildSelectQuery(array $user): string
    {
        $sql = 'SELECT p.*, u.nombre AS responsable_nombre, u.apellidos AS responsable_apellidos FROM proyectos p JOIN usuarios u ON u.id = p.responsable_id';

        if (($user['rol'] ?? '') === 'colaborador') {
            $sql .= ' JOIN proyecto_miembro pm ON pm.proyecto_id = p.id';
        }

        return $sql;
    }

    private function buildWhereClause(array $filter, array $user): array
    {
        $where = [];
        $params = [];

        if (($user['rol'] ?? '') === 'colaborador') {
            $where[] = 'pm.usuario_id = :user_id';
            $params['user_id'] = $user['id'];
        }

        $filterMap = [
            'search' => ['cond' => 'LOWER(p.nombre) LIKE :search', 'type' => 'search'],
            'estado' => ['cond' => 'p.estado = :estado', 'type' => 'direct'],
            'responsable_id' => ['cond' => 'p.responsable_id = :responsable_id', 'type' => 'int'],
        ];

        foreach ($filterMap as $field => $config) {
            $val = $filter[$field] ?? '';
            if (is_string($val) && trim($val) !== '') {
                $where[] = $config['cond'];
                $params[$field] = $config['type'] === 'search'
                    ? '%' . mb_strtolower($val) . '%'
                    : ($config['type'] === 'int' ? (int)$val : $val);
            }
        }

        $dateResult = $this->buildDateConditions($filter);
        if (!empty($dateResult['conditions'])) {
            $where[] = '(' . implode(' OR ', $dateResult['conditions']) . ')';
            $params = array_merge($params, $dateResult['params']);
        }

        return [$where, $params];
    }

    private function buildDateConditions(array $filter): array
    {
        $conditions = [];
        $params = [];

        $hasStart = !empty($filter['date_start']);
        $hasEnd = !empty($filter['date_end']);

        if (!$hasStart && !$hasEnd) {
            return ['conditions' => [], 'params' => []];
        }

        if ($hasStart && $hasEnd) {
            $conditions[] = "(p.fecha_inicio >= :ds1 AND p.fecha_inicio <= :de1)";
            $conditions[] = "(p.fecha_entrega >= :ds2 AND p.fecha_entrega <= :de2)";
            $params = ['ds1' => $filter['date_start'], 'de1' => $filter['date_end'], 'ds2' => $filter['date_start'], 'de2' => $filter['date_end']];
        } elseif ($hasStart) {
            $conditions[] = "(p.fecha_inicio >= :ds1 OR p.fecha_entrega >= :ds2)";
            $params = ['ds1' => $filter['date_start'], 'ds2' => $filter['date_start']];
        } else {
            $conditions[] = "(p.fecha_inicio <= :de1 OR p.fecha_entrega <= :de2)";
            $params = ['de1' => $filter['date_end'], 'de2' => $filter['date_end']];
        }

        return ['conditions' => $conditions, 'params' => $params];
    }

    private function countByStatus(array $filter, array $user): array
    {
        $pdo = Database::getConnection();
        [$where, $params] = $this->buildWhereClause($filter, $user);

        $sql = 'SELECT estado, COUNT(*) as qtd FROM proyectos p JOIN usuarios u ON u.id = p.responsable_id';
        if (($user['rol'] ?? '') === 'colaborador') {
            $sql .= ' JOIN proyecto_miembro pm ON pm.proyecto_id = p.id';
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

        $result = ['planificacion' => 0, 'en_curso' => 0, 'pausado' => 0, 'finalizado' => 0];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['estado']] = (int)$row['qtd'];
        }

        return $result;
    }

    private function countTotal(array $filter, array $user): int
    {
        $pdo = Database::getConnection();
        [$where, $params] = $this->buildWhereClause($filter, $user);

        $sql = 'SELECT COUNT(*) FROM proyectos p JOIN usuarios u ON u.id = p.responsable_id';
        if (($user['rol'] ?? '') === 'colaborador') {
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

    private function validateProjectData(array $data, bool $partial = false): void
    {
        if (!$partial && (empty($data['nombre']) || empty($data['descripcion']) || empty($data['fecha_inicio']) || empty($data['fecha_entrega']) || empty($data['responsable_id']))) {
            throw new InvalidArgumentException('Datos de proyecto incompletos.');
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

        if (!$user || !in_array($user['rol'], ['jefe_proyecto', 'administrador'], true)) {
            throw new InvalidArgumentException('El responsable debe ser un jefe de proyecto o administrador.');
        }
    }

    private function insertProject(PDO $pdo, array $data): int
    {
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

        return (int)$pdo->lastInsertId();
    }

    private function insertManagerAsMember(PDO $pdo, int $projectId, int $responsableId): void
    {
        $stmt = $pdo->prepare(
            'INSERT INTO proyecto_miembro (proyecto_id, usuario_id, fecha_add, rol_especifico)
             VALUES (:proyecto_id, :usuario_id, :fecha_add, :rol_especifico)'
        );
        $stmt->execute([
            'proyecto_id' => $projectId,
            'usuario_id' => $responsableId,
            'fecha_add' => date('Y-m-d H:i:s'),
            'rol_especifico' => 'Jefe de Proyecto'
        ]);
    }

    private function insertInitialPhases(PDO $pdo, int $projectId, array $data): void
    {
        $rawPhases = $data['phases'] ?? ($data['fases'] ?? []);
        if (empty($rawPhases) || !is_array($rawPhases)) {
            return;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO fases (nombre, descripcion, orden, completada, proyecto_id) VALUES (:nombre, :descripcion, :orden, 0, :proyecto_id)'
        );

        foreach ($rawPhases as $index => $phase) {
            $nombre = is_array($phase) ? ($phase['nombre'] ?? '') : $phase;
            $descripcion = is_array($phase) ? ($phase['descripcion'] ?? 'Fase criada automaticamente.') : 'Fase criada automaticamente.';
            $orden = is_array($phase) ? (int)($phase['orden'] ?? ($index + 1)) : ($index + 1);

            if (empty(trim($nombre))) {
                continue;
            }

            $stmt->execute([
                'nombre' => htmlspecialchars_decode($nombre, ENT_QUOTES),
                'descripcion' => htmlspecialchars_decode($descripcion, ENT_QUOTES),
                'orden' => $orden,
                'proyecto_id' => $projectId
            ]);
        }
    }

    private function insertInitialMembers(PDO $pdo, int $projectId, array $data): void
    {
        $rawMembers = $data['members'] ?? ($data['miembros'] ?? ($data['membros'] ?? []));
        if (empty($rawMembers) || !is_array($rawMembers)) {
            return;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO proyecto_miembro (proyecto_id, usuario_id, fecha_add, rol_especifico) VALUES (:proyecto_id, :usuario_id, :fecha_add, :rol_especifico)'
        );

        foreach ($rawMembers as $member) {
            $userId = $member['usuario_id'] ?? $member['id'] ?? null;
            if (!$userId || $userId === (int)$data['responsable_id']) {
                continue;
            }

            $stmt->execute([
                'proyecto_id' => $projectId,
                'usuario_id' => (int)$userId,
                'fecha_add' => date('Y-m-d H:i:s'),
                'rol_especifico' => htmlspecialchars_decode($member['rol_especifico'] ?? $member['rol'] ?? 'Colaborador', ENT_QUOTES)
            ]);
        }
    }

    private function buildUpdateFields(array $data): array
    {
        $fields = [];
        $params = ['id' => null];

        foreach (['nombre', 'descripcion', 'fecha_inicio', 'fecha_entrega', 'responsable_id'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }

        if (isset($data['estado'])) {
            $fields[] = 'estado = :estado';
            $params['estado'] = $data['estado'];
        }

        return ['fields' => $fields, 'params' => array_filter($params, fn($k) => $k !== 'id')];
    }

    private function determineProjectStatus(string $currentStatus, int $total, int $completed): string
    {
        if ($currentStatus === 'pausado') {
            return 'pausado';
        }
        if ($completed === $total && $total > 0) {
            return 'finalizado';
        }
        if ($completed === 0) {
            return 'planificacion';
        }
        return 'en_curso';
    }
}