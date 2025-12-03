<?php
/**
 * Script to configure email From address settings
 * 
 * Note: When using Gmail SMTP, Gmail requires the "From" address to match
 * the authenticated email address unless you've configured "Send mail as" in Gmail.
 * 
 * To send from info@joyceresorts.com or noreply@joyceresorts.com:
 * 1. Configure "Send mail as" in Gmail Settings > Accounts and Import
 * 2. Or use a different SMTP service that allows custom From addresses
 * 3. Or use info@joyceresorts.com as the SMTP username (if you have access)
 */

require_once __DIR__ . '/../bootstrap/app.php';

use App\Services\Settings\SettingStore;

$settings = new SettingStore();

echo "Current Email Configuration:\n";
echo "============================\n\n";

$notifications = $settings->group('notifications');
$integrations = $settings->group('integrations');
$branding = $settings->group('branding');

echo "From Email: " . ($notifications['default_from_email'] ?? 'Not set') . "\n";
echo "From Name: " . ($notifications['default_from_name'] ?? 'Not set') . "\n";
echo "SMTP Host: " . ($integrations['smtp_host'] ?? 'Not set') . "\n";
echo "SMTP Username: " . ($integrations['smtp_username'] ?? 'Not set') . "\n";
echo "Branding Contact Email: " . ($branding['contact_email'] ?? 'Not set') . "\n\n";

echo "Current Behavior:\n";
echo "=================\n";
$smtpHost = $integrations['smtp_host'] ?? '';
$smtpUser = $integrations['smtp_username'] ?? '';
$isGmail = strpos(strtolower($smtpHost), 'gmail') !== false || strpos(strtolower($smtpUser), 'gmail') !== false;

if ($isGmail) {
    echo "⚠️  Using Gmail SMTP detected.\n";
    echo "Gmail will show the authenticated email ({$smtpUser}) as the sender.\n";
    echo "To send from a different address:\n";
    echo "1. Go to Gmail Settings > Accounts and Import > Send mail as\n";
    echo "2. Add info@joyceresorts.com or noreply@joyceresorts.com\n";
    echo "3. Verify the address and set it as default\n";
    echo "\nAlternatively, use the desired email as SMTP username if you have access.\n";
} else {
    echo "✓ Not using Gmail - custom From address should work.\n";
}

echo "\n";
echo "To update email settings, use the admin panel or update storage/settings.json\n";

