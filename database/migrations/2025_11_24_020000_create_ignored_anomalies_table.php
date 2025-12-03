<?php

return [
    "CREATE TABLE IF NOT EXISTS ignored_anomalies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        attendance_log_id INT NOT NULL,
        ignored_by INT NOT NULL COMMENT 'User who ignored this anomaly',
        reason TEXT NULL COMMENT 'Optional reason for ignoring',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_ignored_anomaly (attendance_log_id),
        INDEX idx_ignored_by (ignored_by),
        FOREIGN KEY (attendance_log_id) REFERENCES attendance_logs(id) ON DELETE CASCADE,
        FOREIGN KEY (ignored_by) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

