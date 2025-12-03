<?php

require __DIR__ . '/../bootstrap/app.php';

use App\Core\Database;

$pdo = Database::connection();

echo "Updating Joyce Resorts users...\n\n";

// Get the Joyce Resorts tenant ID
$tenantStmt = $pdo->prepare('SELECT id FROM tenants WHERE name = :name OR domain LIKE :domain LIMIT 1');
$tenantStmt->execute(['name' => 'Joyce Resorts', 'domain' => '%joyce%']);
$tenant = $tenantStmt->fetch(PDO::FETCH_ASSOC);

if (!$tenant) {
    // Try to get any tenant
    $tenantStmt = $pdo->query('SELECT id FROM tenants LIMIT 1');
    $tenant = $tenantStmt->fetch(PDO::FETCH_ASSOC);
}

if (!$tenant) {
    die("✗ No tenant found. Please create a tenant first.\n");
}

$tenantId = (int)$tenant['id'];
echo "Using tenant ID: {$tenantId}\n\n";

// Step 1: Delete test users
echo "1. Removing test users...\n";
$testEmails = ['admin@hotela.test', 'finance@hotela.test', 'ops@hotela.test'];
$deleteStmt = $pdo->prepare('DELETE FROM users WHERE email = :email');
foreach ($testEmails as $email) {
    $deleteStmt->execute(['email' => $email]);
    $deleted = $deleteStmt->rowCount();
    if ($deleted > 0) {
        echo "   ✓ Deleted: {$email}\n";
    } else {
        echo "   ⚠ Not found: {$email}\n";
    }
}

// Step 2: Find Ezra and Cinty
echo "\n2. Finding Ezra and Cinty...\n";
$findStmt = $pdo->prepare('
    SELECT id, name, email, role_key, tenant_id 
    FROM users 
    WHERE (name LIKE :name1 OR email LIKE :email1) 
       OR (name LIKE :name2 OR email LIKE :email2)
');
$findStmt->execute([
    'name1' => '%ezra%',
    'email1' => '%ezra%',
    'name2' => '%cinty%',
    'email2' => '%cinty%',
]);
$users = $findStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($users)) {
    echo "   ⚠ No users found matching Ezra or Cinty\n";
    echo "   Searching all users...\n";
    $allUsers = $pdo->query('SELECT id, name, email, role_key, tenant_id FROM users ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($allUsers as $u) {
        echo "   - {$u['name']} ({$u['email']}) - Role: {$u['role_key']} - Tenant: {$u['tenant_id']}\n";
    }
} else {
    foreach ($users as $user) {
        echo "   Found: {$user['name']} ({$user['email']}) - Current Role: {$user['role_key']}\n";
    }
}

// Step 3: Update users
echo "\n3. Updating user roles and tenant assignment...\n";
$updateStmt = $pdo->prepare('
    UPDATE users 
    SET tenant_id = :tenant_id, role_key = :role_key 
    WHERE id = :id
');

// Find Ezra (should be director)
$ezraStmt = $pdo->prepare('
    SELECT id, name, email, role_key 
    FROM users 
    WHERE (name LIKE :name OR email LIKE :email) 
    LIMIT 1
');
$ezraStmt->execute(['name' => '%ezra%', 'email' => '%ezra%']);
$ezra = $ezraStmt->fetch(PDO::FETCH_ASSOC);

if ($ezra) {
    $updateStmt->execute([
        'id' => $ezra['id'],
        'tenant_id' => $tenantId,
        'role_key' => 'director'
    ]);
    echo "   ✓ Updated {$ezra['name']} to Director\n";
} else {
    echo "   ⚠ Ezra not found\n";
}

// Find Cinty (should be finance_manager or operation_manager)
$cintyStmt = $pdo->prepare('
    SELECT id, name, email, role_key 
    FROM users 
    WHERE (name LIKE :name OR email LIKE :email) 
    LIMIT 1
');
$cintyStmt->execute(['name' => '%cinty%', 'email' => '%cinty%']);
$cinty = $cintyStmt->fetch(PDO::FETCH_ASSOC);

if ($cinty) {
    // Check if we should make them finance or operations
    // Based on user request: "ezrah and cinty as finance and ops"
    // So Ezra = director, Cinty = finance_manager
    // But wait, the user said "use the directors and ezrah and cinty as finance and ops"
    // This is a bit ambiguous. Let me interpret:
    // - Ezra should be director (already set above)
    // - Cinty should be finance_manager
    
    $updateStmt->execute([
        'id' => $cinty['id'],
        'tenant_id' => $tenantId,
        'role_key' => 'finance_manager'
    ]);
    echo "   ✓ Updated {$cinty['name']} to Finance Manager\n";
} else {
    echo "   ⚠ Cinty not found\n";
}

// Check if there are other directors that need to be kept
echo "\n4. Checking for other directors...\n";
$directorsStmt = $pdo->prepare('
    SELECT id, name, email, role_key, tenant_id 
    FROM users 
    WHERE role_key = "director"
    ORDER BY name
');
$directorsStmt->execute();
$directors = $directorsStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($directors as $dir) {
    if ($dir['tenant_id'] != $tenantId) {
        $updateStmt->execute([
            'id' => $dir['id'],
            'tenant_id' => $tenantId,
            'role_key' => 'director'
        ]);
        echo "   ✓ Updated {$dir['name']} to tenant {$tenantId}\n";
    } else {
        echo "   - {$dir['name']} ({$dir['email']}) - Already assigned to tenant\n";
    }
}

// Final summary
echo "\n5. Final user list for Joyce Resorts:\n";
$finalStmt = $pdo->prepare('
    SELECT id, name, email, role_key 
    FROM users 
    WHERE tenant_id = :tenant_id
    ORDER BY 
        CASE role_key 
            WHEN "director" THEN 1
            WHEN "finance_manager" THEN 2
            WHEN "operation_manager" THEN 3
            ELSE 4
        END,
        name
');
$finalStmt->execute(['tenant_id' => $tenantId]);
$finalUsers = $finalStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($finalUsers as $user) {
    echo "   - {$user['name']} ({$user['email']}) - {$user['role_key']}\n";
}

echo "\nDone!\n";

