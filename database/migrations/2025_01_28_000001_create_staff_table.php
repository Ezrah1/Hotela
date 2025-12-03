<?php

return [
    // Staff table - extends users with additional employee information
    "CREATE TABLE IF NOT EXISTS staff (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        employee_id VARCHAR(50) NULL,
        phone VARCHAR(50) NULL,
        id_number VARCHAR(50) NULL,
        address TEXT NULL,
        department VARCHAR(100) NULL,
        basic_salary DECIMAL(12,2) DEFAULT 0,
        hire_date DATE NULL,
        status ENUM('active','inactive','terminated') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY staff_user_id_unique (user_id),
        CONSTRAINT fk_staff_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    // Create index for department filtering
    "CREATE INDEX IF NOT EXISTS idx_staff_department ON staff(department);",
    "CREATE INDEX IF NOT EXISTS idx_staff_status ON staff(status);"
];

