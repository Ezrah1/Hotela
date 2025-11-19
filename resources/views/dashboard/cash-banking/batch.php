<?php
$pageTitle = 'Cash Banking Batch | Hotela';
$batch = $batch ?? null;
$shifts = $shifts ?? [];
$reconciliation = $reconciliation ?? null;

if (!$batch) {
    header('Location: ' . base_url('staff/dashboard/cash-banking?error=Batch%20not%20found'));
    exit;
}

$success = $_GET['success'] ?? null;
$error = $_GET['error'] ?? null;

$canReconcile = $batch['status'] === 'unbanked' && !$reconciliation;
$canApprove = $reconciliation && $reconciliation['status'] === 'pending';
$canBank = $batch['status'] === 'ready_for_banking';
$isBanked = $batch['status'] === 'banked';

ob_start();
?>
<section class="card">
    <header class="batch-header">
        <div>
            <h2>Cash Banking Batch: <?= htmlspecialchars($batch['batch_reference']); ?></h2>
            <p class="subtitle">
                Status: <strong><?= ucfirst(str_replace('_', ' ', $batch['status'])); ?></strong>
            </p>
        </div>
        <a href="<?= base_url('staff/dashboard/cash-banking'); ?>" class="btn btn-secondary">Back to List</a>
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

    <!-- Batch Summary -->
    <div class="batch-summary">
        <div class="summary-item">
            <span class="label">Total Cash</span>
            <span class="value">KES <?= number_format((float)$batch['total_cash'], 2); ?></span>
        </div>
        <div class="summary-item">
            <span class="label">Shift Date</span>
            <span class="value"><?= date('F j, Y', strtotime($batch['shift_date'])); ?></span>
        </div>
        <div class="summary-item">
            <span class="label">Scheduled Banking</span>
            <span class="value"><?= date('F j, Y', strtotime($batch['scheduled_banking_date'])); ?></span>
        </div>
        <?php if ($isBanked): ?>
            <div class="summary-item">
                <span class="label">Banked Date</span>
                <span class="value"><?= date('F j, Y', strtotime($batch['banked_date'])); ?></span>
            </div>
            <div class="summary-item">
                <span class="label">Banked By</span>
                <span class="value"><?= htmlspecialchars($batch['banked_by_name'] ?? '-'); ?></span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Shifts in Batch -->
    <div class="shifts-section">
        <h3>Shifts in This Batch</h3>
        <div class="table-responsive">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Cashier</th>
                        <th>Date</th>
                        <th>Cash Declared</th>
                        <th>Cash Calculated</th>
                        <th>Difference</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($shifts as $shift): ?>
                        <tr>
                            <td><?= htmlspecialchars($shift['user_name'] ?? 'Unknown'); ?></td>
                            <td><?= date('M j, Y', strtotime($shift['shift_date'])); ?></td>
                            <td>KES <?= number_format((float)$shift['cash_declared'], 2); ?></td>
                            <td>KES <?= number_format((float)$shift['cash_calculated'], 2); ?></td>
                            <td class="<?= (float)$shift['difference'] >= 0 ? 'positive' : 'negative'; ?>">
                                KES <?= number_format((float)$shift['difference'], 2); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Reconciliation Section -->
    <?php if ($canReconcile): ?>
        <div class="reconciliation-section">
            <h3>Reconcile Cash</h3>
            <p class="section-note">Verify cash declared matches calculated totals (POS sales + Booking payments) and document any adjustments.</p>
            
            <?php
            $totalDeclared = array_sum(array_column($shifts, 'cash_declared'));
            $totalCalculated = $cashBreakdown['total'] ?? array_sum(array_column($shifts, 'cash_calculated'));
            ?>
            
            <div class="cash-breakdown-info">
                <div class="breakdown-item">
                    <span class="label">POS Cash:</span>
                    <span class="value">KES <?= number_format($cashBreakdown['pos_cash'] ?? 0, 2); ?></span>
                </div>
                <div class="breakdown-item">
                    <span class="label">Booking Cash:</span>
                    <span class="value">KES <?= number_format($cashBreakdown['booking_cash'] ?? 0, 2); ?></span>
                </div>
                <div class="breakdown-item total">
                    <span class="label">Total Calculated:</span>
                    <span class="value">KES <?= number_format($totalCalculated, 2); ?></span>
                </div>
            </div>
            
            <form method="post" action="<?= base_url('staff/dashboard/cash-banking/reconcile'); ?>" class="reconciliation-form">
                <input type="hidden" name="batch_id" value="<?= (int)$batch['id']; ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>
                            <span>Total Cash Declared</span>
                            <input type="number" name="cash_declared" step="0.01" required 
                                   value="<?= number_format($totalDeclared, 2, '.', ''); ?>" 
                                   class="modern-input">
                        </label>
                    </div>
                    <div class="form-group">
                        <label>
                            <span>Total Cash Calculated (from POS & Bookings)</span>
                            <input type="number" name="cash_calculated" step="0.01" required 
                                   value="<?= number_format($totalCalculated, 2, '.', ''); ?>" 
                                   class="modern-input" readonly style="background: #f8fafc;">
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label>
                        <span>Adjustment Amount (if any)</span>
                        <input type="number" name="adjustment_amount" step="0.01" 
                               class="modern-input" placeholder="0.00">
                        <small>Enter adjustment if cash declared doesn't match calculated amount</small>
                    </label>
                </div>

                <div class="form-group">
                    <label>
                        <span>Adjustment Reason</span>
                        <textarea name="adjustment_reason" class="modern-input" rows="3" 
                                  placeholder="Explain any adjustments made..."></textarea>
                    </label>
                </div>

                <div class="form-group">
                    <label>
                        <span>Reconciliation Notes</span>
                        <textarea name="notes" class="modern-input" rows="3" 
                                  placeholder="Additional notes about the reconciliation..."></textarea>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary">Create Reconciliation</button>
            </form>
        </div>
    <?php endif; ?>

    <!-- Reconciliation Approval -->
    <?php if ($canApprove): ?>
        <div class="reconciliation-section">
            <h3>Pending Reconciliation</h3>
            <div class="reconciliation-details">
                <div class="detail-row">
                    <span class="label">Cash Declared:</span>
                    <span class="value">KES <?= number_format((float)$reconciliation['cash_declared'], 2); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Cash Calculated:</span>
                    <span class="value">KES <?= number_format((float)$reconciliation['cash_calculated'], 2); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Difference:</span>
                    <span class="value <?= (float)$reconciliation['difference'] >= 0 ? 'positive' : 'negative'; ?>">
                        KES <?= number_format((float)$reconciliation['difference'], 2); ?>
                    </span>
                </div>
                <?php if ((float)$reconciliation['adjustment_amount'] != 0): ?>
                    <div class="detail-row">
                        <span class="label">Adjustment:</span>
                        <span class="value">KES <?= number_format((float)$reconciliation['adjustment_amount'], 2); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Adjustment Reason:</span>
                        <span class="value"><?= htmlspecialchars($reconciliation['adjustment_reason'] ?? '-'); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($reconciliation['notes']): ?>
                    <div class="detail-row">
                        <span class="label">Notes:</span>
                        <span class="value"><?= htmlspecialchars($reconciliation['notes']); ?></span>
                    </div>
                <?php endif; ?>
                <div class="detail-row">
                    <span class="label">Reconciled By:</span>
                    <span class="value"><?= htmlspecialchars($reconciliation['reconciled_by_name'] ?? 'Unknown'); ?></span>
                </div>
            </div>

            <form method="post" action="<?= base_url('staff/dashboard/cash-banking/approve-reconciliation'); ?>" class="approval-form">
                <input type="hidden" name="reconciliation_id" value="<?= (int)$reconciliation['id']; ?>">
                <button type="submit" class="btn btn-success">Approve & Mark Ready for Banking</button>
            </form>
        </div>
    <?php endif; ?>

    <!-- Banking Section -->
    <?php if ($canBank): ?>
        <div class="banking-section">
            <h3>Mark as Banked</h3>
            <p class="section-note">Upload the bank deposit slip or receipt to complete the banking process.</p>
            
            <form method="post" action="<?= base_url('staff/dashboard/cash-banking/mark-banked'); ?>" 
                  enctype="multipart/form-data" class="banking-form">
                <input type="hidden" name="batch_id" value="<?= (int)$batch['id']; ?>">
                
                <div class="form-group">
                    <label>
                        <span>Deposit Slip / Receipt (Required)</span>
                        <input type="file" name="deposit_slip" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required 
                               class="modern-input">
                        <small>Upload scanned deposit slip or receipt (PDF, image, or Word document)</small>
                    </label>
                </div>

                <div class="form-group">
                    <label>
                        <span>Notes (Optional)</span>
                        <textarea name="notes" class="modern-input" rows="3" 
                                  placeholder="Any additional notes about the banking..."></textarea>
                    </label>
                </div>

                <button type="submit" class="btn btn-success">Mark as Banked</button>
            </form>
        </div>
    <?php endif; ?>

    <!-- Banked Details -->
    <?php if ($isBanked && $batch['deposit_slip_path']): ?>
        <div class="banked-details">
            <h3>Banking Complete</h3>
            <div class="detail-row">
                <span class="label">Deposit Slip:</span>
                <span class="value">
                    <a href="<?= asset($batch['deposit_slip_path']); ?>" target="_blank" class="btn btn-sm btn-secondary">
                        View Deposit Slip
                    </a>
                </span>
            </div>
            <?php if ($batch['notes']): ?>
                <div class="detail-row">
                    <span class="label">Notes:</span>
                    <span class="value"><?= htmlspecialchars($batch['notes']); ?></span>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>

