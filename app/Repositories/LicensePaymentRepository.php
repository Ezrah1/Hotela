<?php

namespace App\Repositories;

use PDO;

class LicensePaymentRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO license_payments (tenant_id, package_id, amount, currency, payment_method, payment_status, payment_reference, transaction_id, mpesa_phone, mpesa_checkout_request_id, mpesa_merchant_request_id, mpesa_status, metadata)
            VALUES (:tenant_id, :package_id, :amount, :currency, :payment_method, :payment_status, :payment_reference, :transaction_id, :mpesa_phone, :mpesa_checkout_request_id, :mpesa_merchant_request_id, :mpesa_status, :metadata)
        ');
        
        $stmt->execute([
            'tenant_id' => $data['tenant_id'] ?? null,
            'package_id' => $data['package_id'],
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'USD',
            'payment_method' => $data['payment_method'],
            'payment_status' => $data['payment_status'] ?? 'pending',
            'payment_reference' => $data['payment_reference'] ?? null,
            'transaction_id' => $data['transaction_id'] ?? null,
            'mpesa_phone' => $data['mpesa_phone'] ?? null,
            'mpesa_checkout_request_id' => $data['mpesa_checkout_request_id'] ?? null,
            'mpesa_merchant_request_id' => $data['mpesa_merchant_request_id'] ?? null,
            'mpesa_status' => $data['mpesa_status'] ?? null,
            'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null,
        ]);
        
        return (int)$this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('
            SELECT lp.*, t.name AS tenant_name, t.domain AS tenant_domain, p.name AS package_name
            FROM license_payments lp
            LEFT JOIN tenants t ON t.id = lp.tenant_id
            LEFT JOIN license_packages p ON p.id = lp.package_id
            WHERE lp.id = :id
        ');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findByReference(string $reference): ?array
    {
        $stmt = $this->db->prepare('
            SELECT lp.*, t.name AS tenant_name, t.domain AS tenant_domain, p.name AS package_name
            FROM license_payments lp
            LEFT JOIN tenants t ON t.id = lp.tenant_id
            LEFT JOIN license_packages p ON p.id = lp.package_id
            WHERE lp.payment_reference = :reference OR lp.transaction_id = :reference
        ');
        $stmt->execute(['reference' => $reference]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function updateStatus(int $id, string $status, ?array $data = null): bool
    {
        $fields = ['payment_status = :status'];
        $params = ['id' => $id, 'status' => $status];
        
        if ($status === 'completed' && !isset($data['paid_at'])) {
            $fields[] = 'paid_at = NOW()';
        }
        
        if ($data) {
            $allowed = ['transaction_id', 'mpesa_status', 'mpesa_checkout_request_id', 'mpesa_merchant_request_id', 'metadata'];
            foreach ($allowed as $field) {
                if (isset($data[$field])) {
                    $fields[] = "$field = :$field";
                    if ($field === 'metadata') {
                        $params[$field] = json_encode($data[$field]);
                    } else {
                        $params[$field] = $data[$field];
                    }
                }
            }
        }
        
        $sql = 'UPDATE license_payments SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function all(?int $tenantId = null): array
    {
        if ($tenantId) {
            $stmt = $this->db->prepare('
                SELECT lp.*, t.name AS tenant_name, t.domain AS tenant_domain, p.name AS package_name
                FROM license_payments lp
                LEFT JOIN tenants t ON t.id = lp.tenant_id
                LEFT JOIN license_packages p ON p.id = lp.package_id
                WHERE lp.tenant_id = :tenant_id
                ORDER BY lp.created_at DESC
            ');
            $stmt->execute(['tenant_id' => $tenantId]);
        } else {
            $stmt = $this->db->query('
                SELECT lp.*, t.name AS tenant_name, t.domain AS tenant_domain, p.name AS package_name
                FROM license_payments lp
                LEFT JOIN tenants t ON t.id = lp.tenant_id
                LEFT JOIN license_packages p ON p.id = lp.package_id
                ORDER BY lp.created_at DESC
            ');
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

