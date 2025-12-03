<?php

namespace App\Core;

class Router
{
    protected array $routes = [];


    public function load(string $routesFile): void
    {
        if (!file_exists($routesFile)) {
            return;
        }

        $routes = require $routesFile;

        foreach ($routes as $route) {
            // Support both old format [method, uri, action] and new format [method, uri, action, middleware]
            if (count($route) >= 3) {
                [$method, $uri, $action] = $route;
                $middleware = $route[3] ?? null;
                $this->register($method, $uri, $action, $middleware);
            }
        }
    }
    
    public function register(string $method, string $uri, callable|array $action, ?string $middleware = null): void
    {
        $this->routes[strtoupper($method)][rtrim($uri, '/') ?: '/'] = [
            'action' => $action,
            'middleware' => $middleware
        ];
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $uri = $request->uri();

        $route = $this->routes[$method][$uri] ?? null;

        if (!$route) {
            http_response_code(404);
            echo 'Page not found';
            return;
        }

        // Handle both old format (direct action) and new format (with middleware)
        $action = is_array($route) && isset($route['action']) ? $route['action'] : $route;
        $middleware = is_array($route) && isset($route['middleware']) ? $route['middleware'] : null;

        // Auto-apply middleware based on URI patterns (unless explicitly set)
        if (!$middleware) {
            $middleware = $this->detectMiddleware($uri);
        }

        // Apply middleware if specified
        if ($middleware) {
            $this->applyMiddleware($middleware);
        }

        if (is_array($action)) {
            [$controller, $methodName] = $action;
            $controllerInstance = new $controller();
            $controllerInstance->{$methodName}($request);
            return;
        }

        call_user_func($action, $request);
    }

    protected function detectMiddleware(string $uri): ?string
    {
        // System admin routes
        if (str_starts_with($uri, '/sysadmin')) {
            // Exclude login/logout from auth requirement
            if (!in_array($uri, ['/sysadmin/login', '/sysadmin/logout'])) {
                return 'SystemAuth';
            }
            return null;
        }

        // Tenant routes (staff dashboard)
        if (str_starts_with($uri, '/staff/dashboard') || str_starts_with($uri, '/staff/admin')) {
            // Exclude login/logout from auth requirement
            if (!in_array($uri, ['/staff/login', '/staff/logout', '/staff'])) {
                return 'TenantAuth';
            }
            return null;
        }

        return null;
    }

    protected function applyMiddleware(string $middleware): void
    {
        $middlewareClass = "App\\Middleware\\{$middleware}";
        if (class_exists($middlewareClass)) {
            $instance = new $middlewareClass();
            if (method_exists($instance, 'handle')) {
                $instance->handle();
            }
        }
    }
}


