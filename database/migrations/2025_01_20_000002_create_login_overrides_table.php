<?php

return [
    "CREATE TABLE IF NOT EXISTS login_overrides (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        approver_id INT NOT NULL,
        reason TEXT NULL,
        expires_at DATETIME NOT NULL,
        used_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (user_id),
        INDEX idx_expires (expires_at),
        INDEX idx_approver (approver_id),
        CONSTRAINT fk_login_overrides_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_login_overrides_approver FOREIGN KEY (approver_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

