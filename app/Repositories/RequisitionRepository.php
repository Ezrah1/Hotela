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

    public function all(?string $type = null, ?string $status = null): array
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

        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $stmt = $this->db->prepare('
            SELECT requisitions.*, 
                   users.name AS requester,
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
        
        $itemStmt = $this->db->prepare('
            INSERT INTO requisition_items (requisition_id, inventory_item_id, quantity, preferred_supplier_id) 
            VALUES (:req, :item, :qty, :supplier)
        ');
        foreach ($items as $item) {
            $itemData = $inventoryRepo->getItem((int)$item['inventory_item_id']);
            $preferredSupplierId = $itemData['preferred_supplier_id'] ?? null;
            
            $itemStmt->execute([
                'req' => $requisitionId,
                'item' => $item['inventory_item_id'],
                'qty' => $item['quantity'],
                'supplier' => $preferredSupplierId,
            ]);
        }

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
        $stmt = $this->db->prepare('
            SELECT requisition_items.*, 
                   inventory_items.name, 
                   inventory_items.unit,
                   inventory_items.reorder_point,
                   inventory_items.minimum_stock,
                   s.name AS preferred_supplier_name
            FROM requisition_items
            INNER JOIN inventory_items ON inventory_items.id = requisition_items.inventory_item_id
            LEFT JOIN suppliers s ON s.id = requisition_items.preferred_supplier_id
            WHERE requisition_items.requisition_id = :req
        ');
        $stmt->execute(['req' => $requisitionId]);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('
            SELECT requisitions.*, 
                   users.name AS requester,
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
                    cost_estimate = :cost
                WHERE id = :id
            ');
            $stmt->execute([
                'id' => $id,
                'user' => $userId,
                'notes' => $notes,
                'cost' => $costEstimate,
            ]);
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
                status = "approved"
            WHERE id = :id
        ');
        $stmt->execute([
            'id' => $id,
            'user' => $userId,
            'notes' => $notes,
        ]);
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

    public function createPurchaseOrder(int $requisitionId, int $supplierId, array $items, ?string $expectedDate = null): int
    {
        $supplierRepo = new \App\Repositories\SupplierRepository();
        $supplier = $supplierRepo->find($supplierId);
        $supplierName = $supplier['name'] ?? 'Unknown Supplier';

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

        $poId = (int)$this->db->lastInsertId();
        $itemStmt = $this->db->prepare('
            INSERT INTO purchase_order_items (purchase_order_id, inventory_item_id, quantity, unit_cost)
            VALUES (:po, :item, :qty, :cost)
        ');
        foreach ($items as $item) {
            $itemStmt->execute([
                'po' => $poId,
                'item' => $item['inventory_item_id'],
                'qty' => $item['quantity'],
                'cost' => $item['unit_cost'] ?? 0,
            ]);
        }

        $this->updateStatus($requisitionId, 'ordered');

        return $poId;
    }
}

