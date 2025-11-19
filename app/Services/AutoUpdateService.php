<?php

namespace App\Services;

use App\Repositories\SystemInfoRepository;
use PDO;

class AutoUpdateService
{
    protected SystemInfoRepository $infoRepo;
    protected PDO $db;

    public function __construct()
    {
        $this->db = db();
        $this->infoRepo = new SystemInfoRepository($this->db);
    }

    public function checkForUpdates(): array
    {
        $currentVersion = $this->infoRepo->get('app_version', '1.0.0');
        $updateServerUrl = $this->infoRepo->get('update_server_url', 'https://updates.hotela.com/api/check');
        $lastCheck = $this->infoRepo->get('last_update_check');

        // Only check once per day
        if ($lastCheck && (time() - strtotime($lastCheck)) < 86400) {
            return [
                'checked' => false,
                'message' => 'Update check was performed recently.',
            ];
        }

        try {
            $ch = curl_init($updateServerUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'current_version' => $currentVersion,
                    'platform' => 'php',
                    'php_version' => PHP_VERSION,
                ]),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT => 10,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $this->infoRepo->set('last_update_check', date('Y-m-d H:i:s'));

            if ($httpCode !== 200) {
                return [
                    'checked' => true,
                    'available' => false,
                    'message' => 'Could not connect to update server.',
                ];
            }

            $data = json_decode($response, true);

            if (!$data || !isset($data['latest_version'])) {
                return [
                    'checked' => true,
                    'available' => false,
                    'message' => 'Invalid response from update server.',
                ];
            }

            $latestVersion = $data['latest_version'];
            $hasUpdate = version_compare($latestVersion, $currentVersion, '>');

            return [
                'checked' => true,
                'available' => $hasUpdate,
                'current_version' => $currentVersion,
                'latest_version' => $latestVersion,
                'download_url' => $data['download_url'] ?? null,
                'changelog' => $data['changelog'] ?? null,
                'checksum' => $data['checksum'] ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'checked' => true,
                'available' => false,
                'message' => 'Error checking for updates: ' . $e->getMessage(),
            ];
        }
    }

    public function applyUpdate(string $updatePackageUrl, string $expectedChecksum = null): array
    {
        try {
            // Download update package
            $tempFile = sys_get_temp_dir() . '/hotela_update_' . time() . '.zip';
            $packageContent = file_get_contents($updatePackageUrl);

            if ($packageContent === false) {
                return ['success' => false, 'message' => 'Failed to download update package.'];
            }

            // Verify checksum if provided
            if ($expectedChecksum) {
                $actualChecksum = hash('sha256', $packageContent);
                if ($actualChecksum !== $expectedChecksum) {
                    return ['success' => false, 'message' => 'Checksum verification failed. Package may be corrupted.'];
                }
            }

            file_put_contents($tempFile, $packageContent);

            // Backup key directories
            $backupDir = BASE_PATH . '/backups/pre_update_' . date('Y-m-d_H-i-s');
            $this->backupDirectories($backupDir);

            // Extract and apply update
            $zip = new \ZipArchive();
            if ($zip->open($tempFile) !== true) {
                return ['success' => false, 'message' => 'Failed to extract update package.'];
            }

            $extractPath = BASE_PATH;
            $zip->extractTo($extractPath);
            $zip->close();

            // Clean up
            unlink($tempFile);

            // Update version
            $newVersion = $this->infoRepo->get('app_version', '1.0.0'); // Will be updated by migration in package
            $this->infoRepo->set('last_update_applied', date('Y-m-d H:i:s'));

            return [
                'success' => true,
                'message' => 'Update applied successfully.',
                'backup_location' => $backupDir,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error applying update: ' . $e->getMessage(),
            ];
        }
    }

    protected function backupDirectories(string $backupDir): void
    {
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $directoriesToBackup = [
            'app',
            'config',
            'database/migrations',
            'public',
            'resources/views',
        ];

        foreach ($directoriesToBackup as $dir) {
            $source = BASE_PATH . '/' . $dir;
            if (is_dir($source)) {
                $dest = $backupDir . '/' . $dir;
                $this->copyDirectory($source, $dest);
            }
        }
    }

    protected function copyDirectory(string $source, string $dest): void
    {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $target = $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            if ($item->isDir()) {
                if (!is_dir($target)) {
                    mkdir($target, 0755, true);
                }
            } else {
                copy($item, $target);
            }
        }
    }
}

