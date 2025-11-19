<?php

namespace App\Modules\Reports\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Support\Auth;

class OperationsReportController extends Controller
{
    public function index(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager', 'director']);

        $start = $this->sanitizeDate($request->input('start')) ?? date('Y-m-01');
        $end = $this->sanitizeDate($request->input('end')) ?? date('Y-m-d');

        if (strtotime($end) < strtotime($start)) {
            $end = $start;
        }

        // Room occupancy statistics
        $roomStats = $this->getRoomStatistics($start, $end);
        
        // Check-in/Check-out statistics
        $checkInOutStats = $this->getCheckInOutStatistics($start, $end);
        
        // Room status breakdown
        $roomStatusBreakdown = $this->getRoomStatusBreakdown();
        
        // Maintenance requests
        $maintenanceStats = $this->getMaintenanceStatistics($start, $end);
        
        // Task completion statistics
        $taskStats = $this->getTaskStatistics($start, $end);
        
        // Inventory alerts (low stock)
        $inventoryAlerts = $this->getInventoryAlerts();
        
        // Staff attendance summary
        $attendanceStats = $this->getAttendanceStatistics($start, $end);

        $this->view('dashboard/reports/operations', [
            'filters' => [
                'start' => $start,
                'end' => $end,
            ],
            'roomStats' => $roomStats,
            'checkInOutStats' => $checkInOutStats,
            'roomStatusBreakdown' => $roomStatusBreakdown,
            'maintenanceStats' => $maintenanceStats,
            'taskStats' => $taskStats,
            'inventoryAlerts' => $inventoryAlerts,
            'attendanceStats' => $attendanceStats,
        ]);
    }

