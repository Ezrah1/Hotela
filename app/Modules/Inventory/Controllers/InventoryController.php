<?php

namespace App\Modules\Inventory\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\InventoryRepository;
use App\Repositories\RequisitionRepository;
use App\Services\Inventory\InventoryService;
use App\Support\Auth;
use App\Support\InventoryPermission;

class InventoryController extends Controller
{
    protected InventoryRepository $inventory;
    protected RequisitionRepository $requisitions;
    protected InventoryService $inventoryService;

    public function __construct()
    {
        $this->inventory = new InventoryRepository();
        $this->requisitions = new RequisitionRepository();
        $this->inventoryService = new InventoryService();
    }

    public function index(): void
    {
        Auth::requireRoles(['director', 'admin', 'operation_manager', 'finance_manager', 'cashier']);

        $canRequisitions = InventoryPermission::can('inventory.requisitions.view');
        $canApprove = InventoryPermission::can('inventory.approve_po');
        $canAdjust = InventoryPermission::can('inventory.adjust_stock');
        $valuation = null;
        if (InventoryPermission::can('inventory.view_valuation')) {
            $valuation = $this->inventoryService->valuation();
        }

        // Filters
        $category = $_GET['category'] ?? null;
        $search = $_GET['q'] ?? null;
        $items = $this->inventory->itemsWithStock($category ?: null, $search ?: null);
        $categories = $this->inventory->categories();

        // POS → Inventory mapping health
        $posRepo = new \App\Repositories\PosItemRepository();
        $unmappedCount = $posRepo->unmappedCount();
        $unmappedItems = $unmappedCount > 0 ? $posRepo->unmappedItems(10) : [];

        $user = Auth::user();
        $userRole = $user['role_key'] ?? ($user['role'] ?? '');
        $canManageCategories = in_array($userRole, ['director', 'admin', 'tech_admin']);
        
        $this->view('dashboard/inventory/index', [
            'canRequisitions' => $canRequisitions,
            'canApprove' => $canApprove,
            'canAdjust' => $canAdjust,
            'canManageCategories' => $canManageCategories,
            'valuation' => $valuation,
            'locations' => $this->inventoryService->locations(),
            'inventoryItems' => $this->inventory->allItems(),
            'items' => $items,
            'categories' => $categories,
            'activeCategory' => $category,
            'search' => $search,
            'unmappedCount' => $unmappedCount,
            'unmappedItems' => $unmappedItems,
        ]);
    }
    
