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

        if (self::$cachedUser !== null) {
            return self::$cachedUser;
        }

        $repository = new UserRepository();
        $user = $repository->find((int)$_SESSION['user_id']);

        if (!$user) {
            self::logout();
            throw new RuntimeException('User not found');
        }

        // Single installation - no tenant logic needed
        $role = $_SESSION['role_override'] ?? $user['role_key'];
        $user['role'] = $role;
        $user['permissions'] = self::permissionsFor($role);

        return self::$cachedUser = $user;
    }

    public static function role(): string
    {
        return self::user()['role'];
    }

    public static function can(string $permission): bool
    {
        $permissions = self::user()['permissions'];

        if (in_array('*', $permissions, true)) {
            return true;
        }

        return in_array($permission, $permissions, true);
    }

    public static function attempt(string $email, string $password): bool
    {
        $repository = new UserRepository();
        $user = $repository->findByEmail($email);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            unset($_SESSION['role_override']);
            self::$cachedUser = null;
            return true;
        }

        return false;
    }

    public static function logout(): void
    {
        unset($_SESSION['user_id'], $_SESSION['role_override']);
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
        $exemptRoles = ['admin', 'director', 'tech_admin'];
        
        if (!in_array($role, $exemptRoles, true)) {
            $attendanceRepo = new \App\Repositories\AttendanceRepository();
            $overrideRepo = new \App\Repositories\LoginOverrideRepository();
            
            // Check if user has been checked out today
            $isCheckedOut = $attendanceRepo->isCheckedOut($user['id']);
            
            if ($isCheckedOut) {
                // Check for active override
                $override = $overrideRepo->getActiveOverride($user['id']);
                
                if (!$override) {
                    // User has been checked out and no override - log them out
                    self::logout();
                    header('Location: ' . base_url('staff/login?error=' . urlencode('You have been logged out because you have ended your shift. Please contact an administrator for access.')));
                    exit;
                }
            }
        }

        if ($roles && !in_array(self::role(), $roles, true)) {
            // Show error message with redirect
            show_message('error', 'Access Forbidden', 'You do not have permission to access this page.', base_url('staff/dashboard'), 5);
            exit;
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
}


