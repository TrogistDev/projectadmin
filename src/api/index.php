<?php

declare(strict_types=1);

// 1. CONFIGURAÇÕES RÍGIDAS DE SESSÃO E COOKIES (CHECKLIST ACERTADO)
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_path', '/');
ini_set('session.gc_maxlifetime', '28800'); // 8h
ini_set('session.cookie_lifetime', '28800'); // cookie persiste 8h

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. MIDDLEWARE CORS DE SEGURANÇA PARA CREDENCIAIS
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = ['http://localhost:8000', 'http://127.0.0.1:8000', 'http://localhost:3000']; 

if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
}

// Interrupção imediata de Preflight OPTIONS para blindagem de tráfego
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/config.php';

use Api\Utils\Request;
use Api\Utils\Response;

$isApi = str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api');

if ($isApi) {
    try {
        $request = new Request();
        $method = $request->getMethod();
        $path = rtrim($request->getPath(), '/');

        // =================================================================
        // ROTAS DE AUTENTICAÇÃO (Abertas)
        // =================================================================
        if ($path === '/api/login' && $method === 'POST') {
            (new Api\Controllers\AuthController())->login($request);
            exit;
        }
        if ($path === '/api/logout' && $method === 'POST') {
            (new Api\Controllers\AuthController())->logout();
            exit;
        }

        // =================================================================
        // ROTAS PROTEGIDAS (A validação de sessão ocorre dentro dos Controllers)
        // =================================================================
        if ($path === '/api/users' && $method === 'GET') {
            (new Api\Controllers\UserController())->index($request);
            exit;
        }
        if ($path === '/api/users' && $method === 'POST') {
            (new Api\Controllers\UserController())->create($request);
            exit;
        }

        // Rotas de Projetos
        if (preg_match('#^/api/projects$#', $path)) {
            $controller = new Api\Controllers\ProjectController();
            $method === 'GET' ? $controller->index($request) : $controller->create($request);
            exit;
        }
        if (preg_match('#^/api/projects/([0-9]+)$#', $path, $matches)) {
            $controller = new Api\Controllers\ProjectController();
            $id = (int)$matches[1];
            if ($method === 'GET') $controller->show($id);
            if ($method === 'PUT') $controller->update($id, $request);
            if ($method === 'DELETE') $controller->delete($id);
            exit;
        }

        // Rotas de Membros
        if (preg_match('#^/api/projects/([0-9]+)/members/([0-9]+)$#', $path, $matches)) {
            if ($method === 'DELETE') (new Api\Controllers\MemberController())->remove((int)$matches[1], (int)$matches[2]);
            exit;
        }
        if (preg_match('#^/api/projects/([0-9]+)/members$#', $path, $matches)) {
            $controller = new Api\Controllers\MemberController();
            $id = (int)$matches[1];
            $method === 'GET' ? $controller->list($id) : $controller->add($id, $request);
            exit;
        }

        // Rotas de Fases
        if (preg_match('#^/api/projects/([0-9]+)/phases$#', $path, $matches)) {
            $controller = new Api\Controllers\PhaseController();
            $id = (int)$matches[1];
            $method === 'GET' ? $controller->list($id) : $controller->create($id, $request);
            exit;
        }
        if (preg_match('#^/api/phases/([0-9]+)$#', $path, $matches)) {
            $controller = new Api\Controllers\PhaseController();
            $id = (int)$matches[1];
            if ($method === 'PUT') $controller->update($id, $request);
            if ($method === 'DELETE') $controller->delete($id);
            exit;
        }

        Response::error('Endpoint not found', 404);

    } catch (\InvalidArgumentException $e) {
        Response::error($e->getMessage(), 422);
    } catch (\RuntimeException $e) {
        Response::error($e->getMessage(), 400);
    } catch (\Throwable $e) {
        Response::error('Erro interno no servidor.', 500);
    }
    exit;
}