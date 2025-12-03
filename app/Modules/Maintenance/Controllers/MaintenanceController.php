<?php

namespace App\Modules\Maintenance\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\MaintenanceRepository;
use App\Repositories\RoomRepository;
use App\Repositories\UserRepository;
use App\Repositories\SupplierRepository;
use App\Support\Auth;

class MaintenanceController extends Controller
{
    protected MaintenanceRepository $maintenance;
    protected RoomRepository $rooms;
    protected UserRepository $users;
    protected SupplierRepository $suppliers;

    public function __construct()
    {
        $this->maintenance = new MaintenanceRepository();
        $this->rooms = new RoomRepository();
        $this->users = new UserRepository();
        $this->suppliers = new SupplierRepository();
    }

    public function index(Request $request): void
    {
        // Allow all authenticated users to view maintenance requests
        Auth::requireRoles([]);

        $status = $request->input('status');
        $roomId = $request->input('room_id') ? (int)$request->input('room_id') : null;
        $assignedTo = $request->input('assigned_to') ? (int)$request->input('assigned_to') : null;
        $filter = $request->input('filter', 'department'); // 'department', 'mine', 'all'

        $user = Auth::user();
        $userRole = $user['role_key'] ?? '';
        $userId = (int)($user['id'] ?? 0);
        $canViewAll = \App\Support\DepartmentHelper::canViewAllDepartments($userRole);

        $requests = $this->maintenance->all($status, $roomId, $assignedTo, 200);
        
        // Apply department filtering
        if ($filter === 'mine' && !$canViewAll) {
            // Show only user's own requests
            $requests = array_filter($requests, function($req) use ($userId) {
                return ($req['requested_by'] ?? null) == $userId;
            });
        } elseif ($filter === 'department' && !$canViewAll) {
            // Show department requests
            $userDepartment = \App\Support\DepartmentHelper::getDepartmentFromRole($userRole);
            if ($userDepartment) {
                $departmentRoleKeys = \App\Support\DepartmentHelper::getRolesForDepartment($userDepartment);
                $requests = array_filter($requests, function($req) use ($departmentRoleKeys) {
                    $requesterRole = $req['requester_role_key'] ?? null;
                    return in_array($requesterRole, $departmentRoleKeys, true);
                });
            } else {
                // Fallback: show only user's own
                $requests = array_filter($requests, function($req) use ($userId) {
                    return ($req['requested_by'] ?? null) == $userId;
                });
            }
        }
        // If canViewAll or filter === 'all', show all requests (no filtering)

        $statistics = $this->maintenance->getStatistics();
        $allRooms = $this->rooms->all();
        $allStaff = $this->users->all(null, 'active', null);

        $this->view('dashboard/maintenance/index', [
            'requests' => $requests,
            'statistics' => $statistics,
            'allRooms' => $allRooms,
            'allStaff' => $allStaff,
            'userRole' => $userRole,
            'canViewAll' => $canViewAll,
            'filters' => [
                'status' => $status,
                'room_id' => $roomId,
                'assigned_to' => $assignedTo,
                'filter' => $filter,
            ],
        ]);
    }

