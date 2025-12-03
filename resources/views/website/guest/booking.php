<?php
ob_start();
$statusLabels = [
    'pending' => 'Pending',
    'confirmed' => 'Confirmed',
    'checked_in' => 'Checked In',
    'checked_out' => 'Checked Out',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled',
];
$statusBadges = [
    'pending' => 'guest-badge-warning',
    'confirmed' => 'guest-badge-info',
    'checked_in' => 'guest-badge-success',
    'checked_out' => 'guest-badge-success',
    'completed' => 'guest-badge-success',
    'cancelled' => 'guest-badge-danger',
];
$status = $booking['status'] ?? 'pending';
?>
<div>
    <div style="margin-bottom: 1.5rem;">
        <a href="<?= base_url('guest/portal'); ?>" class="guest-btn guest-btn-outline" style="display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
            ← Back to Dashboard
        </a>
        <h1 class="guest-page-title">Booking Details</h1>
    </div>

    <div class="guest-card">
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 0.5rem;">
                    <?= htmlspecialchars($booking['room_type_name'] ?? 'Room'); ?>
                </h2>
                <?php if ($booking['room_number'] ?? $booking['display_name']): ?>
                    <p style="color: var(--guest-text-light); font-size: 1rem;">
                        Room <?= htmlspecialchars($booking['room_number'] ?? $booking['display_name']); ?>
                    </p>
                <?php endif; ?>
            </div>
            <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.5rem;">
                <span class="guest-badge <?= $statusBadges[$status] ?? 'guest-badge-info'; ?>">
                    <?= $statusLabels[$status] ?? ucfirst($status); ?>
                </span>
                <?php 
                $paymentStatus = $booking['payment_status'] ?? 'unpaid';
                if ($paymentStatus !== 'paid'): 
                ?>
                    <span class="guest-badge guest-badge-warning" style="font-size: 0.75rem;">
                        ⚠️ Payment Pending
                    </span>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($paymentStatus !== 'paid'): ?>
            <div style="margin-bottom: 1.5rem; padding: 1.5rem; background: #fef3c7; border-radius: 0.5rem; border-left: 4px solid #f59e0b;">
                <h3 style="color: #92400e; font-size: 1rem; font-weight: 600; margin: 0 0 0.75rem 0;">
                    ⚠️ Payment Required to Confirm Booking
                </h3>
                <p style="color: #78350f; font-size: 0.9rem; margin: 0 0 0.75rem 0;">
                    Your booking has been reserved, but payment is required to confirm your reservation. The room is temporarily held for you.
                </p>
                <?php if ($booking['payment_method'] === 'mpesa'): ?>
                    <p style="color: #78350f; font-size: 0.875rem; margin: 0;">
                        <strong>M-Pesa Payment:</strong> Please check your phone and complete the M-Pesa payment. Once payment is confirmed, you will receive a booking confirmation email.
                    </p>
                <?php else: ?>
                    <p style="color: #78350f; font-size: 0.875rem; margin: 0;">
                        <strong>Payment Method:</strong> <?= ucfirst(str_replace('_', ' ', $booking['payment_method'] ?? 'pay on arrival')); ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div style="display: grid; gap: 1.5rem; margin-bottom: 1.5rem;">
            <div>
                <h3 style="font-size: 0.875rem; font-weight: 600; color: var(--guest-text-light); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Check-in & Check-out</h3>
                <p style="font-size: 1rem;">
                    <strong><?= date('l, F j, Y', strtotime($booking['check_in'])); ?></strong> to 
                    <strong><?= date('l, F j, Y', strtotime($booking['check_out'])); ?></strong>
                </p>
            </div>

            <div>
                <h3 style="font-size: 0.875rem; font-weight: 600; color: var(--guest-text-light); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Total Cost</h3>
                <p style="font-size: 1.5rem; font-weight: 600; color: var(--guest-primary);">
                    KES <?= number_format((float)($booking['total_amount'] ?? 0), 2); ?>
                </p>
            </div>

            <div>
                <h3 style="font-size: 0.875rem; font-weight: 600; color: var(--guest-text-light); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Payment Status</h3>
                <p>
                    <?php
                    $paymentStatusDisplay = $booking['payment_status'] ?? 'unpaid';
                    $paymentBadge = $paymentStatusDisplay === 'paid' ? 'guest-badge-success' : 'guest-badge-warning';
                    ?>
                    <span class="guest-badge <?= $paymentBadge; ?>">
                        <?= $paymentStatusDisplay === 'paid' ? '✓ Paid' : '⚠️ ' . ucfirst($paymentStatusDisplay); ?>
                    </span>
                    <?php if ($paymentStatusDisplay === 'paid' && !empty($booking['mpesa_transaction_id'])): ?>
                        <br><small style="color: var(--guest-text-light); font-size: 0.875rem; margin-top: 0.25rem; display: block;">
                            Transaction: <?= htmlspecialchars($booking['mpesa_transaction_id']); ?>
                        </small>
                    <?php elseif ($paymentStatusDisplay !== 'paid' && $booking['payment_method'] === 'mpesa' && !empty($booking['mpesa_phone'])): ?>
                        <br><small style="color: var(--guest-text-light); font-size: 0.875rem; margin-top: 0.25rem; display: block;">
                            M-Pesa: <?= htmlspecialchars($booking['mpesa_phone']); ?>
                        </small>
                    <?php endif; ?>
                </p>
            </div>

            <div>
                <h3 style="font-size: 0.875rem; font-weight: 600; color: var(--guest-text-light); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Booking Reference</h3>
                <p style="font-family: monospace; font-size: 1rem; font-weight: 500;">
                    <?= htmlspecialchars($booking['reference'] ?? ''); ?>
                </p>
            </div>
        </div>

        <div style="display: flex; gap: 1rem; flex-wrap: wrap; padding-top: 1.5rem; border-top: 1px solid var(--guest-border);">
            <?php if ($paymentStatus !== 'paid'): ?>
                <button type="button" class="guest-btn" style="background: #10b981; color: white; border: none; cursor: pointer; padding: 0.75rem 1.5rem; font-size: 1rem;" onclick="showPaymentModal('<?= htmlspecialchars($booking['reference'], ENT_QUOTES); ?>', <?= (float)($booking['total_amount'] ?? 0); ?>, '<?= htmlspecialchars($booking['guest_phone'] ?? '', ENT_QUOTES); ?>')">
                    💳 Pay Now - KES <?= number_format((float)($booking['total_amount'] ?? 0), 2); ?>
                </button>
            <?php endif; ?>
            <a href="<?= base_url('guest/booking?ref=' . urlencode($booking['reference']) . '&download=receipt'); ?>" class="guest-btn guest-btn-outline">
                Download Receipt
            </a>
            <a href="<?= base_url('contact'); ?>" class="guest-btn guest-btn-outline">
                Contact Support
            </a>
            <?php if (in_array($status, ['pending', 'confirmed'])): ?>
                <a href="<?= base_url('contact?subject=modify_booking&ref=' . urlencode($booking['reference'])); ?>" class="guest-btn guest-btn-outline">
                    Modify Booking
                </a>
            <?php endif; ?>
            <?php if ($status === 'checked_in'): ?>
                <a href="<?= base_url('guest/orders?booking=' . urlencode($booking['reference'])); ?>" class="guest-btn">
                    Order Food for This Stay
                </a>
            <?php endif; ?>
            <?php if (in_array($status, ['checked_out', 'completed'])): ?>
                <button type="button" class="guest-btn" style="background: #8b5cf6; color: white; border: none; cursor: pointer;" onclick="showReviewModal(<?= (int)($booking['id'] ?? 0); ?>, '<?= htmlspecialchars($booking['reference'] ?? '', ENT_QUOTES); ?>')">
                    ⭐ Leave a Review
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Add real-time payment status updates for pending M-Pesa payments
$paymentMethod = $booking['payment_method'] ?? 'pay_on_arrival';
$mpesaStatus = $booking['mpesa_status'] ?? null;
$paymentStatus = $booking['payment_status'] ?? 'unpaid';
$isMpesaPending = $paymentMethod === 'mpesa' && ($mpesaStatus === 'pending' || $paymentStatus === 'unpaid');
?>

