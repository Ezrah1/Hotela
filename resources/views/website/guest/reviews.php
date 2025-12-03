<?php
ob_start();
?>
<div>
    <div style="margin-bottom: 1.5rem;">
        <a href="<?= base_url('guest/portal'); ?>" class="guest-btn guest-btn-outline" style="display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
            ← Back to Dashboard
        </a>
        <h1 class="guest-page-title">Reviews</h1>
        <p class="guest-page-subtitle">Share your experience and read what others have to say</p>
    </div>

    <!-- Overall Rating Summary -->
    <div class="guest-card" style="text-align: center; padding: 2rem;">
        <div style="font-size: 3rem; font-weight: 700; color: var(--guest-primary); margin-bottom: 0.5rem;">
            <?= number_format($averageRating, 1); ?>/5
        </div>
        <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">
            <?php
            $fullStars = floor($averageRating);
            $halfStar = ($averageRating - $fullStars) >= 0.5;
            for ($i = 0; $i < $fullStars; $i++) echo '★';
            if ($halfStar) echo '½';
            for ($i = $fullStars + ($halfStar ? 1 : 0); $i < 5; $i++) echo '☆';
            ?>
        </div>
        <p style="color: var(--guest-text-light);">
            Based on <?= $totalReviews; ?> review<?= $totalReviews !== 1 ? 's' : ''; ?>
        </p>
    </div>

    <!-- Write Review Form -->
    <div class="guest-card">
        <h2 class="guest-card-title">Write a Review</h2>
        <?php if (!empty($_GET['error'])): ?>
            <div style="padding: 1rem; background: #fee2e2; border: 1px solid #fecaca; border-radius: 0.5rem; margin-bottom: 1rem; color: #991b1b;">
                <?php
                $errors = [
                    'invalid_method' => 'Invalid request method.',
                    'invalid_rating' => 'Please select a valid rating (1-5 stars).',
                    'booking_not_found' => 'Booking not found.',
                    'access_denied' => 'You do not have access to this booking.',
                ];
                echo htmlspecialchars($errors[$_GET['error']] ?? 'An error occurred.');
                ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($_GET['success'])): ?>
            <div style="padding: 1rem; background: #dcfce7; border: 1px solid #bbf7d0; border-radius: 0.5rem; margin-bottom: 1rem; color: #166534;">
                Thank you! Your review has been submitted and is pending approval.
            </div>
        <?php endif; ?>
        <form method="post" action="<?= base_url('guest/reviews/create'); ?>" style="display: grid; gap: 1.5rem;">
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--guest-text);">
                    Rating <span style="color: var(--guest-danger);">*</span>
                </label>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <label style="cursor: pointer;">
                            <input type="radio" name="rating" value="<?= $i; ?>" required style="display: none;">
                            <span class="star-rating" data-rating="<?= $i; ?>" style="font-size: 2rem; color: #ddd; transition: color 0.2s;">★</span>
                        </label>
                    <?php endfor; ?>
                </div>
            </div>

            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--guest-text);">
                    Review Title
                </label>
                <input type="text" name="title" placeholder="Brief summary of your experience" class="modern-input" style="width: 100%; padding: 0.75rem; border: 1px solid var(--guest-border); border-radius: 0.5rem; font-size: 1rem;">
            </div>

            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--guest-text);">
                    Your Review
                </label>
                <textarea name="comment" rows="5" placeholder="Share your experience..." class="modern-input" style="width: 100%; padding: 0.75rem; border: 1px solid var(--guest-border); border-radius: 0.5rem; font-size: 1rem; font-family: inherit; resize: vertical;"></textarea>
            </div>

            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--guest-text);">
                    Category
                </label>
                <select name="category" class="modern-input" style="width: 100%; padding: 0.75rem; border: 1px solid var(--guest-border); border-radius: 0.5rem; font-size: 1rem;">
                    <option value="overall">Overall Experience</option>
                    <option value="room">Room</option>
                    <option value="service">Service</option>
                    <option value="food">Food & Dining</option>
                </select>
            </div>

            <button type="submit" class="guest-btn">Submit Review</button>
        </form>
    </div>

    <!-- My Reviews -->
    <?php if (!empty($myReviews)): ?>
        <div class="guest-card">
            <h2 class="guest-card-title">My Reviews</h2>
            <div style="display: grid; gap: 1.5rem;">
                <?php foreach ($myReviews as $review): ?>
                    <div style="padding: 1.5rem; background: var(--guest-bg); border-radius: 0.5rem; border: 1px solid var(--guest-border);">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                            <div>
                                <h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 0.5rem;">
                                    <?= htmlspecialchars($review['title'] ?: 'Review'); ?>
                                </h3>
                                <div style="font-size: 1.25rem; color: var(--guest-primary); margin-bottom: 0.5rem;">
                                    <?php for ($i = 0; $i < 5; $i++): ?>
                                        <?= $i < (int)$review['rating'] ? '★' : '☆'; ?>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <span class="guest-badge <?= $review['status'] === 'approved' ? 'guest-badge-success' : 'guest-badge-warning'; ?>">
                                <?= ucfirst($review['status']); ?>
                            </span>
                        </div>
                        <?php if ($review['comment']): ?>
                            <p style="color: var(--guest-text); margin-bottom: 0.5rem;"><?= nl2br(htmlspecialchars($review['comment'])); ?></p>
                        <?php endif; ?>
                        <p style="font-size: 0.85rem; color: var(--guest-text-light);">
                            <?= date('M j, Y', strtotime($review['created_at'])); ?> • <?= ucfirst($review['category']); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- All Reviews -->
    <div class="guest-card">
        <h2 class="guest-card-title">What Others Are Saying</h2>
        <?php if (empty($allReviews)): ?>
            <div class="guest-empty">
                <div class="guest-empty-icon">💬</div>
                <p>No reviews yet</p>
                <p style="font-size: 0.9rem; margin-top: 0.5rem; color: var(--guest-text-light);">
                    Be the first to share your experience!
                </p>
            </div>
        <?php else: ?>
            <div style="display: grid; gap: 1.5rem;">
                <?php foreach ($allReviews as $review): ?>
                    <div style="padding: 1.5rem; background: var(--guest-bg); border-radius: 0.5rem; border: 1px solid var(--guest-border);">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                            <div>
                                <h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 0.5rem;">
                                    <?= htmlspecialchars($review['title'] ?: 'Review'); ?>
                                </h3>
                                <div style="font-size: 1.25rem; color: var(--guest-primary); margin-bottom: 0.5rem;">
                                    <?php for ($i = 0; $i < 5; $i++): ?>
                                        <?= $i < (int)$review['rating'] ? '★' : '☆'; ?>
                                    <?php endfor; ?>
                                </div>
                                <p style="font-size: 0.9rem; color: var(--guest-text-light);">
                                    <?= htmlspecialchars($review['guest_name']); ?>
                                </p>
                            </div>
                            <span class="guest-badge guest-badge-info">
                                <?= ucfirst($review['category']); ?>
                            </span>
                        </div>
                        <?php if ($review['comment']): ?>
                            <p style="color: var(--guest-text); margin-bottom: 0.5rem;"><?= nl2br(htmlspecialchars($review['comment'])); ?></p>
                        <?php endif; ?>
                        <p style="font-size: 0.85rem; color: var(--guest-text-light);">
                            <?= date('M j, Y', strtotime($review['created_at'])); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.star-rating:hover,
