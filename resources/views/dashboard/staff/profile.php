<?php
$pageTitle = 'Staff Profile: ' . htmlspecialchars($user['name']) . ' | Hotela';
ob_start();
?>
<section class="card">
    <header class="profile-header">
        <div class="profile-header-main">
            <div class="profile-avatar">
                <?php if (!empty($staff['profile_photo'])): ?>
                    <img src="<?= htmlspecialchars($staff['profile_photo']); ?>" alt="<?= htmlspecialchars($user['name']); ?>" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover;">
                <?php else: ?>
                    <div style="width: 80px; height: 80px; border-radius: 50%; background: #0d9488; color: white; display: flex; align-items: center; justify-content: center; font-size: 2em; font-weight: 600;">
                        <?= strtoupper(substr($user['name'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <h2><?= htmlspecialchars($user['name']); ?></h2>
                <p class="profile-role"><?= htmlspecialchars($user['role_name'] ?? ucfirst(str_replace('_', ' ', $user['role_key']))); ?></p>
                <?php if (!empty($staff['department'])): ?>
                    <p class="profile-department"><?= htmlspecialchars($staff['department']); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="profile-actions">
            <a class="btn btn-outline" href="<?= base_url('staff/dashboard/staff/edit?id=' . (int)$user['id']); ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
                Edit Profile
            </a>
            <a class="btn btn-ghost" href="<?= base_url('staff/dashboard/staff'); ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
                Back to Staff
            </a>
        </div>
    </header>

    <div class="profile-content">
        <div class="profile-section">
            <h3 class="section-title">Basic Information</h3>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Full Name</span>
                    <span class="info-value"><?= htmlspecialchars($user['name']); ?></span>
                </div>
                <?php if (!empty($user['username'])): ?>
                <div class="info-item">
                    <span class="info-label">Username</span>
                    <span class="info-value"><code><?= htmlspecialchars($user['username']); ?></code></span>
                </div>
                <?php endif; ?>
                <div class="info-item">
                    <span class="info-label">Email</span>
                    <span class="info-value"><a href="mailto:<?= htmlspecialchars($user['email']); ?>"><?= htmlspecialchars($user['email']); ?></a></span>
                </div>
                <?php if (!empty($staff['phone'])): ?>
                <div class="info-item">
                    <span class="info-label">Phone</span>
                    <span class="info-value"><a href="tel:<?= htmlspecialchars($staff['phone']); ?>"><?= htmlspecialchars($staff['phone']); ?></a></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($staff['id_number'])): ?>
                <div class="info-item">
                    <span class="info-label">ID/Passport Number</span>
                    <span class="info-value"><?= htmlspecialchars($staff['id_number']); ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($staff['employee_id'])): ?>
                <div class="info-item">
                    <span class="info-label">Employee ID</span>
                    <span class="info-value"><?= htmlspecialchars($staff['employee_id']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($staff['address'])): ?>
        <div class="profile-section">
            <h3 class="section-title">Contact Information</h3>
            <div class="info-grid">
                <div class="info-item info-item-full">
                    <span class="info-label">Address</span>
                    <span class="info-value"><?= nl2br(htmlspecialchars($staff['address'])); ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($staff['emergency_contact_name'])): ?>
        <div class="profile-section">
            <h3 class="section-title">Emergency Contact</h3>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Name</span>
                    <span class="info-value"><?= htmlspecialchars($staff['emergency_contact_name']); ?></span>
                </div>
                <?php if (!empty($staff['emergency_contact_phone'])): ?>
                <div class="info-item">
                    <span class="info-label">Phone</span>
                    <span class="info-value"><a href="tel:<?= htmlspecialchars($staff['emergency_contact_phone']); ?>"><?= htmlspecialchars($staff['emergency_contact_phone']); ?></a></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($staff['emergency_contact_relation'])): ?>
                <div class="info-item">
                    <span class="info-label">Relation</span>
                    <span class="info-value"><?= htmlspecialchars($staff['emergency_contact_relation']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="profile-section">
            <h3 class="section-title">Employment Details</h3>
            <div class="info-grid">
                <?php if (!empty($staff['department'])): ?>
                <div class="info-item">
                    <span class="info-label">Department</span>
                    <span class="info-value"><?= htmlspecialchars($staff['department']); ?></span>
                </div>
                <?php endif; ?>
                <div class="info-item">
                    <span class="info-label">Role</span>
                    <span class="info-value"><?= htmlspecialchars($user['role_name'] ?? ucfirst(str_replace('_', ' ', $user['role_key']))); ?></span>
                </div>
                <?php if (!empty($staff['basic_salary']) && (float)$staff['basic_salary'] > 0): ?>
                <div class="info-item">
                    <span class="info-label">Basic Salary</span>
                    <span class="info-value">KES <?= number_format((float)$staff['basic_salary'], 2); ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($staff['hire_date'])): ?>
                <div class="info-item">
                    <span class="info-label">Date of Employment</span>
                    <span class="info-value"><?= date('F j, Y', strtotime($staff['hire_date'])); ?></span>
                </div>
                <?php endif; ?>
                <div class="info-item">
                    <span class="info-label">Employment Status</span>
                    <span class="info-value">
                        <span class="badge <?= ($user['status'] === 'active') ? 'badge-success' : 'badge-danger'; ?>">
                            <?= ucfirst($user['status']); ?>
                        </span>
                        <?php if (!empty($staff['status']) && $staff['status'] !== $user['status']): ?>
                            <span class="badge badge-warning"><?= ucfirst($staff['status']); ?> (Staff Record)</span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        </div>

        <?php if (!empty($permissions)): ?>
        <div class="profile-section">
            <h3 class="section-title">System Permissions</h3>
            <div class="permissions-list">
                <?php if (in_array('*', $permissions, true)): ?>
                    <div class="permission-item">
                        <span class="badge badge-success">Full Access (All Permissions)</span>
                    </div>
                <?php else: ?>
                    <?php foreach ($permissions as $permission): ?>
                        <div class="permission-item">
                            <code><?= htmlspecialchars($permission); ?></code>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<section class="card">
    <h3 class="section-title">Attendance Logs (Last 30 Days)</h3>
    <?php if (!empty($attendanceLogs)): ?>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Hours</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attendanceLogs as $log): ?>
                        <tr>
                            <td><?= date('M j, Y', strtotime($log['date'])); ?></td>
                            <td><?= !empty($log['check_in_time']) ? date('h:i A', strtotime($log['check_in_time'])) : '—'; ?></td>
                            <td><?= !empty($log['check_out_time']) ? date('h:i A', strtotime($log['check_out_time'])) : '—'; ?></td>
                            <td>
                                <?php if (!empty($log['check_in_time']) && !empty($log['check_out_time'])): ?>
                                    <?php
                                    $checkIn = strtotime($log['check_in_time']);
                                    $checkOut = strtotime($log['check_out_time']);
                                    $hours = round(($checkOut - $checkIn) / 3600, 1);
                                    echo $hours . ' hrs';
                                    ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($log['checked_in']) && empty($log['checked_out'])): ?>
                                    <span class="badge badge-info">Checked In</span>
                                <?php elseif (!empty($log['checked_out'])): ?>
                                    <span class="badge badge-success">Completed</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Incomplete</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-muted">No attendance records found.</p>
    <?php endif; ?>
    <div style="margin-top: 15px;">
        <a href="<?= base_url('staff/dashboard/attendance/my-attendance?user_id=' . (int)$user['id']); ?>" class="btn btn-outline">View All Attendance</a>
    </div>
