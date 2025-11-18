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

	<form method="get" action="<?= base_url('dashboard/reports/sales'); ?>" class="filters-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:0.75rem;margin-bottom:1.25rem;">
		<label>
			<span>Start date</span>
			<input type="date" name="start" value="<?= htmlspecialchars($filters['start']); ?>">
		</label>
		<label>
			<span>End date</span>
			<input type="date" name="end" value="<?= htmlspecialchars($filters['end']); ?>">
		</label>
		<div style="display:flex;gap:0.5rem;align-items:flex-end;">
			<button class="btn btn-outline" type="submit">Apply</button>
			<a class="btn btn-outline" href="<?= base_url('dashboard/reports/sales?start=' . urlencode(date('Y-m-01')) . '&end=' . urlencode(date('Y-m-d'))); ?>">This Month</a>
			<a class="btn btn-outline" href="<?= base_url('dashboard/reports/sales?start=' . urlencode(date('Y-m-d', strtotime('-6 days'))) . '&end=' . urlencode(date('Y-m-d'))); ?>">Last 7 days</a>
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
<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');