input[type="radio"]:checked + .star-rating {
    color: var(--guest-primary) !important;
}

input[type="radio"]:checked ~ label .star-rating,
input[type="radio"]:checked + .star-rating ~ .star-rating {
    color: var(--guest-primary) !important;
}

input[type="radio"]:checked ~ label .star-rating {
    color: #ddd !important;
}
</style>

<script>
document.querySelectorAll('input[type="radio"][name="rating"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const rating = parseInt(this.value);
        document.querySelectorAll('.star-rating').forEach((star, index) => {
            if (index < 5 - rating) {
                star.style.color = '#ddd';
            } else {
                star.style.color = 'var(--guest-primary)';
            }
        });
    });
});

document.querySelectorAll('.star-rating').forEach((star, index) => {
    star.addEventListener('mouseenter', function() {
        const rating = parseInt(this.dataset.rating);
        document.querySelectorAll('.star-rating').forEach((s, i) => {
            if (i < 5 - rating) {
                s.style.color = '#ddd';
            } else {
                s.style.color = 'var(--guest-primary)';
            }
        });
    });
});

document.querySelector('form').addEventListener('mouseleave', function() {
    const checked = document.querySelector('input[type="radio"][name="rating"]:checked');
    if (checked) {
        const rating = parseInt(checked.value);
        document.querySelectorAll('.star-rating').forEach((star, index) => {
            if (index < 5 - rating) {
                star.style.color = '#ddd';
            } else {
                star.style.color = 'var(--guest-primary)';
            }
        });
    } else {
        document.querySelectorAll('.star-rating').forEach(star => {
            star.style.color = '#ddd';
        });
    }
});
</script>

<?php
$slot = ob_get_clean();
include view_path('layouts/guest.php');
?>

