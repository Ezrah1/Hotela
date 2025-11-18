<?php

return [
    "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        role_key VARCHAR(50) NULL,
        user_id INT NULL,
        title VARCHAR(150) NOT NULL,
        message TEXT NOT NULL,
        payload JSON NULL,
        status ENUM('unread','read') DEFAULT 'unread',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        read_at TIMESTAMP NULL,
        INDEX idx_notifications_role (role_key),
        CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

