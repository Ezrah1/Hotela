<?php
/**
 * Database and File Backup Script
 * 
 * Creates backups of:
 * - Database (MySQL dump)
 * - Application files (config, uploads, etc.)
 * 
 * Usage: php scripts/backup.php [--database-only] [--files-only] [--output-dir=/path]
 */

declare(strict_types=1);

$app = require __DIR__ . '/../bootstrap/app.php';

use App\Core\Database;

// Configuration
$backupBaseDir = getenv('BACKUP_DIR') ?: (getenv('HOME') . '/Desktop/Backups');
if (PHP_OS_FAMILY === 'Windows') {
    $backupBaseDir = getenv('BACKUP_DIR') ?: 'C:\Users\\' . getenv('USERNAME') . '\Desktop\Backups';
}

$timestamp = date('Y-m-d_H-i-s');
$backupDir = $backupBaseDir . DIRECTORY_SEPARATOR . 'hotela_backup_' . $timestamp;

// Parse command line arguments
$options = getopt('', ['database-only', 'files-only', 'output-dir:']);
$backupDatabase = !isset($options['files-only']);
$backupFiles = !isset($options['database-only']);

if (isset($options['output-dir'])) {
    $backupBaseDir = $options['output-dir'];
    $backupDir = $backupBaseDir . DIRECTORY_SEPARATOR . 'hotela_backup_' . $timestamp;
}

echo "=== Hotela Backup Script ===\n";
echo "Backup directory: {$backupDir}\n\n";

// Create backup directory
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
    echo "✓ Created backup directory\n";
}

$errors = [];

// 1. Backup Database
if ($backupDatabase) {
    echo "\n1. Backing up database...\n";
    echo "------------------------\n";
    
    try {
        $config = config('app.db');
        $dbName = $config['database'] ?? 'hotela';
        $dbUser = $config['username'] ?? 'root';
        $dbPass = $config['password'] ?? '';
        $dbHost = $config['host'] ?? '127.0.0.1';
        $dbPort = $config['port'] ?? 3306;
        
        $backupFile = $backupDir . DIRECTORY_SEPARATOR . 'database_' . $timestamp . '.sql';
        
        // Find mysqldump
        $mysqldump = 'mysqldump';
        if (PHP_OS_FAMILY === 'Windows') {
            // Check common XAMPP locations
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
        
        // Build mysqldump command
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
        
        echo "Running: mysqldump {$dbName}...\n";
        exec($command . ' 2>&1', $output, $returnCode);
        
        if ($returnCode === 0 && file_exists($backupFile) && filesize($backupFile) > 0) {
            $size = number_format(filesize($backupFile) / 1024, 2);
            echo "✓ Database backup created: {$backupFile} ({$size} KB)\n";
        } else {
            $error = "Failed to create database backup. Return code: {$returnCode}";
            if (!empty($output)) {
                $error .= "\n" . implode("\n", $output);
            }
            $errors[] = $error;
            echo "❌ {$error}\n";
        }
    } catch (Exception $e) {
        $error = "Database backup error: " . $e->getMessage();
        $errors[] = $error;
        echo "❌ {$error}\n";
    }
}

// 2. Backup Files
if ($backupFiles) {
    echo "\n2. Backing up files...\n";
    echo "---------------------\n";
    
    $filesDir = $backupDir . DIRECTORY_SEPARATOR . 'files';
    if (!is_dir($filesDir)) {
        mkdir($filesDir, 0755, true);
    }
    
    $directoriesToBackup = [
        'config' => 'Configuration files',
        'storage' => 'Storage and uploads',
        'database/migrations' => 'Database migrations',
        'public/uploads' => 'Public uploads',
    ];
    
    $basePath = base_path();
    $backedUp = 0;
    
    foreach ($directoriesToBackup as $dir => $description) {
        $sourcePath = $basePath . DIRECTORY_SEPARATOR . $dir;
        
        if (!is_dir($sourcePath)) {
            echo "⚠ Skipping {$dir}: directory not found\n";
            continue;
        }
        
        $destPath = $filesDir . DIRECTORY_SEPARATOR . $dir;
        $destParent = dirname($destPath);
        
        if (!is_dir($destParent)) {
            mkdir($destParent, 0755, true);
        }
        
        try {
            if (PHP_OS_FAMILY === 'Windows') {
                // Use xcopy on Windows
                $command = sprintf(
                    'xcopy /E /I /Y "%s" "%s"',
                    $sourcePath,
                    $destPath
                );
                exec($command, $output, $returnCode);
            } else {
                // Use cp on Unix
                $command = sprintf(
                    'cp -r "%s" "%s"',
                    $sourcePath,
                    $destPath
                );
                exec($command, $output, $returnCode);
            }
            
            if ($returnCode === 0) {
                $backedUp++;
                echo "✓ Backed up {$dir} ({$description})\n";
            } else {
                $errors[] = "Failed to backup {$dir}";
                echo "❌ Failed to backup {$dir}\n";
            }
        } catch (Exception $e) {
            $errors[] = "Error backing up {$dir}: " . $e->getMessage();
            echo "❌ Error backing up {$dir}: {$e->getMessage()}\n";
        }
    }
    
    echo "\n✓ Backed up {$backedUp} directories\n";
}

// 3. Create backup info file
$infoFile = $backupDir . DIRECTORY_SEPARATOR . 'backup_info.txt';
$info = [
    'Backup Date' => date('Y-m-d H:i:s'),
    'Application' => 'Hotela',
    'Version' => config('app.version', '1.0'),
    'Database' => $config['database'] ?? 'hotela',
    'Backup Type' => $backupDatabase && $backupFiles ? 'Full' : ($backupDatabase ? 'Database Only' : 'Files Only'),
    'Backup Directory' => $backupDir,
];

$infoContent = "=== Hotela Backup Information ===\n\n";
foreach ($info as $key => $value) {
    $infoContent .= "{$key}: {$value}\n";
}

if (!empty($errors)) {
    $infoContent .= "\n=== Errors ===\n";
    foreach ($errors as $error) {
        $infoContent .= "- {$error}\n";
    }
}

file_put_contents($infoFile, $infoContent);
echo "\n✓ Created backup info file\n";

// 4. Create archive (optional - requires zip extension)
if (extension_loaded('zip') && ($backupDatabase && $backupFiles)) {
    echo "\n3. Creating archive...\n";
    echo "--------------------\n";
    
    $zipFile = $backupBaseDir . DIRECTORY_SEPARATOR . 'hotela_backup_' . $timestamp . '.zip';
    
    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($backupDir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($iterator as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($backupDir) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
        
        $zip->close();
        
        if (file_exists($zipFile)) {
            $size = number_format(filesize($zipFile) / 1024 / 1024, 2);
            echo "✓ Archive created: {$zipFile} ({$size} MB)\n";
            
            // Optionally remove original directory
            // rmdir_recursive($backupDir);
        } else {
            echo "⚠ Archive creation may have failed\n";
        }
    } else {
        echo "⚠ Could not create archive (zip extension may have issues)\n";
    }
}

// Summary
echo "\n=== Backup Summary ===\n";
echo "Backup location: {$backupDir}\n";

if (!empty($errors)) {
    echo "\n⚠ Warnings/Errors:\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
} else {
    echo "\n✅ Backup completed successfully!\n";
}

echo "\n";

// Helper function to recursively remove directory
function rmdir_recursive(string $dir): bool
{
    if (!is_dir($dir)) {
        return false;
    }
    
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        is_dir($path) ? rmdir_recursive($path) : unlink($path);
    }
    
    return rmdir($dir);
}

