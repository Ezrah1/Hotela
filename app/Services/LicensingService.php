<?php

namespace App\Services;

use App\Repositories\SystemLicenseRepository;
use PDO;

class LicensingService
{
    protected SystemLicenseRepository $licenseRepo;
    protected PDO $db;

    public function __construct()
    {
        $this->db = db();
        $this->licenseRepo = new SystemLicenseRepository($this->db);
    }

    public function validate(): array
    {
        try {
            $license = $this->licenseRepo->getCurrent();
        } catch (\PDOException $e) {
            // If tables don't exist yet, allow access (graceful degradation)
            if (str_contains($e->getMessage(), "doesn't exist") || str_contains($e->getMessage(), 'Base table')) {
                return [
                    'valid' => true,
                    'status' => 'trial',
                    'message' => 'License system not initialized. Running in trial mode.',
                ];
            }
            throw $e;
        }

        if (!$license) {
            return [
                'valid' => false,
                'status' => 'missing',
                'message' => 'No license found. Please activate your license.',
            ];
        }

        // Check if expired
        if ($license['expires_at'] && strtotime($license['expires_at']) < time()) {
            $this->licenseRepo->updateStatus($license['id'], 'expired');
            return [
                'valid' => false,
                'status' => 'expired',
                'message' => 'License has expired. Please renew your subscription.',
                'expires_at' => $license['expires_at'],
            ];
        }

        // Check if revoked
        if ($license['status'] === 'revoked') {
            return [
                'valid' => false,
                'status' => 'revoked',
                'message' => 'License has been revoked. Please contact support.',
            ];
        }

        // If last verification was more than 24 hours ago, verify with server
        $lastVerified = $license['last_verified_at'] ? strtotime($license['last_verified_at']) : 0;
        $shouldVerify = (time() - $lastVerified) > 86400; // 24 hours

        if ($shouldVerify && $license['verification_url']) {
            $serverValidation = $this->verifyWithServer($license);
            if (!$serverValidation['valid']) {
                $this->licenseRepo->updateStatus($license['id'], $serverValidation['status'] ?? 'revoked');
                return $serverValidation;
            }
            $this->licenseRepo->updateLastVerified($license['id']);
        }

        return [
            'valid' => true,
            'status' => $license['status'],
            'plan_type' => $license['plan_type'],
            'expires_at' => $license['expires_at'],
            'message' => 'License is active.',
        ];
    }

    public function activate(string $licenseKey, string $hardwareFingerprint = null): array
    {
        if (!$hardwareFingerprint) {
            $hardwareFingerprint = $this->generateHardwareFingerprint();
        }

        // Verify with license server
        $verification = $this->verifyLicenseKey($licenseKey, $hardwareFingerprint);

        if (!$verification['valid']) {
            return $verification;
        }

        // Save license
        $licenseId = $this->licenseRepo->createOrUpdate([
            'license_key' => $licenseKey,
            'hardware_fingerprint' => $hardwareFingerprint,
            'plan_type' => $verification['plan_type'] ?? 'monthly',
            'status' => 'active',
            'expires_at' => $verification['expires_at'] ?? null,
            'verification_url' => $verification['verification_url'] ?? null,
        ]);

        return [
            'valid' => true,
            'message' => 'License activated successfully.',
            'license_id' => $licenseId,
        ];
    }

    protected function verifyWithServer(array $license): array
    {
        if (!$license['verification_url']) {
            return ['valid' => true]; // No server verification required
        }

        try {
            $ch = curl_init($license['verification_url']);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'license_key' => $license['license_key'],
                    'hardware_fingerprint' => $license['hardware_fingerprint'],
                ]),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT => 10,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                return ['valid' => false, 'status' => 'revoked', 'message' => 'License verification failed.'];
            }

            $data = json_decode($response, true);
            return $data ?? ['valid' => true];
        } catch (\Exception $e) {
            // If verification fails, allow grace period (don't immediately revoke)
            return ['valid' => true, 'warning' => 'Could not verify with server, using cached status.'];
        }
    }

    protected function verifyLicenseKey(string $licenseKey, string $hardwareFingerprint): array
    {
        // TODO: Implement actual license server verification
        // For now, accept any license key starting with "PROD-"
        if (str_starts_with($licenseKey, 'PROD-')) {
            return [
                'valid' => true,
                'plan_type' => 'monthly',
                'expires_at' => date('Y-m-d H:i:s', strtotime('+1 month')),
                'verification_url' => null, // Set your actual license server URL
            ];
        }

        return [
            'valid' => false,
            'message' => 'Invalid license key format.',
        ];
    }

    protected function generateHardwareFingerprint(): string
    {
        $components = [
            php_uname('n'), // Hostname
            php_uname('m'), // Machine type
            $_SERVER['SERVER_NAME'] ?? '',
        ];

        return hash('sha256', implode('|', $components));
    }

    public function isLocked(): bool
    {
        $validation = $this->validate();
        return !$validation['valid'] && $validation['status'] !== 'trial';
    }
}

