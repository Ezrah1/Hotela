<?php

namespace App\Core;

class Router
{
    protected array $routes = [];

    public function register(string $method, string $uri, callable|array $action): void
    {
        $this->routes[strtoupper($method)][rtrim($uri, '/') ?: '/'] = $action;
    }

    public function load(string $routesFile): void
    {
        if (!file_exists($routesFile)) {
            return;
        }

        $routes = require $routesFile;

        foreach ($routes as $route) {
            [$method, $uri, $action] = $route;
            $this->register($method, $uri, $action);
        }
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $uri = $request->uri();

        $action = $this->routes[$method][$uri] ?? null;

        if (!$action) {
            http_response_code(404);
            echo 'Page not found';
            return;
        }

        if (is_array($action)) {
            [$controller, $methodName] = $action;
            $controllerInstance = new $controller();
            $controllerInstance->{$methodName}($request);
            return;
        }

        call_user_func($action, $request);
    }
}


