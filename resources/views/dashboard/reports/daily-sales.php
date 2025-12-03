<?php
$pageTitle = 'Daily Sales Report | Hotela';
$summary = $summary ?? ['orders' => 0, 'revenue' => 0, 'avg_order' => 0, 'best_day' => ['day' => null, 'total' => 0]];
$payments = $payments ?? [];
$trend = $trend ?? [];
$topItems = $topItems ?? [];
$topStaff = $topStaff ?? [];
$filters = $filters ?? ['date' => date('Y-m-d')];

$dateLabel = date('l, F j, Y', strtotime($filters['date']));

ob_start();
?>
<section class="card">
	<header class="booking-staff-header">
		<div>
			<h2>Daily Sales Report</h2>
			<p>Sales performance for <?= htmlspecialchars($dateLabel); ?>.</p>
		</div>
	</header>

	<form method="get" action="<?= base_url('staff/dashboard/reports/daily-sales'); ?>" class="filters-grid" id="report-filter-form" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:0.75rem;margin-bottom:1.25rem;">
		<label>
			<span>Select Date</span>
			<input type="date" name="date" id="date-input" value="<?= htmlspecialchars($filters['date']); ?>">
		</label>
		<div style="display:flex;gap:0.5rem;align-items:flex-end;">
			<button class="btn btn-primary" type="submit">View Report</button>
			<a href="<?= base_url('staff/dashboard/reports/daily-sales?date=' . date('Y-m-d')); ?>" class="btn btn-outline">Today</a>
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
	</div>

	<?php if (!empty($payments)): ?>
		<div style="margin-top:2rem;">
			<h3 style="margin-bottom:1rem;">Payment Method Breakdown</h3>
			<table class="table-lite">
				<thead>
					<tr>
						<th>Payment Method</th>
						<th>Orders</th>
						<th>Revenue</th>
						<th>Percentage</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					$totalRevenue = $summary['revenue'] > 0 ? $summary['revenue'] : 1;
					foreach ($payments as $payment): 
						$percentage = ($payment['total'] / $totalRevenue) * 100;
					?>
						<tr>
							<td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $payment['payment_type']))); ?></td>
							<td><?= number_format($payment['orders']); ?></td>
							<td>KES <?= number_format($payment['total'], 2); ?></td>
							<td><?= number_format($percentage, 1); ?>%</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>

	<?php if (!empty($topItems)): ?>
		<div style="margin-top:2rem;">
			<h3 style="margin-bottom:1rem;">Top Selling Items</h3>
			<table class="table-lite">
				<thead>
					<tr>
						<th>Item</th>
						<th>Quantity</th>
						<th>Revenue</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($topItems as $item): ?>
						<tr>
							<td><?= htmlspecialchars($item['item_name']); ?></td>
							<td><?= number_format($item['quantity'], 0); ?></td>
							<td>KES <?= number_format($item['revenue'], 2); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>

	<?php if (!empty($topStaff)): ?>
		<div style="margin-top:2rem;">
			<h3 style="margin-bottom:1rem;">Top Performing Staff</h3>
			<table class="table-lite">
				<thead>
					<tr>
						<th>Staff Member</th>
						<th>Orders</th>
						<th>Revenue</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($topStaff as $staff): ?>
						<tr>
							<td><?= htmlspecialchars($staff['staff_name']); ?></td>
							<td><?= number_format($staff['orders']); ?></td>
							<td>KES <?= number_format($staff['revenue'], 2); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>

	<?php if (empty($summary['orders'])): ?>
		<div style="text-align:center;padding:3rem;color:#64748b;">
			<p>No sales recorded for this date.</p>
		</div>
	<?php endif; ?>
</section>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

