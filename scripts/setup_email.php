<?php

declare(strict_types=1);

/**
 * Email Setup Helper Script
 * 
 * This script helps you configure email settings for the Hotela system.
 */

$app = require __DIR__ . '/../bootstrap/app.php';

use App\Services\Settings\SettingStore;

echo "=== Hotela Email Setup ===\n\n";

// Check PHPMailer (check multiple possible paths)
$possiblePaths = [
    BASE_PATH . '/vendor/phpmailer/phpmailer/src/PHPMailer.php',
    BASE_PATH . '/vendor/phpmailer/src/PHPMailer.php',
];

$phpmailerFound = false;
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        $phpmailerFound = true;
        break;
    }
}

if (!$phpmailerFound) {
    echo "❌ PHPMailer is NOT installed.\n\n";
    echo "To install PHPMailer, you have two options:\n\n";
    echo "Option 1: Using Composer (Recommended)\n";
    echo "  cd " . BASE_PATH . "\n";
    echo "  composer require phpmailer/phpmailer\n\n";
    echo "Option 2: Manual Installation\n";
    echo "  1. Download PHPMailer from: https://github.com/PHPMailer/PHPMailer\n";
    echo "  2. Extract to: " . BASE_PATH . "/vendor/phpmailer/\n";
    echo "  3. The structure should be: vendor/phpmailer/src/PHPMailer.php\n\n";
    exit(1);
} else {
    echo "✓ PHPMailer is installed\n\n";
}

$settings = new SettingStore();
$notifications = $settings->group('notifications');
$integrations = $settings->group('integrations');

echo "Current Email Configuration:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Email Enabled: " . (!empty($notifications['email_enabled']) ? '✓ YES' : '✗ NO') . "\n";
echo "From Email: " . ($notifications['default_from_email'] ?? 'NOT SET') . "\n";
echo "From Name: " . ($notifications['default_from_name'] ?? 'NOT SET') . "\n\n";

echo "SMTP Configuration:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$smtpHost = $integrations['smtp_host'] ?? 'localhost';
echo "SMTP Host: " . $smtpHost;
if ($smtpHost === 'localhost' || $smtpHost === '127.0.0.1') {
    echo " ⚠️  (Using PHP mail() - may not work on all servers)\n";
} else {
    echo " ✓\n";
}
echo "SMTP Port: " . ($integrations['smtp_port'] ?? '587') . "\n";
echo "SMTP Username: " . (!empty($integrations['smtp_username']) ? $integrations['smtp_username'] : 'NOT SET') . "\n";
echo "SMTP Password: " . (!empty($integrations['smtp_password']) ? '***SET***' : 'NOT SET') . "\n";
echo "SMTP Encryption: " . ($integrations['smtp_encryption'] ?? 'tls') . "\n";
echo "SMTP Auth: " . (!empty($integrations['smtp_auth']) ? 'YES' : 'NO') . "\n\n";

// Check for common issues
$issues = [];

if (empty($notifications['email_enabled'])) {
    $issues[] = "Email notifications are disabled in settings";
}

if ($smtpHost === 'localhost' && !empty($integrations['smtp_username'])) {
    $issues[] = "SMTP Host is set to 'localhost' but SMTP credentials are configured. Change SMTP Host to your email provider's SMTP server (e.g., smtp.gmail.com for Gmail)";
}

if (!empty($integrations['smtp_username']) && empty($integrations['smtp_password'])) {
    $issues[] = "SMTP Username is set but password is missing";
}

if (!empty($integrations['smtp_username']) && ($smtpHost === 'localhost' || $smtpHost === '127.0.0.1')) {
    $issues[] = "SMTP credentials are configured but host is localhost. Update SMTP Host in admin settings.";
}

if (empty($issues)) {
    echo "✓ Configuration looks good!\n\n";
    echo "To test email sending, run:\n";
    echo "  php scripts/test_email.php your-email@example.com\n";
} else {
    echo "⚠️  Issues Found:\n";
    foreach ($issues as $issue) {
        echo "  - $issue\n";
    }
    echo "\n";
    echo "To fix these issues:\n";
    echo "  1. Go to: /staff/admin/settings\n";
    echo "  2. Navigate to the 'Integrations' or 'Notifications' section\n";
    echo "  3. Update the email/SMTP settings\n\n";
    
    if ($smtpHost === 'localhost' && !empty($integrations['smtp_username'])) {
        echo "For Gmail SMTP, use these settings:\n";
        echo "  SMTP Host: smtp.gmail.com\n";
        echo "  SMTP Port: 587 (for TLS) or 465 (for SSL)\n";
        echo "  SMTP Encryption: TLS (for port 587) or SSL (for port 465)\n";
        echo "  SMTP Username: your-email@gmail.com\n";
        echo "  SMTP Password: Your Gmail App Password (not your regular password)\n";
        echo "  Enable SMTP Authentication: YES\n\n";
        echo "Note: For Gmail, you need to:\n";
        echo "  1. Enable 2-Step Verification\n";
        echo "  2. Generate an App Password: https://myaccount.google.com/apppasswords\n";
        echo "  3. Use the App Password (16 characters) as your SMTP password\n\n";
    }
}

