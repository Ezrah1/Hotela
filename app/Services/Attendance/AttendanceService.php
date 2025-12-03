<?php

namespace App\Services\Attendance;

use App\Repositories\AttendanceRepository;
use App\Repositories\DutyRosterRepository;
use App\Services\Notifications\NotificationService;

class AttendanceService
{
    protected AttendanceRepository $attendanceRepo;
    protected DutyRosterRepository $rosterRepo;
    protected NotificationService $notifications;

    public function __construct()
    {
        $this->attendanceRepo = new AttendanceRepository();
        $this->rosterRepo = new DutyRosterRepository();
        $this->notifications = new NotificationService();
    }

    /**
     * Check in a staff member
     */
    public function checkIn(int $userId, int $checkedInBy, ?string $notes = null): array
    {
        // Check if user has assigned shift for today
        $today = date('Y-m-d');
        $now = date('H:i:s');
        $hasAssignedShift = $this->rosterRepo->hasAssignedShift($userId, $today, $now);
        
        $attendanceId = $this->attendanceRepo->checkIn($userId, $checkedInBy, $notes);
        
        // Flag for review if checking in outside assigned shift
        $flagForReview = false;
        if (!$hasAssignedShift) {
            $flagForReview = true;
            // Notify operations manager
            $this->notifications->notifyRole('operation_manager', 'Attendance Flag', 
                "Staff member checked in outside assigned shift time.", ['user_id' => $userId, 'attendance_id' => $attendanceId]);
        }
        
        return [
            'success' => true,
            'attendance_id' => $attendanceId,
            'flagged_for_review' => $flagForReview,
        ];
    }

    /**
     * Check out a staff member
     */
    public function checkOut(int $userId, int $checkedOutBy, ?string $notes = null): bool
    {
        $result = $this->attendanceRepo->checkOut($userId, $checkedOutBy, $notes);
        
        if ($result) {
            // Revoke all active sessions for this user (except exempt roles)
            $this->revokeUserSessions($userId);
        }
        
        return $result;
    }

    /**
     * Revoke user sessions when checked out
     */
    protected function revokeUserSessions(int $userId): void
    {
        // This would typically be handled by session management
        // For now, we'll rely on Auth::requireRoles() to check on each request
        // In a production system, you might want to store active session IDs and invalidate them
    }
}

