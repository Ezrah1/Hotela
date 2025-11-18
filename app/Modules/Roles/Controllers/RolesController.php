<?php

namespace App\Modules\Roles\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Support\Auth;

class RolesController extends Controller
{
    public function index(Request $request): void
    {
        Auth::requireRoles(['admin']);

        // Get all roles from config (exclude super_admin from tenant view)
        $rolesConfig = config('roles', []);
        unset($rolesConfig['super_admin']); // Don't show super_admin to tenant admins
        
        // Get role counts from database (tenant-scoped)
        $tenantId = \App\Support\Tenant::id();
        $roleCounts = [];
        
        foreach (array_keys($rolesConfig) as $roleKey) {
            $params = ['role_key' => $roleKey];
            $sql = 'SELECT COUNT(*) as count FROM users WHERE role_key = :role_key AND status = "active"';
            
            if ($tenantId !== null) {
                $sql .= ' AND tenant_id = :tenant_id';
                $params['tenant_id'] = $tenantId;
            }
            
            $stmt = db()->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            $roleCounts[$roleKey] = (int)($result['count'] ?? 0);
        }

        $this->view('dashboard/roles/index', [
            'roles' => $rolesConfig,
            'roleCounts' => $roleCounts,
        ]);
    }

    public function editRole(Request $request): void
    {
        Auth::requireRoles(['admin']);

        $roleKey = $request->input('role');
        if (!$roleKey) {
            header('Location: ' . base_url('dashboard/roles?error=Invalid%20role'));
            return;
        }

        $rolesConfig = config('roles', []);
        if (!isset($rolesConfig[$roleKey])) {
            header('Location: ' . base_url('dashboard/roles?error=Role%20not%20found'));
            return;
        }

        $role = $rolesConfig[$roleKey];
        
        // Get all available permissions from database
        $allPermissions = db()->query('SELECT * FROM permissions ORDER BY `key` ASC')->fetchAll();
        
        // Get role's current permissions from database
        $roleDb = db()->prepare('SELECT id FROM roles WHERE `key` = :key LIMIT 1');
        $roleDb->execute(['key' => $roleKey]);
        $roleData = $roleDb->fetch();
        
        $currentPermissions = [];
        if ($roleData) {
            $rolePerms = db()->prepare('
                SELECT permissions.`key` 
                FROM role_permissions 
                JOIN permissions ON permissions.id = role_permissions.permission_id 
                WHERE role_permissions.role_id = :role_id
            ');
            $rolePerms->execute(['role_id' => $roleData['id']]);
            $currentPermissions = array_column($rolePerms->fetchAll(), 'key');
        }

        $this->view('dashboard/roles/edit', [
            'roleKey' => $roleKey,
            'role' => $role,
            'allPermissions' => $allPermissions,
            'currentPermissions' => $currentPermissions,
        ]);
    }

    public function updateRole(Request $request): void
    {
        Auth::requireRoles(['admin']);

        $roleKey = $request->input('role_key');
        $permissions = $request->input('permissions', []);
        
        if (!$roleKey) {
            header('Location: ' . base_url('dashboard/roles?error=Invalid%20role'));
            return;
        }

        // Get role from database
        $roleStmt = db()->prepare('SELECT id FROM roles WHERE `key` = :key LIMIT 1');
        $roleStmt->execute(['key' => $roleKey]);
        $roleData = $roleStmt->fetch();

        if (!$roleData) {
            header('Location: ' . base_url('dashboard/roles?error=Role%20not%20found'));
            return;
        }

        $roleId = (int)$roleData['id'];

        // Start transaction
        db()->beginTransaction();
        try {
            // Delete existing permissions
            db()->prepare('DELETE FROM role_permissions WHERE role_id = :role_id')->execute(['role_id' => $roleId]);

            // Add new permissions
            if (!empty($permissions) && is_array($permissions)) {
                $insertStmt = db()->prepare('
                    INSERT INTO role_permissions (role_id, permission_id)
                    SELECT :role_id, permissions.id
                    FROM permissions
                    WHERE permissions.`key` = :permission_key
                ');
                
                foreach ($permissions as $permissionKey) {
                    $insertStmt->execute([
                        'role_id' => $roleId,
                        'permission_key' => $permissionKey,
                    ]);
                }
            }

            db()->commit();
            header('Location: ' . base_url('dashboard/roles?success=Role%20permissions%20updated'));
        } catch (\Exception $e) {
            db()->rollBack();
            header('Location: ' . base_url('dashboard/roles?error=' . urlencode($e->getMessage())));
        }
    }
}

