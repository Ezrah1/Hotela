<?php

namespace App\Modules\Staff\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\UserRepository;
use App\Support\Auth;

class StaffController extends Controller
{
    protected UserRepository $users;

    public function __construct()
    {
        $this->users = new UserRepository();
    }

    public function index(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager']);

        $roleFilter = $request->input('role');
        $statusFilter = $request->input('status');
        $search = $request->input('q');

        $users = $this->users->all($roleFilter ?: null, $statusFilter ?: null, $search ?: null);

        // Get all roles for filter dropdown
        $roles = db()->query('SELECT `key`, name FROM roles ORDER BY name ASC')->fetchAll();

        $currentUserId = Auth::user()['id'] ?? null;

        $this->view('dashboard/staff/index', [
            'users' => $users,
            'roles' => $roles,
            'activeRole' => $roleFilter,
            'activeStatus' => $statusFilter,
            'search' => $search,
            'currentUserId' => $currentUserId,
        ]);
    }

    public function edit(Request $request): void
    {
        Auth::requireRoles(['admin']);

        $userId = (int)$request->input('id');
        if (!$userId) {
            header('Location: ' . base_url('dashboard/staff?error=Invalid%20user'));
            return;
        }

        $user = $this->users->find($userId);
        if (!$user) {
            header('Location: ' . base_url('dashboard/staff?error=User%20not%20found'));
            return;
        }

        // Check tenant access
        
        // Single installation - no tenant access check needed

        $roles = db()->query('SELECT `key`, name FROM roles ORDER BY name ASC')->fetchAll();

        $this->view('dashboard/staff/edit', [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    public function update(Request $request): void
    {
        Auth::requireRoles(['admin']);

        $userId = (int)$request->input('user_id');
        if (!$userId) {
            header('Location: ' . base_url('dashboard/staff?error=Invalid%20user'));
            return;
        }

        $user = $this->users->find($userId);
        if (!$user) {
            header('Location: ' . base_url('dashboard/staff?error=User%20not%20found'));
            return;
        }

        // Check tenant access
        
        // Single installation - no tenant access check needed

        $name = trim($request->input('name', ''));
        $email = trim($request->input('email', ''));
        $roleKey = $request->input('role_key');
        $status = $request->input('status');

        if (!$name || !$email || !$roleKey) {
            header('Location: ' . base_url('dashboard/staff/edit?id=' . $userId . '&error=Missing%20required%20fields'));
            return;
        }

        // Check if email is already taken by another user
        $existing = $this->users->findByEmail($email);
        if ($existing && (int)$existing['id'] !== $userId) {
            header('Location: ' . base_url('dashboard/staff/edit?id=' . $userId . '&error=Email%20already%20in%20use'));
            return;
        }

        // Verify role exists
        $roleCheck = db()->prepare('SELECT `key` FROM roles WHERE `key` = :key LIMIT 1');
        $roleCheck->execute(['key' => $roleKey]);
        if (!$roleCheck->fetch()) {
            header('Location: ' . base_url('dashboard/staff/edit?id=' . $userId . '&error=Invalid%20role'));
            return;
        }

        $this->users->update($userId, [
            'name' => $name,
            'email' => $email,
            'role_key' => $roleKey,
            'status' => $status,
        ]);

        header('Location: ' . base_url('dashboard/staff?success=User%20updated'));
    }
}

