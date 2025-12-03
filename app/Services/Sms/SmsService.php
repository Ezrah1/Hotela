<?php

namespace App\Services\Sms;

use App\Services\Settings\SettingStore;

class SmsService
{
    protected SettingStore $settings;

    public function __construct(?SettingStore $settings = null)
    {
        $this->settings = $settings ?? new SettingStore();
    }

    /**
     * Send an SMS message
     */
    public function send(string $to, string $message): bool
    {
        $notifications = $this->settings->group('notifications');
        $integrations = $this->settings->group('integrations');
        
        // Check if SMS is enabled
        if (empty($notifications['sms_enabled'])) {
            error_log('SMS sending is disabled in settings');
            return false;
        }

        $gateway = $integrations['sms_gateway'] ?? '';
        
        if (empty($gateway)) {
            error_log('SMS gateway not configured');
            return false;
        }

        try {
            // Normalize phone number (remove spaces, dashes, etc.)
            $phone = preg_replace('/[^0-9+]/', '', $to);
            
            // Ensure phone starts with country code (add 254 for Kenya if missing)
            $startsWithPlus = substr($phone, 0, 1) === '+';
            $startsWith254 = substr($phone, 0, 3) === '254';
            if (!$startsWithPlus && !$startsWith254) {
                // If it's a 10-digit number starting with 0, replace 0 with 254
                if (strlen($phone) === 10 && str_starts_with($phone, '0')) {
                    $phone = '254' . substr($phone, 1);
                } elseif (strlen($phone) === 9) {
                    // 9-digit number, add 254
                    $phone = '254' . $phone;
                }
            }
            
            // Remove + if present
            $phone = str_replace('+', '', $phone);

            // Send via configured gateway
            if (strtolower($gateway) === 'africastalking') {
                return $this->sendViaAfricasTalking($phone, $message);
            } else {
                // Generic SMS gateway - log for now
                error_log("SMS Gateway '{$gateway}' not yet implemented. Message to {$phone}: {$message}");
                return false;
            }
        } catch (\Exception $e) {
            error_log('SMS service error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send SMS via Africa's Talking
     */
    protected function sendViaAfricasTalking(string $phone, string $message): bool
    {
        $integrations = $this->settings->group('integrations');
        
        $apiKey = $integrations['africastalking_api_key'] ?? env('AFRICASTALKING_API_KEY', '');
        $username = $integrations['africastalking_username'] ?? env('AFRICASTALKING_USERNAME', '');
        $senderId = $integrations['africastalking_sender_id'] ?? env('AFRICASTALKING_SENDER_ID', '');
        
        if (empty($apiKey) || empty($username)) {
            error_log('Africa\'s Talking API credentials not configured');
            return false;
        }

        try {
            // Format phone number for Africa's Talking (must start with +)
            $formattedPhone = '+' . $phone;
            
            $url = 'https://api.africastalking.com/version1/messaging';
            
            $data = [
                'username' => $username,
                'to' => $formattedPhone,
                'message' => $message,
            ];
            
            if (!empty($senderId)) {
                $data['from'] = $senderId;
            }
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'apiKey: ' . $apiKey,
                'Content-Type: application/x-www-form-urlencoded',
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 201 || $httpCode === 200) {
                return true;
            } else {
                error_log('Africa\'s Talking SMS API error: HTTP ' . $httpCode . ' - ' . $response);
                return false;
            }
        } catch (\Exception $e) {
            error_log('Africa\'s Talking SMS error: ' . $e->getMessage());
            return false;
        }
    }
}

