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
        $currentUser = Auth::user();
        $project = $this->service->find($id);

        if (!$project) {
            Response::error('Proyecto no encontrado.', 404);
        }

        if ($currentUser['rol'] === Permission::COLABORADOR && !$this->service->isMember($id, $currentUser['id'])) {
            Response::error('Acceso denegado.', 403);
        }

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

    public function delete(int $id): void
    {
        Auth::requireLogin();
        $currentUser = Auth::user();

        if ($currentUser['rol'] !== Permission::ADMIN) {
            Response::error('Solo administrador puede eliminar proyectos.', 403);
        }

        $project = $this->service->find($id);
        if (!$project) {
            Response::error('Proyecto no encontrado.', 404);
        }

        $this->service->delete($id);
        Response::json(['message' => 'Proyecto eliminado.']);
    }
}
