<?php
$pageTitle = 'Folio | ' . htmlspecialchars($reservation['guest_name']);
ob_start();
?>
<section class="card">
    <header class="booking-staff-header">
        <div>
            <h2>Folio: <?= htmlspecialchars($reservation['reference']); ?></h2>
            <p><?= htmlspecialchars($reservation['guest_name']); ?> · <?= htmlspecialchars($reservation['check_in']); ?> → <?= htmlspecialchars($reservation['check_out']); ?></p>
        </div>
        <a class="btn btn-outline" href="<?= base_url('dashboard/bookings'); ?>">Back to bookings</a>
    </header>
    <?php if (!empty($_GET['error'])): ?>
        <div class="alert danger"><?= htmlspecialchars($_GET['error']); ?></div>
    <?php elseif (!empty($_GET['success'])): ?>
        <div class="alert success">Entry added successfully.</div>
    <?php endif; ?>
    <div class="folio-summary">
        <div>
            <p>Total Charges</p>
            <strong>KES <?= number_format($folio['total'], 2); ?></strong>
        </div>
        <div>
            <p>Balance</p>
            <strong><?= $folio['balance'] > 0 ? 'KES ' . number_format($folio['balance'], 2) : 'Settled'; ?></strong>
        </div>
        <div>
            <p>Status</p>
            <strong><?= htmlspecialchars($folio['status']); ?></strong>
        </div>
    </div>
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
                <td><?= htmlspecialchars($entry['created_at']); ?></td>
                <td><?= htmlspecialchars($entry['description']); ?></td>
                <td><?= htmlspecialchars(ucfirst($entry['type'])); ?></td>
                <td><?= htmlspecialchars(number_format($entry['amount'], 2)); ?></td>
                <td><?= htmlspecialchars($entry['source'] ?? '-'); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<section class="card">
    <h3>Add Entry</h3>
    <form class="folio-form" method="post" action="<?= base_url('dashboard/bookings/folio-entry'); ?>">
        <input type="hidden" name="reservation_id" value="<?= (int)$reservation['id']; ?>">
        <label>
            <span>Description</span>
            <input type="text" name="description" required>
        </label>
        <label>
            <span>Amount</span>
            <input type="number" step="0.01" name="amount" required>
        </label>
        <label>
            <span>Type</span>
            <select name="type">
                <option value="charge">Charge</option>
                <option value="payment">Payment</option>
            </select>
        </label>
        <label>
            <span>Source</span>
            <input type="text" name="source" placeholder="POS, Cash, M-Pesa...">
        </label>
        <button class="btn btn-primary" type="submit">Add Entry</button>
    </form>
</section>
<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');

