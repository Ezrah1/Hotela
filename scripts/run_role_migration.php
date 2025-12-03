<?php

require __DIR__ . '/../bootstrap/app.php';

use App\Core\Database;
use App\Repositories\SystemAdminRepository;
use App\Services\Email\EmailService;

$pdo = Database::connection();

echo "Starting role migration...\n\n";

// Step 1: Run system_admins table migration
echo "1. Creating system_admins table...\n";
$migration1 = require __DIR__ . '/../database/migrations/2025_11_24_030000_create_system_admins_table.php';
foreach ($migration1 as $sql) {
    try {
        $pdo->exec($sql);
        echo "   ✓ Table created\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "   ⚠ Table already exists (skipping)\n";
        } else {
            die("   ✗ Error: " . $e->getMessage() . "\n");
        }
    }
}

// Step 2: Run admin-to-director migration
echo "\n2. Migrating admin users to director role...\n";
$migration2 = require __DIR__ . '/../database/migrations/2025_11_24_040000_migrate_admin_to_director.php';
foreach ($migration2 as $sql) {
    try {
        $pdo->exec($sql);
        echo "   ✓ Migration step completed\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false || 
            strpos($e->getMessage(), 'already exists') !== false) {
            echo "   ⚠ Step already applied (skipping)\n";
        } else {
            echo "   ⚠ Warning: " . $e->getMessage() . "\n";
        }
    }
}

// Step 3: Get all users that were migrated
echo "\n3. Finding migrated users...\n";
$migratedUsers = $pdo->query("
    SELECT id, name, email, role_key 
    FROM users 
    WHERE role_key = 'director' 
    AND id IN (SELECT id FROM users WHERE role_key = 'director' AND tenant_id IS NOT NULL)
")->fetchAll(PDO::FETCH_ASSOC);

if (empty($migratedUsers)) {
    echo "   ⚠ No users found with director role\n";
} else {
    echo "   ✓ Found " . count($migratedUsers) . " director user(s)\n";
    
    // Step 4: Send notification emails (if email service is configured)
    echo "\n4. Sending notification emails to migrated users...\n";
    try {
        $emailService = new EmailService();
        foreach ($migratedUsers as $user) {
            $subject = "Your Account Role Has Been Updated";
            $message = "
                <p>Dear {$user['name']},</p>
                <p>Your account role has been updated to <strong>Director</strong>, which is now the highest role in the hotel management system.</p>
                <p>As a Director, you have full access to all hotel operations and settings.</p>
                <p>If you have any questions, please contact support.</p>
                <p>Best regards,<br>Hotela System</p>
            ";
            
            try {
                $emailService->send(
                    $user['email'],
                    $subject,
                    $message
                );
                echo "   ✓ Email sent to {$user['email']}\n";
            } catch (Exception $e) {
                echo "   ⚠ Failed to send email to {$user['email']}: " . $e->getMessage() . "\n";
            }
        }
    } catch (Exception $e) {
        echo "   ⚠ Email service not available: " . $e->getMessage() . "\n";
    }
}

// Step 5: Create system admin if it doesn't exist
echo "\n5. Creating system admin account...\n";
try {
    $sysAdminRepo = new SystemAdminRepository();
    $existing = $sysAdminRepo->findByUsername('sysadmin');
    if ($existing) {
        echo "   ⚠ System admin already exists\n";
    } else {
        $adminId = $sysAdminRepo->create([
            'username' => $_ENV['SYSTEM_ADMIN_USERNAME'] ?? 'sysadmin',
            'email' => $_ENV['SYSTEM_ADMIN_EMAIL'] ?? 'sysadmin@hotela.local',
            'password' => $_ENV['SYSTEM_ADMIN_PASSWORD'] ?? 'ChangeMe123!',
            'is_active' => 1
        ]);
        echo "   ✓ System admin created (ID: $adminId)\n";
        if (($_ENV['SYSTEM_ADMIN_PASSWORD'] ?? 'ChangeMe123!') === 'ChangeMe123!') {
            echo "   ⚠ WARNING: Using default password. Please change it immediately!\n";
        }
    }
} catch (Exception $e) {
    echo "   ⚠ Error creating system admin: " . $e->getMessage() . "\n";
}

echo "\n✓ Migration completed!\n";
echo "\nNext steps:\n";
echo "1. Update routes to use TenantAuth middleware for /staff routes\n";
echo "2. Create system admin login page at /sysadmin/login\n";
echo "3. Remove admin role from tenant UI components\n";
echo "4. Test director login and verify access\n";

