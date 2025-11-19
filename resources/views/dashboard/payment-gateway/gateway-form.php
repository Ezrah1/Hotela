<?php
$gateways = [
    'mpesa' => [
        'name' => 'M-Pesa',
        'icon' => '💰',
        'description' => 'Mobile money payment gateway for Kenya and East Africa'
    ],
    'card' => [
        'name' => 'Card Payments',
        'icon' => '💳',
        'description' => 'Credit and debit card processing'
    ],
    'stripe' => [
        'name' => 'Stripe',
        'icon' => '💳',
        'description' => 'Online payment processing platform'
    ],
    'paypal' => [
        'name' => 'PayPal',
        'icon' => '💳',
        'description' => 'Global online payment system'
    ],
    'bank' => [
        'name' => 'Bank Transfer',
        'icon' => '🏦',
        'description' => 'Direct bank transfer payments'
    ],
    'cash' => [
        'name' => 'Cash',
        'icon' => '💵',
        'description' => 'Cash payment method'
    ],
];

$activeGateway = $gateway ?? 'mpesa';
$gatewaySettings = $gatewaySettings ?? [];
$isInSettings = $isInSettings ?? false;
$settingsBaseUrl = $isInSettings ? base_url('staff/admin/settings?tab=payment-gateway') : base_url('staff/dashboard/payment-gateway');
?>

