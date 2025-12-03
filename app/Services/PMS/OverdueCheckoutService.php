<?php

namespace App\Services\PMS;

use App\Repositories\ReservationRepository;
use App\Services\Notifications\NotificationService;

class OverdueCheckoutService
{
    protected ReservationRepository $reservations;
    protected NotificationService $notifications;

    public function __construct()
    {
        $this->reservations = new ReservationRepository();
        $this->notifications = new NotificationService();
    }

    /**
     * Check for overdue checkouts and create notifications
     * Returns array of overdue reservations
     */
    public function checkAndNotify(): array
    {
        $overdue = $this->reservations->getOverdueCheckouts();
        
        if (empty($overdue)) {
            return [];
        }

        // Group by days overdue for better notification messages
        $grouped = [];
        foreach ($overdue as $reservation) {
            $daysOverdue = (int)($reservation['days_overdue'] ?? 0);
            $grouped[$daysOverdue][] = $reservation;
        }

        // Notify stakeholders
        $stakeholderRoles = ['receptionist', 'cashier', 'operation_manager', 'director', 'finance_manager'];
        
        foreach ($grouped as $days => $reservations) {
            $count = count($reservations);
            $dayText = $days === 1 ? '1 day' : "$days days";
            
            $title = "⚠️ Overdue Checkout Alert";
            $message = sprintf(
                '%d guest(s) have exceeded checkout date by %s. Immediate action required to check out.',
                $count,
                $dayText
            );
            
            $payload = [
                'type' => 'overdue_checkout',
                'days_overdue' => $days,
                'count' => $count,
                'reservations' => array_map(function($r) {
                    return [
                        'id' => $r['id'],
                        'reference' => $r['reference'],
                        'guest_name' => $r['guest_name'],
                        'room' => $r['display_name'] ?? $r['room_number'] ?? 'Unassigned',
                        'check_out' => $r['check_out'],
                        'days_overdue' => $r['days_overdue'],
                    ];
                }, $reservations),
            ];

            // Check for existing unread notification with same title and payload type in last 24 hours
            // to prevent duplicates
            $payloadJson = json_encode($payload);
            $existingCheck = $this->hasRecentNotification($title, 'overdue_checkout', 24);
            
            foreach ($stakeholderRoles as $role) {
                // Only create notification if one doesn't already exist for this role
                if (!$existingCheck || !$this->hasRecentNotificationForRole($role, $title, 'overdue_checkout', 24)) {
                    $this->notifications->notifyRole($role, $title, $message, $payload);
                }
            }
        }

        return $overdue;
    }
    
    /**
     * Check if a notification with given title and payload type exists in the last N hours
     */
    protected function hasRecentNotification(string $title, string $payloadType, int $hours = 24): bool
    {
        $sql = "
            SELECT COUNT(*) 
            FROM notifications 
            WHERE title = :title 
                AND payload LIKE :payload_pattern
                AND status = 'unread'
                AND created_at >= DATE_SUB(NOW(), INTERVAL :hours HOUR)
        ";
        
        $stmt = db()->prepare($sql);
        $stmt->execute([
            'title' => $title,
            'payload_pattern' => '%"type":"' . $payloadType . '"%',
            'hours' => $hours,
        ]);
        
        return (int)$stmt->fetchColumn() > 0;
    }
    
    /**
     * Check if a notification with given title and payload type exists for a specific role in the last N hours
     */
    protected function hasRecentNotificationForRole(string $roleKey, string $title, string $payloadType, int $hours = 24): bool
    {
        $sql = "
            SELECT COUNT(*) 
            FROM notifications 
            WHERE role_key = :role_key
                AND title = :title 
                AND payload LIKE :payload_pattern
                AND status = 'unread'
                AND created_at >= DATE_SUB(NOW(), INTERVAL :hours HOUR)
        ";
        
        $stmt = db()->prepare($sql);
        $stmt->execute([
            'role_key' => $roleKey,
            'title' => $title,
            'payload_pattern' => '%"type":"' . $payloadType . '"%',
            'hours' => $hours,
        ]);
        
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Get overdue checkouts without creating notifications
     */
    public function getOverdue(): array
    {
        return $this->reservations->getOverdueCheckouts();
    }
}

