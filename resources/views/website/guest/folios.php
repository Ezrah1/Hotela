<?php
ob_start();
?>
<div>
    <h1 class="guest-page-title">My Folios</h1>
    <p class="guest-page-subtitle">View all your charges and payments</p>

    <?php if (empty($folios)): ?>
        <div class="guest-card">
            <p style="text-align: center; color: var(--guest-text-light); padding: 2rem;">
                You don't have any folios yet. Folios are created when you make a booking or have charges added to your account.
            </p>
        </div>
    <?php else: ?>
        <div style="display: grid; gap: 1.5rem;">
            <?php foreach ($folios as $folio): ?>
                <div class="guest-card">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #e2e8f0;">
                        <div>
                            <h2 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem;">
                                <?php if ($folio['reference']): ?>
                                    <?= htmlspecialchars($folio['reference']); ?>
                                <?php else: ?>
                                    Guest Folio #<?= (int)$folio['id']; ?>
                                <?php endif; ?>
                            </h2>
                            <?php if ($folio['room_display_name'] || $folio['room_number']): ?>
                                <p style="font-size: 0.875rem; color: var(--guest-text-light); margin-bottom: 0.25rem;">
                                    Room: <?= htmlspecialchars($folio['room_display_name'] ?? $folio['room_number']); ?>
                                </p>
                            <?php endif; ?>
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
                            <div style="font-size: 1.5rem; font-weight: 700; color: <?= (float)$folio['balance'] > 0 ? '#dc2626' : '#059669'; ?>; margin-bottom: 0.25rem;">
                                <?= (float)$folio['balance'] > 0 ? format_currency($folio['balance']) : 'Settled'; ?>
                            </div>
                            <div style="font-size: 0.875rem; color: var(--guest-text-light);">
                                Total: <?= format_currency($folio['total']); ?>
                            </div>
                            <div style="margin-top: 0.5rem;">
                                <span style="display: inline-block; padding: 0.25rem 0.75rem; background: <?= $folio['status'] === 'open' ? '#dbeafe' : '#d1fae5'; ?>; color: <?= $folio['status'] === 'open' ? '#1e40af' : '#065f46'; ?>; border-radius: 0.25rem; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">
                                    <?= ucfirst($folio['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($folio['entries'])): ?>
                        <div style="margin-bottom: 1rem;">
                            <h3 style="font-size: 0.875rem; font-weight: 600; color: var(--guest-text-light); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.75rem;">Folio Entries</h3>
                            <div style="display: grid; gap: 0.5rem;">
                                <?php foreach ($folio['entries'] as $entry): ?>
                                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f8fafc; border-radius: 0.375rem;">
                                        <div>
                                            <div style="font-weight: 500; margin-bottom: 0.25rem;">
                                                <?= htmlspecialchars($entry['description']); ?>
                                            </div>
                                            <div style="font-size: 0.75rem; color: var(--guest-text-light);">
                                                <?= date('M j, Y g:i A', strtotime($entry['created_at'])); ?>
                                                <?php if ($entry['source']): ?>
                                                    · <?= htmlspecialchars($entry['source']); ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div style="text-align: right;">
                                            <div style="font-weight: 600; color: <?= $entry['type'] === 'charge' ? '#dc2626' : '#059669'; ?>;">
                                                <?= $entry['type'] === 'charge' ? '+' : '-'; ?><?= format_currency(abs((float)$entry['amount'])); ?>
                                            </div>
                                            <div style="font-size: 0.75rem; color: var(--guest-text-light); text-transform: capitalize;">
                                                <?= htmlspecialchars($entry['type']); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div style="display: flex; gap: 0.75rem; margin-top: 1rem;">
                        <?php if ($folio['reservation_id']): ?>
                            <a href="<?= base_url('guest/booking?ref=' . urlencode($folio['reference'])); ?>" class="guest-btn guest-btn-outline">
                                View Booking
                            </a>
                        <?php endif; ?>
                        <a href="<?= base_url('guest/booking?ref=' . urlencode($folio['reference'] ?? 'GUEST-' . $folio['id']) . '&download=receipt'); ?>" class="guest-btn guest-btn-primary">
                            Download Receipt
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
$slot = ob_get_clean();
include view_path('layouts/guest.php');
?>

