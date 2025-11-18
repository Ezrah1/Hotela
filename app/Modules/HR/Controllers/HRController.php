<?php

namespace App\Modules\HR\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\UserRepository;
use App\Support\Auth;

class HRController extends Controller
{
    protected UserRepository $users;

    public function __construct()
    {
        $this->users = new UserRepository();
    }

    public function index(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager', 'director']);

        $search = $request->input('q', '');
        $statusFilter = $request->input('status', '');
        $roleFilter = $request->input('role', '');

        $users = $this->users->all($roleFilter ?: null, $statusFilter ?: null, $search ?: null);

        // Get employee records
        $employeeRecords = $this->getEmployeeRecords();

        $roles = db()->query('SELECT `key`, name FROM roles ORDER BY name ASC')->fetchAll();

        $this->view('dashboard/hr/index', [
            'users' => $users,
            'employeeRecords' => $employeeRecords,
            'roles' => $roles,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'roleFilter' => $roleFilter,
        ]);
    }

    public function employee(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager', 'director']);

        $userId = (int)$request->input('id');
        if (!$userId) {
            header('Location: ' . base_url('dashboard/hr?error=Invalid%20employee'));
            return;
        }

        $user = $this->users->find($userId);
        if (!$user) {
            header('Location: ' . base_url('dashboard/hr?error=Employee%20not%20found'));
            return;
        }

        // Check tenant access
        $tenantId = \App\Support\Tenant::id();
        if ($tenantId !== null && (int)($user['tenant_id'] ?? 0) !== $tenantId) {
            header('Location: ' . base_url('dashboard/hr?error=Access%20denied'));
            return;
        }

        // Get employee records
        $records = $this->getEmployeeRecords($userId);
        $payrollHistory = $this->getPayrollHistory($userId);
        $attendanceSummary = $this->getAttendanceSummary($userId);

        $this->view('dashboard/hr/employee', [
            'user' => $user,
            'records' => $records,
            'payrollHistory' => $payrollHistory,
            'attendanceSummary' => $attendanceSummary,
        ]);
    }

    public function addRecord(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager']);

        $userId = (int)$request->input('user_id');
        $type = $request->input('type');
        $title = trim($request->input('title', ''));
        $description = trim($request->input('description', ''));

        if (!$userId || !$type || !$title) {
            header('Location: ' . base_url('dashboard/hr/employee?id=' . $userId . '&error=Missing%20required%20fields'));
            return;
        }

        $user = $this->users->find($userId);
        if (!$user) {
            header('Location: ' . base_url('dashboard/hr?error=Employee%20not%20found'));
            return;
        }

        // Check tenant access
        $tenantId = \App\Support\Tenant::id();
        if ($tenantId !== null && (int)($user['tenant_id'] ?? 0) !== $tenantId) {
            header('Location: ' . base_url('dashboard/hr?error=Access%20denied'));
            return;
        }

        $this->createEmployeeRecord($userId, $type, $title, $description);

        header('Location: ' . base_url('dashboard/hr/employee?id=' . $userId . '&success=Record%20added'));
    }

    protected function getEmployeeRecords(?int $userId = null): array
    {
        try {
            $tenantId = \App\Support\Tenant::id();
            $params = [];

            $sql = "
                SELECT employee_records.*, users.name as employee_name
                FROM employee_records
                INNER JOIN users ON users.id = employee_records.user_id
                WHERE 1=1
            ";

            if ($userId) {
                $sql .= ' AND employee_records.user_id = :user_id';
                $params['user_id'] = $userId;
            }

            if ($tenantId !== null) {
                $sql .= ' AND employee_records.tenant_id = :tenant_id';
                $params['tenant_id'] = $tenantId;
            }

            $sql .= ' ORDER BY employee_records.created_at DESC LIMIT 50';

            $stmt = db()->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            // Table doesn't exist yet - return empty array
            if (str_contains($e->getMessage(), "doesn't exist")) {
                return [];
            }
            throw $e;
        }
    }

    protected function getPayrollHistory(int $userId): array
    {
        try {
            $tenantId = \App\Support\Tenant::id();
            $params = ['user_id' => $userId];

            $sql = "
                SELECT payroll.*
                FROM payroll
                WHERE payroll.user_id = :user_id
            ";

            if ($tenantId !== null) {
                $sql .= ' AND payroll.tenant_id = :tenant_id';
                $params['tenant_id'] = $tenantId;
            }

            $sql .= ' ORDER BY payroll.pay_period_end DESC LIMIT 12';

            $stmt = db()->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            // Table doesn't exist yet - return empty array
            if (str_contains($e->getMessage(), "doesn't exist")) {
                return [];
            }
            throw $e;
        }
    }

    protected function getAttendanceSummary(int $userId): array
    {
        // Placeholder - would integrate with attendance system
        return [
            'total_days' => 0,
            'present_days' => 0,
            'absent_days' => 0,
            'late_days' => 0,
        ];
    }

    protected function createEmployeeRecord(int $userId, string $type, string $title, string $description): void
    {
        try {
            $tenantId = \App\Support\Tenant::id();
            $currentUser = Auth::user();

            $sql = "
                INSERT INTO employee_records (tenant_id, user_id, type, title, description, created_by, created_at)
                VALUES (:tenant_id, :user_id, :type, :title, :description, :created_by, NOW())
            ";

            $params = [
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'description' => $description,
                'created_by' => $currentUser['id'] ?? null,
            ];

            $stmt = db()->prepare($sql);
            $stmt->execute($params);
        } catch (\PDOException $e) {
            // Table doesn't exist - provide helpful error
            if (str_contains($e->getMessage(), "doesn't exist")) {
                throw new \Exception('Employee records table does not exist. Please run migrations: php scripts/migrate.php');
            }
            throw $e;
        }
    }
}