<style>
.batch-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #e2e8f0;
}

.batch-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #f8fafc;
    border-radius: 0.5rem;
}

.summary-item {
    display: flex;
    flex-direction: column;
}

.summary-item .label {
    font-size: 0.875rem;
    color: #64748b;
    margin-bottom: 0.5rem;
}

.summary-item .value {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e293b;
}

.shifts-section, .reconciliation-section, .banking-section, .banked-details {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #f8fafc;
    border-radius: 0.5rem;
}

.section-note {
    color: #64748b;
    margin-bottom: 1.5rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.reconciliation-details {
    margin-bottom: 1.5rem;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid #e2e8f0;
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-row .label {
    font-weight: 500;
    color: #64748b;
}

.detail-row .value {
    font-weight: 600;
    color: #1e293b;
}

.detail-row .value.positive {
    color: #059669;
}

.detail-row .value.negative {
    color: #dc2626;
}

.cash-breakdown-info {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.breakdown-item {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid #e2e8f0;
}

.breakdown-item:last-child {
    border-bottom: none;
}

.breakdown-item.total {
    margin-top: 0.5rem;
    padding-top: 0.75rem;
    border-top: 2px solid #cbd5e1;
    font-weight: 600;
}

.breakdown-item .label {
    color: #64748b;
}

.breakdown-item .value {
    color: #1e293b;
    font-weight: 500;
}

.breakdown-item.total .value {
    font-size: 1.125rem;
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

