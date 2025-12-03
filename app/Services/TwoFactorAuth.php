<?php

namespace App\Services;

/**
 * Simple TOTP (Time-based One-Time Password) implementation
 * Compatible with Google Authenticator and similar apps
 */
class TwoFactorAuth
{
    /**
     * Generate a random secret key for 2FA
     */
    public static function generateSecret(int $length = 16): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // Base32 alphabet
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $secret;
    }

    /**
     * Generate a QR code URL for the secret
     */
    public static function getQRCodeUrl(string $secret, string $label, string $issuer = 'Hotela'): string
    {
        $otpauth = sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s',
            rawurlencode($issuer),
            rawurlencode($label),
            $secret,
            rawurlencode($issuer)
        );
        return 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($otpauth);
    }

    /**
     * Verify a TOTP code
     */
    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        $timeStep = 30; // 30 seconds
        $currentTime = floor(time() / $timeStep);
        
        // Check current time and adjacent windows
        for ($i = -$window; $i <= $window; $i++) {
            $time = $currentTime + $i;
            $expectedCode = self::generateCode($secret, $time);
            if (hash_equals($expectedCode, $code)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Generate a TOTP code for a given time
     */
    protected static function generateCode(string $secret, int $time): string
    {
        $key = self::base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $time);
        $hash = hash_hmac('sha1', $time, $key, true);
        $offset = ord($hash[19]) & 0xf;
        $code = (
            ((ord($hash[$offset + 0]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;
        return str_pad((string)$code, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Decode base32 string
     */
    protected static function base32Decode(string $secret): string
    {
        $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        $secret = strtoupper($secret);
        
        for ($i = 0; $i < strlen($secret); $i++) {
            $val = strpos($base32chars, $secret[$i]);
            $bits .= str_pad(decbin($val), 5, '0', STR_PAD_LEFT);
        }
        
        $return = '';
        for ($i = 0; $i + 8 <= strlen($bits); $i += 8) {
            $return .= chr(bindec(substr($bits, $i, 8)));
        }
        
        return $return;
    }

    /**
     * Generate backup codes
     */
    public static function generateBackupCodes(int $count = 10): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4)));
        }
        return $codes;
    }

    /**
     * Verify backup code
     */
    public static function verifyBackupCode(string $code, array $backupCodes): bool
    {
        $code = strtoupper(trim($code));
        $index = array_search($code, $backupCodes);
        if ($index !== false) {
            unset($backupCodes[$index]);
            return true;
        }
        return false;
    }
}

