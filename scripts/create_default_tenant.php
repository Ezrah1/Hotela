<?php

require __DIR__ . '/../bootstrap/app.php';

use App\Core\Database;

$pdo = Database::connection();

echo "Creating default tenant...\n\n";

// Check if tenants table exists
try {
    $pdo->query('SELECT 1 FROM tenants LIMIT 1');
} catch (PDOException $e) {
    die("✗ Tenants table does not exist. Please run the migration first.\n");
}

// Check if a tenant already exists
$existing = $pdo->query('SELECT COUNT(*) FROM tenants')->fetchColumn();
if ($existing > 0) {
    echo "✓ Tenant already exists (count: {$existing})\n";
    exit(0);
}

// Get domain from environment or use default
$domain = $_SERVER['HTTP_HOST'] ?? 'hotela.local';
if (strpos($domain, ':') !== false) {
    $domain = explode(':', $domain)[0];
}

// Create default tenant
$stmt = $pdo->prepare('
    INSERT INTO tenants (name, slug, domain, status) 
    VALUES (:name, :slug, :domain, :status)
');
$slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $domain));

try {
    $stmt->execute([
        'name' => 'Joyce Resorts',
        'slug' => $slug,
        'domain' => $domain,
        'status' => 'active',
    ]);
    $tenantId = (int)$pdo->lastInsertId();
    echo "✓ Default tenant created (ID: {$tenantId})\n";
    echo "   Name: Joyce Resorts\n";
    echo "   Domain: {$domain}\n";
    echo "   Slug: {$slug}\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        echo "⚠ Tenant with domain '{$domain}' already exists\n";
    } else {
        die("✗ Error: " . $e->getMessage() . "\n");
    }
}

echo "\nDone!\n";