</section>

<?php if (!empty($tasks)): ?>
<section class="card">
    <h3 class="section-title">Assigned Tasks (Recent)</h3>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Task</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Due Date</th>
                    <th>Assigned</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tasks as $task): ?>
                    <tr>
                        <td><?= htmlspecialchars($task['title'] ?? 'Untitled Task'); ?></td>
                        <td>
                            <?php
                            $priority = $task['priority'] ?? 'medium';
                            $priorityClass = match($priority) {
                                'high' => 'badge-danger',
                                'medium' => 'badge-warning',
                                'low' => 'badge-info',
                                default => 'badge-secondary'
                            };
                            ?>
                            <span class="badge <?= $priorityClass; ?>"><?= ucfirst($priority); ?></span>
                        </td>
                        <td>
                            <?php
                            $status = $task['status'] ?? 'pending';
                            $statusClass = match($status) {
                                'completed' => 'badge-success',
                                'in_progress' => 'badge-info',
                                'cancelled' => 'badge-danger',
                                default => 'badge-secondary'
                            };
                            ?>
                            <span class="badge <?= $statusClass; ?>"><?= ucfirst(str_replace('_', ' ', $status)); ?></span>
                        </td>
                        <td><?= !empty($task['due_date']) ? date('M j, Y', strtotime($task['due_date'])) : '—'; ?></td>
                        <td><?= !empty($task['created_at']) ? date('M j, Y', strtotime($task['created_at'])) : '—'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($requisitions)): ?>
