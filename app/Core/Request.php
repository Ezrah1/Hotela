<?php

namespace App\Core;

class Request
{
    protected array $query;
    protected array $body;
    protected array $server;

    public function __construct()
    {
        $this->query = $_GET ?? [];
        $this->body = $_POST ?? [];
        $this->server = $_SERVER ?? [];
        
        // Handle JSON request body
        if ($this->method() === 'POST' || $this->method() === 'PUT' || $this->method() === 'PATCH') {
            $contentType = $this->server['CONTENT_TYPE'] ?? '';
            if (str_contains($contentType, 'application/json')) {
                $jsonInput = file_get_contents('php://input');
                if ($jsonInput) {
                    $jsonData = json_decode($jsonInput, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
                        $this->body = array_merge($this->body, $jsonData);
                    }
                }
            }
        }
    }

    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function uri(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $uri = strtok($uri, '?');

        $scriptName = $this->server['SCRIPT_NAME'] ?? '';
        $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

        if ($basePath && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }

        if ($uri === '' || $uri === false) {
            return '/';
        }

        $normalized = rtrim($uri, '/');

        return $normalized === '' ? '/' : $normalized;
    }

    public function input(string $key, $default = null)
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }
}


