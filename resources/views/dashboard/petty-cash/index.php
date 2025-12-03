<?php
$pageTitle = 'Petty Cash Management | Hotela';
$account = $account ?? null;
$transactions = $transactions ?? [];
$summary = $summary ?? ['total_deposits' => 0, 'total_withdrawals' => 0, 'total_expenses' => 0];
$filters = $filters ?? ['start' => date('Y-m-01'), 'end' => date('Y-m-d'), 'type' => ''];

$dateRangeLabel = date('M j, Y', strtotime($filters['start'])) . ' - ' . date('M j, Y', strtotime($filters['end']));
$success = $_GET['success'] ?? null;
$error = $_GET['error'] ?? null;

$currentBalance = $account ? (float)($account['balance'] ?? 0) : 0;
$limitAmount = $account ? (float)($account['limit_amount'] ?? 2000) : 2000;
$availableBalance = $currentBalance;
$balancePercentage = $limitAmount > 0 ? ($currentBalance / $limitAmount) * 100 : 0;

ob_start();
?>
<section class="card">
    <header class="petty-cash-header">
        <div>
            <h2>Petty Cash Management</h2>
            <p class="petty-cash-subtitle">Manage petty cash account for small purchases (Limit: KES <?= number_format($limitAmount, 2); ?>)</p>
        </div>
        <div class="header-actions">
        <a href="<?= base_url('staff/dashboard/petty-cash/deposit'); ?>" class="btn btn-success">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Add Funds
        </a>
        <a href="<?= base_url('staff/dashboard/expenses/create'); ?>" class="btn btn-primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Create Expense
        </a>
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

    <!-- Balance Card -->
    <div class="balance-card">
        <div class="balance-main">
            <div class="balance-info">
                <span class="balance-label">Current Balance</span>
                <span class="balance-amount">KES <?= number_format($currentBalance, 2); ?></span>
            </div>
            <div class="balance-limit">
                <span class="limit-label">Limit</span>
                <span class="limit-amount">KES <?= number_format($limitAmount, 2); ?></span>
            </div>
        </div>
        <div class="balance-progress">
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?= min($balancePercentage, 100); ?>%;"></div>
            </div>
            <span class="progress-text"><?= number_format($balancePercentage, 1); ?>% of limit</span>
        </div>
        <?php if ($account && !empty($account['custodian_name'])): ?>
            <div class="custodian-info">
                <span>Custodian: <strong><?= htmlspecialchars($account['custodian_name']); ?></strong></span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Time Period Quick Filters -->
    <div class="time-period-filters" style="display:flex;gap:0.5rem;margin-bottom:1rem;padding:0.75rem;background:#f8fafc;border-radius:0.5rem;border:1px solid #e5e7eb;">
        <button type="button" class="time-period-btn" data-period="custom">Custom</button>
        <button type="button" class="time-period-btn" data-period="today">Today</button>
        <button type="button" class="time-period-btn" data-period="week">This Week</button>
        <button type="button" class="time-period-btn" data-period="month">This Month</button>
        <button type="button" class="time-period-btn" data-period="year">This Year</button>
        <button type="button" class="time-period-btn" data-period="all">All Time</button>
    </div>

    <form method="get" action="<?= base_url('staff/dashboard/petty-cash'); ?>" class="petty-cash-filters" id="report-filter-form">
        <div class="filter-grid">
            <label>
                <span>Start Date</span>
                <input type="date" name="start" id="date-start" value="<?= htmlspecialchars($filters['start']); ?>" class="modern-input">
            </label>
            <label>
                <span>End Date</span>
                <input type="date" name="end" id="date-end" value="<?= htmlspecialchars($filters['end']); ?>" class="modern-input">
            </label>
            <label>
                <span>Transaction Type</span>
                <select name="type" class="modern-select">
                    <option value="">All Types</option>
                    <option value="deposit" <?= $filters['type'] === 'deposit' ? 'selected' : ''; ?>>Deposits</option>
                    <option value="expense" <?= $filters['type'] === 'expense' ? 'selected' : ''; ?>>Expenses</option>
                </select>
            </label>
            <div class="filter-actions">
                <button class="btn btn-primary" type="submit">Apply Filters</button>
                <button type="button" class="btn btn-outline" id="clear-filters">Clear</button>
            </div>
        </div>
    </form>

    <!-- Summary KPIs -->
    <div class="petty-cash-kpis">
        <div class="kpi-card kpi-success">
            <div class="kpi-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Total Deposits</span>
                <span class="kpi-value">KES <?= number_format($summary['total_deposits'] ?? 0, 2); ?></span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Expenses Paid</span>
                <span class="kpi-value">KES <?= number_format($summary['total_expenses'] ?? 0, 2); ?></span>
            </div>
        </div>
    </div>

    <!-- Transactions List -->
    <div class="transactions-section">
        <div class="section-header">
            <h3 class="section-title">Transaction History</h3>
            <?php 
            $user = \App\Support\Auth::user();
            $userRole = $user['role_key'] ?? $user['role'] ?? '';
            if (in_array($userRole, ['admin', 'finance_manager'])): 
            ?>
                <a href="<?= base_url('staff/dashboard/petty-cash/settings'); ?>" class="btn btn-outline btn-sm">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M12 1v6m0 6v6M5.64 5.64l4.24 4.24m4.24 4.24l4.24 4.24M1 12h6m6 0h6M5.64 18.36l4.24-4.24m4.24-4.24l4.24-4.24"></path>
                    </svg>
                    Settings
                </a>
            <?php endif; ?>
        </div>
        <?php if (empty($transactions)): ?>
            <div class="empty-state">
                <svg class="empty-icon" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
                <h3>No Transactions Found</h3>
                <p>No petty cash transactions match your current filters.</p>
            </div>
        <?php else: ?>
            <div class="transactions-table-wrapper">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Receipt/Reference</th>
                            <th>Amount</th>
                            <th>Processed By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td>
                                    <div class="date-time">
                                        <span class="date"><?= date('M j, Y', strtotime($transaction['created_at'])); ?></span>
                                        <span class="time"><?= date('g:i A', strtotime($transaction['created_at'])); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="transaction-type type-<?= strtolower($transaction['transaction_type']); ?>">
                                        <?= ucfirst($transaction['transaction_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="description"><?= htmlspecialchars($transaction['description']); ?></span>
                                    <?php if (!empty($transaction['expense_reference'])): ?>
                                        <a href="<?= base_url('staff/dashboard/expenses/show?id=' . $transaction['expense_id']); ?>" class="expense-link">
                                            (Expense: <?= htmlspecialchars($transaction['expense_reference']); ?>)
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($transaction['receipt_number'])): ?>
                                        <span class="receipt-number"><?= htmlspecialchars($transaction['receipt_number']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="amount-value amount-<?= strtolower($transaction['transaction_type']); ?>">
                                        <?= $transaction['transaction_type'] === 'deposit' ? '+' : '-'; ?>KES <?= number_format((float)$transaction['amount'], 2); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($transaction['processed_by_name'])): ?>
                                        <span class="user-name"><?= htmlspecialchars($transaction['processed_by_name']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
.petty-cash-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.petty-cash-header h2 {
    margin: 0 0 0.25rem 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--dark);
}

.petty-cash-subtitle {
    margin: 0;
    font-size: 0.95rem;
    color: #64748b;
}

.header-actions {
    display: flex;
    gap: 0.75rem;
}

.btn-success {
    background: #16a34a;
    color: #fff;
    border: none;
}

.btn-success:hover {
    background: #15803d;
}

.balance-card {
    background: linear-gradient(135deg, var(--primary) 0%, #a67c52 100%);
    border-radius: 0.75rem;
    padding: 2rem;
    margin-bottom: 2rem;
    color: #fff;
}

.balance-main {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.balance-info {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.balance-label {
    font-size: 0.875rem;
    opacity: 0.9;
}

.balance-amount {
    font-size: 2.5rem;
    font-weight: 700;
}

.balance-limit {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.5rem;
}

.limit-label {
    font-size: 0.875rem;
    opacity: 0.9;
}

.limit-amount {
    font-size: 1.25rem;
    font-weight: 600;
}

.balance-progress {
    margin-bottom: 1rem;
}

.progress-bar {
    width: 100%;
    height: 8px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.progress-fill {
    height: 100%;
    background: #fff;
    transition: width 0.3s ease;
}

.progress-text {
    font-size: 0.875rem;
    opacity: 0.9;
}

.custodian-info {
    padding-top: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.2);
    font-size: 0.875rem;
}

.petty-cash-filters {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #f8fafc;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    align-items: flex-end;
}

.filter-grid label {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-grid label span {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--dark);
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

.filter-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.petty-cash-kpis {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.25rem;
    margin-bottom: 2rem;
}

.kpi-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    transition: all 0.2s ease;
}

.kpi-card:hover {
    border-color: var(--primary);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.kpi-card.kpi-success {
    border-left: 4px solid #16a34a;
}

.kpi-card.kpi-warning {
    border-left: 4px solid #f59e0b;
}

.kpi-icon {
    width: 48px;
    height: 48px;
    border-radius: 0.5rem;
    background: rgba(138, 106, 63, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary);
    flex-shrink: 0;
}

.kpi-card.kpi-success .kpi-icon {
    background: rgba(22, 163, 74, 0.1);
    color: #16a34a;
}

.kpi-card.kpi-warning .kpi-icon {
    background: rgba(245, 158, 11, 0.1);
    color: #f59e0b;
}

.kpi-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.kpi-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #64748b;
}

.kpi-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--dark);
}

.transactions-section {
    padding: 1.5rem;
    background: #f8fafc;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.section-title {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--dark);
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

.transactions-table-wrapper {
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

.date-time {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.date {
    font-weight: 600;
    color: var(--dark);
}

.time {
    font-size: 0.875rem;
    color: #64748b;
}

.transaction-type {
    display: inline-flex;
    align-items: center;
    padding: 0.375rem 0.75rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: capitalize;
}

.transaction-type.type-deposit {
    background: #dcfce7;
    color: #16a34a;
}


.transaction-type.type-expense {
    background: #fee2e2;
    color: #dc2626;
}

.description {
    color: var(--dark);
    display: block;
    margin-bottom: 0.25rem;
}

.expense-link {
    font-size: 0.875rem;
    color: var(--primary);
    text-decoration: none;
}

.expense-link:hover {
    text-decoration: underline;
}

.receipt-number {
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
    color: var(--primary);
}

.amount-value {
    font-size: 1rem;
    font-weight: 700;
}

.amount-value.amount-deposit {
    color: #16a34a;
}

.amount-value.amount-expense {
    color: #dc2626;
}

.user-name {
    color: var(--dark);
}

.text-muted {
    color: #94a3b8;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-icon {
    color: #cbd5e1;
    margin-bottom: 1.5rem;
}

.empty-state h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--dark);
}

.empty-state p {
    margin: 0;
    color: #64748b;
}

.alert {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
    font-size: 0.95rem;
}

.alert-success {
    background: #dcfce7;
    color: #16a34a;
    border: 1px solid #86efac;
}

.alert-error {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fca5a5;
}

.alert svg {
    flex-shrink: 0;
}

@media (max-width: 768px) {
    .petty-cash-header {
        flex-direction: column;
        gap: 1rem;
    }

    .header-actions {
        width: 100%;
    }

    .header-actions .btn {
        flex: 1;
    }

    .balance-main {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .filter-grid {
        grid-template-columns: 1fr;
    }

    .petty-cash-kpis {
        grid-template-columns: 1fr;
    }

    .transactions-table-wrapper {
        overflow-x: scroll;
    }
}
</style>

<script>
let activeTimePeriod = null;

function formatLocalDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function setTimePeriod(period) {
    activeTimePeriod = period;
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    let dateFrom = '';
    let dateTo = formatLocalDate(today);
    
    switch(period) {
        case 'custom':
            document.getElementById('date-start')?.focus();
            activeTimePeriod = 'custom';
            document.querySelectorAll('.time-period-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.period === 'custom');
            });
            return;
        case 'today':
            dateFrom = dateTo;
            break;
        case 'week':
            const weekAgo = new Date(today);
            weekAgo.setDate(today.getDate() - 7);
            dateFrom = formatLocalDate(weekAgo);
            break;
        case 'month':
            const monthAgo = new Date(today);
            monthAgo.setMonth(today.getMonth() - 1);
            dateFrom = formatLocalDate(monthAgo);
            break;
        case 'year':
            const yearAgo = new Date(today);
            yearAgo.setFullYear(today.getFullYear() - 1);
            dateFrom = formatLocalDate(yearAgo);
            break;
        case 'all':
            dateFrom = '';
            dateTo = '';
            break;
    }
    
    document.getElementById('date-start').value = dateFrom;
    document.getElementById('date-end').value = dateTo;
    
    document.querySelectorAll('.time-period-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.period === period);
    });
    
    document.getElementById('report-filter-form').submit();
}

function initFilters() {
    const urlParams = new URLSearchParams(window.location.search);
    const dateFrom = urlParams.get('start') || '';
    const dateTo = urlParams.get('end') || '';
    
    if (dateFrom && dateTo) {
        const today = formatLocalDate(new Date());
        const weekAgoDate = new Date();
        weekAgoDate.setDate(weekAgoDate.getDate() - 7);
        const weekAgo = formatLocalDate(weekAgoDate);
        const monthAgoDate = new Date();
        monthAgoDate.setMonth(monthAgoDate.getMonth() - 1);
        const monthAgo = formatLocalDate(monthAgoDate);
        const yearAgoDate = new Date();
        yearAgoDate.setFullYear(yearAgoDate.getFullYear() - 1);
        const yearAgo = formatLocalDate(yearAgoDate);
        
        if (dateFrom === today && dateTo === today) {
            activeTimePeriod = 'today';
        } else if (dateFrom === weekAgo && dateTo === today) {
            activeTimePeriod = 'week';
        } else if (dateFrom === monthAgo && dateTo === today) {
            activeTimePeriod = 'month';
        } else if (dateFrom === yearAgo && dateTo === today) {
            activeTimePeriod = 'year';
        } else {
            activeTimePeriod = 'custom';
        }
    } else if (!dateFrom && !dateTo) {
        activeTimePeriod = 'all';
    }
    
    document.querySelectorAll('.time-period-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.period === activeTimePeriod);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    initFilters();
    
    document.querySelectorAll('.time-period-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            setTimePeriod(this.dataset.period);
        });
    });
    
    document.getElementById('clear-filters')?.addEventListener('click', function() {
        window.location.href = '<?= base_url('staff/dashboard/petty-cash'); ?>';
    });
    
    document.getElementById('date-start')?.addEventListener('change', function() {
        activeTimePeriod = null;
        document.querySelectorAll('.time-period-btn').forEach(btn => {
            btn.classList.remove('active');
        });
    });
    document.getElementById('date-end')?.addEventListener('change', function() {
        activeTimePeriod = null;
        document.querySelectorAll('.time-period-btn').forEach(btn => {
            btn.classList.remove('active');
        });
    });
});
</script>

<style>
.time-period-btn {
    padding: 0.5rem 1rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    background: white;
    color: #374151;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.time-period-btn:hover {
    background: #f3f4f6;
    border-color: #9ca3af;
}

.time-period-btn.active {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

