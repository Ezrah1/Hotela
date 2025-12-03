<?php

namespace App\Repositories;

use PDO;

class RequisitionRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    public function all(?string $type = null, ?string $status = null, ?array $departmentRoleKeys = null): array
    {
        $conditions = [];
        $params = [];

        if ($type) {
            $conditions[] = 'requisitions.type = :type';
            $params['type'] = $type;
        }

        if ($status) {
            $conditions[] = 'requisitions.status = :status';
            $params['status'] = $status;
        }

        // Filter by department if role keys provided (for department-level visibility)
        if ($departmentRoleKeys && !empty($departmentRoleKeys)) {
            $placeholders = [];
            foreach ($departmentRoleKeys as $index => $roleKey) {
                $param = 'role_key_' . $index;
                $params[$param] = $roleKey;
                $placeholders[] = ":{$param}";
            }
            $conditions[] = 'users.role_key IN (' . implode(', ', $placeholders) . ')';
        }

        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $stmt = $this->db->prepare('
            SELECT requisitions.*, 
                   users.name AS requester,
                   users.role_key AS requester_role_key,
                   u1.name AS ops_verified_by_name,
                   u2.name AS finance_approved_by_name,
                   s.name AS supplier_name
            FROM requisitions
            LEFT JOIN users ON users.id = requisitions.requested_by
            LEFT JOIN users u1 ON u1.id = requisitions.ops_verified_by
            LEFT JOIN users u2 ON u2.id = requisitions.finance_approved_by
            LEFT JOIN suppliers s ON s.id = requisitions.supplier_id
            ' . $whereClause . '
            ORDER BY requisitions.created_at DESC
        ');
        $stmt->execute($params);
        $requisitions = $stmt->fetchAll();

        foreach ($requisitions as &$requisition) {
            $requisition['items'] = $this->items((int)$requisition['id']);
            $requisition['purchase_order'] = $this->purchaseOrderByRequisition((int)$requisition['id']);
        }

        return $requisitions;
    }

    public function create(int $userId, string $notes, array $items, string $type = 'staff', string $urgency = 'medium'): int
    {
        $reference = 'REQ-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
        $stmt = $this->db->prepare('
            INSERT INTO requisitions (reference, requested_by, status, type, urgency, notes) 
            VALUES (:reference, :user, :status, :type, :urgency, :notes)
        ');
        $stmt->execute([
            'reference' => $reference,
            'user' => $userId,
            'status' => 'pending',
            'type' => $type,
            'urgency' => $urgency,
            'notes' => $notes,
        ]);

        $requisitionId = (int)$this->db->lastInsertId();
        
        // Get item details to include preferred supplier
        $inventoryRepo = new \App\Repositories\InventoryRepository();
        
        // Check if requisition_items table has custom_item_name column
        $hasCustomColumn = false;
        try {
            $checkStmt = $this->db->query("SHOW COLUMNS FROM requisition_items LIKE 'custom_item_name'");
            $hasCustomColumn = $checkStmt->rowCount() > 0;
        } catch (\Exception $e) {
            // Column doesn't exist, will need to add it
        }
        
        if ($hasCustomColumn) {
            $itemStmt = $this->db->prepare('
                INSERT INTO requisition_items (requisition_id, inventory_item_id, custom_item_name, quantity, preferred_supplier_id) 
                VALUES (:req, :item, :custom_name, :qty, :supplier)
            ');
        } else {
            // Fallback for older schema - only allow inventory items
            $itemStmt = $this->db->prepare('
                INSERT INTO requisition_items (requisition_id, inventory_item_id, quantity, preferred_supplier_id) 
                VALUES (:req, :item, :qty, :supplier)
            ');
        }
        
        foreach ($items as $item) {
            $inventoryItemId = isset($item['inventory_item_id']) && $item['inventory_item_id'] > 0 
                ? (int)$item['inventory_item_id'] 
                : null;
            $customItemName = isset($item['custom_item_name']) && !empty($item['custom_item_name'])
                ? trim($item['custom_item_name'])
                : null;
            $preferredSupplierId = null;
            
            // Validate: must have either inventory_item_id or custom_item_name
            if (!$inventoryItemId && !$customItemName) {
                continue; // Skip invalid items
            }
            
            // Get preferred supplier only for inventory items
            if ($inventoryItemId) {
                $itemData = $inventoryRepo->getItem($inventoryItemId);
                $preferredSupplierId = $itemData['preferred_supplier_id'] ?? null;
            }
            
            if ($hasCustomColumn) {
                $itemStmt->execute([
                    'req' => $requisitionId,
                    'item' => $inventoryItemId,
                    'custom_name' => $customItemName,
                    'qty' => $item['quantity'],
                    'supplier' => $preferredSupplierId,
                ]);
            } else {
                // Fallback: only process inventory items if custom column doesn't exist
                if ($inventoryItemId) {
                    $itemStmt->execute([
                        'req' => $requisitionId,
                        'item' => $inventoryItemId,
                        'qty' => $item['quantity'],
                        'supplier' => $preferredSupplierId,
                    ]);
                }
            }
        }

        // Send notification to Operations Manager for review
        $notificationService = new \App\Services\Notifications\NotificationService();
        $itemCount = count($items);
        $urgencyLabel = ucfirst($urgency);
        $notificationService->notifyRole('operation_manager', 'New Requisition Created', 
            sprintf('Requisition %s created (%s urgency, %d item%s). Requires Ops verification.', 
                $reference, $urgencyLabel, $itemCount, $itemCount !== 1 ? 's' : ''),
            ['requisition_id' => $requisitionId, 'reference' => $reference, 'type' => $type]
        );

        return $requisitionId;
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->db->prepare('UPDATE requisitions SET status = :status WHERE id = :id');
        $stmt->execute([
            'status' => $status,
            'id' => $id,
        ]);
    }


    public function purchaseOrderItems(int $purchaseOrderId): array
    {
        $stmt = $this->db->prepare('
            SELECT purchase_order_items.*, inventory_items.name, inventory_items.unit
            FROM purchase_order_items
            INNER JOIN inventory_items ON inventory_items.id = purchase_order_items.inventory_item_id
            WHERE purchase_order_items.purchase_order_id = :po
        ');
        $stmt->execute(['po' => $purchaseOrderId]);

        return $stmt->fetchAll();
    }

    public function purchaseOrderByRequisition(int $requisitionId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM purchase_orders WHERE requisition_id = :req ORDER BY id DESC LIMIT 1');
        $stmt->execute(['req' => $requisitionId]);
        $po = $stmt->fetch();

        if ($po) {
            $po['items'] = $this->purchaseOrderItems((int)$po['id']);
        }

        return $po ?: null;
    }

    public function items(int $requisitionId): array
    {
        // Check if custom_item_name column exists
        $hasCustomColumn = false;
        try {
            $checkStmt = $this->db->query("SHOW COLUMNS FROM requisition_items LIKE 'custom_item_name'");
            $hasCustomColumn = $checkStmt->rowCount() > 0;
        } catch (\Exception $e) {
            // Column doesn't exist
        }
        
        if ($hasCustomColumn) {
            $stmt = $this->db->prepare('
                SELECT ri.*, 
                       ii.name, 
                       ii.unit,
                       ii.sku,
                       ii.reorder_point,
                       ii.minimum_stock,
                       s.name AS preferred_supplier_name,
                       s.id AS preferred_supplier_id
                FROM requisition_items ri
                LEFT JOIN inventory_items ii ON ii.id = ri.inventory_item_id
                LEFT JOIN suppliers s ON s.id = ri.preferred_supplier_id
                WHERE ri.requisition_id = :req
                ORDER BY ri.id
            ');
        } else {
            $stmt = $this->db->prepare('
                SELECT requisition_items.*, 
                       inventory_items.name, 
                       inventory_items.unit,
                       inventory_items.sku,
                       inventory_items.reorder_point,
                       inventory_items.minimum_stock,
                       s.name AS preferred_supplier_name
                FROM requisition_items
                INNER JOIN inventory_items ON inventory_items.id = requisition_items.inventory_item_id
                LEFT JOIN suppliers s ON s.id = requisition_items.preferred_supplier_id
                WHERE requisition_items.requisition_id = :req
            ');
        }
        
        $stmt->execute(['req' => $requisitionId]);
        $items = $stmt->fetchAll();
        
        // For custom items, use custom_item_name as the name
        foreach ($items as &$item) {
            if (empty($item['name']) && !empty($item['custom_item_name'])) {
                $item['name'] = $item['custom_item_name'];
                $item['unit'] = $item['unit'] ?? 'unit';
                $item['sku'] = 'CUSTOM';
            }
        }
        
        return $items;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('
            SELECT requisitions.*, 
                   users.name AS requester,
                   users.role_key AS requester_role_key,
                   u1.name AS ops_verified_by_name,
                   u2.name AS finance_approved_by_name,
                   s.name AS supplier_name
            FROM requisitions
            LEFT JOIN users ON users.id = requisitions.requested_by
            LEFT JOIN users u1 ON u1.id = requisitions.ops_verified_by
            LEFT JOIN users u2 ON u2.id = requisitions.finance_approved_by
            LEFT JOIN suppliers s ON s.id = requisitions.supplier_id
            WHERE requisitions.id = :id
        ');
        $stmt->execute(['id' => $id]);
        $requisition = $stmt->fetch();

        if ($requisition) {
            $requisition['items'] = $this->items((int)$requisition['id']);
            $requisition['purchase_order'] = $this->purchaseOrderByRequisition((int)$requisition['id']);
        }

        return $requisition ?: null;
    }

    /**
     * Check for duplicate requisitions in the same department
     * Returns existing requisition if found, null otherwise
     */
    public function findDuplicate(int $userId, array $itemIds, ?string $department = null): ?array
    {
        // Get user's role to determine department
        $userStmt = $this->db->prepare('SELECT role_key FROM users WHERE id = :id');
        $userStmt->execute(['id' => $userId]);
        $user = $userStmt->fetch();
        
        if (!$user) {
            return null;
        }

        $userRoleKey = $user['role_key'];
        
        // If department not provided, get from role
        if (!$department) {
            $department = \App\Support\DepartmentHelper::getDepartmentFromRole($userRoleKey);
        }

        if (!$department) {
            return null;
        }

        // Get all role keys for this department
        $departmentRoleKeys = \App\Support\DepartmentHelper::getRolesForDepartment($department);

        if (empty($departmentRoleKeys)) {
            return null;
        }

        // Find open/pending requisitions in the same department with matching items
        $placeholders = [];
        $params = [];
        foreach ($departmentRoleKeys as $index => $roleKey) {
            $param = 'role_key_' . $index;
            $params[$param] = $roleKey;
            $placeholders[] = ":{$param}";
        }

        // Statuses that indicate an active/open requisition
        $openStatuses = ['pending', 'ops_verified', 'finance_approved', 'approved', 'ordered'];
        $statusPlaceholders = [];
        foreach ($openStatuses as $index => $status) {
            $param = 'status_' . $index;
            $params[$param] = $status;
            $statusPlaceholders[] = ":{$param}";
        }

        // Check for requisitions with matching items
        $itemPlaceholders = [];
        foreach ($itemIds as $index => $itemId) {
            $param = 'item_' . $index;
            $params[$param] = (int)$itemId;
            $itemPlaceholders[] = ":{$param}";
        }

        $sql = '
            SELECT DISTINCT r.*, u.role_key AS requester_role_key
            FROM requisitions r
            INNER JOIN users u ON u.id = r.requested_by
            INNER JOIN requisition_items ri ON ri.requisition_id = r.id
            WHERE u.role_key IN (' . implode(', ', $placeholders) . ')
            AND r.status IN (' . implode(', ', $statusPlaceholders) . ')
            AND ri.inventory_item_id IN (' . implode(', ', $itemPlaceholders) . ')
            ORDER BY r.created_at DESC
            LIMIT 1
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $duplicate = $stmt->fetch();

        if ($duplicate) {
            $duplicate['items'] = $this->items((int)$duplicate['id']);
        }

        return $duplicate ?: null;
    }

    public function verifyOps(int $id, int $userId, string $notes, bool $approve, ?float $costEstimate = null): void
    {
        if ($approve) {
            // Check if items exist and stock is sufficient for internal use
            $items = $this->items($id);
            $inventoryRepo = new \App\Repositories\InventoryRepository();
            $allItemsExist = true;
            $sufficientStock = true;

            foreach ($items as $item) {
                $itemData = $inventoryRepo->getItem((int)$item['inventory_item_id']);
                if (!$itemData) {
                    $allItemsExist = false;
                    break;
                }

            // Check stock levels across all locations
            $locations = $inventoryRepo->locations();
            $totalStock = 0;
            foreach ($locations as $location) {
                $totalStock += $inventoryRepo->level((int)$item['inventory_item_id'], (int)$location['id']);
            }
            if ($totalStock < (float)$item['quantity']) {
                $sufficientStock = false;
            }
            }

            if (!$allItemsExist) {
                throw new \Exception('Some items do not exist in inventory');
            }

            // If stock is insufficient, convert to procurement requisition
            if (!$sufficientStock) {
                $this->db->prepare('UPDATE requisitions SET type = "procurement" WHERE id = :id')->execute(['id' => $id]);
            }

            $stmt = $this->db->prepare('
                UPDATE requisitions 
                SET ops_verified = 1, 
                    ops_verified_by = :user, 
                    ops_verified_at = NOW(),
                    ops_notes = :notes,
                    cost_estimate = :cost,
                    status = "ops_verified"
                WHERE id = :id
            ');
            $stmt->execute([
                'id' => $id,
                'user' => $userId,
                'notes' => $notes,
                'cost' => $costEstimate,
            ]);

            // Send notification to Finance Manager
            $requisition = $this->find($id);
            $notificationService = new \App\Services\Notifications\NotificationService();
            $notificationService->notifyRole('finance_manager', 'Requisition Verified by Ops', 
                sprintf('Requisition %s has been verified by Operations. Cost estimate: KES %s. Requires Finance approval.', 
                    $requisition['reference'] ?? 'N/A', 
                    number_format($costEstimate ?? 0, 2)),
                ['requisition_id' => $id, 'reference' => $requisition['reference'] ?? '']
            );
        } else {
            $stmt = $this->db->prepare('
                UPDATE requisitions 
                SET ops_verified = 0, 
                    ops_verified_by = :user, 
                    ops_verified_at = NOW(),
                    ops_notes = :notes,
                    status = "rejected"
                WHERE id = :id
            ');
            $stmt->execute([
                'id' => $id,
                'user' => $userId,
                'notes' => $notes,
            ]);
        }
    }

    public function approveFinance(int $id, int $userId, string $notes): void
    {
        $stmt = $this->db->prepare('
            UPDATE requisitions 
            SET finance_approved = 1, 
                finance_approved_by = :user, 
                finance_approved_at = NOW(),
                finance_notes = :notes,
                status = "finance_approved"
            WHERE id = :id
        ');
        $stmt->execute([
            'id' => $id,
            'user' => $userId,
            'notes' => $notes,
        ]);

        // Send notification to Admin/Director for final approval
        $requisition = $this->find($id);
        $notificationService = new \App\Services\Notifications\NotificationService();
        $notificationService->notifyRole('admin', 'Requisition Approved by Finance', 
            sprintf('Requisition %s has been approved by Finance. Requires Director/Admin final approval and supplier assignment.', 
                $requisition['reference'] ?? 'N/A'),
            ['requisition_id' => $id, 'reference' => $requisition['reference'] ?? '']
        );
    }

    public function rejectFinance(int $id, int $userId, string $notes): void
    {
        $stmt = $this->db->prepare('
            UPDATE requisitions 
            SET finance_approved = 0, 
                finance_approved_by = :user, 
                finance_approved_at = NOW(),
                finance_notes = :notes,
                status = "rejected"
            WHERE id = :id
        ');
        $stmt->execute([
            'id' => $id,
            'user' => $userId,
            'notes' => $notes,
        ]);
    }

    public function assignSupplier(int $id, int $supplierId): void
    {
        $stmt = $this->db->prepare('
            UPDATE requisitions 
            SET supplier_id = :supplier
            WHERE id = :id
        ');
        $stmt->execute([
            'id' => $id,
            'supplier' => $supplierId,
        ]);
    }

    /**
     * Approve requisition by Director/Admin (final approval step)
     */
    public function approveDirector(int $id, int $userId, string $notes): void
    {
        $stmt = $this->db->prepare('
            UPDATE requisitions 
            SET director_approved = 1, 
                director_approved_by = :user, 
                director_approved_at = NOW(),
                director_notes = :notes,
                status = "approved"
            WHERE id = :id
        ');
        $stmt->execute([
            'id' => $id,
            'user' => $userId,
            'notes' => $notes,
        ]);

        // Send notification to Operations Manager that requisition is fully approved
        $requisition = $this->find($id);
        $notificationService = new \App\Services\Notifications\NotificationService();
        $notificationService->notifyRole('operation_manager', 'Requisition Fully Approved', 
            sprintf('Requisition %s has been fully approved by Director/Admin. Ready for purchase order creation.', 
                $requisition['reference'] ?? 'N/A'),
            ['requisition_id' => $id, 'reference' => $requisition['reference'] ?? '']
        );
    }

    /**
     * Reject requisition by Director/Admin
     */
    public function rejectDirector(int $id, int $userId, string $notes): void
    {
        $stmt = $this->db->prepare('
            UPDATE requisitions 
            SET director_approved = 0, 
                director_approved_by = :user, 
                director_approved_at = NOW(),
                director_notes = :notes,
                status = "rejected"
            WHERE id = :id
        ');
        $stmt->execute([
            'id' => $id,
            'user' => $userId,
            'notes' => $notes,
        ]);

        // Send notification to requester
        $requisition = $this->find($id);
        if ($requisition && $requisition['requested_by']) {
            $notificationService = new \App\Services\Notifications\NotificationService();
            $notificationService->notifyUser((int)$requisition['requested_by'], 'Requisition Rejected', 
                sprintf('Requisition %s has been rejected by Director/Admin. Reason: %s', 
                    $requisition['reference'] ?? 'N/A', $notes),
                ['requisition_id' => $id, 'reference' => $requisition['reference'] ?? '']
            );
        }
    }

    public function createPurchaseOrder(int $requisitionId, int $supplierId, array $items, ?string $expectedDate = null): int
    {
        $supplierRepo = new \App\Repositories\SupplierRepository();
        $supplier = $supplierRepo->find($supplierId);
        $supplierName = $supplier['name'] ?? 'Unknown Supplier';

        // Generate PO reference
        $reference = 'PO-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        
        // Check if reference column exists
        $hasReference = false;
        try {
            $checkStmt = $this->db->query("SHOW COLUMNS FROM purchase_orders LIKE 'reference'");
            $hasReference = $checkStmt->rowCount() > 0;
        } catch (\Exception $e) {
            // Column doesn't exist
        }

        if ($hasReference) {
            $stmt = $this->db->prepare('
                INSERT INTO purchase_orders (reference, requisition_id, supplier_id, supplier_name, status, expected_date)
                VALUES (:ref, :req, :supplier_id, :supplier, :status, :date)
            ');
            $stmt->execute([
                'ref' => $reference,
                'req' => $requisitionId,
                'supplier_id' => $supplierId,
                'supplier' => $supplierName,
                'status' => 'sent',
                'date' => $expectedDate,
            ]);
        } else {
            $stmt = $this->db->prepare('
                INSERT INTO purchase_orders (requisition_id, supplier_id, supplier_name, status, expected_date)
                VALUES (:req, :supplier_id, :supplier, :status, :date)
            ');
            $stmt->execute([
                'req' => $requisitionId,
                'supplier_id' => $supplierId,
                'supplier' => $supplierName,
                'status' => 'sent',
                'date' => $expectedDate,
            ]);
        }

        $poId = (int)$this->db->lastInsertId();
        $totalAmount = 0.00;
        
        $itemStmt = $this->db->prepare('
            INSERT INTO purchase_order_items (purchase_order_id, inventory_item_id, quantity, unit_cost)
            VALUES (:po, :item, :qty, :cost)
        ');
        foreach ($items as $item) {
            $unitCost = (float)($item['unit_cost'] ?? 0);
            $quantity = (float)($item['quantity'] ?? 0);
            $lineTotal = $unitCost * $quantity;
            $totalAmount += $lineTotal;
            
            $itemStmt->execute([
                'po' => $poId,
                'item' => $item['inventory_item_id'],
                'qty' => $quantity,
                'cost' => $unitCost,
            ]);
        }

        // Update total amount in purchase order
        $updateStmt = $this->db->prepare('UPDATE purchase_orders SET total_amount = :total WHERE id = :id');
        $updateStmt->execute(['total' => $totalAmount, 'id' => $poId]);

        $this->updateStatus($requisitionId, 'ordered');

        return $poId;
    }
}

