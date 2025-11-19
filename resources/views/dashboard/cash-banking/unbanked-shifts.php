<?php
$pageTitle = 'Create Banking Batch | Hotela';
$shiftsByDate = $shiftsByDate ?? [];

ob_start();
?>
<section class="card">
    <header class="batch-header">
        <div>
            <h2>Create Banking Batch</h2>
            <p class="subtitle">Select closed shifts to create a banking batch</p>
        </div>
        <a href="<?= base_url('staff/dashboard/cash-banking'); ?>" class="btn btn-secondary">Back</a>
    </header>

    <?php if (empty($shiftsByDate)): ?>
        <div class="alert alert-info">
            No unbanked shifts found. All shifts have been included in banking batches.
        </div>
    <?php else: ?>
        <form method="post" action="<?= base_url('staff/dashboard/cash-banking/create-batch'); ?>" id="batch-form">
            <div class="shifts-by-date">
                <?php foreach ($shiftsByDate as $date => $shifts): ?>
                    <div class="date-group">
                        <h3>
                            <input type="checkbox" class="select-all-date" data-date="<?= htmlspecialchars($date); ?>">
                            <?= date('F j, Y', strtotime($date)); ?>
                        </h3>
                        <div class="shifts-list">
                            <?php foreach ($shifts as $shift): ?>
                                <div class="shift-item">
                                    <label>
                                        <input type="checkbox" name="shift_ids[]" value="<?= (int)$shift['id']; ?>" 
                                               class="shift-checkbox" data-date="<?= htmlspecialchars($date); ?>">
                                        <div class="shift-info">
                                            <div class="shift-main">
                                                <strong><?= htmlspecialchars($shift['user_name'] ?? 'Unknown Cashier'); ?></strong>
                                                <span class="shift-amount">KES <?= number_format((float)$shift['cash_declared'], 2); ?></span>
                                            </div>
                                            <div class="shift-details">
                                                <span>Calculated: KES <?= number_format((float)$shift['cash_calculated'], 2); ?></span>
                                                <span class="<?= (float)$shift['difference'] >= 0 ? 'positive' : 'negative'; ?>">
                                                    Diff: KES <?= number_format((float)$shift['difference'], 2); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="batch-summary">
                <div class="summary-row">
                    <span class="label">Selected Shifts:</span>
                    <span class="value" id="selected-count">0</span>
                </div>
                <div class="summary-row">
                    <span class="label">Total Cash:</span>
                    <span class="value" id="total-cash">KES 0.00</span>
                </div>
            </div>

            <div class="form-actions">
                <input type="hidden" name="date" value="<?= date('Y-m-d'); ?>">
                <button type="submit" class="btn btn-primary" id="create-batch-btn" disabled>
                    Create Banking Batch
                </button>
            </div>
        </form>
    <?php endif; ?>
</section>

<style>
.shifts-by-date {
    margin-bottom: 2rem;
}

.date-group {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #f8fafc;
    border-radius: 0.5rem;
}

.date-group h3 {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-top: 0;
    margin-bottom: 1rem;
    color: #1e293b;
}

.shifts-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.shift-item {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    padding: 1rem;
}

.shift-item label {
    display: flex;
    align-items: center;
    gap: 1rem;
    cursor: pointer;
}

.shift-item input[type="checkbox"] {
    width: 1.25rem;
    height: 1.25rem;
    cursor: pointer;
}

.shift-info {
    flex: 1;
}

.shift-main {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.shift-amount {
    font-size: 1.125rem;
    font-weight: 600;
    color: #059669;
}

.shift-details {
    display: flex;
    gap: 1rem;
    font-size: 0.875rem;
    color: #64748b;
}

.shift-details .positive {
    color: #059669;
}

.shift-details .negative {
    color: #dc2626;
}

.batch-summary {
    background: #f8fafc;
    padding: 1.5rem;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid #e2e8f0;
}

.summary-row:last-child {
    border-bottom: none;
}

.summary-row .label {
    font-weight: 500;
    color: #64748b;
}

.summary-row .value {
    font-weight: 600;
    color: #1e293b;
    font-size: 1.125rem;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.shift-checkbox');
    const selectAllCheckboxes = document.querySelectorAll('.select-all-date');
    const selectedCount = document.getElementById('selected-count');
    const totalCash = document.getElementById('total-cash');
    const createBtn = document.getElementById('create-batch-btn');

    function updateSummary() {
        const selected = Array.from(checkboxes).filter(cb => cb.checked);
        const count = selected.length;
        let total = 0;

        selected.forEach(cb => {
            const shiftItem = cb.closest('.shift-item');
            const amountText = shiftItem.querySelector('.shift-amount').textContent;
            const amount = parseFloat(amountText.replace(/[KES,\s]/g, ''));
            total += amount;
        });

        selectedCount.textContent = count;
        totalCash.textContent = 'KES ' + total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        createBtn.disabled = count === 0;
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateSummary);
    });

    selectAllCheckboxes.forEach(selectAll => {
        selectAll.addEventListener('change', function() {
            const date = this.dataset.date;
            const dateCheckboxes = document.querySelectorAll(`.shift-checkbox[data-date="${date}"]`);
            dateCheckboxes.forEach(cb => {
                cb.checked = this.checked;
            });
            updateSummary();
        });
    });

    updateSummary();
});
</script>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