    public function createCategory(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'tech_admin']);
        
        $categoryName = trim($request->input('category_name', ''));
        
        if (empty($categoryName)) {
            header('Location: ' . base_url('staff/dashboard/inventory?error=Category%20name%20is%20required'));
            return;
        }
        
        // Sanitize category name (remove special characters, limit length)
        $categoryName = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $categoryName);
        $categoryName = trim($categoryName);
        $categoryName = substr($categoryName, 0, 100);
        
        if (empty($categoryName)) {
            header('Location: ' . base_url('staff/dashboard/inventory?error=Invalid%20category%20name'));
            return;
        }
        
        // Check if category already exists
        $existingCategories = $this->inventory->categories();
        if (in_array($categoryName, $existingCategories)) {
            header('Location: ' . base_url('staff/dashboard/inventory?error=Category%20already%20exists'));
            return;
        }
        
        // Since categories are derived from the category column in inventory_items,
        // we need to create a placeholder item with this category to establish it
        // This placeholder can be a "Category Template" item that's marked as inactive or hidden
        $db = db();
        
        // Check if we can create a placeholder item
        // Generate a unique SKU for the placeholder
        $placeholderSku = 'CAT-' . strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', substr($categoryName, 0, 20)));
        
        // Check if SKU already exists
        $checkStmt = $db->prepare('SELECT id FROM inventory_items WHERE sku = ? LIMIT 1');
        $checkStmt->execute([$placeholderSku]);
        if ($checkStmt->fetch()) {
            $placeholderSku = 'CAT-' . strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', substr($categoryName, 0, 15))) . '-' . time();
        }
        
        // Create placeholder item with this category
        // This item will be hidden from normal listings but establishes the category
        $stmt = $db->prepare('
            INSERT INTO inventory_items (sku, name, unit, category, status, reorder_point, avg_cost)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        
        $stmt->execute([
            $placeholderSku,
            '[Category: ' . $categoryName . ']', // Placeholder name
            'pcs', // Default unit
            $categoryName,
            'inactive', // Mark as inactive so it doesn't show in normal listings
            0,
            0.00
        ]);
        
        header('Location: ' . base_url('staff/dashboard/inventory?success=Category%20created%20successfully'));
    }

    public function requisitions(): void
    {
        // Allow all authenticated users to view requisitions
        Auth::requireRoles([]);

        $type = $_GET['type'] ?? null;
        $status = $_GET['status'] ?? null;
        $filter = $_GET['filter'] ?? 'department'; // 'department', 'mine', 'all'
        $user = Auth::user();
        $userRole = $user['role_key'] ?? '';
        $userId = (int)($user['id'] ?? 0);

        // Determine department filtering based on role
        $departmentRoleKeys = null;
        $canViewAll = \App\Support\DepartmentHelper::canViewAllDepartments($userRole);
        
        if (!$canViewAll) {
            // For department-level users, show all requisitions from their department
            $userDepartment = \App\Support\DepartmentHelper::getDepartmentFromRole($userRole);
            if ($userDepartment) {
                $departmentRoleKeys = \App\Support\DepartmentHelper::getRolesForDepartment($userDepartment);
            }
        }

        // Apply filter based on user selection
        if ($filter === 'mine' && !$canViewAll) {
            // Show only user's own requisitions
            $allRequisitions = $this->requisitions->all($type, $status);
            $allRequisitions = array_filter($allRequisitions, function($req) use ($userId) {
                return ($req['requested_by'] ?? null) == $userId;
            });
        } elseif ($filter === 'department' && !$canViewAll && $departmentRoleKeys) {
            // Show department requisitions
            $allRequisitions = $this->requisitions->all($type, $status, $departmentRoleKeys);
        } elseif ($canViewAll) {
            // Management roles see all
            $allRequisitions = $this->requisitions->all($type, $status);
        } else {
            // Fallback: show only user's own
            $allRequisitions = $this->requisitions->all($type, $status);
            $allRequisitions = array_filter($allRequisitions, function($req) use ($userId) {
                return ($req['requested_by'] ?? null) == $userId;
            });
        }

        $this->view('dashboard/inventory/requisitions', [
            'requisitions' => $allRequisitions,
            'inventoryItems' => $this->inventory->allItems(),
            'locations' => $this->inventoryService->locations(),
            'userRole' => $userRole,
            'canViewAll' => $canViewAll,
            'filters' => ['type' => $type, 'status' => $status, 'filter' => $filter],
        ]);
    }

    public function storeRequisition(Request $request): void
    {
        // Allow all authenticated users to create requisitions
        Auth::requireRoles([]);

        $user = Auth::user();
        $userId = (int)($user['id'] ?? 0);
        $userRole = $user['role_key'] ?? '';

        $notes = trim($request->input('notes', ''));

        $ids = $request->input('inventory_item_id');
        $qtys = $request->input('quantity');
        $customNames = $request->input('custom_item_name');
        $isCustomFlags = $request->input('is_custom_item');

        $items = [];
        $itemIds = [];
        if (is_array($qtys)) {
            foreach ($qtys as $idx => $qty) {
                $qty = (float)($qty ?? 0);
                if ($qty <= 0) continue;
                
                $isCustom = isset($isCustomFlags[$idx]) && $isCustomFlags[$idx] === '1';
                
                if ($isCustom) {
                    // Custom item
                    $customName = trim($customNames[$idx] ?? '');
                    if (empty($customName)) continue;
                    
                    $items[] = [
                        'inventory_item_id' => null,
                        'custom_item_name' => $customName,
                        'quantity' => $qty
                    ];
                } else {
                    // Regular inventory item
                    $id = isset($ids[$idx]) ? (int)$ids[$idx] : 0;
                    if ($id > 0) {
                        $items[] = ['inventory_item_id' => $id, 'quantity' => $qty];
                        $itemIds[] = $id;
                    }
                }
            }
        } else {
            // Single item (backward compatibility)
            $isCustom = isset($isCustomFlags) && (is_array($isCustomFlags) ? $isCustomFlags[0] === '1' : $isCustomFlags === '1');
            $qty = (float)$qtys;
            
            if ($qty > 0) {
                if ($isCustom) {
                    $customName = trim($customNames[0] ?? (is_string($customNames) ? $customNames : ''));
                    if (!empty($customName)) {
                        $items[] = [
                            'inventory_item_id' => null,
                            'custom_item_name' => $customName,
                            'quantity' => $qty
                        ];
                    }
                } else {
                    $id = (int)$ids;
                    if ($id > 0) {
                        $items[] = ['inventory_item_id' => $id, 'quantity' => $qty];
                        $itemIds[] = $id;
                    }
                }
            }
        }

        if (empty($items)) {
            header('Location: ' . base_url('staff/dashboard/inventory/requisitions?error=Invalid%20inputs'));
            return;
        }

        // Check for duplicate requisitions in the same department
        $duplicate = $this->requisitions->findDuplicate($userId, $itemIds);
        if ($duplicate) {
            $duplicateId = (int)$duplicate['id'];
            $duplicateRef = htmlspecialchars($duplicate['reference'] ?? 'N/A');
            header('Location: ' . base_url('staff/dashboard/inventory/requisitions?error=' . urlencode("A similar requisition already exists in your department (Reference: {$duplicateRef}). Please add comments to the existing request instead.")) . '&duplicate_id=' . $duplicateId);
            return;
        }

        $urgency = $request->input('urgency', 'medium');
        $requisitionId = $this->requisitions->create($userId, $notes, $items, 'staff', $urgency);

        // Notify department members about new requisition
        $userDepartment = \App\Support\DepartmentHelper::getDepartmentFromRole($userRole);
        if ($userDepartment) {
            $departmentRoleKeys = \App\Support\DepartmentHelper::getRolesForDepartment($userDepartment);
            $notificationService = new \App\Services\Notifications\NotificationService();
            $requisition = $this->requisitions->find($requisitionId);
            $reference = $requisition['reference'] ?? 'N/A';
            
            // Notify all department members
            foreach ($departmentRoleKeys as $roleKey) {
                $notificationService->notifyRole($roleKey, 'New Department Requisition', 
                    sprintf('A new requisition %s has been created in your department (%s urgency).', 
                        $reference, ucfirst($urgency)),
                    ['requisition_id' => $requisitionId, 'reference' => $reference]
                );
            }
        }

        header('Location: ' . base_url('staff/dashboard/inventory/requisitions?success=Requisition%20created'));
    }

    public function updateRequisitionStatus(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'finance_manager', 'operation_manager']);

        $id = (int)$request->input('requisition_id');
        $status = $request->input('status');
        $supplier = trim($request->input('supplier_name', ''));

        if (!$id || !$status) {
            header('Location: ' . base_url('dashboard/inventory/requisitions?error=Invalid%20request'));
            return;
        }

        if ($status === 'approved') {
            // Only Operations Manager (or Admin) approves
            $roleKey = Auth::user()['role_key'] ?? (Auth::user()['role'] ?? '');
            if (!in_array($roleKey, ['operation_manager', 'admin'], true)) {
                header('Location: ' . base_url('staff/dashboard/inventory/requisitions?error=Only%20Operations%20can%20approve'));
                return;
            }
            if ($supplier === '') {
                header('Location: ' . base_url('staff/dashboard/inventory/requisitions?error=Supplier%20required'));
                return;
            }
            // Look up supplier by name to get ID
            $supplierRepo = new \App\Repositories\SupplierRepository();
            $supplierData = $supplierRepo->findByName($supplier);
            if (!$supplierData) {
                header('Location: ' . base_url('staff/dashboard/inventory/requisitions?error=Supplier%20not%20found'));
                return;
            }
            $items = $this->requisitions->items($id);
            $mapped = array_map(fn ($item) => [
                'inventory_item_id' => $item['inventory_item_id'],
                'quantity' => $item['quantity'],
                'unit_cost' => $item['unit_cost'] ?? ($item['avg_cost'] ?? 0),
            ], $items);
            $this->requisitions->createPurchaseOrder($id, (int)$supplierData['id'], $mapped);
        } elseif ($status === 'funded') {
            // Only Finance Manager (or Admin) funds
            $roleKey = Auth::user()['role_key'] ?? (Auth::user()['role'] ?? '');
            if (!in_array($roleKey, ['finance_manager', 'admin'], true)) {
                header('Location: ' . base_url('staff/dashboard/inventory/requisitions?error=Only%20Finance%20can%20fund'));
                return;
            }
        }

        $this->requisitions->updateStatus($id, $status);

        header('Location: ' . base_url('staff/dashboard/inventory/requisitions'));
    }

    public function approveDirectorRequisition(Request $request): void
    {
        Auth::requireRoles(['admin', 'director']);
        
        $id = (int)$request->input('requisition_id');
        $action = $request->input('action'); // 'approve' or 'reject'
        $directorNotes = trim($request->input('director_notes', ''));
        
        if (!$id || !$action) {
            header('Location: ' . base_url('staff/dashboard/inventory/requisitions?error=Invalid%20request'));
            return;
        }
        
        $user = Auth::user();
        
        if ($action === 'approve') {
            $this->requisitions->approveDirector($id, (int)$user['id'], $directorNotes);
        } else {
            $this->requisitions->rejectDirector($id, (int)$user['id'], $directorNotes);
        }
        
        header('Location: ' . base_url('staff/dashboard/inventory/requisitions?success=' . ($action === 'approve' ? 'Director%20approved' : 'Director%20rejected')));
    }

    public function receivePurchaseOrder(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'operation_manager']);
        InventoryPermission::require('inventory.requisitions.receive');

        $poId = (int)$request->input('purchase_order_id');
        $locationId = (int)$request->input('location_id');

        if (!$poId || !$locationId) {
            header('Location: ' . base_url('dashboard/inventory/requisitions?error=Invalid%20selection'));
            return;
        }

        $items = $this->requisitions->purchaseOrderItems($poId);
        foreach ($items as $item) {
            $this->inventoryService->receiveStock((int)$item['inventory_item_id'], $locationId, (float)$item['quantity'], 'PO #' . $poId);
        }

        db()->prepare('UPDATE purchase_orders SET status = :status WHERE id = :id')->execute([
            'status' => 'received',
            'id' => $poId,
        ]);

        header('Location: ' . base_url('dashboard/inventory/requisitions?success=received'));
    }

    public function completeRequisition(Request $request): void
    {
        // Release approved requisitions to staff; OM/Admin only
        Auth::requireRoles(['director', 'admin', 'operation_manager']);

        $reqId = (int)$request->input('requisition_id');
        $locationId = (int)$request->input('location_id');
        if (!$reqId || !$locationId) {
            header('Location: ' . base_url('dashboard/inventory/requisitions?error=Invalid%20request'));
            return;
        }

        // Fetch items and deduct
        $items = $this->requisitions->items($reqId);
        foreach ($items as $item) {
            $qty = (float)$item['quantity'];
            if ($qty > 0) {
                $this->inventoryService->deductStock((int)$item['inventory_item_id'], $locationId, $qty, 'REQ #' . $reqId, 'Requisition release', 'requisition');
            }
        }

        // Mark requisition as received/completed
        $this->requisitions->updateStatus($reqId, 'received');

        header('Location: ' . base_url('staff/dashboard/inventory/requisitions?success=released'));
    }

    public function verifyOpsRequisition(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager', 'director']);

        $id = (int)$request->input('id');
        $action = $request->input('action'); // 'approve' or 'reject'
        $opsNotes = trim($request->input('ops_notes', ''));
        $costEstimate = $request->input('cost_estimate') ? (float)$request->input('cost_estimate') : null;

        if (!$id || !$action) {
            header('Location: ' . base_url('staff/dashboard/inventory/requisitions?error=Invalid%20request'));
            return;
        }

        try {
            $user = Auth::user();
            $this->requisitions->verifyOps($id, (int)$user['id'], $opsNotes, $action === 'approve', $costEstimate);
            $message = $action === 'approve' ? 'Requisition%20verified%20by%20Ops' : 'Requisition%20rejected';
            header('Location: ' . base_url('staff/dashboard/inventory/requisitions?success=' . $message));
        } catch (\Exception $e) {
            header('Location: ' . base_url('staff/dashboard/inventory/requisitions?error=' . urlencode($e->getMessage())));
        }
    }

    public function approveFinanceRequisition(Request $request): void
    {
        Auth::requireRoles(['admin', 'finance_manager', 'director']);

        $id = (int)$request->input('id');
        $action = $request->input('action'); // 'approve' or 'reject'
        $financeNotes = trim($request->input('finance_notes', ''));

        if (!$id || !$action) {
            header('Location: ' . base_url('staff/dashboard/inventory/requisitions?error=Invalid%20request'));
            return;
        }

        try {
            $user = Auth::user();
            if ($action === 'approve') {
                $this->requisitions->approveFinance($id, (int)$user['id'], $financeNotes);
                $message = 'Requisition%20approved%20by%20Finance';
            } else {
                $this->requisitions->rejectFinance($id, (int)$user['id'], $financeNotes);
                $message = 'Requisition%20rejected%20by%20Finance';
            }
            header('Location: ' . base_url('staff/dashboard/inventory/requisitions?success=' . $message));
        } catch (\Exception $e) {
            header('Location: ' . base_url('staff/dashboard/inventory/requisitions?error=' . urlencode($e->getMessage())));
        }
    }

    public function assignSupplierRequisition(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager', 'finance_manager', 'director']);

        $id = (int)$request->input('id');
        $supplierId = (int)$request->input('supplier_id');

        if (!$id || !$supplierId) {
            header('Location: ' . base_url('staff/dashboard/inventory/requisitions?error=Invalid%20request'));
            return;
        }

        try {
            $this->requisitions->assignSupplier($id, $supplierId);
            header('Location: ' . base_url('staff/dashboard/inventory/requisitions?success=Supplier%20assigned'));
        } catch (\Exception $e) {
            header('Location: ' . base_url('staff/dashboard/inventory/requisitions?error=' . urlencode($e->getMessage())));
        }
    }

    public function createPOFromRequisition(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager', 'finance_manager', 'director']);

        $id = (int)$request->input('id');
        $supplierId = (int)$request->input('supplier_id');
        $expectedDate = $request->input('expected_date');

        if (!$id || !$supplierId) {
            header('Location: ' . base_url('staff/dashboard/inventory/requisitions?error=Invalid%20request'));
            return;
        }

        try {
            $requisition = $this->requisitions->find($id);
            if (!$requisition || $requisition['status'] !== 'approved') {
                header('Location: ' . base_url('staff/dashboard/inventory/requisitions?error=Requisition%20must%20be%20approved'));
                return;
            }

            $items = [];
            foreach ($requisition['items'] as $item) {
                $items[] = [
                    'inventory_item_id' => (int)$item['inventory_item_id'],
                    'quantity' => (float)$item['quantity'],
                    'unit_cost' => (float)($item['unit_cost'] ?? 0),
                ];
            }

            $this->requisitions->createPurchaseOrder($id, $supplierId, $items, $expectedDate);
            header('Location: ' . base_url('staff/dashboard/inventory/requisitions?success=Purchase%20Order%20created'));
        } catch (\Exception $e) {
            header('Location: ' . base_url('staff/dashboard/inventory/requisitions?error=' . urlencode($e->getMessage())));
        }
    }

    public function autoImport(Request $request): void
    {
        // Admin or Ops triggers auto-import/mapping
        Auth::requireRoles(['director', 'admin', 'operation_manager']);

        $posRepo = new \App\Repositories\PosItemRepository();
        // get many items; safe cap
        $unmapped = $posRepo->unmappedItems(1000);

        $created = 0;
        $mapped = 0;
        foreach ($unmapped as $row) {
            $name = $row['name'] ?? 'POS Item';
            $sku = trim((string)($row['sku'] ?? ''));
            $category = $row['category'] ?? null;

            // derive an inventory SKU if none
            $invSku = $sku !== '' ? 'INV-' . $sku : 'INV-' . strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $name), 0, 12));

            $existing = $this->inventory->findBySku($invSku);
            $inventoryItemId = $existing['id'] ?? null;
            if (!$inventoryItemId) {
                $inventoryItemId = $this->inventory->createItem([
                    'name' => $name,
                    'sku' => $invSku,
                    'unit' => 'unit',
                    'category' => $category,
                    'reorder_point' => 0,
                    'avg_cost' => 0,
                    'is_pos_item' => 1,
                    'status' => 'active',
                    'allow_negative' => 1, // show immediately in POS even before first stock
                ]);
                $created++;
            }

            $this->inventory->ensurePosComponent((int)$row['id'], (int)$inventoryItemId, 1.0);
            $mapped++;
        }

        header('Location: ' . base_url('dashboard/inventory?success=autoimport&created=' . $created . '&mapped=' . $mapped));
    }

    public function createItem(): void
    {
        Auth::requireRoles(['director', 'admin', 'operation_manager']);
        
        $categories = $this->inventory->categories();
        
        $locations = $this->inventoryService->locations();
        
        $this->view('dashboard/inventory/item-form', [
            'item' => null,
            'categories' => $categories,
            'locations' => $locations,
            'mode' => 'create'
        ]);
    }

    public function editItem(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'operation_manager']);
        
        $itemId = (int)$request->input('id');
        if (!$itemId) {
            header('Location: ' . base_url('staff/dashboard/inventory?error=Invalid%20item'));
            return;
        }
        
        $item = $this->inventory->getItem($itemId);
        if (!$item) {
            header('Location: ' . base_url('staff/dashboard/inventory?error=Item%20not%20found'));
            return;
        }
        
        $categories = $this->inventory->categories();
        $locations = $this->inventoryService->locations();
        
        $this->view('dashboard/inventory/item-form', [
            'item' => $item,
            'categories' => $categories,
            'locations' => $locations,
            'mode' => 'edit'
        ]);
    }

    public function storeItem(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'operation_manager']);
        
        $name = trim($request->input('name', ''));
        $sku = trim($request->input('sku', ''));
        $unit = trim($request->input('unit', 'unit'));
        $category = trim($request->input('category', ''));
        $reorderPoint = (float)$request->input('reorder_point', 0);
        $avgCost = (float)$request->input('avg_cost', 0);
        $isPosItem = (int)$request->input('is_pos_item', 0);
        $status = $request->input('status', 'active');
        $allowNegative = (int)$request->input('allow_negative', 0);
        
        if (empty($name)) {
            header('Location: ' . base_url('staff/dashboard/inventory/item/create?error=Name%20is%20required'));
            return;
        }
        
        // Generate SKU if not provided
        if (empty($sku)) {
            $sku = 'INV-' . strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $name), 0, 12)) . '-' . time();
        }
        
        // Check if SKU already exists
        $existing = $this->inventory->findBySku($sku);
        if ($existing) {
            header('Location: ' . base_url('staff/dashboard/inventory/item/create?error=SKU%20already%20exists'));
            return;
        }
        
        try {
            $itemId = $this->inventory->createItem([
                'name' => $name,
                'sku' => $sku,
                'unit' => $unit,
                'category' => $category ?: null,
                'reorder_point' => $reorderPoint,
                'avg_cost' => $avgCost,
                'selling_price' => $sellingPrice,
                'is_pos_item' => $isPosItem,
                'status' => $status,
                'allow_negative' => $allowNegative,
            ]);
            
            // Handle initial stock levels
            $stockLevels = $request->input('stock', []);
            if (is_array($stockLevels) && !empty($stockLevels)) {
                foreach ($stockLevels as $locationId => $quantity) {
                    $locationId = (int)$locationId;
                    $quantity = (float)$quantity;
                    if ($locationId > 0 && $quantity > 0) {
                        $this->inventoryService->receiveStock(
                            $itemId, 
                            $locationId, 
                            $quantity, 
                            'Initial Stock',
                            'Initial stock when item was created'
                        );
                    }
                }
            }
            
            header('Location: ' . base_url('staff/dashboard/inventory?success=Item%20created'));
        } catch (\Exception $e) {
            header('Location: ' . base_url('staff/dashboard/inventory/item/create?error=' . urlencode($e->getMessage())));
        }
    }

    public function updateItem(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'operation_manager']);
        
        $itemId = (int)$request->input('id');
        if (!$itemId) {
            header('Location: ' . base_url('staff/dashboard/inventory?error=Invalid%20item'));
            return;
        }
        
        $item = $this->inventory->getItem($itemId);
        if (!$item) {
            header('Location: ' . base_url('staff/dashboard/inventory?error=Item%20not%20found'));
            return;
        }
        
        $name = trim($request->input('name', ''));
        $sku = trim($request->input('sku', ''));
        $unit = trim($request->input('unit', 'unit'));
        $category = trim($request->input('category', ''));
        $reorderPoint = (float)$request->input('reorder_point', 0);
        $avgCost = (float)$request->input('avg_cost', 0);
        $sellingPrice = (float)$request->input('selling_price', 0);
        $isPosItem = (int)$request->input('is_pos_item', 0);
        $status = $request->input('status', 'active');
        $allowNegative = (int)$request->input('allow_negative', 0);
        
        if (empty($name)) {
            header('Location: ' . base_url('staff/dashboard/inventory/item/edit?id=' . $itemId . '&error=Name%20is%20required'));
            return;
        }
        
        // Check if SKU already exists (excluding current item)
        if (!empty($sku)) {
            $existing = $this->inventory->findBySku($sku);
            if ($existing && (int)$existing['id'] !== $itemId) {
                header('Location: ' . base_url('staff/dashboard/inventory/item/edit?id=' . $itemId . '&error=SKU%20already%20exists'));
                return;
            }
        }
        
        try {
            $this->inventory->updateItem($itemId, [
                'name' => $name,
                'sku' => $sku ?: $item['sku'],
                'unit' => $unit,
                'category' => $category ?: null,
                'reorder_point' => $reorderPoint,
                'avg_cost' => $avgCost,
                'selling_price' => $sellingPrice,
                'is_pos_item' => $isPosItem,
                'status' => $status,
                'allow_negative' => $allowNegative,
            ]);
            
            // Handle stock level updates
            $stockLevels = $request->input('stock', []);
            if (is_array($stockLevels)) {
                $inventoryRepo = new \App\Repositories\InventoryRepository();
                foreach ($stockLevels as $locationId => $newQuantity) {
                    $locationId = (int)$locationId;
                    $newQuantity = (float)$newQuantity;
                    if ($locationId > 0) {
                        // Use adjust method to set exact quantity (creates movement record)
                        $inventoryRepo->adjust(
                            $itemId, 
                            $locationId, 
                            $newQuantity, 
                            'Stock Update',
                            'Stock level updated via item edit form'
                        );
                    }
                }
            }
            
            header('Location: ' . base_url('staff/dashboard/inventory?success=Item%20updated'));
        } catch (\Exception $e) {
            header('Location: ' . base_url('staff/dashboard/inventory/item/edit?id=' . $itemId . '&error=' . urlencode($e->getMessage())));
        }
    }

    public function deleteItem(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'operation_manager']);
        
        $itemId = (int)$request->input('id');
        if (!$itemId) {
            header('Location: ' . base_url('staff/dashboard/inventory?error=Invalid%20item'));
            return;
        }
        
        try {
            $this->inventory->deleteItem($itemId);
            header('Location: ' . base_url('staff/dashboard/inventory?success=Item%20deleted'));
        } catch (\Exception $e) {
            header('Location: ' . base_url('staff/dashboard/inventory?error=' . urlencode($e->getMessage())));
        }
    }
}