    public function create(Request $request): void
    {
        // Allow all authenticated users to create maintenance requests
        Auth::requireRoles([]);

        if ($request->method() === 'POST') {
            $user = Auth::user();

            $title = trim($request->input('title', ''));
            $description = trim($request->input('description', ''));
            $roomId = $request->input('room_id') ? (int)$request->input('room_id') : null;

            if (empty($title)) {
                header('Location: ' . base_url('staff/dashboard/maintenance/create?error=Title%20is%20required'));
                return;
            }

            if (empty($description)) {
                header('Location: ' . base_url('staff/dashboard/maintenance/create?error=Description%20is%20required'));
                return;
            }

            // Check for duplicate maintenance requests in the same department
            $duplicate = $this->maintenance->findDuplicate(
                (int)(Auth::user()['id'] ?? 0),
                $title,
                $description,
                $roomId
            );
            if ($duplicate) {
                $duplicateId = (int)$duplicate['id'];
                $duplicateRef = htmlspecialchars($duplicate['reference'] ?? 'N/A');
                header('Location: ' . base_url('staff/dashboard/maintenance/create?error=' . urlencode("A similar maintenance request already exists in your department (Reference: {$duplicateRef}). Please add comments to the existing request instead.")) . '&duplicate_id=' . $duplicateId);
                return;
            }

            // Handle photo uploads
            $photos = [];
            if (isset($_FILES['photos']) && is_array($_FILES['photos']['name'])) {
                $uploadService = new \App\Services\FileUploadService();
                foreach ($_FILES['photos']['name'] as $key => $name) {
                    if ($_FILES['photos']['error'][$key] === UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $_FILES['photos']['name'][$key],
                            'type' => $_FILES['photos']['type'][$key],
                            'tmp_name' => $_FILES['photos']['tmp_name'][$key],
                            'error' => $_FILES['photos']['error'][$key],
                            'size' => $_FILES['photos']['size'][$key],
                        ];
                        try {
                            $imagePath = $uploadService->uploadImage($file, 'maintenance');
                            if ($imagePath) {
                                $photos[] = asset($imagePath);
                            }
                        } catch (\Exception $e) {
                            // Skip failed uploads
                        }
                    }
                }
            }

            $data = [
                'room_id' => $roomId,
                'title' => $title,
                'description' => $description,
                'priority' => $request->input('priority', 'medium'),
                'status' => 'pending', // Always start as pending for ops review
                'requested_by' => $user['id'] ?? null,
                'notes' => trim($request->input('notes', '')),
                'materials_needed' => trim($request->input('materials_needed', '')),
                'photos' => $photos,
            ];

            try {
                $requestId = $this->maintenance->create($data);
                
                // Notify department members about new maintenance request
                $userRole = $user['role_key'] ?? '';
                $userDepartment = \App\Support\DepartmentHelper::getDepartmentFromRole($userRole);
                if ($userDepartment) {
                    $departmentRoleKeys = \App\Support\DepartmentHelper::getRolesForDepartment($userDepartment);
                    $notificationService = new \App\Services\Notifications\NotificationService();
                    $requestData = $this->maintenance->find($requestId);
                    $reference = $requestData['reference'] ?? 'N/A';
                    
                    // Notify all department members
                    foreach ($departmentRoleKeys as $roleKey) {
                        $notificationService->notifyRole($roleKey, 'New Department Maintenance Request', 
                            sprintf('A new maintenance request %s has been created in your department (%s priority).', 
                                $reference, ucfirst($data['priority'])),
                            ['request_id' => $requestId, 'reference' => $reference]
                        );
                    }
                }
                
                header('Location: ' . base_url('staff/dashboard/maintenance?success=Maintenance%20request%20created'));
            } catch (\Exception $e) {
                header('Location: ' . base_url('staff/dashboard/maintenance/create?error=' . urlencode($e->getMessage())));
            }
            return;
        }

        $allRooms = $this->rooms->all();
        $allStaff = $this->users->all(null, 'active', null);

