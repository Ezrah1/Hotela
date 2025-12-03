<?php

namespace App\Middleware;

use App\Repositories\SystemAdminRepository;

/**
 * Middleware for system admin routes (/sysadmin)
 * Only allows system administrators from system_admins table
 */
class SystemAuth
{
    public function handle(): void
    {
        // Check if system admin is logged in
        if (!isset($_SESSION['system_admin_id'])) {
            header('Location: ' . base_url('sysadmin/login'));
            exit;
        }

        // Verify system admin still exists and is active
        $repo = new SystemAdminRepository();
        $admin = $repo->findById((int)$_SESSION['system_admin_id']);

        if (!$admin || !$admin['is_active']) {
            unset($_SESSION['system_admin_id']);
            header('Location: ' . base_url('sysadmin/login?error=' . urlencode('Your system admin access has been revoked.')));
            exit;
        }

        // Update last activity timestamp
        $repo->updateLastActivity($admin['id']);
        
        // Log the action
        $repo->logAction(
            $admin['id'],
            'access',
            'route',
            null,
            ['uri' => $_SERVER['REQUEST_URI'] ?? '']
        );
    }
}

