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
        $currentUser = Auth::user();

        if (!Permission::canManageUsers($currentUser['rol'])) {
            Response::error('No autorizado para ver usuarios.', 403);
        }

        $users = $this->service->list($request->getQuery());
        Response::json($users);
    }
}
