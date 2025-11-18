<?php

namespace App\Repositories;

use PDO;

class DashboardRepository
{
    protected PDO $db;
    protected ?int $tenantId;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
        $this->tenantId = \App\Support\Tenant::id();
    }

    protected function tenantFilter(string $column, array &$params): string
    {
        if ($this->tenantId === null) {
            return '';
        }

        $param = 'tenant_' . str_replace('.', '_', $column) . '_' . count($params);
        $params[$param] = $this->tenantId;

        return " AND {$column} = :{$param}";
    }

    public function occupancySnapshot(): array
    {
        $params = [];
        $sql = 'SELECT status, COUNT(*) AS total FROM rooms WHERE 1=1';
        $sql .= $this->tenantFilter('rooms.tenant_id', $params);
        $sql .= ' GROUP BY status';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

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
        $params = [];
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
        $sql .= $this->tenantFilter('reservations.tenant_id', $params);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
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
        $params = ['status' => 'open'];
        $sql = 'SELECT SUM(balance) FROM folios WHERE status = :status';
        $sql .= $this->tenantFilter('folios.tenant_id', $params);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (float)($stmt->fetchColumn() ?? 0);
    }

    public function activeUsers(): int
    {
        $params = [];
        $sql = 'SELECT COUNT(*) FROM users WHERE 1=1';
        $sql .= $this->tenantFilter('users.tenant_id', $params);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    public function unreadNotificationsCount(?string $role = null): int
    {
        $params = [];
        $sql = 'SELECT COUNT(*) FROM notifications WHERE read_at IS NULL';

        if ($role) {
            $sql .= ' AND (role_key IS NULL OR role_key = :role)';
            $params['role'] = $role;
        }

        if ($this->tenantId !== null) {
            $sql .= ' AND (tenant_id IS NULL OR tenant_id = :tenantScoped)';
            $params['tenantScoped'] = $this->tenantId;
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
            $sql .= ' AND (role_key IS NULL OR role_key = :role)';
            $params['role'] = $role;
        }

        if ($this->tenantId !== null) {
            $sql .= ' AND (tenant_id IS NULL OR tenant_id = :tenantScoped)';
            $params['tenantScoped'] = $this->tenantId;
        }

        $sql .= ' ORDER BY created_at DESC LIMIT ' . (int)$limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function arrivals(int $limit = 5): array
    {
        $params = [
            'start' => date('Y-m-d'),
            'end' => date('Y-m-d', strtotime('+2 days')),
        ];

        $sql = '
            SELECT reservations.reference, reservations.guest_name, reservations.check_in,
                   rooms.display_name, rooms.room_number, room_types.name AS room_type_name
            FROM reservations
            LEFT JOIN rooms ON rooms.id = reservations.room_id
            LEFT JOIN room_types ON room_types.id = reservations.room_type_id
            WHERE reservations.check_in BETWEEN :start AND :end
            ORDER BY reservations.check_in ASC
        ';
        $sql .= $this->tenantFilter('reservations.tenant_id', $params);
        $sql .= ' LIMIT ' . (int)$limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function departures(int $limit = 5): array
    {
        $params = [
            'start' => date('Y-m-d'),
            'end' => date('Y-m-d', strtotime('+2 days')),
        ];

        $sql = '
            SELECT reservations.reference, reservations.guest_name, reservations.check_out,
                   rooms.display_name, rooms.room_number, room_types.name AS room_type_name
            FROM reservations
            LEFT JOIN rooms ON rooms.id = reservations.room_id
            LEFT JOIN room_types ON room_types.id = reservations.room_type_id
            WHERE reservations.check_out BETWEEN :start AND :end
            ORDER BY reservations.check_out ASC
        ';
        $sql .= $this->tenantFilter('reservations.tenant_id', $params);
        $sql .= ' LIMIT ' . (int)$limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function pendingPayments(int $limit = 5): array
    {
        $params = ['status' => 'open'];
        $sql = '
            SELECT reservations.reference, reservations.guest_name,
                   rooms.display_name, rooms.room_number, folios.balance
            FROM folios
            INNER JOIN reservations ON reservations.id = folios.reservation_id
            LEFT JOIN rooms ON rooms.id = reservations.room_id
            WHERE folios.balance > 0 AND folios.status = :status
        ';
        $sql .= $this->tenantFilter('folios.tenant_id', $params);
        $sql .= ' ORDER BY folios.updated_at DESC LIMIT ' . (int)$limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
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
        $params = [];
        $sql = '
            SELECT
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN total ELSE 0 END) AS today_total,
                COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) AS today_count,
                SUM(CASE WHEN YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE()) THEN total ELSE 0 END) AS month_total
            FROM pos_sales
            WHERE 1 = 1
        ';
        $sql .= $this->tenantFilter('pos_sales.tenant_id', $params);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch() ?: [];

        return [
            'today_total' => (float)($row['today_total'] ?? 0),
            'today_count' => (int)($row['today_count'] ?? 0),
            'month_total' => (float)($row['month_total'] ?? 0),
        ];
    }

    public function housekeepingQueue(int $limit = 10): array
    {
        $params = [];
        $sql = '
            SELECT rooms.display_name, rooms.room_number, room_types.name AS room_type_name, rooms.floor
            FROM rooms
            INNER JOIN room_types ON room_types.id = rooms.room_type_id
            WHERE rooms.status = \'needs_cleaning\'
        ';
        $sql .= $this->tenantFilter('rooms.tenant_id', $params);
        $sql .= ' ORDER BY rooms.floor, rooms.room_number LIMIT ' . (int)$limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}


