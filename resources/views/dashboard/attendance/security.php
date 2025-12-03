<?php
$pageTitle = 'Staff Check-in / Check-out | Hotela';
ob_start();
?>
<section class="card">
    <header class="attendance-header">
        <div>
            <h2>Staff Check-in / Check-out</h2>
            <p class="attendance-subtitle">Manage staff attendance at the security desk</p>
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

    <!-- Today's Attendance Overview -->
    <div class="today-attendance-section">
        <h3>Today's Check-ins</h3>
        <?php 
        // Filter to only show checked-in staff (not checked out)
        $checkedInRecords = [];
        if (!empty($todayAttendance)) {
            foreach ($todayAttendance as $record) {
                if (($record['status'] ?? '') === 'present' && empty($record['check_out_time'])) {
                    $checkedInRecords[] = $record;
                }
            }
        }
        ?>
        <?php if (!empty($checkedInRecords)): ?>
            <div class="employee-list">
                <?php foreach ($checkedInRecords as $record): ?>
                    <?php $userId = (int)($record['user_id'] ?? 0); ?>
                    <div class="employee-item checked-in">
                        <div class="employee-info">
                            <div class="employee-avatar" style="background: #10b981;">
                                <?= strtoupper(substr($record['user_name'] ?? 'U', 0, 1)); ?>
                            </div>
                            <div class="employee-details">
                                <strong><?= htmlspecialchars($record['user_name'] ?? 'Unknown'); ?></strong>
                                <div class="employee-meta">
                                    <span class="role"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $record['user_role'] ?? $record['role_key'] ?? 'N/A'))); ?></span>
                                    <?php if (!empty($record['check_in_time'])): ?>
                                        <span class="check-in-time">Checked in at <?= date('H:i', strtotime($record['check_in_time'])); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <form method="post" action="<?= base_url('staff/dashboard/attendance/check-out'); ?>" class="employee-action-form" onsubmit="return confirm('Check out <?= htmlspecialchars(addslashes($record['user_name'] ?? 'this employee')); ?>?');">
                            <input type="hidden" name="user_id" value="<?= $userId; ?>">
                            <div class="notes-input-container" style="display: none;" id="notes-out-<?= $userId; ?>">
                                <textarea name="notes" rows="2" class="modern-input" placeholder="Optional notes..."></textarea>
                            </div>
                            <button type="button" class="btn btn-outline btn-small" onclick="toggleNotes(<?= $userId; ?>, 'out')">Add Notes</button>
                            <button type="submit" class="btn btn-warning btn-small">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 0.25rem;">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                Check Out
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="empty-state">No check-ins recorded today.</p>
        <?php endif; ?>
    </div>

    <!-- Check In Staff List -->
    <div class="check-in-section">
        <h3>Check In Staff</h3>
        <?php
        $exemptRoles = ['admin', 'director', 'tech', 'security'];
        $availableUsers = [];
        $checkedInUsers = [];
        
        foreach ($allUsers ?? [] as $user):
            // Skip exempt roles (admin, director, tech, security)
            if (in_array($user['role_key'] ?? '', $exemptRoles, true)) continue;
            
            // Check if already checked in today
            $attendanceRepo = new \App\Repositories\AttendanceRepository();
            $todayAttendance = $attendanceRepo->getTodayAttendance($user['id']);
            $isCheckedIn = $todayAttendance && ($todayAttendance['status'] ?? '') === 'present' && empty($todayAttendance['check_out_time']);
            
            // Ensure we have a valid user ID
            $userId = (int)($user['id'] ?? 0);
            if (!$userId || $userId <= 0) continue;
            
            if ($isCheckedIn) {
                $checkedInUsers[] = $user + ['attendance' => $todayAttendance];
            } else {
                $availableUsers[] = $user;
            }
        endforeach;
        ?>
        
        <?php if (!empty($availableUsers)): ?>
            <div class="employee-list">
                <h4>Available for Check-in</h4>
                <?php foreach ($availableUsers as $user): ?>
                    <?php $userId = (int)($user['id'] ?? 0); ?>
                    <div class="employee-item">
                        <div class="employee-info">
                            <div class="employee-avatar">
                                <?= strtoupper(substr($user['name'] ?? 'U', 0, 1)); ?>
                            </div>
                            <div class="employee-details">
                                <strong><?= htmlspecialchars($user['name'] ?? 'Unknown'); ?></strong>
                                <div class="employee-meta">
                                    <?php if (!empty($user['username'])): ?>
                                        <span class="username"><code><?= htmlspecialchars($user['username']); ?></code></span>
                                    <?php endif; ?>
                                    <span class="role"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $user['role_key'] ?? 'N/A'))); ?></span>
                                    <?php if (!empty($user['department'])): ?>
                                        <span class="department"><?= htmlspecialchars($user['department']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <form method="post" action="<?= base_url('staff/dashboard/attendance/check-in'); ?>" class="employee-action-form" onsubmit="return confirm('Check in <?= htmlspecialchars(addslashes($user['name'] ?? 'this employee')); ?>?');">
                            <input type="hidden" name="user_id" value="<?= $userId; ?>">
                            <div class="notes-input-container" style="display: none;" id="notes-<?= $userId; ?>">
                                <textarea name="notes" rows="2" class="modern-input" placeholder="Optional notes..."></textarea>
                            </div>
                            <button type="button" class="btn btn-outline btn-small" onclick="toggleNotes(<?= $userId; ?>)">Add Notes</button>
                            <button type="submit" class="btn btn-primary btn-small">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 0.25rem;">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                                Check In
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-warning" style="margin: 1rem 0; padding: 0.75rem 1rem; background: #fef3c7; color: #92400e; border: 1px solid #fde68a; border-radius: 0.5rem;">
                <strong>No employees available for check-in</strong>
                <p>All non-exempt employees have already checked in today.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Checked-in Staff List (for Check Out) -->
    <?php 
    // Get all checked-in staff (not checked out) for the check-out list
    $checkedInForCheckOut = [];
    if (!empty($todayAttendance)) {
        foreach ($todayAttendance as $record) {
            if (($record['status'] ?? '') === 'present' && empty($record['check_out_time'])) {
                $checkedInForCheckOut[] = $record;
            }
        }
    }
    ?>
    <?php if (!empty($checkedInForCheckOut)): ?>
    <div class="check-out-section">
        <h3>Checked In Staff (Check Out)</h3>
        <div class="employee-list">
            <?php foreach ($checkedInForCheckOut as $record): ?>
                <?php 
                $userId = (int)($record['user_id'] ?? 0);
                if (!$userId) continue;
                ?>
                <div class="employee-item checked-in">
                    <div class="employee-info">
                        <div class="employee-avatar" style="background: #10b981;">
                            <?= strtoupper(substr($record['user_name'] ?? 'U', 0, 1)); ?>
                        </div>
                        <div class="employee-details">
                            <strong><?= htmlspecialchars($record['user_name'] ?? 'Unknown'); ?></strong>
                            <div class="employee-meta">
                                <span class="role"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $record['user_role'] ?? $record['role_key'] ?? 'N/A'))); ?></span>
                                <?php if (!empty($record['check_in_time'])): ?>
                                    <span class="check-in-time">Checked in at <?= date('H:i', strtotime($record['check_in_time'])); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <form method="post" action="<?= base_url('staff/dashboard/attendance/check-out'); ?>" class="employee-action-form" onsubmit="return confirm('Check out <?= htmlspecialchars(addslashes($record['user_name'] ?? 'this employee')); ?>?');">
                        <input type="hidden" name="user_id" value="<?= $userId; ?>">
                        <div class="notes-input-container" style="display: none;" id="notes-out-<?= $userId; ?>">
                            <textarea name="notes" rows="2" class="modern-input" placeholder="Optional notes..."></textarea>
                        </div>
                        <button type="button" class="btn btn-outline btn-small" onclick="toggleNotes(<?= $userId; ?>, 'out')">Add Notes</button>
                        <button type="submit" class="btn btn-warning btn-small">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 0.25rem;">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            Check Out
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</section>

