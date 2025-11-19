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

// Check license status (except for license renewal page)
if (!str_starts_with($request->uri(), '/license')) {
    $licensingService = new \App\Services\LicensingService();
    if ($licensingService->isLocked()) {
        // Allow access to license renewal page
        if (str_starts_with($request->uri(), '/staff/dashboard/license')) {
            // Continue to license page
        } else {
            header('Location: /staff/dashboard/license');
            exit;
        }
    }
}

// Require authentication for protected routes (staff area)
$protectedPrefixes = ['/staff/dashboard', '/staff/admin', '/sysadmin'];
foreach ($protectedPrefixes as $prefix) {
    if (str_starts_with($request->uri(), $prefix)) {
        \App\Support\Auth::requireRoles();
        break;
    }
}

$router->dispatch($request);


