<?php

declare(strict_types=1);

namespace Api\Controllers;

use Api\Services\PhaseService;
use Api\Services\ProjectService;
use Api\Utils\Auth;
use Api\Utils\Permission;
use Api\Utils\Request;
use Api\Utils\Response;

class PhaseController
{
    private PhaseService $service;
    private ProjectService $projectService;

    public function __construct()
    {
        $this->service = new PhaseService();
        $this->projectService = new ProjectService();
    }

    public function list(int $projectId): void
    {
        Auth::requireLogin();
        $currentUser = Auth::user();
        $role = $currentUser['rol'];
        $userId = (int)$currentUser['id'];

        $isMember = $this->projectService->isMember($projectId, $userId);
        $isResponsible = $this->projectService->isResponsible($projectId, $userId);

        if (!Permission::canViewProject($role, $isMember)) {
            Response::error('Acceso denegado.', 403);
        }

        Response::json($this->service->listByProject($projectId));
    }

    public function create(int $projectId, Request $request): void
    {
        $this->authorizePhaseModification($projectId);
        $phaseId = $this->service->create($projectId, $request->getBody());
        $this->projectService->recalculateStatus($projectId);
        Response::json(['id' => $phaseId], 201);
    }

    public function update(int $id, Request $request): void
    {
        Auth::requireLogin();

        $phase = $this->service->find($id);
        if (!$phase) {
            Response::error('Fase no encontrada.', 404);
        }

        $projectId = (int)$phase['proyecto_id'];
        $data = $request->getBody();

        $isOnlyToggling = isset($data['completada']) && 
                         !isset($data['nombre']) && 
                         !isset($data['descripcion']) && 
                         !isset($data['orden']);

        if ($isOnlyToggling) {
            $currentUser = Auth::user();
            if (!Permission::canTogglePhase($currentUser['rol'])) {
                Response::error('No tempe permisos para interagir com este projeto.', 403);
            }
        } else {
            $this->authorizePhaseModification($projectId);
            $currentUser = Auth::user();
            if ($currentUser['rol'] === Permission::COLABORADOR) {
                Response::error('No autorizado para modificar la estructura de la fase.', 403);
            }
        }

        $this->service->update($id, $data);
        $this->projectService->recalculateStatus($projectId);
        Response::json(['message' => 'Fase actualizada correctamente.']);
    }

    public function delete(int $id): void
    {
        Auth::requireLogin();

        $projectId = $this->service->getProjectId($id);
        if ($projectId === null) {
            Response::error('Fase no encontrada.', 404);
        }

        if ($this->service->isProjectFinalized($projectId)) {
            Response::error('No se puede eliminar fases de un proyecto finalizado.', 400);
        }

        $this->authorizePhaseModification((int)$projectId);
        $this->service->delete($id);
        $this->projectService->recalculateStatus((int)$projectId);
        Response::json(['message' => 'Fase eliminada.']);
    }

    private function authorizePhaseModification(int $projectId): void
    {
        Auth::requireLogin();
        $currentUser = Auth::user();
        $role = $currentUser['rol'];
        $userId = (int)$currentUser['id'];

        $isResponsible = $this->projectService->isResponsible($projectId, $userId);
        $isMember = $this->projectService->isMember($projectId, $userId);

        if (!Permission::canEditPhase($role, $isResponsible, $isMember)) {
            Response::error('No autorizado para editar fases en este proyecto.', 403);
        }
    }
}