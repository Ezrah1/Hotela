<?php

namespace App\Modules\Backups\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Support\Auth;

class BackupController extends Controller
{
    protected function getBackupDir(): string
    {
        $backupDir = getenv('BACKUP_DIR');
        if (!$backupDir) {
            if (PHP_OS_FAMILY === 'Windows') {
                $backupDir = 'C:\Users\\' . getenv('USERNAME') . '\Desktop\Backups';
            } else {
                $backupDir = getenv('HOME') . '/Backups';
            }
        }
        return $backupDir;
    }

    public function index(Request $request): void
    {
        Auth::requireRoles(['admin', 'director', 'tech']);
        
        $backupDir = $this->getBackupDir();
        $backups = [];
        
        if (is_dir($backupDir)) {
            $items = scandir($backupDir);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                
                $path = $backupDir . DIRECTORY_SEPARATOR . $item;
                
                if (is_dir($path) && str_starts_with($item, 'hotela_backup_')) {
                    $infoFile = $path . DIRECTORY_SEPARATOR . 'backup_info.txt';
                    $info = $this->parseBackupInfo($infoFile);
                    $info['name'] = $item;
                    $info['path'] = $path;
                    $info['size'] = $this->getDirectorySize($path);
                    $info['created'] = filemtime($path);
                    $backups[] = $info;
                } elseif (is_file($path) && (str_ends_with($item, '.sql') || str_ends_with($item, '.zip'))) {
                    $backups[] = [
                        'name' => $item,
                        'path' => $path,
                        'size' => filesize($path),
                        'created' => filemtime($path),
                        'type' => str_ends_with($item, '.sql') ? 'Database Only' : 'Archive',
                        'Backup Date' => date('Y-m-d H:i:s', filemtime($path)),
                    ];
                }
            }
        }
        
        // Sort by creation date (newest first)
        usort($backups, function($a, $b) {
            return ($b['created'] ?? 0) - ($a['created'] ?? 0);
        });
        
        // Get role config for layout
        $user = Auth::user();
        $role = $user['role_key'] ?? $user['role'] ?? 'director';
        $roleConfig = config('roles.' . $role, config('roles.director', []));
        
        // Set page title
        $pageTitle = 'Backup Management | Hotela';
        
