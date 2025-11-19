<?php

namespace App\Repositories;

use PDO;

class CashShiftRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    public function getOpenShift(int $userId, string $date): ?array
    {
        $stmt = $this->db->prepare('
            SELECT * FROM pos_shifts 
            WHERE user_id = :user_id AND shift_date = :date AND status = "open"
            LIMIT 1
        ');
        $stmt->execute(['user_id' => $userId, 'date' => $date]);
        return $stmt->fetch() ?: null;
    }

    public function create(int $userId, string $date): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO pos_shifts (user_id, shift_date, status)
            VALUES (:user_id, :date, "open")
        ');
        $stmt->execute(['user_id' => $userId, 'date' => $date]);
        return (int)$this->db->lastInsertId();
    }

    public function getCashTotalForDate(int $userId, string $date): float
    {
        $breakdown = $this->getCashBreakdownForDate($userId, $date);
        return $breakdown['total'];
    }

    public function getCashBreakdownForDate(int $userId, string $date): array
    {
        // Get cash from POS sales by this user
        $stmt = $this->db->prepare('
            SELECT COALESCE(SUM(total), 0) AS total
            FROM pos_sales
            WHERE user_id = :user_id 
            AND DATE(created_at) = :date
            AND payment_type = "cash"
        ');
        $stmt->execute(['user_id' => $userId, 'date' => $date]);
        $posResult = $stmt->fetch();
        $posCash = (float)($posResult['total'] ?? 0);

        // Get cash from folio payments (booking payments) for the entire date
        // Note: We include all cash folio payments for the date, not just by user_id
        // because folio payments might be processed by different staff
        $stmt = $this->db->prepare('
            SELECT COALESCE(SUM(fe.amount), 0) AS total
            FROM folio_entries fe
            INNER JOIN folios f ON f.id = fe.folio_id
            WHERE fe.type = "payment"
            AND COALESCE(fe.source, "cash") = "cash"
            AND DATE(fe.created_at) = :date
        ');
        $stmt->execute(['date' => $date]);
        $folioResult = $stmt->fetch();
        $folioCash = (float)($folioResult['total'] ?? 0);

        return [
            'pos_cash' => $posCash,
            'booking_cash' => $folioCash,
            'total' => $posCash + $folioCash,
        ];
    }

    public function closeShift(int $shiftId, float $cashDeclared, int $closedBy, ?string $notes = null): void
    {
        $shift = $this->findById($shiftId);
        if (!$shift) {
            throw new \RuntimeException('Shift not found');
        }

        $cashCalculated = $this->getCashTotalForDate((int)$shift['user_id'], $shift['shift_date']);
        $difference = $cashDeclared - $cashCalculated;

        $stmt = $this->db->prepare('
            UPDATE pos_shifts
            SET cash_declared = :declared,
                cash_calculated = :calculated,
                difference = :difference,
                status = "closed",
                closed_at = NOW(),
                closed_by = :closed_by,
                notes = :notes
            WHERE id = :id
        ');
        $stmt->execute([
            'declared' => $cashDeclared,
            'calculated' => $cashCalculated,
            'difference' => $difference,
            'closed_by' => $closedBy,
            'notes' => $notes,
            'id' => $shiftId,
        ]);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('
            SELECT s.*, 
                   u.name AS user_name,
                   cb.name AS closed_by_name
            FROM pos_shifts s
            LEFT JOIN users u ON u.id = s.user_id
            LEFT JOIN users cb ON cb.id = s.closed_by
            WHERE s.id = :id
            LIMIT 1
        ');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function getClosedShiftsForDate(string $date): array
    {
        $stmt = $this->db->prepare('
            SELECT s.*, u.name AS user_name
            FROM pos_shifts s
            LEFT JOIN users u ON u.id = s.user_id
            WHERE s.shift_date = :date AND s.status = "closed"
            ORDER BY s.closed_at DESC
        ');
        $stmt->execute(['date' => $date]);
        return $stmt->fetchAll();
    }

    public function getUnbankedShifts(): array
    {
        $stmt = $this->db->prepare('
            SELECT s.*, u.name AS user_name
            FROM pos_shifts s
            LEFT JOIN users u ON u.id = s.user_id
            WHERE s.status = "closed"
            AND s.id NOT IN (
                SELECT shift_id FROM cash_banking_batch_shifts
            )
            ORDER BY s.shift_date DESC, s.closed_at DESC
        ');
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

