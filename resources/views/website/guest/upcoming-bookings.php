<?php
ob_start();
?>
<div>
    <div style="margin-bottom: 1.5rem;">
        <a href="<?= base_url('guest/portal'); ?>" class="guest-btn guest-btn-outline" style="display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
            ← Back to Dashboard
        </a>
        <h1 class="guest-page-title">Upcoming Bookings</h1>
        <p class="guest-page-subtitle">Your upcoming stays</p>
    </div>

    <div class="guest-card">
        <?php if (empty($upcomingBookings)): ?>
            <div class="guest-empty">
                <div class="guest-empty-icon">📅</div>
                <p>No upcoming bookings</p>
                <p style="font-size: 0.9rem; margin-top: 0.5rem; color: var(--guest-text-light);">
                    You don't have any upcoming reservations
                </p>
            </div>
        <?php else: ?>
            <div style="display: grid; gap: 1.5rem;">
                <?php foreach ($upcomingBookings as $booking): ?>
                    <div style="padding: 1.5rem; background: var(--guest-bg); border-radius: 0.5rem; border: 1px solid var(--guest-border);">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
                            <div style="flex: 1;">
                                <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem;">
                                    <?= htmlspecialchars($booking['room_type_name'] ?? 'Room'); ?>
                                    <?php if ($booking['room_number'] ?? $booking['display_name']): ?>
                                        <span style="color: var(--guest-text-light); font-weight: 400; font-size: 1rem;">
                                            • <?= htmlspecialchars($booking['room_number'] ?? $booking['display_name']); ?>
                                        </span>
                                    <?php endif; ?>
                                </h3>
                                <p style="color: var(--guest-text-light); font-size: 0.95rem; margin-bottom: 0.5rem;">
                                    <strong>Check-in:</strong> <?= date('l, F j, Y', strtotime($booking['check_in'])); ?>
                                </p>
                                <p style="color: var(--guest-text-light); font-size: 0.95rem; margin-bottom: 0.5rem;">
                                    <strong>Check-out:</strong> <?= date('l, F j, Y', strtotime($booking['check_out'])); ?>
                                </p>
                                <p style="color: var(--guest-text-light); font-size: 0.95rem;">
                                    <strong>Total:</strong> KES <?= number_format((float)($booking['total_amount'] ?? 0), 2); ?>
                                </p>
                            </div>
                            <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.5rem;">
                                <span class="guest-badge guest-badge-info">
                                    <?= ucfirst(str_replace('_', ' ', $booking['status'] ?? 'pending')); ?>
                                </span>
                                <?php if (($booking['payment_status'] ?? 'unpaid') !== 'paid'): ?>
                                    <span class="guest-badge guest-badge-warning" style="font-size: 0.75rem;">
                                        ⚠️ Payment Pending
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (($booking['payment_status'] ?? 'unpaid') !== 'paid'): ?>
                            <div style="margin-bottom: 1.5rem; padding: 1rem; background: #fef3c7; border-radius: 0.5rem; border-left: 4px solid #f59e0b;">
                                <p style="color: #92400e; font-size: 0.95rem; margin: 0; font-weight: 600; margin-bottom: 0.5rem;">
                                    ⚠️ Payment Required
                                </p>
                                <p style="color: #78350f; font-size: 0.875rem; margin: 0;">
                                    Your booking is reserved but payment is pending. Please complete payment to confirm your reservation. The room will be held temporarily until payment is received.
                                </p>
                            </div>
                        <?php else: ?>
                            <p style="color: var(--guest-text); font-size: 1rem; margin-bottom: 1.5rem; padding: 1rem; background: white; border-radius: 0.5rem; border-left: 4px solid var(--guest-primary);">
                                Your stay at <?= htmlspecialchars(settings('branding.name', 'Hotela')); ?> starts on <strong><?= date('jS F', strtotime($booking['check_in'])); ?></strong>.
                            </p>
                        <?php endif; ?>
                        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                            <a href="<?= base_url('guest/booking?ref=' . urlencode($booking['reference'])); ?>" class="guest-btn">
                                View Details
                            </a>
                            <?php if (($booking['payment_status'] ?? 'unpaid') !== 'paid'): ?>
                                <button type="button" class="guest-btn" style="background: #10b981; color: white; border: none; cursor: pointer;" onclick="showPaymentModal('<?= htmlspecialchars($booking['reference'], ENT_QUOTES); ?>', <?= (float)($booking['total_amount'] ?? 0); ?>, '<?= htmlspecialchars($booking['guest_phone'] ?? '', ENT_QUOTES); ?>')">
                                    💳 Pay Now
                                </button>
                            <?php endif; ?>
                            <?php if ($booking['status'] === 'confirmed' || $booking['status'] === 'checked_in'): ?>
                                <a href="<?= base_url('guest/active-orders?booking=' . urlencode($booking['reference'])); ?>" class="guest-btn guest-btn-outline">
                                    Order Food
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Payment Modal (same as booking detail page) -->
<div id="paymentModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 2rem; border-radius: 0.5rem; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto;">
        <h2 style="margin: 0 0 1.5rem 0; font-size: 1.5rem;">Complete Payment</h2>
        <form id="paymentForm" onsubmit="processPayment(event)">
            <input type="hidden" id="paymentReference" name="reference">
            <input type="hidden" id="paymentAmount" name="amount">
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Payment Method</label>
                <select id="paymentMethod" name="payment_method" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--guest-border); border-radius: 0.5rem; font-size: 1rem;">
                    <option value="mpesa">M-Pesa</option>
                    <option value="cash">Cash (Pay on Arrival)</option>
                    <option value="card">Card (Pay on Arrival)</option>
                </select>
            </div>
            
            <div id="phoneField" style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">M-Pesa Phone Number</label>
                <input type="tel" id="paymentPhone" name="phone" placeholder="254700000000" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--guest-border); border-radius: 0.5rem; font-size: 1rem;">
                <p style="font-size: 0.875rem; color: var(--guest-text-light); margin-top: 0.5rem;">Enter your M-Pesa registered phone number</p>
            </div>
            
            <div style="margin-bottom: 1.5rem; padding: 1rem; background: #f3f4f6; border-radius: 0.5rem;">
                <p style="margin: 0; font-size: 0.9rem; color: var(--guest-text-light);">Amount to Pay:</p>
                <p style="margin: 0.5rem 0 0 0; font-size: 1.5rem; font-weight: 600; color: var(--guest-primary);">KES <span id="paymentAmountDisplay">0.00</span></p>
            </div>
            
            <div id="paymentError" style="display: none; padding: 0.75rem; background: #fee2e2; color: #991b1b; border-radius: 0.5rem; margin-bottom: 1rem;"></div>
            <div id="paymentSuccess" style="display: none; padding: 0.75rem; background: #d1fae5; color: #065f46; border-radius: 0.5rem; margin-bottom: 1rem;"></div>
            
            <div style="display: flex; gap: 0.75rem;">
                <button type="button" onclick="closePaymentModal()" class="guest-btn guest-btn-outline" style="flex: 1;">Cancel</button>
                <button type="submit" class="guest-btn" style="flex: 1; background: #10b981; color: white; border: none;">Pay Now</button>
            </div>
        </form>
    </div>
