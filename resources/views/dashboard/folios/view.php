<?php
use App\Support\Auth;
$pageTitle = 'Folio: ' . htmlspecialchars($reservation['reference']);
$roleConfig = config('roles', [])[Auth::role()] ?? [];

ob_start();
?>
<section class="card">
    <header class="booking-staff-header">
        <div>
            <h2>Folio: <?= htmlspecialchars($reservation['reference']); ?></h2>
            <p>
                <?= htmlspecialchars($reservation['guest_name']); ?>
                <?php if ($reservation['check_in'] && $reservation['check_out']): ?>
                    · <?= htmlspecialchars($reservation['check_in']); ?> → <?= htmlspecialchars($reservation['check_out']); ?>
                <?php elseif ($folio['guest_email'] || $folio['guest_phone']): ?>
                    · Guest Folio
                <?php endif; ?>
            </p>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <a class="btn btn-outline" href="<?= base_url('staff/dashboard/folios'); ?>">Back to Folios</a>
            <?php if ($reservation['id']): ?>
                <a class="btn btn-outline" href="<?= base_url('staff/dashboard/bookings/folio?reservation_id=' . (int)$reservation['id']); ?>">Manage Folio</a>
            <?php else: ?>
                <a class="btn btn-outline" href="<?= base_url('staff/dashboard/bookings/folio?ref=' . urlencode($reservation['reference'])); ?>">Manage Folio</a>
            <?php endif; ?>
        </div>
    </header>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert danger"><?= htmlspecialchars($_GET['error']); ?></div>
    <?php elseif (!empty($_GET['success'])): ?>
        <div class="alert success"><?= htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>

    <div class="folio-summary" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin: 2rem 0; padding: 1.5rem; background: #f8fafc; border-radius: 0.75rem;">
        <div>
            <p style="font-size: 0.875rem; color: #64748b; margin: 0 0 0.5rem 0;">Total Charges</p>
            <strong style="font-size: 1.5rem; color: #1e293b;"><?= format_currency($folio['total']); ?></strong>
        </div>
        <div>
            <p style="font-size: 0.875rem; color: #64748b; margin: 0 0 0.5rem 0;">Balance</p>
            <strong style="font-size: 1.5rem; color: <?= (float)$folio['balance'] > 0 ? '#dc2626' : '#059669'; ?>;">
                <?= (float)$folio['balance'] > 0 ? format_currency($folio['balance']) : 'Settled'; ?>
            </strong>
        </div>
        <div>
            <p style="font-size: 0.875rem; color: #64748b; margin: 0 0 0.5rem 0;">Status</p>
            <strong style="font-size: 1.5rem; color: #1e293b;">
                <span class="badge badge-<?= $folio['status'] === 'open' ? 'primary' : 'success'; ?>">
                    <?= ucfirst($folio['status']); ?>
                </span>
            </strong>
        </div>
        <div>
            <p style="font-size: 0.875rem; color: #64748b; margin: 0 0 0.5rem 0;">Room</p>
            <strong style="font-size: 1.5rem; color: #1e293b;">
                <?= htmlspecialchars($folio['room_display_name'] ?? $folio['room_number'] ?? 'Unassigned'); ?>
            </strong>
        </div>
    </div>

    <h3 style="margin: 2rem 0 1rem 0;">Folio Entries</h3>
    <?php if (empty($entries)): ?>
        <div class="empty-state" style="text-align: center; padding: 2rem; color: #64748b;">
            <p>No entries in this folio yet.</p>
        </div>
    <?php else: ?>
        <table class="table-lite">
            <thead>
            <tr>
                <th>Date</th>
                <th>Description</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Source</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($entries as $entry): ?>
                <tr>
                    <td><?= date('M j, Y g:i A', strtotime($entry['created_at'])); ?></td>
                    <td><?= htmlspecialchars($entry['description']); ?></td>
                    <td>
                        <span class="badge badge-<?= $entry['type'] === 'charge' ? 'warning' : 'success'; ?>">
                            <?= ucfirst($entry['type']); ?>
                        </span>
                    </td>
                    <td style="text-align: right; font-weight: 600; color: <?= $entry['type'] === 'charge' ? '#dc2626' : '#059669'; ?>;">
                        <?= $entry['type'] === 'charge' ? '+' : '-'; ?><?= format_currency(abs((float)$entry['amount'])); ?>
                    </td>
                    <td><?= htmlspecialchars($entry['source'] ?? 'Manual'); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

