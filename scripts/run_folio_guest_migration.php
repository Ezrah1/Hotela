<?php

require_once __DIR__ . '/../app/Support/helpers.php';
require_once __DIR__ . '/../bootstrap/app.php';

$migrationFile = __DIR__ . '/../database/migrations/2025_11_24_000000_add_guest_fields_to_folios.php';
$migrations = require $migrationFile;

$pdo = db();

echo "Running folio guest fields migration...\n";

foreach ($migrations as $sql) {
    try {
        $pdo->exec($sql);
        echo "✓ Migration executed successfully\n";
    } catch (\PDOException $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}

echo "Migration completed!\n";

