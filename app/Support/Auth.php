<?php

namespace App\Support;

use App\Repositories\UserRepository;
use RuntimeException;

class Auth
{
    protected static ?array $cachedUser = null;

    public static function user(): array
    {
        if (!self::check()) {
            throw new RuntimeException('Not authenticated');
        }

        // SECURITY: Validate session hasn't been tampered with - CRITICAL CHECK
        if (isset($_SESSION['__original_user_id']) && isset($_SESSION['user_id'])) {
            if ($_SESSION['user_id'] != $_SESSION['__original_user_id'] && !isset($_SESSION['__auth_modified'])) {
                $originalId = $_SESSION['__original_user_id'];
                $currentId = $_SESSION['user_id'];
                $backtrace = array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10), 0, 5);
                error_log('SECURITY ALERT: Session hijacking detected in Auth::user(). Original: ' . $originalId . ', Current: ' . $currentId . ', IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . ', URI: ' . ($_SERVER['REQUEST_URI'] ?? 'unknown') . ', GET: ' . json_encode($_GET) . ', POST: ' . json_encode($_POST) . ', Backtrace: ' . json_encode($backtrace));
                // Restore original IMMEDIATELY
                $_SESSION['user_id'] = $originalId;
                unset($_SESSION['__auth_modified']);
                // Clear cache to force reload with correct user
                self::$cachedUser = null;
            }
        }

        // Additional validation: ensure original_user_id is always set if user_id exists
        if (isset($_SESSION['user_id']) && !isset($_SESSION['__original_user_id'])) {
            $_SESSION['__original_user_id'] = $_SESSION['user_id'];
        }

        if (self::$cachedUser !== null) {
            // Validate cached user matches current session
            if (isset(self::$cachedUser['id']) && isset($_SESSION['user_id'])) {
                if ((int)self::$cachedUser['id'] !== (int)$_SESSION['user_id']) {
                    // Cached user doesn't match session - clear cache
                    self::$cachedUser = null;
                }
            }
            if (self::$cachedUser !== null) {
                return self::$cachedUser;
            }
        }

        $repository = new UserRepository();
        $user = $repository->find((int)$_SESSION['user_id']);

        if (!$user) {
            self::logout();
            throw new RuntimeException('User not found');
        }

        // Load all roles for the user
        $userRoles = $repository->getUserRoles($user['id']);
        $user['roles'] = $userRoles;
        $user['role_keys'] = array_column($userRoles, 'role_key');
        
        // Get primary role for backward compatibility
        $primaryRole = $repository->getPrimaryRole($user['id']);
        if (!$primaryRole && !empty($user['role_keys'])) {
            $primaryRole = $user['role_keys'][0];
        }
        
        // Single installation - no tenant logic needed
        $role = $_SESSION['role_override'] ?? $primaryRole ?? $user['role_key'];
        $user['role'] = $role;
        $user['role_key'] = $primaryRole ?? $user['role_key'] ?? '';
        
        // Combine permissions from all roles
        $user['permissions'] = self::combinedPermissionsFor($user['role_keys']);

