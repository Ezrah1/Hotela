<?php
$pageTitle = 'POS Dashboard | Hotela';
$filters = $filters ?? ['start' => date('Y-m-01'), 'end' => date('Y-m-d')];
$summary = $summary ?? ['revenue' => 0, 'orders' => 0, 'avg_order' => 0, 'best_day' => ['day' => null, 'total' => 0]];
$payments = $payments ?? [];
$trend = $trend ?? [];
$topItems = $topItems ?? [];
$topCategories = $topCategories ?? [];
$topStaff = $topStaff ?? [];

ob_start();
?>
<section class="card">
	<header class="booking-staff-header">
		<div>
			<h2>POS Dashboard</h2>
			<p>Monitor real-time performance and quickly jump into sales.</p>
		</div>
		<div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
			<a class="btn btn-primary" href="<?= base_url('dashboard/pos'); ?>">Open POS</a>
			<a class="btn btn-outline" href="<?= base_url('dashboard/reports/sales'); ?>">Full Reports</a>
		</div>
	</header>

	<form method="get" action="<?= base_url('dashboard/pos/dashboard'); ?>" class="filters-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:0.75rem;margin-bottom:1rem;">
		<label>
			<span>Start</span>
			<input type="date" name="start" value="<?= htmlspecialchars($filters['start']); ?>">
		</label>
		<label>
			<span>End</span>
			<input type="date" name="end" value="<?= htmlspecialchars($filters['end']); ?>">
		</label>
		<div style="display:flex;gap:0.5rem;align-items:flex-end;">
			<button class="btn btn-outline" type="submit">Apply</button>
			<a class="btn btn-outline" href="<?= base_url('dashboard/pos/dashboard?start=' . urlencode(date('Y-m-d', strtotime('-6 days'))) . '&end=' . urlencode(date('Y-m-d'))); ?>">Last 7 days</a>
		</div>
	</form>

	<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));">
		<div>
			<small>Revenue</small>
			<span>KES <?= number_format($summary['revenue'], 2); ?></span>
		</div>
		<div>
			<small>Orders</small>
			<span><?= number_format($summary['orders']); ?></span>
		</div>
		<div>
			<small>Average Order</small>
			<span>KES <?= number_format($summary['avg_order'], 2); ?></span>
		</div>
		<div>
			<small>Best Day</small>
			<span><?= $summary['best_day']['day'] ? date('M j', strtotime($summary['best_day']['day'])) . ' • KES ' . number_format($summary['best_day']['total'], 2) : 'n/a'; ?></span>
		</div>
	</div>

	<div class="grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1rem;margin-top:1.5rem;">
		<section class="card" style="border:1px solid #e2e8f0;">
			<h3>Payment Mix</h3>
			<?php if (empty($payments)): ?>
				<p class="muted">No payments recorded.</p>
			<?php else: ?>
				<ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:0.5rem;">
					<?php foreach ($payments as $row):
						$percent = $summary['revenue'] > 0 ? round(((float)$row['total'] / $summary['revenue']) * 100) : 0;
					?>
						<li style="display:flex;justify-content:space-between;align-items:center;">
							<div>
								<strong><?= htmlspecialchars(ucfirst($row['payment_type'])); ?></strong>
								<span class="muted" style="margin-left:0.5rem;"><?= (int)$row['orders']; ?> orders</span>
							</div>
							<div>
								KES <?= number_format((float)$row['total'], 0); ?>
								<small class="muted">(<?= $percent; ?>%)</small>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>

		<section class="card" style="border:1px solid #e2e8f0;">
			<h3>Top Categories</h3>
			<?php if (empty($topCategories)): ?>
				<p class="muted">No category data for this range.</p>
			<?php else: ?>
				<?php $maxCat = max(array_column($topCategories, 'revenue')) ?: 1; ?>
				<ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:0.5rem;">
					<?php foreach ($topCategories as $category):
						$percent = min(100, round(((float)$category['revenue'] / $maxCat) * 100));
					?>
						<li>
							<div style="display:flex;justify-content:space-between;">
								<strong><?= htmlspecialchars($category['category_name']); ?></strong>
								<span>KES <?= number_format((float)$category['revenue'], 0); ?></span>
							</div>
							<div style="height:6px;background:#e2e8f0;border-radius:999px;margin-top:0.3rem;">
								<div style="width:<?= $percent; ?>%;height:6px;border-radius:999px;background:#f59e0b;"></div>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>

		<section class="card" style="border:1px solid #e2e8f0;">
			<h3>Top Items</h3>
			<?php if (empty($topItems)): ?>
				<p class="muted">No items sold.</p>
			<?php else: ?>
				<table class="table-lite">
					<thead>
					<tr>
						<th>Item</th>
						<th>Qty</th>
						<th>Total</th>
					</tr>
					</thead>
					<tbody>
					<?php foreach ($topItems as $item): ?>
						<tr>
							<td><?= htmlspecialchars($item['item_name']); ?></td>
							<td><?= number_format((float)$item['quantity']); ?></td>
							<td>KES <?= number_format((float)$item['revenue'], 0); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</section>

		<section class="card" style="border:1px solid #e2e8f0;">
			<h3>Top Staff</h3>
			<?php if (empty($topStaff)): ?>
				<p class="muted">No sales recorded.</p>
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
							<td>KES <?= number_format((float)$staff['revenue'], 0); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</section>
	</div>

	<section class="card" style="margin-top:1.5rem;border:1px solid #e2e8f0;">
		<h3>Daily Trend</h3>
		<?php if (empty($trend)): ?>
			<p class="muted">No sales during this period.</p>
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
					foreach ($trend as $row):
						$percent = min(100, round(((float)$row['total'] / $maxTotal) * 100));
					?>
						<tr>
							<td><?= htmlspecialchars(date('M j', strtotime($row['day']))); ?></td>
							<td><?= (int)$row['orders']; ?></td>
							<td>KES <?= number_format((float)$row['total'], 0); ?></td>
							<td>
								<div style="height:8px;background:#e2e8f0;border-radius:999px;">
									<div style="width:<?= $percent; ?>%;height:8px;border-radius:999px;background:#0ea5e9;"></div>
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


