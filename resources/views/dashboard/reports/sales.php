<?php
$pageTitle = 'Sales Report | Hotela';
$summary = $summary ?? ['orders' => 0, 'revenue' => 0, 'avg_order' => 0, 'best_day' => ['day' => null, 'total' => 0]];
$payments = $payments ?? [];
$trend = $trend ?? [];
$topItems = $topItems ?? [];
$topStaff = $topStaff ?? [];
$filters = $filters ?? ['start' => date('Y-m-01'), 'end' => date('Y-m-d')];

$dateRangeLabel = date('M j, Y', strtotime($filters['start'])) . ' - ' . date('M j, Y', strtotime($filters['end']));

ob_start();
?>
<section class="card">
	<header class="booking-staff-header">
		<div>
			<h2>Sales Report</h2>
			<p>Performance overview for POS revenue between <?= htmlspecialchars($dateRangeLabel); ?>.</p>
		</div>
	</header>

	<!-- Time Period Quick Filters -->
	<div class="time-period-filters" style="display:flex;gap:0.5rem;margin-bottom:1rem;padding:0.75rem;background:#f8fafc;border-radius:0.5rem;border:1px solid #e5e7eb;">
		<button type="button" class="time-period-btn" data-period="custom">Custom</button>
		<button type="button" class="time-period-btn" data-period="today">Today</button>
		<button type="button" class="time-period-btn" data-period="week">This Week</button>
		<button type="button" class="time-period-btn" data-period="month">This Month</button>
		<button type="button" class="time-period-btn" data-period="year">This Year</button>
		<button type="button" class="time-period-btn" data-period="all">All Time</button>
	</div>

	<form method="get" action="<?= base_url('staff/dashboard/reports/sales'); ?>" class="filters-grid" id="report-filter-form" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:0.75rem;margin-bottom:1.25rem;">
		<label>
			<span>Start date</span>
			<input type="date" name="start" id="date-start" value="<?= htmlspecialchars($filters['start']); ?>">
		</label>
		<label>
			<span>End date</span>
			<input type="date" name="end" id="date-end" value="<?= htmlspecialchars($filters['end']); ?>">
		</label>
		<div style="display:flex;gap:0.5rem;align-items:flex-end;">
			<button class="btn btn-primary" type="submit">Apply Filters</button>
			<button type="button" class="btn btn-outline" id="clear-filters">Clear</button>
		</div>
	</form>

	<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));">
		<div>
			<small>Total Revenue</small>
			<span>KES <?= number_format($summary['revenue'], 2); ?></span>
		</div>
		<div>
			<small>Total Orders</small>
			<span><?= number_format($summary['orders']); ?></span>
		</div>
		<div>
			<small>Average Order Value</small>
			<span>KES <?= number_format($summary['avg_order'], 2); ?></span>
		</div>
		<div>
			<small>Best Day</small>
			<span><?= $summary['best_day']['day'] ? date('M j', strtotime($summary['best_day']['day'])) . ' • KES ' . number_format($summary['best_day']['total'], 2) : 'n/a'; ?></span>
		</div>
	</div>

	<div class="grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.25rem;margin-top:1.5rem;">
		<section class="card" style="border:1px solid #e2e8f0;">
			<h3 style="margin-bottom:0.5rem;">Payment Breakdown</h3>
			<?php if (empty($payments)): ?>
				<p class="muted">No payment data for this range.</p>
			<?php else: ?>
				<table class="table-lite">
					<thead>
					<tr>
						<th>Method</th>
						<th>Orders</th>
						<th>Total</th>
					</tr>
					</thead>
					<tbody>
					<?php foreach ($payments as $row): ?>
						<tr>
							<td><?= htmlspecialchars(ucfirst($row['payment_type'])); ?></td>
							<td><?= (int)$row['orders']; ?></td>
							<td>KES <?= number_format((float)$row['total'], 2); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</section>

		<section class="card" style="border:1px solid #e2e8f0;">
			<h3 style="margin-bottom:0.5rem;">Top Items</h3>
			<?php if (empty($topItems)): ?>
				<p class="muted">No sales yet.</p>
			<?php else: ?>
				<table class="table-lite">
					<thead>
					<tr>
						<th>Item</th>
						<th>Qty</th>
						<th>Revenue</th>
					</tr>
					</thead>
					<tbody>
					<?php foreach ($topItems as $item): ?>
						<tr>
							<td><?= htmlspecialchars($item['item_name']); ?></td>
							<td><?= number_format((float)$item['quantity'], 0); ?></td>
							<td>KES <?= number_format((float)$item['revenue'], 2); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</section>

		<section class="card" style="border:1px solid #e2e8f0;">
			<h3 style="margin-bottom:0.5rem;">Top Staff</h3>
			<?php if (empty($topStaff)): ?>
				<p class="muted">No staff activity recorded.</p>
			<?php else: ?>
				<table class="table-lite">
					<thead>
					<tr>
						<th>Staff</th>
						<th>Orders</th>
						<th>Revenue</th>
					</tr>
					</thead>
					<tbody>
					<?php foreach ($topStaff as $staff): ?>
						<tr>
							<td><?= htmlspecialchars($staff['staff_name']); ?></td>
							<td><?= (int)$staff['orders']; ?></td>
							<td>KES <?= number_format((float)$staff['revenue'], 2); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</section>
	</div>

	<section class="card" style="margin-top:1.5rem;border:1px solid #e2e8f0;">
		<h3 style="margin-bottom:0.5rem;">Daily Trend</h3>
		<?php if (empty($trend)): ?>
			<p class="muted">No sales recorded in this range.</p>
		<?php else: ?>
			<div style="overflow-x:auto;">
				<table class="table-lite">
					<thead>
					<tr>
						<th>Date</th>
						<th>Orders</th>
						<th>Total</th>
						<th>Visual</th>
					</tr>
					</thead>
					<tbody>
					<?php
					$maxTotal = max(array_column($trend, 'total')) ?: 1;
					foreach ($trend as $day):
						$percent = min(100, round(((float)$day['total'] / $maxTotal) * 100));
					?>
						<tr>
							<td><?= htmlspecialchars(date('M j', strtotime($day['day']))); ?></td>
							<td><?= (int)$day['orders']; ?></td>
							<td>KES <?= number_format((float)$day['total'], 2); ?></td>
							<td>
								<div style="height:8px;background:#e2e8f0;border-radius:999px;">
									<div style="width:<?= $percent; ?>%;height:8px;border-radius:999px;background:linear-gradient(90deg,#0ea5e9,#22c55e);"></div>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</section>
</section>
<script>
// Time period filter functionality
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
            // Focus on date inputs
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
    
    // Update form fields
    document.getElementById('date-start').value = dateFrom;
    document.getElementById('date-end').value = dateTo;
    
    // Update button states
    document.querySelectorAll('.time-period-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.period === period);
    });
    
    // Submit form
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
    
    // Time period buttons
    document.querySelectorAll('.time-period-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            setTimePeriod(this.dataset.period);
        });
    });
    
    // Clear filters
    document.getElementById('clear-filters')?.addEventListener('click', function() {
        window.location.href = '<?= base_url('staff/dashboard/reports/sales'); ?>';
    });
    
    // Date inputs - set custom when manually changed
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


