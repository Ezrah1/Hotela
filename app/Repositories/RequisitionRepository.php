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

    public function all(): array
    {
        $stmt = $this->db->query('
            SELECT requisitions.*, users.name AS requester
            FROM requisitions
            LEFT JOIN users ON users.id = requisitions.requested_by
            ORDER BY requisitions.created_at DESC
        ');
        $requisitions = $stmt->fetchAll();

        foreach ($requisitions as &$requisition) {
            $requisition['items'] = $this->items((int)$requisition['id']);
            $requisition['purchase_order'] = $this->purchaseOrderByRequisition((int)$requisition['id']);
        }

        return $requisitions;
    }

    public function create(int $userId, string $notes, array $items): int
    {
        $reference = 'REQ-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
        $stmt = $this->db->prepare('INSERT INTO requisitions (reference, requested_by, status, notes) VALUES (:reference, :user, :status, :notes)');
        $stmt->execute([
            'reference' => $reference,
            'user' => $userId,
            'status' => 'pending',
            'notes' => $notes,
        ]);

        $requisitionId = (int)$this->db->lastInsertId();
        $itemStmt = $this->db->prepare('INSERT INTO requisition_items (requisition_id, inventory_item_id, quantity) VALUES (:req, :item, :qty)');
        foreach ($items as $item) {
            $itemStmt->execute([
                'req' => $requisitionId,
                'item' => $item['inventory_item_id'],
                'qty' => $item['quantity'],
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

    public function createPurchaseOrder(int $requisitionId, string $supplierName, array $items, ?string $expectedDate = null): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO purchase_orders (requisition_id, supplier_name, status, expected_date)
            VALUES (:req, :supplier, :status, :date)
        ');
        $stmt->execute([
            'req' => $requisitionId,
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
            SELECT requisition_items.*, inventory_items.name, inventory_items.unit
            FROM requisition_items
            INNER JOIN inventory_items ON inventory_items.id = requisition_items.inventory_item_id
            WHERE requisition_items.requisition_id = :req
        ');
        $stmt->execute(['req' => $requisitionId]);

        return $stmt->fetchAll();
    }
}

