<?php

namespace App\Repositories;

use PDO;

class SupplierRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    public function all(?string $search = null, ?string $category = null, ?string $status = null, ?string $group = null): array
    {
        $params = [];

        $sql = "
            SELECT 
                s.*,
                COUNT(DISTINCT po.id) AS purchase_order_count,
                COUNT(DISTINCT CASE WHEN po.status = 'sent' THEN po.id END) AS pending_po_count,
                SUM(CASE WHEN po.status = 'received' THEN poi.quantity * poi.unit_cost ELSE 0 END) AS total_spent
            FROM suppliers s
            LEFT JOIN purchase_orders po ON po.supplier_id = s.id
            LEFT JOIN purchase_order_items poi ON poi.purchase_order_id = po.id AND po.status = 'received'
            WHERE 1 = 1
        ";

        if ($search) {
            $sql .= ' AND (s.name LIKE :search OR s.contact_person LIKE :search OR s.email LIKE :search OR s.phone LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        if ($category) {
            $sql .= ' AND s.category = :category';
            $params['category'] = $category;
        }

        if ($status) {
            $sql .= ' AND s.status = :status';
            $params['status'] = $status;
        }

        if ($group) {
            $sql .= ' AND s.supplier_group = :group';
            $params['group'] = $group;
        }

        $sql .= ' GROUP BY s.id ORDER BY s.name ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        
        $params = ['id' => $id];

        $sql = "SELECT * FROM suppliers WHERE id = :id";

        

        $sql .= ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $supplier = $stmt->fetch();
        return $supplier ?: null;
    }

    public function findByName(string $name): ?array
    {
        $sql = "SELECT * FROM suppliers WHERE name = :name LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['name' => $name]);
        $supplier = $stmt->fetch();
        return $supplier ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT * FROM suppliers WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => strtolower(trim($email))]);
        $supplier = $stmt->fetch();
        return $supplier ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO suppliers (
                name, contact_person, email, phone, address, 
                city, country, tax_id, payment_terms, notes, status,
                category, supplier_group,
                bank_name, bank_account_number, bank_branch, bank_swift_code,
                payment_methods, credit_limit, current_balance
            ) VALUES (
                :name, :contact_person, :email, :phone, :address,
                :city, :country, :tax_id, :payment_terms, :notes, :status,
                :category, :supplier_group,
                :bank_name, :bank_account_number, :bank_branch, :bank_swift_code,
                :payment_methods, :credit_limit, :current_balance
            )
        ');

        $stmt->execute([
            'name' => $data['name'],
            'contact_person' => $data['contact_person'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? null,
            'tax_id' => $data['tax_id'] ?? null,
            'payment_terms' => $data['payment_terms'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => $data['status'] ?? 'active',
            'category' => $data['category'] ?? 'product_supplier',
            'supplier_group' => $data['supplier_group'] ?? null,
            'bank_name' => $data['bank_name'] ?? null,
            'bank_account_number' => $data['bank_account_number'] ?? null,
            'bank_branch' => $data['bank_branch'] ?? null,
            'bank_swift_code' => $data['bank_swift_code'] ?? null,
            'payment_methods' => $data['payment_methods'] ?? null,
            'credit_limit' => $data['credit_limit'] ?? 0,
            'current_balance' => $data['current_balance'] ?? 0,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $params = [
            'id' => $id,
            'name' => $data['name'],
            'contact_person' => $data['contact_person'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? null,
            'tax_id' => $data['tax_id'] ?? null,
            'payment_terms' => $data['payment_terms'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => $data['status'] ?? 'active',
            'category' => $data['category'] ?? 'product_supplier',
            'supplier_group' => $data['supplier_group'] ?? null,
            'bank_name' => $data['bank_name'] ?? null,
            'bank_account_number' => $data['bank_account_number'] ?? null,
            'bank_branch' => $data['bank_branch'] ?? null,
            'bank_swift_code' => $data['bank_swift_code'] ?? null,
            'payment_methods' => $data['payment_methods'] ?? null,
            'credit_limit' => $data['credit_limit'] ?? 0,
            'current_balance' => $data['current_balance'] ?? 0,
        ];

        $sql = '
            UPDATE suppliers SET
                name = :name,
                contact_person = :contact_person,
                email = :email,
                phone = :phone,
                address = :address,
                city = :city,
                country = :country,
                tax_id = :tax_id,
                payment_terms = :payment_terms,
                notes = :notes,
                status = :status,
                category = :category,
                supplier_group = :supplier_group,
                bank_name = :bank_name,
                bank_account_number = :bank_account_number,
                bank_branch = :bank_branch,
                bank_swift_code = :bank_swift_code,
                payment_methods = :payment_methods,
                credit_limit = :credit_limit,
                current_balance = :current_balance,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        
        $params = ['id' => $id];

        // Check if supplier has purchase orders
        $checkStmt = $this->db->prepare('SELECT COUNT(*) as count FROM purchase_orders WHERE supplier_id = :id');
        $checkStmt->execute(['id' => $id]);
        $result = $checkStmt->fetch();

        if ($result && (int)$result['count'] > 0) {
            return false; // Cannot delete supplier with purchase orders
        }

        $sql = 'DELETE FROM suppliers WHERE id = :id';

        

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount() > 0;
    }

    public function getPurchaseOrders(int $supplierId): array
    {
        // Check if reference column exists
        $hasReference = false;
        try {
            $checkStmt = $this->db->query("SHOW COLUMNS FROM purchase_orders LIKE 'reference'");
            $hasReference = $checkStmt->rowCount() > 0;
        } catch (\Exception $e) {
            // Column doesn't exist
        }

        $referenceSelect = $hasReference ? 'po.reference,' : 'CONCAT("PO-", LPAD(po.id, 6, "0")) AS reference,';
        
        $sql = "
            SELECT 
                po.*,
                {$referenceSelect}
                COUNT(poi.id) AS item_count,
                SUM(poi.quantity * poi.unit_cost) AS total_amount
            FROM purchase_orders po
            LEFT JOIN purchase_order_items poi ON poi.purchase_order_id = po.id
            WHERE po.supplier_id = :supplier_id
            GROUP BY po.id
            ORDER BY po.created_at DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['supplier_id' => $supplierId]);

        return $stmt->fetchAll();
    }

    /**
     * Get suppliers by category
     */
    public function getByCategory(string $category): array
    {
        return $this->all(null, $category);
    }

    /**
     * Get suppliers for an inventory item (suppliers who can provide this item)
     */
    public function getSuppliersForItem(int $inventoryItemId, bool $preferredOnly = false): array
    {
        $sql = "
            SELECT 
                s.*,
                si.unit_price,
                si.minimum_order_quantity,
                si.lead_time_days,
                si.is_preferred,
                si.last_ordered_date
            FROM suppliers s
            INNER JOIN supplier_items si ON si.supplier_id = s.id
            WHERE si.inventory_item_id = :item_id
            AND s.status = 'active'
        ";

        if ($preferredOnly) {
            $sql .= ' AND si.is_preferred = 1';
        }

        $sql .= ' ORDER BY si.is_preferred DESC, si.unit_price ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['item_id' => $inventoryItemId]);

        return $stmt->fetchAll();
    }

    /**
     * Get suggested suppliers for an item based on performance, price, and availability
     */
    public function getSuggestedSuppliers(int $inventoryItemId, int $limit = 5): array
    {
        $sql = "
            SELECT 
                s.*,
                si.unit_price,
                si.minimum_order_quantity,
                si.lead_time_days,
                si.is_preferred,
                COALESCE(s.reliability_score, 0) AS reliability_score,
                COALESCE(s.average_delivery_days, si.lead_time_days, 7) AS estimated_delivery_days,
                COUNT(DISTINCT po.id) AS order_count,
                AVG(sp.total_rating) AS avg_rating
            FROM suppliers s
            INNER JOIN supplier_items si ON si.supplier_id = s.id
            LEFT JOIN purchase_orders po ON po.supplier_id = s.id
            LEFT JOIN supplier_performance sp ON sp.supplier_id = s.id
            WHERE si.inventory_item_id = :item_id
            AND s.status = 'active'
            GROUP BY s.id, si.id
            ORDER BY 
                si.is_preferred DESC,
                s.reliability_score DESC,
                avg_rating DESC,
                si.unit_price ASC
            LIMIT :limit
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':item_id', $inventoryItemId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Associate a supplier with an inventory item
     */
    public function associateItem(int $supplierId, int $inventoryItemId, ?float $unitPrice = null, ?int $leadTimeDays = null, bool $isPreferred = false): bool
    {
        $stmt = $this->db->prepare('
            INSERT INTO supplier_items (supplier_id, inventory_item_id, unit_price, lead_time_days, is_preferred)
            VALUES (:supplier, :item, :price, :lead_time, :preferred)
            ON DUPLICATE KEY UPDATE
                unit_price = COALESCE(:price, unit_price),
                lead_time_days = COALESCE(:lead_time, lead_time_days),
                is_preferred = :preferred,
                updated_at = CURRENT_TIMESTAMP
        ');

        return $stmt->execute([
            'supplier' => $supplierId,
            'item' => $inventoryItemId,
            'price' => $unitPrice,
            'lead_time' => $leadTimeDays,
            'preferred' => $isPreferred ? 1 : 0,
        ]);
    }

    /**
     * Get supplier performance history
     */
    public function getPerformanceHistory(int $supplierId, int $limit = 20): array
    {
        $sql = "
            SELECT 
                sp.*,
                po.reference AS po_reference,
                DATEDIFF(sp.delivery_date, sp.expected_delivery_date) AS delivery_variance_days
            FROM supplier_performance sp
            LEFT JOIN purchase_orders po ON po.id = sp.purchase_order_id
            WHERE sp.supplier_id = :supplier_id
            ORDER BY sp.order_date DESC
            LIMIT :limit
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':supplier_id', $supplierId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Record supplier performance
     */
    public function recordPerformance(int $supplierId, array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO supplier_performance (
                supplier_id, purchase_order_id, order_date, delivery_date, expected_delivery_date,
                on_time_delivery, quality_rating, price_rating, service_rating, total_rating, notes
            ) VALUES (
                :supplier, :po_id, :order_date, :delivery_date, :expected_date,
                :on_time, :quality, :price, :service, :total, :notes
            )
        ');

        $totalRating = null;
        $ratings = array_filter([
            $data['quality_rating'] ?? null,
            $data['price_rating'] ?? null,
            $data['service_rating'] ?? null,
        ]);
        if (!empty($ratings)) {
            $totalRating = array_sum($ratings) / count($ratings);
        }

        $stmt->execute([
            'supplier' => $supplierId,
            'po_id' => $data['purchase_order_id'] ?? null,
            'order_date' => $data['order_date'] ?? date('Y-m-d'),
            'delivery_date' => $data['delivery_date'] ?? null,
            'expected_date' => $data['expected_delivery_date'] ?? null,
            'on_time' => $data['on_time_delivery'] ?? 0,
            'quality' => $data['quality_rating'] ?? null,
            'price' => $data['price_rating'] ?? null,
            'service' => $data['service_rating'] ?? null,
            'total' => $totalRating,
            'notes' => $data['notes'] ?? null,
        ]);

        $performanceId = (int)$this->db->lastInsertId();

        // Update supplier's reliability score and average delivery days
        $this->updateSupplierMetrics($supplierId);

        return $performanceId;
    }

    /**
     * Update supplier reliability score and average delivery days based on performance history
     */
    protected function updateSupplierMetrics(int $supplierId): void
    {
        // Calculate average delivery days
        $deliveryStmt = $this->db->prepare('
            SELECT AVG(DATEDIFF(delivery_date, order_date)) AS avg_days
            FROM supplier_performance
            WHERE supplier_id = :supplier
            AND delivery_date IS NOT NULL
            AND order_date IS NOT NULL
        ');
        $deliveryStmt->execute(['supplier' => $supplierId]);
        $avgDelivery = $deliveryStmt->fetchColumn();

        // Calculate reliability score (based on on-time delivery rate and ratings)
        $reliabilityStmt = $this->db->prepare('
            SELECT 
                AVG(CASE WHEN on_time_delivery = 1 THEN 1 ELSE 0 END) * 0.4 +
                AVG(COALESCE(total_rating, 0)) / 5 * 0.6 AS reliability
            FROM supplier_performance
            WHERE supplier_id = :supplier
        ');
        $reliabilityStmt->execute(['supplier' => $supplierId]);
        $reliability = $reliabilityStmt->fetchColumn();

        // Update supplier record
        $updateStmt = $this->db->prepare('
            UPDATE suppliers SET
                reliability_score = :reliability,
                average_delivery_days = :avg_delivery,
                last_order_date = (
                    SELECT MAX(order_date) FROM supplier_performance WHERE supplier_id = :supplier
                )
            WHERE id = :supplier
        ');
        $updateStmt->execute([
            'supplier' => $supplierId,
            'reliability' => $reliability ? round((float)$reliability * 100, 2) : 0,
            'avg_delivery' => $avgDelivery ? (int)round((float)$avgDelivery) : null,
        ]);
    }

    /**
     * Get pricing history for a supplier-item combination
     */
    public function getPricingHistory(int $supplierId, int $inventoryItemId, int $limit = 20): array
    {
        $sql = "
            SELECT 
                sph.*,
                ii.name AS item_name,
                po.reference AS po_reference
            FROM supplier_pricing_history sph
            INNER JOIN inventory_items ii ON ii.id = sph.inventory_item_id
            LEFT JOIN purchase_orders po ON po.id = sph.purchase_order_id
            WHERE sph.supplier_id = :supplier AND sph.inventory_item_id = :item
            ORDER BY sph.effective_date DESC, sph.created_at DESC
            LIMIT :limit
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':supplier', $supplierId, PDO::PARAM_INT);
        $stmt->bindValue(':item', $inventoryItemId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Record pricing history
     */
    public function recordPricing(int $supplierId, int $inventoryItemId, float $unitPrice, ?int $purchaseOrderId = null, ?string $notes = null): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO supplier_pricing_history (
                supplier_id, inventory_item_id, unit_price, effective_date, purchase_order_id, notes
            ) VALUES (
                :supplier, :item, :price, :date, :po_id, :notes
            )
        ');

        $stmt->execute([
            'supplier' => $supplierId,
            'item' => $inventoryItemId,
            'price' => $unitPrice,
            'date' => date('Y-m-d'),
            'po_id' => $purchaseOrderId,
            'notes' => $notes,
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Get all supplier groups
     */
    public function getGroups(): array
    {
        try {
            // Check if column exists first
            $checkStmt = $this->db->query("SHOW COLUMNS FROM suppliers LIKE 'supplier_group'");
            if ($checkStmt->rowCount() === 0) {
                return [];
            }
            
            $stmt = $this->db->query("
                SELECT DISTINCT supplier_group
                FROM suppliers
                WHERE supplier_group IS NOT NULL AND supplier_group != ''
                ORDER BY supplier_group ASC
            ");

            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            // Column doesn't exist or other error
            return [];
        }
    }
}

