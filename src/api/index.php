<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/autoload.php';

use Api\Utils\Auth;
use Api\Utils\Response;

Auth::start();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $method = strtoupper($method);
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

$routes = [
    'GET|/api/login' => ['Api\Controllers\AuthController', 'loginForm'],
    'POST|/api/login' => ['Api\Controllers\AuthController', 'login'],
    'POST|/api/logout' => ['Api\Controllers\AuthController', 'logout'],
    'GET|/api/users' => ['Api\Controllers\UserController', 'index'],
    'POST|/api/users' => ['Api\Controllers\UserController', 'create'],
    'PUT|/api/users/([0-9]+)' => ['Api\Controllers\UserController', 'update'],
    'DELETE|/api/users/([0-9]+)' => ['Api\Controllers\UserController', 'delete'],
    'GET|/api/projects' => ['Api\Controllers\ProjectController', 'index'],
    'GET|/api/projects/([0-9]+)' => ['Api\Controllers\ProjectController', 'show'],
    'POST|/api/projects' => ['Api\Controllers\ProjectController', 'create'],
    'PUT|/api/projects/([0-9]+)' => ['Api\Controllers\ProjectController', 'update'],
    'DELETE|/api/projects/([0-9]+)' => ['Api\Controllers\ProjectController', 'delete'],
    'GET|/api/projects/([0-9]+)/members' => ['Api\Controllers\MemberController', 'listByProject'],
    'POST|/api/projects/([0-9]+)/members' => ['Api\Controllers\MemberController', 'add'],
    'DELETE|/api/projects/([0-9]+)/members/([0-9]+)' => ['Api\Controllers\MemberController', 'remove'],
    'GET|/api/projects/([0-9]+)/phases' => ['Api\Controllers\PhaseController', 'list'],
    'POST|/api/projects/([0-9]+)/phases' => ['Api\Controllers\PhaseController', 'create'],
    'PUT|/api/phases/([0-9]+)' => ['Api\Controllers\PhaseController', 'update'],
    'DELETE|/api/phases/([0-9]+)' => ['Api\Controllers\PhaseController', 'delete'],
];

$matched = false;

foreach ($routes as $pattern => $handler) {
    [$httpMethod, $routePattern] = explode('|', $pattern, 2);

    if ($method !== $httpMethod) {
        continue;
    }

    if (preg_match('#^' . $routePattern . '$#', $path, $matches)) {
        [$controllerClass, $methodName] = $handler;
        array_shift($matches);

        try {
            $controller = new $controllerClass();
            $reflection = new ReflectionMethod($controller, $methodName);
            $params = $reflection->getParameters();

            $resolvedParams = [];
            foreach ($params as $param) {
                $type = $param->getType();
                $className = ($type instanceof \ReflectionNamedType) ? $type->getName() : null;

                if ($className && class_exists($className)) {
                    $resolvedParams[] = new $className();
                } else {
                    $value = array_shift($matches);
                    $resolvedParams[] = $value !== null ? (int)$value : null;
                }
            }

            $reflection->invokeArgs($controller, $resolvedParams);
            $matched = true;
            break;
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 422);
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 400);
        } catch (\Throwable $e) {
            Response::error('Erro interno no servidor.', 500);
        }
    }
}

if (!$matched) {
    Response::error('Rota no encontrada.', 404);
}

// Middleware CORS
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = ['http://localhost:8000', 'http://127.0.0.1:8000', 'http://localhost:3000'];

if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}


