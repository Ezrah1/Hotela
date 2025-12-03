<?php
ob_start();
?>
<div>
    <div style="margin-bottom: 1.5rem;">
        <a href="<?= base_url('guest/portal'); ?>" class="guest-btn guest-btn-outline" style="display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
            ← Back to Dashboard
        </a>
        <h1 class="guest-page-title">Past Bookings</h1>
        <p class="guest-page-subtitle">Your booking history</p>
    </div>

    <div class="guest-card">
        <?php if (empty($pastBookings)): ?>
            <div class="guest-empty">
                <div class="guest-empty-icon">📋</div>
                <p>No past bookings</p>
                <p style="font-size: 0.9rem; margin-top: 0.5rem; color: var(--guest-text-light);">
                    You haven't completed any stays yet
                </p>
            </div>
        <?php else: ?>
            <div style="display: grid; gap: 1.5rem;">
                <?php foreach ($pastBookings as $booking): ?>
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
                            <span class="guest-badge guest-badge-success">Completed</span>
                        </div>
                        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                            <a href="<?= base_url('guest/booking?ref=' . urlencode($booking['reference'])); ?>" class="guest-btn">
                                View Details
                            </a>
                            <a href="<?= base_url('guest/booking?ref=' . urlencode($booking['reference']) . '&download=receipt'); ?>" class="guest-btn guest-btn-outline">
                                Download Receipt
                            </a>
                            <button type="button" class="guest-btn" style="background: #8b5cf6; color: white; border: none; cursor: pointer;" onclick="showReviewModal(<?= (int)($booking['id'] ?? 0); ?>, '<?= htmlspecialchars($booking['reference'] ?? '', ENT_QUOTES); ?>')">
                                ⭐ Leave a Review
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Review Modal
function showReviewModal(reservationId, reference) {
    let modal = document.getElementById('reviewModal');
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
        modal = document.getElementById('reviewModal');
        
        // Initialize star rating after modal is created
        setTimeout(function() {
            const stars = document.querySelectorAll('#ratingStars span');
            let selectedRating = 0;
            stars.forEach((star, index) => {
                star.addEventListener('click', function() {
                    selectedRating = index + 1;
                    const ratingInput = document.getElementById('reviewRating');
                    if (ratingInput) {
                        ratingInput.value = selectedRating;
                    }
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
            const starsContainer = document.getElementById('ratingStars');
            if (starsContainer) {
                starsContainer.addEventListener('mouseleave', function() {
                    stars.forEach((s, i) => {
                        s.style.color = i < selectedRating ? '#fbbf24' : '#d1d5db';
                    });
                });
            }
        }, 10);
    }
    
    // Set values and show modal
    const reservationInput = document.getElementById('reviewReservationId');
    const ratingInput = document.getElementById('reviewRating');
    const form = document.getElementById('reviewForm');
    const errorDiv = document.getElementById('reviewError');
    const successDiv = document.getElementById('reviewSuccess');
    const stars = document.querySelectorAll('#ratingStars span');
    
    if (reservationInput) {
        reservationInput.value = reservationId || '';
    }
    if (ratingInput) {
        ratingInput.value = '';
    }
    if (form) {
        form.reset();
    }
    if (errorDiv) {
        errorDiv.style.display = 'none';
    }
    if (successDiv) {
        successDiv.style.display = 'none';
    }
    if (stars && stars.length > 0) {
        stars.forEach(s => s.style.color = '#d1d5db');
    }
    
    if (modal) {
        modal.style.display = 'flex';
    }
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

