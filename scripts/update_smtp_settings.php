<?php

declare(strict_types=1);

$app = require __DIR__ . '/../bootstrap/app.php';

use App\Services\Settings\SettingStore;

echo "=== Updating SMTP Settings ===\n\n";

$settings = new SettingStore();
$integrations = $settings->group('integrations');

// Update SMTP settings for Gmail
$newSettings = [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_encryption' => 'tls', // TLS for port 587
    'smtp_auth' => true,
    // Keep existing username and password
    'smtp_username' => $integrations['smtp_username'] ?? '',
    'smtp_password' => $integrations['smtp_password'] ?? '',
];

echo "Current SMTP Host: " . ($integrations['smtp_host'] ?? 'localhost') . "\n";
echo "Updating to: smtp.gmail.com\n\n";

$settings->updateGroup('integrations', $newSettings);

echo "✓ SMTP settings updated!\n\n";
echo "Updated Settings:\n";
echo "  SMTP Host: smtp.gmail.com\n";
echo "  SMTP Port: 587\n";
echo "  SMTP Encryption: TLS\n";
echo "  SMTP Username: " . ($newSettings['smtp_username'] ?: 'NOT SET') . "\n";
echo "  SMTP Password: " . ($newSettings['smtp_password'] ? '***SET***' : 'NOT SET') . "\n\n";

if (empty($newSettings['smtp_username']) || empty($newSettings['smtp_password'])) {
    echo "⚠️  WARNING: SMTP Username or Password is not set!\n";
    echo "Please update these in /staff/admin/settings\n\n";
}

echo "Note: For Gmail, make sure you:\n";
echo "  1. Have 2-Step Verification enabled\n";
echo "  2. Generated an App Password: https://myaccount.google.com/apppasswords\n";
echo "  3. Use the 16-character App Password (not your regular password)\n\n";