</div>

<script>
function showPaymentModal(reference, amount, phone) {
    document.getElementById('paymentModal').style.display = 'flex';
    document.getElementById('paymentReference').value = reference;
    document.getElementById('paymentAmount').value = amount;
    document.getElementById('paymentAmountDisplay').textContent = amount.toLocaleString('en-KE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    if (phone) {
        document.getElementById('paymentPhone').value = phone;
    }
    document.getElementById('paymentError').style.display = 'none';
    document.getElementById('paymentSuccess').style.display = 'none';
}

function closePaymentModal() {
    document.getElementById('paymentModal').style.display = 'none';
    document.getElementById('paymentForm').reset();
}

function processPayment(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const paymentMethod = formData.get('payment_method');
    const phoneField = document.getElementById('phoneField');
    const phoneInput = document.getElementById('paymentPhone');
    
    if (paymentMethod === 'mpesa') {
        phoneField.style.display = 'block';
        phoneInput.required = true;
    } else {
        phoneField.style.display = 'none';
        phoneInput.required = false;
    }
    
    if (paymentMethod !== 'mpesa') {
        const errorDiv = document.getElementById('paymentError');
        errorDiv.style.display = 'block';
        errorDiv.style.background = '#fef3c7';
        errorDiv.style.color = '#92400e';
        errorDiv.textContent = 'For ' + paymentMethod + ' payments, please contact the hotel directly or pay on arrival.';
        return;
    }
    
    if (paymentMethod === 'mpesa' && !phoneInput.value.trim()) {
        const errorDiv = document.getElementById('paymentError');
        errorDiv.style.display = 'block';
        errorDiv.textContent = 'Please enter your M-Pesa phone number.';
        return;
    }
    
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Processing...';
    
    const errorDiv = document.getElementById('paymentError');
    const successDiv = document.getElementById('paymentSuccess');
    errorDiv.style.display = 'none';
    successDiv.style.display = 'none';
    
    fetch('<?= base_url('guest/booking/pay'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            successDiv.style.display = 'block';
            successDiv.textContent = data.message || 'Payment request sent successfully!';
            if (data.redirect) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 2000);
            } else {
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            }
        } else {
            errorDiv.style.display = 'block';
            errorDiv.textContent = data.error || 'Payment processing failed. Please try again.';
            submitBtn.disabled = false;
            submitBtn.textContent = 'Pay Now';
        }
    })
    .catch(error => {
        errorDiv.style.display = 'block';
        errorDiv.textContent = 'An error occurred. Please try again.';
        submitBtn.disabled = false;
        submitBtn.textContent = 'Pay Now';
    });
}

document.getElementById('paymentModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePaymentModal();
    }
});

document.getElementById('paymentMethod').addEventListener('change', function() {
    const phoneField = document.getElementById('phoneField');
    const phoneInput = document.getElementById('paymentPhone');
    if (this.value === 'mpesa') {
        phoneField.style.display = 'block';
        phoneInput.required = true;
    } else {
        phoneField.style.display = 'none';
        phoneInput.required = false;
    }
});
</script>

<?php
$slot = ob_get_clean();
include view_path('layouts/guest.php');
?>

