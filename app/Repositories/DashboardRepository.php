<?php

namespace App\Repositories;

use PDO;

class DashboardRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    public function occupancySnapshot(): array
    {
        $sql = 'SELECT status, COUNT(*) AS total FROM rooms GROUP BY status';

        $stmt = $this->db->query($sql);

        $counts = [];
        foreach ($stmt->fetchAll() as $row) {
            $counts[$row['status']] = (int)$row['total'];
        }

        $total = array_sum($counts);
        $occupied = $counts['occupied'] ?? 0;
        $available = $counts['available'] ?? 0;
        $needsCleaning = $counts['needs_cleaning'] ?? 0;

        $percent = $total > 0 ? round(($occupied / $total) * 100, 1) : 0;

        return [
            'total' => $total,
            'occupied' => $occupied,
            'available' => $available,
            'needs_cleaning' => $needsCleaning,
            'occupancy_percent' => $percent,
        ];
    }

    public function revenueSummary(): array
    {
        $sql = "
            SELECT
                SUM(CASE WHEN DATE(check_in) = CURDATE() THEN total_amount ELSE 0 END) AS today_revenue,
                SUM(CASE WHEN YEAR(check_in) = YEAR(CURDATE()) AND MONTH(check_in) = MONTH(CURDATE()) THEN total_amount ELSE 0 END) AS month_revenue,
                AVG(CASE
                        WHEN DATEDIFF(check_out, check_in) <= 0 THEN total_amount
                        ELSE total_amount / DATEDIFF(check_out, check_in)
                    END) AS adr_value
            FROM reservations
            WHERE status IN ('confirmed','checked_in','checked_out')
        ";

        $stmt = $this->db->query($sql);
        $row = $stmt->fetch() ?: [];

        $snapshot = $this->occupancySnapshot();
        $rooms = max(1, $snapshot['total'] ?: 1);
        $revpar = ($row['today_revenue'] ?? 0) / $rooms;

        return [
            'today' => (float)($row['today_revenue'] ?? 0),
            'month' => (float)($row['month_revenue'] ?? 0),
            'adr' => round((float)($row['adr_value'] ?? 0), 2),
            'revpar' => round($revpar, 2),
        ];
    }

    public function outstandingBalance(): float
    {
        // Only sum positive balances (money owed to the hotel)
        // Negative balances represent credits/overpayments and shouldn't be included in outstanding
        $sql = 'SELECT SUM(balance) FROM folios WHERE status = ? AND balance > 0';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['open']);

        return (float)($stmt->fetchColumn() ?? 0);
    }

    public function activeUsers(): int
    {
        $sql = 'SELECT COUNT(*) FROM users';
        $stmt = $this->db->query($sql);

        return (int)$stmt->fetchColumn();
    }

    public function unreadNotificationsCount(?string $role = null): int
    {
        $params = [];
        $sql = 'SELECT COUNT(*) FROM notifications WHERE read_at IS NULL';

        if ($role) {
            $sql .= ' AND (role_key IS NULL OR role_key = ?)';
            $params[] = $role;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    public function notificationsForRole(?string $role = null, int $limit = 5): array
    {
        $params = [];
        $sql = 'SELECT title, message, created_at FROM notifications WHERE 1=1';

        if ($role) {
            $sql .= ' AND (role_key IS NULL OR role_key = ?)';
            $params[] = $role;
        }

        $sql .= ' ORDER BY created_at DESC LIMIT ' . (int)$limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function arrivals(int $limit = 5): array
    {
        $params = [
            date('Y-m-d'),
            date('Y-m-d', strtotime('+2 days')),
        ];

        $sql = '
            SELECT reservations.reference, reservations.guest_name, reservations.check_in,
                   rooms.display_name, rooms.room_number, room_types.name AS room_type_name
            FROM reservations
            LEFT JOIN rooms ON rooms.id = reservations.room_id
            LEFT JOIN room_types ON room_types.id = reservations.room_type_id
            WHERE reservations.check_in BETWEEN ? AND ?
            AND reservations.status NOT IN (\'checked_out\', \'cancelled\')
            AND reservations.check_in_status != \'checked_out\'
            ORDER BY reservations.check_in ASC
            LIMIT ' . (int)$limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function departures(int $limit = 5): array
    {
        $params = [
            date('Y-m-d'),
            date('Y-m-d', strtotime('+2 days')),
        ];

        $sql = '
            SELECT reservations.reference, reservations.guest_name, reservations.check_out,
                   rooms.display_name, rooms.room_number, room_types.name AS room_type_name
            FROM reservations
            LEFT JOIN rooms ON rooms.id = reservations.room_id
            LEFT JOIN room_types ON room_types.id = reservations.room_type_id
            WHERE reservations.check_out BETWEEN ? AND ?
            AND reservations.status NOT IN (\'checked_out\', \'cancelled\')
            AND reservations.check_in_status != \'checked_out\'
            ORDER BY reservations.check_out ASC
            LIMIT ' . (int)$limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function pendingPayments(int $limit = 5): array
    {
        $sql = '
            SELECT reservations.reference, reservations.guest_name,
                   rooms.display_name, rooms.room_number, folios.balance
            FROM folios
            INNER JOIN reservations ON reservations.id = folios.reservation_id
            LEFT JOIN rooms ON rooms.id = reservations.room_id
            WHERE folios.balance > 0 AND folios.status = ?
            ORDER BY folios.updated_at DESC LIMIT ' . (int)$limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['open']);

        return $stmt->fetchAll();
    }

    /**
     * Calculate total amount of pending payments (sum of all positive balances)
     */
    public function pendingPaymentsTotal(): float
    {
        $sql = 'SELECT SUM(balance) FROM folios WHERE balance > 0 AND status = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['open']);

        return (float)($stmt->fetchColumn() ?? 0);
    }

    /**
     * Count number of folios with pending payments
     */
    public function pendingPaymentsCount(): int
    {
        $sql = 'SELECT COUNT(*) FROM folios WHERE balance > 0 AND status = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['open']);

        return (int)($stmt->fetchColumn() ?? 0);
    }

    public function lowStockItems(int $limit = 5): array
    {
        $inventory = new InventoryRepository($this->db);
        return $inventory->lowStockItems($limit);
    }

    public function lowStockCount(): int
    {
        $items = $this->lowStockItems(100);
        return count($items);
    }

    public function posSalesSummary(): array
    {
        $sql = '
            SELECT
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN total ELSE 0 END) AS today_total,
                COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) AS today_count,
                SUM(CASE WHEN YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE()) THEN total ELSE 0 END) AS month_total
            FROM pos_sales
        ';

        $stmt = $this->db->query($sql);
        $row = $stmt->fetch() ?: [];

        return [
            'today_total' => (float)($row['today_total'] ?? 0),
            'today_count' => (int)($row['today_count'] ?? 0),
            'month_total' => (float)($row['month_total'] ?? 0),
        ];
    }

    public function housekeepingQueue(int $limit = 10): array
    {
        $sql = '
            SELECT rooms.display_name, rooms.room_number, room_types.name AS room_type_name, rooms.floor
            FROM rooms
            INNER JOIN room_types ON room_types.id = rooms.room_type_id
            WHERE rooms.status = \'needs_cleaning\'
            ORDER BY rooms.floor, rooms.room_number LIMIT ' . (int)$limit;

        $stmt = $this->db->query($sql);

        return $stmt->fetchAll();
    }
}


