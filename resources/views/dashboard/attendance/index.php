<?php
$pageTitle = 'Attendance Management | Hotela';
ob_start();
?>
<section class="card">
    <header class="attendance-header">
        <div>
            <h2>Attendance Management</h2>
            <p class="attendance-subtitle">Comprehensive attendance tracking, statistics, and anomaly detection</p>
        </div>
        <div class="header-actions">
            <a href="<?= base_url('staff/dashboard/attendance/my-attendance'); ?>" class="btn btn-outline">
                My Attendance
            </a>
        </div>
    </header>

    <?php if (!empty($_GET['success'])): ?>
        <div class="alert alert-success" style="margin: 1rem 0; padding: 0.75rem 1rem; background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; border-radius: 0.5rem;">
            <?= htmlspecialchars($_GET['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert alert-error" style="margin: 1rem 0; padding: 0.75rem 1rem; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; border-radius: 0.5rem;">
            <?= htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="attendance-filters">
        <form method="get" action="<?= base_url('staff/dashboard/attendance'); ?>" class="filter-form">
            <div class="filter-grid">
                <div class="form-group">
                    <label>
                        <span>Employee (Optional)</span>
                        <select name="user_id" class="modern-select">
                            <option value="">All Employees</option>
                            <?php foreach ($allUsers ?? [] as $user): ?>
                                <option value="<?= $user['id']; ?>" <?= ($selectedUserId ?? null) === $user['id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($user['name']); ?> (<?= htmlspecialchars($user['email']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <span>Start Date</span>
                        <input type="date" name="start_date" value="<?= htmlspecialchars($startDate ?? date('Y-m-d', strtotime('-30 days'))); ?>" class="modern-input">
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <span>End Date</span>
                        <input type="date" name="end_date" value="<?= htmlspecialchars($endDate ?? date('Y-m-d')); ?>" class="modern-input">
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <span>&nbsp;</span>
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Apply Filters</button>
                    </label>
                </div>
            </div>
        </form>
    </div>

    <!-- Statistics Overview -->
    <div class="stats-grid">
        <?php
        $totalDays = 0;
        $totalHours = 0;
        $totalEmployees = count($statistics ?? []);
        foreach ($statistics ?? [] as $stat) {
            $totalDays += (int)$stat['total_days'];
            $totalHours += (float)$stat['total_hours'];
        }
        ?>
        <div class="stat-card">
            <div class="stat-icon" style="background: #dbeafe; color: #1e40af;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= number_format($totalDays); ?></div>
                <div class="stat-label">Total Days Worked</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #dcfce7; color: #166534;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= number_format($totalHours, 1); ?></div>
                <div class="stat-label">Total Hours</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #fef3c7; color: #92400e;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $totalEmployees; ?></div>
                <div class="stat-label">Employees Tracked</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #fee2e2; color: #991b1b;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= count($anomalies ?? []); ?></div>
                <div class="stat-label">Anomalies Detected</div>
            </div>
        </div>
    </div>

    <!-- Anomaly Detection Section -->
    <?php if (!empty($anomalies)): ?>
        <div class="anomalies-section">
            <h3>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 0.5rem;">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
                Anomaly Detection (Suspicious Patterns)
            </h3>
            <p class="anomalies-description">The following records show patterns that may indicate attendance manipulation or errors. Review carefully, especially for management roles.</p>
            <div class="anomalies-list">
                <?php foreach ($anomalies as $anomaly): ?>
                    <div class="anomaly-card severity-<?= htmlspecialchars($anomaly['severity']); ?>">
                        <div class="anomaly-header">
                            <div>
                                <strong><?= htmlspecialchars($anomaly['record']['user_name']); ?></strong>
                                <span class="role-badge"><?= htmlspecialchars($anomaly['record']['role_key'] ?? 'N/A'); ?></span>
                            </div>
                            <span class="severity-badge severity-<?= htmlspecialchars($anomaly['severity']); ?>">
                                <?= strtoupper(htmlspecialchars($anomaly['severity'])); ?>
                            </span>
                        </div>
                        <div class="anomaly-details">
                            <div class="detail-item">
                                <span class="detail-label">Date:</span>
                                <span class="detail-value"><?= date('M j, Y', strtotime($anomaly['record']['date'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Check In:</span>
                                <span class="detail-value"><?= date('H:i', strtotime($anomaly['record']['check_in_time'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Check Out:</span>
                                <span class="detail-value"><?= date('H:i', strtotime($anomaly['record']['check_out_time'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Hours:</span>
                                <span class="detail-value"><?= round($anomaly['record']['hours_worked'], 2); ?> hrs</span>
                            </div>
                        </div>
                        <div class="anomaly-issues">
                            <strong>Issues Detected:</strong>
                            <ul>
                                <?php foreach ($anomaly['issues'] as $issue): ?>
                                    <li><?= htmlspecialchars($issue); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Best Attendance -->
    <?php if (!empty($bestAttendance)): ?>
        <div class="best-attendance-section">
            <h3>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 0.5rem;">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                </svg>
                Top Performers (Last 30 Days)
            </h3>
            <div class="best-attendance-grid">
                <?php foreach ($bestAttendance as $index => $performer): ?>
                    <div class="performer-card">
                        <div class="performer-rank">#<?= $index + 1; ?></div>
                        <div class="performer-info">
                            <strong><?= htmlspecialchars($performer['user_name']); ?></strong>
                            <small><?= htmlspecialchars($performer['role_key'] ?? 'N/A'); ?></small>
                        </div>
                        <div class="performer-stats">
                            <div class="performer-stat">
                                <span class="stat-label">Days:</span>
                                <span class="stat-value"><?= (int)$performer['days_present']; ?></span>
                            </div>
                            <div class="performer-stat">
                                <span class="stat-label">Hours:</span>
                                <span class="stat-value"><?= round((float)$performer['total_hours'], 1); ?></span>
                            </div>
                            <div class="performer-stat">
                                <span class="stat-label">Avg/Day:</span>
                                <span class="stat-value"><?= round((float)$performer['avg_hours_per_day'], 1); ?>h</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Employee Statistics Table -->
    <?php if (!empty($statistics)): ?>
        <div class="statistics-section">
            <h3>Employee Statistics</h3>
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Role</th>
                        <th>Days Worked</th>
                        <th>Total Hours</th>
                        <th>Avg Hours/Day</th>
                        <th>Min Hours</th>
                        <th>Max Hours</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($statistics as $stat): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($stat['user_name']); ?></strong>
                            </td>
                            <td><?= htmlspecialchars($stat['role_key'] ?? 'N/A'); ?></td>
                            <td><?= (int)$stat['total_days']; ?></td>
                            <td><strong><?= number_format((float)$stat['total_hours'], 1); ?>h</strong></td>
                            <td><?= $stat['avg_hours_per_day'] ? number_format((float)$stat['avg_hours_per_day'], 1) . 'h' : 'N/A'; ?></td>
                            <td><?= $stat['min_hours'] ? number_format((float)$stat['min_hours'], 1) . 'h' : 'N/A'; ?></td>
                            <td><?= $stat['max_hours'] ? number_format((float)$stat['max_hours'], 1) . 'h' : 'N/A'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Check In/Out Forms (Only for Security and Tech Admin) -->
    <?php
    $currentUser = \App\Support\Auth::user();
    $canCheckInOut = in_array($currentUser['role_key'] ?? '', ['security', 'tech_admin'], true);
    ?>
    <?php if ($canCheckInOut): ?>
    <div class="attendance-actions">
        <form method="post" action="<?= base_url('staff/dashboard/attendance/check-in'); ?>" class="attendance-form">
            <h3>Check In</h3>
            <div class="form-group">
                <label>
                    <span>Select Employee</span>
                    <select name="user_id" required class="modern-select">
                        <option value="">-- Select Employee --</option>
                        <?php
                        foreach ($allUsers ?? [] as $user):
                            $attendanceRepo = new \App\Repositories\AttendanceRepository();
                            $todayAttendance = $attendanceRepo->getTodayAttendance($user['id']);
                            $isCheckedIn = $todayAttendance && $todayAttendance['checked_in'] && !$todayAttendance['checked_out'];
                            if ($isCheckedIn) continue;
                        ?>
                            <option value="<?= $user['id']; ?>">
                                <?= htmlspecialchars($user['name']); ?> (<?= htmlspecialchars($user['email']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <div class="form-group">
                <label>
                    <span>Notes (Optional)</span>
                    <textarea name="notes" rows="2" class="modern-input" placeholder="Additional notes..."></textarea>
                </label>
            </div>
            <button type="submit" class="btn btn-primary">Check In</button>
        </form>

        <form method="post" action="<?= base_url('staff/dashboard/attendance/check-out'); ?>" class="attendance-form">
            <h3>Check Out</h3>
            <div class="form-group">
                <label>
                    <span>Select Employee</span>
                    <select name="user_id" required class="modern-select">
                        <option value="">-- Select Employee --</option>
                        <?php
                        foreach ($allUsers ?? [] as $user):
                            $attendanceRepo = new \App\Repositories\AttendanceRepository();
                            $todayAttendance = $attendanceRepo->getTodayAttendance($user['id']);
                            $isCheckedIn = $todayAttendance && $todayAttendance['checked_in'] && !$todayAttendance['checked_out'];
                            if (!$isCheckedIn) continue;
                        ?>
                            <option value="<?= $user['id']; ?>">
                                <?= htmlspecialchars($user['name']); ?> (<?= htmlspecialchars($user['email']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <div class="form-group">
                <label>
                    <span>Notes (Optional)</span>
                    <textarea name="notes" rows="2" class="modern-input" placeholder="Additional notes..."></textarea>
                </label>
            </div>
            <button type="submit" class="btn btn-primary">Check Out</button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Override Form (Only for Admin, Director, Tech Admin) -->
    <?php
    $canGrantOverride = in_array($currentUser['role_key'] ?? '', ['admin', 'director', 'tech_admin'], true);
    ?>
    <?php if ($canGrantOverride): ?>
    <div class="attendance-override">
        <h3>Grant Login Override</h3>
        <p class="override-description">Grant temporary login access (1 hour) to employees who haven't checked in or have already checked out.</p>
        <form method="post" action="<?= base_url('staff/dashboard/attendance/grant-override'); ?>" class="override-form">
            <div class="form-group">
                <label>
                    <span>Select Employee</span>
                    <select name="user_id" required class="modern-select">
                        <option value="">-- Select Employee --</option>
                        <?php foreach ($allUsers ?? [] as $user): ?>
                            <option value="<?= $user['id']; ?>">
                                <?= htmlspecialchars($user['name']); ?> (<?= htmlspecialchars($user['email']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <div class="form-group">
                <label>
                    <span>Reason</span>
                    <textarea name="reason" rows="3" required class="modern-input" placeholder="Reason for override..."></textarea>
                </label>
            </div>
            <input type="hidden" name="duration_hours" value="1">
            <button type="submit" class="btn btn-primary">Grant Override</button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Today's Attendance -->
    <div class="attendance-list">
        <h3>Today's Attendance (<?= date('F j, Y'); ?>)</h3>
        <?php if (empty($todayAttendance)): ?>
            <div class="empty-state">
                <p>No attendance records for today.</p>
            </div>
        <?php else: ?>
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Role</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Hours</th>
                        <th>Status</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($todayAttendance as $record): ?>
                        <?php
                        $hours = null;
                        if ($record['check_out_time']) {
                            $checkIn = strtotime($record['check_in_time']);
                            $checkOut = strtotime($record['check_out_time']);
                            $hours = round(($checkOut - $checkIn) / 3600, 2);
                        }
                        ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($record['user_name']); ?></strong><br>
                                <small><?= htmlspecialchars($record['user_email']); ?></small>
                            </td>
                            <td><?= htmlspecialchars($record['role_key'] ?? 'N/A'); ?></td>
                            <td><?= date('H:i', strtotime($record['check_in_time'])); ?></td>
                            <td><?= $record['check_out_time'] ? date('H:i', strtotime($record['check_out_time'])) : '--'; ?></td>
                            <td><?= $hours !== null ? number_format($hours, 1) . 'h' : '--'; ?></td>
                            <td>
                                <?php if ($record['checked_in'] && !$record['checked_out']): ?>
                                    <span class="status status-checked_in">Checked In</span>
                                <?php elseif ($record['checked_out']): ?>
                                    <span class="status status-checked_out">Checked Out</span>
                                <?php else: ?>
                                    <span class="status">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($record['notes'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- All Records Table -->
    <div class="attendance-records-section">
        <h3>Attendance Records</h3>
        <?php if (empty($allRecords)): ?>
            <div class="empty-state">
                <p>No attendance records found for the selected period.</p>
            </div>
        <?php else: ?>
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Employee</th>
                        <th>Role</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Hours Worked</th>
                        <th>Status</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allRecords as $record): ?>
                        <tr>
                            <td><?= date('M j, Y', strtotime($record['date'])); ?></td>
                            <td>
                                <strong><?= htmlspecialchars($record['user_name']); ?></strong><br>
                                <small><?= htmlspecialchars($record['user_email']); ?></small>
                            </td>
                            <td><?= htmlspecialchars($record['role_key'] ?? 'N/A'); ?></td>
                            <td><?= date('H:i', strtotime($record['check_in_time'])); ?></td>
                            <td><?= $record['check_out_time'] ? date('H:i', strtotime($record['check_out_time'])) : '--'; ?></td>
                            <td>
                                <?php if ($record['hours_worked'] !== null): ?>
                                    <strong><?= number_format($record['hours_worked'], 2); ?>h</strong>
                                <?php else: ?>
                                    <span style="color: #94a3b8;">--</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($record['checked_in'] && !$record['checked_out']): ?>
                                    <span class="status status-checked_in">Checked In</span>
                                <?php elseif ($record['checked_out']): ?>
                                    <span class="status status-checked_out">Checked Out</span>
                                <?php else: ?>
                                    <span class="status">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($record['notes'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>

<style>
.attendance-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.attendance-header h2 {
    margin: 0 0 0.25rem 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--dark);
}

.attendance-subtitle {
    margin: 0;
    font-size: 0.95rem;
    color: #64748b;
}

.attendance-filters {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #f8fafc;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
}

.filter-form {
    width: 100%;
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    background: #fff;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.2;
}

.stat-label {
    font-size: 0.875rem;
    color: #64748b;
    margin-top: 0.25rem;
}

.anomalies-section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #fef2f2;
    border-radius: 0.75rem;
    border: 1px solid #fecaca;
}

.anomalies-section h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #991b1b;
}

.anomalies-description {
    margin: 0 0 1rem 0;
    color: #7f1d1d;
    font-size: 0.875rem;
}

.anomalies-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.anomaly-card {
    padding: 1rem;
    background: #fff;
    border-radius: 0.5rem;
    border-left: 4px solid #dc2626;
}

.anomaly-card.severity-high {
    border-left-color: #dc2626;
    background: #fef2f2;
}

.anomaly-card.severity-medium {
    border-left-color: #f59e0b;
    background: #fffbeb;
}

.anomaly-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.role-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    background: #e2e8f0;
    color: #475569;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    margin-left: 0.5rem;
}

.severity-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.severity-badge.severity-high {
    background: #fee2e2;
    color: #991b1b;
}

.severity-badge.severity-medium {
    background: #fef3c7;
    color: #92400e;
}

.anomaly-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 0.5rem;
    margin-bottom: 0.75rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #e2e8f0;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.detail-label {
    font-size: 0.75rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.detail-value {
    font-weight: 600;
    color: #1e293b;
}

.anomaly-issues {
    font-size: 0.875rem;
}

.anomaly-issues strong {
    color: #991b1b;
    display: block;
    margin-bottom: 0.5rem;
}

.anomaly-issues ul {
    margin: 0;
    padding-left: 1.25rem;
    color: #7f1d1d;
}

.anomaly-issues li {
    margin-bottom: 0.25rem;
}

.best-attendance-section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #f0fdf4;
    border-radius: 0.75rem;
    border: 1px solid #bbf7d0;
}

.best-attendance-section h3 {
    margin: 0 0 1rem 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #166534;
}

.best-attendance-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1rem;
}

.performer-card {
    padding: 1rem;
    background: #fff;
    border-radius: 0.5rem;
    border: 1px solid #bbf7d0;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.performer-rank {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #10b981;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.125rem;
    flex-shrink: 0;
}

.performer-info {
    flex: 1;
}

.performer-info strong {
    display: block;
    color: #1e293b;
    margin-bottom: 0.25rem;
}

.performer-info small {
    color: #64748b;
    font-size: 0.75rem;
}

.performer-stats {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    text-align: right;
}

.performer-stat {
    display: flex;
    justify-content: space-between;
    gap: 0.5rem;
    font-size: 0.875rem;
}

.performer-stat .stat-label {
    color: #64748b;
}

.performer-stat .stat-value {
    font-weight: 600;
    color: #1e293b;
}

.statistics-section {
    margin-bottom: 2rem;
}

.statistics-section h3 {
    margin: 0 0 1rem 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--dark);
}

.attendance-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}

.attendance-form {
    padding: 1.5rem;
    background: #f8fafc;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
}

.attendance-form h3 {
    margin: 0 0 1rem 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--dark);
}

.attendance-override {
    padding: 1.5rem;
    background: #fef3c7;
    border-radius: 0.75rem;
    border: 1px solid #fcd34d;
    margin-bottom: 2rem;
}

.attendance-override h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--dark);
}

.override-description {
    margin: 0 0 1rem 0;
    color: #92400e;
    font-size: 0.875rem;
}

.override-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.attendance-list,
.attendance-records-section {
    margin-top: 2rem;
}

.attendance-list h3,
.attendance-records-section h3 {
    margin: 0 0 1rem 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--dark);
}

.modern-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 0.5rem;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
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

.status-checked_in {
    background: #d1fae5;
    color: #065f46;
}

.status-checked_out {
    background: #fee2e2;
    color: #991b1b;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group label span {
    font-weight: 600;
    color: #475569;
    font-size: 0.95rem;
}

.modern-input,
.modern-select {
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

.modern-input:focus,
.modern-select:focus {
    outline: none;
    border-color: #8b5cf6;
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #64748b;
}

@media (max-width: 768px) {
    .attendance-actions {
        grid-template-columns: 1fr;
    }

    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .best-attendance-grid {
        grid-template-columns: 1fr;
    }

    .filter-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>
