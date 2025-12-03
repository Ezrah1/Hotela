<?php

return [
    "CREATE TABLE IF NOT EXISTS audit_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NULL,
        user_id INT NULL,
        user_name VARCHAR(150) NULL,
        role_key VARCHAR(50) NULL,
        action VARCHAR(100) NOT NULL,
        entity_type VARCHAR(50) NULL,
        entity_id INT NULL,
        description TEXT NULL,
        old_values JSON NULL,
        new_values JSON NULL,
        ip_address VARCHAR(45) NULL,
        user_agent TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_role_key (role_key),
        INDEX idx_action (action),
        INDEX idx_entity (entity_type, entity_id),
        INDEX idx_created_at (created_at),
        INDEX idx_tenant_id (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