<div class="payment-gateway-container">
    <div class="gateway-tabs">
        <?php foreach ($gateways as $key => $gatewayInfo): ?>
            <a href="<?= $settingsBaseUrl . '&gateway=' . urlencode($key); ?>" 
               class="gateway-tab <?= $activeGateway === $key ? 'active' : ''; ?>">
                <span class="gateway-icon"><?= htmlspecialchars($gatewayInfo['icon']); ?></span>
                <span class="gateway-name"><?= htmlspecialchars($gatewayInfo['name']); ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="gateway-content">
        <form method="post" action="<?= base_url('staff/dashboard/payment-gateway/update'); ?>" class="gateway-form">
            <input type="hidden" name="gateway" value="<?= htmlspecialchars($activeGateway); ?>">
            
            <div class="gateway-header">
                <div class="gateway-info">
                    <h2>
                        <span class="gateway-icon"><?= htmlspecialchars($gateways[$activeGateway]['icon']); ?></span>
                        <?= htmlspecialchars($gateways[$activeGateway]['name']); ?>
                    </h2>
                    <p class="gateway-description"><?= htmlspecialchars($gateways[$activeGateway]['description']); ?></p>
                </div>
                <label class="toggle-switch">
                    <input type="hidden" name="enabled" value="0">
                    <input type="checkbox" name="enabled" value="1" <?= !empty($gatewaySettings['enabled']) ? 'checked' : ''; ?>>
                    <span class="toggle-slider"></span>
                    <span class="toggle-label">Enable Gateway</span>
                </label>
            </div>

            <div class="gateway-fields">
                <?php if ($activeGateway === 'mpesa'): ?>
                    <fieldset>
                        <legend>M-Pesa Configuration</legend>
                        <label>
                            <span>PayBill Number / Till Number</span>
                            <input type="text" name="paybill_number" 
                                   value="<?= htmlspecialchars($gatewaySettings['paybill_number'] ?? ''); ?>" 
                                   placeholder="123456">
                        </label>
                        <label>
                            <span>Consumer Key</span>
                            <input type="text" name="consumer_key" 
                                   value="<?= htmlspecialchars($gatewaySettings['consumer_key'] ?? ''); ?>" 
                                   placeholder="Your M-Pesa consumer key">
                        </label>
                        <label>
                            <span>Consumer Secret</span>
                            <input type="password" name="consumer_secret" 
                                   value="<?= htmlspecialchars($gatewaySettings['consumer_secret'] ?? ''); ?>" 
                                   placeholder="Your M-Pesa consumer secret">
                        </label>
                        <label>
                            <span>Passkey</span>
                            <input type="password" name="passkey" 
                                   value="<?= htmlspecialchars($gatewaySettings['passkey'] ?? ''); ?>" 
                                   placeholder="Your M-Pesa passkey">
                        </label>
                        <label>
                            <span>Environment</span>
                            <select name="environment">
                                <option value="sandbox" <?= ($gatewaySettings['environment'] ?? 'sandbox') === 'sandbox' ? 'selected' : ''; ?>>Sandbox (Testing)</option>
                                <option value="production" <?= ($gatewaySettings['environment'] ?? '') === 'production' ? 'selected' : ''; ?>>Production</option>
                            </select>
                        </label>
                        <label>
                            <span>Shortcode</span>
                            <input type="text" name="shortcode" 
                                   value="<?= htmlspecialchars($gatewaySettings['shortcode'] ?? ''); ?>" 
                                   placeholder="Your business shortcode">
                        </label>
                    </fieldset>

                <?php elseif ($activeGateway === 'card'): ?>
                    <fieldset>
                        <legend>Card Payment Configuration</legend>
                        <label>
                            <span>Merchant ID</span>
                            <input type="text" name="merchant_id" 
                                   value="<?= htmlspecialchars($gatewaySettings['merchant_id'] ?? ''); ?>" 
                                   placeholder="Your merchant ID">
                        </label>
                        <label>
                            <span>API Key</span>
                            <input type="text" name="api_key" 
                                   value="<?= htmlspecialchars($gatewaySettings['api_key'] ?? ''); ?>" 
                                   placeholder="Your API key">
                        </label>
                        <label>
                            <span>API Secret</span>
                            <input type="password" name="api_secret" 
                                   value="<?= htmlspecialchars($gatewaySettings['api_secret'] ?? ''); ?>" 
                                   placeholder="Your API secret">
                        </label>
                        <label>
                            <span>Environment</span>
                            <select name="environment">
                                <option value="sandbox" <?= ($gatewaySettings['environment'] ?? 'sandbox') === 'sandbox' ? 'selected' : ''; ?>>Sandbox (Testing)</option>
                                <option value="production" <?= ($gatewaySettings['environment'] ?? '') === 'production' ? 'selected' : ''; ?>>Production</option>
                            </select>
                        </label>
                        <label>
                            <span>Supported Card Types</span>
                            <div class="checkbox-group">
                                <label class="checkbox">
                                    <input type="checkbox" name="card_types[]" value="visa" <?= in_array('visa', $gatewaySettings['card_types'] ?? []) ? 'checked' : ''; ?>>
                                    <span>Visa</span>
                                </label>
                                <label class="checkbox">
                                    <input type="checkbox" name="card_types[]" value="mastercard" <?= in_array('mastercard', $gatewaySettings['card_types'] ?? []) ? 'checked' : ''; ?>>
                                    <span>Mastercard</span>
                                </label>
                                <label class="checkbox">
                                    <input type="checkbox" name="card_types[]" value="amex" <?= in_array('amex', $gatewaySettings['card_types'] ?? []) ? 'checked' : ''; ?>>
                                    <span>American Express</span>
                                </label>
                            </div>
                        </label>
                    </fieldset>

                <?php elseif ($activeGateway === 'stripe'): ?>
                    <fieldset>
                        <legend>Stripe Configuration</legend>
                        <label>
                            <span>Publishable Key</span>
                            <input type="text" name="publishable_key" 
                                   value="<?= htmlspecialchars($gatewaySettings['publishable_key'] ?? ''); ?>" 
                                   placeholder="pk_test_...">
                        </label>
                        <label>
                            <span>Secret Key</span>
                            <input type="password" name="secret_key" 
                                   value="<?= htmlspecialchars($gatewaySettings['secret_key'] ?? ''); ?>" 
                                   placeholder="sk_test_...">
                        </label>
                        <label>
                            <span>Webhook Secret</span>
                            <input type="password" name="webhook_secret" 
                                   value="<?= htmlspecialchars($gatewaySettings['webhook_secret'] ?? ''); ?>" 
                                   placeholder="whsec_...">
                        </label>
                        <label>
                            <span>Environment</span>
                            <select name="environment">
                                <option value="test" <?= ($gatewaySettings['environment'] ?? 'test') === 'test' ? 'selected' : ''; ?>>Test Mode</option>
                                <option value="live" <?= ($gatewaySettings['environment'] ?? '') === 'live' ? 'selected' : ''; ?>>Live Mode</option>
                            </select>
                        </label>
                        <label>
                            <span>Currency</span>
                            <input type="text" name="currency" 
                                   value="<?= htmlspecialchars($gatewaySettings['currency'] ?? 'USD'); ?>" 
                                   placeholder="USD" maxlength="3">
                        </label>
                    </fieldset>

                <?php elseif ($activeGateway === 'paypal'): ?>
                    <fieldset>
                        <legend>PayPal Configuration</legend>
                        <label>
                            <span>Client ID</span>
                            <input type="text" name="client_id" 
                                   value="<?= htmlspecialchars($gatewaySettings['client_id'] ?? ''); ?>" 
                                   placeholder="Your PayPal client ID">
                        </label>
                        <label>
                            <span>Client Secret</span>
                            <input type="password" name="client_secret" 
                                   value="<?= htmlspecialchars($gatewaySettings['client_secret'] ?? ''); ?>" 
                                   placeholder="Your PayPal client secret">
                        </label>
                        <label>
                            <span>Environment</span>
                            <select name="environment">
                                <option value="sandbox" <?= ($gatewaySettings['environment'] ?? 'sandbox') === 'sandbox' ? 'selected' : ''; ?>>Sandbox (Testing)</option>
                                <option value="production" <?= ($gatewaySettings['environment'] ?? '') === 'production' ? 'selected' : ''; ?>>Production</option>
                            </select>
                        </label>
                        <label>
                            <span>Currency</span>
                            <input type="text" name="currency" 
                                   value="<?= htmlspecialchars($gatewaySettings['currency'] ?? 'USD'); ?>" 
                                   placeholder="USD" maxlength="3">
                        </label>
                    </fieldset>

                <?php elseif ($activeGateway === 'bank'): ?>
                    <fieldset>
                        <legend>Bank Transfer Configuration</legend>
                        <label>
                            <span>Bank Name</span>
                            <input type="text" name="bank_name" 
                                   value="<?= htmlspecialchars($gatewaySettings['bank_name'] ?? ''); ?>" 
                                   placeholder="Your bank name">
                        </label>
                        <label>
                            <span>Account Name</span>
                            <input type="text" name="account_name" 
                                   value="<?= htmlspecialchars($gatewaySettings['account_name'] ?? ''); ?>" 
                                   placeholder="Account holder name">
                        </label>
                        <label>
                            <span>Account Number</span>
                            <input type="text" name="account_number" 
                                   value="<?= htmlspecialchars($gatewaySettings['account_number'] ?? ''); ?>" 
                                   placeholder="Account number">
                        </label>
                        <label>
                            <span>SWIFT / BIC Code</span>
                            <input type="text" name="swift_code" 
                                   value="<?= htmlspecialchars($gatewaySettings['swift_code'] ?? ''); ?>" 
                                   placeholder="SWIFT or BIC code">
                        </label>
                        <label>
                            <span>IBAN</span>
                            <input type="text" name="iban" 
                                   value="<?= htmlspecialchars($gatewaySettings['iban'] ?? ''); ?>" 
                                   placeholder="International Bank Account Number">
                        </label>
                        <label>
                            <span>Branch Code</span>
                            <input type="text" name="branch_code" 
                                   value="<?= htmlspecialchars($gatewaySettings['branch_code'] ?? ''); ?>" 
                                   placeholder="Branch code">
                        </label>
                    </fieldset>

                <?php elseif ($activeGateway === 'cash'): ?>
                    <fieldset>
                        <legend>Cash Payment Configuration</legend>
                        <label>
                            <span>Default Currency</span>
                            <input type="text" name="currency" 
                                   value="<?= htmlspecialchars($gatewaySettings['currency'] ?? 'KES'); ?>" 
                                   placeholder="KES" maxlength="3">
                        </label>
                        <label class="checkbox">
                            <input type="hidden" name="require_receipt" value="0">
                            <input type="checkbox" name="require_receipt" value="1" <?= !empty($gatewaySettings['require_receipt']) ? 'checked' : ''; ?>>
                            <span>Require receipt for cash payments</span>
                        </label>
                        <label class="checkbox">
                            <input type="hidden" name="allow_change" value="0">
                            <input type="checkbox" name="allow_change" value="1" <?= !empty($gatewaySettings['allow_change']) ? 'checked' : ''; ?>>
                            <span>Allow change to be given</span>
                        </label>
                        <label>
                            <span>Maximum Cash Amount</span>
                            <input type="number" step="0.01" name="max_amount" 
                                   value="<?= htmlspecialchars($gatewaySettings['max_amount'] ?? ''); ?>" 
                                   placeholder="Maximum amount for cash payments">
                        </label>
                    </fieldset>
                <?php endif; ?>
            </div>

            <div class="gateway-actions">
                <button type="submit" class="btn btn-primary">Save Configuration</button>
                <?php if ($activeGateway === 'mpesa'): ?>
                    <a href="<?= base_url('staff/dashboard/payment-gateway/mpesa-test'); ?>" class="btn" style="background: #28a745; color: white;">Test M-Pesa (Sandbox)</a>
                <?php endif; ?>
                <?php if ($isInSettings): ?>
                    <a href="<?= base_url('staff/admin/settings?tab=payment-gateway'); ?>" class="btn btn-secondary">Cancel</a>
                <?php else: ?>
                    <a href="<?= base_url('staff/dashboard/payment-gateway'); ?>" class="btn btn-secondary">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

