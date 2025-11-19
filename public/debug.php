<?php
// Temporary debug file to check what the app sees
// Bootstrap the app first to get access to helper functions
$app = require __DIR__ . '/../bootstrap/app.php';

header('Content-Type: text/plain');

echo "=== Debug Information ===\n\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'NOT SET') . "\n";
echo "X-Forwarded-Host: " . ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['X-Forwarded-Host'] ?? 'NOT SET') . "\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'NOT SET') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'NOT SET') . "\n";
echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'NOT SET') . "\n\n";

echo "=== Domain Config ===\n";
$domains = config('domains');
echo "Platform Domain: " . ($domains['platform_domain'] ?? 'NOT SET') . "\n";
echo "Default Tenant Domain: " . ($domains['default_tenant_domain'] ?? 'NOT SET') . "\n\n";

echo "=== Domain Recognition ===\n";
$platformDomain = $domains['platform_domain'] ?? '';
$host = $_SERVER['HTTP_HOST'] ?? '';
$forwardedHost = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['X-Forwarded-Host'] ?? '';
$actualHost = $forwardedHost ?: $host;

// Normalize (same logic as index.php)
$hostNormalized = strtolower(explode(':', $host)[0]);
$actualHostNormalized = strtolower(explode(':', $actualHost)[0]);
$platformDomainNormalized = strtolower(explode(':', $platformDomain)[0]);

$isPlatform = false;
if ($platformDomain) {
    $isPlatform = ($hostNormalized === $platformDomainNormalized || $actualHostNormalized === $platformDomainNormalized);
}
if (!$isPlatform) {
    $isPlatform = ($hostNormalized === 'platform.local' || $actualHostNormalized === 'platform.local');
}

echo "Host (normalized): $hostNormalized\n";
echo "Actual Host (normalized): $actualHostNormalized\n";
echo "Platform Domain (normalized): $platformDomainNormalized\n";
echo "Is Platform: " . ($isPlatform ? 'YES' : 'NO') . "\n";
echo "Would load routes: " . ($isPlatform ? 'routes/platform.php' : 'routes/tenant.php') . "\n\n";

if (!$isPlatform) {
    echo "=== Tenant Resolution Attempt ===\n";
    $tenantRepo = new \App\Repositories\TenantRepository();
    $tenant = $tenantRepo->findByDomain($host);
    echo "Tenant found: " . ($tenant ? 'YES (ID: ' . $tenant['id'] . ')' : 'NO') . "\n\n";
}

echo "=== All Headers ===\n";
foreach ($_SERVER as $key => $value) {
    if (str_starts_with($key, 'HTTP_') || in_array($key, ['HTTP_HOST', 'REQUEST_URI', 'SCRIPT_NAME', 'SERVER_NAME'])) {
        echo "$key: $value\n";
    }
}

