<?php

return [
    "CREATE TABLE IF NOT EXISTS guest_login_codes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        code VARCHAR(10) NOT NULL,
        expires_at DATETIME NOT NULL,
        used_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_code (code),
        INDEX idx_expires (expires_at),
        INDEX idx_used (used_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

