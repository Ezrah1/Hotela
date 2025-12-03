<?php

return [
    "CREATE TABLE IF NOT EXISTS gallery_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT NULL,
        image_url VARCHAR(500) NOT NULL,
        display_order INT DEFAULT 0,
        status ENUM('published','draft') DEFAULT 'published',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_display_order (display_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

