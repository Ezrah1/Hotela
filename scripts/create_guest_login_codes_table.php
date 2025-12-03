<?php

declare(strict_types=1);

$app = require __DIR__ . '/../bootstrap/app.php';

use App\Core\Database;

$pdo = Database::connection();

$sql = "CREATE TABLE IF NOT EXISTS guest_login_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    code VARCHAR(10) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_code (code),
    INDEX idx_expires (expires_at),
    INDEX idx_used (used_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

try {
    $pdo->exec($sql);
    echo "Table 'guest_login_codes' created successfully!\n";
} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'already exists')) {
        echo "Table 'guest_login_codes' already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}