<style>
.attendance-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.attendance-header h2 {
    margin: 0 0 0.5rem 0;
    font-size: 1.75rem;
    color: #111827;
}

.attendance-subtitle {
    margin: 0;
    color: #6b7280;
    font-size: 0.95rem;
}

.header-actions {
    display: flex;
    gap: 0.75rem;
}

.today-attendance-section,
.check-in-section,
.check-out-section,
.search-section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #f9fafb;
    border-radius: 0.5rem;
    border: 1px solid #e5e7eb;
}

.today-attendance-section h3,
.check-in-section h3,
.check-out-section h3,
.search-section h3 {
    margin: 0 0 1.5rem 0;
    font-size: 1.25rem;
    color: #111827;
    font-weight: 600;
}

.attendance-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1rem;
}

.attendance-card {
    background: white;
    border-radius: 0.5rem;
    padding: 1rem;
    border: 2px solid #e5e7eb;
    transition: all 0.2s;
}

.attendance-card.checked-in {
    border-color: #10b981;
    background: #f0fdf4;
}

.attendance-card.checked-out {
    border-color: #6b7280;
    background: #f9fafb;
}

.attendance-card-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.attendance-card-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #0d9488;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 1.1rem;
}

.attendance-card-info {
    flex: 1;
}

.attendance-card-info strong {
    display: block;
    color: #111827;
    font-size: 0.95rem;
}

