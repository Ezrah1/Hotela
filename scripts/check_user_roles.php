<?php

require __DIR__ . '/../bootstrap/app.php';

use App\Core\Database;

$pdo = Database::connection();

echo "Checking user roles...\n\n";

// Check for Ezra
$stmt = $pdo->prepare("SELECT id, name, email, role_key FROM users WHERE email LIKE ? OR name LIKE ?");
$stmt->execute(['%ezra%', '%ezra%']);
$ezraUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Ezra users:\n";
print_r($ezraUsers);

// Check for Purity
$stmt = $pdo->prepare("SELECT id, name, email, role_key FROM users WHERE email LIKE ? OR name LIKE ?");
$stmt->execute(['%purity%', '%purity%']);
$purityUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "\nPurity users:\n";
print_r($purityUsers);

// Check all directors
$stmt = $pdo->query("SELECT id, name, email, role_key FROM users WHERE role_key = 'director'");
$directors = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "\nAll directors:\n";
print_r($directors);

// Check all admins (should be none after migration)
$stmt = $pdo->query("SELECT id, name, email, role_key FROM users WHERE role_key = 'admin'");
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "\nAll admins (should be empty):\n";
print_r($admins);

