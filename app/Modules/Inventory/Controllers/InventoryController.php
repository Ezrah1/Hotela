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
        Auth::requireRoles(['admin', 'operation_manager', 'finance_manager', 'cashier']);

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

        $this->view('dashboard/inventory/index', [
            'canRequisitions' => $canRequisitions,
            'canApprove' => $canApprove,
            'canAdjust' => $canAdjust,
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

    public function requisitions(): void
    {
        Auth::requireRoles(['admin', 'operation_manager', 'finance_manager', 'cashier', 'service_agent', 'kitchen', 'housekeeping', 'ground', 'security']);

        $type = $_GET['type'] ?? null;
        $status = $_GET['status'] ?? null;
        $user = Auth::user();
        $userRole = $user['role_key'] ?? '';

        $allRequisitions = $this->requisitions->all($type, $status);
        
        // Filter by user role if not admin/ops/finance
        if (!in_array($userRole, ['admin', 'operation_manager', 'finance_manager', 'director'], true)) {
            $allRequisitions = array_filter($allRequisitions, function($req) use ($user) {
                return ($req['requested_by'] ?? null) == ($user['id'] ?? null);
            });
        }

        $this->view('dashboard/inventory/requisitions', [
            'requisitions' => $allRequisitions,
            'inventoryItems' => $this->inventory->allItems(),
            'locations' => $this->inventoryService->locations(),
            'userRole' => $userRole,
            'filters' => ['type' => $type, 'status' => $status],
        ]);
    }

    public function storeRequisition(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager', 'finance_manager', 'cashier', 'service_agent', 'kitchen', 'housekeeping', 'ground', 'security']);

        $notes = trim($request->input('notes', ''));

        $ids = $request->input('inventory_item_id');
        $qtys = $request->input('quantity');

        $items = [];
        if (is_array($ids) && is_array($qtys)) {
            foreach ($ids as $idx => $rawId) {
                $id = (int)$rawId;
                $qty = (float)($qtys[$idx] ?? 0);
                if ($id > 0 && $qty > 0) {
                    $items[] = ['inventory_item_id' => $id, 'quantity' => $qty];
                }
            }
        } else {
            $id = (int)$ids;
            $qty = (float)$qtys;
            if ($id > 0 && $qty > 0) {
                $items[] = ['inventory_item_id' => $id, 'quantity' => $qty];
            }
        }

        if (empty($items)) {
            header('Location: ' . base_url('dashboard/inventory/requisitions?error=Invalid%20inputs'));
            return;
        }

        $urgency = $request->input('urgency', 'medium');
        $this->requisitions->create((int)(Auth::user()['id'] ?? 0), $notes, $items, 'staff', $urgency);

        header('Location: ' . base_url('staff/dashboard/inventory/requisitions?success=Requisition%20created'));
    }

    public function updateRequisitionStatus(Request $request): void
    {
        Auth::requireRoles(['admin', 'finance_manager', 'operation_manager']);

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

    public function receivePurchaseOrder(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager']);
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
        Auth::requireRoles(['admin', 'operation_manager']);

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
        Auth::requireRoles(['admin', 'operation_manager']);

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
        Auth::requireRoles(['admin', 'operation_manager']);
        
        $categories = $this->inventory->categories();
        
        $this->view('dashboard/inventory/item-form', [
            'item' => null,
            'categories' => $categories,
            'mode' => 'create'
        ]);
    }

    public function editItem(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager']);
        
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
        
        $this->view('dashboard/inventory/item-form', [
            'item' => $item,
            'categories' => $categories,
            'mode' => 'edit'
        ]);
    }

    public function storeItem(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager']);
        
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
                'is_pos_item' => $isPosItem,
                'status' => $status,
                'allow_negative' => $allowNegative,
            ]);
            
            header('Location: ' . base_url('staff/dashboard/inventory?success=Item%20created'));
        } catch (\Exception $e) {
            header('Location: ' . base_url('staff/dashboard/inventory/item/create?error=' . urlencode($e->getMessage())));
        }
    }

    public function updateItem(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager']);
        
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
                'is_pos_item' => $isPosItem,
                'status' => $status,
                'allow_negative' => $allowNegative,
            ]);
            
            header('Location: ' . base_url('staff/dashboard/inventory?success=Item%20updated'));
        } catch (\Exception $e) {
            header('Location: ' . base_url('staff/dashboard/inventory/item/edit?id=' . $itemId . '&error=' . urlencode($e->getMessage())));
        }
    }

    public function deleteItem(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager']);
        
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

