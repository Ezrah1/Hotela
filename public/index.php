<?php

declare(strict_types=1);

$app = require __DIR__ . '/../bootstrap/app.php';

use App\Core\Request;
use App\Core\Router;

$router = new Router();

$domains = config('domains');
$platformDomain = $domains['platform_domain'] ?? '';
$host = $_SERVER['HTTP_HOST'] ?? '';

// Support both platform.local (local dev) and hotela.ezrahkiilu.com (production)
// Also check X-Forwarded-Host header in case tunnel modifies the host
$forwardedHost = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['X-Forwarded-Host'] ?? '';
$actualHost = $forwardedHost ?: $host;

// Normalize hosts (remove port if present)
$host = strtolower(explode(':', $host)[0]);
$actualHost = strtolower(explode(':', $actualHost)[0]);
$platformDomain = strtolower(explode(':', $platformDomain)[0]);

// Check if this is a platform domain
$isPlatform = false;
if ($platformDomain) {
    $isPlatform = ($host === $platformDomain || $actualHost === $platformDomain);
}
// Also support platform.local for local development
if (!$isPlatform) {
    $isPlatform = ($host === 'platform.local' || $actualHost === 'platform.local');
}

// Single installation - always load platform routes
$router->load(base_path('routes/platform.php'));

$request = new Request();

// Check license status (except for license renewal page and login)
if (!str_starts_with($request->uri(), '/license') && !str_starts_with($request->uri(), '/login') && !str_starts_with($request->uri(), '/staff/login')) {
    $licensingService = new \App\Services\LicensingService();
    if ($licensingService->isLocked()) {
        // Allow access to license renewal page and logout
        if (str_starts_with($request->uri(), '/staff/dashboard/license') || 
            str_starts_with($request->uri(), '/logout') ||
            str_starts_with($request->uri(), '/staff/logout')) {
            // Continue to license page or logout
        } else {
            header('Location: ' . base_url('staff/dashboard/license'));
            exit;
        }
    }
}

// Require authentication for protected routes (staff area)
// Note: /sysadmin routes are handled by SystemAuth middleware, not here
$protectedPrefixes = ['/staff/dashboard', '/staff/admin'];
$uri = $request->uri();
foreach ($protectedPrefixes as $prefix) {
    if (str_starts_with($uri, $prefix)) {
        \App\Support\Auth::requireRoles();
        break;
    }
}
// Don't apply tenant auth to sysadmin routes - they use SystemAuth middleware

$router->dispatch($request);


