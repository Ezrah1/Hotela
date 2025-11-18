<?php

namespace App\Repositories;

use PDO;

class PosSaleRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    public function create(array $saleData, array $items): int
    {
        $reference = $saleData['reference'] ?? $this->generateReference();
        $tenantId = \App\Support\Tenant::id();
        $validItemIds = [];

        // Validate that all item_ids exist and belong to the tenant
        if (!empty($items)) {
            $itemIds = array_unique(array_filter(array_column($items, 'item_id')));
            
            if (!empty($itemIds)) {
                $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
                
                $sql = "SELECT id FROM pos_items WHERE id IN ($placeholders)";
                $params = $itemIds;
                
                if ($tenantId !== null) {
                    $sql .= " AND tenant_id = ?";
                    $params[] = $tenantId;
                }
                
                $checkStmt = $this->db->prepare($sql);
                $checkStmt->execute($params);
                
                $validItemIds = array_column($checkStmt->fetchAll(), 'id');
                $invalidItemIds = array_diff($itemIds, $validItemIds);
                
                if (!empty($invalidItemIds)) {
                    throw new \RuntimeException('Invalid or deleted item IDs: ' . implode(', ', $invalidItemIds) . '. Please refresh the POS page and try again.');
                }
            }
        }

        $stmt = $this->db->prepare('
            INSERT INTO pos_sales (tenant_id, reference, user_id, till_id, payment_type, total, notes, reservation_id)
            VALUES (:tenant_id, :reference, :user_id, :till_id, :payment_type, :total, :notes, :reservation_id)
        ');
        $stmt->execute([
            'tenant_id' => $tenantId,
            'reference' => $reference,
            'user_id' => $saleData['user_id'],
            'till_id' => $saleData['till_id'] ?? null,
            'payment_type' => $saleData['payment_type'] ?? 'cash',
            'total' => $saleData['total'],
            'notes' => $saleData['notes'] ?? null,
            'reservation_id' => $saleData['reservation_id'] ?? null,
        ]);

        $saleId = (int)$this->db->lastInsertId();

        $itemStmt = $this->db->prepare('
            INSERT INTO pos_sale_items (sale_id, item_id, quantity, price, line_total)
            VALUES (:sale_id, :item_id, :quantity, :price, :line_total)
        ');

        foreach ($items as $item) {
            $itemId = (int)($item['item_id'] ?? 0);
            
            // Only insert if item_id is valid and exists
            if ($itemId > 0 && (empty($validItemIds) || in_array($itemId, $validItemIds))) {
                $itemStmt->execute([
                    'sale_id' => $saleId,
                    'item_id' => $itemId,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'line_total' => $item['line_total'],
                ]);
            }
        }

        return $saleId;
    }

    public function all(?string $filter = null, int $limit = 100): array
    {
        $params = [];
        $sql = '
            SELECT 
                ps.id, ps.reference, ps.payment_type, ps.total, ps.notes, ps.created_at,
                u.name AS user_name,
                t.name AS till_name,
                r.reference AS reservation_reference,
                r.guest_name AS reservation_guest_name
            FROM pos_sales ps
            LEFT JOIN users u ON u.id = ps.user_id
            LEFT JOIN pos_tills t ON t.id = ps.till_id
            LEFT JOIN reservations r ON r.id = ps.reservation_id
            WHERE 1 = 1
        ';

        $tenantId = \App\Support\Tenant::id();
        if ($tenantId !== null) {
            $sql .= ' AND ps.tenant_id = :tenant_id';
            $params['tenant_id'] = $tenantId;
        }

        if ($filter === 'today') {
            $sql .= ' AND DATE(ps.created_at) = CURDATE()';
        } elseif ($filter === 'week') {
            $sql .= ' AND ps.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)';
        } elseif ($filter === 'month') {
            $sql .= ' AND ps.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)';
        }

        $sql .= ' ORDER BY ps.created_at DESC LIMIT ' . (int)$limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $params = ['id' => $id];
        $sql = '
            SELECT 
                ps.*,
                u.name AS user_name,
                t.name AS till_name,
                r.reference AS reservation_reference,
                r.guest_name AS reservation_guest_name
            FROM pos_sales ps
            LEFT JOIN users u ON u.id = ps.user_id
            LEFT JOIN pos_tills t ON t.id = ps.till_id
            LEFT JOIN reservations r ON r.id = ps.reservation_id
            WHERE ps.id = :id
        ';

        $tenantId = \App\Support\Tenant::id();
        if ($tenantId !== null) {
            $sql .= ' AND ps.tenant_id = :tenant_id';
            $params['tenant_id'] = $tenantId;
        }

        $sql .= ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $sale = $stmt->fetch();
        return $sale ?: null;
    }

    public function getItems(int $saleId): array
    {
        $sql = '
            SELECT 
                psi.*,
                pi.name AS item_name
            FROM pos_sale_items psi
            INNER JOIN pos_items pi ON pi.id = psi.item_id
            WHERE psi.sale_id = :sale_id
            ORDER BY psi.id
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['sale_id' => $saleId]);

        return $stmt->fetchAll();
    }

    protected function generateReference(): string
    {
        return 'POS-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    }
}
