<?php

return [
    "CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reservation_id INT NULL,
        guest_name VARCHAR(255) NOT NULL,
        guest_email VARCHAR(255) NOT NULL,
        guest_phone VARCHAR(50) NULL,
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        title VARCHAR(255) NULL,
        comment TEXT NULL,
        category ENUM('room', 'service', 'food', 'overall') DEFAULT 'overall',
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        helpful_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_reservation (reservation_id),
        INDEX idx_status (status),
        INDEX idx_rating (rating),
        INDEX idx_category (category),
        FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];