<section class="card">
    <h3 class="section-title">Requisition History (Recent)</h3>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Total Amount</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requisitions as $req): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($req['reference'] ?? 'N/A'); ?></code></td>
                        <td><?= htmlspecialchars($req['location_name'] ?? 'N/A'); ?></td>
                        <td>
                            <?php
                            $status = $req['status'] ?? 'pending';
                            $statusClass = match($status) {
                                'approved' => 'badge-success',
                                'rejected' => 'badge-danger',
                                'pending' => 'badge-warning',
                                'fulfilled' => 'badge-info',
                                default => 'badge-secondary'
                            };
                            ?>
                            <span class="badge <?= $statusClass; ?>"><?= ucfirst($status); ?></span>
                        </td>
                        <td>KES <?= number_format((float)($req['total_amount'] ?? 0), 2); ?></td>
                        <td><?= !empty($req['created_at']) ? date('M j, Y', strtotime($req['created_at'])) : '—'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($hrNotes)): ?>
<section class="card">
    <h3 class="section-title">HR Notes (Disciplinary & Performance)</h3>
    <div class="hr-notes-list">
        <?php foreach ($hrNotes as $note): ?>
            <div class="hr-note-item">
                <div class="hr-note-header">
                    <span class="badge <?= match($note['type'] ?? 'note') {
                        'disciplinary' => 'badge-danger',
                        'performance' => 'badge-warning',
                        'training' => 'badge-info',
                        'award' => 'badge-success',
                        default => 'badge-secondary'
                    }; ?>">
                        <?= ucfirst($note['type'] ?? 'Note'); ?>
                    </span>
                    <span class="text-muted"><?= date('M j, Y', strtotime($note['created_at'])); ?></span>
                </div>
                <div class="hr-note-content">
                    <?= nl2br(htmlspecialchars($note['notes'] ?? '')); ?>
                </div>
                <?php if (!empty($note['created_by'])): ?>
                    <div class="hr-note-footer">
                        <small class="text-muted">Recorded by User ID: <?= (int)$note['created_by']; ?></small>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<style>
.profile-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding-bottom: 2rem;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 2rem;
}

.profile-header-main {
    display: flex;
    gap: 1.5rem;
    align-items: center;
}

.profile-avatar {
    flex-shrink: 0;
}

.profile-info h2 {
    margin: 0 0 0.5rem 0;
    font-size: 1.75rem;
    color: #111827;
}

.profile-role {
    margin: 0 0 0.25rem 0;
    font-size: 1.1rem;
    color: #0d9488;
    font-weight: 600;
}

.profile-department {
    margin: 0;
    color: #6b7280;
    font-size: 0.95rem;
}

.profile-actions {
    display: flex;
    gap: 0.75rem;
    flex-shrink: 0;
}

.profile-content {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.profile-section {
    padding: 1.5rem 0;
    border-bottom: 1px solid #e5e7eb;
}

.profile-section:last-child {
    border-bottom: none;
}

.section-title {
    margin: 0 0 1.5rem 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #111827;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.info-item-full {
    grid-column: 1 / -1;
}

.info-label {
    font-size: 0.875rem;
    color: #6b7280;
    font-weight: 500;
}

.info-value {
    font-size: 1rem;
    color: #111827;
    word-break: break-word;
}

.info-value code {
    background: #f3f4f6;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-family: 'Courier New', monospace;
    font-size: 0.9em;
    color: #0d9488;
}

.permissions-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.permission-item code {
    background: #f3f4f6;
    padding: 0.5rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    color: #374151;
}

.hr-notes-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.hr-note-item {
    padding: 1rem;
    background: #f9fafb;
    border-left: 3px solid #0d9488;
    border-radius: 0.375rem;
}

.hr-note-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.hr-note-content {
    color: #374151;
    line-height: 1.6;
    margin-bottom: 0.5rem;
}

.hr-note-footer {
    margin-top: 0.5rem;
    padding-top: 0.5rem;
    border-top: 1px solid #e5e7eb;
}

@media (max-width: 768px) {
    .profile-header {
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .profile-actions {
        width: 100%;
        flex-direction: column;
    }
    
    .profile-actions .btn {
        width: 100%;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
$slot = ob_get_clean();
include view_path('dashboard/base.php');
?>

