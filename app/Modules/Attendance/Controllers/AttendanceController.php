<?php

namespace App\Modules\Attendance\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\AttendanceRepository;
use App\Repositories\LoginOverrideRepository;
use App\Repositories\UserRepository;
use App\Support\Auth;

class AttendanceController extends Controller
{
    protected AttendanceRepository $attendance;
    protected LoginOverrideRepository $overrides;
    protected UserRepository $users;

    public function __construct()
    {
        $this->attendance = new AttendanceRepository();
        $this->overrides = new LoginOverrideRepository();
        $this->users = new UserRepository();
    }

    public function index(Request $request): void
    {
        Auth::requireRoles(['admin', 'director']);

        $date = $request->input('date', date('Y-m-d'));
        $userId = $request->input('user_id') ? (int)$request->input('user_id') : null;
        $startDate = $request->input('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $request->input('end_date', date('Y-m-d'));

        $todayAttendance = $this->attendance->getAllTodayAttendance();
        $allRecords = $this->attendance->getAllAttendanceRecords($startDate, $endDate, $userId, 200);
        $statistics = $this->attendance->getAttendanceStatistics($userId, $startDate, $endDate);
        $anomalies = $this->attendance->detectAnomalies($userId, 30);
        $bestAttendance = $this->attendance->getBestAttendance(10);
        
        // Get all users for filter
        $allUsers = $this->users->all(null, 'active', null);

        $this->view('dashboard/attendance/index', [
            'todayAttendance' => $todayAttendance,
            'allRecords' => $allRecords,
            'statistics' => $statistics,
            'anomalies' => $anomalies,
            'bestAttendance' => $bestAttendance,
            'allUsers' => $allUsers,
            'date' => $date,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedUserId' => $userId,
        ]);
    }

    public function checkIn(Request $request): void
    {
        Auth::requireRoles(['security', 'tech_admin']);

        $userId = (int)$request->input('user_id');
        $notes = trim($request->input('notes', ''));

        if (!$userId) {
            header('Location: ' . base_url('staff/dashboard/attendance?error=Invalid%20user'));
            return;
        }

        try {
            $this->attendance->checkIn($userId, $notes ?: null);
            header('Location: ' . base_url('staff/dashboard/attendance?success=Check-in%20recorded'));
        } catch (\Exception $e) {
            header('Location: ' . base_url('staff/dashboard/attendance?error=' . urlencode($e->getMessage())));
        }
    }

    public function checkOut(Request $request): void
    {
        Auth::requireRoles(['security', 'tech_admin']);

        $userId = (int)$request->input('user_id');
        $notes = trim($request->input('notes', ''));

        if (!$userId) {
            header('Location: ' . base_url('staff/dashboard/attendance?error=Invalid%20user'));
            return;
        }

        try {
            $this->attendance->checkOut($userId, $notes ?: null);
            
            // If the user being checked out is currently logged in, log them out
            if (Auth::check() && (int)Auth::user()['id'] === $userId) {
                Auth::logout();
                header('Location: ' . base_url('staff/login?message=' . urlencode('You have been logged out because you were checked out.')));
                return;
            }
            
            header('Location: ' . base_url('staff/dashboard/attendance?success=Check-out%20recorded'));
        } catch (\Exception $e) {
            header('Location: ' . base_url('staff/dashboard/attendance?error=' . urlencode($e->getMessage())));
        }
    }

    public function grantOverride(Request $request): void
    {
        Auth::requireRoles(['admin', 'director', 'tech_admin']);

        $userId = (int)$request->input('user_id');
        $reason = trim($request->input('reason', ''));
        $durationHours = (int)$request->input('duration_hours', 1);

        if (!$userId) {
            header('Location: ' . base_url('staff/dashboard/attendance?error=Invalid%20user'));
            return;
        }

        if ($durationHours < 1 || $durationHours > 1) {
            $durationHours = 1; // Enforce 1 hour maximum
        }

        $approver = Auth::user();
        
        try {
            $this->overrides->create($userId, $approver['id'], $reason ?: null, $durationHours);
            header('Location: ' . base_url('staff/dashboard/attendance?success=Login%20override%20granted'));
        } catch (\Exception $e) {
            header('Location: ' . base_url('staff/dashboard/attendance?error=' . urlencode($e->getMessage())));
        }
    }

    public function myAttendance(Request $request): void
    {
        Auth::requireRoles([]); // All authenticated users

        $user = Auth::user();
        $todayAttendance = $this->attendance->getTodayAttendance($user['id']);
        $history = $this->attendance->getAttendanceHistory($user['id'], 30);

        $this->view('dashboard/attendance/my-attendance', [
            'todayAttendance' => $todayAttendance,
            'history' => $history,
        ]);
    }
}

