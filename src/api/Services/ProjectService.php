<?php

declare(strict_types=1);

namespace Api\Services;

use Api\Database\Database;
use Api\Repositories\ProjectRepository;
use Api\Utils\Validator;
use Api\Services\MemberService;
use Api\Services\PhaseService;
use PDO;

class ProjectService
{
    private ProjectRepository $repository;
    private MemberService $memberService;
    private PhaseService $phaseService;

    public function __construct()
    {
        $this->repository = new ProjectRepository();
        $this->memberService = new MemberService();
        $this->phaseService = new PhaseService();
    }

    /**
     * Lista projetos paginados e calcula estatísticas delegando o SQL para o Repository
     */
    public function list(array $filter = [], array $user = []): array
    {
        [$where, $params] = $this->buildWhereClause($filter, $user);

        $limit = (int)($filter['limit'] ?? 10);
        $page = max(1, (int)($filter['page'] ?? 1));
        $offset = ($page - 1) * $limit;
        
        $userRole = (string)($user['rol'] ?? '');

        return [
            'data'              => $this->repository->getFilteredProjects($where, $params, $limit, $offset, $userRole, [
                'date_order'     => (string)($filter['date_order'] ?? ''),
                'deadline_order' => (string)($filter['deadline_order'] ?? ''),
            ]),
            'page'              => $page,
            'limit'             => $limit,
            'total'             => $this->repository->countTotal($where, $params, $userRole),
            'totais_por_estado' => $this->repository->countByStatus($where, $params, $userRole),
        ];
    }

    public function find(int $id): ?array
    {
        $projectData = $this->repository->findById($id);
        if (!$projectData) {
            return null;
        }

        $projectData['phases'] = $this->phaseService->listByProject($id);
        $projectData['members'] = $this->memberService->listByProject($id);

        return $projectData;
    }

    public function create(array $data): int
    {
        Validator::assertProjectData($data);
        Validator::assertProjectManager((int)$data['responsable_id']);

        return Database::transaction(function (PDO $pdo) use ($data) {
            $projectId = $this->repository->insert($pdo, $data);
            
            $this->repository->insertMember($pdo, $projectId, (int)$data['responsable_id'], 'Jefe de Proyecto');
            $this->insertInitialPhases($pdo, $projectId, $data);
            $this->insertInitialMembers($pdo, $projectId, $data);

            return $projectId;
        });
    }

    public function update(int $id, array $data): void
    {
        Validator::assertProjectData($data, true);

        if (isset($data['responsable_id'])) {
            Validator::assertProjectManager((int)$data['responsable_id']);
        }

        $updates = $this->buildUpdateFields($data);
        if (empty($updates['fields'])) {
            return;
        }

        $this->repository->update($id, $updates['fields'], $updates['params']);
    }

    public function delete(int $id): void
    {
        Database::transaction(function (PDO $pdo) use ($id) {
            $this->repository->deleteCascade($pdo, $id);
        });
    }

    public function recalculateStatus(int $projectId): void
    {
        $row = $this->repository->getStatusAndPhaseCounts($projectId);
        if (!$row) {
            return;
        }

        $total = (int)$row['total'];
        $completed = (int)$row['completed'];
        $currentStatus = $row['estado'] ?? 'planificacion';

        $status = $this->determineProjectStatus($currentStatus, $total, $completed);
        $percentage = $total > 0 ? (int)floor($completed * 100 / $total) : 0;

        $this->repository->updateStatusAndProgress($projectId, $percentage, $status);
    }

    public function isMember(int $projectId, int $userId): bool
    {
        return $this->repository->isMember($projectId, $userId);
    }

    public function isResponsible(int $projectId, int $userId): bool
    {
        return $this->repository->isResponsible($projectId, $userId);
    }

    // ==========================================
    // HELPERS PRIVADOS DE MAPEAMENTO E PARSING
    // ==========================================

    private function buildWhereClause(array $filter, array $user): array
    {
        $where = [];
        $params = [];

        if (($user['rol'] ?? '') === 'colaborador') {
            $where[] = 'pm.usuario_id = :user_id';
            $params['user_id'] = $user['id'];
        }

        $filterMap = [
            'search'         => ['cond' => 'LOWER(p.nombre) LIKE :search', 'type' => 'search'],
            'estado'         => ['cond' => 'p.estado = :estado', 'type' => 'direct'],
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

    private function buildUpdateFields(array $data): array
    {
        $fields = [];
        $params = [];

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

        return ['fields' => $fields, 'params' => $params];
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

    private function insertInitialPhases(PDO $pdo, int $projectId, array $data): void
    {
        $rawPhases = $data['phases'] ?? ($data['fases'] ?? []);
        if (empty($rawPhases) || !is_array($rawPhases)) {
            return;
        }

        foreach ($rawPhases as $index => $phase) {
            $nombre = is_array($phase) ? ($phase['nombre'] ?? '') : $phase;
            $descripcion = is_array($phase) ? ($phase['descripcion'] ?? 'Fase criada automaticamente.') : 'Fase criada automaticamente.';
            $orden = is_array($phase) ? (int)($phase['orden'] ?? ($index + 1)) : ($index + 1);

            if (empty(trim((string)$nombre))) {
                continue;
            }

            $this->repository->insertPhase($pdo, $projectId, (string)$nombre, (string)$descripcion, $orden);
        }
    }

    private function insertInitialMembers(PDO $pdo, int $projectId, array $data): void
    {
        $rawMembers = $data['members'] ?? ($data['miembros'] ?? ($data['membros'] ?? []));
        if (empty($rawMembers) || !is_array($rawMembers)) {
            return;
        }

        foreach ($rawMembers as $member) {
            $userId = $member['usuario_id'] ?? $member['id'] ?? null;
            if (!$userId || (int)$userId === (int)$data['responsable_id']) {
                continue;
            }

            $role = htmlspecialchars_decode($member['rol_especifico'] ?? $member['rol'] ?? 'Colaborador', ENT_QUOTES);
            $this->repository->insertMember($pdo, $projectId, (int)$userId, $role);
        }
    }
}