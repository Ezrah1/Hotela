<?php
ob_start();
?>
<div style="max-width: 600px; margin: 2rem auto; padding: 2rem;">
    <div style="text-align: center; margin-bottom: 2rem;">
        <div style="font-size: 4rem; margin-bottom: 1rem;">⏳</div>
        <h1 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 0.5rem;">Waiting for Payment</h1>
        <p style="color: #64748b; font-size: 0.9rem;">Please complete the payment on your device</p>
    </div>

    <div style="background: white; border-radius: 0.5rem; padding: 1.5rem; border: 1px solid #e2e8f0; margin-bottom: 1.5rem;">
        <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
            <span style="color: #64748b;">Order Reference:</span>
            <strong><?= htmlspecialchars($orderRef); ?></strong>
        </div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
            <span style="color: #64748b;">Amount:</span>
            <strong>KES <?= number_format($total, 2); ?></strong>
        </div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
            <span style="color: #64748b;">Payment Method:</span>
            <strong><?= htmlspecialchars($paymentMethodName ?? 'Digital Payment'); ?></strong>
        </div>
        <div style="display: flex; justify-content: space-between;">
            <span style="color: #64748b;">Status:</span>
            <span id="payment-status" style="font-weight: 600; color: #f59e0b;">Pending</span>
        </div>
    </div>

    <div id="waiting-message" style="background: #fef3c7; color: #92400e; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; text-align: center;">
        <p style="margin: 0; font-size: 0.9rem;">
            <strong>Please check your device</strong> and complete the payment.
            <br>This page will automatically update when payment is confirmed.
        </p>
    </div>

    <div id="success-message" style="display: none; background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; text-align: center;">
        <p style="margin: 0; font-size: 0.9rem;">
            <strong>✓ Payment Confirmed!</strong>
            <br>Redirecting to your orders...
        </p>
    </div>

    <div id="error-message" style="display: none; background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; text-align: center;">
        <p style="margin: 0; font-size: 0.9rem;" id="error-text"></p>
    </div>

    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
        <button id="confirm-payment-btn" onclick="confirmPayment()" style="display: none; padding: 0.75rem 1.5rem; background: #059669; color: white; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer; font-size: 0.9rem;">
            I Have Paid - Confirm Payment
        </button>
        <a href="<?= base_url('guest/orders'); ?>" style="padding: 0.75rem 1.5rem; background: #e2e8f0; color: #475569; border-radius: 0.5rem; font-weight: 600; text-decoration: none; font-size: 0.9rem; display: inline-block;">
            View My Orders
        </a>
    </div>
</div>

<script>
let checkInterval;
let checkCount = 0;
const maxChecks = 60; // Check for 5 minutes (60 * 5 seconds)

function checkPaymentStatus() {
    checkCount++;
    
    if (checkCount > maxChecks) {
        clearInterval(checkInterval);
        document.getElementById('waiting-message').style.display = 'none';
        document.getElementById('error-message').style.display = 'block';
        document.getElementById('error-text').textContent = 'Payment check timed out. If you have completed payment, please click "I Have Paid" to confirm.';
        document.getElementById('confirm-payment-btn').style.display = 'inline-block';
        return;
    }
    
    fetch('<?= base_url('order/check-payment-status?ref=' . urlencode($orderRef)); ?>')
        .then(response => response.json())
        .then(data => {
            if (data.ok && data.paid) {
                clearInterval(checkInterval);
                document.getElementById('waiting-message').style.display = 'none';
                document.getElementById('success-message').style.display = 'block';
                document.getElementById('payment-status').textContent = 'Paid';
                document.getElementById('payment-status').style.color = '#16a34a';
                document.getElementById('confirm-payment-btn').style.display = 'none';
                
                // Redirect to orders page after 2 seconds
                setTimeout(() => {
                    window.location.href = '<?= base_url('guest/orders?success=1&ref=' . urlencode($orderRef) . '&payment_status=paid'); ?>';
                }, 2000);
            } else if (data.ok && data.payment_status === 'failed' || data.mpesa_status === 'cancelled') {
                clearInterval(checkInterval);
                document.getElementById('waiting-message').style.display = 'none';
                document.getElementById('error-message').style.display = 'block';
                document.getElementById('error-text').textContent = 'Payment was cancelled or failed. Please try again.';
                document.getElementById('confirm-payment-btn').style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Payment status check error:', error);
            // Don't stop checking on network errors, just log them
        });
}

function confirmPayment() {
    if (!confirm('Have you completed the payment? Click OK to confirm payment manually.')) {
        return;
    }
    
    const btn = document.getElementById('confirm-payment-btn');
    btn.disabled = true;
    btn.textContent = 'Confirming...';
    
    const formData = new URLSearchParams();
    formData.append('ref', '<?= htmlspecialchars($orderRef, ENT_QUOTES); ?>');
    
    fetch('<?= base_url('order/confirm-payment'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: formData.toString()
    })
    .then(response => {
        if (response.redirected) {
            window.location.href = response.url;
        } else {
            return response.text();
        }
    })
    .then(data => {
        if (data && data.includes('error')) {
            alert('Failed to confirm payment. Please contact support.');
            btn.disabled = false;
            btn.textContent = 'I Have Paid - Confirm Payment';
        } else {
            window.location.href = '<?= base_url('guest/orders?success=1&ref=' . urlencode($orderRef) . '&payment_status=paid'); ?>';
        }
    })
    .catch(error => {
        console.error('Confirm payment error:', error);
        alert('An error occurred. Please try again or contact support.');
        btn.disabled = false;
        btn.textContent = 'I Have Paid - Confirm Payment';
    });
}

// Start checking payment status every 5 seconds
checkInterval = setInterval(checkPaymentStatus, 5000);
// Also check immediately
checkPaymentStatus();

// Show confirm button after 30 seconds as fallback
setTimeout(() => {
    document.getElementById('confirm-payment-btn').style.display = 'inline-block';
}, 30000);
</script>

<?php
$slot = ob_get_clean();
include view_path('layouts/guest.php');
?>

