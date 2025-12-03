<?php

return [
    "CREATE TABLE IF NOT EXISTS guest_accounts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        guest_email VARCHAR(150) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        guest_name VARCHAR(150) NULL,
        guest_phone VARCHAR(50) NULL,
        last_login_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_guest_email (guest_email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

