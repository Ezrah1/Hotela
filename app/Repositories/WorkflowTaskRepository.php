<?php

namespace App\Repositories;

use PDO;

class WorkflowTaskRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    /**
     * Create a new workflow task
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO workflow_tasks 
            (task_type, title, description, priority, status, assigned_to, assigned_by, 
             department, location, due_date, workflow_step, workflow_data, related_id, related_type)
            VALUES 
            (:task_type, :title, :description, :priority, :status, :assigned_to, :assigned_by,
             :department, :location, :due_date, :workflow_step, :workflow_data, :related_id, :related_type)
        ');
        $stmt->execute([
            'task_type' => $data['task_type'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'priority' => $data['priority'] ?? 'normal',
            'status' => $data['status'] ?? 'pending',
            'assigned_to' => $data['assigned_to'] ?? null,
            'assigned_by' => $data['assigned_by'] ?? null,
            'department' => $data['department'] ?? null,
            'location' => $data['location'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'workflow_step' => $data['workflow_step'] ?? 1,
            'workflow_data' => isset($data['workflow_data']) ? json_encode($data['workflow_data']) : null,
            'related_id' => $data['related_id'] ?? null,
            'related_type' => $data['related_type'] ?? null,
        ]);
        
        return (int)$this->db->lastInsertId();
    }

    /**
     * Assign task to a staff member
     */
    public function assign(int $taskId, int $assignedTo, ?int $assignedBy = null): bool
    {
        $stmt = $this->db->prepare('
            UPDATE workflow_tasks 
            SET assigned_to = :assigned_to,
                assigned_by = :assigned_by,
                status = "assigned",
                updated_at = NOW()
            WHERE id = :id
        ');
        $stmt->execute([
            'id' => $taskId,
            'assigned_to' => $assignedTo,
            'assigned_by' => $assignedBy,
        ]);
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Update task status
     */
    public function updateStatus(int $taskId, string $status, ?int $completedBy = null): bool
    {
        $updates = ['status = :status', 'updated_at = NOW()'];
        $params = ['id' => $taskId, 'status' => $status];
        
        if ($status === 'completed' && $completedBy) {
            $updates[] = 'completed_at = NOW()';
            $updates[] = 'completed_by = :completed_by';
            $params['completed_by'] = $completedBy;
        }
        
        $stmt = $this->db->prepare('
            UPDATE workflow_tasks 
            SET ' . implode(', ', $updates) . '
            WHERE id = :id
        ');
        $stmt->execute($params);
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Advance workflow step
     */
    public function advanceStep(int $taskId, int $newStep): bool
    {
        $stmt = $this->db->prepare('
            UPDATE workflow_tasks 
            SET workflow_step = :step,
                updated_at = NOW()
            WHERE id = :id
        ');
        $stmt->execute([
            'id' => $taskId,
            'step' => $newStep,
        ]);
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Get tasks for a user
     */
    public function getForUser(int $userId, ?string $status = null): array
    {
        $params = ['user_id' => $userId];
        $conditions = ['assigned_to = :user_id'];
        
        if ($status) {
            $conditions[] = 'status = :status';
            $params['status'] = $status;
        }
        
        $stmt = $this->db->prepare('
            SELECT t.*, 
                   u1.name AS assigned_to_name,
                   u2.name AS assigned_by_name,
                   u3.name AS completed_by_name
            FROM workflow_tasks t
            LEFT JOIN users u1 ON u1.id = t.assigned_to
            LEFT JOIN users u2 ON u2.id = t.assigned_by
            LEFT JOIN users u3 ON u3.id = t.completed_by
            WHERE ' . implode(' AND ', $conditions) . '
            ORDER BY t.priority DESC, t.due_date ASC, t.created_at DESC
        ');
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }

    /**
     * Get tasks by type and status
     */
    public function getByType(string $taskType, ?string $status = null): array
    {
        $params = ['task_type' => $taskType];
        $conditions = ['task_type = :task_type'];
        
        if ($status) {
            $conditions[] = 'status = :status';
            $params['status'] = $status;
        }
        
        $stmt = $this->db->prepare('
            SELECT t.*, 
                   u1.name AS assigned_to_name,
                   u2.name AS assigned_by_name
            FROM workflow_tasks t
            LEFT JOIN users u1 ON u1.id = t.assigned_to
            LEFT JOIN users u2 ON u2.id = t.assigned_by
            WHERE ' . implode(' AND ', $conditions) . '
            ORDER BY t.priority DESC, t.due_date ASC
        ');
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }

    /**
     * Reassign task to next available staff
     */
    public function reassignToAvailable(int $taskId, string $department, ?string $roleKey = null): bool
    {
        // Find available staff in department who are present
        $attendanceRepo = new \App\Repositories\AttendanceRepository();
        $presentStaff = $attendanceRepo->getPresentStaff();
        
        // Filter by department and role
        $availableStaff = array_filter($presentStaff, function($staff) use ($department, $roleKey) {
            if ($department && $staff['role_key'] !== $department) {
                return false;
            }
            if ($roleKey && $staff['role_key'] !== $roleKey) {
                return false;
            }
            return true;
        });
        
        if (empty($availableStaff)) {
            // No available staff - escalate to supervisor
            return $this->escalate($taskId, 'No available staff in department');
        }
        
        // Assign to first available staff
        $newAssignee = $availableStaff[0]['id'];
        return $this->assign($taskId, $newAssignee);
    }

    /**
     * Escalate task to supervisor
     */
    public function escalate(int $taskId, string $reason): bool
    {
        $stmt = $this->db->prepare('
            UPDATE workflow_tasks 
            SET status = "escalated",
                workflow_data = JSON_SET(COALESCE(workflow_data, "{}"), "$.escalation_reason", :reason),
                updated_at = NOW()
            WHERE id = :id
        ');
        $stmt->execute([
            'id' => $taskId,
            'reason' => $reason,
        ]);
        
        return $stmt->rowCount() > 0;
    }
}

