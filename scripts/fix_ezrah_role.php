<?php

declare(strict_types=1);

$app = require __DIR__ . '/../bootstrap/app.php';

use App\Core\Database;

$pdo = Database::connection();

// Fix Ezrah Kiilu's role from finance_manager to tech
$stmt = $pdo->prepare('UPDATE users SET role_key = ? WHERE email = ?');
$stmt->execute(['tech', 'ezrah.kiilu@hotela.local']);

echo "Updated Ezrah Kiilu role to: tech (Technical Admin)\n";

// Verify the change
$check = $pdo->prepare('SELECT name, email, role_key FROM users WHERE email = ?');
$check->execute(['ezrah.kiilu@hotela.local']);
$user = $check->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "Verification:\n";
    echo "  Name: {$user['name']}\n";
    echo "  Email: {$user['email']}\n";
    echo "  Role: {$user['role_key']}\n";
} else {
    echo "ERROR: User not found!\n";
}

// Check security users
echo "\nSecurity users:\n";
$securityCheck = $pdo->prepare('SELECT name, email, role_key FROM users WHERE role_key = ?');
$securityCheck->execute(['security']);
$securityUsers = $securityCheck->fetchAll(PDO::FETCH_ASSOC);

if (empty($securityUsers)) {
    echo "  No security users found.\n";
} else {
    foreach ($securityUsers as $user) {
        echo "  - {$user['name']} ({$user['email']}) - Role: {$user['role_key']}\n";
    }
}

echo "\nDone!\n";

