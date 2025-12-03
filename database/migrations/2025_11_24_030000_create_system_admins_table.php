<?php

return [
    "CREATE TABLE IF NOT EXISTS system_admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL UNIQUE,
        email VARCHAR(150) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        encrypted_credentials TEXT NULL COMMENT 'Encrypted backup credentials',
        is_active TINYINT(1) DEFAULT 1,
        last_login_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    "CREATE TABLE IF NOT EXISTS license_activations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        installation_id VARCHAR(255) NOT NULL UNIQUE COMMENT 'Unique installation identifier',
        director_email VARCHAR(150) NOT NULL,
        director_user_id INT NULL COMMENT 'Reference to users table',
        license_key VARCHAR(255) NOT NULL,
        signed_token TEXT NOT NULL COMMENT 'Signed token for integrity verification',
        activated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NULL,
        status ENUM('active', 'expired', 'suspended') DEFAULT 'active',
        last_verified_at TIMESTAMP NULL,
        INDEX idx_installation (installation_id),
        INDEX idx_director_email (director_email),
        INDEX idx_status (status),
        CONSTRAINT fk_license_director FOREIGN KEY (director_user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    "CREATE TABLE IF NOT EXISTS system_audit_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        system_admin_id INT NOT NULL,
        action VARCHAR(100) NOT NULL,
        entity_type VARCHAR(50) NULL,
        entity_id INT NULL,
        details JSON NULL,
        ip_address VARCHAR(45) NULL,
        user_agent TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_admin (system_admin_id),
        INDEX idx_action (action),
        INDEX idx_entity (entity_type, entity_id),
        INDEX idx_created (created_at),
        CONSTRAINT fk_system_audit_admin FOREIGN KEY (system_admin_id) REFERENCES system_admins(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

