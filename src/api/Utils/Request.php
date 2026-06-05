<?php

declare(strict_types=1);

namespace Api\Utils;

class Request
{
    private array $body;
    private array $query;
    private array $server;

    public function __construct()
    {
        $this->server = $_SERVER;
        $this->query = $this->sanitize($_GET);
        $this->body = $this->parseBody();
        
        // RIGOR: Limpeza de memória residual global do PHP
        $_POST = [];
        $_GET = [];
        $_REQUEST = [];
    }

    private function parseBody(): array
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            return [];
        }

        return $this->sanitize($data);
    }

    private function sanitize(array $data): array
    {
        $clean = [];

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // 🟢 EXCEÇÃO DE SEGURANÇA: Campos confidenciais de credenciais brutas (senhas/tokens)
                // NÃO passam por htmlspecialchars para evitar mutação de caracteres válidos.
                if (in_array($key, ['contrasena', 'password', 'token', 'contrasena_verificar'], true)) {
                    $clean[$key] = trim($value);
                } else {
                    $clean[$key] = trim(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
                }
            } elseif (is_array($value)) {
                $clean[$key] = $this->sanitize($value);
            } else {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }

    public function getBody(): array
    {
        return $this->body;
    }

    public function getQuery(): array
    {
        return $this->query;
    }

    public function getMethod(): string
    {
        return $this->server['REQUEST_METHOD'] ?? 'GET';
    }

    public function getPath(): string
    {
        return parse_url($this->server['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    }
}