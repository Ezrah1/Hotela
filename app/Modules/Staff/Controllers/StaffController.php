<?php

namespace App\Modules\Staff\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\UserRepository;
use App\Support\Auth;
use PDO;

class StaffController extends Controller
{
    protected UserRepository $users;

    public function __construct()
    {
        $this->users = new UserRepository();
    }

    public function index(Request $request): void
    {
        // SECURITY: Log current session state for debugging
        $originalId = $_SESSION['__original_user_id'] ?? 'NOT SET';
        $currentId = $_SESSION['user_id'] ?? 'NOT SET';
        $authModified = isset($_SESSION['__auth_modified']) ? 'YES' : 'NO';
        error_log('DEBUG StaffController::index() - Session state: Original=' . $originalId . ', Current=' . $currentId . ', AuthModified=' . $authModified . ', URI=' . ($_SERVER['REQUEST_URI'] ?? 'unknown'));
        
        // SECURITY: Validate session integrity before processing
        if (isset($_SESSION['__original_user_id']) && isset($_SESSION['user_id'])) {
            if ($_SESSION['user_id'] != $_SESSION['__original_user_id'] && !isset($_SESSION['__auth_modified'])) {
                error_log('SECURITY ALERT: Session hijacking detected in StaffController::index(). Original: ' . $_SESSION['__original_user_id'] . ', Current: ' . $_SESSION['user_id'] . ', IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . ', URI: ' . ($_SERVER['REQUEST_URI'] ?? 'unknown'));
                // Restore original immediately
                $_SESSION['user_id'] = $_SESSION['__original_user_id'];
                unset($_SESSION['__auth_modified']);
                // Clear Auth cache
                Auth::clearCache();
            }
        }
        
        Auth::requireRoles(['director', 'operation_manager']);

        // SECURITY: Verify user again after requireRoles (in case it changed)
        $user = Auth::user();
        $actualUserId = $user['id'] ?? null;
        error_log('DEBUG StaffController::index() - After requireRoles: ActualUserId=' . $actualUserId . ', SessionUserId=' . ($_SESSION['user_id'] ?? 'NOT SET'));

        $roleFilter = $request->input('role');
        $statusFilter = $request->input('status');
        $search = $request->input('q');

        $users = $this->users->all($roleFilter ?: null, $statusFilter ?: null, $search ?: null);

        // Get tenant roles only (exclude admin and super_admin)
        $roles = db()->query("
            SELECT `key`, name 
            FROM roles 
            WHERE `key` != 'admin' 
            AND `key` != 'super_admin'
            AND (is_tenant_role = 1 OR is_tenant_role IS NULL)
            ORDER BY name ASC
        ")->fetchAll();

        $currentUserId = $actualUserId;

        $this->view('dashboard/staff/index', [
            'users' => $users,
            'roles' => $roles,
            'activeRole' => $roleFilter,
            'activeStatus' => $statusFilter,
            'search' => $search,
            'currentUserId' => $currentUserId,
        ]);
    }

    public function profile(Request $request): void
    {
        Auth::requireRoles(['director', 'operation_manager']);

        $userId = (int)$request->input('id');
        if (!$userId) {
            header('Location: ' . base_url('staff/dashboard/staff?error=Invalid%20user'));
            return;
        }

        $user = $this->users->find($userId);
        if (!$user) {
            header('Location: ' . base_url('staff/dashboard/staff?error=User%20not%20found'));
            return;
        }

        // Get staff details
        $staffStmt = db()->prepare('SELECT * FROM staff WHERE user_id = ? LIMIT 1');
        $staffStmt->execute([$userId]);
        $staff = $staffStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        // Get attendance logs (last 30 days)
        $attendanceRepo = new \App\Repositories\AttendanceRepository();
        $attendanceLogs = $attendanceRepo->getAttendanceHistory($userId, 30);

        // Get requisition history
        $requisitionStmt = db()->prepare('
            SELECT r.*, l.name as location_name 
            FROM inventory_requisitions r
            LEFT JOIN inventory_locations l ON l.id = r.location_id
            WHERE r.requested_by = ?
            ORDER BY r.created_at DESC
            LIMIT 20
        ');
        $requisitionStmt->execute([$userId]);
        $requisitions = $requisitionStmt->fetchAll(PDO::FETCH_ASSOC);

        // Get assigned tasks (recent)
        $taskStmt = db()->prepare('
            SELECT * FROM tasks 
            WHERE assigned_to = ?
            ORDER BY created_at DESC
            LIMIT 10
        ');
        $taskStmt->execute([$userId]);
        $tasks = $taskStmt->fetchAll(PDO::FETCH_ASSOC);

        // Get HR notes (disciplinary, performance, etc.)
        $notesStmt = db()->prepare('
            SELECT * FROM hr_notes
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT 20
        ');
        $notesStmt->execute([$userId]);
        $hrNotes = $notesStmt->fetchAll(PDO::FETCH_ASSOC);

        // Get permissions from role
        $roleConfig = config('roles', []);
        $permissions = $roleConfig[$user['role_key']]['permissions'] ?? [];

        $this->view('dashboard/staff/profile', [
            'user' => $user,
            'staff' => $staff,
            'attendanceLogs' => $attendanceLogs,
            'requisitions' => $requisitions,
            'tasks' => $tasks,
            'hrNotes' => $hrNotes,
            'permissions' => $permissions,
        ]);
    }

    public function edit(Request $request): void
    {
        Auth::requireRoles(['director']);

        $userId = (int)$request->input('id');
        if (!$userId) {
            header('Location: ' . base_url('staff/dashboard/staff?error=Invalid%20user'));
            return;
        }

        $user = $this->users->find($userId);
        if (!$user) {
            header('Location: ' . base_url('staff/dashboard/staff?error=User%20not%20found'));
            return;
        }

        // Check tenant access
        
        // Single installation - no tenant access check needed

        // Get tenant roles only (exclude admin and super_admin)
        $roles = db()->query("
            SELECT `key`, name 
            FROM roles 
            WHERE `key` != 'admin' 
            AND `key` != 'super_admin'
            AND (is_tenant_role = 1 OR is_tenant_role IS NULL)
            ORDER BY name ASC
        ")->fetchAll();

        // Get user's current roles
        $userRepository = new UserRepository();
        $userRoles = $userRepository->getUserRoles($userId);

        $this->view('dashboard/staff/edit', [
            'user' => $user,
            'roles' => $roles,
            'userRoles' => $userRoles,
        ]);
    }

    public function update(Request $request): void
    {
        Auth::requireRoles(['director']);

        // Get user_id from request - try multiple methods in order of preference
        $userId = null;
        
        // Method 1: Try POST data first (most common for form submissions)
        if (isset($_POST['user_id']) && $_POST['user_id'] !== '') {
            $userId = (int)$_POST['user_id'];
        }
        
        // Method 2: Try request input (handles both POST and GET)
        if (!$userId) {
            $userIdInput = $request->input('user_id');
            if ($userIdInput !== null && $userIdInput !== '') {
                $userId = (int)$userIdInput;
            }
        }
        
        // Method 3: Try GET query parameter (fallback from URL)
        if (!$userId && isset($_GET['id']) && $_GET['id'] !== '') {
            $userId = (int)$_GET['id'];
        }
        
        // Method 4: Try request input for 'id' (in case it's passed as 'id' instead of 'user_id')
        if (!$userId) {
            $idInput = $request->input('id');
            if ($idInput !== null && $idInput !== '') {
                $userId = (int)$idInput;
            }
        }
        
        // Validate user_id
        if (!$userId || $userId <= 0) {
            // Enhanced debugging
            $debugInfo = [
                'POST' => $_POST ?? [],
                'GET' => $_GET ?? [],
                'request_all' => $request->all(),
                'request_method' => $request->method(),
                'user_id_from_post' => $_POST['user_id'] ?? 'NOT SET',
                'user_id_from_get' => $_GET['id'] ?? 'NOT SET',
                'user_id_from_input' => $request->input('user_id'),
            ];
            error_log('StaffController::update() - Invalid user_id. Debug: ' . json_encode($debugInfo));
            header('Location: ' . base_url('staff/dashboard/staff?error=Invalid%20user%20ID%20-%20Please%20contact%20support'));
            return;
        }

        $user = $this->users->find($userId);
        if (!$user) {
            header('Location: ' . base_url('staff/dashboard/staff?error=User%20not%20found'));
            return;
        }

        // Check tenant access
        
        // Single installation - no tenant access check needed

        $name = trim($request->input('name', ''));
        $email = trim($request->input('email', ''));
        $status = $request->input('status');
        
        // Handle multi-role assignment
        $roles = $request->input('roles');
        $primaryRole = $request->input('primary_role');
        
        // Fallback to single role_key for non-multi-role users
        $roleKey = $request->input('role_key');

        if (!$name || !$email) {
            header('Location: ' . base_url('staff/dashboard/staff/edit?id=' . $userId . '&error=Missing%20required%20fields'));
            return;
        }

        // Check if email is already taken by another user
        $existing = $this->users->findByEmail($email);
        if ($existing && (int)$existing['id'] !== $userId) {
            header('Location: ' . base_url('staff/dashboard/staff/edit?id=' . $userId . '&error=Email%20already%20in%20use'));
            return;
        }

        // Handle multi-role assignment
        if (is_array($roles) && !empty($roles)) {
            // Filter out admin and director roles (security check)
            $allowedRoles = array_filter($roles, function($role) {
                return !in_array($role, ['admin', 'director', 'super_admin'], true);
            });
            
            if (empty($allowedRoles)) {
                header('Location: ' . base_url('staff/dashboard/staff/edit?id=' . $userId . '&error=At%20least%20one%20valid%20role%20is%20required'));
                return;
            }
            
            // Verify all roles exist
            $placeholders = [];
            $params = [];
            foreach ($allowedRoles as $index => $roleKey) {
                $param = 'role_' . $index;
                $params[$param] = $roleKey;
                $placeholders[] = ":{$param}";
            }
            
            $roleCheck = db()->prepare('SELECT `key` FROM roles WHERE `key` IN (' . implode(', ', $placeholders) . ')');
            $roleCheck->execute($params);
            $validRoles = array_column($roleCheck->fetchAll(), 'key');
            
            if (count($validRoles) !== count($allowedRoles)) {
                header('Location: ' . base_url('staff/dashboard/staff/edit?id=' . $userId . '&error=One%20or%20more%20invalid%20roles'));
                return;
            }
            
            // Set primary role (first in array if not specified)
            if (!$primaryRole || !in_array($primaryRole, $allowedRoles, true)) {
                $primaryRole = $allowedRoles[0];
            }
            
            // Update user roles
            $this->users->setUserRoles($userId, $allowedRoles, $primaryRole);
            
            // Update user basic info
            $this->users->update($userId, [
                'name' => $name,
                'email' => $email,
                'status' => $status,
            ]);
        } else {
            // Single role assignment (backward compatibility)
            if (!$roleKey) {
                header('Location: ' . base_url('staff/dashboard/staff/edit?id=' . $userId . '&error=Role%20is%20required'));
                return;
            }
            
            // Prevent assigning admin/director to staff
            if (in_array($roleKey, ['admin', 'director', 'super_admin'], true)) {
                header('Location: ' . base_url('staff/dashboard/staff/edit?id=' . $userId . '&error=Cannot%20assign%20system%20roles%20to%20staff'));
                return;
            }
            
            // Verify role exists
            $roleCheck = db()->prepare('SELECT `key` FROM roles WHERE `key` = :key LIMIT 1');
            $roleCheck->execute(['key' => $roleKey]);
            if (!$roleCheck->fetch()) {
                header('Location: ' . base_url('staff/dashboard/staff/edit?id=' . $userId . '&error=Invalid%20role'));
                return;
            }
            
            // Set as single role
            $this->users->setUserRoles($userId, [$roleKey], $roleKey);
            
            // Update user basic info
            $this->users->update($userId, [
                'name' => $name,
                'email' => $email,
                'status' => $status,
            ]);
        }

        header('Location: ' . base_url('staff/dashboard/staff?success=User%20updated'));
    }

    public function create(Request $request): void
    {
        Auth::requireRoles(['director', 'operation_manager']);

        // Get tenant roles only (exclude admin and super_admin)
        $roles = db()->query("
            SELECT `key`, name 
            FROM roles 
            WHERE `key` != 'admin' 
            AND `key` != 'super_admin'
            AND (is_tenant_role = 1 OR is_tenant_role IS NULL)
            ORDER BY name ASC
        ")->fetchAll();

        $this->view('dashboard/staff/create', [
            'roles' => $roles,
        ]);
    }

    public function store(Request $request): void
    {
        Auth::requireRoles(['director', 'operation_manager']);

        $name = trim($request->input('name', ''));
        $email = trim($request->input('email', ''));
        $username = trim($request->input('username', ''));
        $password = $request->input('password');
        $status = $request->input('status', 'active');
        
        // Handle multi-role assignment
        $roles = $request->input('roles');
        $primaryRole = $request->input('primary_role');
        
        // Fallback to single role_key for non-multi-role users
        $roleKey = $request->input('role_key');

        if (!$name || !$email || !$password) {
            header('Location: ' . base_url('staff/dashboard/staff/create?error=Missing%20required%20fields'));
            return;
        }

        // Check if email is already taken
        $existing = $this->users->findByEmail($email);
        if ($existing) {
            header('Location: ' . base_url('staff/dashboard/staff/create?error=Email%20already%20in%20use'));
            return;
        }

        // Check if username is already taken (if provided)
        if ($username) {
            $existingUsername = $this->users->findByUsername($username);
            if ($existingUsername) {
                header('Location: ' . base_url('staff/dashboard/staff/create?error=Username%20already%20in%20use'));
                return;
            }
        }

        // Validate password strength
        if (strlen($password) < 6) {
            header('Location: ' . base_url('staff/dashboard/staff/create?error=Password%20must%20be%20at%20least%206%20characters'));
            return;
        }

        // Handle multi-role assignment
        $roleKeysToAssign = [];
        if (is_array($roles) && !empty($roles)) {
            // Filter out admin and director roles (security check)
            $allowedRoles = array_filter($roles, function($role) {
                return !in_array($role, ['admin', 'director', 'super_admin'], true);
            });
            
            if (empty($allowedRoles)) {
                header('Location: ' . base_url('staff/dashboard/staff/create?error=At%20least%20one%20valid%20role%20is%20required'));
                return;
            }
            
            // Verify all roles exist
            $placeholders = [];
            $params = [];
            foreach ($allowedRoles as $index => $roleKey) {
                $param = 'role_' . $index;
                $params[$param] = $roleKey;
                $placeholders[] = ":{$param}";
            }
            
            $roleCheck = db()->prepare('SELECT `key` FROM roles WHERE `key` IN (' . implode(', ', $placeholders) . ')');
            $roleCheck->execute($params);
            $validRoles = array_column($roleCheck->fetchAll(), 'key');
            
            if (count($validRoles) !== count($allowedRoles)) {
                header('Location: ' . base_url('staff/dashboard/staff/create?error=One%20or%20more%20invalid%20roles'));
                return;
            }
            
            $roleKeysToAssign = $allowedRoles;
            $primaryRole = $primaryRole && in_array($primaryRole, $allowedRoles, true) ? $primaryRole : $allowedRoles[0];
        } else {
            // Single role assignment
            if (!$roleKey) {
                header('Location: ' . base_url('staff/dashboard/staff/create?error=Role%20is%20required'));
                return;
            }
            
            // Prevent assigning admin/director to staff
            if (in_array($roleKey, ['admin', 'director', 'super_admin'], true)) {
                header('Location: ' . base_url('staff/dashboard/staff/create?error=Cannot%20assign%20system%20roles%20to%20staff'));
                return;
            }
            
            // Verify role exists
            $roleCheck = db()->prepare('SELECT `key` FROM roles WHERE `key` = :key LIMIT 1');
            $roleCheck->execute(['key' => $roleKey]);
            if (!$roleCheck->fetch()) {
                header('Location: ' . base_url('staff/dashboard/staff/create?error=Invalid%20role'));
                return;
            }
            
            $roleKeysToAssign = [$roleKey];
            $primaryRole = $roleKey;
        }

        // Create user
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (name, email, username, password, role_key, status, created_at) 
                VALUES (:name, :email, :username, :password, :role_key, :status, NOW())";
        
        $stmt = db()->prepare($sql);
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'username' => $username ?: null,
            'password' => $passwordHash,
            'role_key' => $primaryRole,
            'status' => $status,
        ]);
        
        $userId = (int)db()->lastInsertId();
        
        // Set user roles
        $this->users->setUserRoles($userId, $roleKeysToAssign, $primaryRole);

        header('Location: ' . base_url('staff/dashboard/staff?success=Staff%20member%20created%20successfully'));
    }
}

