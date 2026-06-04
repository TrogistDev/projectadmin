<?php

declare(strict_types=1);

namespace Api\Controllers;

use Api\Services\MemberService;
use Api\Services\ProjectService;
use Api\Utils\Auth;
use Api\Utils\Permission;
use Api\Utils\Request;
use Api\Utils\Response;

class MemberController
{
    private MemberService $service;
    private ProjectService $projectService;

    public function __construct()
    {
        $this->service = new MemberService();
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

    public function add(int $projectId, Request $request): void
    {
        Auth::requireLogin();
        $currentUser = Auth::user();

        if (!Permission::canManageMembers($currentUser['rol'])) {
            Response::error('No autorizado para agregar miembros.', 403);
        }

        $this->service->add($projectId, $request->getBody());
        Response::json(['message' => 'Miembro agregado al proyecto.'], 201);
    }

    public function remove(int $projectId, int $userId): void
    {
        Auth::requireLogin();
        $currentUser = Auth::user();

        if (!Permission::canManageMembers($currentUser['rol'])) {
            Response::error('No autorizado para eliminar miembros.', 403);
        }

        $this->service->remove($projectId, $userId);
        Response::json(['message' => 'Miembro eliminado del proyecto.']);
    }
}
