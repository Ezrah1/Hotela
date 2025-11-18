<?php

namespace App\Repositories;

use PDO;

class PettyCashRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    public function getAccount(): ?array
    {
        $tenantId = \App\Support\Tenant::id();
        $params = [];

        $sql = "
            SELECT 
                pca.*,
                custodian.name AS custodian_name,
                custodian.email AS custodian_email
            FROM petty_cash_account pca
            LEFT JOIN users custodian ON custodian.id = pca.custodian_id
            WHERE 1 = 1
        ";

        if ($tenantId !== null) {
            $sql .= ' AND pca.tenant_id = :tenant_id';
            $params['tenant_id'] = $tenantId;
        }

        $sql .= ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $account = $stmt->fetch();
        
        // If no account exists, create one
        if (!$account) {
            $accountId = $this->createAccount();
            // Fetch the newly created account
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $account = $stmt->fetch();
        }

        return $account;
    }

    public function createAccount(): int
    {
        $tenantId = \App\Support\Tenant::id();

        $stmt = $this->db->prepare('
            INSERT INTO petty_cash_account (tenant_id, account_name, balance, limit_amount, status)
            VALUES (:tenant_id, :account_name, :balance, :limit_amount, :status)
        ');

        $stmt->execute([
            'tenant_id' => $tenantId,
            'account_name' => 'Petty Cash',
            'balance' => 0,
            'limit_amount' => 2000,
            'status' => 'active',
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function updateAccount(array $data): void
    {
        $tenantId = \App\Support\Tenant::id();
        $params = [
            'account_name' => $data['account_name'] ?? 'Petty Cash',
            'limit_amount' => $data['limit_amount'] ?? 2000,
            'custodian_id' => $data['custodian_id'] ?? null,
            'status' => $data['status'] ?? 'active',
        ];

        $sql = '
            UPDATE petty_cash_account SET
                account_name = :account_name,
                limit_amount = :limit_amount,
                custodian_id = :custodian_id,
                status = :status,
                updated_at = CURRENT_TIMESTAMP
            WHERE 1 = 1
        ';

        if ($tenantId !== null) {
            $sql .= ' AND tenant_id = :tenant_id';
            $params['tenant_id'] = $tenantId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function updateBalance(int $accountId, float $amount, string $type): void
    {
        $account = $this->getAccountById($accountId);
        if (!$account) {
            throw new \Exception('Petty cash account not found');
        }

        $currentBalance = (float)$account['balance'];
        
        if ($type === 'deposit') {
            $newBalance = $currentBalance + $amount;
        } elseif ($type === 'expense') {
            $newBalance = $currentBalance - $amount;
            
            if ($newBalance < 0) {
                throw new \Exception('Insufficient petty cash balance');
            }
        } else {
            throw new \Exception('Invalid transaction type');
        }

        $stmt = $this->db->prepare('UPDATE petty_cash_account SET balance = :balance WHERE id = :id');
        $stmt->execute([
            'balance' => $newBalance,
            'id' => $accountId,
        ]);
    }

    public function getAccountById(int $id): ?array
    {
        $tenantId = \App\Support\Tenant::id();
        $params = ['id' => $id];

        $sql = 'SELECT * FROM petty_cash_account WHERE id = :id';

        if ($tenantId !== null) {
            $sql .= ' AND tenant_id = :tenant_id';
            $params['tenant_id'] = $tenantId;
        }

        $sql .= ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch() ?: null;
    }

    public function addTransaction(array $data): int
    {
        $account = $this->getAccount();
        if (!$account) {
            throw new \Exception('Petty cash account not found');
        }

        $accountId = (int)$account['id'];
        $amount = (float)$data['amount'];
        $type = $data['transaction_type'] ?? 'expense';

        // Check limit for expenses
        if ($type === 'expense') {
            $currentBalance = (float)$account['balance'];
            if ($amount > $currentBalance) {
                throw new \Exception('Insufficient petty cash balance');
            }
        }

        $stmt = $this->db->prepare('
            INSERT INTO petty_cash_transactions (
                tenant_id, account_id, transaction_type, amount, description,
                expense_id, receipt_number, authorized_by, processed_by, notes
            ) VALUES (
                :tenant_id, :account_id, :transaction_type, :amount, :description,
                :expense_id, :receipt_number, :authorized_by, :processed_by, :notes
            )
        ');

        $stmt->execute([
            'tenant_id' => \App\Support\Tenant::id(),
            'account_id' => $accountId,
            'transaction_type' => $type,
            'amount' => $amount,
            'description' => $data['description'],
            'expense_id' => $data['expense_id'] ?? null,
            'receipt_number' => $data['receipt_number'] ?? null,
            'authorized_by' => $data['authorized_by'] ?? null,
            'processed_by' => $data['processed_by'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $transactionId = (int)$this->db->lastInsertId();

        // Update account balance
        $this->updateBalance($accountId, $amount, $type);

        return $transactionId;
    }

    public function getTransactions(?string $startDate = null, ?string $endDate = null, ?string $type = null): array
    {
        $tenantId = \App\Support\Tenant::id();
        $params = [];

        $sql = "
            SELECT 
                pct.*,
                authorized.name AS authorized_by_name,
                processed.name AS processed_by_name,
                e.reference AS expense_reference,
                e.description AS expense_description
            FROM petty_cash_transactions pct
            LEFT JOIN users authorized ON authorized.id = pct.authorized_by
            LEFT JOIN users processed ON processed.id = pct.processed_by
            LEFT JOIN expenses e ON e.id = pct.expense_id
            WHERE 1 = 1
        ";

        if ($tenantId !== null) {
            $sql .= ' AND pct.tenant_id = :tenant_id';
            $params['tenant_id'] = $tenantId;
        }

        if ($startDate) {
            $sql .= ' AND DATE(pct.created_at) >= :start_date';
            $params['start_date'] = $startDate;
        }

        if ($endDate) {
            $sql .= ' AND DATE(pct.created_at) <= :end_date';
            $params['end_date'] = $endDate;
        }

        if ($type) {
            $sql .= ' AND pct.transaction_type = :type';
            $params['type'] = $type;
        }

        $sql .= ' ORDER BY pct.created_at DESC';

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
                SUM(CASE WHEN transaction_type = 'deposit' THEN amount ELSE 0 END) AS total_deposits,
                SUM(CASE WHEN transaction_type = 'expense' THEN amount ELSE 0 END) AS total_expenses
            FROM petty_cash_transactions
            WHERE 1 = 1
        ";

        if ($tenantId !== null) {
            $sql .= ' AND tenant_id = :tenant_id';
            $params['tenant_id'] = $tenantId;
        }

        if ($startDate) {
            $sql .= ' AND DATE(created_at) >= :start_date';
            $params['start_date'] = $startDate;
        }

        if ($endDate) {
            $sql .= ' AND DATE(created_at) <= :end_date';
            $params['end_date'] = $endDate;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch() ?: [];
    }

    public function canWithdraw(float $amount): bool
    {
        $account = $this->getAccount();
        if (!$account) {
            return false;
        }

        $currentBalance = (float)$account['balance'];
        return $amount <= $currentBalance;
    }

    public function getAvailableBalance(): float
    {
        $account = $this->getAccount();
        if (!$account) {
            return 0;
        }

        return (float)$account['balance'];
    }
}

