<?php

require __DIR__ . '/../bootstrap/app.php';

$migrations = include __DIR__ . '/../database/migrations/2025_01_31_000000_create_attendance_tables.php';

foreach ($migrations as $sql) {
    try {
        db()->exec($sql);
        echo "Migration executed successfully\n";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

echo "All migrations completed.\n";

