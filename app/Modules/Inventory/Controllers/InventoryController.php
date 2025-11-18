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

        $this->view('dashboard/inventory/requisitions', [
            'requisitions' => $this->requisitions->all(),
            'inventoryItems' => $this->inventory->allItems(),
            'locations' => $this->inventoryService->locations(),
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

        $this->requisitions->create((int)(Auth::user()['id'] ?? 0), $notes, $items);

        header('Location: ' . base_url('dashboard/inventory/requisitions?success=1'));
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
                header('Location: ' . base_url('dashboard/inventory/requisitions?error=Only%20Operations%20can%20approve'));
                return;
            }
            if ($supplier === '') {
                header('Location: ' . base_url('dashboard/inventory/requisitions?error=Supplier%20required'));
                return;
            }
            $items = $this->requisitions->items($id);
            $mapped = array_map(fn ($item) => [
                'inventory_item_id' => $item['inventory_item_id'],
                'quantity' => $item['quantity'],
                'unit_cost' => $item['unit_cost'] ?? ($item['avg_cost'] ?? 0),
            ], $items);
            $this->requisitions->createPurchaseOrder($id, $supplier, $mapped);
        } elseif ($status === 'funded') {
            // Only Finance Manager (or Admin) funds
            $roleKey = Auth::user()['role_key'] ?? (Auth::user()['role'] ?? '');
            if (!in_array($roleKey, ['finance_manager', 'admin'], true)) {
                header('Location: ' . base_url('dashboard/inventory/requisitions?error=Only%20Finance%20can%20fund'));
                return;
            }
        }

        $this->requisitions->updateStatus($id, $status);

        header('Location: ' . base_url('dashboard/inventory/requisitions'));
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

        header('Location: ' . base_url('dashboard/inventory/requisitions?success=released'));
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
}

