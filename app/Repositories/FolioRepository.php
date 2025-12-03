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

    public function create(int $reservationId = null, string $guestEmail = null, string $guestPhone = null, string $guestName = null): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO folios (reservation_id, guest_email, guest_phone, guest_name) 
            VALUES (:reservation, :guest_email, :guest_phone, :guest_name)
        ');
        $stmt->execute([
            'reservation' => $reservationId,
            'guest_email' => $guestEmail,
            'guest_phone' => $guestPhone,
            'guest_name' => $guestName,
        ]);

        return (int)$this->db->lastInsertId();
    }
    
    /**
     * Find folio by guest email or phone
     */
    public function findByGuest(string $email = null, string $phone = null): ?array
    {
        $conditions = [];
        $params = [];
        
        if ($email) {
            $conditions[] = 'folios.guest_email = :email';
            $params['email'] = strtolower(trim($email));
        }
        
        if ($phone) {
            $conditions[] = 'folios.guest_phone = :phone';
            $params['phone'] = preg_replace('/[^0-9]/', '', $phone);
        }
        
        if (empty($conditions)) {
            return null;
        }
        
        $sql = 'SELECT * FROM folios WHERE ' . implode(' OR ', $conditions) . ' AND reservation_id IS NULL ORDER BY created_at DESC LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Get all folios for a guest (by email or phone)
     */
    public function findAllByGuest(string $email = null, string $phone = null): array
    {
        $conditions = [];
        $params = [];
        
        if ($email) {
            $conditions[] = '(folios.guest_email = :email OR reservations.guest_email = :email)';
            $params['email'] = strtolower(trim($email));
        }
        
        if ($phone) {
            $sanitizedPhone = preg_replace('/[^0-9]/', '', $phone);
            $conditions[] = '(folios.guest_phone = :phone OR REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(reservations.guest_phone, " ", ""), "-", ""), "(", ""), ")", ""), "+", "") = :phone)';
            $params['phone'] = $sanitizedPhone;
        }
        
        if (empty($conditions)) {
            return [];
        }
        
        $sql = "
            SELECT 
                folios.*,
                reservations.id AS reservation_id,
                reservations.reference,
                COALESCE(folios.guest_name, reservations.guest_name) AS guest_name,
                COALESCE(folios.guest_email, reservations.guest_email) AS guest_email,
                COALESCE(folios.guest_phone, reservations.guest_phone) AS guest_phone,
                reservations.check_in,
                reservations.check_out,
                rooms.room_number,
                rooms.display_name AS room_display_name,
                room_types.name AS room_type_name
            FROM folios
            LEFT JOIN reservations ON reservations.id = folios.reservation_id
            LEFT JOIN rooms ON rooms.id = reservations.room_id
            LEFT JOIN room_types ON room_types.id = reservations.room_type_id
            WHERE " . implode(' OR ', $conditions) . "
            ORDER BY folios.created_at DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll() ?: [];
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

        // Sync booking payment status based on folio balance
        $this->syncBookingPaymentStatus($folioId, $balance, $charges, $payments);
    }

    /**
     * Sync booking payment status when folio balance changes
     */
    protected function syncBookingPaymentStatus(int $folioId, float $balance, float $charges, float $payments): void
    {
        // Get folio with reservation info
        $stmt = $this->db->prepare('
            SELECT reservation_id FROM folios WHERE id = :folio
        ');
        $stmt->execute(['folio' => $folioId]);
        $folio = $stmt->fetch();

        if (!$folio || !$folio['reservation_id']) {
            return; // No reservation linked, skip
        }

        $reservationId = (int)$folio['reservation_id'];

        // Determine payment status based on balance and payments
        $paymentStatus = 'unpaid';
        if ($charges > 0) {
            // There are charges - determine status based on balance
            if ($balance <= 0) {
                // Fully paid (balance is 0 or negative/credit)
                $paymentStatus = 'paid';
            } elseif ($payments > 0) {
                // Partially paid (some payments but still outstanding)
                $paymentStatus = 'partial';
            }
            // else: unpaid (no payments made)
        } else {
            // No charges yet - keep existing status or set to unpaid
            // Don't change status if there are no charges
            return;
        }

        // Update reservation payment status
        $updateStmt = $this->db->prepare('
            UPDATE reservations 
            SET payment_status = :payment_status 
            WHERE id = :reservation_id
        ');
        $updateStmt->execute([
            'payment_status' => $paymentStatus,
            'reservation_id' => $reservationId,
        ]);

        // Also update any orders linked to this reservation
        $this->syncOrderPaymentStatus($reservationId, $paymentStatus);
    }

    /**
     * Sync order payment status when booking payment is updated
     */
    protected function syncOrderPaymentStatus(int $reservationId, string $paymentStatus): void
    {
        // Find orders linked to this reservation
        $stmt = $this->db->prepare('
            SELECT id, reference, payment_status FROM orders 
            WHERE reservation_id = :reservation_id 
            AND payment_status IN (\'pending\', \'unpaid\')
        ');
        $stmt->execute(['reservation_id' => $reservationId]);
        $orders = $stmt->fetchAll();

        if (empty($orders)) {
            return;
        }

        // If booking is paid, mark linked orders as paid (if they were pending)
        if ($paymentStatus === 'paid') {
            $orderIds = array_column($orders, 'id');
            if (!empty($orderIds)) {
                $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
                $updateStmt = $this->db->prepare("
                    UPDATE orders 
                    SET payment_status = 'paid' 
                    WHERE id IN ({$placeholders}) 
                    AND payment_status IN ('pending', 'unpaid')
                ");
                $updateStmt->execute($orderIds);

                // Also update associated POS sales
                foreach ($orders as $order) {
                    $orderRef = $order['reference'] ?? null;
                    if ($orderRef) {
                        $posStmt = $this->db->prepare('
                            UPDATE pos_sales 
                            SET payment_status = \'paid\', mpesa_status = \'completed\' 
                            WHERE reference = :ref 
                            AND payment_status IN (\'pending\', \'unpaid\')
                        ');
                        $posStmt->execute(['ref' => $orderRef]);
                    }
                }
            }
        }
    }

    public function entries(int $folioId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM folio_entries WHERE folio_id = :folio ORDER BY created_at DESC');
        $stmt->execute(['folio' => $folioId]);

        return $stmt->fetchAll();
    }

    public function all(?string $status = null, ?string $startDate = null, ?string $endDate = null, int $limit = 100): array
    {
        $params = [];
        $conditions = [];

        if ($status) {
            $conditions[] = 'folios.status = :status';
            $params['status'] = $status;
        }

        if ($startDate) {
            $conditions[] = '(reservations.check_in >= :start_date OR folios.created_at >= :start_date)';
            $params['start_date'] = $startDate;
        }

        if ($endDate) {
            $conditions[] = '(reservations.check_in <= :end_date OR folios.created_at <= :end_date)';
            $params['end_date'] = $endDate;
        }

        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $sql = "
            SELECT 
                folios.*,
                reservations.id AS reservation_id,
                reservations.reference,
                COALESCE(folios.guest_name, reservations.guest_name) AS guest_name,
                COALESCE(folios.guest_email, reservations.guest_email) AS guest_email,
                COALESCE(folios.guest_phone, reservations.guest_phone) AS guest_phone,
                reservations.check_in,
                reservations.check_out,
                rooms.room_number,
                rooms.display_name AS room_display_name,
                room_types.name AS room_type_name
            FROM folios
            LEFT JOIN reservations ON reservations.id = folios.reservation_id
            LEFT JOIN rooms ON rooms.id = reservations.room_id
            LEFT JOIN room_types ON room_types.id = reservations.room_type_id
            {$whereClause}
            ORDER BY folios.created_at DESC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}


