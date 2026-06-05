<?php

declare(strict_types=1);

namespace Api\Controllers;

use Api\Services\ProjectService;
use Api\Utils\Auth;
use Api\Utils\Permission;
use Api\Utils\Request;
use Api\Utils\Response;

class ProjectController
{
    private ProjectService $service;

    public function __construct()
    {
        $this->service = new ProjectService();
    }

    public function index(Request $request): void
    {
        Auth::requireLogin();
        $filter = $request->getQuery();
        $projects = $this->service->list($filter, Auth::user());

        Response::json($projects);
    }

    public function show(int $id): void
    {
        Auth::requireLogin();
        
        $project = $this->service->find($id);
        if (!$project) {
            Response::error('Proyecto no encontrado.', 404);
        }

        // Garante injeções em múltiplos idiomas para evitar quebras no frontend
        $memberService = new \Api\Services\MemberService();
        $members = $memberService->listByProject($id);
        $project['miembros'] = $members;
        $project['members'] = $members;

        $phaseService = new \Api\Services\PhaseService();
        $phases = $phaseService->listByProject($id);
        $project['fases'] = $phases;
        $project['phases'] = $phases;

        Response::json($project);
    }

    public function create(Request $request): void
    {
        Auth::requireLogin();
        $currentUser = Auth::user();

        if (!Permission::canCreateProject($currentUser['rol'])) {
            Response::error('No autorizado para crear proyectos.', 403);
        }

        $projectId = $this->service->create($request->getBody());
        Response::json(['id' => $projectId], 201);
    }

    public function update(int $id, Request $request): void
    {
        Auth::requireLogin();
        $currentUser = Auth::user();
        $project = $this->service->find($id);

        if (!$project) {
            Response::error('Proyecto no encontrado.', 404);
        }

        $isResponsible = $this->service->isResponsible($id, $currentUser['id']);
        if (!Permission::canEditProject($currentUser['rol'], $isResponsible)) {
            Response::error('No autorizado para editar este proyecto.', 403);
        }

        $this->service->update($id, $request->getBody());
        Response::json(['message' => 'Proyecto actualizado.']);
    }

    /**
     * Endpoint dinâmico para pausar/despausar projetos
     */
    public function togglePause(int $id, Request $request): void
    {
        Auth::requireLogin();
        $currentUser = Auth::user();
        $project = $this->service->find($id);

        if (!$project) {
            Response::error('Proyecto no encontrado.', 404);
        }

        $isResponsible = $this->service->isResponsible($id, $currentUser['id']);
        if ($currentUser['rol'] !== Permission::ADMIN && !$isResponsible) {
            Response::error('No autorizado para alterar el estado de este proyecto.', 403);
        }

        $data = $request->getBody();
        $pausar = $data['pausar'] ?? true;
        
        $nuevoEstado = $pausar ? 'pausado' : 'en_curso';
        
        $this->service->updateEstado($id, $nuevoEstado);
        $this->service->recalculateStatus($id);

        Response::json(['message' => "Proyecto alterado a estado: {$nuevoEstado} correctamente."]);
    }

    public function delete(int $id): void
    {
        Auth::requireLogin();
        $currentUser = Auth::user();

        if ($currentUser['rol'] !== Permission::ADMIN) {
            Response::error('No autorizado para eliminar este proyecto.', 403);
        }

        $project = $this->service->find($id);
        if (!$project) {
            Response::error('Proyecto no encontrado.', 404);
        }

        $this->service->delete($id);
        Response::json(['message' => 'Proyecto eliminado.']);
    }
}