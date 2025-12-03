<?php
/**
 * Quick Database Backup Script
 * 
 * Simple database backup using mysqldump
 * Usage: php scripts/backup_database.php
 */

declare(strict_types=1);

$app = require __DIR__ . '/../bootstrap/app.php';

use App\Core\Database;

$config = config('app.db');
$dbName = $config['database'] ?? 'hotela';
$dbUser = $config['username'] ?? 'root';
$dbPass = $config['password'] ?? '';
$dbHost = $config['host'] ?? '127.0.0.1';
$dbPort = $config['port'] ?? 3306;

// Backup directory
$backupDir = getenv('BACKUP_DIR') ?: (PHP_OS_FAMILY === 'Windows' 
    ? 'C:\Users\\' . getenv('USERNAME') . '\Desktop\Backups'
    : getenv('HOME') . '/Backups');

if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

$timestamp = date('Y-m-d_H-i-s');
$backupFile = $backupDir . DIRECTORY_SEPARATOR . "hotela_db_{$timestamp}.sql";

echo "Backing up database: {$dbName}\n";
echo "Backup file: {$backupFile}\n\n";

// Find mysqldump
$mysqldump = 'mysqldump';
if (PHP_OS_FAMILY === 'Windows') {
    $xamppPaths = [
        'C:\xampp\mysql\bin\mysqldump.exe',
        'C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqldump.exe',
        'C:\Program Files\MySQL\MySQL Server 5.7\bin\mysqldump.exe',
    ];
    
    foreach ($xamppPaths as $path) {
        if (file_exists($path)) {
            $mysqldump = $path;
            break;
        }
    }
}

// Build command
$command = sprintf(
    '"%s" --host=%s --port=%s --user=%s %s %s > "%s"',
    $mysqldump,
    escapeshellarg($dbHost),
    escapeshellarg((string)$dbPort),
    escapeshellarg($dbUser),
    !empty($dbPass) ? '--password=' . escapeshellarg($dbPass) : '',
    escapeshellarg($dbName),
    $backupFile
);

exec($command . ' 2>&1', $output, $returnCode);

if ($returnCode === 0 && file_exists($backupFile) && filesize($backupFile) > 0) {
    $size = number_format(filesize($backupFile) / 1024, 2);
    echo "✅ Database backup created successfully!\n";
    echo "Size: {$size} KB\n";
    echo "File: {$backupFile}\n";
} else {
    echo "❌ Backup failed!\n";
    if (!empty($output)) {
        echo "Error: " . implode("\n", $output) . "\n";
    }
    exit(1);
}

