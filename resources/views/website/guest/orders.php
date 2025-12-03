<?php
ob_start();
$success = $_GET['success'] ?? null;
$orderRef = $_GET['ref'] ?? null;
$paymentStatus = $_GET['payment_status'] ?? null;
?>
<div>
    <h1 class="guest-page-title">My Orders</h1>
    <p class="guest-page-subtitle">Track your food and drink orders</p>

    <?php if ($success && $orderRef): ?>
        <div style="padding: 1rem 1.5rem; background: #d1fae5; color: #065f46; border-radius: 0.5rem; margin-bottom: 1.5rem; border-left: 4px solid #16a34a;">
            <strong>✓ Order Placed Successfully!</strong>
            <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem;">
                Your order <strong><?= htmlspecialchars($orderRef); ?></strong> has been placed. 
                <?php if (in_array($paymentStatus, ['paid', 'completed'])): ?>
                    Payment confirmed. You can print your receipt below.
                <?php endif; ?>
            </p>
        </div>
    <?php endif; ?>

    <!-- Current Orders -->
    <div class="guest-card">
        <h2 class="guest-card-title">Current Orders</h2>
        <?php if (empty($currentOrders)): ?>
            <div class="guest-empty">
                <div class="guest-empty-icon">🍽️</div>
                <p>No active orders</p>
            </div>
        <?php else: ?>
            <div style="display: grid; gap: 1rem;">
                <?php foreach ($currentOrders as $order): ?>
                    <div style="padding: 1.25rem; background: var(--guest-bg); border-radius: 0.5rem; border: 1px solid var(--guest-border);">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
                            <div>
                                <h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 0.5rem;">
                                    Order #<?= htmlspecialchars($order['reference']); ?>
                                </h3>
                                <p style="color: var(--guest-text-light); font-size: 0.9rem;">
                                    <?= count($order['items'] ?? []); ?> items • KES <?= number_format((float)($order['total'] ?? 0), 2); ?>
                                </p>
                                <p style="color: var(--guest-text-light); font-size: 0.85rem; margin-top: 0.25rem;">
                                    <?= date('M j, Y g:i A', strtotime($order['created_at'])); ?>
                                </p>
                            </div>
                            <?php
                            $statusBadge = 'guest-badge-info';
                            $statusText = ucfirst($order['status'] ?? 'pending');
                            if ($order['status'] === 'ready') $statusBadge = 'guest-badge-success';
                            if ($order['status'] === 'preparing') $statusBadge = 'guest-badge-warning';
                            if ($order['status'] === 'delivered') $statusBadge = 'guest-badge-success';
                            ?>
                            <span class="guest-badge <?= $statusBadge; ?>"><?= $statusText; ?></span>
                        </div>
                        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                            <a href="<?= base_url('guest/order?ref=' . urlencode($order['reference'])); ?>" class="guest-btn guest-btn-outline" style="font-size: 0.9rem; padding: 0.5rem 1rem;">
                                View Details
                            </a>
                            <?php 
                            // Show print receipt if payment is paid/completed OR if order is completed/delivered
                            $canPrintReceipt = in_array($order['payment_status'] ?? '', ['paid', 'completed']) 
                                || in_array($order['status'] ?? '', ['completed', 'delivered']);
                            if ($canPrintReceipt): ?>
                                <a href="<?= base_url('guest/order?ref=' . urlencode($order['reference']) . '&download=receipt'); ?>" target="_blank" class="guest-btn" style="font-size: 0.9rem; padding: 0.5rem 1rem; background: #059669; color: white;">
                                    🖨️ Print Receipt
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Past Orders -->
    <div class="guest-card">
        <h2 class="guest-card-title">Past Orders</h2>
        <?php if (empty($pastOrders)): ?>
            <div class="guest-empty">
                <div class="guest-empty-icon">📋</div>
                <p>No past orders</p>
            </div>
        <?php else: ?>
            <div style="display: grid; gap: 1rem;">
                <?php foreach ($pastOrders as $order): ?>
                    <div style="padding: 1.25rem; background: var(--guest-bg); border-radius: 0.5rem; border: 1px solid var(--guest-border);">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
                            <div>
                                <h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 0.5rem;">
                                    Order #<?= htmlspecialchars($order['reference']); ?>
                                </h3>
                                <p style="color: var(--guest-text-light); font-size: 0.9rem;">
                                    <?= count($order['items'] ?? []); ?> items • KES <?= number_format((float)($order['total'] ?? 0), 2); ?>
                                </p>
                                <p style="color: var(--guest-text-light); font-size: 0.85rem; margin-top: 0.25rem;">
                                    <?= date('M j, Y g:i A', strtotime($order['created_at'])); ?>
                                </p>
                            </div>
                            <span class="guest-badge <?= $order['status'] === 'completed' ? 'guest-badge-success' : 'guest-badge-danger'; ?>">
                                <?= ucfirst($order['status'] ?? 'completed'); ?>
                            </span>
                        </div>
                        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                            <a href="<?= base_url('guest/order?ref=' . urlencode($order['reference'])); ?>" class="guest-btn guest-btn-outline" style="font-size: 0.9rem; padding: 0.5rem 1rem;">
                                View Details
                            </a>
                            <?php 
                            // Show print receipt if payment is paid/completed OR if order is completed/delivered
                            $canPrintReceipt = in_array($order['payment_status'] ?? '', ['paid', 'completed']) 
                                || in_array($order['status'] ?? '', ['completed', 'delivered']);
                            if ($canPrintReceipt): ?>
                                <a href="<?= base_url('guest/order?ref=' . urlencode($order['reference']) . '&download=receipt'); ?>" target="_blank" class="guest-btn" style="font-size: 0.9rem; padding: 0.5rem 1rem; background: #059669; color: white;">
                                    🖨️ Print Receipt
                                </a>
                            <?php endif; ?>
                            <?php if ($order['status'] === 'completed' || $order['status'] === 'delivered'): ?>
                                <a href="<?= base_url('order?reorder_ref=' . urlencode($order['reference'])); ?>" class="guest-btn" style="font-size: 0.9rem; padding: 0.5rem 1rem;">
                                    Reorder
                                </a>
                                <button type="button" class="guest-btn" style="background: #8b5cf6; color: white; border: none; cursor: pointer; font-size: 0.9rem; padding: 0.5rem 1rem;" onclick="showReviewModal(<?= !empty($order['reservation_id']) ? (int)$order['reservation_id'] : 'null'; ?>, '<?= htmlspecialchars($order['reference'] ?? '', ENT_QUOTES); ?>')">
                                    ⭐ Leave a Review
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Review Modal (same as booking.php)
function showReviewModal(reservationId, reference) {
    const modal = document.getElementById('reviewModal');
    if (!modal) {
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
                                <span data-rating="1" style="color: #d1d5db;">★</span>
                                <span data-rating="2" style="color: #d1d5db;">★</span>
                                <span data-rating="3" style="color: #d1d5db;">★</span>
                                <span data-rating="4" style="color: #d1d5db;">★</span>
                                <span data-rating="5" style="color: #d1d5db;">★</span>
                            </div>
                            <input type="hidden" id="reviewRating" name="rating" required>
                        </div>
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Title (optional)</label>
                            <input type="text" name="title" placeholder="e.g., Great food!" style="width: 100%; padding: 0.75rem; border: 1px solid var(--guest-border); border-radius: 0.5rem; font-size: 1rem; box-sizing: border-box;">
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
        });
    }
    const modal = document.getElementById('reviewModal');
    document.getElementById('reviewReservationId').value = reservationId;
    document.getElementById('reviewRating').value = '';
    document.getElementById('reviewForm').reset();
    document.getElementById('reviewError').style.display = 'none';
    document.getElementById('reviewSuccess').style.display = 'none';
    const stars = document.querySelectorAll('#ratingStars span');
    stars.forEach(s => s.style.color = '#d1d5db');
    modal.style.display = 'flex';
}

function closeReviewModal() {
    const modal = document.getElementById('reviewModal');
    if (modal) modal.style.display = 'none';
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
            document.getElementById('reviewError').style.display = 'block';
            document.getElementById('reviewError').textContent = 'Failed to submit review. Please try again.';
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Review';
        } else {
            document.getElementById('reviewSuccess').style.display = 'block';
            document.getElementById('reviewSuccess').textContent = 'Review submitted successfully! Thank you for your feedback.';
            setTimeout(() => {
                closeReviewModal();
                window.location.reload();
            }, 2000);
        }
    })
    .catch(error => {
        document.getElementById('reviewError').style.display = 'block';
        document.getElementById('reviewError').textContent = 'An error occurred. Please try again.';
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit Review';
    });
}

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

