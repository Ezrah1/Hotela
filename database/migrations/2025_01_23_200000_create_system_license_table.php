<?php

return [
    "CREATE TABLE IF NOT EXISTS system_license (
        id INT PRIMARY KEY AUTO_INCREMENT,
        license_key VARCHAR(255) NOT NULL UNIQUE,
        hardware_fingerprint VARCHAR(255),
        plan_type ENUM('monthly', 'yearly', 'lifetime') NOT NULL DEFAULT 'monthly',
        status ENUM('active', 'expired', 'revoked', 'trial') NOT NULL DEFAULT 'trial',
        expires_at DATETIME,
        last_verified_at DATETIME,
        verification_url VARCHAR(500),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_license_key (license_key),
        INDEX idx_status (status),
        INDEX idx_expires_at (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    // Insert default trial license (using CONCAT for MySQL compatibility)
    "INSERT INTO system_license (license_key, plan_type, status, expires_at, notes) VALUES
        (CONCAT('TRIAL-', UPPER(SUBSTRING(MD5(RAND()), 1, 12))), 'monthly', 'trial', DATE_ADD(NOW(), INTERVAL 30 DAY), 'Initial trial license')
    ON DUPLICATE KEY UPDATE license_key = license_key;",
];

