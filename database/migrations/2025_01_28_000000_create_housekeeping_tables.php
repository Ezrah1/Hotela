<?php

return [
    // Update room status enum to include all housekeeping statuses
    "ALTER TABLE rooms MODIFY COLUMN status ENUM(
        'available',
        'occupied',
        'maintenance',
        'blocked',
        'needs_cleaning',
        'dirty',
        'clean',
        'in_progress',
        'do_not_disturb',
        'needs_maintenance',
        'inspected'
    ) DEFAULT 'available';",

    // Housekeeping tasks table
    "CREATE TABLE IF NOT EXISTS housekeeping_tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NULL,
        room_id INT NOT NULL,
        assigned_to INT NULL,
        task_type ENUM('cleaning','maintenance_report','deep_cleaning','inspection','guest_request') DEFAULT 'cleaning',
        status ENUM('pending','in_progress','completed','inspected','approved','rejected') DEFAULT 'pending',
        priority ENUM('low','normal','high','urgent') DEFAULT 'normal',
        scheduled_date DATE NULL,
        started_at TIMESTAMP NULL,
        completed_at TIMESTAMP NULL,
        inspected_at TIMESTAMP NULL,
        inspected_by INT NULL,
        notes TEXT NULL,
        photos JSON NULL,
        inventory_used JSON NULL,
        created_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_housekeeping_tasks_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
        CONSTRAINT fk_housekeeping_tasks_assigned FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
        CONSTRAINT fk_housekeeping_tasks_inspector FOREIGN KEY (inspected_by) REFERENCES users(id) ON DELETE SET NULL,
        CONSTRAINT fk_housekeeping_tasks_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // Room status logs table
    "CREATE TABLE IF NOT EXISTS room_status_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NULL,
        room_id INT NOT NULL,
        previous_status VARCHAR(50) NULL,
        new_status VARCHAR(50) NOT NULL,
        changed_by INT NULL,
        reason VARCHAR(255) NULL,
        notes TEXT NULL,
        related_reservation_id INT NULL,
        related_task_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_room_status_logs_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
        CONSTRAINT fk_room_status_logs_changed_by FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,
        CONSTRAINT fk_room_status_logs_reservation FOREIGN KEY (related_reservation_id) REFERENCES reservations(id) ON DELETE SET NULL,
        CONSTRAINT fk_room_status_logs_task FOREIGN KEY (related_task_id) REFERENCES housekeeping_tasks(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // Guest requests table (for room service requests from guests)
    "CREATE TABLE IF NOT EXISTS guest_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NULL,
        reservation_id INT NOT NULL,
        room_id INT NOT NULL,
        request_type ENUM('cleaning','extra_towels','extra_toiletries','extra_items','maintenance','other') DEFAULT 'cleaning',
        status ENUM('pending','assigned','in_progress','completed','cancelled') DEFAULT 'pending',
        priority ENUM('low','normal','high','urgent') DEFAULT 'normal',
        guest_name VARCHAR(150) NOT NULL,
        guest_phone VARCHAR(50) NULL,
        guest_email VARCHAR(150) NULL,
        request_details TEXT NULL,
        assigned_to INT NULL,
        assigned_at TIMESTAMP NULL,
        started_at TIMESTAMP NULL,
        completed_at TIMESTAMP NULL,
        notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_guest_requests_reservation FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
        CONSTRAINT fk_guest_requests_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
        CONSTRAINT fk_guest_requests_assigned FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // Do Not Disturb (DND) status tracking
    "CREATE TABLE IF NOT EXISTS room_dnd_status (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NULL,
        room_id INT NOT NULL,
        reservation_id INT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        activated_by INT NULL,
        activated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        deactivated_at TIMESTAMP NULL,
        reason TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_room_dnd_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
        CONSTRAINT fk_room_dnd_reservation FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
        CONSTRAINT fk_room_dnd_activated_by FOREIGN KEY (activated_by) REFERENCES users(id) ON DELETE SET NULL,
        UNIQUE KEY unique_active_dnd_room_per_room (room_id, is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // Maintenance reports from housekeeping
    "ALTER TABLE maintenance_requests ADD COLUMN IF NOT EXISTS reported_by_housekeeping BOOLEAN DEFAULT FALSE;",
    "ALTER TABLE maintenance_requests ADD COLUMN IF NOT EXISTS housekeeping_task_id INT NULL;",
    "ALTER TABLE maintenance_requests ADD CONSTRAINT fk_maintenance_housekeeping_task FOREIGN KEY (housekeeping_task_id) REFERENCES housekeeping_tasks(id) ON DELETE SET NULL;",

    // Indexes for performance
    "CREATE INDEX IF NOT EXISTS idx_housekeeping_tasks_room ON housekeeping_tasks(room_id);",
    "CREATE INDEX IF NOT EXISTS idx_housekeeping_tasks_status ON housekeeping_tasks(status);",
    "CREATE INDEX IF NOT EXISTS idx_housekeeping_tasks_assigned ON housekeeping_tasks(assigned_to);",
    "CREATE INDEX IF NOT EXISTS idx_housekeeping_tasks_scheduled ON housekeeping_tasks(scheduled_date);",
    "CREATE INDEX IF NOT EXISTS idx_room_status_logs_room ON room_status_logs(room_id);",
    "CREATE INDEX IF NOT EXISTS idx_guest_requests_room ON guest_requests(room_id);",
    "CREATE INDEX IF NOT EXISTS idx_guest_requests_status ON guest_requests(status);",
    "CREATE INDEX IF NOT EXISTS idx_room_dnd_room ON room_dnd_status(room_id);",
    "CREATE INDEX IF NOT EXISTS idx_room_dnd_active ON room_dnd_status(is_active);"
];

