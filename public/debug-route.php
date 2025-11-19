<?php

$app = require __DIR__ . '/../bootstrap/app.php';

use App\Core\Request;
use App\Core\Router;

header('Content-Type: text/plain');

echo "=== Route Debug Information ===\n\n";

echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'NOT SET') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'NOT SET') . "\n";
echo "REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'NOT SET') . "\n\n";

$request = new Request();
echo "Normalized URI: " . $request->uri() . "\n";
echo "Method: " . $request->method() . "\n\n";

$router = new Router();
$router->load(base_path('routes/platform.php'));

echo "=== Registered Routes ===\n";
$reflection = new ReflectionClass($router);
$routesProperty = $reflection->getProperty('routes');
$routesProperty->setAccessible(true);
$routes = $routesProperty->getValue($router);

foreach ($routes as $method => $methodRoutes) {
    foreach ($methodRoutes as $uri => $action) {
        if (str_contains($uri, 'license')) {
            echo "$method $uri\n";
        }
    }
}

echo "\n=== Matching Route ===\n";
$method = $request->method();
$uri = $request->uri();
$action = $routes[$method][$uri] ?? null;

if ($action) {
    echo "Found: $method $uri\n";
    if (is_array($action)) {
        echo "Controller: " . $action[0] . "\n";
        echo "Method: " . $action[1] . "\n";
    }
} else {
    echo "NOT FOUND: $method $uri\n";
    echo "\nAvailable routes for $method:\n";
    foreach ($routes[$method] ?? [] as $routeUri => $routeAction) {
        echo "  $routeUri\n";
    }
}

