<?php

return [
    // Create user_roles pivot table for multi-role assignment
    "CREATE TABLE IF NOT EXISTS user_roles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        role_key VARCHAR(50) NOT NULL,
        is_primary TINYINT(1) DEFAULT 0 COMMENT 'Primary role (for backward compatibility)',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_role (user_id, role_key),
        INDEX idx_user_id (user_id),
        INDEX idx_role_key (role_key),
        INDEX idx_primary (is_primary),
        CONSTRAINT fk_user_roles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_user_roles_role FOREIGN KEY (role_key) REFERENCES roles(`key`) ON DELETE RESTRICT ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    // Migrate existing role_key from users table to user_roles
    "INSERT INTO user_roles (user_id, role_key, is_primary)
     SELECT id, role_key, 1
     FROM users
     WHERE role_key IS NOT NULL
     AND NOT EXISTS (
         SELECT 1 FROM user_roles ur WHERE ur.user_id = users.id AND ur.role_key = users.role_key
     )",
];