        return self::$cachedUser = $user;
    }

    public static function role(): string
    {
        return self::user()['role'];
    }

    /**
     * Check if the current user has a specific role
     */
    public static function hasRole(string $roleKey): bool
    {
        $user = self::user();
        $roleKeys = $user['role_keys'] ?? [];
        
        // If user has no roles array, fall back to single role_key
        if (empty($roleKeys) && isset($user['role_key'])) {
            $roleKeys = [$user['role_key']];
        }
        
        return in_array($roleKey, $roleKeys, true);
    }

    /**
     * Get all roles for the current user
     */
    public static function roles(): array
    {
        $user = self::user();
        return $user['role_keys'] ?? [];
    }

    public static function can(string $permission): bool
    {
        $permissions = self::user()['permissions'];

        if (in_array('*', $permissions, true)) {
            return true;
        }

        return in_array($permission, $permissions, true);
    }

    public static function attempt(string $identifier, string $password): bool
    {
        $repository = new UserRepository();
        // Support both username and email for login
        $user = $repository->findByUsernameOrEmail($identifier);

        if ($user && password_verify($password, $user['password'])) {
            // Mark this as a legitimate session modification BEFORE setting user_id
            $_SESSION['__auth_modified'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['__original_user_id'] = $user['id']; // Store as reference for validation
            $_SESSION['__session_init'] = true; // Ensure session is marked as initialized
            $_SESSION['__session_locked'] = true; // Lock session
            $_SESSION['__session_fingerprint'] = md5($user['id'] . ($_SERVER['REMOTE_ADDR'] ?? '') . ($_SERVER['HTTP_USER_AGENT'] ?? '')); // Create fingerprint
            unset($_SESSION['role_override']);
            self::$cachedUser = null;
            return true;
        }

        return false;
    }

    public static function logout(): void
    {
        // Mark this as a legitimate session modification
        $_SESSION['__auth_modified'] = true;
        
        // Clear all tenant session variables
        unset(
            $_SESSION['user_id'], 
            $_SESSION['role_override'], 
            $_SESSION['__original_user_id'],
            $_SESSION['__session_init'],
            $_SESSION['__session_locked'],
            $_SESSION['__session_fingerprint'],
            $_SESSION['__session_init_time']
        );
        
        // Clear cached user
        self::$cachedUser = null;
    }

    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function requireRoles(array $roles = []): void
    {
        if (!self::check()) {
            $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '/');
            header('Location: ' . base_url('staff/login?redirect=' . $redirect));
            exit;
        }

        // Check attendance status for non-exempt roles
        $user = self::user();
        $role = $user['role_key'] ?? '';
        $exemptRoles = ['admin', 'director', 'tech_admin', 'security']; // Admin, Director, Tech Admin, and Security staff don't need check-in
        
        if (!in_array($role, $exemptRoles, true)) {
            $attendanceRepo = new \App\Repositories\AttendanceRepository();
            $overrideRepo = new \App\Repositories\LoginOverrideRepository();
            
            // Check if user is currently present
            $isPresent = $attendanceRepo->isPresent($user['id']);
            
            // Check for active override
            $override = $overrideRepo->getActiveOverride($user['id']);
            
            if (!$isPresent && !$override) {
                // User is not present and no override - log them out
                self::logout();
                header('Location: ' . base_url('staff/login?error=' . urlencode('You have been logged out because you are not marked as present. Please check in at the security desk.')));
                exit;
            }
            
            // Check if user was checked out (revoke session immediately)
            $isCheckedOut = $attendanceRepo->isCheckedOut($user['id']);
            if ($isCheckedOut && !$override) {
                self::logout();
                header('Location: ' . base_url('staff/login?error=' . urlencode('You have been logged out because you have ended your shift. Please contact an administrator for access.')));
                exit;
            }
        }

        // Director has access to everything - bypass role checks
        $user = self::user();
        $userRoles = $user['role_keys'] ?? [];
        
        // If user has no roles array, fall back to single role_key
        if (empty($userRoles) && isset($user['role_key'])) {
            $userRoles = [$user['role_key']];
        }
        
        // Check if user is director or admin (system-level roles)
        if (in_array('director', $userRoles, true) || in_array('admin', $userRoles, true)) {
            return; // Director/Admin has full access
        }

        // If roles are specified, check if user has any of them
        if ($roles && !empty($roles)) {
            $hasRequiredRole = false;
            foreach ($userRoles as $userRole) {
                if (in_array($userRole, $roles, true)) {
                    $hasRequiredRole = true;
                    break;
                }
            }
            
            if (!$hasRequiredRole) {
                // Show error message with redirect
                show_message('error', 'Access Forbidden', 'You do not have permission to access this page.', base_url('staff/dashboard'), 5);
                exit;
            }
        }
    }

    public static function setRole(string $role): void
    {
        if (!self::check()) {
            return;
        }

        $roles = array_keys(config('roles', []));
        if (in_array($role, $roles, true)) {
            $_SESSION['role_override'] = $role;
            self::$cachedUser = null;
        }
    }

    protected static function permissionsFor(string $role): array
    {
        $roles = config('roles', []);

        return $roles[$role]['permissions'] ?? [];
    }

    /**
     * Combine permissions from multiple roles (union of all permissions)
     */
    protected static function combinedPermissionsFor(array $roleKeys): array
    {
        $roles = config('roles', []);
        $allPermissions = [];
        $hasWildcard = false;

        foreach ($roleKeys as $roleKey) {
            $rolePermissions = $roles[$roleKey]['permissions'] ?? [];
            
            // If any role has wildcard, user has all permissions
            if (in_array('*', $rolePermissions, true)) {
                $hasWildcard = true;
                break;
            }
            
            $allPermissions = array_merge($allPermissions, $rolePermissions);
        }

        if ($hasWildcard) {
            return ['*'];
        }

        // Remove duplicates and return unique permissions
        return array_values(array_unique($allPermissions));
    }

    public static function clearCache(): void
    {
        self::$cachedUser = null;
    }
}


