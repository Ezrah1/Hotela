<?php

namespace App\Services\Workflow;

use App\Repositories\WorkflowTaskRepository;
use App\Repositories\AttendanceRepository;
use App\Repositories\DutyRosterRepository;
use App\Services\Notifications\NotificationService;

class WorkflowService
{
    protected WorkflowTaskRepository $taskRepo;
    protected AttendanceRepository $attendanceRepo;
    protected DutyRosterRepository $rosterRepo;
    protected NotificationService $notifications;

    public function __construct()
    {
        $this->taskRepo = new WorkflowTaskRepository();
        $this->attendanceRepo = new AttendanceRepository();
        $this->rosterRepo = new DutyRosterRepository();
        $this->notifications = new NotificationService();
    }

    /**
     * Create and auto-assign a task based on workflow rules
     */
    public function createTask(array $taskData): int
    {
        // Determine department and assignee based on task type
        $assignee = $this->findAvailableAssignee($taskData);
        
        if ($assignee) {
            $taskData['assigned_to'] = $assignee;
            $taskData['status'] = 'assigned';
        } else {
            $taskData['status'] = 'pending';
            // Escalate if no one available
            $this->notifications->notifyRole('operation_manager', 'Task Escalation',
                "Task '{$taskData['title']}' could not be auto-assigned. No available staff.", $taskData);
        }
        
        $taskId = $this->taskRepo->create($taskData);
        
        // Notify assignee if assigned
        if ($assignee) {
            $this->notifyAssignee($taskId, $assignee, $taskData);
        }
        
        return $taskId;
    }

    /**
     * Find available staff member for task assignment
     */
    protected function findAvailableAssignee(array $taskData): ?int
    {
        $department = $taskData['department'] ?? null;
        $roleKey = $taskData['role_key'] ?? null;
        
        // Get present staff
        $presentStaff = $this->attendanceRepo->getPresentStaff();
        
        // Filter by department/role
        $availableStaff = array_filter($presentStaff, function($staff) use ($department, $roleKey) {
            if ($department && $staff['role_key'] !== $department) {
                return false;
            }
            if ($roleKey && $staff['role_key'] !== $roleKey) {
                return false;
            }
            
            // Check if staff has assigned shift for current time
            $today = date('Y-m-d');
            $now = date('H:i:s');
            return $this->rosterRepo->hasAssignedShift($staff['id'], $today, $now);
        });
        
        if (empty($availableStaff)) {
            return null;
        }
        
        // Return first available staff member
        return (int)$availableStaff[0]['id'];
    }

    /**
     * Notify assignee about new task
     */
    protected function notifyAssignee(int $taskId, int $userId, array $taskData): void
    {
        $message = "New task assigned: {$taskData['title']}";
        if (!empty($taskData['description'])) {
            $message .= "\n{$taskData['description']}";
        }
        
        $this->notifications->notifyUser($userId, 'Task Assigned', $message, [
            'task_id' => $taskId,
            'task_type' => $taskData['task_type'],
            'priority' => $taskData['priority'] ?? 'normal',
        ]);
    }

    /**
     * Auto-assign housekeeping task when guest checks out
     */
    public function assignHousekeepingTask(int $roomId, string $roomNumber, int $bookingId): int
    {
        return $this->createTask([
            'task_type' => 'housekeeping',
            'title' => "Clean Room {$roomNumber}",
            'description' => "Guest has checked out. Room requires cleaning and preparation for next guest.",
            'priority' => 'high',
            'department' => 'housekeeping',
            'location' => $roomNumber,
            'related_id' => $roomId,
            'related_type' => 'room',
            'workflow_step' => 1,
            'workflow_data' => ['booking_id' => $bookingId],
        ]);
    }

    /**
     * Auto-create inventory requisition when stock is low
     */
    public function createInventoryRequisition(int $itemId, string $itemName, int $currentStock, int $reorderPoint): int
    {
        return $this->createTask([
            'task_type' => 'inventory',
            'title' => "Requisition: {$itemName}",
            'description' => "Stock level ({$currentStock}) is below reorder point ({$reorderPoint}).",
            'priority' => 'normal',
            'department' => 'operation_manager',
            'location' => $itemName,
            'related_id' => $itemId,
            'related_type' => 'inventory_item',
            'workflow_step' => 1, // Requires Operations Manager approval
            'workflow_data' => [
                'item_id' => $itemId,
                'current_stock' => $currentStock,
                'reorder_point' => $reorderPoint,
            ],
        ]);
    }

    /**
     * Auto-route maintenance request
     */
    public function createMaintenanceRequest(array $requestData): int
    {
        // Step 1: Operations verification
        $taskId = $this->createTask([
            'task_type' => 'maintenance',
            'title' => $requestData['title'] ?? 'Maintenance Request',
            'description' => $requestData['description'] ?? '',
            'priority' => $requestData['priority'] ?? 'normal',
            'department' => 'operation_manager',
            'location' => $requestData['location'] ?? '',
            'related_id' => $requestData['related_id'] ?? null,
            'related_type' => $requestData['related_type'] ?? 'maintenance',
            'workflow_step' => 1, // Operations verification
            'workflow_data' => $requestData,
        ]);
        
        return $taskId;
    }

    /**
     * Advance workflow to next step
     */
    public function approveStep(int $taskId, int $approvedBy, ?string $notes = null): bool
    {
        $task = $this->taskRepo->find($taskId);
        if (!$task) {
            return false;
        }
        
        $currentStep = (int)$task['workflow_step'];
        $nextStep = $currentStep + 1;
        
        // Determine next step based on task type
        $maxSteps = $this->getMaxStepsForTaskType($task['task_type']);
        
        if ($nextStep > $maxSteps) {
            // Workflow complete - assign for execution
            $this->taskRepo->updateStatus($taskId, 'assigned', null);
        } else {
            // Move to next approval step
            $this->taskRepo->advanceStep($taskId, $nextStep);
            
            // Notify next approver
            $this->notifyNextApprover($taskId, $nextStep, $task);
        }
        
        return true;
    }

    /**
     * Get maximum workflow steps for task type
     */
    protected function getMaxStepsForTaskType(string $taskType): int
    {
        $workflows = [
            'housekeeping' => 1, // Direct assignment
            'inventory' => 2, // Operations → Finance
            'maintenance' => 3, // Operations → Finance/Director → Execution
            'guest_service' => 2, // Assignment → Completion
        ];
        
        return $workflows[$taskType] ?? 1;
    }

    /**
     * Notify next approver in workflow
     */
    protected function notifyNextApprover(int $taskId, int $step, array $task): void
    {
        $roleMap = [
            1 => 'operation_manager',
            2 => 'finance_manager',
            3 => 'director',
        ];
        
        $role = $roleMap[$step] ?? null;
        if ($role) {
            $this->notifications->notifyRole($role, 'Workflow Approval Required',
                "Task '{$task['title']}' requires your approval.", ['task_id' => $taskId]);
        }
    }
}

