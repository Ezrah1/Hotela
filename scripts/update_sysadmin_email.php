<?php

declare(strict_types=1);

$app = require __DIR__ . '/../bootstrap/app.php';

use App\Core\Database;

$pdo = Database::connection();

$newEmail = 'ezrahefforts@gmail.com';

// Check if sysadmin exists
$stmt = $pdo->query('SELECT id, username, email FROM system_admins LIMIT 1');
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    echo "No system admin found. Creating one...\n";
    // Create sysadmin if doesn't exist
    $stmt = $pdo->prepare('
        INSERT INTO system_admins (username, email, password_hash, is_active)
        VALUES (:username, :email, :password_hash, 1)
    ');
    $stmt->execute([
        'username' => 'sysadmin',
        'email' => $newEmail,
        'password_hash' => password_hash('admin', PASSWORD_DEFAULT)
    ]);
    echo "System admin created with email: {$newEmail}\n";
} else {
    echo "Current sysadmin: ID={$admin['id']}, Username={$admin['username']}, Email={$admin['email']}\n";
    
    // Update email
    $stmt = $pdo->prepare('UPDATE system_admins SET email = :email WHERE id = :id');
    $stmt->execute([
        'id' => $admin['id'],
        'email' => $newEmail
    ]);
    
    echo "Email updated to: {$newEmail}\n";
}

echo "Done!\n";

