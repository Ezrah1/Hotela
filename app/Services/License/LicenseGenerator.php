<?php

namespace App\Services\License;

class LicenseGenerator
{
    /**
     * Generate a unique license key
     */
    public static function generate(): string
    {
        // Generate a license key in format: HOTELA-XXXX-XXXX-XXXX-XXXX
        $parts = [];
        for ($i = 0; $i < 4; $i++) {
            $parts[] = strtoupper(substr(md5(uniqid(rand(), true)), 0, 4));
        }
        return 'HOTELA-' . implode('-', $parts);
    }

    /**
     * Generate a signed token for license verification
     */
    public static function signToken(string $licenseKey, string $installationId, string $directorEmail): string
    {
        $data = [
            'license_key' => $licenseKey,
            'installation_id' => $installationId,
            'director_email' => $directorEmail,
            'timestamp' => time(),
        ];
        
        $payload = base64_encode(json_encode($data));
        $secret = self::getSecretKey();
        $signature = hash_hmac('sha256', $payload, $secret);
        
        return $payload . '.' . $signature;
    }

    /**
     * Verify a signed token
     */
    public static function verifyToken(string $signedToken): ?array
    {
        $parts = explode('.', $signedToken);
        if (count($parts) !== 2) {
            return null;
        }

        [$payload, $signature] = $parts;
        $secret = self::getSecretKey();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        $data = json_decode(base64_decode($payload), true);
        if (!$data || !isset($data['license_key'], $data['installation_id'], $data['director_email'])) {
            return null;
        }

        return $data;
    }

    /**
     * Get the secret key for signing (stored in environment or config)
     */
    protected static function getSecretKey(): string
    {
        // In production, this should be in environment variables
        $key = env('LICENSE_SECRET_KEY', 'hotela-license-secret-key-change-in-production');
        
        // If no key exists, generate and store one
        $keyFile = base_path('.license_secret');
        if (!file_exists($keyFile)) {
            $generatedKey = bin2hex(random_bytes(32));
            file_put_contents($keyFile, $generatedKey);
            return $generatedKey;
        }

        return file_get_contents($keyFile);
    }

    /**
     * Format license key for display
     */
    public static function format(string $licenseKey): string
    {
        return strtoupper($licenseKey);
    }
}

