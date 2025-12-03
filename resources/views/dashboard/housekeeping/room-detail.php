<?php
$pageTitle = $pageTitle ?? 'Room Details | ' . settings('branding.name', 'Hotela');
$room = $room ?? [];
$statusHistory = $statusHistory ?? [];
$tasks = $tasks ?? [];

ob_start();
?>
<section class="card">
    <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2>Room <?= htmlspecialchars($room['display_name'] ?? $room['room_number'] ?? 'N/A'); ?></h2>
            <p style="color: #64748b; margin-top: 0.25rem;">
                <?= htmlspecialchars($room['room_type_name'] ?? 'Room'); ?>
                <?php if (!empty($room['floor'])): ?>
                    · Floor <?= htmlspecialchars($room['floor']); ?>
                <?php endif; ?>
            </p>
        </div>
        <div>
            <a href="<?= base_url('staff/dashboard/housekeeping'); ?>" class="btn btn-outline">← Back to Dashboard</a>
        </div>
    </header>

    <?php if (!empty($_GET['success'])): ?>
        <div class="alert success" style="margin-bottom: 1rem;">
            <?= htmlspecialchars(urldecode($_GET['success'])); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert danger" style="margin-bottom: 1rem;">
            <?= htmlspecialchars(urldecode($_GET['error'])); ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
        <!-- Room Status Card -->
        <div class="room-status-card">
            <h3>Current Status</h3>
            <div style="margin-top: 1rem;">
                <span class="status-badge status-<?= htmlspecialchars($room['status'] ?? 'unknown'); ?>">
                    <?= ucfirst(str_replace('_', ' ', $room['status'] ?? 'Unknown')); ?>
                </span>
            </div>
            
            <!-- Update Room Status Form -->
            <form method="post" action="<?= base_url('staff/dashboard/housekeeping/update-room-status'); ?>" style="margin-top: 1.5rem;">
                <input type="hidden" name="room_id" value="<?= (int)$room['id']; ?>">
                <label style="display: block; margin-bottom: 0.5rem;">
                    <span>Update Status</span>
                    <select name="status" class="modern-select" required>
                        <option value="dirty" <?= ($room['status'] ?? '') === 'dirty' ? 'selected' : ''; ?>>Dirty</option>
                        <option value="in_progress" <?= ($room['status'] ?? '') === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="clean" <?= ($room['status'] ?? '') === 'clean' ? 'selected' : ''; ?>>Clean</option>
                        <option value="inspected" <?= ($room['status'] ?? '') === 'inspected' ? 'selected' : ''; ?>>Inspected</option>
                        <option value="available" <?= ($room['status'] ?? '') === 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="occupied" <?= ($room['status'] ?? '') === 'occupied' ? 'selected' : ''; ?>>Occupied</option>
                        <option value="do_not_disturb" <?= ($room['status'] ?? '') === 'do_not_disturb' ? 'selected' : ''; ?>>Do Not Disturb</option>
                        <option value="needs_maintenance" <?= ($room['status'] ?? '') === 'needs_maintenance' ? 'selected' : ''; ?>>Needs Maintenance</option>
                    </select>
                </label>
                <label style="display: block; margin-bottom: 1rem;">
                    <span>Reason (Optional)</span>
                    <textarea name="reason" rows="2" class="modern-input" placeholder="Reason for status change..."></textarea>
                </label>
                <button type="submit" class="btn btn-primary">Update Status</button>
            </form>
        </div>

        <!-- Room Information Card -->
        <div class="room-info-card">
            <h3>Room Information</h3>
            <dl style="margin-top: 1rem; display: grid; gap: 0.75rem;">
                <div>
                    <dt style="font-weight: 600; color: #64748b; font-size: 0.875rem;">Room Number</dt>
                    <dd style="margin-top: 0.25rem; color: #111827;"><?= htmlspecialchars($room['display_name'] ?? $room['room_number'] ?? 'N/A'); ?></dd>
                </div>
                <div>
                    <dt style="font-weight: 600; color: #64748b; font-size: 0.875rem;">Room Type</dt>
                    <dd style="margin-top: 0.25rem; color: #111827;"><?= htmlspecialchars($room['room_type_name'] ?? 'N/A'); ?></dd>
                </div>
                <?php if (!empty($room['floor'])): ?>
                <div>
                    <dt style="font-weight: 600; color: #64748b; font-size: 0.875rem;">Floor</dt>
                    <dd style="margin-top: 0.25rem; color: #111827;"><?= htmlspecialchars($room['floor']); ?></dd>
                </div>
                <?php endif; ?>
                <?php if (!empty($room['capacity'])): ?>
                <div>
                    <dt style="font-weight: 600; color: #64748b; font-size: 0.875rem;">Capacity</dt>
                    <dd style="margin-top: 0.25rem; color: #111827;"><?= (int)$room['capacity']; ?> guests</dd>
                </div>
                <?php endif; ?>
            </dl>
        </div>
    </div>

    <!-- Tasks Section -->
    <div style="margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3>Housekeeping Tasks</h3>
            <a href="<?= base_url('staff/dashboard/housekeeping/create-task?room_id=' . (int)$room['id']); ?>" class="btn btn-primary btn-small">Create Task</a>
        </div>

        <?php if (empty($tasks)): ?>
            <div class="empty-state" style="text-align: center; padding: 2rem; color: #64748b;">
                <p>No tasks found for this room.</p>
            </div>
        <?php else: ?>
            <div class="task-list">
                <?php foreach ($tasks as $task): ?>
                    <div class="task-item">
                        <div class="task-item__info">
                            <div class="task-item__room">
                                <?= ucfirst(htmlspecialchars($task['task_type'] ?? 'Task')); ?>
                                <?php if (!empty($task['priority']) && $task['priority'] !== 'normal'): ?>
                                    <span class="priority-badge priority-<?= htmlspecialchars($task['priority']); ?>">
                                        <?= ucfirst(htmlspecialchars($task['priority'])); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="task-item__type">
                                Status: <?= ucfirst(htmlspecialchars($task['status'] ?? 'pending')); ?>
                                <?php if (!empty($task['assigned_name'])): ?>
                                    | Assigned to: <?= htmlspecialchars($task['assigned_name']); ?>
                                <?php endif; ?>
                                <?php if (!empty($task['scheduled_date'])): ?>
                                    | Scheduled: <?= date('M j, Y', strtotime($task['scheduled_date'])); ?>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($task['notes'])): ?>
                                <div style="font-size: 0.875rem; color: #64748b; margin-top: 0.25rem;">
                                    <?= htmlspecialchars($task['notes']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="task-item__actions">
                            <?php if ($task['status'] === 'pending'): ?>
                                <form method="post" action="<?= base_url('staff/dashboard/housekeeping/update-task'); ?>" style="display: inline;">
                                    <input type="hidden" name="task_id" value="<?= (int)$task['id']; ?>">
                                    <input type="hidden" name="status" value="in_progress">
                                    <button type="submit" class="btn btn-primary btn-small">Start</button>
                                </form>
                            <?php elseif ($task['status'] === 'in_progress'): ?>
                                <form method="post" action="<?= base_url('staff/dashboard/housekeeping/update-task'); ?>" style="display: inline;">
                                    <input type="hidden" name="task_id" value="<?= (int)$task['id']; ?>">
                                    <input type="hidden" name="status" value="completed">
                                    <button type="submit" class="btn btn-primary btn-small">Complete</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Status History Section -->
    <div>
        <h3 style="margin-bottom: 1rem;">Status History</h3>
        <?php if (empty($statusHistory)): ?>
            <div class="empty-state" style="text-align: center; padding: 2rem; color: #64748b;">
                <p>No status history available.</p>
            </div>
        <?php else: ?>
            <div class="status-history">
                <?php foreach ($statusHistory as $history): ?>
                    <div class="history-item">
                        <div class="history-item__status">
                            <span class="status-badge status-<?= htmlspecialchars($history['new_status'] ?? 'unknown'); ?>">
                                <?= ucfirst(str_replace('_', ' ', $history['new_status'] ?? 'Unknown')); ?>
                            </span>
                        </div>
                        <div class="history-item__info">
                            <div style="font-weight: 600; color: #111827;">
                                Changed from <?= ucfirst(str_replace('_', ' ', $history['previous_status'] ?? 'N/A')); ?>
                                to <?= ucfirst(str_replace('_', ' ', $history['new_status'] ?? 'Unknown')); ?>
                            </div>
                            <?php if (!empty($history['reason'])): ?>
                                <div style="font-size: 0.875rem; color: #64748b; margin-top: 0.25rem;">
                                    Reason: <?= htmlspecialchars($history['reason']); ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($history['changed_by_name'])): ?>
                                <div style="font-size: 0.875rem; color: #64748b; margin-top: 0.25rem;">
                                    Changed by: <?= htmlspecialchars($history['changed_by_name']); ?>
                                </div>
                            <?php endif; ?>
                            <div style="font-size: 0.75rem; color: #9ca3af; margin-top: 0.25rem;">
                                <?= !empty($history['created_at']) ? date('M j, Y H:i', strtotime($history['created_at'])) : '—'; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
