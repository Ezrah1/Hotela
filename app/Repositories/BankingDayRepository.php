<?php

namespace App\Repositories;

use PDO;

class BankingDayRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    public function isBankingDay(string $date): bool
    {
        // Check if it's a weekend
        $dayOfWeek = (int)date('w', strtotime($date));
        if ($dayOfWeek === 0 || $dayOfWeek === 6) { // Sunday or Saturday
            return false;
        }

        // Check if it's marked as non-banking day
        $stmt = $this->db->prepare('
            SELECT is_banking_day FROM banking_days WHERE date = :date LIMIT 1
        ');
        $stmt->execute(['date' => $date]);
        $result = $stmt->fetch();

        if ($result) {
            return (bool)$result['is_banking_day'];
        }

        return true; // Default to banking day if not specified
    }

    public function getNextBankingDay(string $startDate): string
    {
        $currentDate = $startDate;
        $maxDays = 14; // Prevent infinite loop
        $daysChecked = 0;

        while ($daysChecked < $maxDays) {
            // Move to next day
            if ($daysChecked > 0) {
                $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
            }

            if ($this->isBankingDay($currentDate)) {
                return $currentDate;
            }

            $daysChecked++;
        }

        // Fallback: return the date after max days
        return date('Y-m-d', strtotime($startDate . " +{$maxDays} days"));
    }

    public function markNonBankingDay(string $date, string $reason, int $createdBy): void
    {
        $stmt = $this->db->prepare('
            INSERT INTO banking_days (date, is_banking_day, reason, created_by)
            VALUES (:date, 0, :reason, :created_by)
            ON DUPLICATE KEY UPDATE
                is_banking_day = 0,
                reason = :reason2,
                created_by = :created_by2
        ');
        $stmt->execute([
            'date' => $date,
            'reason' => $reason,
            'created_by' => $createdBy,
            'reason2' => $reason,
            'created_by2' => $createdBy,
        ]);
    }

    public function markBankingDay(string $date, int $createdBy): void
    {
        $stmt = $this->db->prepare('
            INSERT INTO banking_days (date, is_banking_day, created_by)
            VALUES (:date, 1, :created_by)
            ON DUPLICATE KEY UPDATE
                is_banking_day = 1,
                created_by = :created_by2
        ');
        $stmt->execute([
            'date' => $date,
            'created_by' => $createdBy,
            'created_by2' => $createdBy,
        ]);
    }

    public function getNonBankingDays(int $limit = 100): array
    {
        $stmt = $this->db->prepare('
            SELECT bd.*, u.name AS created_by_name
            FROM banking_days bd
            LEFT JOIN users u ON u.id = bd.created_by
            WHERE bd.is_banking_day = 0
            AND bd.date >= CURDATE()
            ORDER BY bd.date ASC
            LIMIT :limit
        ');
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

