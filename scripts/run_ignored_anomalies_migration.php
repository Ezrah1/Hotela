<?php

require_once __DIR__ . '/../bootstrap/app.php';

$migrationFile = __DIR__ . '/../database/migrations/2025_11_24_020000_create_ignored_anomalies_table.php';

if (!file_exists($migrationFile)) {
    die("Migration file not found: $migrationFile\n");
}

$migrations = require $migrationFile;

if (!is_array($migrations)) {
    die("Invalid migration file format\n");
}

$db = db();

echo "Running migration: create_ignored_anomalies_table\n";

foreach ($migrations as $index => $sql) {
    echo "Executing SQL statement " . ($index + 1) . "...\n";
    
    try {
        $db->exec($sql);
        echo "✓ Successfully executed statement " . ($index + 1) . "\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false || 
            strpos($e->getMessage(), 'Duplicate') !== false) {
            echo "⚠ Table or column already exists (skipping): " . $e->getMessage() . "\n";
        } else {
            die("✗ Error executing statement " . ($index + 1) . ": " . $e->getMessage() . "\n");
        }
    }
}

echo "\nMigration completed successfully!\n";

