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

    public function all(?string $search = null): array
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

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO suppliers (
                name, contact_person, email, phone, address, 
                city, country, tax_id, payment_terms, notes, status,
                bank_name, bank_account_number, bank_branch, bank_swift_code,
                payment_methods, credit_limit, current_balance
            ) VALUES (
                :name, :contact_person, :email, :phone, :address,
                :city, :country, :tax_id, :payment_terms, :notes, :status,
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
        $sql = "
            SELECT 
                po.*,
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
}

