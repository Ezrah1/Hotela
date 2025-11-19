<?php
$pageTitle = 'Operations Reports | Hotela';
$roomStats = $roomStats ?? ['total_rooms' => 0, 'occupied_rooms' => 0, 'available_rooms' => 0, 'occupancy_rate' => 0, 'reservations_count' => 0];
$checkInOutStats = $checkInOutStats ?? ['check_ins' => 0, 'check_outs' => 0, 'avg_stay_duration' => 0];
$roomStatusBreakdown = $roomStatusBreakdown ?? ['breakdown' => [], 'total' => 0];
$maintenanceStats = $maintenanceStats ?? ['pending' => 0, 'in_progress' => 0, 'completed' => 0, 'total' => 0];
$taskStats = $taskStats ?? ['pending' => 0, 'in_progress' => 0, 'completed' => 0, 'cancelled' => 0, 'total' => 0, 'completion_rate' => 0];
$inventoryAlerts = $inventoryAlerts ?? [];
$attendanceStats = $attendanceStats ?? ['total_staff' => 0, 'total_days' => 0, 'total_hours' => 0];
$filters = $filters ?? ['start' => date('Y-m-01'), 'end' => date('Y-m-d')];

$dateRangeLabel = date('M j, Y', strtotime($filters['start'])) . ' - ' . date('M j, Y', strtotime($filters['end']));

ob_start();
?>
<section class="card">
    <header class="operations-header">
        <div>
            <h2>Operations Reports</h2>
            <p class="operations-subtitle">Operational overview and metrics for <?= htmlspecialchars($dateRangeLabel); ?></p>
        </div>
    </header>

    <form method="get" action="<?= base_url('staff/dashboard/reports/operations'); ?>" class="operations-filters">
        <div class="filter-grid">
            <label>
                <span>Start Date</span>
                <input type="date" name="start" value="<?= htmlspecialchars($filters['start']); ?>" class="modern-input">
            </label>
            <label>
                <span>End Date</span>
                <input type="date" name="end" value="<?= htmlspecialchars($filters['end']); ?>" class="modern-input">
            </label>
            <div class="filter-actions">
                <button class="btn btn-primary" type="submit">Apply Filters</button>
                <a class="btn btn-outline" href="<?= base_url('staff/dashboard/reports/operations?start=' . urlencode(date('Y-m-01')) . '&end=' . urlencode(date('Y-m-d'))); ?>">This Month</a>
                <a class="btn btn-outline" href="<?= base_url('staff/dashboard/reports/operations?start=' . urlencode(date('Y-m-d', strtotime('-6 days'))) . '&end=' . urlencode(date('Y-m-d'))); ?>">Last 7 Days</a>
            </div>
        </div>
    </form>

    <!-- Summary KPIs -->
    <div class="operations-kpis">
        <div class="kpi-card kpi-primary">
            <div class="kpi-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Occupancy Rate</span>
                <span class="kpi-value"><?= number_format($roomStats['occupancy_rate'], 1); ?>%</span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Total Reservations</span>
                <span class="kpi-value"><?= number_format($roomStats['reservations_count']); ?></span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="8.5" cy="7" r="4"></circle>
                    <line x1="20" y1="8" x2="20" y2="14"></line>
                    <line x1="23" y1="11" x2="17" y2="11"></line>
                </svg>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Check-ins</span>
                <span class="kpi-value"><?= number_format($checkInOutStats['check_ins']); ?></span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 11l3 3L22 4"></path>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                </svg>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Check-outs</span>
                <span class="kpi-value"><?= number_format($checkInOutStats['check_outs']); ?></span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Avg Stay Duration</span>
                <span class="kpi-value"><?= number_format($checkInOutStats['avg_stay_duration'], 1); ?> days</span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Task Completion</span>
                <span class="kpi-value"><?= number_format($taskStats['completion_rate'], 1); ?>%</span>
            </div>
        </div>
    </div>

    <!-- Room Statistics -->
    <div class="operations-sections">
        <div class="operations-section">
            <h3 class="section-title">Room Statistics</h3>
            <div class="stats-grid-mini">
                <div class="stat-mini">
                    <span class="stat-label">Total Rooms</span>
                    <span class="stat-value"><?= number_format($roomStats['total_rooms']); ?></span>
                </div>
                <div class="stat-mini">
                    <span class="stat-label">Occupied</span>
                    <span class="stat-value"><?= number_format($roomStats['occupied_rooms']); ?></span>
                </div>
                <div class="stat-mini">
                    <span class="stat-label">Available</span>
                    <span class="stat-value"><?= number_format($roomStats['available_rooms']); ?></span>
                </div>
            </div>
            
            <?php if (!empty($roomStatusBreakdown['breakdown'])): ?>
                <h4 style="margin-top: 1.5rem; margin-bottom: 0.75rem; font-size: 0.95rem; color: #64748b;">Room Status Breakdown</h4>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Count</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roomStatusBreakdown['breakdown'] as $status => $count): ?>
                            <tr>
                                <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $status))); ?></td>
                                <td><?= number_format($count); ?></td>
                                <td><?= $roomStatusBreakdown['total'] > 0 ? number_format(($count / $roomStatusBreakdown['total']) * 100, 1) : 0; ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Task Statistics -->
        <div class="operations-section">
            <h3 class="section-title">Task Management</h3>
            <div class="stats-grid-mini">
                <div class="stat-mini">
                    <span class="stat-label">Total Tasks</span>
                    <span class="stat-value"><?= number_format($taskStats['total']); ?></span>
                </div>
                <div class="stat-mini stat-success">
                    <span class="stat-label">Completed</span>
                    <span class="stat-value"><?= number_format($taskStats['completed']); ?></span>
                </div>
                <div class="stat-mini stat-warning">
                    <span class="stat-label">In Progress</span>
                    <span class="stat-value"><?= number_format($taskStats['in_progress']); ?></span>
                </div>
                <div class="stat-mini stat-info">
                    <span class="stat-label">Pending</span>
                    <span class="stat-value"><?= number_format($taskStats['pending']); ?></span>
                </div>
            </div>
            <div style="margin-top: 1rem; padding: 1rem; background: #f8fafc; border-radius: 0.5rem;">
                <strong>Completion Rate:</strong> <?= number_format($taskStats['completion_rate'], 1); ?>%
            </div>
        </div>
    </div>

    <!-- Maintenance & Inventory -->
    <div class="operations-sections">
        <?php if ($maintenanceStats['total'] > 0): ?>
        <div class="operations-section">
            <h3 class="section-title">Maintenance Requests</h3>
            <div class="stats-grid-mini">
                <div class="stat-mini stat-info">
                    <span class="stat-label">Pending</span>
                    <span class="stat-value"><?= number_format($maintenanceStats['pending']); ?></span>
                </div>
                <div class="stat-mini stat-warning">
                    <span class="stat-label">In Progress</span>
                    <span class="stat-value"><?= number_format($maintenanceStats['in_progress']); ?></span>
                </div>
                <div class="stat-mini stat-success">
                    <span class="stat-label">Completed</span>
                    <span class="stat-value"><?= number_format($maintenanceStats['completed']); ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Inventory Alerts -->
        <?php if (!empty($inventoryAlerts)): ?>
        <div class="operations-section">
            <h3 class="section-title">Inventory Alerts (Low Stock)</h3>
            <div style="max-height: 300px; overflow-y: auto;">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Current Stock</th>
                            <th>Reorder Level</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventoryAlerts as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['name']); ?></td>
                                <td><?= number_format((float)$item['current_stock'], 2); ?> <?= htmlspecialchars($item['unit'] ?? ''); ?></td>
                                <td><?= number_format((float)$item['reorder_level'], 2); ?> <?= htmlspecialchars($item['unit'] ?? ''); ?></td>
                                <td>
                                    <span class="status status-warning">Low Stock</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Staff Attendance Summary -->
    <?php if ($attendanceStats['total_staff'] > 0): ?>
    <div class="operations-section" style="margin-top: 1.5rem;">
        <h3 class="section-title">Staff Attendance Summary</h3>
        <div class="stats-grid-mini">
            <div class="stat-mini">
                <span class="stat-label">Staff Tracked</span>
                <span class="stat-value"><?= number_format($attendanceStats['total_staff']); ?></span>
            </div>
            <div class="stat-mini">
                <span class="stat-label">Total Days</span>
                <span class="stat-value"><?= number_format($attendanceStats['total_days']); ?></span>
            </div>
            <div class="stat-mini">
                <span class="stat-label">Total Hours</span>
                <span class="stat-value"><?= number_format($attendanceStats['total_hours'], 1); ?>h</span>
            </div>
        </div>
    </div>
    <?php endif; ?>
