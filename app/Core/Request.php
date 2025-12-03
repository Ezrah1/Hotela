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
        
        // Handle request body for POST/PUT/PATCH
        if ($this->method() === 'POST' || $this->method() === 'PUT' || $this->method() === 'PATCH') {
            $contentType = $this->server['CONTENT_TYPE'] ?? '';
            
            // Handle JSON request body
            if (str_contains($contentType, 'application/json')) {
                $jsonInput = file_get_contents('php://input');
                if ($jsonInput) {
                    $jsonData = json_decode($jsonInput, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
                        $this->body = array_merge($this->body, $jsonData);
                    }
                }
            }
            // Handle form-encoded request body
            // PHP should auto-populate $_POST, but if it's empty, parse manually from php://input
            elseif (str_contains($contentType, 'application/x-www-form-urlencoded')) {
                // If $_POST is empty, try parsing from php://input
                if (empty($this->body)) {
                    $input = file_get_contents('php://input');
                    if ($input) {
                        parse_str($input, $parsed);
                        if (is_array($parsed) && !empty($parsed)) {
                            $this->body = $parsed;
                        }
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
        // First check $_POST directly (most reliable for standard form submissions)
        if (isset($_POST[$key])) {
            return $_POST[$key];
        }
        // Then check our parsed body
        if (isset($this->body[$key])) {
            return $this->body[$key];
        }
        // Finally check query params
        if (isset($this->query[$key])) {
            return $this->query[$key];
        }
        return $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }
}


