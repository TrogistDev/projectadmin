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

        $rawQuery = $_GET;
        if (empty($rawQuery) && !empty($_SERVER['QUERY_STRING'])) {
            parse_str($_SERVER['QUERY_STRING'], $rawQuery);
        }
        $this->query = $this->sanitize($rawQuery);

        $this->body = $this->parseBody();

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
                $trimmed = trim($value);
                if ($trimmed === '' || $trimmed === null) {
                    continue;
                }
                if (in_array($key, ['contrasena', 'password', 'token', 'contrasena_verificar'], true)) {
                    $clean[$key] = $trimmed;
                } else {
                    $clean[$key] = htmlspecialchars($trimmed, ENT_QUOTES, 'UTF-8');
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
        $query = $this->query;
        
        $map = [
            'filter-start-date' => 'date_start',
            'filter-end-date' => 'date_end',
        ];
        
        foreach ($map as $old => $new) {
            if (isset($query[$old]) && $query[$old] !== '') {
                $query[$new] = $query[$old];
            }
        }
        
        return $query;
    }

    public function getMethod(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function getPath(): string
    {
        return parse_url($this->server['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    }
}