<?php if ($isMpesaPending): ?>
<script>
    // Real-time payment status polling for M-Pesa payments
    (function() {
        const reference = '<?= htmlspecialchars($booking['reference'] ?? ''); ?>';
        if (!reference) return;
        
        let pollCount = 0;
        const maxPolls = 60; // Poll for up to 5 minutes (60 * 5 seconds)
        let pollInterval;
        
        function checkPaymentStatus() {
            pollCount++;
            
            if (pollCount > maxPolls) {
                clearInterval(pollInterval);
                return;
            }
            
            fetch('<?= base_url('api/booking/payment-status'); ?>?reference=' + encodeURIComponent(reference))
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const paymentStatus = data.payment_status;
                        const mpesaStatus = data.mpesa_status;
                        const bookingStatus = data.booking_status;
                        
                        // Check if payment status changed
                        if (paymentStatus === 'paid' || mpesaStatus === 'completed') {
                            // Payment confirmed - reload page to show updated status
                            clearInterval(pollInterval);
                            window.location.reload();
                        } else if (mpesaStatus === 'failed' || mpesaStatus === 'cancelled') {
                            // Payment failed - reload page to show updated status
                            clearInterval(pollInterval);
                            window.location.reload();
                        }
                    }
                })
                .catch(error => {
                    console.error('Error checking payment status:', error);
                });
        }
        
        // Start polling every 5 seconds
        pollInterval = setInterval(checkPaymentStatus, 5000);
        
        // Also check immediately
        checkPaymentStatus();
    })();
