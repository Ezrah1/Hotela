<?php
$pageTitle = 'Cash Banking Management | Hotela';
$openShift = $openShift ?? null;
$cashCalculated = $cashCalculated ?? 0;
$unbankedTotal = $unbankedTotal ?? 0;
$unbankedBatches = $unbankedBatches ?? [];
$readyBatches = $readyBatches ?? [];
$bankedBatches = $bankedBatches ?? [];
$user = $user ?? \App\Support\Auth::user();

$isFinanceManager = in_array($user['role_key'] ?? ($user['role'] ?? ''), ['admin', 'finance_manager'], true);
$isCashier = in_array($user['role_key'] ?? ($user['role'] ?? ''), ['admin', 'cashier'], true);

$success = $_GET['success'] ?? null;
$error = $_GET['error'] ?? null;

ob_start();
?>
<section class="card">
    <header class="cash-banking-header">
        <div>
            <h2>Cash Banking Management</h2>
            <p class="subtitle">Track daily cash sales, reconcile, and manage banking</p>
        </div>
    </header>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <?= htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <?= htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Cashier Shift Section -->
    <?php if ($isCashier && $openShift): ?>
        <div class="shift-card">
            <h3>Today's Shift</h3>
            <div class="shift-info">
                <div class="info-row">
                    <span class="label">Date:</span>
                    <span class="value"><?= date('F j, Y', strtotime($openShift['shift_date'])); ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Cash from POS Sales:</span>
                    <span class="value">KES <?= number_format($cashBreakdown['pos_cash'] ?? 0, 2); ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Cash from Bookings:</span>
                    <span class="value">KES <?= number_format($cashBreakdown['booking_cash'] ?? 0, 2); ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Total Cash Calculated:</span>
                    <span class="value"><strong>KES <?= number_format($cashCalculated, 2); ?></strong></span>
                </div>
                <?php if ($openShift['status'] === 'closed'): ?>
                    <div class="info-row">
                        <span class="label">Cash Declared:</span>
                        <span class="value">KES <?= number_format((float)$openShift['cash_declared'], 2); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Difference:</span>
                        <span class="value <?= (float)$openShift['difference'] >= 0 ? 'positive' : 'negative'; ?>">
                            KES <?= number_format((float)$openShift['difference'], 2); ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($openShift['status'] === 'open'): ?>
                <form method="post" action="<?= base_url('staff/dashboard/cash-banking/close-shift'); ?>" class="close-shift-form">
                    <input type="hidden" name="shift_id" value="<?= (int)$openShift['id']; ?>">
                    <div class="form-group">
                        <label>
                            <span>Cash Declared</span>
                            <input type="number" name="cash_declared" step="0.01" min="0" required 
                                   value="<?= number_format($cashCalculated, 2, '.', ''); ?>" 
                                   class="modern-input" placeholder="0.00">
                        </label>
                    </div>
                    <div class="form-group">
                        <label>
                            <span>Notes (Optional)</span>
                            <textarea name="notes" class="modern-input" rows="3" placeholder="Any notes about the shift..."></textarea>
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary">Close Shift</button>
                </form>
            <?php else: ?>
                <div class="alert alert-info">
                    Shift closed on <?= date('M j, Y g:i A', strtotime($openShift['closed_at'])); ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Finance Manager Section -->
    <?php if ($isFinanceManager): ?>
        <div class="unbanked-summary">
            <h3>Unbanked Cash Summary</h3>
            <div class="summary-amount">KES <?= number_format($unbankedTotal, 2); ?></div>
            <a href="<?= base_url('staff/dashboard/cash-banking/unbanked-shifts'); ?>" class="btn btn-primary">
                Create Banking Batch
            </a>
        </div>

        <!-- Unbanked Batches -->
        <?php if (!empty($unbankedBatches)): ?>
            <div class="batches-section">
                <h3>Unbanked Batches</h3>
                <div class="table-responsive">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Scheduled Banking</th>
                                <th>Shifts</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($unbankedBatches as $batch): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($batch['batch_reference']); ?></strong></td>
                                    <td><?= date('M j, Y', strtotime($batch['shift_date'])); ?></td>
                                    <td>KES <?= number_format((float)$batch['total_cash'], 2); ?></td>
                                    <td><?= date('M j, Y', strtotime($batch['scheduled_banking_date'])); ?></td>
                                    <td><?= (int)$batch['shift_count']; ?></td>
                                    <td>
                                        <a href="<?= base_url('staff/dashboard/cash-banking/batch?id=' . $batch['id']); ?>" class="btn btn-sm btn-primary">
                                            View & Reconcile
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Ready for Banking Batches -->
        <?php if (!empty($readyBatches)): ?>
            <div class="batches-section">
                <h3>Ready for Banking</h3>
                <div class="table-responsive">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Scheduled Banking</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($readyBatches as $batch): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($batch['batch_reference']); ?></strong></td>
                                    <td><?= date('M j, Y', strtotime($batch['shift_date'])); ?></td>
                                    <td>KES <?= number_format((float)$batch['total_cash'], 2); ?></td>
                                    <td><?= date('M j, Y', strtotime($batch['scheduled_banking_date'])); ?></td>
                                    <td>
                                        <a href="<?= base_url('staff/dashboard/cash-banking/batch?id=' . $batch['id']); ?>" class="btn btn-sm btn-success">
                                            Mark as Banked
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Banked Batches (Recent) -->
        <?php if (!empty($bankedBatches)): ?>
            <div class="batches-section">
                <h3>Recently Banked</h3>
                <div class="table-responsive">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Banked Date</th>
                                <th>Banked By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($bankedBatches, 0, 10) as $batch): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($batch['batch_reference']); ?></strong></td>
                                    <td><?= date('M j, Y', strtotime($batch['shift_date'])); ?></td>
                                    <td>KES <?= number_format((float)$batch['total_cash'], 2); ?></td>
                                    <td><?= $batch['banked_date'] ? date('M j, Y', strtotime($batch['banked_date'])) : '-'; ?></td>
                                    <td><?= htmlspecialchars($batch['banked_by_name'] ?? '-'); ?></td>
                                    <td>
                                        <a href="<?= base_url('staff/dashboard/cash-banking/batch?id=' . $batch['id']); ?>" class="btn btn-sm btn-secondary">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<style>
.cash-banking-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #e2e8f0;
}

.subtitle {
    color: #64748b;
    margin-top: 0.5rem;
}

.shift-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.shift-card h3 {
    margin-top: 0;
    margin-bottom: 1rem;
    color: #1e293b;
}

.shift-info {
    margin-bottom: 1.5rem;
}

.info-row {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid #e2e8f0;
}

.info-row:last-child {
    border-bottom: none;
}

.info-row .label {
    font-weight: 500;
    color: #64748b;
}

.info-row .value {
    font-weight: 600;
    color: #1e293b;
}

.info-row .value.positive {
    color: #059669;
}

.info-row .value.negative {
    color: #dc2626;
}

.close-shift-form {
    margin-top: 1.5rem;
}

.form-group {
    margin-bottom: 1rem;
}

.unbanked-summary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 0.5rem;
    margin-bottom: 2rem;
    text-align: center;
}

.unbanked-summary h3 {
    margin-top: 0;
    margin-bottom: 1rem;
    color: white;
}

.summary-amount {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
}

.batches-section {
    margin-bottom: 2rem;
}

.batches-section h3 {
    margin-bottom: 1rem;
    color: #1e293b;
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

