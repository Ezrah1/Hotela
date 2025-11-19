<?php

namespace App\Repositories;

use PDO;

class CashBankingRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    public function createBatch(string $date, array $shiftIds): int
    {
        // Calculate total cash from shifts
        $totalCash = 0;
        if (!empty($shiftIds)) {
            $placeholders = implode(',', array_fill(0, count($shiftIds), '?'));
            $stmt = $this->db->prepare("
                SELECT SUM(cash_declared) AS total
                FROM pos_shifts
                WHERE id IN ($placeholders)
            ");
            $stmt->execute($shiftIds);
            $result = $stmt->fetch();
            $totalCash = (float)($result['total'] ?? 0);
        }

        $reference = $this->generateReference();
        $scheduledDate = $this->getNextBankingDay($date);

        $stmt = $this->db->prepare('
            INSERT INTO cash_banking_batches (batch_reference, shift_date, total_cash, scheduled_banking_date, status)
            VALUES (:reference, :date, :total, :scheduled, "unbanked")
        ');
        $stmt->execute([
            'reference' => $reference,
            'date' => $date,
            'total' => $totalCash,
            'scheduled' => $scheduledDate,
        ]);

        $batchId = (int)$this->db->lastInsertId();

        // Link shifts to batch
        $linkStmt = $this->db->prepare('
            INSERT INTO cash_banking_batch_shifts (batch_id, shift_id)
            VALUES (:batch_id, :shift_id)
        ');
        foreach ($shiftIds as $shiftId) {
            $linkStmt->execute(['batch_id' => $batchId, 'shift_id' => $shiftId]);
        }

        return $batchId;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('
            SELECT b.*, 
                   u.name AS banked_by_name,
                   COUNT(cbs.shift_id) AS shift_count
            FROM cash_banking_batches b
            LEFT JOIN users u ON u.id = b.banked_by
            LEFT JOIN cash_banking_batch_shifts cbs ON cbs.batch_id = b.id
            WHERE b.id = :id
            GROUP BY b.id
            LIMIT 1
        ');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function findByReference(string $reference): ?array
    {
        $stmt = $this->db->prepare('
            SELECT b.*, 
                   u.name AS banked_by_name,
                   COUNT(cbs.shift_id) AS shift_count
            FROM cash_banking_batches b
            LEFT JOIN users u ON u.id = b.banked_by
            LEFT JOIN cash_banking_batch_shifts cbs ON cbs.batch_id = b.id
            WHERE b.batch_reference = :reference
            GROUP BY b.id
            LIMIT 1
        ');
        $stmt->execute(['reference' => $reference]);
        return $stmt->fetch() ?: null;
    }

    public function getBatchesByStatus(string $status): array
    {
        $stmt = $this->db->prepare('
            SELECT b.*, 
                   u.name AS banked_by_name,
                   COUNT(cbs.shift_id) AS shift_count
            FROM cash_banking_batches b
            LEFT JOIN users u ON u.id = b.banked_by
            LEFT JOIN cash_banking_batch_shifts cbs ON cbs.batch_id = b.id
            WHERE b.status = :status
            GROUP BY b.id
            ORDER BY b.shift_date DESC, b.created_at DESC
        ');
        $stmt->execute(['status' => $status]);
        return $stmt->fetchAll();
    }

    public function getShiftsForBatch(int $batchId): array
    {
        $stmt = $this->db->prepare('
            SELECT s.*, u.name AS user_name
            FROM pos_shifts s
            INNER JOIN cash_banking_batch_shifts cbs ON cbs.shift_id = s.id
            LEFT JOIN users u ON u.id = s.user_id
            WHERE cbs.batch_id = :batch_id
            ORDER BY s.shift_date DESC
        ');
        $stmt->execute(['batch_id' => $batchId]);
        return $stmt->fetchAll();
    }

    public function createReconciliation(int $batchId, int $reconciledBy, float $cashDeclared, float $cashCalculated, ?float $adjustmentAmount = null, ?string $adjustmentReason = null, ?string $notes = null): int
    {
        $difference = $cashDeclared - $cashCalculated;
        $adjustment = $adjustmentAmount ?? 0;

        $stmt = $this->db->prepare('
            INSERT INTO cash_reconciliations (batch_id, reconciled_by, cash_declared, cash_calculated, difference, adjustment_amount, adjustment_reason, notes, status)
            VALUES (:batch_id, :reconciled_by, :declared, :calculated, :difference, :adjustment, :adjustment_reason, :notes, "pending")
        ');
        $stmt->execute([
            'batch_id' => $batchId,
            'reconciled_by' => $reconciledBy,
            'declared' => $cashDeclared,
            'calculated' => $cashCalculated,
            'difference' => $difference,
            'adjustment' => $adjustment,
            'adjustment_reason' => $adjustmentReason,
            'notes' => $notes,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function approveReconciliation(int $reconciliationId): void
    {
        $reconciliation = $this->getReconciliation($reconciliationId);
        if (!$reconciliation) {
            throw new \RuntimeException('Reconciliation not found');
        }

        $this->db->beginTransaction();
        try {
            // Update reconciliation status
            $stmt = $this->db->prepare('
                UPDATE cash_reconciliations
                SET status = "approved", approved_at = NOW()
                WHERE id = :id
            ');
            $stmt->execute(['id' => $reconciliationId]);

            // Mark batch as ready for banking
            $stmt = $this->db->prepare('
                UPDATE cash_banking_batches
                SET status = "ready_for_banking"
                WHERE id = :batch_id
            ');
            $stmt->execute(['batch_id' => $reconciliation['batch_id']]);

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getReconciliation(int $reconciliationId): ?array
    {
        $stmt = $this->db->prepare('
            SELECT r.*, u.name AS reconciled_by_name
            FROM cash_reconciliations r
            LEFT JOIN users u ON u.id = r.reconciled_by
            WHERE r.id = :id
            LIMIT 1
        ');
        $stmt->execute(['id' => $reconciliationId]);
        return $stmt->fetch() ?: null;
    }

    public function getReconciliationForBatch(int $batchId): ?array
    {
        $stmt = $this->db->prepare('
            SELECT r.*, u.name AS reconciled_by_name
            FROM cash_reconciliations r
            LEFT JOIN users u ON u.id = r.reconciled_by
            WHERE r.batch_id = :batch_id
            ORDER BY r.created_at DESC
            LIMIT 1
        ');
        $stmt->execute(['batch_id' => $batchId]);
        return $stmt->fetch() ?: null;
    }

    public function markAsBanked(int $batchId, int $bankedBy, string $depositSlipPath, ?string $notes = null): void
    {
        $stmt = $this->db->prepare('
            UPDATE cash_banking_batches
            SET status = "banked",
                banked_date = CURDATE(),
                banked_by = :banked_by,
                deposit_slip_path = :deposit_slip,
                notes = :notes
            WHERE id = :id
        ');
        $stmt->execute([
            'id' => $batchId,
            'banked_by' => $bankedBy,
            'deposit_slip' => $depositSlipPath,
            'notes' => $notes,
        ]);
    }

    public function getTotalUnbankedCash(): float
    {
        $stmt = $this->db->prepare('
            SELECT COALESCE(SUM(total_cash), 0) AS total
            FROM cash_banking_batches
            WHERE status IN ("unbanked", "ready_for_banking")
        ');
        $stmt->execute();
        $result = $stmt->fetch();
        return (float)($result['total'] ?? 0);
    }

    protected function generateReference(): string
    {
        return 'CASH-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    }

    protected function getNextBankingDay(string $date): string
    {
        $bankingDayRepo = new BankingDayRepository($this->db);
        return $bankingDayRepo->getNextBankingDay($date);
    }
}

