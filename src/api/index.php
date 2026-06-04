<?php

declare(strict_types=1);

require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/config.php';

use Api\Utils\Request;
use Api\Utils\Response;
use Api\Controllers\AuthController;
use Api\Controllers\ProjectController;
use Api\Controllers\PhaseController;
use Api\Controllers\MemberController;
use Api\Controllers\UserController;

session_start();

try {
    $request = new Request();
    $method = $request->getMethod();
    $path = rtrim($request->getPath(), '/');

    $authController = new AuthController();
    $projectController = new ProjectController();
    $phaseController = new PhaseController();
    $memberController = new MemberController();
    $userController = new UserController();

    if ($path === '/api/login' && $method === 'POST') {
        $authController->login($request);
    }

    if ($path === '/api/logout' && $method === 'POST') {
        $authController->logout();
    }

    if (preg_match('#^/api/projects$#', $path)) {
        if ($method === 'GET') {
            $projectController->index($request);
        } elseif ($method === 'POST') {
            $projectController->create($request);
        }
    }

    if (preg_match('#^/api/projects/([0-9]+)$#', $path, $matches)) {
        $projectId = (int)$matches[1];
        if ($method === 'GET') {
            $projectController->show($projectId);
        } elseif ($method === 'PUT') {
            $projectController->update($projectId, $request);
        } elseif ($method === 'DELETE') {
            $projectController->delete($projectId);
        }
    }

    if (preg_match('#^/api/projects/([0-9]+)/members$#', $path, $matches)) {
        $projectId = (int)$matches[1];
        if ($method === 'GET') {
            $memberController->list($projectId);
        } elseif ($method === 'POST') {
            $memberController->add($projectId, $request);
        }
    }

    if (preg_match('#^/api/projects/([0-9]+)/members/([0-9]+)$#', $path, $matches)) {
        $projectId = (int)$matches[1];
        $userId = (int)$matches[2];
        if ($method === 'DELETE') {
            $memberController->remove($projectId, $userId);
        }
    }

    if (preg_match('#^/api/projects/([0-9]+)/phases$#', $path, $matches)) {
        $projectId = (int)$matches[1];
        if ($method === 'GET') {
            $phaseController->list($projectId);
        } elseif ($method === 'POST') {
            $phaseController->create($projectId, $request);
        }
    }

    if (preg_match('#^/api/phases/([0-9]+)$#', $path, $matches)) {
        $phaseId = (int)$matches[1];
        if ($method === 'PUT') {
            $phaseController->update($phaseId, $request);
        } elseif ($method === 'DELETE') {
            $phaseController->delete($phaseId);
        }
    }

    if ($path === '/api/users' && $method === 'GET') {
        $userController->index($request);
    }

    Response::error('Endpoint not found', 404);
} catch (\InvalidArgumentException $exception) {
    Response::error($exception->getMessage(), 422);
} catch (\RuntimeException $exception) {
    Response::error($exception->getMessage(), 400);
} catch (\Throwable $exception) {
    Response::error('Erro interno no servidor.', 500);
}