.attendance-card-info small {
    color: #6b7280;
    font-size: 0.8rem;
}

.attendance-card-details {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.875rem;
}

.detail-label {
    color: #6b7280;
}

.detail-value {
    color: #111827;
    font-weight: 600;
}

.status-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-badge.status-checked-in {
    background: #d1fae5;
    color: #065f46;
}

.status-badge.status-completed {
    background: #e5e7eb;
    color: #374151;
}

.attendance-card-actions {
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
}

.attendance-form,
.search-form {
    max-width: 500px;
}

.form-group {
    margin-bottom: 1.25rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
}

.form-group label span {
    display: block;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.modern-select,
.modern-input {
    width: 100%;
    padding: 0.625rem 0.875rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 0.95rem;
    transition: all 0.2s;
}

.modern-select:focus,
.modern-input:focus {
    outline: none;
    border-color: #0d9488;
    box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1);
}

textarea.modern-input {
    resize: vertical;
    min-height: 80px;
}

.btn {
    display: inline-flex;
    align-items: center;
    padding: 0.625rem 1.25rem;
    border-radius: 0.375rem;
    font-weight: 600;
    font-size: 0.95rem;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary {
    background: #0d9488;
    color: white;
}

.btn-primary:hover {
    background: #0f766e;
}

.btn-outline {
    background: white;
    color: #0d9488;
    border: 1px solid #0d9488;
}

.btn-outline:hover {
    background: #f0fdfa;
}

.btn-small {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

.empty-state {
    color: #6b7280;
    font-style: italic;
    text-align: center;
    padding: 2rem;
}

.employee-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-top: 1rem;
}

.employee-list h4 {
    margin: 0 0 1rem 0;
    font-size: 1.1rem;
    color: #374151;
    font-weight: 600;
}

.employee-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 0.5rem;
    transition: all 0.2s;
}

.employee-item:hover {
    border-color: #0d9488;
    box-shadow: 0 2px 8px rgba(13, 148, 136, 0.1);
}

.employee-item.checked-in {
    background: #f0fdf4;
    border-color: #10b981;
}

.employee-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
}

.employee-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #0d9488;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.employee-details {
    flex: 1;
}

.employee-details strong {
    display: block;
    font-size: 1rem;
    color: #111827;
    margin-bottom: 0.25rem;
}

.employee-meta {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
    font-size: 0.875rem;
    color: #6b7280;
}

.employee-meta .username code {
    background: #f3f4f6;
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    font-family: 'Courier New', monospace;
    font-size: 0.8em;
    color: #0d9488;
}

.employee-meta .role {
    color: #374151;
    font-weight: 500;
}

.employee-meta .department {
    color: #6b7280;
}

.employee-meta .check-in-time {
    color: #059669;
    font-weight: 500;
}

.employee-action-form {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0;
}

.notes-input-container {
    width: 200px;
    margin-right: 0.5rem;
}

.notes-input-container textarea {
    width: 100%;
    padding: 0.5rem;
    font-size: 0.875rem;
    border: 1px solid #d1d5db;
    border-radius: 0.25rem;
}

.btn-small {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    white-space: nowrap;
}

.btn-warning {
    background: #f59e0b;
    color: white;
}

.btn-warning:hover {
    background: #d97706;
}

@media (max-width: 768px) {
    .attendance-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .attendance-grid {
        grid-template-columns: 1fr;
    }
    
    .employee-item {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .employee-action-form {
        flex-direction: column;
        width: 100%;
    }
    
    .notes-input-container {
        width: 100% !important;
        margin-right: 0 !important;
        margin-bottom: 0.5rem;
    }
    
    .employee-action-form .btn {
        width: 100%;
    }
}
</style>

<script>
function toggleNotes(userId, type = 'in') {
    const prefix = type === 'out' ? 'notes-out-' : 'notes-';
    const notesContainer = document.getElementById(prefix + userId);
    if (notesContainer) {
        if (notesContainer.style.display === 'none') {
            notesContainer.style.display = 'block';
        } else {
            notesContainer.style.display = 'none';
        }
    }
}
</script>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