        $this->view('dashboard/backups/index', [
            'backups' => $backups,
            'backupDir' => $backupDir,
            'totalSize' => array_sum(array_column($backups, 'size')),
            'pageTitle' => $pageTitle,
            'roleConfig' => $roleConfig,
        ]);
    }

    public function create(Request $request): void
    {
        Auth::requireRoles(['admin', 'director', 'tech']);
        
        header('Content-Type: application/json');
        
        $type = $request->input('type', 'full'); // 'full', 'database', 'files'
        
        // Execute backup script
        $scriptPath = base_path('scripts/backup.php');
        
        if (!file_exists($scriptPath)) {
            echo json_encode([
                'success' => false,
                'message' => 'Backup script not found',
            ]);
            return;
        }
        
        $args = $type === 'database' ? '--database-only' : ($type === 'files' ? '--files-only' : '');
        
        // Build command
        $phpPath = PHP_BINARY;
        
        // Create logs directory if it doesn't exist
        $logDir = base_path('storage/logs');
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . DIRECTORY_SEPARATOR . 'backup_' . date('Y-m-d_H-i-s') . '.log';
        
        // For Windows, use a batch file approach to avoid Apache process issues
        // For Unix, use nohup and redirect output
        if (PHP_OS_FAMILY === 'Windows') {
            // Create a temporary batch file to run the backup
            $batchFile = $logDir . DIRECTORY_SEPARATOR . 'run_backup_' . uniqid() . '.bat';
            $batchContent = sprintf(
                '@echo off' . PHP_EOL .
                'cd /d "%s"' . PHP_EOL .
                '"%s" "%s" %s > "%s" 2>&1' . PHP_EOL .
                'del "%%~f0"' . PHP_EOL, // Delete batch file after execution
                base_path(),
                $phpPath,
                $scriptPath,
                $args,
                $logFile
            );
            
            file_put_contents($batchFile, $batchContent);
            
            // Use start command with /MIN to run minimized and /B to run in background
            // This prevents Apache from blocking on the process
            $command = sprintf(
                'start /MIN /B "" "%s"',
                $batchFile
            );
            
            // Execute without waiting - this prevents Apache from blocking
            $handle = popen($command, 'r');
            if ($handle) {
                pclose($handle);
            }
            
            echo json_encode([
                'success' => true,
                'message' => ucfirst($type) . ' backup process started in the background. Please refresh the page in a few moments to see the new backup.',
            ]);
        } else {
            // Unix/Linux: Use nohup to run in background
            $command = sprintf(
                'nohup "%s" "%s" %s > "%s" 2>&1 & echo $!',
                $phpPath,
                $scriptPath,
                $args,
                $logFile
            );
            
            // Execute without waiting
            $pid = exec($command);
            
            echo json_encode([
                'success' => true,
                'message' => ucfirst($type) . ' backup process started in the background (PID: ' . $pid . '). Please refresh the page in a few moments to see the new backup.',
            ]);
        }
    }

    public function download(Request $request): void
    {
        Auth::requireRoles(['admin', 'director', 'tech']);
        
        $name = $request->input('name');
        if (!$name) {
            http_response_code(400);
            echo 'Backup name is required';
            return;
        }
        
        $backupDir = $this->getBackupDir();
        $backupPath = $backupDir . DIRECTORY_SEPARATOR . $name;
        
        // Security: ensure path is within backup directory
        $realBackupDir = realpath($backupDir);
        $realBackupPath = realpath($backupPath);
        
        if (!$realBackupPath || !str_starts_with($realBackupPath, $realBackupDir)) {
            http_response_code(403);
            echo 'Access denied';
            return;
        }
        
        if (!file_exists($realBackupPath)) {
            http_response_code(404);
            echo 'Backup not found';
            return;
        }
        
        // If it's a directory, create a zip
        if (is_dir($realBackupPath)) {
            $zipFile = $realBackupPath . '.zip';
            if (!file_exists($zipFile)) {
                $this->createZip($realBackupPath, $zipFile);
            }
            $realBackupPath = $zipFile;
            $name = basename($zipFile);
        }
        
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($name) . '"');
        header('Content-Length: ' . filesize($realBackupPath));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        
        readfile($realBackupPath);
    }

    public function delete(Request $request): void
    {
        Auth::requireRoles(['admin', 'director', 'tech']);
        
        $name = $request->input('name');
        if (!$name) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Backup name is required']);
            return;
        }
        
        $backupDir = $this->getBackupDir();
        $backupPath = $backupDir . DIRECTORY_SEPARATOR . $name;
        
        // Security: ensure path is within backup directory
        $realBackupDir = realpath($backupDir);
        $realBackupPath = realpath($backupPath);
        
        if (!$realBackupPath || !str_starts_with($realBackupPath, $realBackupDir)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        
        if (!file_exists($realBackupPath)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Backup not found']);
            return;
        }
        
        // Delete file or directory
        if (is_dir($realBackupPath)) {
            $this->deleteDirectory($realBackupPath);
        } else {
            unlink($realBackupPath);
        }
        
        // Also delete zip if exists
        $zipFile = $realBackupPath . '.zip';
        if (file_exists($zipFile)) {
            unlink($zipFile);
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Backup deleted successfully']);
    }

    protected function parseBackupInfo(string $infoFile): array
    {
        $info = [
            'Backup Date' => '',
            'Backup Type' => 'Full',
            'Database' => '',
            'Version' => '',
        ];
        
        if (file_exists($infoFile)) {
            $content = file_get_contents($infoFile);
            foreach (explode("\n", $content) as $line) {
                if (strpos($line, ':') !== false) {
                    [$key, $value] = explode(':', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    if (isset($info[$key])) {
                        $info[$key] = $value;
                    }
                }
            }
        }
        
        return $info;
    }

    protected function getDirectorySize(string $directory): int
    {
        $size = 0;
        if (!is_dir($directory)) {
            return 0;
        }
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            $size += $file->getSize();
        }
        
        return $size;
    }

    protected function createZip(string $source, string $destination): bool
    {
        if (!extension_loaded('zip')) {
            return false;
        }
        
        $zip = new \ZipArchive();
        if ($zip->open($destination, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
            return false;
        }
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($iterator as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($source) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
        
        return $zip->close();
    }

    protected function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        
        return rmdir($dir);
    }
}