.room-status-card, .room-info-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 1.5rem;
}

.room-status-card h3, .room-info-card h3 {
    margin: 0 0 1rem 0;
    font-size: 1.125rem;
    color: #111827;
}

.status-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
}

.status-badge.status-dirty { background: #fee2e2; color: #991b1b; }
.status-badge.status-clean { background: #d1fae5; color: #065f46; }
.status-badge.status-in_progress { background: #fef3c7; color: #92400e; }
.status-badge.status-do_not_disturb { background: #eef2ff; color: #4338ca; }
.status-badge.status-needs_maintenance { background: #fee2e2; color: #991b1b; }
.status-badge.status-inspected { background: #d1fae5; color: #065f46; }
.status-badge.status-available { background: #dcfce7; color: #16a34a; }
.status-badge.status-occupied { background: #dbeafe; color: #2563eb; }

.priority-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    margin-left: 0.5rem;
}

.priority-badge.priority-high { background: #fee2e2; color: #991b1b; }
.priority-badge.priority-urgent { background: #dc2626; color: #fff; }
.priority-badge.priority-low { background: #f3f4f6; color: #6b7280; }

.task-item {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 0.75rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.task-item__info {
    flex: 1;
}

.task-item__room {
    font-weight: 600;
    color: #111827;
    margin-bottom: 0.25rem;
}

.task-item__type {
    font-size: 0.875rem;
    color: #64748b;
}

.task-item__actions {
    display: flex;
    gap: 0.5rem;
}

.status-history {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.history-item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 0.5rem;
    border-left: 3px solid #3b82f6;
}

.history-item__status {
    flex-shrink: 0;
}

.history-item__info {
    flex: 1;
}

.btn-small {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

.alert {
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
}

.alert.success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #86efac;
}

.alert.danger {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
}

@media (max-width: 768px) {
    section > div[style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