</section>

<style>
.operations-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.operations-header h2 {
    margin: 0 0 0.25rem 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--dark);
}

.operations-subtitle {
    margin: 0;
    font-size: 0.95rem;
    color: #64748b;
}

.operations-filters {
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
    align-items: end;
}

.filter-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.operations-kpis {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.kpi-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    background: #fff;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.kpi-card.kpi-primary {
    border-left: 4px solid #8b5cf6;
}

.kpi-icon {
    width: 48px;
    height: 48px;
    border-radius: 0.5rem;
    background: #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: #6b7280;
}

.kpi-card.kpi-primary .kpi-icon {
    background: #ede9fe;
    color: #8b5cf6;
}

.kpi-content {
    flex: 1;
}

.kpi-label {
    display: block;
    font-size: 0.875rem;
    color: #64748b;
    margin-bottom: 0.25rem;
}

.kpi-value {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
}

.operations-sections {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.operations-section {
    padding: 1.5rem;
    background: #fff;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
}

.section-title {
    margin: 0 0 1rem 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--dark);
}

.stats-grid-mini {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 1rem;
}

.stat-mini {
    padding: 1rem;
    background: #f8fafc;
    border-radius: 0.5rem;
    text-align: center;
}

.stat-mini.stat-success {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
}

.stat-mini.stat-warning {
    background: #fffbeb;
    border: 1px solid #fef3c7;
}

.stat-mini.stat-info {
    background: #eff6ff;
    border: 1px solid #dbeafe;
}

.stat-label {
    display: block;
    font-size: 0.75rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.5rem;
}

.stat-value {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
}

.modern-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 0.5rem;
    overflow: hidden;
}

.modern-table thead {
    background: #f8fafc;
}

.modern-table th {
    padding: 0.75rem 1rem;
    text-align: left;
    font-weight: 600;
    font-size: 0.875rem;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.modern-table td {
    padding: 0.75rem 1rem;
    border-top: 1px solid #e2e8f0;
    font-size: 0.95rem;
    color: #1e293b;
}

.modern-table tbody tr:hover {
    background: #f8fafc;
}

.status {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 500;
}

.status-warning {
    background: #fef3c7;
    color: #92400e;
}

.modern-input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 0.95rem;
    transition: all 0.2s ease;
    background: #ffffff;
    color: #1e293b;
    font-family: inherit;
}

.modern-input:focus {
    outline: none;
    border-color: #8b5cf6;
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
}

@media (max-width: 768px) {
    .operations-kpis {
        grid-template-columns: repeat(2, 1fr);
    }

    .operations-sections {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

