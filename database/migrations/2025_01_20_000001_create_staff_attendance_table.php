<?php

return [
    "CREATE TABLE IF NOT EXISTS staff_attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        check_in_time DATETIME NOT NULL,
        check_out_time DATETIME NULL,
        checked_in BOOLEAN DEFAULT TRUE,
        checked_out BOOLEAN DEFAULT FALSE,
        date DATE NOT NULL,
        notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_date (user_id, date),
        INDEX idx_date (date),
        INDEX idx_checked_in (checked_in),
        CONSTRAINT fk_staff_attendance_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

