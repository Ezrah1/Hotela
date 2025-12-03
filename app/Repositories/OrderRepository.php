<?php

namespace App\Repositories;

use PDO;

class OrderRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    /**
     * Get all orders with filters
     */
    public function all(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $conditions = [];
        $params = [];

        // Status filter
        if (!empty($filters['status'])) {
            $conditions[] = 'o.status = :status';
            $params['status'] = $filters['status'];
        }

        // Order type filter
        if (!empty($filters['order_type'])) {
            $conditions[] = 'o.order_type = :order_type';
            $params['order_type'] = $filters['order_type'];
        }

        // Date range filter
        if (!empty($filters['date_from'])) {
            $conditions[] = 'DATE(o.created_at) >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $conditions[] = 'DATE(o.created_at) <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }

        // Assigned staff filter
        if (!empty($filters['assigned_staff_id'])) {
            $conditions[] = 'o.assigned_staff_id = :assigned_staff_id';
            $params['assigned_staff_id'] = (int)$filters['assigned_staff_id'];
        }

        // Updated since filter (for recent updates)
        if (!empty($filters['updated_since'])) {
            $conditions[] = 'o.updated_at >= :updated_since';
            $params['updated_since'] = $filters['updated_since'];
        }

        // Search filter
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $conditions[] = '(o.reference LIKE :search OR o.customer_name LIKE :search OR o.room_number LIKE :search OR o.customer_phone LIKE :search)';
            $params['search'] = $search;
        }

        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $sql = "
            SELECT o.*,
                   u.name as user_name,
                   u.email as user_email,
                   r.reference as reservation_reference,
                   r.guest_name as reservation_guest_name,
                   s.name as assigned_staff_name
            FROM orders o
            LEFT JOIN users u ON u.id = o.user_id
            LEFT JOIN reservations r ON r.id = o.reservation_id
            LEFT JOIN users s ON s.id = o.assigned_staff_id
            {$whereClause}
            ORDER BY o.created_at DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Load items for each order
        foreach ($orders as &$order) {
            $order['items'] = $this->getItems((int)$order['id']);
            $order['status_logs'] = $this->getStatusLogs((int)$order['id']);
            $order['comments'] = $this->getComments((int)$order['id']);
        }

        return $orders;
    }

    /**
     * Find order by ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT o.*,
                   u.name as user_name,
                   u.email as user_email,
                   r.reference as reservation_reference,
                   r.guest_name as reservation_guest_name,
                   s.name as assigned_staff_name
            FROM orders o
            LEFT JOIN users u ON u.id = o.user_id
            LEFT JOIN reservations r ON r.id = o.reservation_id
            LEFT JOIN users s ON s.id = o.assigned_staff_id
            WHERE o.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            return null;
        }

        $order['items'] = $this->getItems($id);
        $order['status_logs'] = $this->getStatusLogs($id);
        $order['comments'] = $this->getComments($id);

        return $order;
    }

    /**
     * Get orders for a guest by email or phone
     */
    public function listForGuest(string $identifier): array
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return [];
        }

        $params = [];
        $conditions = [];

        if (str_contains($identifier, '@')) {
            $params['customer_email'] = strtolower(trim($identifier));
            $conditions[] = 'LOWER(TRIM(customer_email)) = :customer_email';
        } else {
            $sanitized = preg_replace('/[^0-9]/', '', $identifier);
            if ($sanitized === '') {
                return [];
            }
            $params['customer_phone'] = $sanitized;
            // Normalize phone numbers by removing all non-numeric characters for comparison
            $conditions[] = 'REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(customer_phone, " ", ""), "-", ""), "(", ""), ")", ""), "+", ""), ".", "") = :customer_phone';
        }

        $sql = "
            SELECT o.*,
                   r.reference as reservation_reference,
                   r.guest_name as reservation_guest_name
            FROM orders o
            LEFT JOIN reservations r ON r.id = o.reservation_id
            WHERE " . implode(' AND ', $conditions) . "
            ORDER BY o.created_at DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Load items for each order
        foreach ($orders as &$order) {
            $order['items'] = $this->getItems((int)$order['id']);
            $order['status_logs'] = $this->getStatusLogs((int)$order['id']);
        }

        return $orders;
    }

    /**
     * Find order by reference
     */
    public function findByReference(string $reference): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM orders WHERE reference = :reference");
        $stmt->execute(['reference' => $reference]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        return $order ?: null;
    }

    /**
     * Create a new order
     */
    public function create(array $data): int
    {
        $reference = $data['reference'] ?? $this->generateReference();
        
        $stmt = $this->db->prepare('
            INSERT INTO orders (
                reference, order_type, source, user_id, reservation_id, customer_name, 
                customer_phone, customer_email, room_number, service_type, status, 
                payment_status, payment_type, total, notes, special_instructions
            ) VALUES (
                :reference, :order_type, :source, :user_id, :reservation_id, :customer_name,
                :customer_phone, :customer_email, :room_number, :service_type, :status,
                :payment_status, :payment_type, :total, :notes, :special_instructions
            )
        ');

        $stmt->execute([
            'reference' => $reference,
            'order_type' => $data['order_type'] ?? 'pos_order',
            'source' => $data['source'] ?? 'pos',
            'user_id' => $data['user_id'] ?? null,
            'reservation_id' => $data['reservation_id'] ?? null,
            'customer_name' => $data['customer_name'] ?? null,
            'customer_phone' => $data['customer_phone'] ?? null,
            'customer_email' => $data['customer_email'] ?? null,
            'room_number' => $data['room_number'] ?? null,
            'service_type' => $data['service_type'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'payment_status' => $data['payment_status'] ?? 'pending',
            'payment_type' => $data['payment_type'] ?? 'cash',
            'total' => $data['total'] ?? 0,
            'notes' => $data['notes'] ?? null,
            'special_instructions' => $data['special_instructions'] ?? null,
        ]);

        $orderId = (int)$this->db->lastInsertId();

        // Add items
        if (!empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $this->addItem($orderId, $item);
            }
        }

        // Log initial status
        $this->logStatusChange($orderId, $data['status'] ?? 'pending', $data['user_id'] ?? null, 'Order created');

        return $orderId;
    }

    /**
     * Add item to order
     */
    public function addItem(int $orderId, array $item): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO order_items (
                order_id, pos_item_id, inventory_item_id, item_name, quantity, 
                unit_price, line_total, special_notes
            ) VALUES (
                :order_id, :pos_item_id, :inventory_item_id, :item_name, :quantity,
                :unit_price, :line_total, :special_notes
            )
        ');

        $stmt->execute([
            'order_id' => $orderId,
            'pos_item_id' => $item['pos_item_id'] ?? null,
            'inventory_item_id' => $item['inventory_item_id'] ?? null,
            'item_name' => $item['item_name'] ?? $item['name'] ?? '',
            'quantity' => $item['quantity'] ?? 1,
            'unit_price' => $item['unit_price'] ?? $item['price'] ?? 0,
            'line_total' => ($item['quantity'] ?? 1) * ($item['unit_price'] ?? $item['price'] ?? 0),
            'special_notes' => $item['special_notes'] ?? null,
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Get order items
     */
    public function getItems(int $orderId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM order_items WHERE order_id = :order_id ORDER BY id');
        $stmt->execute(['order_id' => $orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update order status
     */
    public function updateStatus(int $orderId, string $status, ?int $userId = null, ?string $notes = null): void
    {
        $updates = ['status = :status'];
        $params = ['status' => $status, 'id' => $orderId];

        // Set timestamps based on status
        switch ($status) {
            case 'preparing':
                $updates[] = 'preparation_started_at = NOW()';
                break;
            case 'ready':
                $updates[] = 'ready_at = NOW()';
                break;
            case 'delivered':
                $updates[] = 'delivered_at = NOW()';
                break;
            case 'completed':
                $updates[] = 'completed_at = NOW()';
                break;
            case 'cancelled':
                $updates[] = 'cancelled_at = NOW()';
                if ($notes) {
                    $updates[] = 'cancellation_reason = :cancellation_reason';
                    $params['cancellation_reason'] = $notes;
                }
                break;
        }

        $sql = 'UPDATE orders SET ' . implode(', ', $updates) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        // Log status change
        $this->logStatusChange($orderId, $status, $userId, $notes);
    }

    /**
     * Assign staff to order
     */
    public function assignStaff(int $orderId, int $staffId): void
    {
        $stmt = $this->db->prepare('UPDATE orders SET assigned_staff_id = :staff_id WHERE id = :id');
        $stmt->execute(['staff_id' => $staffId, 'id' => $orderId]);
    }

    /**
     * Add comment to order
     */
    public function addComment(int $orderId, int $userId, string $comment, string $visibility = 'all'): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO order_comments (order_id, user_id, comment, visibility)
            VALUES (:order_id, :user_id, :comment, :visibility)
        ');
        $stmt->execute([
            'order_id' => $orderId,
            'user_id' => $userId,
            'comment' => $comment,
            'visibility' => $visibility,
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Get status logs
     */
    public function getStatusLogs(int $orderId): array
    {
        $stmt = $this->db->prepare('
            SELECT osl.*, u.name as changed_by_name
            FROM order_status_logs osl
            LEFT JOIN users u ON u.id = osl.changed_by
            WHERE osl.order_id = :order_id
            ORDER BY osl.created_at ASC
        ');
        $stmt->execute(['order_id' => $orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get comments
     */
    public function getComments(int $orderId): array
    {
        $stmt = $this->db->prepare('
            SELECT oc.*, u.name as user_name
            FROM order_comments oc
            LEFT JOIN users u ON u.id = oc.user_id
            WHERE oc.order_id = :order_id
            ORDER BY oc.created_at DESC
        ');
        $stmt->execute(['order_id' => $orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Log status change
     */
    protected function logStatusChange(int $orderId, string $status, ?int $userId = null, ?string $notes = null): void
    {
        $stmt = $this->db->prepare('
            INSERT INTO order_status_logs (order_id, status, changed_by, notes)
            VALUES (:order_id, :status, :changed_by, :notes)
        ');
        $stmt->execute([
            'order_id' => $orderId,
            'status' => $status,
            'changed_by' => $userId,
            'notes' => $notes,
        ]);
    }

    /**
     * Generate order reference
     */
    protected function generateReference(): string
    {
        return 'ORD-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    }

    /**
     * Get order counts by status
     */
    public function getCountsByStatus(array $filters = []): array
    {
        $conditions = ['status NOT IN (\'completed\', \'cancelled\')'];
        $params = [];

        // Assigned staff filter
        if (!empty($filters['assigned_staff_id'])) {
            $conditions[] = 'assigned_staff_id = :assigned_staff_id';
            $params['assigned_staff_id'] = (int)$filters['assigned_staff_id'];
        }

        $whereClause = 'WHERE ' . implode(' AND ', $conditions);

        $sql = "
            SELECT status, COUNT(*) as count
            FROM orders
            {$whereClause}
            GROUP BY status
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $counts = [];
        foreach ($results as $row) {
            $counts[$row['status']] = (int)$row['count'];
        }
        
        return $counts;
    }

    /**
     * Get kitchen orders (pending, confirmed, preparing, ready)
     */
    public function getKitchenOrders(?string $status = null): array
    {
        $conditions = [
            "o.status IN ('pending', 'confirmed', 'preparing', 'ready')",
            "o.order_type IN ('restaurant', 'room_service', 'website_delivery', 'pos_order')"
        ];
        $params = [];

        if ($status) {
            $conditions[] = 'o.status = :status';
            $params['status'] = $status;
        }

        $whereClause = 'WHERE ' . implode(' AND ', $conditions);

        $sql = "
            SELECT o.*,
                   u.name as user_name,
                   r.reference as reservation_reference,
                   r.guest_name as reservation_guest_name,
                   s.name as assigned_staff_name
            FROM orders o
            LEFT JOIN users u ON u.id = o.user_id
            LEFT JOIN reservations r ON r.id = o.reservation_id
            LEFT JOIN users s ON s.id = o.assigned_staff_id
            {$whereClause}
            ORDER BY 
                CASE o.status
                    WHEN 'pending' THEN 1
                    WHEN 'confirmed' THEN 2
                    WHEN 'preparing' THEN 3
                    WHEN 'ready' THEN 4
                    ELSE 5
                END,
                o.created_at ASC
            LIMIT 100
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->execute();

        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Load items for each order
        foreach ($orders as &$order) {
            $order['items'] = $this->getItems((int)$order['id']);
        }

        return $orders;
    }

    /**
     * Get kitchen order status counts
     */
    public function getKitchenStatusCounts(): array
    {
        $sql = "
            SELECT status, COUNT(*) as count
            FROM orders
            WHERE status IN ('pending', 'confirmed', 'preparing', 'ready')
                AND order_type IN ('restaurant', 'room_service', 'website_delivery', 'pos_order')
            GROUP BY status
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $counts = [
            'pending' => 0,
            'confirmed' => 0,
            'preparing' => 0,
            'ready' => 0,
        ];
        
        foreach ($results as $row) {
            if (isset($counts[$row['status']])) {
                $counts[$row['status']] = (int)$row['count'];
            }
        }
        
        return $counts;
    }
}

