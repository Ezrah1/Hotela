<?php
$pageTitle = 'Guest Invoices | Hotela';
ob_start();
?>
<section class="card">
    <header class="booking-staff-header">
        <div>
            <h2>Guest Invoices</h2>
            <p class="text-muted" style="margin-top: 0.5rem;">View and manage all guest folios and invoices</p>
        </div>
    </header>

    <!-- Summary Cards -->
    <div class="invoice-summary" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <div class="summary-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; border-radius: 0.75rem;">
            <div style="font-size: 0.875rem; opacity: 0.9; margin-bottom: 0.5rem;">Total Invoices</div>
            <div style="font-size: 2rem; font-weight: 700;"><?= number_format($totalInvoices ?? 0); ?></div>
        </div>
        <div class="summary-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 1.5rem; border-radius: 0.75rem;">
            <div style="font-size: 0.875rem; opacity: 0.9; margin-bottom: 0.5rem;">Total Amount</div>
            <div style="font-size: 2rem; font-weight: 700;">KES <?= number_format($totalAmount ?? 0, 2); ?></div>
        </div>
        <div class="summary-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 1.5rem; border-radius: 0.75rem;">
            <div style="font-size: 0.875rem; opacity: 0.9; margin-bottom: 0.5rem;">Outstanding Balance</div>
            <div style="font-size: 2rem; font-weight: 700;">KES <?= number_format($totalBalance ?? 0, 2); ?></div>
        </div>
        <div class="summary-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; padding: 1.5rem; border-radius: 0.75rem;">
            <div style="font-size: 0.875rem; opacity: 0.9; margin-bottom: 0.5rem;">Open Invoices</div>
            <div style="font-size: 2rem; font-weight: 700;"><?= number_format($openCount ?? 0); ?></div>
        </div>
    </div>

    <!-- Filters -->
    <form method="get" action="<?= base_url('staff/dashboard/invoices'); ?>" class="filter-grid" style="margin-bottom: 1.5rem; padding: 0.75rem; background: #f8fafc; border-radius: 0.5rem;">
        <label>
            <span>Status</span>
            <select name="status" class="modern-input">
                <option value="">All Statuses</option>
                <option value="open" <?= ($status ?? '') === 'open' ? 'selected' : ''; ?>>Open</option>
                <option value="closed" <?= ($status ?? '') === 'closed' ? 'selected' : ''; ?>>Closed</option>
                <option value="refunded" <?= ($status ?? '') === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
            </select>
        </label>
        <label>
            <span>Start Date</span>
            <input type="date" name="start" value="<?= htmlspecialchars($startDate ?? ''); ?>" class="modern-input">
        </label>
        <label>
            <span>End Date</span>
            <input type="date" name="end" value="<?= htmlspecialchars($endDate ?? ''); ?>" class="modern-input">
        </label>
        <div class="filter-actions" style="display: flex; gap: 0.5rem; align-items: flex-end;">
            <button class="btn btn-primary" type="submit">Apply</button>
            <a href="<?= base_url('staff/dashboard/invoices'); ?>" class="btn btn-outline">Clear</a>
        </div>
    </form>

    <!-- Invoices Table -->
    <?php if (empty($invoices)): ?>
        <div class="empty-state" style="text-align: center; padding: 3rem; color: #64748b;">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 1rem;">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
            <h3>No invoices found</h3>
            <p>No invoices match your current filters.</p>
        </div>
    <?php else: ?>
        <div class="invoices-table-wrapper">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Guest</th>
                        <th>Room</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Total</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoices as $invoice): ?>
                        <tr>
                            <td>
                                <code style="background: #f1f5f9; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.875rem;">
                                    <?= htmlspecialchars($invoice['reference'] ?? 'N/A'); ?>
                                </code>
                            </td>
                            <td>
                                <div>
                                    <div style="font-weight: 600; color: var(--dark);"><?= htmlspecialchars($invoice['guest_name'] ?? 'Guest'); ?></div>
                                    <?php if (!empty($invoice['guest_email'])): ?>
                                        <div style="font-size: 0.875rem; color: #64748b; margin-top: 0.25rem;"><?= htmlspecialchars($invoice['guest_email']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($invoice['room_display_name']) || !empty($invoice['room_number'])): ?>
                                    <span class="room-badge"><?= htmlspecialchars($invoice['room_display_name'] ?? $invoice['room_number'] ?? 'Unassigned'); ?></span>
                                <?php else: ?>
                                    <span style="color: #94a3b8;">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars(date('M j, Y', strtotime($invoice['check_in'] ?? ''))); ?></td>
                            <td><?= htmlspecialchars(date('M j, Y', strtotime($invoice['check_out'] ?? ''))); ?></td>
                            <td style="font-weight: 600;">KES <?= number_format((float)($invoice['total'] ?? 0), 2); ?></td>
                            <td>
                                <?php 
                                $balance = (float)($invoice['balance'] ?? 0);
                                $balanceClass = $balance > 0 ? 'text-warning' : ($balance < 0 ? 'text-info' : 'text-success');
                                ?>
                                <span class="<?= $balanceClass; ?>" style="font-weight: 600;">
                                    KES <?= number_format($balance, 2); ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $invoiceStatus = $invoice['status'] ?? 'open';
                                $statusColors = [
                                    'open' => ['bg' => '#fef3c7', 'text' => '#92400e'],
                                    'closed' => ['bg' => '#d1fae5', 'text' => '#065f46'],
                                    'refunded' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
                                ];
                                $statusColor = $statusColors[$invoiceStatus] ?? $statusColors['open'];
                                ?>
                                <span style="display: inline-block; padding: 0.375rem 0.75rem; border-radius: 0.375rem; font-size: 0.875rem; font-weight: 600; background: <?= $statusColor['bg']; ?>; color: <?= $statusColor['text']; ?>;">
                                    <?= ucfirst($invoiceStatus); ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?= base_url('staff/dashboard/bookings/folio?reservation_id=' . (int)($invoice['reservation_id'] ?? 0)); ?>" class="btn btn-outline btn-small">
                                    View Folio
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<style>
.booking-staff-header {
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.booking-staff-header h2 {
    margin: 0 0 0.5rem 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--dark);
}

.invoices-table-wrapper {
    overflow-x: auto;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
}

.modern-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
}

.modern-table thead {
    background: #f8fafc;
}

.modern-table th {
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--dark);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.modern-table td {
    padding: 1rem;
    border-top: 1px solid #e2e8f0;
    font-size: 0.95rem;
}

.modern-table tbody tr:hover {
    background: #f8fafc;
}

.room-badge {
    display: inline-block;
    padding: 0.375rem 0.75rem;
    background: rgba(138, 106, 63, 0.1);
    border-radius: 0.375rem;
    color: var(--primary);
    font-weight: 600;
    font-size: 0.875rem;
}

.btn-small {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

.text-warning {
    color: #d97706;
}

.text-info {
    color: #0284c7;
}

.text-success {
    color: #059669;
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 0.75rem;
    align-items: end;
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

