<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Support/helpers.php';

define('BASE_PATH', dirname(__DIR__));

$dotenvPath = BASE_PATH . DIRECTORY_SEPARATOR . '.env';
if (file_exists($dotenvPath)) {
    $lines = file($dotenvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }

        $parts = explode('=', $trimmed, 2);
        if (count($parts) !== 2) {
            continue;
        }

        [$name, $value] = array_map('trim', $parts);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

$config = require config_path('app.php');

// Set timezone - prioritize system_settings table, fall back to config
try {
    $systemRepo = new \App\Repositories\SystemSettingsRepository();
    $timezone = $systemRepo->get('timezone');
    if ($timezone) {
        date_default_timezone_set($timezone);
    } else {
        date_default_timezone_set($config['timezone']);
    }
} catch (\Throwable $e) {
    // Fall back to config if system_settings table doesn't exist yet
    date_default_timezone_set($config['timezone']);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// SECURITY: Store original user_id IMMEDIATELY after session start, before any code runs
// ALWAYS set original_user_id if user_id exists, even if session_locked is already set
// This ensures we always have a reference point
if (isset($_SESSION['user_id'])) {
    $currentUserId = (int)$_SESSION['user_id'];
    
    // If we don't have an original_user_id yet, set it now
    if (!isset($_SESSION['__original_user_id'])) {
        $_SESSION['__original_user_id'] = $currentUserId;
        $_SESSION['__session_init_time'] = time();
        $_SESSION['__session_fingerprint'] = md5($currentUserId . ($_SERVER['REMOTE_ADDR'] ?? '') . ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    } else {
        // Validate fingerprint hasn't changed (prevents session fixation)
        $expectedFingerprint = md5($_SESSION['__original_user_id'] . ($_SERVER['REMOTE_ADDR'] ?? '') . ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        if (isset($_SESSION['__session_fingerprint']) && $_SESSION['__session_fingerprint'] !== $expectedFingerprint && !isset($_SESSION['__auth_modified'])) {
            error_log('SECURITY ALERT: Session fingerprint mismatch detected. Possible session fixation or hijacking. IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            // Don't restore if fingerprint changed - this might be legitimate IP change
            // But log it for investigation
        }
    }
    // Always update session_locked to ensure protection is active
    $_SESSION['__session_locked'] = true;
}

// SECURITY: Block user_id in GET/REQUEST to prevent session hijacking
// BUT allow it in POST for legitimate operations like attendance check-in, grant override, etc.
// Only block GET parameters and REQUEST if not in POST (which is safe for forms)
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$allowedPostRoutes = [
    '/staff/dashboard/attendance/check-in',
    '/staff/dashboard/attendance/check-out',
    '/staff/dashboard/attendance/grant-override',
];

$isAllowedPostRoute = false;
if ($_SERVER['REQUEST_METHOD'] ?? '' === 'POST') {
    foreach ($allowedPostRoutes as $route) {
        if (strpos($requestUri, $route) !== false) {
            $isAllowedPostRoute = true;
            break;
        }
    }
}

// Block user_id in GET (always a security risk)
if (isset($_GET['user_id'])) {
    error_log('SECURITY ALERT: Blocked attempt to modify user_id via GET parameter. IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . ' URI: ' . $requestUri);
    unset($_GET['user_id'], $_REQUEST['user_id']);
}

// Block user_id in POST only if it's NOT an allowed route (legitimate form submissions)
// Also block if it's in REQUEST but not from POST (could be GET or cookie manipulation)
if (!$isAllowedPostRoute && isset($_POST['user_id']) && !isset($_SESSION['__auth_modified'])) {
    error_log('SECURITY ALERT: Blocked attempt to modify user_id via POST parameter on unauthorized route. IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . ' URI: ' . $requestUri);
    unset($_POST['user_id'], $_REQUEST['user_id']);
} elseif (isset($_REQUEST['user_id']) && !isset($_POST['user_id']) && !isset($_SESSION['__auth_modified'])) {
    // Block if it's in REQUEST but not from POST (could be cookie or GET manipulation)
    error_log('SECURITY ALERT: Blocked attempt to modify user_id via REQUEST (not from POST). IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . ' URI: ' . $requestUri);
    unset($_REQUEST['user_id']);
}

// SECURITY: Validate and restore session IMMEDIATELY if tampered with
// This check runs MULTIPLE times to catch any modifications
if (isset($_SESSION['__original_user_id']) && isset($_SESSION['user_id'])) {
    if ($_SESSION['user_id'] != $_SESSION['__original_user_id'] && !isset($_SESSION['__auth_modified'])) {
        $originalId = $_SESSION['__original_user_id'];
        $currentId = $_SESSION['user_id'];
        error_log('SECURITY ALERT: Session hijacking detected in bootstrap. Original: ' . $originalId . ', Current: ' . $currentId . ', IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . ', URI: ' . ($_SERVER['REQUEST_URI'] ?? 'unknown') . ', GET: ' . json_encode($_GET) . ', POST: ' . json_encode($_POST));
        // Force restore immediately - this is CRITICAL
        $_SESSION['user_id'] = $originalId;
        // Clear any auth modified flag that might have been set maliciously
        unset($_SESSION['__auth_modified']);
        // Force clear user cache to prevent using wrong user
        if (class_exists('\App\Support\Auth')) {
            \App\Support\Auth::clearCache();
        }
    }
}

// SECURITY: Final validation - ensure session integrity before any code runs
// This runs AFTER the initial check to catch any modifications that might have happened
if (isset($_SESSION['__original_user_id']) && isset($_SESSION['user_id'])) {
    if ($_SESSION['user_id'] != $_SESSION['__original_user_id'] && !isset($_SESSION['__auth_modified'])) {
        error_log('SECURITY ALERT: Final validation failed - session tampered. Restoring. IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        $_SESSION['user_id'] = $_SESSION['__original_user_id'];
        unset($_SESSION['__auth_modified']);
    }
}

// Clear auth_modified flag ONLY if session is valid and matches original
if (isset($_SESSION['__auth_modified']) && isset($_SESSION['__original_user_id']) && isset($_SESSION['user_id'])) {
    if ($_SESSION['user_id'] == $_SESSION['__original_user_id']) {
        // Session is valid, can clear the flag
        unset($_SESSION['__auth_modified']);
    } else {
        // Session doesn't match - clear flag and restore
        unset($_SESSION['__auth_modified']);
        $_SESSION['user_id'] = $_SESSION['__original_user_id'];
    }
}

// SECURITY: Register shutdown function to validate session one final time before script ends
// This catches any modifications that happen during request processing
register_shutdown_function(function() {
    if (isset($_SESSION['__original_user_id']) && isset($_SESSION['user_id'])) {
        if ($_SESSION['user_id'] != $_SESSION['__original_user_id'] && !isset($_SESSION['__auth_modified'])) {
            error_log('SECURITY ALERT: Session hijacking detected in shutdown function. Original: ' . $_SESSION['__original_user_id'] . ', Current: ' . $_SESSION['user_id'] . ', IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . ', URI: ' . ($_SERVER['REQUEST_URI'] ?? 'unknown'));
            // Restore original
            $_SESSION['user_id'] = $_SESSION['__original_user_id'];
            unset($_SESSION['__auth_modified']);
            // Clear Auth cache if class exists
            if (class_exists('\App\Support\Auth')) {
                \App\Support\Auth::clearCache();
            }
        }
    }
});

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = BASE_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR;

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// License activation check (only for tenant routes, not system admin routes)
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
if (str_starts_with($requestUri, '/staff') && !str_starts_with($requestUri, '/sysadmin')) {
    try {
        // Check if license_activations table exists
        $pdo = \App\Core\Database::connection();
        $tableExists = $pdo->query("SHOW TABLES LIKE 'license_activations'")->fetch() !== false;
        
        if ($tableExists) {
            $licenseRepo = new \App\Repositories\LicenseRepository();
            $isActivated = $licenseRepo->isActivated();
            
            // Only redirect directors to activation page
            if (!$isActivated && !str_starts_with($requestUri, '/staff/login') && !str_starts_with($requestUri, '/staff/dashboard/license')) {
                // Check if user is logged in and is a director
                if (isset($_SESSION['user_id'])) {
                    $userRepo = new \App\Repositories\UserRepository();
                    $user = $userRepo->find((int)$_SESSION['user_id']);
                    if ($user && ($user['role_key'] ?? '') === 'director') {
                        header('Location: ' . base_url('staff/dashboard/license'));
                        exit;
                    }
                }
            }
        }
    } catch (\Throwable $e) {
        // Silently fail if tables don't exist yet (during initial setup)
        error_log('License check failed: ' . $e->getMessage());
    }
    
    // Check for overdue checkouts and notify stakeholders
    // Only check if user is logged in and has relevant role
    if (isset($_SESSION['user_id']) && !str_starts_with($requestUri, '/staff/login')) {
        try {
            $userRepo = new \App\Repositories\UserRepository();
            $user = $userRepo->find((int)$_SESSION['user_id']);
            if ($user) {
                $userRole = $user['role_key'] ?? ($user['role'] ?? null);
                $relevantRoles = ['receptionist', 'cashier', 'operation_manager', 'director', 'finance_manager'];
                
                // Check for overdue checkouts (cache for 1 hour to avoid excessive checks)
                $cacheKey = 'overdue_checkout_check';
                $lastCheck = $_SESSION[$cacheKey] ?? 0;
                $checkInterval = 3600; // 1 hour
                
                if (in_array($userRole, $relevantRoles, true) && (time() - $lastCheck) > $checkInterval) {
                    $overdueService = new \App\Services\PMS\OverdueCheckoutService();
                    $overdueService->checkAndNotify();
                    $_SESSION[$cacheKey] = time();
                }
            }
        } catch (\Throwable $e) {
            // Log error but don't break the request
            error_log('Overdue checkout check failed: ' . $e->getMessage());
        }
    }
}

return [
    'config' => $config,
];


