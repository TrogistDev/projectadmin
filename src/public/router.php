<?php

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $uri;

if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    return false;
}

if (strpos($uri, '/api/') === 0) {
    require __DIR__ . '/../api/index.php';
    return;
}

if ($uri === '/debug') {
    require __DIR__ . '/debug.php';
    return;
}

require __DIR__ . '/index.html';