    protected function sanitizeDate(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $ts = strtotime($value);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    protected function getRoomStatistics(string $start, string $end): array
    {
        // Get total rooms
        $stmt = db()->prepare('SELECT COUNT(*) as total FROM rooms WHERE status != "deleted"');
        $stmt->execute();
        $totalRooms = (int)($stmt->fetch()['total'] ?? 0);

        // Get occupied rooms in date range
        $stmt = db()->prepare('
            SELECT COUNT(DISTINCT room_id) as occupied
            FROM reservations
            WHERE status IN ("confirmed", "checked_in")
            AND check_in <= :end
            AND check_out >= :start
            AND room_id IS NOT NULL
        ');
        $stmt->execute(['start' => $start, 'end' => $end]);
        $occupiedRooms = (int)($stmt->fetch()['occupied'] ?? 0);

        // Calculate occupancy rate
        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 2) : 0;

        // Get reservations count
        $stmt = db()->prepare('
            SELECT COUNT(*) as count
            FROM reservations
            WHERE check_in >= :start AND check_in <= :end
            AND status IN ("confirmed", "checked_in", "checked_out")
        ');
        $stmt->execute(['start' => $start, 'end' => $end]);
        $reservationsCount = (int)($stmt->fetch()['count'] ?? 0);

        return [
            'total_rooms' => $totalRooms,
            'occupied_rooms' => $occupiedRooms,
            'available_rooms' => $totalRooms - $occupiedRooms,
            'occupancy_rate' => $occupancyRate,
            'reservations_count' => $reservationsCount,
        ];
    }

    protected function getCheckInOutStatistics(string $start, string $end): array
    {
        // Check-ins
        $stmt = db()->prepare('
            SELECT COUNT(*) as count
            FROM reservations
            WHERE check_in >= :start AND check_in <= :end
            AND status IN ("checked_in", "checked_out")
        ');
        $stmt->execute(['start' => $start, 'end' => $end]);
        $checkIns = (int)($stmt->fetch()['count'] ?? 0);

        // Check-outs
        $stmt = db()->prepare('
            SELECT COUNT(*) as count
            FROM reservations
            WHERE check_out >= :start AND check_out <= :end
            AND status = "checked_out"
        ');
        $stmt->execute(['start' => $start, 'end' => $end]);
        $checkOuts = (int)($stmt->fetch()['count'] ?? 0);

        // Average stay duration
        $stmt = db()->prepare('
            SELECT AVG(DATEDIFF(check_out, check_in)) as avg_duration
            FROM reservations
            WHERE check_out >= :start AND check_out <= :end
            AND status = "checked_out"
            AND check_in IS NOT NULL AND check_out IS NOT NULL
        ');
        $stmt->execute(['start' => $start, 'end' => $end]);
        $avgDuration = round((float)($stmt->fetch()['avg_duration'] ?? 0), 1);

        return [
            'check_ins' => $checkIns,
            'check_outs' => $checkOuts,
            'avg_stay_duration' => $avgDuration,
        ];
    }

    protected function getRoomStatusBreakdown(): array
    {
        $stmt = db()->prepare('
            SELECT 
                status,
                COUNT(*) as count
            FROM rooms
            WHERE status != "deleted"
            GROUP BY status
        ');
        $stmt->execute();
        
        $breakdown = [];
        $total = 0;
        foreach ($stmt->fetchAll() as $row) {
            $breakdown[$row['status']] = (int)$row['count'];
            $total += (int)$row['count'];
        }

        return [
            'breakdown' => $breakdown,
            'total' => $total,
        ];
    }

    protected function getMaintenanceStatistics(string $start, string $end): array
    {
        // Check if maintenance_requests table exists
        try {
            $stmt = db()->prepare('
                SELECT 
                    status,
                    COUNT(*) as count
                FROM maintenance_requests
                WHERE created_at >= :start AND created_at <= :end
                GROUP BY status
            ');
            $stmt->execute(['start' => $start, 'end' => $end . ' 23:59:59']);
            
            $stats = [
                'pending' => 0,
                'in_progress' => 0,
                'completed' => 0,
                'total' => 0,
            ];
            
            foreach ($stmt->fetchAll() as $row) {
                $status = strtolower($row['status'] ?? 'pending');
                $count = (int)$row['count'];
                if (isset($stats[$status])) {
                    $stats[$status] = $count;
                }
                $stats['total'] += $count;
            }
            
            return $stats;
        } catch (\PDOException $e) {
            // Table doesn't exist
            return [
                'pending' => 0,
                'in_progress' => 0,
                'completed' => 0,
                'total' => 0,
            ];
        }
    }

    protected function getTaskStatistics(string $start, string $end): array
    {
        try {
            // Get task completion stats
            $stmt = db()->prepare('
                SELECT 
                    status,
                    COUNT(*) as count
                FROM tasks
                WHERE created_at >= :start AND created_at <= :end
                GROUP BY status
            ');
            $stmt->execute(['start' => $start, 'end' => $end . ' 23:59:59']);
            
            $stats = [
                'pending' => 0,
                'in_progress' => 0,
                'completed' => 0,
                'cancelled' => 0,
                'total' => 0,
            ];
            
            foreach ($stmt->fetchAll() as $row) {
                $status = strtolower($row['status'] ?? 'pending');
                $count = (int)$row['count'];
                if (isset($stats[$status])) {
                    $stats[$status] = $count;
                }
                $stats['total'] += $count;
            }
            
            // Calculate completion rate
            $completionRate = $stats['total'] > 0 
                ? round(($stats['completed'] / $stats['total']) * 100, 2) 
                : 0;
            
            $stats['completion_rate'] = $completionRate;
            
            return $stats;
        } catch (\PDOException $e) {
            return [
                'pending' => 0,
                'in_progress' => 0,
                'completed' => 0,
                'cancelled' => 0,
                'total' => 0,
                'completion_rate' => 0,
            ];
        }
    }

    protected function getInventoryAlerts(): array
    {
        try {
            $stmt = db()->prepare('
                SELECT 
                    name,
                    current_stock,
                    reorder_level,
                    unit
                FROM inventory_items
                WHERE current_stock <= reorder_level
                AND status = "active"
                ORDER BY current_stock ASC
                LIMIT 20
            ');
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            return [];
        }
    }

    protected function getAttendanceStatistics(string $start, string $end): array
    {
        try {
            // Get attendance summary
            $stmt = db()->prepare('
                SELECT 
                    COUNT(DISTINCT user_id) as total_staff,
                    COUNT(DISTINCT date) as total_days,
                    SUM(CASE WHEN checked_out = TRUE AND check_out_time IS NOT NULL 
                        THEN TIMESTAMPDIFF(SECOND, check_in_time, check_out_time) / 3600.0 
                        ELSE 0 END) as total_hours
                FROM staff_attendance
                WHERE date >= :start AND date <= :end
            ');
            $stmt->execute(['start' => $start, 'end' => $end]);
            $result = $stmt->fetch();
            
            return [
                'total_staff' => (int)($result['total_staff'] ?? 0),
                'total_days' => (int)($result['total_days'] ?? 0),
                'total_hours' => round((float)($result['total_hours'] ?? 0), 2),
            ];
        } catch (\PDOException $e) {
            return [
                'total_staff' => 0,
                'total_days' => 0,
                'total_hours' => 0,
            ];
        }
    }
}