</script>
<?php endif; ?>

<!-- Payment Modal -->
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
    
    // Show/hide phone field based on payment method
    if (paymentMethod === 'mpesa') {
        phoneField.style.display = 'block';
        phoneInput.required = true;
    } else {
        phoneField.style.display = 'none';
        phoneInput.required = false;
    }
    
    // For non-M-Pesa methods, show message
    if (paymentMethod !== 'mpesa') {
        const errorDiv = document.getElementById('paymentError');
        errorDiv.style.display = 'block';
        errorDiv.style.background = '#fef3c7';
        errorDiv.style.color = '#92400e';
        errorDiv.textContent = 'For ' + paymentMethod + ' payments, please contact the hotel directly or pay on arrival.';
        return;
    }
    
    // Validate phone for M-Pesa
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

// Close modal when clicking outside
document.getElementById('paymentModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePaymentModal();
    }
});

// Update phone field visibility when payment method changes
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

// Review Modal
function showReviewModal(reservationId, reference) {
    const modal = document.getElementById('reviewModal');
    if (!modal) {
        // Create modal if it doesn't exist
        const modalHTML = `
            <div id="reviewModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
                <div style="background: white; padding: 2rem; border-radius: 0.5rem; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto;">
                    <h2 style="margin: 0 0 1.5rem 0; font-size: 1.5rem;">Leave a Review</h2>
                    <form id="reviewForm" onsubmit="submitReview(event)">
                        <input type="hidden" id="reviewReservationId" name="reservation_id">
                        <input type="hidden" name="category" value="overall">
                        
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Rating</label>
                            <div style="display: flex; gap: 0.5rem; font-size: 2rem; cursor: pointer;" id="ratingStars">
                                <span data-rating="1" style="color: #d1d5db; transition: color 0.2s;">★</span>
                                <span data-rating="2" style="color: #d1d5db; transition: color 0.2s;">★</span>
                                <span data-rating="3" style="color: #d1d5db; transition: color 0.2s;">★</span>
                                <span data-rating="4" style="color: #d1d5db; transition: color 0.2s;">★</span>
                                <span data-rating="5" style="color: #d1d5db; transition: color 0.2s;">★</span>
                            </div>
                            <input type="hidden" id="reviewRating" name="rating" required>
                        </div>
                        
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Title (optional)</label>
                            <input type="text" name="title" placeholder="e.g., Great stay!" style="width: 100%; padding: 0.75rem; border: 1px solid var(--guest-border); border-radius: 0.5rem; font-size: 1rem; box-sizing: border-box;">
                        </div>
                        
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Your Review</label>
                            <textarea name="comment" rows="4" placeholder="Share your experience..." style="width: 100%; padding: 0.75rem; border: 1px solid var(--guest-border); border-radius: 0.5rem; font-size: 1rem; box-sizing: border-box; font-family: inherit;"></textarea>
                        </div>
                        
                        <div id="reviewError" style="display: none; padding: 0.75rem; background: #fee2e2; color: #991b1b; border-radius: 0.5rem; margin-bottom: 1rem;"></div>
                        <div id="reviewSuccess" style="display: none; padding: 0.75rem; background: #d1fae5; color: #065f46; border-radius: 0.5rem; margin-bottom: 1rem;"></div>
                        
                        <div style="display: flex; gap: 0.75rem;">
                            <button type="button" onclick="closeReviewModal()" class="guest-btn guest-btn-outline" style="flex: 1;">Cancel</button>
                            <button type="submit" class="guest-btn" style="flex: 1; background: #8b5cf6; color: white; border: none;">Submit Review</button>
                        </div>
                    </form>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Add star rating functionality
        const stars = document.querySelectorAll('#ratingStars span');
        let selectedRating = 0;
        stars.forEach((star, index) => {
            star.addEventListener('click', function() {
                selectedRating = index + 1;
                document.getElementById('reviewRating').value = selectedRating;
                stars.forEach((s, i) => {
                    s.style.color = i < selectedRating ? '#fbbf24' : '#d1d5db';
                });
            });
            star.addEventListener('mouseenter', function() {
                const hoverRating = index + 1;
                stars.forEach((s, i) => {
                    s.style.color = i < hoverRating ? '#fbbf24' : '#d1d5db';
                });
            });
        });
        document.getElementById('ratingStars').addEventListener('mouseleave', function() {
            stars.forEach((s, i) => {
                s.style.color = i < selectedRating ? '#fbbf24' : '#d1d5db';
            });
        });
    }
    
    const modal = document.getElementById('reviewModal');
    document.getElementById('reviewReservationId').value = reservationId;
    document.getElementById('reviewRating').value = '';
    document.getElementById('reviewForm').reset();
    document.getElementById('reviewError').style.display = 'none';
    document.getElementById('reviewSuccess').style.display = 'none';
    
    // Reset stars
    const stars = document.querySelectorAll('#ratingStars span');
    stars.forEach(s => s.style.color = '#d1d5db');
    
    modal.style.display = 'flex';
}

function closeReviewModal() {
    const modal = document.getElementById('reviewModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function submitReview(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    
    if (!formData.get('rating')) {
        document.getElementById('reviewError').style.display = 'block';
        document.getElementById('reviewError').textContent = 'Please select a rating.';
        return;
    }
    
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';
    
    const errorDiv = document.getElementById('reviewError');
    const successDiv = document.getElementById('reviewSuccess');
    errorDiv.style.display = 'none';
    successDiv.style.display = 'none';
    
    fetch('<?= base_url('guest/reviews/create'); ?>', {
        method: 'POST',
        body: formData
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
            errorDiv.style.display = 'block';
            errorDiv.textContent = 'Failed to submit review. Please try again.';
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Review';
        } else {
            successDiv.style.display = 'block';
            successDiv.textContent = 'Review submitted successfully! Thank you for your feedback.';
            setTimeout(() => {
                closeReviewModal();
                window.location.reload();
            }, 2000);
        }
    })
    .catch(error => {
        errorDiv.style.display = 'block';
        errorDiv.textContent = 'An error occurred. Please try again.';
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit Review';
    });
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    const modal = document.getElementById('reviewModal');
    if (modal && e.target === modal) {
        closeReviewModal();
    }
});
</script>

<?php
$slot = ob_get_clean();
include view_path('layouts/guest.php');
?>

