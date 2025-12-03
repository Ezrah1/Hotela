<?php

namespace App\Modules\Housekeeping\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\HousekeepingRepository;
use App\Repositories\GuestRequestRepository;
use App\Repositories\MaintenanceRepository;
use App\Repositories\RoomRepository;
use App\Services\Notifications\NotificationService;
use App\Support\Auth;

class HousekeepingController extends Controller
{
    protected HousekeepingRepository $housekeeping;
    protected GuestRequestRepository $guestRequests;
    protected MaintenanceRepository $maintenance;
    protected RoomRepository $rooms;
    protected NotificationService $notifications;

    public function __construct()
    {
        $this->housekeeping = new HousekeepingRepository();
        $this->guestRequests = new GuestRequestRepository();
        $this->maintenance = new MaintenanceRepository();
        $this->rooms = new RoomRepository();
        $this->notifications = new NotificationService();
    }

    protected function getStaffList(): array
    {
        $stmt = db()->prepare("SELECT u.id, u.name FROM users u INNER JOIN roles r ON r.key = u.role_key WHERE u.role_key IN ('housekeeping', 'operation_manager', 'ground') AND u.status = 'active' ORDER BY u.name");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function index(Request $request): void
    {
        Auth::requireRoles(['housekeeping', 'operation_manager', 'admin', 'director', 'receptionist', 'ground']);
        
        $status = $request->input('status');
        $assignedTo = $request->input('assigned_to') ? (int)$request->input('assigned_to') : null;
        $myTasks = $request->input('my_tasks') === '1';
        
        $user = Auth::user();
        $staffId = $user['id'] ?? null;
        $role = $user['role_key'] ?? null;
        
        // If viewing my tasks, filter by current staff
        if ($myTasks && $staffId) {
            $assignedTo = $staffId;
        }

        $rooms = $this->housekeeping->getHousekeepingBoard();
        $tasks = $this->housekeeping->getTasksForHousekeeper($assignedTo, $status, 200);
        $pendingRequests = $this->guestRequests->getPendingRequests();
        $dailyStats = $this->housekeeping->getDailyStats();
        
        // Get staff list for assignment dropdown
        $staffList = $this->getStaffList();

        // Filter rooms by status if provided
        if ($status) {
            $rooms = array_filter($rooms, function($room) use ($status) {
                return $room['status'] === $status;
            });
        }

        $this->view('dashboard/housekeeping/index', [
            'rooms' => $rooms,
            'tasks' => $tasks,
            'pendingRequests' => $pendingRequests,
            'dailyStats' => $dailyStats,
            'staffList' => $staffList,
            'status' => $status,
            'assignedTo' => $assignedTo,
            'myTasks' => $myTasks,
            'pageTitle' => 'Housekeeping Dashboard | ' . settings('branding.name', 'Hotela'),
        ]);
    }

    public function updateRoomStatus(Request $request): void
    {
        Auth::requireRoles(['housekeeping', 'operation_manager', 'admin', 'director', 'receptionist', 'front_desk']);
        
        $roomId = (int)$request->input('room_id');
        $status = $request->input('status');
        $reason = $request->input('reason');
        
        $user = Auth::user();
        $staffId = $user['id'] ?? null;

        $allowedStatuses = ['dirty', 'clean', 'in_progress', 'do_not_disturb', 'needs_maintenance', 'inspected', 'available', 'occupied'];
        if (!in_array($status, $allowedStatuses)) {
            header('Location: ' . base_url('staff/dashboard/housekeeping?error=Invalid%20status'));
            return;
        }

        $this->housekeeping->updateRoomStatus($roomId, $status, $staffId, $reason);

        // Notify front desk if room becomes clean
        if ($status === 'clean') {
            $room = $this->rooms->find($roomId);
            if ($room) {
                $this->notifications->notifyRole('front_desk', 'Room cleaned and ready', sprintf(
                    '%s has been cleaned and is ready for check-in.',
                    $room['display_name'] ?? $room['room_number']
                ), ['room_id' => $roomId]);
            }
        }

        header('Location: ' . base_url('staff/dashboard/housekeeping?success=Status%20updated'));
    }

    public function createTask(Request $request): void
    {
        Auth::requireRoles(['housekeeping', 'operation_manager', 'admin', 'director', 'receptionist', 'front_desk']);
        
        $roomId = (int)$request->input('room_id');
        $taskType = $request->input('task_type', 'cleaning');
        $priority = $request->input('priority', 'normal');
        $scheduledDate = $request->input('scheduled_date');
        $assignedTo = $request->input('assigned_to') ? (int)$request->input('assigned_to') : null;
        $notes = $request->input('notes');
        
        $user = Auth::user();
        $staffId = $user['id'] ?? null;

        $taskId = $this->housekeeping->createTask([
            'room_id' => $roomId,
            'task_type' => $taskType,
            'priority' => $priority,
            'scheduled_date' => $scheduledDate ?: date('Y-m-d'),
            'assigned_to' => $assignedTo,
            'notes' => $notes,
            'created_by' => $staffId,
        ]);

        // Notify assigned housekeeper
        if ($assignedTo) {
            $room = $this->rooms->find($roomId);
            $roomName = $room ? ($room['display_name'] ?? $room['room_number'] ?? 'Room') : 'Room';
            
            // Note: notifyUser expects user_id, but staff table might be separate
            // If staff table has user_id, map it, otherwise use staff_id as user_id
            $this->notifications->notifyRole('housekeeping', 'New housekeeping task assigned', sprintf(
                'You have been assigned a %s task for %s.',
                $taskType,
                $roomName
            ), ['task_id' => $taskId, 'room_id' => $roomId, 'assigned_to' => $assignedTo]);
        }

        header('Location: ' . base_url('staff/dashboard/housekeeping?success=Task%20created'));
    }

    public function updateTask(Request $request): void
    {
        Auth::requireRoles(['housekeeping', 'operation_manager', 'admin', 'director']);
        
        $taskId = (int)$request->input('task_id');
        $status = $request->input('status');
        $notes = $request->input('notes');
        
        $user = Auth::user();
        $staffId = $user['id'] ?? null;
        $role = $user['role_key'] ?? null;

        $task = $this->housekeeping->findTaskById($taskId);
        if (!$task) {
            header('Location: ' . base_url('staff/dashboard/housekeeping?error=Task%20not%20found'));
            return;
        }

        $updateData = ['notes' => $notes];

        // Handle status transitions
        if ($status === 'in_progress' && $task['status'] === 'pending') {
            $updateData['status'] = 'in_progress';
            $updateData['started_at'] = date('Y-m-d H:i:s');
            // Update room status
            $this->housekeeping->updateRoomStatus($task['room_id'], 'in_progress', $staffId, 'Housekeeping in progress', null, $taskId);
        } elseif ($status === 'completed' && in_array($task['status'], ['pending', 'in_progress'])) {
            $updateData['status'] = 'completed';
            $updateData['completed_at'] = date('Y-m-d H:i:s');
            // Update room status - mark as clean but needs inspection
            $this->housekeeping->updateRoomStatus($task['room_id'], 'clean', $staffId, 'Cleaning completed - awaiting inspection', null, $taskId);
            
            // Notify supervisor for inspection
            $this->notifications->notifyRole('operations_manager', 'Room cleaned - inspection required', sprintf(
                '%s has been cleaned and requires inspection.',
                $task['room_number'] ?? $task['display_name'] ?? 'Room'
            ), ['task_id' => $taskId, 'room_id' => $task['room_id']]);
        } elseif ($status === 'inspected' && $task['status'] === 'completed' && ($role === 'operations_manager' || $role === 'manager')) {
            $updateData['status'] = 'inspected';
            $updateData['inspected_at'] = date('Y-m-d H:i:s');
            $updateData['inspected_by'] = $staffId;
            // Room is already clean, just mark as inspected
        } elseif ($status === 'approved' && $task['status'] === 'inspected' && ($role === 'operations_manager' || $role === 'manager')) {
            $updateData['status'] = 'approved';
            // Update room to available/ready
            $this->housekeeping->updateRoomStatus($task['room_id'], 'available', $staffId, 'Inspected and approved', null, $taskId);
            
            // Notify front desk
            $this->notifications->notifyRole('front_desk', 'Room ready for check-in', sprintf(
                '%s has been inspected and is ready for check-in.',
                $task['room_number'] ?? $task['display_name'] ?? 'Room'
            ), ['task_id' => $taskId, 'room_id' => $task['room_id']]);
        }

        $this->housekeeping->updateTask($taskId, $updateData);

        header('Location: ' . base_url('staff/dashboard/housekeeping?success=Task%20updated'));
    }

    public function reportMaintenance(Request $request): void
    {
        Auth::requireRoles(['housekeeping', 'operation_manager', 'admin', 'director']);
        
        $taskId = (int)$request->input('task_id');
        $roomId = (int)$request->input('room_id');
        $title = $request->input('title');
        $description = $request->input('description');
        $category = $request->input('category', 'General');
        $priority = $request->input('priority', 'normal');
        $photos = $request->input('photos'); // JSON array of photo URLs
        
        $user = Auth::user();
        $staffId = $user['id'] ?? null;
        
        // Get task to update notes
        $task = $this->housekeeping->findTaskById($taskId);

        // Create maintenance request
        $maintenanceId = $this->maintenance->create([
            'room_id' => $roomId,
            'title' => $title,
            'description' => $description,
            'category' => $category,
            'priority' => $priority,
            'status' => 'pending',
            'requested_by' => $staffId,
            'reported_by_housekeeping' => true,
            'housekeeping_task_id' => $taskId,
            'photos' => is_array($photos) ? $photos : json_decode($photos ?? '[]', true),
        ]);

        // Update room status
        $this->housekeeping->updateRoomStatus($roomId, 'needs_maintenance', $staffId, 'Maintenance issue reported', null, $taskId);

        // Update task status
        if ($task) {
            $currentNotes = $task['notes'] ?? '';
            $this->housekeeping->updateTask($taskId, [
                'notes' => $currentNotes . "\n\nMaintenance reported: " . $title,
            ]);
        }

        // Notify maintenance team
        $room = $this->rooms->find($roomId);
        $roomName = $room ? ($room['display_name'] ?? $room['room_number'] ?? 'Room') : 'Room';
        
        $this->notifications->notifyRole('maintenance', 'Maintenance issue reported by housekeeping', sprintf(
            '%s reported in %s: %s',
            $title,
            $roomName,
            $description
        ), ['maintenance_id' => $maintenanceId, 'room_id' => $roomId]);

        header('Location: ' . base_url('staff/dashboard/housekeeping?success=Maintenance%20reported'));
    }

    public function setDND(Request $request): void
    {
        Auth::requireRoles(['housekeeping', 'operation_manager', 'admin', 'director', 'receptionist', 'front_desk']);
        
        $roomId = (int)$request->input('room_id');
        $isActive = $request->input('dnd') === '1';
        $reservationId = $request->input('reservation_id') ? (int)$request->input('reservation_id') : null;
        $reason = $request->input('reason');
        
        $user = Auth::user();
        $staffId = $user['id'] ?? null;

        $this->housekeeping->setDNDStatus($roomId, $isActive, $reservationId, $staffId, $reason);

        $message = $isActive ? 'DND activated' : 'DND deactivated';
        header('Location: ' . base_url('staff/dashboard/housekeeping?success=' . urlencode($message)));
    }

    public function viewRoom(Request $request): void
    {
        Auth::requireRoles(['housekeeping', 'operation_manager', 'admin', 'director', 'receptionist', 'ground']);
        
        $roomId = (int)$request->input('room_id');
        
        $room = $this->rooms->find($roomId);
        if (!$room) {
            header('Location: ' . base_url('staff/dashboard/housekeeping?error=Room%20not%20found'));
            return;
        }

        $statusHistory = $this->housekeeping->getRoomStatusHistory($roomId, 50);
        $tasks = $this->housekeeping->getTasksForHousekeeper(null, null, 100);
        $tasks = array_filter($tasks, function($task) use ($roomId) {
            return (int)$task['room_id'] === $roomId;
        });

        $this->view('dashboard/housekeeping/room-detail', [
            'room' => $room,
            'statusHistory' => $statusHistory,
            'tasks' => $tasks,
            'pageTitle' => 'Room Details | ' . settings('branding.name', 'Hotela'),
        ]);
    }

    public function updateGuestRequest(Request $request): void
    {
        $requestId = (int)$request->input('request_id');
        $status = $request->input('status');
        $assignedTo = $request->input('assigned_to') ? (int)$request->input('assigned_to') : null;

        $guestRequest = $this->guestRequests->findById($requestId);
        if (!$guestRequest) {
            header('Location: ' . base_url('staff/dashboard/housekeeping?error=Request%20not%20found'));
            return;
        }

        $this->guestRequests->updateStatus($requestId, $status, $assignedTo);

        // Create housekeeping task if assigned
        if ($status === 'assigned' && $assignedTo) {
            $taskId = $this->housekeeping->createTask([
                'room_id' => (int)$guestRequest['room_id'],
                'task_type' => 'guest_request',
                'priority' => $guestRequest['priority'] ?? 'normal',
                'scheduled_date' => date('Y-m-d'),
                'assigned_to' => $assignedTo,
                'notes' => sprintf('Guest request: %s - %s', $guestRequest['request_type'], $guestRequest['request_details'] ?? ''),
                'created_by' => Auth::user()['id'] ?? null,
            ]);
        }

        header('Location: ' . base_url('staff/dashboard/housekeeping?success=Request%20updated'));
    }
}

