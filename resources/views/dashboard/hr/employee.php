<?php
$pageTitle = 'Employee Details | Hotela';
$records = $records ?? [];
$payrollHistory = $payrollHistory ?? [];
$attendanceSummary = $attendanceSummary ?? [];
ob_start();
?>
<section class="card">
    <header class="employee-header">
        <div>
            <h2><?= htmlspecialchars($user['name']); ?></h2>
            <p class="employee-subtitle"><?= htmlspecialchars($user['email']); ?> • <?= htmlspecialchars($user['role_name'] ?? $user['role_key']); ?></p>
        </div>
        <a class="btn btn-ghost" href="<?= base_url('dashboard/hr'); ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
            Back to HR
        </a>
    </header>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert danger"><?= htmlspecialchars($_GET['error']); ?></div>
    <?php elseif (!empty($_GET['success'])): ?>
        <div class="alert success"><?= htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>

    <div class="employee-tabs">
        <button class="tab-button active" data-tab="records">Employee Records</button>
        <button class="tab-button" data-tab="payroll">Payroll History</button>
        <button class="tab-button" data-tab="attendance">Attendance</button>
    </div>

    <!-- Employee Records Tab -->
    <div class="tab-content active" id="tab-records">
        <div class="section-header">
            <h3>Employee Records</h3>
            <button class="btn btn-primary btn-small" onclick="document.getElementById('add-record-form').style.display='block'">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Add Record
            </button>
        </div>

        <form id="add-record-form" method="post" action="<?= base_url('dashboard/hr/employee/record'); ?>" style="display: none; margin-bottom: 1.5rem; padding: 1.5rem; background: #f8fafc; border-radius: 0.75rem; border: 1px solid #e2e8f0;">
            <input type="hidden" name="user_id" value="<?= (int)$user['id']; ?>">
            <div class="form-grid">
                <label>
                    <span>Record Type *</span>
                    <select name="type" required class="modern-select">
                        <option value="note">Note</option>
                        <option value="performance">Performance</option>
                        <option value="disciplinary">Disciplinary</option>
                        <option value="training">Training</option>
                        <option value="award">Award</option>
                        <option value="other">Other</option>
                    </select>
                </label>
                <label class="form-field-full">
                    <span>Title *</span>
                    <input type="text" name="title" required class="modern-input" placeholder="Record title">
                </label>
                <label class="form-field-full">
                    <span>Description</span>
                    <textarea name="description" rows="3" class="modern-input" placeholder="Record details..."></textarea>
                </label>
            </div>
            <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                <button class="btn btn-primary" type="submit">Add Record</button>
                <button class="btn btn-outline" type="button" onclick="document.getElementById('add-record-form').style.display='none'">Cancel</button>
            </div>
        </form>

        <?php if (empty($records)): ?>
            <div class="empty-state">
                <p>No employee records found. Add a record to get started.</p>
            </div>
        <?php else: ?>
            <div class="records-list">
                <?php foreach ($records as $record): ?>
                    <div class="record-card record-<?= strtolower($record['type']); ?>">
                        <div class="record-header">
                            <div>
                                <h4 class="record-title"><?= htmlspecialchars($record['title']); ?></h4>
                                <span class="record-type"><?= htmlspecialchars(ucfirst($record['type'])); ?></span>
                            </div>
                            <span class="record-date"><?= date('M j, Y', strtotime($record['created_at'])); ?></span>
                        </div>
                        <?php if (!empty($record['description'])): ?>
                            <p class="record-description"><?= nl2br(htmlspecialchars($record['description'])); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Payroll History Tab -->
    <div class="tab-content" id="tab-payroll">
        <div class="section-header">
            <h3>Payroll History</h3>
        </div>

        <?php if (empty($payrollHistory)): ?>
            <div class="empty-state">
                <p>No payroll records found for this employee.</p>
            </div>
        <?php else: ?>
            <div class="payroll-history-table">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th>Basic Salary</th>
                            <th>Allowances</th>
                            <th>Deductions</th>
                            <th>Net Salary</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payrollHistory as $payroll): ?>
                            <tr>
                                <td>
                                    <?= date('M Y', strtotime($payroll['pay_period_end'])); ?>
                                </td>
                                <td>KES <?= number_format((float)$payroll['basic_salary'], 2); ?></td>
                                <td>KES <?= number_format((float)$payroll['allowances'], 2); ?></td>
                                <td>KES <?= number_format((float)$payroll['deductions'], 2); ?></td>
                                <td><strong>KES <?= number_format((float)$payroll['net_salary'], 2); ?></strong></td>
                                <td>
                                    <span class="status-badge status-<?= strtolower($payroll['status']); ?>">
                                        <?= htmlspecialchars(ucfirst($payroll['status'])); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Attendance Tab -->
    <div class="tab-content" id="tab-attendance">
        <div class="section-header">
            <h3>Attendance Summary</h3>
        </div>

        <div class="attendance-summary">
            <div class="summary-grid">
                <div class="summary-card">
                    <span class="summary-label">Total Days</span>
                    <span class="summary-value"><?= number_format($attendanceSummary['total_days'] ?? 0); ?></span>
                </div>
                <div class="summary-card summary-success">
                    <span class="summary-label">Present</span>
                    <span class="summary-value"><?= number_format($attendanceSummary['present_days'] ?? 0); ?></span>
                </div>
                <div class="summary-card summary-danger">
                    <span class="summary-label">Absent</span>
                    <span class="summary-value"><?= number_format($attendanceSummary['absent_days'] ?? 0); ?></span>
                </div>
                <div class="summary-card summary-warning">
                    <span class="summary-label">Late</span>
                    <span class="summary-value"><?= number_format($attendanceSummary['late_days'] ?? 0); ?></span>
                </div>
            </div>
            <div class="attendance-note">
                <p><em>Attendance tracking will be integrated with the attendance system.</em></p>
            </div>
        </div>
    </div>
