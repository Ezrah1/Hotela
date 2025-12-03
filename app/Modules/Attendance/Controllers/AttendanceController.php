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
        $currentUser = Auth::user();
        $role = $currentUser['role_key'] ?? '';
        
        // Security gets a different, simplified view
        if ($role === 'security') {
            Auth::requireRoles(['security']);
            
            $todayAttendance = $this->attendance->getAllTodayAttendance();
            
            // Get all active users for check-in dropdown (excluding exempt roles)
            $allUsers = $this->users->all(null, 'active', null);
            
            // Debug: Log how many users we have
            error_log('Security attendance view - All users count: ' . count($allUsers));
            if (!empty($allUsers)) {
                error_log('First user sample: ' . json_encode($allUsers[0] ?? []));
            }

            $this->view('dashboard/attendance/security', [
                'todayAttendance' => $todayAttendance,
                'allUsers' => $allUsers,
            ]);
            return;
        }
        
        // Admin and directors get the full management view
        Auth::requireRoles(['admin', 'director']);

        $date = $request->input('date', date('Y-m-d'));
        $userId = $request->input('user_id') ? (int)$request->input('user_id') : null;
        $startDate = $request->input('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $request->input('end_date', date('Y-m-d'));

        $todayAttendance = $this->attendance->getAllTodayAttendance();
        $allRecords = $this->attendance->getAllAttendanceRecords($startDate, $endDate, $userId, 200);
        $aggregateStats = $this->attendance->getAttendanceStatistics($userId, $startDate, $endDate);
        $perEmployeeStats = $this->attendance->getPerEmployeeStatistics($startDate, $endDate);
        $anomalies = $this->attendance->detectAnomalies($userId, 30);
        $bestAttendance = $this->attendance->getBestAttendance(10);
        
        // Get all users for filter
        $allUsers = $this->users->all(null, 'active', null);

        $this->view('dashboard/attendance/index', [
            'todayAttendance' => $todayAttendance,
            'allRecords' => $allRecords,
            'statistics' => $aggregateStats, // Aggregate stats for summary cards
            'perEmployeeStats' => $perEmployeeStats, // Per-employee stats for table
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
        Auth::requireRoles(['security', 'tech']);

        // Debug: Check all possible sources of POST data
        $method = $_SERVER['REQUEST_METHOD'] ?? 'unknown';
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        // Try multiple ways to get the user_id
        $userIdInput = null;
        
        // 1. Check $_POST directly
        if (isset($_POST['user_id']) && $_POST['user_id'] !== '') {
            $userIdInput = $_POST['user_id'];
        }
        // 2. Check request body if it's empty
        elseif ($method === 'POST' && empty($_POST)) {
            $rawInput = file_get_contents('php://input');
            parse_str($rawInput, $parsedData);
            if (isset($parsedData['user_id']) && $parsedData['user_id'] !== '') {
                $userIdInput = $parsedData['user_id'];
                $_POST = $parsedData; // Update $_POST for future use
            }
        }
        // 3. Check Request class
        elseif ($request->input('user_id')) {
            $userIdInput = $request->input('user_id');
        }
        
        // Debug logging FIRST - before any processing
        error_log('=== CHECK-IN DEBUG START ===');
        error_log('Method: ' . $method);
        error_log('Content-Type: ' . $contentType);
        error_log('$_POST: ' . json_encode($_POST));
        error_log('php://input: ' . file_get_contents('php://input'));
        error_log('Request input: ' . json_encode($request->input('user_id')));
        error_log('userIdInput (raw): ' . var_export($userIdInput, true));
        
        // Handle both string and integer inputs
        if ($userIdInput !== null && $userIdInput !== '' && $userIdInput !== '0') {
            if (is_string($userIdInput)) {
                $userIdInput = trim($userIdInput);
            }
            $userId = (int)$userIdInput;
        } else {
            $userId = 0;
        }
        
        error_log('userId (processed): ' . $userId);
        error_log('=== CHECK-IN DEBUG END ===');
        
        $notes = trim($_POST['notes'] ?? $request->input('notes', '') ?? '');

        if (!$userId || $userId <= 0) {
            $debugInfo = [
                'POST' => $_POST,
                'REQUEST_METHOD' => $method,
                'CONTENT_TYPE' => $contentType,
                'raw_input' => file_get_contents('php://input'),
                'request_input' => $request->input('user_id'),
                'userIdInput' => $userIdInput,
                'userId' => $userId,
            ];
            error_log('Check-in validation failed. Full debug: ' . json_encode($debugInfo, JSON_PRETTY_PRINT));
            header('Location: ' . base_url('staff/dashboard/attendance?error=' . urlencode('Please select an employee')));
            return;
        }

        // Verify user exists
        $user = $this->users->find($userId);
        if (!$user) {
            header('Location: ' . base_url('staff/dashboard/attendance?error=User%20not%20found'));
            return;
        }

        // Security should not check in exempt roles
        $exemptRoles = ['admin', 'director', 'tech', 'security'];
        $currentUser = Auth::user();
        if (($currentUser['role_key'] ?? '') === 'security' && in_array($user['role_key'] ?? '', $exemptRoles, true)) {
            header('Location: ' . base_url('staff/dashboard/attendance?error=This%20employee%20does%20not%20require%20check-in'));
            return;
        }

        try {
            $attendanceId = $this->attendance->checkIn($userId, $notes ?: null);
            
            // Send check-in confirmation email
            if (!empty($user['email'])) {
                try {
                    // Get the attendance record that was just created
                    $attendanceRecord = $this->attendance->getCurrentAttendance($userId);
                    if ($attendanceRecord) {
                        $emailService = new \App\Services\Email\EmailService();
                        $emailService->sendStaffCheckInEmail($user, $attendanceRecord);
                    }
                } catch (\Exception $e) {
                    // Log error but don't fail check-in
                    error_log('Failed to send check-in email to ' . ($user['email'] ?? 'unknown') . ': ' . $e->getMessage());
                }
            }
            
            header('Location: ' . base_url('staff/dashboard/attendance?success=Check-in%20recorded%20for%20' . urlencode($user['name'])));
        } catch (\Exception $e) {
            header('Location: ' . base_url('staff/dashboard/attendance?error=' . urlencode($e->getMessage())));
        }
    }

    public function checkOut(Request $request): void
    {
        Auth::requireRoles(['security', 'tech']);

        $userIdInput = $request->input('user_id');
        $userId = $userIdInput ? (int)$userIdInput : 0;
        $notes = trim($request->input('notes', ''));

        if (!$userId) {
            header('Location: ' . base_url('staff/dashboard/attendance?error=Please%20select%20an%20employee'));
            return;
        }

        // Verify user exists
        $user = $this->users->find($userId);
        if (!$user) {
            header('Location: ' . base_url('staff/dashboard/attendance?error=User%20not%20found'));
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
            
            header('Location: ' . base_url('staff/dashboard/attendance?success=Check-out%20recorded%20for%20' . urlencode($user['name'])));
        } catch (\Exception $e) {
            header('Location: ' . base_url('staff/dashboard/attendance?error=' . urlencode($e->getMessage())));
        }
    }

    public function grantOverride(Request $request): void
    {
        Auth::requireRoles(['admin', 'director', 'tech']);

        // DIRECTLY read from $_POST - bypass Request class which may have issues
        $userId = 0;
        if (isset($_POST['user_id']) && $_POST['user_id'] !== '' && $_POST['user_id'] !== '0') {
            $userIdInput = $_POST['user_id'];
            if (is_string($userIdInput)) {
                $userIdInput = trim($userIdInput);
            }
            if (is_numeric($userIdInput)) {
                $userId = (int)$userIdInput;
            }
        }
        
        // If still 0, try Request class as fallback
        if ($userId === 0) {
            $userIdInput = $request->input('user_id');
            if ($userIdInput && $userIdInput !== '' && $userIdInput !== '0') {
                if (is_string($userIdInput)) {
                    $userIdInput = trim($userIdInput);
                }
                if (is_numeric($userIdInput)) {
                    $userId = (int)$userIdInput;
                }
            }
        }
        
        $reason = '';
        if (isset($_POST['reason'])) {
            $reason = trim($_POST['reason']);
        } else {
            $reason = trim($request->input('reason', ''));
        }
        
        $durationHours = 1;
        if (isset($_POST['duration_hours'])) {
            $durationHours = (int)$_POST['duration_hours'];
        } else {
            $durationHours = (int)$request->input('duration_hours', 1);
        }
        
        $redirect = '';
        if (isset($_POST['redirect'])) {
            $redirect = trim($_POST['redirect']);
        } else {
            $redirect = trim($request->input('redirect', ''));
        }

        // Debug logging
        $debugInfo = [
            'METHOD' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            '_POST' => $_POST,
            'request_input_user_id' => $request->input('user_id'),
            'final_userId' => $userId,
        ];
        error_log('=== GRANT OVERRIDE DEBUG ===');
        error_log(print_r($debugInfo, true));
        error_log('=== END DEBUG ===');

        // Validate user_id
        if (!$userId || $userId <= 0) {
            $redirectUrl = $redirect ?: base_url('staff/dashboard/staff');
            header('Location: ' . $redirectUrl . '?error=Please%20select%20a%20user');
            return;
        }

        // Validate that the user exists
        $userRepo = new \App\Repositories\UserRepository();
        $user = $userRepo->find($userId);
        if (!$user) {
            $redirectUrl = $redirect ?: base_url('staff/dashboard/staff');
            header('Location: ' . $redirectUrl . '?error=User%20not%20found');
            return;
        }

        if ($durationHours < 1 || $durationHours > 1) {
            $durationHours = 1; // Enforce 1 hour maximum
        }

        $approver = Auth::user();
        
        try {
            $this->overrides->create($userId, $approver['id'], $reason ?: null, $durationHours);
            $redirectUrl = $redirect ?: base_url('staff/dashboard/staff');
            header('Location: ' . $redirectUrl . '?success=Login%20override%20granted%20for%20' . urlencode($user['name']));
        } catch (\Exception $e) {
            $redirectUrl = $redirect ?: base_url('staff/dashboard/staff');
            header('Location: ' . $redirectUrl . '?error=' . urlencode($e->getMessage()));
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

    public function ignoreAnomaly(Request $request): void
    {
        Auth::requireRoles(['admin', 'director']);
        
        $attendanceLogId = (int)$request->input('attendance_log_id');
        $reason = $request->input('reason');
        
        if (!$attendanceLogId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid attendance log ID']);
            return;
        }
        
        $currentUser = Auth::user();
        $ignoredBy = (int)$currentUser['id'];
        
        $success = $this->attendance->ignoreAnomaly($attendanceLogId, $ignoredBy, $reason);
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Anomaly ignored successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to ignore anomaly']);
        }
    }
}

