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
        Auth::requireRoles(['admin', 'operation_manager', 'director', 'ground', 'finance_manager']);

        $status = $request->input('status');
        $roomId = $request->input('room_id') ? (int)$request->input('room_id') : null;
        $assignedTo = $request->input('assigned_to') ? (int)$request->input('assigned_to') : null;

        $requests = $this->maintenance->all($status, $roomId, $assignedTo, 200);
        $statistics = $this->maintenance->getStatistics();
        $allRooms = $this->rooms->all();
        $allStaff = $this->users->all(null, 'active', null);

        $this->view('dashboard/maintenance/index', [
            'requests' => $requests,
            'statistics' => $statistics,
            'allRooms' => $allRooms,
            'allStaff' => $allStaff,
            'filters' => [
                'status' => $status,
                'room_id' => $roomId,
                'assigned_to' => $assignedTo,
            ],
        ]);
    }

    public function create(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager', 'director', 'ground', 'housekeeping', 'receptionist']);

        if ($request->method() === 'POST') {
            $user = Auth::user();

            $title = trim($request->input('title', ''));
            $description = trim($request->input('description', ''));

            if (empty($title)) {
                header('Location: ' . base_url('staff/dashboard/maintenance/create?error=Title%20is%20required'));
                return;
            }

            if (empty($description)) {
                header('Location: ' . base_url('staff/dashboard/maintenance/create?error=Description%20is%20required'));
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
                'room_id' => $request->input('room_id') ? (int)$request->input('room_id') : null,
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
                $this->maintenance->create($data);
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

        $allSuppliers = $this->suppliers->all();
        $recommendedSupplierIds = [];
        if (!empty($requestData['recommended_suppliers'])) {
            $recommendedSupplierIds = array_map('intval', explode(',', $requestData['recommended_suppliers']));
        }

        $this->view('dashboard/maintenance/assign-supplier', [
            'request' => $requestData,
            'allSuppliers' => $allSuppliers,
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

