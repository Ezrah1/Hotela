<?php

declare(strict_types=1);

$app = require __DIR__ . '/../bootstrap/app.php';

use App\Core\Database;

$pdo = Database::connection();

$sql = "CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NULL,
    guest_name VARCHAR(255) NOT NULL,
    guest_email VARCHAR(255) NOT NULL,
    guest_phone VARCHAR(50) NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(255) NULL,
    comment TEXT NULL,
    category VARCHAR(50) DEFAULT 'overall',
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_reservation (reservation_id),
    INDEX idx_guest_email (guest_email),
    INDEX idx_status (status),
    INDEX idx_rating (rating),
    INDEX idx_category (category),
    CONSTRAINT fk_reviews_reservation FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

try {
    $pdo->exec($sql);
    echo "Table 'reviews' created successfully!\n";
} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'already exists')) {
        echo "Table 'reviews' already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}

