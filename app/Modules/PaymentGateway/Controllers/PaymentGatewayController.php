<?php

namespace App\Modules\PaymentGateway\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\Settings\SettingStore;
use App\Support\Auth;

class PaymentGatewayController extends Controller
{
    protected SettingStore $store;

    public function __construct()
    {
        $this->store = new SettingStore();
    }

    public function index(Request $request): void
    {
        Auth::requireRoles(['director', 'admin']);
        
        $gateway = $request->input('gateway', 'mpesa');
        $settings = $this->store->all();
        $gatewaySettings = $settings['payment_gateways'][$gateway] ?? [];

        $this->view('dashboard/payment-gateway/index', [
            'activeGateway' => $gateway,
            'gatewaySettings' => $gatewaySettings,
            'settings' => $settings,
            'pageTitle' => 'Payment Gateways | Hotela',
        ]);
    }

    public function update(Request $request): void
    {
        Auth::requireRoles(['director', 'admin']);
        
        $gateway = $request->input('gateway');
        $payload = $request->all();
        unset($payload['gateway']);

        if (!$gateway) {
            http_response_code(400);
            echo 'Invalid gateway.';
            return;
        }

        $sanitized = $this->sanitizeValues($payload);
        
        // Get existing payment gateways settings
        $settings = $this->store->all();
        $paymentGateways = $settings['payment_gateways'] ?? [];
        $paymentGateways[$gateway] = $sanitized;
        
        // Save each gateway as a nested structure
        // The SettingStore will JSON encode the nested array
        $this->store->updateGroup('payment_gateways', [$gateway => $sanitized]);

        // Check if request came from settings page
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if (strpos($referer, 'admin/settings') !== false || strpos($referer, 'staff/admin/settings') !== false) {
            // Use the correct route from platform.php
            header('Location: ' . base_url('staff/admin/settings?tab=payment-gateway&gateway=' . urlencode($gateway) . '&success=' . urlencode('Payment gateway settings saved successfully')));
        } else {
            header('Location: ' . base_url('staff/dashboard/payment-gateway?gateway=' . urlencode($gateway) . '&success=' . urlencode('Payment gateway settings saved successfully')));
        }
    }

    protected function sanitizeValues(array $values): array
    {
        return array_map(function ($value) {
            if (is_array($value)) {
                return $this->sanitizeValues($value);
            }

            return is_string($value) ? trim($value) : $value;
        }, $values);
    }
}

