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

        if ($currentUser['rol'] === Permission::COLABORADOR && !$this->projectService->isMember($projectId, $currentUser['id'])) {
            Response::error('Acceso denegado.', 403);
        }

        Response::json($this->service->listByProject($projectId));
    }

    public function create(int $projectId, Request $request): void
    {
        Auth::requireLogin();
        $currentUser = Auth::user();

        if ($currentUser['rol'] === Permission::COLABORADOR && !$this->projectService->isMember($projectId, $currentUser['id'])) {
            Response::error('No autorizado para crear fases en este proyecto.', 403);
        }

        if ($currentUser['rol'] === Permission::JEFE && !$this->projectService->isResponsible($projectId, $currentUser['id'])) {
            Response::error('No autorizado para crear fases en este proyecto.', 403);
        }

        $phaseId = $this->service->create($projectId, $request->getBody());
        Response::json(['id' => $phaseId], 201);
    }

    public function update(int $id, Request $request): void
    {
        Auth::requireLogin();
        $currentUser = Auth::user();
        $projectId = $this->service->getProjectId($id);

        if ($projectId === null) {
            Response::error('Fase no encontrada.', 404);
        }

        if ($currentUser['rol'] === Permission::COLABORADOR && !$this->projectService->isMember($projectId, $currentUser['id'])) {
            Response::error('Acceso denegado.', 403);
        }

        if ($currentUser['rol'] === Permission::JEFE && !$this->projectService->isResponsible($projectId, $currentUser['id'])) {
            Response::error('Acceso denegado.', 403);
        }

        $this->service->update($id, $request->getBody());
        Response::json(['message' => 'Fase actualizada.']);
    }

    public function delete(int $id): void
    {
        Auth::requireLogin();
        $currentUser = Auth::user();
        $projectId = $this->service->getProjectId($id);

        if ($projectId === null) {
            Response::error('Fase no encontrada.', 404);
        }

        if ($currentUser['rol'] === Permission::COLABORADOR && !$this->projectService->isMember($projectId, $currentUser['id'])) {
            Response::error('No autorizado para eliminar fases.', 403);
        }

        if ($currentUser['rol'] === Permission::JEFE && !$this->projectService->isResponsible($projectId, $currentUser['id'])) {
            Response::error('No autorizado para eliminar fases en este proyecto.', 403);
        }

        $this->service->delete($id);
        Response::json(['message' => 'Fase eliminada.']);
    }
}
