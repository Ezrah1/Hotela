<?php

namespace App\Middleware;

use App\Support\Auth;

/**
 * Middleware for tenant routes (/staff, /admin)
 * Only allows director and below roles (tenant users)
 */
class TenantAuth
{
    public function handle(): void
    {
        if (!Auth::check()) {
            $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '/');
            header('Location: ' . base_url('staff/login?redirect=' . $redirect));
            exit;
        }

        $user = Auth::user();
        $role = $user['role_key'] ?? '';

        // Define tenant roles (director is highest, excludes admin and super_admin)
        $tenantRoles = [
            'director',
            'tech',
            'finance_manager',
            'operation_manager',
            'receptionist',
            'cashier',
            'service_agent',
            'kitchen',
            'housekeeping',
            'ground',
            'security'
        ];

        // Block system admin roles from tenant routes
        $systemRoles = ['admin', 'super_admin'];
        if (in_array($role, $systemRoles, true)) {
            http_response_code(403);
            die('Access Forbidden: System administrators must use the system admin panel.');
        }

        // Ensure user has a valid tenant role
        if (!in_array($role, $tenantRoles, true)) {
            http_response_code(403);
            die('Access Forbidden: Invalid role for tenant access.');
        }

        // Check attendance status for non-exempt roles (same as Auth::requireRoles)
        $exemptRoles = ['director', 'tech', 'security'];
        if (!in_array($role, $exemptRoles, true)) {
            $attendanceRepo = new \App\Repositories\AttendanceRepository();
            $overrideRepo = new \App\Repositories\LoginOverrideRepository();
            
            $isPresent = $attendanceRepo->isPresent($user['id']);
            $override = $overrideRepo->getActiveOverride($user['id']);
            
            if (!$isPresent && !$override) {
                Auth::logout();
                header('Location: ' . base_url('staff/login?error=' . urlencode('You have been logged out because you are not marked as present. Please check in at the security desk.')));
                exit;
            }
            
            $isCheckedOut = $attendanceRepo->isCheckedOut($user['id']);
            if ($isCheckedOut && !$override) {
                Auth::logout();
                header('Location: ' . base_url('staff/login?error=' . urlencode('You have been logged out because you have ended your shift. Please contact an administrator for access.')));
                exit;
            }
        }
    }
}

