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
        try {
            $result = $this->service->list($filter, Auth::user());
            Response::json($result);
        } catch (\PDOException $e) {
            error_log('SQL Error: ' . $e->getMessage());
            Response::error('Erro ao listar projetos.', 500);
        } catch (\Throwable $e) {
            error_log('List error: ' . $e->getMessage());
            Response::error('Erro interno.', 500);
        }
    }

    public function show(int $id): void
    {
        Auth::requireLogin();
        
        $project = $this->service->find($id);
        if (!$project) {
            Response::error('Proyecto no encontrado.', 404);
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

        try {
            $this->service->update($id, $request->getBody());
            Response::json(['message' => 'Proyecto actualizado.']);
        } catch (\PDOException $e) {
            error_log('Update error: ' . $e->getMessage());
            Response::error('Erro ao atualizar projeto.', 500);
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 422);
        }
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