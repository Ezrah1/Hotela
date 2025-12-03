<?php
$pageTitle = 'My Attendance | Hotela';
ob_start();
?>
<section class="card">
    <header class="attendance-header">
        <div>
            <h2>My Attendance</h2>
            <p class="attendance-subtitle">View your attendance records</p>
        </div>
    </header>

    <?php if ($todayAttendance): ?>
        <div class="today-status">
            <h3>Today's Status</h3>
            <div class="status-card">
                <div class="status-item">
                    <span class="status-label">Check In:</span>
                    <span class="status-value"><?= date('H:i', strtotime($todayAttendance['check_in_time'])); ?></span>
                </div>
                <?php if ($todayAttendance['check_out_time']): ?>
                    <div class="status-item">
                        <span class="status-label">Check Out:</span>
                        <span class="status-value"><?= date('H:i', strtotime($todayAttendance['check_out_time'])); ?></span>
                    </div>
                <?php endif; ?>
                <div class="status-item">
                    <span class="status-label">Status:</span>
                    <?php 
                    $hasCheckIn = !empty($todayAttendance['check_in_time']);
                    $hasCheckOut = !empty($todayAttendance['check_out_time']);
                    $status = $todayAttendance['status'] ?? '';
                    ?>
                    <?php if ($hasCheckIn && !$hasCheckOut): ?>
                        <span class="status status-checked_in">Checked In</span>
                    <?php elseif ($hasCheckOut): ?>
                        <span class="status status-checked_out">Checked Out</span>
                    <?php elseif ($status === 'present'): ?>
                        <span class="status status-checked_in">Present</span>
                    <?php else: ?>
                        <span class="status">Pending</span>
                    <?php endif; ?>
                </div>
                <?php if ($todayAttendance['notes']): ?>
                    <div class="status-item">
                        <span class="status-label">Notes:</span>
                        <span class="status-value"><?= htmlspecialchars($todayAttendance['notes']); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="today-status">
            <h3>Today's Status</h3>
            <div class="status-card">
                <p style="color: #64748b; margin: 0;">No attendance record for today.</p>
            </div>
        </div>
    <?php endif; ?>

    <div class="attendance-history">
        <h3>Attendance History (Last 30 Days)</h3>
        <?php if (empty($history)): ?>
            <div class="empty-state">
                <p>No attendance history available.</p>
            </div>
        <?php else: ?>
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Status</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $record): ?>
                        <?php 
                        $checkInTime = $record['check_in_time'] ?? null;
                        $checkOutTime = $record['check_out_time'] ?? null;
                        $hasCheckIn = !empty($checkInTime);
                        $hasCheckOut = !empty($checkOutTime);
                        $status = $record['status'] ?? '';
                        $date = $checkInTime ? date('Y-m-d', strtotime($checkInTime)) : date('Y-m-d');
                        ?>
                        <tr>
                            <td><?= $checkInTime ? date('M j, Y', strtotime($checkInTime)) : 'N/A'; ?></td>
                            <td><?= $checkInTime ? date('H:i', strtotime($checkInTime)) : '--'; ?></td>
                            <td><?= $checkOutTime ? date('H:i', strtotime($checkOutTime)) : '--'; ?></td>
                            <td>
                                <?php if ($hasCheckIn && !$hasCheckOut): ?>
                                    <span class="status status-checked_in">Checked In</span>
                                <?php elseif ($hasCheckOut): ?>
                                    <span class="status status-checked_out">Checked Out</span>
                                <?php elseif ($status === 'present'): ?>
                                    <span class="status status-checked_in">Present</span>
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

.today-status {
    margin-bottom: 2rem;
}

.today-status h3 {
    margin: 0 0 1rem 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--dark);
}

.status-card {
    padding: 1.5rem;
    background: #f8fafc;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.status-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #e2e8f0;
}

.status-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.status-label {
    font-weight: 600;
    color: #475569;
}

.status-value {
    color: #1e293b;
}

.attendance-history {
    margin-top: 2rem;
}

.attendance-history h3 {
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

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #64748b;
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

