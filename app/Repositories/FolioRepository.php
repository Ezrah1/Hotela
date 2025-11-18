<?php

namespace App\Repositories;

use PDO;

class FolioRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    public function findByReservation(int $reservationId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM folios WHERE reservation_id = :reservation LIMIT 1');
        $stmt->execute(['reservation' => $reservationId]);
        $folio = $stmt->fetch();

        return $folio ?: null;
    }

    public function create(int $reservationId): int
    {
        $stmt = $this->db->prepare('INSERT INTO folios (reservation_id) VALUES (:reservation)');
        $stmt->execute(['reservation' => $reservationId]);

        return (int)$this->db->lastInsertId();
    }

    public function addEntry(int $folioId, string $description, float $amount, string $type = 'charge', ?string $source = null): void
    {
        $stmt = $this->db->prepare('
            INSERT INTO folio_entries (folio_id, description, amount, type, source)
            VALUES (:folio, :description, :amount, :type, :source)
        ');
        $stmt->execute([
            'folio' => $folioId,
            'description' => $description,
            'amount' => $amount,
            'type' => $type,
            'source' => $source,
        ]);

        $this->recalculate($folioId);
    }

    public function recalculate(int $folioId): void
    {
        $stmt = $this->db->prepare('
            SELECT
                SUM(CASE WHEN type = \'charge\' THEN amount ELSE 0 END) AS charges,
                SUM(CASE WHEN type = \'payment\' THEN amount ELSE 0 END) AS payments
            FROM folio_entries
            WHERE folio_id = :folio
        ');
        $stmt->execute(['folio' => $folioId]);
        $totals = $stmt->fetch();

        $charges = (float)($totals['charges'] ?? 0);
        $payments = (float)($totals['payments'] ?? 0);
        $balance = $charges - $payments;

        $stmt = $this->db->prepare('UPDATE folios SET total = :total, balance = :balance WHERE id = :folio');
        $stmt->execute([
            'total' => $charges,
            'balance' => $balance,
            'folio' => $folioId,
        ]);
    }

    public function entries(int $folioId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM folio_entries WHERE folio_id = :folio ORDER BY created_at DESC');
        $stmt->execute(['folio' => $folioId]);

        return $stmt->fetchAll();
    }
}


