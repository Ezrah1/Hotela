<?php

namespace App\Repositories;

use PDO;

class PaymentRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    public function create(array $data): int
    {
        $reference = $data['reference'] ?? $this->generateReference();
        
        $stmt = $this->db->prepare('
            INSERT INTO payments (
                tenant_id, reference, payment_type, expense_id, bill_id, supplier_id,
                amount, payment_method, payment_date, transaction_reference,
                cheque_number, bank_name, account_number, notes, status, processed_by
            ) VALUES (
                :tenant_id, :reference, :payment_type, :expense_id, :bill_id, :supplier_id,
                :amount, :payment_method, :payment_date, :transaction_reference,
                :cheque_number, :bank_name, :account_number, :notes, :status, :processed_by
            )
        ');

        $stmt->execute([
            'tenant_id' => \App\Support\Tenant::id(),
            'reference' => $reference,
            'payment_type' => $data['payment_type'],
            'expense_id' => $data['expense_id'] ?? null,
            'bill_id' => $data['bill_id'] ?? null,
            'supplier_id' => $data['supplier_id'] ?? null,
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'] ?? 'bank_transfer',
            'payment_date' => $data['payment_date'] ?? date('Y-m-d'),
            'transaction_reference' => $data['transaction_reference'] ?? null,
            'cheque_number' => $data['cheque_number'] ?? null,
            'bank_name' => $data['bank_name'] ?? null,
            'account_number' => $data['account_number'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => $data['status'] ?? 'completed',
            'processed_by' => $data['processed_by'] ?? null,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $tenantId = \App\Support\Tenant::id();
        $params = ['id' => $id];

        $sql = "
            SELECT 
                p.*,
                processed.name AS processed_by_name,
                expense.reference AS expense_reference,
                expense.description AS expense_description,
                supplier.name AS supplier_name,
                bill.bill_reference AS bill_reference
            FROM payments p
            LEFT JOIN users processed ON processed.id = p.processed_by
            LEFT JOIN expenses expense ON expense.id = p.expense_id
            LEFT JOIN expenses bill ON bill.id = p.bill_id
            LEFT JOIN suppliers supplier ON supplier.id = p.supplier_id
            WHERE p.id = :id
        ";

        if ($tenantId !== null) {
            $sql .= ' AND p.tenant_id = :tenant_id';
            $params['tenant_id'] = $tenantId;
        }

        $sql .= ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch() ?: null;
    }

    public function all(?string $startDate = null, ?string $endDate = null, ?string $paymentType = null, ?string $paymentMethod = null, ?string $status = null): array
    {
        $tenantId = \App\Support\Tenant::id();
        $params = [];

        $sql = "
            SELECT 
                p.*,
                processed.name AS processed_by_name,
                expense.reference AS expense_reference,
                expense.description AS expense_description,
                supplier.name AS supplier_name,
                bill.bill_reference AS bill_reference
            FROM payments p
            LEFT JOIN users processed ON processed.id = p.processed_by
            LEFT JOIN expenses expense ON expense.id = p.expense_id
            LEFT JOIN expenses bill ON bill.id = p.bill_id
            LEFT JOIN suppliers supplier ON supplier.id = p.supplier_id
            WHERE 1 = 1
        ";

        if ($tenantId !== null) {
            $sql .= ' AND p.tenant_id = :tenant_id';
            $params['tenant_id'] = $tenantId;
        }

        if ($startDate) {
            $sql .= ' AND DATE(p.payment_date) >= :start_date';
            $params['start_date'] = $startDate;
        }

        if ($endDate) {
            $sql .= ' AND DATE(p.payment_date) <= :end_date';
            $params['end_date'] = $endDate;
        }

        if ($paymentType) {
            $sql .= ' AND p.payment_type = :payment_type';
            $params['payment_type'] = $paymentType;
        }

        if ($paymentMethod) {
            $sql .= ' AND p.payment_method = :payment_method';
            $params['payment_method'] = $paymentMethod;
        }

        if ($status) {
            $sql .= ' AND p.status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY p.payment_date DESC, p.created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function getSummary(?string $startDate = null, ?string $endDate = null): array
    {
        $tenantId = \App\Support\Tenant::id();
        $params = [];

        $sql = "
            SELECT 
                COUNT(*) AS total_count,
                SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) AS total_amount,
                SUM(CASE WHEN payment_type = 'expense' THEN amount ELSE 0 END) AS expense_payments,
                SUM(CASE WHEN payment_type = 'bill' THEN amount ELSE 0 END) AS bill_payments,
                SUM(CASE WHEN payment_type = 'supplier' THEN amount ELSE 0 END) AS supplier_payments,
                SUM(CASE WHEN payment_method = 'cash' THEN amount ELSE 0 END) AS cash_payments,
                SUM(CASE WHEN payment_method = 'bank_transfer' THEN amount ELSE 0 END) AS bank_payments,
                SUM(CASE WHEN payment_method = 'mpesa' THEN amount ELSE 0 END) AS mpesa_payments,
                SUM(CASE WHEN payment_method = 'card' THEN amount ELSE 0 END) AS card_payments
            FROM payments
            WHERE status = 'completed'
        ";

        if ($tenantId !== null) {
            $sql .= ' AND tenant_id = :tenant_id';
            $params['tenant_id'] = $tenantId;
        }

        if ($startDate) {
            $sql .= ' AND DATE(payment_date) >= :start_date';
            $params['start_date'] = $startDate;
        }

        if ($endDate) {
            $sql .= ' AND DATE(payment_date) <= :end_date';
            $params['end_date'] = $endDate;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch() ?: [];
    }

    public function update(int $id, array $data): void
    {
        $tenantId = \App\Support\Tenant::id();
        $params = [
            'id' => $id,
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'] ?? 'bank_transfer',
            'payment_date' => $data['payment_date'] ?? date('Y-m-d'),
            'transaction_reference' => $data['transaction_reference'] ?? null,
            'cheque_number' => $data['cheque_number'] ?? null,
            'bank_name' => $data['bank_name'] ?? null,
            'account_number' => $data['account_number'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => $data['status'] ?? 'completed',
        ];

        $sql = '
            UPDATE payments SET
                amount = :amount,
                payment_method = :payment_method,
                payment_date = :payment_date,
                transaction_reference = :transaction_reference,
                cheque_number = :cheque_number,
                bank_name = :bank_name,
                account_number = :account_number,
                notes = :notes,
                status = :status,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ';

        if ($tenantId !== null) {
            $sql .= ' AND tenant_id = :tenant_id';
            $params['tenant_id'] = $tenantId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $tenantId = \App\Support\Tenant::id();
        $params = ['id' => $id];

        $sql = 'DELETE FROM payments WHERE id = :id';

        if ($tenantId !== null) {
            $sql .= ' AND tenant_id = :tenant_id';
            $params['tenant_id'] = $tenantId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount() > 0;
    }

    protected function generateReference(): string
    {
        $prefix = 'PAY';
        $date = date('Ymd');
        $random = strtoupper(substr(uniqid(), -6));
        return $prefix . '-' . $date . '-' . $random;
    }
}

