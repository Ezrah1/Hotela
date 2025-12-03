<?php
ob_start();
$error = $_GET['error'] ?? null;
$availableMethods = $availableMethods ?? [];
?>
<div>
    <div style="margin-bottom: 1.5rem;">
        <a href="<?= base_url('guest/order?ref=' . urlencode($order['reference'])); ?>" class="guest-btn guest-btn-outline" style="display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
            ← Back to Order
        </a>
        <h1 class="guest-page-title">Pay for Order</h1>
    </div>

    <?php if ($error): ?>
        <div style="padding: 1rem 1.5rem; background: #fee2e2; color: #991b1b; border-radius: 0.5rem; margin-bottom: 1.5rem; border-left: 4px solid #dc2626;">
            <strong>✗ <?= htmlspecialchars($error); ?></strong>
        </div>
    <?php endif; ?>

    <div class="guest-card">
        <div style="margin-bottom: 2rem;">
            <h2 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem;">Order Summary</h2>
            <div style="display: grid; gap: 0.75rem;">
                <div style="display: flex; justify-content: space-between; padding: 0.75rem; background: var(--guest-bg); border-radius: 0.5rem;">
                    <span style="color: var(--guest-text-light);">Order Reference:</span>
                    <strong><?= htmlspecialchars($order['reference']); ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.75rem; background: var(--guest-bg); border-radius: 0.5rem;">
                    <span style="color: var(--guest-text-light);">Total Amount:</span>
                    <strong style="font-size: 1.125rem; color: var(--guest-primary);">KES <?= number_format((float)($order['total'] ?? 0), 2); ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.75rem; background: var(--guest-bg); border-radius: 0.5rem;">
                    <span style="color: var(--guest-text-light);">Current Payment Method:</span>
                    <strong style="text-transform: capitalize;"><?= htmlspecialchars(str_replace('_', ' ', $order['payment_type'] ?? 'cash')); ?></strong>
                </div>
            </div>
        </div>

        <div style="padding-top: 1.5rem; border-top: 1px solid var(--guest-border);">
            <h2 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem;">Select Payment Method</h2>
            <p style="color: var(--guest-text-light); font-size: 0.9rem; margin-bottom: 1.5rem;">
                Choose a digital payment method to complete your order payment.
            </p>

            <?php if (empty($availableMethods)): ?>
                <div style="padding: 1.5rem; background: #fef3c7; border: 1px solid #fbbf24; border-radius: 0.5rem; color: #92400e; margin-bottom: 1.5rem;">
                    <p style="margin: 0 0 0.5rem 0; font-weight: 600;">No Digital Payment Methods Available</p>
                    <p style="margin: 0; font-size: 0.9rem;">No digital payment methods are currently enabled. Please contact the hotel for payment options.</p>
                </div>
            <?php else: ?>
                <form method="post" action="<?= base_url('guest/order/change-payment'); ?>" id="paymentForm">
                    <input type="hidden" name="ref" value="<?= htmlspecialchars($order['reference']); ?>">
                    
                    <?php 
                    $firstMethod = true;
                    foreach ($availableMethods as $methodKey => $methodInfo): 
                    ?>
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: flex; align-items: center; padding: 1rem; background: var(--guest-bg); border: 2px solid var(--guest-border); border-radius: 0.5rem; cursor: pointer; transition: all 0.2s;" 
                                   onmouseover="this.style.borderColor='var(--guest-primary)'" 
                                   onmouseout="this.style.borderColor='var(--guest-border)'">
                                <input type="radio" name="payment_method" value="<?= htmlspecialchars($methodKey); ?>" <?= $firstMethod ? 'checked' : ''; ?> style="margin-right: 0.75rem; cursor: pointer;" required>
                                <div style="flex: 1;">
                                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                                        <span style="font-size: 1.25rem;"><?= htmlspecialchars($methodInfo['icon'] ?? '💳'); ?></span>
                                        <div style="font-weight: 600;"><?= htmlspecialchars($methodInfo['label']); ?></div>
                                    </div>
                                    <div style="font-size: 0.85rem; color: var(--guest-text-light);"><?= htmlspecialchars($methodInfo['description']); ?></div>
                                </div>
                            </label>
                        </div>
                    <?php 
                        $firstMethod = false;
                    endforeach; 
                    ?>

                    <!-- M-Pesa Phone Field (shown only when M-Pesa is selected) -->
                    <div id="mpesaPhoneField" style="margin-bottom: 1.5rem; display: none;">
                        <label>
                            <span>M-Pesa Phone Number</span>
                            <input type="tel" name="mpesa_phone" placeholder="254700000000" 
                                   value="<?= htmlspecialchars($order['customer_phone'] ?? ''); ?>"
                                   pattern="[0-9]{9,12}">
                            <small style="color: var(--guest-text-light); font-size: 0.85rem;">
                                Enter your M-Pesa registered phone number (e.g., 254700000000)
                            </small>
                        </label>
                    </div>

                    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <button type="submit" class="guest-btn" style="background: #8b5cf6; color: white; flex: 1; min-width: 200px;">
                            💳 Proceed to Payment
                        </button>
                        <a href="<?= base_url('guest/order?ref=' . urlencode($order['reference'])); ?>" class="guest-btn guest-btn-outline" style="flex: 1; min-width: 200px; text-align: center;">
                            Cancel
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Show/hide M-Pesa phone field based on selected payment method
const paymentForm = document.getElementById('paymentForm');
if (paymentForm) {
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const mpesaField = document.getElementById('mpesaPhoneField');
            const mpesaInput = document.querySelector('input[name="mpesa_phone"]');
            
            if (this.value === 'mpesa') {
                mpesaField.style.display = 'block';
                if (mpesaInput) {
                    mpesaInput.required = true;
                }
            } else {
                mpesaField.style.display = 'none';
                if (mpesaInput) {
                    mpesaInput.required = false;
                }
            }
        });
    });
    
    // Trigger change event on page load to show/hide M-Pesa field if needed
    const checkedMethod = document.querySelector('input[name="payment_method"]:checked');
    if (checkedMethod) {
        checkedMethod.dispatchEvent(new Event('change'));
    }
    
    // Form submission
    paymentForm.addEventListener('submit', function(e) {
        const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
        if (!selectedMethod) {
            e.preventDefault();
            alert('Please select a payment method.');
            return false;
        }
        
        const methodValue = selectedMethod.value;
        const mpesaPhone = document.querySelector('input[name="mpesa_phone"]');
        
        if (methodValue === 'mpesa' && mpesaPhone && !mpesaPhone.value.trim()) {
            e.preventDefault();
            alert('Please enter your M-Pesa phone number.');
            return false;
        }
        
        // Show loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processing...';
        }
    });
}
</script>

<?php
$slot = ob_get_clean();
include view_path('layouts/guest.php');
?>

