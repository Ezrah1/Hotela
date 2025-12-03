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
        
        $validItemIds = [];

        // Validate that all item_ids exist - filter out invalid ones instead of throwing error
        if (!empty($items)) {
            $itemIds = array_unique(array_filter(array_column($items, 'item_id')));
            
            if (!empty($itemIds)) {
                $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
                
                $sql = "SELECT id FROM pos_items WHERE id IN ($placeholders)";
                $params = $itemIds;
                
                $checkStmt = $this->db->prepare($sql);
                $checkStmt->execute($params);
                
                $validItemIds = array_column($checkStmt->fetchAll(), 'id');
                $invalidItemIds = array_diff($itemIds, $validItemIds);
                
                // Filter out invalid items instead of throwing error
                if (!empty($invalidItemIds)) {
                    // Remove invalid items from the items array
                    $items = array_filter($items, function($item) use ($validItemIds) {
                        return in_array((int)($item['item_id'] ?? 0), $validItemIds, true);
                    });
                    $items = array_values($items); // Re-index
                    
                    // If no valid items remain, throw error
                    if (empty($items)) {
                        throw new \RuntimeException('All items in the order are invalid or deleted. Please refresh the POS page and try again.');
                    }
                }
            }
        }

        $stmt = $this->db->prepare('
            INSERT INTO pos_sales (reference, user_id, till_id, payment_type, total, notes, reservation_id, mpesa_phone, mpesa_checkout_request_id, mpesa_merchant_request_id, mpesa_status, payment_status)
            VALUES (:reference, :user_id, :till_id, :payment_type, :total, :notes, :reservation_id, :mpesa_phone, :mpesa_checkout_request_id, :mpesa_merchant_request_id, :mpesa_status, :payment_status)
        ');
        $stmt->execute([
            'reference' => $reference,
            'user_id' => $saleData['user_id'],
            'till_id' => $saleData['till_id'] ?? null,
            'payment_type' => $saleData['payment_type'] ?? 'cash',
            'total' => $saleData['total'],
            'notes' => $saleData['notes'] ?? null,
            'reservation_id' => $saleData['reservation_id'] ?? null,
            'mpesa_phone' => $saleData['mpesa_phone'] ?? null,
            'mpesa_checkout_request_id' => $saleData['mpesa_checkout_request_id'] ?? null,
            'mpesa_merchant_request_id' => $saleData['mpesa_merchant_request_id'] ?? null,
            'mpesa_status' => $saleData['mpesa_status'] ?? null,
            // All payments start as 'pending' unless explicitly set or M-Pesa is completed
            // Staff must confirm payment was received for all payment types
            'payment_status' => $saleData['payment_status'] ?? ($saleData['mpesa_status'] === 'completed' ? 'paid' : 'pending'),
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

    public function updateMpesaStatus(int $saleId, string $status, ?string $transactionId = null): void
    {
        $sql = 'UPDATE pos_sales SET mpesa_status = :status, payment_status = :payment_status';
        $params = [
            'id' => $saleId,
            'status' => $status,
            'payment_status' => $status === 'completed' ? 'paid' : ($status === 'failed' ? 'failed' : 'pending'),
        ];
        
        if ($transactionId !== null) {
            $sql .= ', mpesa_transaction_id = :transaction_id';
            $params['transaction_id'] = $transactionId;
        }
        
        $sql .= ' WHERE id = :id';
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function findByCheckoutRequestId(string $checkoutRequestId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM pos_sales WHERE mpesa_checkout_request_id = :checkout_request_id LIMIT 1');
        $stmt->execute(['checkout_request_id' => $checkoutRequestId]);
        return $stmt->fetch() ?: null;
    }

    public function findByReference(string $reference): ?array
    {
        $stmt = $this->db->prepare('
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
            WHERE ps.reference = :reference
            LIMIT 1
        ');
        $stmt->execute(['reference' => $reference]);
        return $stmt->fetch() ?: null;
    }

    protected function generateReference(): string
    {
        return 'POS-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    }
}
