<?php

require __DIR__ . '/../bootstrap/app.php';

use App\Core\Database;

$pdo = Database::connection();

echo "Updating Ezra and Purity to director role...\n\n";

// Update Ezra Ndolo
$stmt = $pdo->prepare("UPDATE users SET role_key = 'director' WHERE email = ? OR (name LIKE ? AND role_key = 'admin')");
$stmt->execute(['ezra.ndolo@hotela.local', '%Ezra Ndolo%']);
$ezraUpdated = $stmt->rowCount();
echo "Ezra updated: $ezraUpdated row(s)\n";

// Update Purity Ndolo
$stmt = $pdo->prepare("UPDATE users SET role_key = 'director' WHERE email = ? OR (name LIKE ? AND role_key = 'admin')");
$stmt->execute(['purity.ndolo@hotela.local', '%Purity Ndolo%']);
$purityUpdated = $stmt->rowCount();
echo "Purity updated: $purityUpdated row(s)\n";

// Verify
$stmt = $pdo->query("SELECT id, name, email, role_key FROM users WHERE email IN ('ezra.ndolo@hotela.local', 'purity.ndolo@hotela.local')");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "\nUpdated users:\n";
print_r($users);

echo "\n✓ Done!\n";

