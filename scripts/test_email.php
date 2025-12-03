<?php

declare(strict_types=1);

$app = require __DIR__ . '/../bootstrap/app.php';

use App\Services\Email\EmailService;
use App\Services\Settings\SettingStore;

echo "=== Email Configuration Test ===\n\n";

$settings = new SettingStore();
$notifications = $settings->group('notifications');
$integrations = $settings->group('integrations');

echo "Email Settings:\n";
echo "  Email Enabled: " . (!empty($notifications['email_enabled']) ? 'YES' : 'NO') . "\n";
echo "  From Email: " . ($notifications['default_from_email'] ?? 'NOT SET') . "\n";
echo "  From Name: " . ($notifications['default_from_name'] ?? 'NOT SET') . "\n\n";

echo "SMTP Settings:\n";
echo "  SMTP Host: " . ($integrations['smtp_host'] ?? 'localhost (using PHP mail())') . "\n";
echo "  SMTP Port: " . ($integrations['smtp_port'] ?? '587') . "\n";
echo "  SMTP Username: " . (!empty($integrations['smtp_username']) ? $integrations['smtp_username'] : 'NOT SET') . "\n";
echo "  SMTP Password: " . (!empty($integrations['smtp_password']) ? '***SET***' : 'NOT SET') . "\n";
echo "  SMTP Encryption: " . ($integrations['smtp_encryption'] ?? 'tls') . "\n";
echo "  SMTP Auth: " . (!empty($integrations['smtp_auth']) ? 'YES' : 'NO') . "\n\n";

// Check if PHPMailer is available (try loading it first)
$possiblePaths = [
    BASE_PATH . '/vendor/phpmailer/phpmailer/src/PHPMailer.php',
    BASE_PATH . '/vendor/phpmailer/src/PHPMailer.php',
];

$phpmailerFound = false;
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $basePath = dirname($path);
        require_once $basePath . '/SMTP.php';
        require_once $basePath . '/Exception.php';
        $phpmailerFound = true;
        break;
    }
}

if (!class_exists('\PHPMailer\PHPMailer\PHPMailer') && !$phpmailerFound) {
    echo "ERROR: PHPMailer is not installed!\n";
    echo "Please install it with: composer require phpmailer/phpmailer\n";
    exit(1);
} else {
    echo "PHPMailer: INSTALLED ✓\n\n";
}

// Test email sending
if (isset($argv[1])) {
    $testEmail = $argv[1];
    echo "Testing email to: {$testEmail}\n";
    
    $emailService = new EmailService();
    $result = $emailService->send(
        $testEmail,
        'Test Email from Hotela',
        '<h1>Test Email</h1><p>If you receive this, email is working correctly!</p>',
        null,
        true
    );
    
    if ($result) {
        echo "\n✓ Email sent successfully!\n";
    } else {
        echo "\n✗ Email sending failed. Check error logs for details.\n";
        echo "Check PHP error log for SMTP debug information.\n";
    }
} else {
    echo "To test email sending, run:\n";
    echo "  php scripts/test_email.php your-email@example.com\n";
}

