<?php

namespace App\Modules\AuditLogs\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\AuditLogRepository;
use App\Repositories\UserRepository;
use App\Support\Auth;

class AuditLogsController extends Controller
{
    protected AuditLogRepository $auditLogs;
    protected UserRepository $users;

    public function __construct()
    {
        $this->auditLogs = new AuditLogRepository();
        $this->users = new UserRepository();
    }

    public function index(Request $request): void
    {
        Auth::requireRoles(['admin', 'director', 'tech']);
        
        // Handle time-based quick filters
        $timeFilter = $request->input('time');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        if ($timeFilter && !$startDate && !$endDate) {
            // Apply time-based filter
            $now = new \DateTime();
            switch ($timeFilter) {
                case 'hour':
                    $startDate = $now->modify('-1 hour')->format('Y-m-d H:i:s');
                    $endDate = (new \DateTime())->format('Y-m-d H:i:s');
                    break;
                case 'today':
                    $startDate = (new \DateTime())->setTime(0, 0, 0)->format('Y-m-d');
                    $endDate = (new \DateTime())->setTime(23, 59, 59)->format('Y-m-d');
                    break;
                case 'yesterday':
                    $startDate = (new \DateTime())->modify('-1 day')->setTime(0, 0, 0)->format('Y-m-d');
                    $endDate = (new \DateTime())->modify('-1 day')->setTime(23, 59, 59)->format('Y-m-d');
                    break;
                case 'week':
                    $startDate = (new \DateTime())->modify('monday this week')->setTime(0, 0, 0)->format('Y-m-d');
                    $endDate = (new \DateTime())->setTime(23, 59, 59)->format('Y-m-d');
                    break;
                case 'month':
                    $startDate = (new \DateTime())->modify('first day of this month')->setTime(0, 0, 0)->format('Y-m-d');
                    $endDate = (new \DateTime())->setTime(23, 59, 59)->format('Y-m-d');
                    break;
                case 'year':
                    $startDate = (new \DateTime())->modify('first day of january this year')->setTime(0, 0, 0)->format('Y-m-d');
                    $endDate = (new \DateTime())->setTime(23, 59, 59)->format('Y-m-d');
                    break;
            }
        }
        
        // Get filter parameters
        $filters = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'user_id' => $request->input('user_id'),
            'role_key' => $request->input('role_key'),
            'action' => $request->input('action'),
            'entity_type' => $request->input('entity_type'),
            'search' => $request->input('search'),
        ];
        
        // Remove empty filters
        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });
        
        // Pagination
        $page = max(1, (int)$request->input('page', 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;
        
        // Get logs
        $logs = $this->auditLogs->search($filters, $perPage, $offset);
        $total = $this->auditLogs->count($filters);
        $totalPages = ceil($total / $perPage);
        
        // Get filter options
        $actions = $this->auditLogs->getDistinctActions();
        $entityTypes = $this->auditLogs->getDistinctEntityTypes();
        $roles = $this->auditLogs->getDistinctRoles();
        $users = $this->auditLogs->getUsers();
        
        // Get role config for layout
        $user = Auth::user();
        $role = $user['role_key'] ?? $user['role'] ?? 'director';
        $roleConfig = config('roles.' . $role, config('roles.director', []));
        
        // Set page title
        $pageTitle = 'Audit Logs | Hotela';
        
        // Render view with layout
        $this->view('dashboard/audit-logs/index', [
            'logs' => $logs,
            'filters' => $filters,
            'actions' => $actions,
            'entityTypes' => $entityTypes,
            'roles' => $roles,
            'users' => $users,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'perPage' => $perPage,
            'pageTitle' => $pageTitle,
            'roleConfig' => $roleConfig,
        ]);
    }
}

