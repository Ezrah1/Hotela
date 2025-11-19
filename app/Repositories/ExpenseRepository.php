<?php

namespace App\Repositories;

use PDO;

class ExpenseRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    public function all(?string $startDate = null, ?string $endDate = null, ?string $department = null, ?string $status = null, ?int $supplierId = null, ?int $categoryId = null): array
    {
        
        $params = [];

        $sql = "
            SELECT 
                e.*,
                ec.name AS category_name,
                s.name AS supplier_name,
                creator.name AS created_by_name,
                approver.name AS approved_by_name,
                payer.name AS paid_by_name
            FROM expenses e
            LEFT JOIN expense_categories ec ON ec.id = e.category_id
            LEFT JOIN suppliers s ON s.id = e.supplier_id
            LEFT JOIN users creator ON creator.id = e.created_by
            LEFT JOIN users approver ON approver.id = e.approved_by
            LEFT JOIN users payer ON payer.id = e.paid_by
            WHERE 1 = 1
        ";

        

        if ($startDate) {
            $sql .= ' AND e.expense_date >= :start_date';
            $params['start_date'] = $startDate;
        }

        if ($endDate) {
            $sql .= ' AND e.expense_date <= :end_date';
            $params['end_date'] = $endDate;
        }

        if ($department) {
            $sql .= ' AND e.department = :department';
            $params['department'] = $department;
        }

        if ($status) {
            $sql .= ' AND e.status = :status';
            $params['status'] = $status;
        }

        if ($supplierId) {
            $sql .= ' AND e.supplier_id = :supplier_id';
            $params['supplier_id'] = $supplierId;
        }

        if ($categoryId) {
            $sql .= ' AND e.category_id = :category_id';
            $params['category_id'] = $categoryId;
        }

        $sql .= ' ORDER BY e.expense_date DESC, e.created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        
        $params = ['id' => $id];

        $sql = "
            SELECT 
                e.*,
                ec.name AS category_name,
                ec.description AS category_description,
                s.name AS supplier_name,
                s.contact_person AS supplier_contact,
                s.email AS supplier_email,
                s.phone AS supplier_phone,
                creator.name AS created_by_name,
                approver.name AS approved_by_name,
                payer.name AS paid_by_name
            FROM expenses e
            LEFT JOIN expense_categories ec ON ec.id = e.category_id
            LEFT JOIN suppliers s ON s.id = e.supplier_id
            LEFT JOIN users creator ON creator.id = e.created_by
            LEFT JOIN users approver ON approver.id = e.approved_by
            LEFT JOIN users payer ON payer.id = e.paid_by
            WHERE e.id = :id
        ";

        

        $sql .= ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $expense = $stmt->fetch();
        return $expense ?: null;
    }

    public function create(array $data): int
    {
        $reference = $data['reference'] ?? $this->generateReference();

        $stmt = $this->db->prepare('
            INSERT INTO expenses (
                reference, category_id, supplier_id, department,
                description, amount, expense_date, payment_method, bill_reference,
                is_recurring, recurring_frequency, status, notes, created_by
            ) VALUES (
                :reference, :category_id, :supplier_id, :department,
                :description, :amount, :expense_date, :payment_method, :bill_reference,
                :is_recurring, :recurring_frequency, :status, :notes, :created_by
            )
        ');

        $stmt->execute([
            'reference' => $reference,
            'category_id' => $data['category_id'] ?? null,
            'supplier_id' => $data['supplier_id'] ?? null,
            'department' => $data['department'] ?? null,
            'description' => $data['description'],
            'amount' => $data['amount'],
            'expense_date' => $data['expense_date'],
            'payment_method' => $data['payment_method'] ?? 'bank_transfer',
            'bill_reference' => $data['bill_reference'] ?? null,
            'is_recurring' => $data['is_recurring'] ?? 0,
            'recurring_frequency' => $data['recurring_frequency'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'notes' => $data['notes'] ?? null,
            'created_by' => $data['created_by'] ?? null,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        
        $params = [
            'id' => $id,
            'category_id' => $data['category_id'] ?? null,
            'supplier_id' => $data['supplier_id'] ?? null,
            'department' => $data['department'] ?? null,
            'description' => $data['description'],
            'amount' => $data['amount'],
            'expense_date' => $data['expense_date'],
            'payment_method' => $data['payment_method'] ?? 'bank_transfer',
            'bill_reference' => $data['bill_reference'] ?? null,
            'is_recurring' => $data['is_recurring'] ?? 0,
            'recurring_frequency' => $data['recurring_frequency'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'notes' => $data['notes'] ?? null,
        ];

        $sql = '
            UPDATE expenses SET
                category_id = :category_id,
                supplier_id = :supplier_id,
                department = :department,
                description = :description,
                amount = :amount,
                expense_date = :expense_date,
                payment_method = :payment_method,
                bill_reference = :bill_reference,
                is_recurring = :is_recurring,
                recurring_frequency = :recurring_frequency,
                status = :status,
                notes = :notes,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ';

        

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function approve(int $id, int $approvedBy): void
    {
        
        $params = [
            'id' => $id,
            'approved_by' => $approvedBy,
        ];

        $sql = 'UPDATE expenses SET status = \'approved\', approved_by = :approved_by WHERE id = :id';

        

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function markPaid(int $id, int $paidBy): void
    {
        
        $params = [
            'id' => $id,
            'paid_by' => $paidBy,
        ];

        $sql = 'UPDATE expenses SET status = \'paid\', paid_by = :paid_by, paid_at = CURRENT_TIMESTAMP WHERE id = :id';

        

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function getSummary(?string $startDate = null, ?string $endDate = null, ?string $department = null): array
    {
        
        $params = [];

        $sql = "
            SELECT 
                COUNT(*) AS total_count,
                SUM(amount) AS total_amount,
                SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) AS pending_amount,
                SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) AS approved_amount,
                SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) AS paid_amount,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) AS pending_count,
                COUNT(CASE WHEN status = 'approved' THEN 1 END) AS approved_count,
                COUNT(CASE WHEN status = 'paid' THEN 1 END) AS paid_count
            FROM expenses
            WHERE 1 = 1
        ";

        

        if ($startDate) {
            $sql .= ' AND expense_date >= :start_date';
            $params['start_date'] = $startDate;
        }

        if ($endDate) {
            $sql .= ' AND expense_date <= :end_date';
            $params['end_date'] = $endDate;
        }

        if ($department) {
            $sql .= ' AND department = :department';
            $params['department'] = $department;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch() ?: [];
    }

    public function getByDepartment(?string $startDate = null, ?string $endDate = null): array
    {
        
        $params = [];

        $sql = "
            SELECT 
                department,
                COUNT(*) AS count,
                SUM(amount) AS total_amount,
                SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) AS paid_amount
            FROM expenses
            WHERE department IS NOT NULL
        ";

        

        if ($startDate) {
            $sql .= ' AND expense_date >= :start_date';
            $params['start_date'] = $startDate;
        }

        if ($endDate) {
            $sql .= ' AND expense_date <= :end_date';
            $params['end_date'] = $endDate;
        }

        $sql .= ' GROUP BY department ORDER BY total_amount DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function getByCategory(?string $startDate = null, ?string $endDate = null): array
    {
        
        $params = [];

        $sql = "
            SELECT 
                ec.name AS category_name,
                ec.department,
                COUNT(e.id) AS count,
                SUM(e.amount) AS total_amount
            FROM expenses e
            INNER JOIN expense_categories ec ON ec.id = e.category_id
            WHERE 1 = 1
        ";

        

        if ($startDate) {
            $sql .= ' AND e.expense_date >= :start_date';
            $params['start_date'] = $startDate;
        }

        if ($endDate) {
            $sql .= ' AND e.expense_date <= :end_date';
            $params['end_date'] = $endDate;
        }

        $sql .= ' GROUP BY ec.id, ec.name, ec.department ORDER BY total_amount DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function getBySupplier(?string $startDate = null, ?string $endDate = null): array
    {
        
        $params = [];

        $sql = "
            SELECT 
                s.id AS supplier_id,
                s.name AS supplier_name,
                COUNT(e.id) AS count,
                SUM(e.amount) AS total_amount,
                SUM(CASE WHEN e.status = 'paid' THEN e.amount ELSE 0 END) AS paid_amount
            FROM expenses e
            INNER JOIN suppliers s ON s.id = e.supplier_id
            WHERE 1 = 1
        ";

        

        if ($startDate) {
            $sql .= ' AND e.expense_date >= :start_date';
            $params['start_date'] = $startDate;
        }

        if ($endDate) {
            $sql .= ' AND e.expense_date <= :end_date';
            $params['end_date'] = $endDate;
        }

        $sql .= ' GROUP BY s.id, s.name ORDER BY total_amount DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function getAttachments(int $expenseId): array
    {
        $sql = 'SELECT * FROM expense_attachments WHERE expense_id = :expense_id ORDER BY created_at DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['expense_id' => $expenseId]);

        return $stmt->fetchAll();
    }

    protected function generateReference(): string
    {
        return 'EXP-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    }
}

