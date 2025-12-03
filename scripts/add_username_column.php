<?php

declare(strict_types=1);

$app = require __DIR__ . '/../bootstrap/app.php';

use App\Core\Database;

$pdo = Database::connection();

try {
    $pdo->exec('ALTER TABLE users ADD COLUMN username VARCHAR(100) NULL AFTER email');
    echo "Added username column\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "Username column already exists\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

try {
    $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_users_username ON users(username)');
    echo "Created username index\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate key') !== false) {
        echo "Username index already exists\n";
    } else {
        echo "Error creating index: " . $e->getMessage() . "\n";
    }
}

try {
    $pdo->exec('ALTER TABLE staff ADD COLUMN IF NOT EXISTS profile_photo VARCHAR(255) NULL AFTER address');
    echo "Added profile_photo column\n";
} catch (PDOException $e) {
    echo "Error adding profile_photo: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec('ALTER TABLE staff ADD COLUMN IF NOT EXISTS emergency_contact_name VARCHAR(150) NULL');
    echo "Added emergency_contact_name column\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec('ALTER TABLE staff ADD COLUMN IF NOT EXISTS emergency_contact_phone VARCHAR(50) NULL');
    echo "Added emergency_contact_phone column\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec('ALTER TABLE staff ADD COLUMN IF NOT EXISTS emergency_contact_relation VARCHAR(50) NULL');
    echo "Added emergency_contact_relation column\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nDone!\n";

