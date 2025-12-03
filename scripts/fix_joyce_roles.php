<?php

require __DIR__ . '/../bootstrap/app.php';

use App\Core\Database;

$pdo = Database::connection();

echo "Fixing Joyce Resorts user roles...\n\n";

// Get the Joyce Resorts tenant ID
$tenantStmt = $pdo->prepare('SELECT id FROM tenants WHERE name = :name OR domain LIKE :domain LIMIT 1');
$tenantStmt->execute(['name' => 'Joyce Resorts', 'domain' => '%joyce%']);
$tenant = $tenantStmt->fetch(PDO::FETCH_ASSOC);

if (!$tenant) {
    $tenantStmt = $pdo->query('SELECT id FROM tenants LIMIT 1');
    $tenant = $tenantStmt->fetch(PDO::FETCH_ASSOC);
}

$tenantId = (int)$tenant['id'];
echo "Using tenant ID: {$tenantId}\n\n";

$updateStmt = $pdo->prepare('
    UPDATE users 
    SET tenant_id = :tenant_id, role_key = :role_key 
    WHERE id = :id
');

// Find Ezrah - set to finance_manager
echo "1. Setting Ezrah to Finance Manager...\n";
$ezrahStmt = $pdo->prepare('
    SELECT id, name, email, role_key 
    FROM users 
    WHERE (name LIKE :name OR email LIKE :email) 
    LIMIT 1
');
$ezrahStmt->execute(['name' => '%ezrah%', 'email' => '%ezrah%']);
$ezrah = $ezrahStmt->fetch(PDO::FETCH_ASSOC);

if ($ezrah) {
    $updateStmt->execute([
        'id' => $ezrah['id'],
        'tenant_id' => $tenantId,
        'role_key' => 'finance_manager'
    ]);
    echo "   ✓ Updated {$ezrah['name']} ({$ezrah['email']}) to Finance Manager\n";
} else {
    echo "   ⚠ Ezrah not found\n";
}

// Find Cinty - set to operation_manager
echo "\n2. Setting Cinty to Operations Manager...\n";
$cintyStmt = $pdo->prepare('
    SELECT id, name, email, role_key 
    FROM users 
    WHERE (name LIKE :name OR email LIKE :email) 
    LIMIT 1
');
$cintyStmt->execute(['name' => '%cinty%', 'email' => '%cinty%']);
$cinty = $cintyStmt->fetch(PDO::FETCH_ASSOC);

if ($cinty) {
    $updateStmt->execute([
        'id' => $cinty['id'],
        'tenant_id' => $tenantId,
        'role_key' => 'operation_manager'
    ]);
    echo "   ✓ Updated {$cinty['name']} ({$cinty['email']}) to Operations Manager\n";
} else {
    echo "   ⚠ Cinty not found\n";
}

// Ensure other directors are kept as directors
echo "\n3. Ensuring directors remain as directors...\n";
$directorsStmt = $pdo->prepare('
    SELECT id, name, email 
    FROM users 
    WHERE role_key = "director" AND id != :ezrah_id
    ORDER BY name
');
$ezrahId = $ezrah ? $ezrah['id'] : 0;
$directorsStmt->execute(['ezrah_id' => $ezrahId]);
$directors = $directorsStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($directors as $dir) {
    $updateStmt->execute([
        'id' => $dir['id'],
        'tenant_id' => $tenantId,
        'role_key' => 'director'
    ]);
    echo "   ✓ Kept {$dir['name']} ({$dir['email']}) as Director\n";
}

// Final summary
echo "\n4. Final user list for Joyce Resorts:\n";
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
    $roleName = ucfirst(str_replace('_', ' ', $user['role_key']));
    echo "   - {$user['name']} ({$user['email']}) - {$roleName}\n";
}

echo "\nDone!\n";

