<?php

declare(strict_types=1);

namespace Api\Controllers;

use Api\Services\UserService;
use Api\Utils\Auth;
use Api\Utils\Permission;
use Api\Utils\Request;
use Api\Utils\Response;

class UserController
{
    private UserService $service;

    public function __construct()
    {
        $this->service = new UserService();
    }

    public function index(Request $request): void
    {
        Auth::requireLogin();
        try {
            $users = $this->service->list($request->getQuery());
            Response::json($users);
        } catch (\Throwable $e) {
            error_log('User list error: ' . $e->getMessage());
            Response::json([]);
        }
    }

    public function create(Request $request): void
    {
        Auth::requireLogin();
        $currentUser = Auth::user();
        $userRole = $currentUser['rol'] ?? $currentUser['role'] ?? '';

        if (!Permission::canManageUsers($userRole)) {
            Response::error('Não autorizado para gerenciar usuários.', 403);
        }

        $data = $request->getBody();
        $newUser = $this->service->create($data);
        Response::json($newUser, 201);
    }

    public function update(int $id, Request $request): void
    {
        Auth::requireLogin();
        $currentUser = Auth::user();
        $userRole = $currentUser['rol'] ?? $currentUser['role'] ?? '';

        if (!Permission::canManageUsers($userRole)) {
            Response::error('Não autorizado para gerenciar usuários.', 403);
        }

        $this->service->update($id, $request->getBody());
        Response::json(['message' => 'Usuário atualizado com sucesso.']);
    }

    public function delete(int $id): void
    {
        Auth::requireLogin();
        $currentUser = Auth::user();
        $userRole = $currentUser['rol'] ?? $currentUser['role'] ?? '';

        if (!Permission::canManageUsers($userRole)) {
            Response::error('Não autorizado para gerenciar usuários.', 403);
        }

        $this->service->delete($id);
        Response::json(['message' => 'Usuário excluído com sucesso.']);
    }
}
