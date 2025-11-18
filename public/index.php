<?php

declare(strict_types=1);

$app = require __DIR__ . '/../bootstrap/app.php';

use App\Core\Request;
use App\Core\Router;

$router = new Router();

$domains = config('domains');
$platformDomain = $domains['platform_domain'] ?? '';
$host = $_SERVER['HTTP_HOST'] ?? '';
$isPlatform = $platformDomain && strcasecmp($host, $platformDomain) === 0;

$router->load(base_path($isPlatform ? 'routes/platform.php' : 'routes/tenant.php'));

$request = new Request();

if ($isPlatform) {
    $protectedPrefixes = ['/dashboard', '/admin', '/staff'];
    foreach ($protectedPrefixes as $prefix) {
        if (str_starts_with($request->uri(), $prefix)) {
            \App\Support\Auth::requireRoles();
            break;
        }
    }
} else {
    $tenant = \App\Support\Tenant::resolveByDomain($host)
        ?? \App\Support\Tenant::resolveByDomain($domains['default_tenant_domain'] ?? $host);

    if (!$tenant) {
        http_response_code(404);
        exit('Tenant not found');
    }

    \App\Support\Tenant::set($tenant);
}

$router->dispatch($request);


