<?php

require __DIR__ . '/../bootstrap/app.php';

use App\Repositories\SystemAdminRepository;

$repo = new SystemAdminRepository();

// Check if system admin already exists
$existing = $repo->findByUsername('sysadmin');
if ($existing) {
    echo "System admin already exists.\n";
    exit(0);
}

// Create default system admin
// In production, these should be set via environment variables
$username = $_ENV['SYSTEM_ADMIN_USERNAME'] ?? 'sysadmin';
$email = $_ENV['SYSTEM_ADMIN_EMAIL'] ?? 'admin@hotela.local';
$password = $_ENV['SYSTEM_ADMIN_PASSWORD'] ?? 'ChangeMe123!';

echo "Creating system admin account...\n";
echo "Username: $username\n";
echo "Email: $email\n";
if ($password === 'ChangeMe123!') {
    echo "WARNING: Using default password. Please change it immediately!\n";
}

$adminId = $repo->create([
    'username' => $username,
    'email' => $email,
    'password' => $password,
    'is_active' => 1
]);

echo "✓ System admin created successfully (ID: $adminId)\n";
echo "Please change the default password after first login.\n";

