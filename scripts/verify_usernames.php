<?php

declare(strict_types=1);

$app = require __DIR__ . '/../bootstrap/app.php';

use App\Core\Database;

$pdo = Database::connection();

$stmt = $pdo->query('SELECT id, name, username, email FROM users ORDER BY id LIMIT 20');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Username Verification\n";
echo str_repeat("=", 80) . "\n\n";

$noUsername = 0;
foreach ($users as $user) {
    $username = $user['username'] ?? 'NO USERNAME';
    if (empty($user['username'])) {
        $noUsername++;
        echo "❌ ";
    } else {
        echo "✓  ";
    }
    echo sprintf("%-30s | %-20s | %s\n", 
        substr($user['name'], 0, 30),
        $username,
        $user['email']
    );
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "Total users checked: " . count($users) . "\n";
echo "Users without username: $noUsername\n";

if ($noUsername > 0) {
    echo "\n⚠️  Some users don't have usernames. Run seed_employees.php to generate them.\n";
}

