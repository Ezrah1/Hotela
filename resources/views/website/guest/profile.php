<?php
ob_start();
?>
<div>
    <h1 class="guest-page-title">My Profile</h1>
    <p class="guest-page-subtitle">Manage your account information</p>

    <div class="guest-card">
        <h2 class="guest-card-title">Personal Information</h2>
        <div style="display: grid; gap: 1.5rem;">
            <div>
                <h3 style="font-size: 0.875rem; font-weight: 600; color: var(--guest-text-light); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Full Name</h3>
                <p style="font-size: 1rem;"><?= htmlspecialchars($guest['guest_name'] ?? 'Not provided'); ?></p>
            </div>

            <div>
                <h3 style="font-size: 0.875rem; font-weight: 600; color: var(--guest-text-light); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Email Address</h3>
                <p style="font-size: 1rem;"><?= htmlspecialchars($guest['guest_email'] ?? 'Not provided'); ?></p>
            </div>

            <div>
                <h3 style="font-size: 0.875rem; font-weight: 600; color: var(--guest-text-light); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Phone Number</h3>
                <p style="font-size: 1rem;"><?= htmlspecialchars($guest['guest_phone'] ?? 'Not provided'); ?></p>
            </div>
        </div>
    </div>

    <div class="guest-card">
        <h2 class="guest-card-title">Booking History</h2>
        <p style="color: var(--guest-text-light); margin-bottom: 1rem;">
            You have <?= count($bookings ?? []); ?> booking<?= count($bookings ?? []) !== 1 ? 's' : ''; ?> in total.
        </p>
        <a href="<?= base_url('guest/portal'); ?>" class="guest-btn guest-btn-outline">
            View All Bookings
        </a>
    </div>

    <?php if (!empty($folios)): ?>
    <div class="guest-card">
        <h2 class="guest-card-title">My Folios</h2>
        <p style="color: var(--guest-text-light); margin-bottom: 1rem;">
            You have <?= count($folios); ?> folio<?= count($folios) !== 1 ? 's' : ''; ?> with charges and payments.
        </p>
        
        <div style="display: grid; gap: 1rem; margin-top: 1.5rem;">
            <?php foreach ($folios as $folio): ?>
                <div style="padding: 1rem; background: #f8fafc; border-radius: 0.5rem; border: 1px solid #e2e8f0;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.75rem;">
                        <div>
                            <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.25rem;">
                                <?php if ($folio['reference']): ?>
                                    <?= htmlspecialchars($folio['reference']); ?>
                                <?php else: ?>
                                    Guest Folio #<?= (int)$folio['id']; ?>
                                <?php endif; ?>
                            </h3>
                            <?php if ($folio['check_in'] && $folio['check_out']): ?>
                                <p style="font-size: 0.875rem; color: var(--guest-text-light);">
                                    <?= date('M j, Y', strtotime($folio['check_in'])); ?> → <?= date('M j, Y', strtotime($folio['check_out'])); ?>
                                </p>
                            <?php else: ?>
                                <p style="font-size: 0.875rem; color: var(--guest-text-light);">
                                    Guest Folio
                                </p>
                            <?php endif; ?>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 1.125rem; font-weight: 700; color: <?= (float)$folio['balance'] > 0 ? '#dc2626' : '#059669'; ?>;">
                                <?= (float)$folio['balance'] > 0 ? format_currency($folio['balance']) : 'Settled'; ?>
                            </div>
                            <div style="font-size: 0.75rem; color: var(--guest-text-light);">
                                Total: <?= format_currency($folio['total']); ?>
                            </div>
                        </div>
                    </div>
                    <div style="display: flex; gap: 0.5rem; margin-top: 0.75rem;">
                        <?php if ($folio['reservation_id']): ?>
                            <a href="<?= base_url('guest/booking?ref=' . urlencode($folio['reference'])); ?>" class="guest-btn guest-btn-outline" style="font-size: 0.875rem; padding: 0.5rem 1rem;">
                                View Booking
                            </a>
                        <?php endif; ?>
                        <a href="<?= base_url('guest/booking?ref=' . urlencode($folio['reference'] ?? 'GUEST-' . $folio['id']) . '&download=receipt'); ?>" class="guest-btn guest-btn-outline" style="font-size: 0.875rem; padding: 0.5rem 1rem;">
                            View Receipt
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div style="margin-top: 1.5rem;">
            <a href="<?= base_url('guest/folios'); ?>" class="guest-btn guest-btn-outline">
                View All Folios
            </a>
        </div>
    </div>
    <?php endif; ?>

    <div class="guest-card">
        <h2 class="guest-card-title">Account Actions</h2>
        <div style="display: grid; gap: 1rem;">
            <a href="<?= base_url('contact?subject=password_reset'); ?>" class="guest-btn guest-btn-outline" style="text-align: left;">
                Request Password Reset
            </a>
            <a href="<?= base_url('contact?subject=update_info'); ?>" class="guest-btn guest-btn-outline" style="text-align: left;">
                Update Personal Information
            </a>
        </div>
    </div>
</div>

<?php
$slot = ob_get_clean();
include view_path('layouts/guest.php');
?>

