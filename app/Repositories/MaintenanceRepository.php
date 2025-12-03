<?php

namespace App\Repositories;

class MaintenanceRepository
{
    protected \PDO $db;

    public function __construct(?\PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    public function all(?string $status = null, ?int $roomId = null, ?int $assignedTo = null, int $limit = 100): array
    {
        $params = [];
        $conditions = [];

        if ($status) {
            $conditions[] = 'mr.status = :status';
            $params['status'] = $status;
        }

        if ($roomId) {
            $conditions[] = 'mr.room_id = :room_id';
            $params['room_id'] = $roomId;
        }

        if ($assignedTo) {
            $conditions[] = 'mr.assigned_to = :assigned_to';
            $params['assigned_to'] = $assignedTo;
        }

        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $stmt = $this->db->prepare("
            SELECT 
                mr.*,
                r.room_number,
                r.display_name as room_name,
                u1.name as requested_by_name,
                u1.role_key as requester_role_key,
                u2.name as assigned_to_name,
                s.name as supplier_name,
                u3.name as approved_by_name,
                u4.name as verified_by_name
            FROM maintenance_requests mr
            LEFT JOIN rooms r ON r.id = mr.room_id
            LEFT JOIN users u1 ON u1.id = mr.requested_by
            LEFT JOIN users u2 ON u2.id = mr.assigned_to
            LEFT JOIN suppliers s ON s.id = mr.supplier_id
            LEFT JOIN users u3 ON u3.id = mr.approved_by
            LEFT JOIN users u4 ON u4.id = mr.verified_by
            {$whereClause}
            ORDER BY 
                CASE mr.priority
                    WHEN 'urgent' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'medium' THEN 3
                    WHEN 'low' THEN 4
                END ASC,
                mr.created_at DESC
            LIMIT :limit
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('
            SELECT 
                mr.*,
                r.room_number,
                r.display_name as room_name,
                u1.name as requested_by_name,
                u1.email as requested_by_email,
                u1.role_key as requester_role_key,
                u2.name as assigned_to_name,
                u2.email as assigned_to_email,
                s.name as supplier_name,
                s.contact_person as supplier_contact,
                s.phone as supplier_phone,
                s.email as supplier_email,
                u3.name as approved_by_name,
                u4.name as verified_by_name
            FROM maintenance_requests mr
            LEFT JOIN rooms r ON r.id = mr.room_id
            LEFT JOIN users u1 ON u1.id = mr.requested_by
            LEFT JOIN users u2 ON u2.id = mr.assigned_to
            LEFT JOIN suppliers s ON s.id = mr.supplier_id
            LEFT JOIN users u3 ON u3.id = mr.approved_by
            LEFT JOIN users u4 ON u4.id = mr.verified_by
            WHERE mr.id = :id
        ');

        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $reference = $this->generateReference();

        // Parse photos JSON if provided
        $photosJson = null;
        if (isset($data['photos']) && is_array($data['photos'])) {
            $photosJson = json_encode($data['photos']);
        } elseif (isset($data['photos']) && is_string($data['photos'])) {
            $photosJson = $data['photos'];
        }

        $stmt = $this->db->prepare('
            INSERT INTO maintenance_requests (
                reference, room_id, title, description, priority,
                status, requested_by, assigned_to, notes, photos,
                materials_needed, recommended_suppliers
            ) VALUES (
                :reference, :room_id, :title, :description, :priority,
                :status, :requested_by, :assigned_to, :notes, :photos,
                :materials_needed, :recommended_suppliers
            )
        ');

        $stmt->execute([
            'reference' => $reference,
            'room_id' => $data['room_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'priority' => $data['priority'] ?? 'medium',
            'status' => $data['status'] ?? 'pending',
            'requested_by' => $data['requested_by'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? null,
            'notes' => $data['notes'] ?? null,
            'photos' => $photosJson,
            'materials_needed' => $data['materials_needed'] ?? null,
            'recommended_suppliers' => $data['recommended_suppliers'] ?? null,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $allowedFields = [
            'title', 'description', 'priority', 'status', 'assigned_to', 'notes', 
            'completed_at', 'cost_estimate', 'supplier_id', 'ops_notes', 'finance_notes',
            'approved_by', 'approved_at', 'work_order_reference', 'photos', 
            'materials_needed', 'recommended_suppliers', 'verified_by', 'verified_at'
        ];
        $updates = [];
        $params = ['id' => $id];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                if ($field === 'photos' && is_array($data[$field])) {
                    $updates[] = "{$field} = :{$field}";
                    $params[$field] = json_encode($data[$field]);
                } else {
                    $updates[] = "{$field} = :{$field}";
                    $params[$field] = $data[$field];
                }
            }
        }

        if (empty($updates)) {
            return false;
        }

        $sql = 'UPDATE maintenance_requests SET ' . implode(', ', $updates) . ', updated_at = NOW() WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM maintenance_requests WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    protected function generateReference(): string
    {
        $prefix = 'MNT';
        $date = date('Ymd');
        $random = strtoupper(substr(uniqid(), -4));
        return $prefix . '-' . $date . '-' . $random;
    }

    public function getStatistics(): array
    {
        try {
            $stmt = $this->db->prepare('
                SELECT 
                    status,
                    COUNT(*) as count
                FROM maintenance_requests
                GROUP BY status
            ');
            $stmt->execute();

            $stats = [
                'pending' => 0,
                'ops_review' => 0,
                'finance_review' => 0,
                'approved' => 0,
                'assigned' => 0,
                'in_progress' => 0,
                'completed' => 0,
                'verified' => 0,
                'cancelled' => 0,
                'total' => 0,
            ];

            foreach ($stmt->fetchAll() as $row) {
                $status = strtolower($row['status']);
                if (isset($stats[$status])) {
                    $stats[$status] = (int)$row['count'];
                }
                $stats['total'] += (int)$row['count'];
            }

            return $stats;
        } catch (\PDOException $e) {
            return [
                'pending' => 0,
                'ops_review' => 0,
                'finance_review' => 0,
                'approved' => 0,
                'assigned' => 0,
                'in_progress' => 0,
                'completed' => 0,
                'verified' => 0,
                'cancelled' => 0,
                'total' => 0,
            ];
        }
    }

    public function getPendingOpsReview(): array
    {
        return $this->all('pending', null, null, 100);
    }

    public function getPendingFinanceReview(): array
    {
        return $this->all('ops_review', null, null, 100);
    }

    public function generateWorkOrderReference(): string
    {
        $prefix = 'WO';
        $date = date('Ymd');
        $random = strtoupper(substr(uniqid(), -4));
        return $prefix . '-' . $date . '-' . $random;
    }

    /**
     * Check for duplicate maintenance requests in the same department
     * Returns existing request if found, null otherwise
     */
    public function findDuplicate(int $userId, string $title, ?string $description = null, ?int $roomId = null): ?array
    {
        // Get user's role to determine department
        $userStmt = $this->db->prepare('SELECT role_key FROM users WHERE id = :id');
        $userStmt->execute(['id' => $userId]);
        $user = $userStmt->fetch();
        
        if (!$user) {
            return null;
        }

        $userRoleKey = $user['role_key'];
        $department = \App\Support\DepartmentHelper::getDepartmentFromRole($userRoleKey);

        if (!$department) {
            return null;
        }

        // Get all role keys for this department
        $departmentRoleKeys = \App\Support\DepartmentHelper::getRolesForDepartment($department);

        if (empty($departmentRoleKeys)) {
            return null;
        }

        // Find open/pending requests in the same department with similar title/description
        $placeholders = [];
        $params = [];
        foreach ($departmentRoleKeys as $index => $roleKey) {
            $param = 'role_key_' . $index;
            $params[$param] = $roleKey;
            $placeholders[] = ":{$param}";
        }

        // Statuses that indicate an active/open request
        $openStatuses = ['pending', 'ops_review', 'finance_review', 'approved', 'assigned', 'in_progress'];
        $statusPlaceholders = [];
        foreach ($openStatuses as $index => $status) {
            $param = 'status_' . $index;
            $params[$param] = $status;
            $statusPlaceholders[] = ":{$param}";
        }

        $params['title'] = '%' . $title . '%';
        if ($description) {
            $params['description'] = '%' . $description . '%';
        }
        if ($roomId) {
            $params['room_id'] = $roomId;
        }

        $sql = '
            SELECT mr.*, u.role_key AS requester_role_key
            FROM maintenance_requests mr
            INNER JOIN users u ON u.id = mr.requested_by
            WHERE u.role_key IN (' . implode(', ', $placeholders) . ')
            AND mr.status IN (' . implode(', ', $statusPlaceholders) . ')
            AND (mr.title LIKE :title OR mr.description LIKE :title)';
        
        if ($description) {
            $sql .= ' AND (mr.title LIKE :description OR mr.description LIKE :description)';
        }
        if ($roomId) {
            $sql .= ' AND mr.room_id = :room_id';
        }
        
        $sql .= ' ORDER BY mr.created_at DESC LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $duplicate = $stmt->fetch();

        return $duplicate ?: null;
    }
}

