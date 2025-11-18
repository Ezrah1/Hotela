<?php

return [
    "CREATE TABLE IF NOT EXISTS tenants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        slug VARCHAR(120) NOT NULL UNIQUE,
        domain VARCHAR(160) NOT NULL UNIQUE,
        contact_email VARCHAR(150) NULL,
        contact_phone VARCHAR(50) NULL,
        status ENUM('active','inactive') DEFAULT 'active',
        settings JSON NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];


