<?php
$pageTitle = 'Payslip Details | Hotela';
ob_start();
?>
<section class="card">
    <header class="payslip-detail-header">
        <div>
            <h2>Payslip - <?= date('F Y', strtotime($payslip['pay_period_end'])); ?></h2>
            <p class="payslip-detail-subtitle">Pay Period: <?= date('M j', strtotime($payslip['pay_period_start'])); ?> - <?= date('M j, Y', strtotime($payslip['pay_period_end'])); ?></p>
        </div>
        <a class="btn btn-ghost" href="<?= base_url('staff/dashboard/payslip'); ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
            Back to Payslips
        </a>
    </header>

    <div class="payslip-detail-content">
        <div class="payslip-employee-info">
            <h3>Employee Information</h3>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Name</span>
                    <span class="info-value"><?= htmlspecialchars($payslip['employee_name']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Email</span>
                    <span class="info-value"><?= htmlspecialchars($payslip['employee_email']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Status</span>
                    <span class="status-badge status-<?= strtolower($payslip['status']); ?>">
                        <?= htmlspecialchars(ucfirst($payslip['status'])); ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="payslip-breakdown">
            <h3>Salary Breakdown</h3>
            <div class="breakdown-table">
                <div class="breakdown-row">
                    <span class="breakdown-label">Basic Salary</span>
                    <span class="breakdown-value">KES <?= number_format((float)$payslip['basic_salary'], 2); ?></span>
                </div>
                <div class="breakdown-row breakdown-positive">
                    <span class="breakdown-label">Allowances</span>
                    <span class="breakdown-value">+KES <?= number_format((float)$payslip['allowances'], 2); ?></span>
                </div>
                <div class="breakdown-row breakdown-negative">
                    <span class="breakdown-label">Deductions</span>
                    <span class="breakdown-value">-KES <?= number_format((float)$payslip['deductions'], 2); ?></span>
                </div>
                <div class="breakdown-row breakdown-total">
                    <span class="breakdown-label">Net Salary</span>
                    <span class="breakdown-value">KES <?= number_format((float)$payslip['net_salary'], 2); ?></span>
                </div>
            </div>
        </div>

        <?php if (!empty($payslip['notes'])): ?>
            <div class="payslip-notes">
                <h3>Notes</h3>
                <p><?= nl2br(htmlspecialchars($payslip['notes'])); ?></p>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
.payslip-detail-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.payslip-detail-header h2 {
    margin: 0 0 0.25rem 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--dark);
}

.payslip-detail-subtitle {
    margin: 0;
    font-size: 0.95rem;
    color: #64748b;
}

.payslip-detail-content {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.payslip-employee-info,
.payslip-breakdown,
.payslip-notes {
    padding: 1.5rem;
    background: #f8fafc;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
}

.payslip-employee-info h3,
.payslip-breakdown h3,
.payslip-notes h3 {
    margin: 0 0 1.25rem 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--dark);
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-label {
    font-size: 0.75rem;
    font-weight: 500;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.info-value {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--dark);
}

.breakdown-table {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.breakdown-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: #fff;
    border-radius: 0.5rem;
    border: 1px solid #e2e8f0;
}

.breakdown-row.breakdown-positive {
    border-left: 3px solid #16a34a;
}

.breakdown-row.breakdown-negative {
    border-left: 3px solid #dc2626;
}

.breakdown-row.breakdown-total {
    background: var(--primary);
    color: #fff;
    border-color: var(--primary);
    font-weight: 700;
}

.breakdown-row.breakdown-total .breakdown-label,
.breakdown-row.breakdown-total .breakdown-value {
    color: #fff;
}

.breakdown-label {
    font-size: 0.95rem;
    color: var(--dark);
}

.breakdown-value {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--dark);
}

.breakdown-positive .breakdown-value {
    color: #16a34a;
}

.breakdown-negative .breakdown-value {
    color: #dc2626;
}

.payslip-notes p {
    margin: 0;
    color: #64748b;
    line-height: 1.6;
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

