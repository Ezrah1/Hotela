<?php

namespace App\Repositories;

use PDO;

class ReservationRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    protected function tenantCondition(string $alias, array &$params): string
    {
        // Single installation - no tenant filtering needed
        return '';
    }

    public function upcoming(int $limit = 10): array
    {
        $params = [];
        $sql = '
            SELECT reservations.*, rooms.room_number, rooms.display_name, room_types.name AS room_type_name
            FROM reservations
            INNER JOIN room_types ON room_types.id = reservations.room_type_id
            LEFT JOIN rooms ON rooms.id = reservations.room_id
            WHERE reservations.check_in >= CURDATE()
            AND reservations.status NOT IN (\'checked_out\', \'cancelled\')
            AND reservations.check_in_status != \'checked_out\'
        ';
        $sql .= $this->tenantCondition('reservations', $params);
        $sql .= '
            ORDER BY reservations.check_in ASC
            LIMIT ' . (int)$limit . '
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Get upcoming bookings for a guest
     */
    public function upcomingForGuest(string $identifier): array
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return [];
        }

        $params = [];
        // Exclude checked-out, cancelled bookings
        // Check both status and check_in_status fields
        $conditions = [
            'reservations.check_in >= CURDATE()',
            'reservations.status NOT IN (\'checked_out\', \'cancelled\')',
            'reservations.check_in_status != \'checked_out\'',
        ];

        if (str_contains($identifier, '@')) {
            $params['guest_email'] = strtolower($identifier);
            $conditions[] = 'LOWER(guest_email) = :guest_email';
        } else {
            $sanitized = preg_replace('/[^0-9]/', '', $identifier);
            if ($sanitized === '') {
                return [];
            }
            $params['guest_phone'] = $sanitized;
            $conditions[] = 'REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(guest_phone, " ", ""), "-", ""), "(", ""), ")", ""), "+", "") = :guest_phone';
        }

        $sql = '
            SELECT reservations.*, rooms.room_number, rooms.display_name, room_types.name AS room_type_name
            FROM reservations
            INNER JOIN room_types ON room_types.id = reservations.room_type_id
            LEFT JOIN rooms ON rooms.id = reservations.room_id
            WHERE ' . implode(' AND ', $conditions);
        $sql .= $this->tenantCondition('reservations', $params);
        $sql .= ' ORDER BY reservations.check_in ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get past bookings for a guest
     */
    public function pastForGuest(string $identifier): array
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return [];
        }

        $params = [];
        // Include bookings that are checked out, cancelled, completed, or have passed check-out date
        $conditions = [
            '(reservations.check_out < CURDATE() OR reservations.status IN (\'checked_out\', \'cancelled\', \'completed\'))',
        ];

        if (str_contains($identifier, '@')) {
            $params['guest_email'] = strtolower($identifier);
            $conditions[] = 'LOWER(guest_email) = :guest_email';
        } else {
            $sanitized = preg_replace('/[^0-9]/', '', $identifier);
            if ($sanitized === '') {
                return [];
            }
            $params['guest_phone'] = $sanitized;
            $conditions[] = 'REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(guest_phone, " ", ""), "-", ""), "(", ""), ")", ""), "+", "") = :guest_phone';
        }

        $sql = '
            SELECT reservations.*, rooms.room_number, rooms.display_name, room_types.name AS room_type_name
            FROM reservations
            INNER JOIN room_types ON room_types.id = reservations.room_type_id
            LEFT JOIN rooms ON rooms.id = reservations.room_id
            WHERE ' . implode(' AND ', $conditions);
        $sql .= $this->tenantCondition('reservations', $params);
        $sql .= ' ORDER BY reservations.check_out DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function calendar(string $startDate, string $endDate): array
    {
        $params = ['start' => $startDate, 'end' => $endDate];
        $sql = '
            SELECT reservations.*, rooms.room_number, rooms.display_name, room_types.name AS room_type_name
            FROM reservations
            INNER JOIN room_types ON room_types.id = reservations.room_type_id
            LEFT JOIN rooms ON rooms.id = reservations.room_id
            WHERE NOT (reservations.check_out <= :start OR reservations.check_in >= :end)
            AND reservations.status NOT IN (\'checked_out\', \'cancelled\')
            AND reservations.check_in_status != \'checked_out\'
        ';
        $sql .= $this->tenantCondition('reservations', $params);
        $sql .= ' ORDER BY reservations.check_in';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $params = $data;
        $params['extras'] = $data['extras'] ?? null;
        $params['source'] = $data['source'] ?? 'website';
        $params['status'] = $data['status'] ?? 'pending';
        $params['total_amount'] = $data['total_amount'] ?? 0;
        $params['deposit_amount'] = $data['deposit_amount'] ?? 0;
        $params['payment_status'] = $data['payment_status'] ?? 'unpaid';
        $params['check_in_status'] = $data['check_in_status'] ?? 'scheduled';
        $params['room_status'] = $data['room_status'] ?? 'pending';
        $params['payment_method'] = $data['payment_method'] ?? null;
        $params['mpesa_phone'] = $data['mpesa_phone'] ?? null;
        $params['mpesa_checkout_request_id'] = $data['mpesa_checkout_request_id'] ?? null;
        $params['mpesa_merchant_request_id'] = $data['mpesa_merchant_request_id'] ?? null;
        $params['mpesa_status'] = $data['mpesa_status'] ?? null;

        $stmt = $this->db->prepare('
            INSERT INTO reservations (
                reference, guest_name, guest_email, guest_phone, check_in, check_out,
                adults, children, room_type_id, room_id, extras, source, status,
                total_amount, deposit_amount, payment_status, check_in_status, room_status,
                payment_method, mpesa_phone, mpesa_checkout_request_id, mpesa_merchant_request_id, mpesa_status
            ) VALUES (
                :reference, :guest_name, :guest_email, :guest_phone, :check_in, :check_out,
                :adults, :children, :room_type_id, :room_id, :extras, :source, :status,
                :total_amount, :deposit_amount, :payment_status, :check_in_status, :room_status,
                :payment_method, :mpesa_phone, :mpesa_checkout_request_id, :mpesa_merchant_request_id, :mpesa_status
            )
        ');

        $stmt->execute($params);

        return (int)$this->db->lastInsertId();
    }

    public function updateStatus(int $id, array $changes): void
    {
        $sets = [];
        $params = ['id' => $id];

        foreach ($changes as $key => $value) {
            $sets[] = "{$key} = :{$key}";
            $params[$key] = $value;
        }

        if (!$sets) {
            return;
        }

        $sql = 'UPDATE reservations SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $tenantCondition = $this->tenantCondition('reservations', $params);
        if ($tenantCondition) {
            $sql .= $tenantCondition;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function update(int $id, array $data): void
    {
        $allowedFields = [
            'guest_name', 'guest_email', 'guest_phone', 'check_in', 'check_out',
            'adults', 'children', 'room_type_id', 'room_id', 'total_amount',
            'deposit_amount', 'payment_status', 'status', 'notes'
        ];

        $sets = [];
        $params = ['id' => $id];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields, true)) {
                $sets[] = "{$key} = :{$key}";
                $params[$key] = $value;
            }
        }

        if (!$sets) {
            return;
        }

        $sql = 'UPDATE reservations SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $tenantCondition = $this->tenantCondition('reservations', $params);
        if ($tenantCondition) {
            $sql .= $tenantCondition;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function findById(int $id): ?array
    {
        $params = ['id' => $id];
        $sql = '
            SELECT reservations.*, rooms.room_number, rooms.display_name, room_types.name AS room_type_name
            FROM reservations
            INNER JOIN room_types ON room_types.id = reservations.room_type_id
            LEFT JOIN rooms ON rooms.id = reservations.room_id
            WHERE reservations.id = :id
        ';
        $tenantCondition = $this->tenantCondition('reservations', $params);
        if ($tenantCondition) {
            $sql .= $tenantCondition;
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $reservation = $stmt->fetch();

        return $reservation ?: null;
    }

    public function findByReference(string $reference): ?array
    {
        $params = ['reference' => $reference];
        $sql = '
            SELECT reservations.*, rooms.room_number, rooms.display_name, room_types.name AS room_type_name
            FROM reservations
            INNER JOIN room_types ON room_types.id = reservations.room_type_id
            LEFT JOIN rooms ON rooms.id = reservations.room_id
            WHERE reservations.reference = :reference
        ';
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }
    
    /**
     * Get checked-in guests who have exceeded their checkout date
     */
    public function getOverdueCheckouts(): array
    {
        $sql = '
            SELECT 
                reservations.*,
                rooms.room_number,
                rooms.display_name,
                room_types.name AS room_type_name,
                DATEDIFF(CURDATE(), reservations.check_out) AS days_overdue
            FROM reservations
            INNER JOIN room_types ON room_types.id = reservations.room_type_id
            LEFT JOIN rooms ON rooms.id = reservations.room_id
            WHERE reservations.status = "checked_in"
                AND reservations.check_in_status = "checked_in"
                AND reservations.check_out < CURDATE()
            ORDER BY reservations.check_out ASC
        ';
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll() ?: [];
    }

    public function validateGuestAccess(string $reference, string $identifier): ?array
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }

        $params = ['reference' => $reference];
        $conditions = 'reference = :reference';

        if (str_contains($identifier, '@')) {
            $params['guest_email'] = strtolower($identifier);
            $conditions .= ' AND LOWER(guest_email) = :guest_email';
        } else {
            $sanitizedPhone = preg_replace('/[^0-9]/', '', $identifier);
            if ($sanitizedPhone === '') {
                return null;
            }
            $params['guest_phone'] = $sanitizedPhone;
            $conditions .= ' AND REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(guest_phone, " ", ""), "-", ""), "(", ""), ")", ""), "+", "") = :guest_phone';
        }

        $sql = "SELECT * FROM reservations WHERE {$conditions}";
        $sql .= $this->tenantCondition('reservations', $params);
        $sql .= ' ORDER BY created_at DESC LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $reservation = $stmt->fetch();

        return $reservation ?: null;
    }

    public function listForGuest(string $identifier): array
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return [];
        }

        $params = [];
        $conditions = [];

        if (str_contains($identifier, '@')) {
            $params['guest_email'] = strtolower($identifier);
            $conditions[] = 'LOWER(guest_email) = :guest_email';
        } else {
            $sanitized = preg_replace('/[^0-9]/', '', $identifier);
            if ($sanitized === '') {
                return [];
            }
            $params['guest_phone'] = $sanitized;
            $conditions[] = 'REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(guest_phone, " ", ""), "-", ""), "(", ""), ")", ""), "+", "") = :guest_phone';
        }

        $sql = '
            SELECT reservations.*, rooms.room_number, rooms.display_name, room_types.name AS room_type_name
            FROM reservations
            INNER JOIN room_types ON room_types.id = reservations.room_type_id
            LEFT JOIN rooms ON rooms.id = reservations.room_id
            WHERE ' . implode(' AND ', $conditions);
        $sql .= $this->tenantCondition('reservations', $params);
        $sql .= ' ORDER BY reservations.check_in DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function checkedInGuests(): array
    {
        $params = [];
        $sql = '
            SELECT reservations.id, reservations.reference, reservations.guest_name, 
                   reservations.guest_email, reservations.guest_phone,
                   rooms.room_number, rooms.display_name, room_types.name AS room_type_name
            FROM reservations
            INNER JOIN room_types ON room_types.id = reservations.room_type_id
            LEFT JOIN rooms ON rooms.id = reservations.room_id
            WHERE reservations.check_in_status = "checked_in"
            AND reservations.room_status = "in_house"
        ';
        $sql .= $this->tenantCondition('reservations', $params);
        $sql .= ' ORDER BY reservations.guest_name ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function all(?string $filter = null, int $limit = 100, ?string $startDate = null, ?string $endDate = null): array
    {
        $params = [];
        $conditions = [];

        if ($filter === 'upcoming') {
            $conditions[] = 'reservations.check_in >= CURDATE()';
            // Exclude checked-out and cancelled bookings
            $conditions[] = 'reservations.status NOT IN (\'checked_out\', \'cancelled\')';
            $conditions[] = 'reservations.check_in_status != \'checked_out\'';
        } elseif ($filter === 'checked_in') {
            $conditions[] = 'reservations.check_in_status = "checked_in"';
        } elseif ($filter === 'checked_out') {
            $conditions[] = 'reservations.check_in_status = "checked_out"';
        } elseif ($filter === 'scheduled') {
            $conditions[] = 'reservations.check_in_status = "scheduled"';
        }

        // Date range filtering
        if ($startDate) {
            $conditions[] = 'reservations.check_in >= :start_date';
            $params['start_date'] = $startDate;
        }
        if ($endDate) {
            $conditions[] = 'reservations.check_in <= :end_date';
            $params['end_date'] = $endDate;
        }

        $sql = '
            SELECT reservations.*, rooms.room_number, rooms.display_name, room_types.name AS room_type_name
            FROM reservations
            INNER JOIN room_types ON room_types.id = reservations.room_type_id
            LEFT JOIN rooms ON rooms.id = reservations.room_id
        ';

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        } else {
            $sql .= ' WHERE 1=1';
        }

        $tenantCondition = $this->tenantCondition('reservations', $params);
        if ($tenantCondition) {
            $sql .= $tenantCondition;
        }
        
        if ($filter === 'upcoming' || $filter === 'scheduled') {
            $sql .= ' ORDER BY reservations.check_in ASC';
        } else {
            $sql .= ' ORDER BY reservations.check_in DESC';
        }
        
        $sql .= ' LIMIT ' . (int)$limit;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}


