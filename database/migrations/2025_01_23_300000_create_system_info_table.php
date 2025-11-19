<?php

return [
    "CREATE TABLE IF NOT EXISTS system_info (
        id INT PRIMARY KEY AUTO_INCREMENT,
        info_key VARCHAR(255) NOT NULL UNIQUE,
        info_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_info_key (info_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    // Insert default system info
    "INSERT INTO system_info (info_key, info_value) VALUES
        ('app_version', '1.0.0'),
        ('last_update_check', NOW()),
        ('last_update_applied', NULL),
        ('update_server_url', 'https://updates.hotela.com/api/check'),
        ('update_channel', 'stable')
    ON DUPLICATE KEY UPDATE info_key = info_key;",
];

