<?php

namespace App\Services\Payments;

use App\Services\Settings\SettingStore;
use RuntimeException;

class MpesaService
{
    protected SettingStore $settings;
    protected string $baseUrl;
    protected ?string $accessToken = null;

    public function __construct()
    {
        $this->settings = new SettingStore();
        $settings = $this->settings->all();
        $mpesaSettings = $settings['payment_gateways']['mpesa'] ?? [];
        
        // Determine base URL based on environment
        $environment = $mpesaSettings['environment'] ?? 'sandbox';
        $this->baseUrl = $environment === 'production' 
            ? 'https://api.safaricom.co.ke' 
            : 'https://sandbox.safaricom.co.ke';
    }

    /**
     * Get OAuth access token
     */
    public function getAccessToken(): string
    {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        $settings = $this->settings->all();
        $mpesaSettings = $settings['payment_gateways']['mpesa'] ?? [];
        
        $consumerKey = $mpesaSettings['consumer_key'] ?? '';
        $consumerSecret = $mpesaSettings['consumer_secret'] ?? '';

        if (empty($consumerKey) || empty($consumerSecret)) {
            throw new RuntimeException('M-Pesa credentials not configured. Please set Consumer Key and Consumer Secret in Payment Gateways settings.');
        }

        $url = $this->baseUrl . '/oauth/v1/generate?grant_type=client_credentials';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . base64_encode($consumerKey . ':' . $consumerSecret)
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new RuntimeException('Failed to get M-Pesa access token. HTTP Code: ' . $httpCode . '. Response: ' . $response);
        }

        $data = json_decode($response, true);
        
        if (!isset($data['access_token'])) {
            throw new RuntimeException('Invalid response from M-Pesa OAuth: ' . $response);
        }