</section>

<style>
.employee-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.employee-header h2 {
    margin: 0 0 0.25rem 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--dark);
}

.employee-subtitle {
    margin: 0;
    font-size: 0.95rem;
    color: #64748b;
}

.employee-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
    border-bottom: 1px solid #e2e8f0;
}

.tab-button {
    padding: 0.75rem 1.5rem;
    background: transparent;
    border: none;
    border-bottom: 2px solid transparent;
    font-size: 0.95rem;
    font-weight: 500;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s ease;
}

.tab-button:hover {
    color: var(--primary);
}

.tab-button.active {
    color: var(--primary);
    border-bottom-color: var(--primary);
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.section-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--dark);
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.form-field-full {
    grid-column: 1 / -1;
}

.modern-input,
.modern-select {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 0.95rem;
    transition: all 0.2s ease;
    font-family: inherit;
}

.modern-input:focus,
.modern-select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(138, 106, 63, 0.1);
}

.records-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.record-card {
    padding: 1.25rem;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    border-left: 4px solid #e2e8f0;
    transition: all 0.2s ease;
}

.record-card:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.record-card.record-performance {
    border-left-color: #3b82f6;
}

.record-card.record-disciplinary {
    border-left-color: #dc2626;
}

.record-card.record-training {
    border-left-color: #16a34a;
}

.record-card.record-award {
    border-left-color: #f59e0b;
}

.record-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.75rem;
}

.record-title {
    margin: 0 0 0.25rem 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--dark);
}

.record-type {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    background: #f1f5f9;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
    color: #64748b;
    text-transform: capitalize;
}

.record-date {
    font-size: 0.875rem;
    color: #64748b;
}

.record-description {
    margin: 0;
    color: #64748b;
    line-height: 1.6;
}

.payroll-history-table {
    overflow-x: auto;
}

.modern-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 0.5rem;
    overflow: hidden;
    border: 1px solid #e2e8f0;
}

.modern-table thead {
    background: #f8fafc;
}

.modern-table th {
    padding: 0.875rem 1rem;
    text-align: left;
    font-size: 0.875rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.modern-table td {
    padding: 1rem;
    border-top: 1px solid #e2e8f0;
    font-size: 0.95rem;
    color: var(--dark);
}

.modern-table tbody tr:hover {
    background: #f8fafc;
}

.attendance-summary {
    padding: 1.5rem;
    background: #f8fafc;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.summary-card {
    padding: 1.5rem;
    background: #fff;
    border-radius: 0.5rem;
    border: 1px solid #e2e8f0;
    text-align: center;
}

.summary-card.summary-success {
    border-color: #16a34a;
    background: #dcfce7;
}

.summary-card.summary-danger {
    border-color: #dc2626;
    background: #fee2e2;
}

.summary-card.summary-warning {
    border-color: #f59e0b;
    background: #fef3c7;
}

.summary-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: #64748b;
    margin-bottom: 0.5rem;
}

.summary-value {
    display: block;
    font-size: 2rem;
    font-weight: 700;
    color: var(--dark);
}

.summary-success .summary-value {
    color: #16a34a;
}

.summary-danger .summary-value {
    color: #dc2626;
}

.summary-warning .summary-value {
    color: #f59e0b;
}

.attendance-note {
    padding: 1rem;
    background: #fff;
    border-radius: 0.5rem;
    border: 1px solid #e2e8f0;
}

.attendance-note p {
    margin: 0;
    color: #64748b;
    font-style: italic;
}

.alert {
    padding: 1rem 1.25rem;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
}

.alert.danger {
    background: #fee2e2;
    border: 1px solid #fecaca;
    color: #991b1b;
}

.alert.success {
    background: #dcfce7;
    border: 1px solid #bbf7d0;
    color: #166534;
}

.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    color: #64748b;
}
</style>

<script>
document.querySelectorAll('.tab-button').forEach(button => {
    button.addEventListener('click', function() {
        const tabId = this.dataset.tab;
        
        // Remove active class from all buttons and contents
        document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
        
        // Add active class to clicked button and corresponding content
        this.classList.add('active');
        document.getElementById('tab-' + tabId).classList.add('active');
    });
});
</script>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