        $this->view('dashboard/maintenance/create', [
            'allRooms' => $allRooms,
            'allStaff' => $allStaff,
        ]);
    }

    public function updateStatus(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager', 'director', 'ground']);

        $id = (int)$request->input('id');
        $status = $request->input('status');
        $notes = trim($request->input('notes', ''));

        if (!$id || !$status) {
            header('Location: ' . base_url('staff/dashboard/maintenance?error=Invalid%20request'));
            return;
        }

        $validStatuses = ['pending', 'ops_review', 'finance_review', 'approved', 'assigned', 'in_progress', 'completed', 'verified', 'cancelled'];
        if (!in_array($status, $validStatuses, true)) {
            header('Location: ' . base_url('staff/dashboard/maintenance?error=Invalid%20status'));
            return;
        }

        $updateData = ['status' => $status];
        
        if ($notes) {
            $existing = $this->maintenance->find($id);
            $existingNotes = $existing['notes'] ?? '';
            $updateData['notes'] = $existingNotes ? $existingNotes . "\n\n" . date('Y-m-d H:i:s') . ': ' . $notes : $notes;
        }

        if ($status === 'completed') {
            $updateData['completed_at'] = date('Y-m-d H:i:s');
        }

        try {
            $this->maintenance->update($id, $updateData);
            header('Location: ' . base_url('staff/dashboard/maintenance?success=Status%20updated'));
        } catch (\Exception $e) {
            header('Location: ' . base_url('staff/dashboard/maintenance?error=' . urlencode($e->getMessage())));
        }
    }

    public function show(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager', 'director', 'ground']);

        $id = (int)$request->input('id');
        $requestData = $this->maintenance->find($id);

        if (!$requestData) {
            header('Location: ' . base_url('staff/dashboard/maintenance?error=Request%20not%20found'));
            return;
        }

        $allStaff = $this->users->all(null, 'active', null);

        $this->view('dashboard/maintenance/show', [
            'request' => $requestData,
            'allStaff' => $allStaff,
        ]);
    }

    public function edit(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager', 'director', 'ground']);

        $id = (int)$request->input('id');
        $requestData = $this->maintenance->find($id);

        if (!$requestData) {
            header('Location: ' . base_url('staff/dashboard/maintenance?error=Request%20not%20found'));
            return;
        }

        if ($request->method() === 'POST') {
            $title = trim($request->input('title', ''));
            $description = trim($request->input('description', ''));

            if (empty($title)) {
                header('Location: ' . base_url('staff/dashboard/maintenance/edit?id=' . $id . '&error=Title%20is%20required'));
                return;
            }

            if (empty($description)) {
                header('Location: ' . base_url('staff/dashboard/maintenance/edit?id=' . $id . '&error=Description%20is%20required'));
                return;
            }

            $data = [
                'title' => $title,
                'description' => $description,
                'priority' => $request->input('priority', 'medium'),
                'assigned_to' => $request->input('assigned_to') ? (int)$request->input('assigned_to') : null,
                'status' => $request->input('status', 'pending'),
            ];

            try {
                $this->maintenance->update($id, $data);
                header('Location: ' . base_url('staff/dashboard/maintenance?success=Request%20updated'));
            } catch (\Exception $e) {
                header('Location: ' . base_url('staff/dashboard/maintenance/edit?id=' . $id . '&error=' . urlencode($e->getMessage())));
            }
            return;
        }

        $allRooms = $this->rooms->all();
        $allStaff = $this->users->all(null, 'active', null);

        $this->view('dashboard/maintenance/edit', [
            'request' => $requestData,
            'allRooms' => $allRooms,
            'allStaff' => $allStaff,
        ]);
    }

    public function opsReview(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager', 'director']);

        $id = (int)$request->input('id');
        $requestData = $this->maintenance->find($id);

        if (!$requestData || $requestData['status'] !== 'pending') {
            header('Location: ' . base_url('staff/dashboard/maintenance?error=Invalid%20request%20or%20wrong%20status'));
            return;
        }

        if ($request->method() === 'POST') {
            $costEstimate = $request->input('cost_estimate') ? (float)$request->input('cost_estimate') : null;
            $opsNotes = trim($request->input('ops_notes', ''));
            $materialsNeeded = trim($request->input('materials_needed', ''));
            
            // Handle multiple supplier selection
            $recommendedSupplierIds = $request->input('recommended_suppliers');
            $recommendedSuppliers = '';
            if (is_array($recommendedSupplierIds)) {
                $recommendedSuppliers = implode(',', array_map('intval', $recommendedSupplierIds));
            } elseif (!empty($recommendedSupplierIds)) {
                $recommendedSuppliers = (string)$recommendedSupplierIds;
            }

            if (empty($opsNotes)) {
                header('Location: ' . base_url('staff/dashboard/maintenance/ops-review?id=' . $id . '&error=Ops%20notes%20are%20required'));
                return;
            }

            $updateData = [
                'status' => 'finance_review',
                'cost_estimate' => $costEstimate,
                'ops_notes' => $opsNotes,
                'materials_needed' => $materialsNeeded ?: $requestData['materials_needed'],
                'recommended_suppliers' => $recommendedSuppliers,
            ];

            try {
                $this->maintenance->update($id, $updateData);
                header('Location: ' . base_url('staff/dashboard/maintenance?success=Request%20forwarded%20to%20Finance'));
            } catch (\Exception $e) {
                header('Location: ' . base_url('staff/dashboard/maintenance/ops-review?id=' . $id . '&error=' . urlencode($e->getMessage())));
            }
            return;
        }

        $allSuppliers = $this->suppliers->all();

        $this->view('dashboard/maintenance/ops-review', [
            'request' => $requestData,
            'allSuppliers' => $allSuppliers,
        ]);
    }

    public function financeReview(Request $request): void
    {
        Auth::requireRoles(['admin', 'finance_manager', 'director']);

        $id = (int)$request->input('id');
        $requestData = $this->maintenance->find($id);

        if (!$requestData || $requestData['status'] !== 'finance_review') {
            header('Location: ' . base_url('staff/dashboard/maintenance?error=Invalid%20request%20or%20wrong%20status'));
            return;
        }

        if ($request->method() === 'POST') {
            $action = $request->input('action'); // 'approve' or 'reject'
            $financeNotes = trim($request->input('finance_notes', ''));
            $user = Auth::user();

            if ($action === 'approve') {
                $updateData = [
                    'status' => 'approved',
                    'finance_notes' => $financeNotes,
                    'approved_by' => $user['id'],
                    'approved_at' => date('Y-m-d H:i:s'),
                ];
            } else {
                $updateData = [
                    'status' => 'pending',
                    'finance_notes' => $financeNotes,
                ];
            }

            try {
                $this->maintenance->update($id, $updateData);
                $message = $action === 'approve' ? 'Request%20approved' : 'Request%20rejected%20and%20returned%20to%20Ops';
                header('Location: ' . base_url('staff/dashboard/maintenance?success=' . $message));
            } catch (\Exception $e) {
                header('Location: ' . base_url('staff/dashboard/maintenance/finance-review?id=' . $id . '&error=' . urlencode($e->getMessage())));
            }
            return;
        }

        $this->view('dashboard/maintenance/finance-review', [
            'request' => $requestData,
        ]);
    }

    public function assignSupplier(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager', 'finance_manager', 'director']);

        $id = (int)$request->input('id');
        $requestData = $this->maintenance->find($id);

        if (!$requestData || $requestData['status'] !== 'approved') {
            header('Location: ' . base_url('staff/dashboard/maintenance?error=Invalid%20request%20or%20wrong%20status'));
            return;
        }

        if ($request->method() === 'POST') {
            $supplierId = $request->input('supplier_id') ? (int)$request->input('supplier_id') : null;

            if (!$supplierId) {
                header('Location: ' . base_url('staff/dashboard/maintenance/assign-supplier?id=' . $id . '&error=Supplier%20is%20required'));
                return;
            }

            $workOrderRef = $this->maintenance->generateWorkOrderReference();

            $updateData = [
                'status' => 'assigned',
                'supplier_id' => $supplierId,
                'work_order_reference' => $workOrderRef,
            ];

            try {
                $this->maintenance->update($id, $updateData);
                header('Location: ' . base_url('staff/dashboard/maintenance?success=Supplier%20assigned%20and%20work%20order%20created'));
            } catch (\Exception $e) {
                header('Location: ' . base_url('staff/dashboard/maintenance/assign-supplier?id=' . $id . '&error=' . urlencode($e->getMessage())));
            }
            return;
        }

        // Filter suppliers to only show service providers (or both) for maintenance
        $allSuppliers = $this->suppliers->all();
        $serviceProviders = array_filter($allSuppliers, function($s) {
            return in_array($s['category'] ?? 'product_supplier', ['service_provider', 'both']) 
                && in_array($s['status'] ?? 'active', ['active']);
        });
        
        $recommendedSupplierIds = [];
        if (!empty($requestData['recommended_suppliers'])) {
            $recommendedSupplierIds = array_map('intval', explode(',', $requestData['recommended_suppliers']));
        }

        $this->view('dashboard/maintenance/assign-supplier', [
            'request' => $requestData,
            'allSuppliers' => $serviceProviders,
            'recommendedSupplierIds' => $recommendedSupplierIds,
        ]);
    }

    public function verifyWork(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager', 'director']);

        $id = (int)$request->input('id');
        $requestData = $this->maintenance->find($id);

        if (!$requestData || $requestData['status'] !== 'completed') {
            header('Location: ' . base_url('staff/dashboard/maintenance?error=Invalid%20request%20or%20work%20not%20completed'));
            return;
        }

        if ($request->method() === 'POST') {
            $user = Auth::user();

            $updateData = [
                'status' => 'verified',
                'verified_by' => $user['id'],
                'verified_at' => date('Y-m-d H:i:s'),
            ];

            try {
                $this->maintenance->update($id, $updateData);
                header('Location: ' . base_url('staff/dashboard/maintenance?success=Work%20verified%20and%20ready%20for%20payment'));
            } catch (\Exception $e) {
                header('Location: ' . base_url('staff/dashboard/maintenance/verify-work?id=' . $id . '&error=' . urlencode($e->getMessage())));
            }
            return;
        }

        $this->view('dashboard/maintenance/verify-work', [
            'request' => $requestData,
        ]);
    }
}