        $this->accessToken = $data['access_token'];
        return $this->accessToken;
    }

    /**
     * Initiate STK Push (Lipa na M-Pesa Online)
     */
    public function stkPush(string $phoneNumber, float $amount, string $accountReference, string $transactionDesc, ?string $callbackUrl = null): array
    {
        $settings = $this->settings->all();
        $mpesaSettings = $settings['payment_gateways']['mpesa'] ?? [];
        
        $shortcode = $mpesaSettings['shortcode'] ?? '';
        $passkey = $mpesaSettings['passkey'] ?? '';
        $paybillNumber = $mpesaSettings['paybill_number'] ?? $shortcode;

        if (empty($shortcode) || empty($passkey)) {
            throw new RuntimeException('M-Pesa Shortcode and Passkey must be configured.');
        }

        // Format phone number (remove + and ensure it starts with 254)
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        if (!str_starts_with($phoneNumber, '254')) {
            if (str_starts_with($phoneNumber, '0')) {
                $phoneNumber = '254' . substr($phoneNumber, 1);
            } else {
                $phoneNumber = '254' . $phoneNumber;
            }
        }

        // Generate timestamp
        $timestamp = date('YmdHis');
        
        // Generate password (Base64 encoded)
        $password = base64_encode($shortcode . $passkey . $timestamp);

        // Generate callback URL if not provided
        if ($callbackUrl === null) {
            $callbackUrl = $this->getCallbackUrl();
        }

        $url = $this->baseUrl . '/mpesa/stkpush/v1/processrequest';
        
        // Validate callback URL format
        if (!filter_var($callbackUrl, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('Invalid callback URL format: ' . $callbackUrl);
        }
        
        // Ensure callback URL is HTTPS
        if (!str_starts_with(strtolower($callbackUrl), 'https://')) {
            throw new RuntimeException('M-Pesa callback URL must use HTTPS. Current URL: ' . $callbackUrl);
        }
        
        $payload = [
            'BusinessShortCode' => $shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => (int)round($amount),
            'PartyA' => $phoneNumber,
            'PartyB' => $shortcode,
            'PhoneNumber' => $phoneNumber,
            'CallBackURL' => $callbackUrl,
            'AccountReference' => $accountReference,
            'TransactionDesc' => $transactionDesc
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->getAccessToken(),
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $errorDetails = '';
            $responseData = json_decode($response, true);
            if ($responseData) {
                $errorDetails = ' Error: ' . ($responseData['errorMessage'] ?? $responseData['errorCode'] ?? 'Unknown error');
                if (isset($responseData['errorMessage']) && str_contains($responseData['errorMessage'], 'CallBackURL')) {
                    $errorDetails .= ' | Callback URL used: ' . $callbackUrl;
                }
            }
            throw new RuntimeException('STK Push failed. HTTP Code: ' . $httpCode . '. Response: ' . $response . $errorDetails);
        }

        $data = json_decode($response, true);
        
        if (!isset($data['ResponseCode']) || $data['ResponseCode'] !== '0') {
            $errorMessage = $data['errorMessage'] ?? $data['CustomerMessage'] ?? 'Unknown error';
            throw new RuntimeException('M-Pesa STK Push error: ' . $errorMessage);
        }

        return [
            'success' => true,
            'merchant_request_id' => $data['MerchantRequestID'] ?? '',
            'checkout_request_id' => $data['CheckoutRequestID'] ?? '',
            'response_code' => $data['ResponseCode'] ?? '',
            'customer_message' => $data['CustomerMessage'] ?? '',
            'data' => $data
        ];
    }

    /**
     * Query STK Push status
     */
    public function queryStkStatus(string $checkoutRequestId): array
    {
        $settings = $this->settings->all();
        $mpesaSettings = $settings['payment_gateways']['mpesa'] ?? [];
        
        $shortcode = $mpesaSettings['shortcode'] ?? '';
        $passkey = $mpesaSettings['passkey'] ?? '';

        if (empty($shortcode) || empty($passkey)) {
            throw new RuntimeException('M-Pesa Shortcode and Passkey must be configured.');
        }

        $timestamp = date('YmdHis');
        $password = base64_encode($shortcode . $passkey . $timestamp);

        $url = $this->baseUrl . '/mpesa/stkpushquery/v1/query';
        
        $payload = [
            'BusinessShortCode' => $shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $checkoutRequestId
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->getAccessToken(),
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new RuntimeException('STK Query failed. HTTP Code: ' . $httpCode . '. Response: ' . $response);
        }

        $data = json_decode($response, true);
        
        return [
            'success' => isset($data['ResponseCode']) && $data['ResponseCode'] === '0',
            'response_code' => $data['ResponseCode'] ?? '',
            'result_code' => $data['ResultCode'] ?? '',
            'result_desc' => $data['ResultDesc'] ?? '',
            'data' => $data
        ];
    }

    /**
     * Get the callback URL for M-Pesa (public method for display purposes)
     */
    public function getCallbackUrlForDisplay(): string
    {
        return $this->getCallbackUrl();
    }

    /**
     * Get the callback URL for M-Pesa
     */
    protected function getCallbackUrl(): string
    {
        $settings = $this->settings->all();
        $mpesaSettings = $settings['payment_gateways']['mpesa'] ?? [];
        
        // Check if custom callback URL is configured
        if (!empty($mpesaSettings['callback_url'])) {
            $url = trim($mpesaSettings['callback_url']);
            // Ensure it's a valid HTTPS URL
            if (filter_var($url, FILTER_VALIDATE_URL) && str_starts_with(strtolower($url), 'https://')) {
                return $url;
            }
        }
        
        // Generate callback URL from current request
        $protocol = 'https';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        
        // Check if we're behind a proxy (Cloudflare, etc.)
        $forwardedHost = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? '';
        if ($forwardedHost) {
            $host = $forwardedHost;
        }
        
        // Check if HTTPS is being used (even behind proxy)
        $isHttps = false;
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            $isHttps = true;
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            $isHttps = true;
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
            $isHttps = true;
        }
        
        if (!$isHttps) {
            // Force HTTPS for M-Pesa callback (required by Safaricom)
            $protocol = 'https';
        }
        
        // Remove port if present (M-Pesa doesn't like ports in callback URLs)
        $host = preg_replace('/:\d+$/', '', $host);
        
        // Build the callback URL - use direct path since API routes are at root level
        // The API endpoint is /api/mpesa/callback (defined in routes/platform.php)
        $path = 'api/mpesa/callback';
        
        // Ensure no double slashes
        $url = $protocol . '://' . $host . '/' . ltrim($path, '/');
        
        // Validate the URL format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            // Fallback: try to construct from APP_URL if available
            $appUrl = config('app.url', '');
            if ($appUrl && filter_var($appUrl, FILTER_VALIDATE_URL)) {
                $parsed = parse_url($appUrl);
                $url = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? $host) . '/' . ltrim($path, '/');
            } else {
                // Last resort: use the host we detected
                $url = 'https://' . $host . '/' . ltrim($path, '/');
            }
        }
        
        return $url;
    }

    /**
     * Verify M-Pesa configuration
     */
    public function verifyConfiguration(): array
    {
        $settings = $this->settings->all();
        $mpesaSettings = $settings['payment_gateways']['mpesa'] ?? [];
        
        $errors = [];
        
        if (empty($mpesaSettings['consumer_key'])) {
            $errors[] = 'Consumer Key is required';
        }
        
        if (empty($mpesaSettings['consumer_secret'])) {
            $errors[] = 'Consumer Secret is required';
        }
        
        if (empty($mpesaSettings['shortcode'])) {
            $errors[] = 'Shortcode is required';
        }
        
        if (empty($mpesaSettings['passkey'])) {
            $errors[] = 'Passkey is required';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'environment' => $mpesaSettings['environment'] ?? 'sandbox',
            'callback_url' => $this->getCallbackUrl()
        ];
    }
}

