<?php

declare(strict_types=1);

$app = require __DIR__ . '/../bootstrap/app.php';

use App\Core\Database;

$pdo = Database::connection();

// Helper function to generate email from name
function generateEmail(string $firstName, string $lastName): string {
    $first = strtolower(preg_replace('/[^a-z]/i', '', $firstName));
    $last = strtolower(preg_replace('/[^a-z]/i', '', $lastName));
    return $first . '.' . $last . '@hotela.local';
}

// Helper function to generate username from name (first.last format)
function generateUsername(string $firstName, string $lastName): string {
    $first = strtolower(preg_replace('/[^a-z]/i', '', $firstName));
    $last = strtolower(preg_replace('/[^a-z]/i', '', $lastName));
    return $first . '.' . $last;
}

// Helper function to generate phone number
function generatePhone(): string {
    return '+2547' . str_pad((string)rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
}

// Helper function to generate ID number
function generateIdNumber(): string {
    return str_pad((string)rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
}

// Get tenant ID - create tenant if it doesn't exist
$tenantDomain = env('TENANT_DOMAIN', 'hotela.local');
try {
    $tenantStmt = $pdo->prepare('SELECT id FROM tenants WHERE domain = ? LIMIT 1');
    $tenantStmt->execute([$tenantDomain]);
    $tenantId = (int)($tenantStmt->fetchColumn() ?: 0);
    
    if (!$tenantId) {
        // Create default tenant if it doesn't exist
        $createTenantStmt = $pdo->prepare('INSERT INTO tenants (name, domain, status) VALUES (?, ?, ?)');
        $createTenantStmt->execute(['Demo Hotel', $tenantDomain, 'active']);
        $tenantId = (int)$pdo->lastInsertId();
        echo "Created default tenant: {$tenantDomain} (ID: {$tenantId})\n";
    }
} catch (PDOException $e) {
    // If tenants table doesn't exist, use NULL tenant_id (single installation)
    $tenantId = null;
    echo "Note: Using single-installation mode (no tenant_id)\n";
}

    // Ensure roles exist
$roles = [
    ['key' => 'director', 'name' => 'Director', 'description' => 'View all reports & strategic settings'],
    ['key' => 'admin', 'name' => 'Administrator', 'description' => 'Full system control'],
    ['key' => 'tech', 'name' => 'Technical Admin', 'description' => 'Systems & infrastructure'],
    ['key' => 'finance_manager', 'name' => 'Finance Manager', 'description' => 'Finance & supplier control'],
    ['key' => 'operation_manager', 'name' => 'Operations Manager', 'description' => 'Hotel operations'],
    ['key' => 'cashier', 'name' => 'Cashier', 'description' => 'POS & folios'],
    ['key' => 'service_agent', 'name' => 'Service Agent', 'description' => 'Front desk & reservations'],
    ['key' => 'kitchen', 'name' => 'Kitchen', 'description' => 'Kitchen operations'],
    ['key' => 'housekeeping', 'name' => 'Housekeeping', 'description' => 'Room cleaning'],
    ['key' => 'ground', 'name' => 'Ground & Maintenance', 'description' => 'Maintenance tasks'],
    ['key' => 'security', 'name' => 'Security', 'description' => 'Security & attendance'],
];

$roleStmt = $pdo->prepare('INSERT INTO roles (`key`, name, description) VALUES (:key, :name, :description)
    ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description)');

foreach ($roles as $role) {
    $roleStmt->execute($role);
}

// Employee data
$employees = [
    // Housekeeping staff (11,000 - 12,000)
    ['name' => 'Faith Wavinya', 'role' => 'housekeeping', 'department' => 'Housekeeping', 'salary' => 11500],
    ['name' => 'Anne Mukene Kyambu', 'role' => 'housekeeping', 'department' => 'Housekeeping', 'salary' => 11500],
    ['name' => 'Cythia Kyalo', 'role' => 'housekeeping', 'department' => 'Housekeeping', 'salary' => 11000],
    ['name' => 'Magdaline Mukeku', 'role' => 'housekeeping', 'department' => 'Housekeeping', 'salary' => 12000],
    ['name' => 'Rachael Mutindi', 'role' => 'housekeeping', 'department' => 'Housekeeping', 'salary' => 11500],
    ['name' => 'Simon Mutuku', 'role' => 'ground', 'department' => 'Grounds/Housekeeping', 'salary' => 12000],
    
    // Service / Front Desk (13,000 - 16,000)
    ['name' => 'Martha Mutua', 'role' => 'service_agent', 'department' => 'Front Desk', 'salary' => 14000],
    ['name' => 'Valentine Mumbe Kimeu', 'role' => 'service_agent', 'department' => 'Front Desk', 'salary' => 14000],
    ['name' => 'Nancyanne Mumbi', 'role' => 'service_agent', 'department' => 'Front Desk', 'salary' => 13500],
    ['name' => 'Jane Mbula', 'role' => 'cashier', 'department' => 'Front Desk', 'salary' => 15000],
    ['name' => 'Dolphine Moraa', 'role' => 'cashier', 'department' => 'Front Desk', 'salary' => 15000],
    ['name' => 'Fransis Maundu Muthini', 'role' => 'service_agent', 'department' => 'Front Desk', 'salary' => 14500],
    ['name' => 'Akhenda Samuel Aaron', 'role' => 'service_agent', 'department' => 'Front Desk', 'salary' => 16000],
    
    // Kitchen / Chef (15,000 each)
    ['name' => 'Bessy Kathambi', 'role' => 'kitchen', 'department' => 'Kitchen', 'salary' => 15000],
    ['name' => 'Josephat Mbika Kinywa', 'role' => 'kitchen', 'department' => 'Kitchen', 'salary' => 15000],
    ['name' => 'Moses Sila Masila', 'role' => 'kitchen', 'department' => 'Kitchen', 'salary' => 15000],
    ['name' => 'Eric Kimanthi', 'role' => 'kitchen', 'department' => 'Kitchen', 'salary' => 15000],
    ['name' => 'Sila Cyrus', 'role' => 'kitchen', 'department' => 'Kitchen', 'salary' => 15000],
    ['name' => 'Kimathi', 'role' => 'kitchen', 'department' => 'Kitchen', 'salary' => 15000],
    ['name' => 'Joshua Jayden', 'role' => 'kitchen', 'department' => 'Kitchen', 'salary' => 15000],
    
    // Management
    ['name' => 'Ezrah Kiilu', 'role' => 'tech', 'department' => 'Technical Admin', 'salary' => 40000], // Fixed: Changed from finance_manager to tech
    ['name' => 'Cinty Mueni', 'role' => 'operation_manager', 'department' => 'Operations', 'salary' => 40000],
    
    // Security
    ['name' => 'Security Officer', 'role' => 'security', 'department' => 'Security', 'salary' => 15000],
    
    // Directors (Super Admin - use admin role for full privileges)
    ['name' => 'Ezra Ndolo', 'role' => 'admin', 'department' => 'Directors', 'salary' => 0], // Directors have full admin privileges
    ['name' => 'Purity Ndolo', 'role' => 'admin', 'department' => 'Directors', 'salary' => 0],
];

// Prepare statements
$userStmt = $pdo->prepare('INSERT INTO users (tenant_id, name, username, email, password, role_key, status)
    VALUES (:tenant_id, :name, :username, :email, :password, :role_key, :status)
    ON DUPLICATE KEY UPDATE name = VALUES(name), username = VALUES(username), role_key = VALUES(role_key), status = VALUES(status)');


// Check if staff table exists
$staffTableExists = false;
try {
    $checkTableStmt = $pdo->query("SHOW TABLES LIKE 'staff'");
    $staffTableExists = $checkTableStmt->rowCount() > 0;
    if ($staffTableExists) {
        echo "Staff table found, will create staff records.\n";
    }
} catch (PDOException $e) {
    // Table doesn't exist
    $staffTableExists = false;
    echo "Staff table not found, will skip staff records. Run migrations first.\n";
}

$staffStmt = null;
if ($staffTableExists) {
    $staffStmt = $pdo->prepare('INSERT INTO staff (user_id, employee_id, phone, id_number, address, department, basic_salary, hire_date, status)
        VALUES (:user_id, :employee_id, :phone, :id_number, :address, :department, :basic_salary, :hire_date, :status)
        ON DUPLICATE KEY UPDATE 
            employee_id = VALUES(employee_id),
            phone = VALUES(phone),
            id_number = VALUES(id_number),
            address = VALUES(address),
            department = VALUES(department),
            basic_salary = VALUES(basic_salary),
            status = VALUES(status)');
}

$employeeCounter = 1;

foreach ($employees as $emp) {
    // Parse name
    $nameParts = explode(' ', $emp['name'], 2);
    $firstName = $nameParts[0];
    $lastName = $nameParts[1] ?? $nameParts[0];
    
    // Generate email and username
    $email = generateEmail($firstName, $lastName);
    $username = generateUsername($firstName, $lastName);
    
    // Check if user already exists
    $checkStmt = $pdo->prepare('SELECT id, role_key, username FROM users WHERE email = ? LIMIT 1');
    $checkStmt->execute([$email]);
    $existingUser = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingUser) {
        $userId = (int)$existingUser['id'];
        $currentRole = $existingUser['role_key'] ?? '';
        $currentUsername = $existingUser['username'] ?? null;
        echo "User already exists: {$emp['name']} ({$email})\n";
        
        // Update username if it doesn't exist
        if (empty($currentUsername)) {
            $updateStmt = $pdo->prepare('UPDATE users SET username = ? WHERE id = ?');
            $updateStmt->execute([$username, $userId]);
            echo "  → Added username: {$username}\n";
        }
        
        // Update role if it has changed (for fixing role assignments)
        if ($currentRole !== $emp['role']) {
            $updateStmt = $pdo->prepare('UPDATE users SET role_key = ? WHERE id = ?');
            $updateStmt->execute([$emp['role'], $userId]);
            echo "  → Updated role from '{$currentRole}' to '{$emp['role']}'\n";
        }
    } else {
        // Create user
        $userStmt->execute([
            'tenant_id' => $tenantId,
            'name' => $emp['name'],
            'username' => $username,
            'email' => $email,
            'password' => password_hash('password', PASSWORD_BCRYPT), // Default password
            'role_key' => $emp['role'],
            'status' => 'active',
        ]);
        $userId = (int)$pdo->lastInsertId();
        echo "Created user: {$emp['name']} ({$username} / {$email}) - ID: {$userId}\n";
    }
    
    // Create or update staff record (only if staff table exists)
    if ($staffTableExists && $staffStmt) {
        $employeeId = 'EMP-' . str_pad((string)$employeeCounter, 4, '0', STR_PAD_LEFT);
        
        // Check if staff record exists
        $checkStaffStmt = $pdo->prepare('SELECT id FROM staff WHERE user_id = ? LIMIT 1');
        $checkStaffStmt->execute([$userId]);
        $existingStaffId = $checkStaffStmt->fetchColumn();
        
        if (!$existingStaffId) {
            $staffStmt->execute([
                'user_id' => $userId,
                'employee_id' => $employeeId,
                'phone' => generatePhone(),
                'id_number' => generateIdNumber(),
                'address' => 'Machakos, Kenya', // Placeholder address
                'department' => $emp['department'],
                'basic_salary' => $emp['salary'],
                'hire_date' => date('Y-m-d', strtotime('-' . rand(30, 365) . ' days')), // Random hire date in past year
                'status' => 'active',
            ]);
            echo "  → Staff record created for {$emp['name']} (Dept: {$emp['department']}, Salary: KES " . number_format($emp['salary'], 2) . ")\n";
        } else {
            // Update existing staff record
            $updateStaffStmt = $pdo->prepare('UPDATE staff SET department = ?, basic_salary = ?, status = ? WHERE user_id = ?');
            $updateStaffStmt->execute([$emp['department'], $emp['salary'], 'active', $userId]);
            echo "  → Staff record updated for {$emp['name']} (Dept: {$emp['department']}, Salary: KES " . number_format($emp['salary'], 2) . ")\n";
        }
    } else {
        echo "  → Note: Staff table not found, skipping staff record creation for {$emp['name']}\n";
    }
    
    $employeeCounter++;
}

echo "\n=== Employee Seeding Complete ===\n";
echo "Total employees processed: " . count($employees) . "\n";
echo "\nDefault password for all users: 'password'\n";
echo "Please change passwords after first login.\n";

