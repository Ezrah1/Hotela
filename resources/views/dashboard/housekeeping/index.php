<?php
$pageTitle = $pageTitle ?? 'Housekeeping Dashboard | ' . settings('branding.name', 'Hotela');
$rooms = $rooms ?? [];
$tasks = $tasks ?? [];
$pendingRequests = $pendingRequests ?? [];
$dailyStats = $dailyStats ?? [];
$staffList = $staffList ?? [];
$status = $status ?? null;
$assignedTo = $assignedTo ?? null;
$myTasks = $myTasks ?? false;

ob_start();
?>

<style>
.housekeeping-board {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 1.5rem;
}

.room-card {
    border: 2px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 1rem;
    background: #fff;
    cursor: pointer;
    transition: all 0.2s ease;
}

.room-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.room-card.status-dirty {
    border-color: #ef4444;
    background: #fef2f2;
}

.room-card.status-clean {
    border-color: #10b981;
    background: #f0fdf4;
}

.room-card.status-in_progress {
    border-color: #f59e0b;
    background: #fffbeb;
}

.room-card.status-do_not_disturb {
    border-color: #6366f1;
    background: #eef2ff;
}

.room-card.status-needs_maintenance {
    border-color: #dc2626;
    background: #fee2e2;
}

.room-card.status-inspected {
    border-color: #059669;
    background: #d1fae5;
}

.room-card.status-available {
    border-color: #10b981;
    background: #f0fdf4;
}

.room-card.status-occupied {
    border-color: #3b82f6;
    background: #eff6ff;
}

.room-card__header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.5rem;
}

.room-card__number {
    font-weight: 700;
    font-size: 1.125rem;
    color: #0f172a;
}

.room-card__status {
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.room-card__status.status-dirty {
    background: #fee2e2;
    color: #991b1b;
}

.room-card__status.status-clean {
    background: #d1fae5;
    color: #065f46;
}

.room-card__status.status-in_progress {
    background: #fef3c7;
    color: #92400e;
}

.room-card__meta {
    font-size: 0.875rem;
    color: #64748b;
    margin-top: 0.5rem;
}

.room-card__actions {
    margin-top: 0.75rem;
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.housekeeping-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-card__value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #0f172a;
}

.stat-card__label {
    font-size: 0.875rem;
    color: #64748b;
}

.task-list {
    margin-top: 1.5rem;
}

.task-item {
    background: #fff;
    border: 1px solid #e2e8f0;
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
    color: #0f172a;
}

.task-item__type {
    font-size: 0.875rem;
    color: #64748b;
}

.task-item__actions {
    display: flex;
    gap: 0.5rem;
}
</style>

<section class="card">
    <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <h2>Housekeeping Dashboard</h2>
            <p style="color: #64748b; margin-top: 0.25rem;">Manage room cleaning and maintenance tasks</p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="<?= base_url('staff/dashboard/housekeeping?my_tasks=1'); ?>" class="btn btn-outline <?= $myTasks ? 'btn-primary' : ''; ?>">
                My Tasks
            </a>
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

    <!-- Daily Statistics -->
    <div class="housekeeping-stats">
        <div class="stat-card">
            <div class="stat-card__value"><?= $dailyStats['pending_count'] ?? 0; ?></div>
            <div class="stat-card__label">Pending</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__value"><?= $dailyStats['in_progress_count'] ?? 0; ?></div>
            <div class="stat-card__label">In Progress</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__value"><?= $dailyStats['completed_count'] ?? 0; ?></div>
            <div class="stat-card__label">Completed</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__value"><?= $dailyStats['inspected_count'] ?? 0; ?></div>
            <div class="stat-card__label">Inspected</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__value"><?= $dailyStats['approved_count'] ?? 0; ?></div>
            <div class="stat-card__label">Approved</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__value"><?= count($pendingRequests); ?></div>
            <div class="stat-card__label">Guest Requests</div>
        </div>
    </div>

    <!-- Filters -->
    <form method="get" action="<?= base_url('staff/dashboard/housekeeping'); ?>" style="margin-bottom: 1.5rem;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <label>
                <span>Status</span>
                <select name="status" class="modern-select">
                    <option value="">All Statuses</option>
                    <option value="dirty" <?= $status === 'dirty' ? 'selected' : ''; ?>>Dirty</option>
                    <option value="clean" <?= $status === 'clean' ? 'selected' : ''; ?>>Clean</option>
                    <option value="in_progress" <?= $status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="do_not_disturb" <?= $status === 'do_not_disturb' ? 'selected' : ''; ?>>Do Not Disturb</option>
                    <option value="needs_maintenance" <?= $status === 'needs_maintenance' ? 'selected' : ''; ?>>Needs Maintenance</option>
                    <option value="inspected" <?= $status === 'inspected' ? 'selected' : ''; ?>>Inspected</option>
                </select>
            </label>
            <label>
                <span>Assigned To</span>
                <select name="assigned_to" class="modern-select">
                    <option value="">All Staff</option>
                    <?php foreach ($staffList as $staff): ?>
                        <option value="<?= (int)$staff['id']; ?>" <?= $assignedTo === (int)$staff['id'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($staff['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div style="display: flex; align-items: flex-end; gap: 0.5rem;">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="<?= base_url('staff/dashboard/housekeeping'); ?>" class="btn btn-outline">Clear</a>
            </div>
        </div>
    </form>

    <!-- Room Status Board -->
    <h3 style="margin-bottom: 1rem;">Room Status Board</h3>
    <div class="housekeeping-board">
        <?php foreach ($rooms as $room): ?>
            <div class="room-card status-<?= htmlspecialchars($room['status']); ?>" 
                 onclick="window.location.href='<?= base_url('staff/dashboard/housekeeping/room?room_id=' . (int)$room['id']); ?>'">
                <div class="room-card__header">
                    <div class="room-card__number">
                        <?= htmlspecialchars($room['display_name'] ?? $room['room_number']); ?>
                    </div>
                    <span class="room-card__status status-<?= htmlspecialchars($room['status']); ?>">
                        <?= ucfirst(str_replace('_', ' ', $room['status'])); ?>
                    </span>
                </div>
                <div class="room-card__meta">
                    <div><?= htmlspecialchars($room['room_type_name']); ?></div>
                    <?php if (!empty($room['assigned_housekeeper'])): ?>
                        <div style="font-size: 0.75rem; margin-top: 0.25rem;">
                            👤 <?= htmlspecialchars($room['assigned_housekeeper']); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($room['pending_tasks']) && (int)$room['pending_tasks'] > 0): ?>
                        <div style="font-size: 0.75rem; margin-top: 0.25rem; color: #dc2626;">
                            ⚠️ <?= (int)$room['pending_tasks']; ?> task(s)
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($room['is_dnd']) && (int)$room['is_dnd']): ?>
                        <div style="font-size: 0.75rem; margin-top: 0.25rem; color: #6366f1;">
                            🚫 Do Not Disturb
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($rooms)): ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 2rem; color: #64748b;">
                No rooms found
            </div>
        <?php endif; ?>
    </div>

    <!-- Task List -->
    <?php if (!empty($tasks)): ?>
        <h3 style="margin-top: 2rem; margin-bottom: 1rem;">Tasks</h3>
        <div class="task-list">
            <?php foreach ($tasks as $task): ?>
                <div class="task-item">
                    <div class="task-item__info">
                        <div class="task-item__room">
                            <?= htmlspecialchars($task['room_number'] ?? $task['display_name'] ?? 'Room'); ?>
                            - <?= ucfirst(htmlspecialchars($task['task_type'])); ?>
                        </div>
                        <div class="task-item__type">
                            Priority: <?= ucfirst(htmlspecialchars($task['priority'])); ?> | 
                            Status: <?= ucfirst(htmlspecialchars($task['status'])); ?>
                            <?php if ($task['assigned_name']): ?>
                                | Assigned to: <?= htmlspecialchars($task['assigned_name']); ?>
                            <?php endif; ?>
                        </div>
                        <?php if ($task['notes']): ?>
                            <div style="font-size: 0.875rem; color: #64748b; margin-top: 0.25rem;">
                                <?= htmlspecialchars($task['notes']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="task-item__actions">
                        <form method="post" action="<?= base_url('staff/dashboard/housekeeping/update-task'); ?>" style="display: inline;">
                            <input type="hidden" name="task_id" value="<?= (int)$task['id']; ?>">
                            <?php if ($task['status'] === 'pending'): ?>
                                <input type="hidden" name="status" value="in_progress">
                                <button type="submit" class="btn btn-primary btn-small">Start</button>
                            <?php elseif ($task['status'] === 'in_progress'): ?>
                                <input type="hidden" name="status" value="completed">
                                <button type="submit" class="btn btn-primary btn-small">Complete</button>
                            <?php elseif ($task['status'] === 'completed' && ($_SESSION['staff_role'] ?? '') === 'operations_manager'): ?>
                                <input type="hidden" name="status" value="inspected">
                                <button type="submit" class="btn btn-primary btn-small">Inspect</button>
                            <?php elseif ($task['status'] === 'inspected' && ($_SESSION['staff_role'] ?? '') === 'operations_manager'): ?>
                                <input type="hidden" name="status" value="approved">
                                <button type="submit" class="btn btn-primary btn-small">Approve</button>
                            <?php endif; ?>
                        </form>
                        <button type="button" class="btn btn-outline btn-small" 
                                onclick="openMaintenanceModal(<?= (int)$task['id']; ?>, <?= (int)$task['room_id']; ?>)">
                            Report Issue
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Guest Requests -->
    <?php if (!empty($pendingRequests)): ?>
        <h3 style="margin-top: 2rem; margin-bottom: 1rem;">Guest Requests</h3>
        <div class="task-list">
            <?php foreach ($pendingRequests as $request): ?>
                <div class="task-item">
                    <div class="task-item__info">
                        <div class="task-item__room">
                            Room <?= htmlspecialchars($request['room_number'] ?? $request['display_name'] ?? 'Room'); ?>
                            - <?= ucfirst(str_replace('_', ' ', $request['request_type'])); ?>
                        </div>
                        <div class="task-item__type">
                            Guest: <?= htmlspecialchars($request['guest_name']); ?> | 
                            Priority: <?= ucfirst(htmlspecialchars($request['priority'])); ?>
                        </div>
                        <?php if ($request['request_details']): ?>
                            <div style="font-size: 0.875rem; color: #64748b; margin-top: 0.25rem;">
                                <?= htmlspecialchars($request['request_details']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="task-item__actions">
                        <?php if ($request['status'] === 'pending'): ?>
                            <form method="post" action="<?= base_url('staff/dashboard/housekeeping/update-guest-request'); ?>" style="display: inline;">
                                <input type="hidden" name="request_id" value="<?= (int)$request['id']; ?>">
                                <input type="hidden" name="status" value="assigned">
                                <input type="hidden" name="assigned_to" value="<?= (\App\Support\Auth::check() ? \App\Support\Auth::user()['id'] : ''); ?>">
                                <button type="submit" class="btn btn-primary btn-small">Accept</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- Maintenance Report Modal -->
<div id="maintenanceModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: #fff; border-radius: 0.75rem; padding: 2rem; max-width: 500px; width: 90%;">
        <h3>Report Maintenance Issue</h3>
        <form method="post" action="<?= base_url('staff/dashboard/housekeeping/report-maintenance'); ?>">
            <input type="hidden" name="task_id" id="maintenance_task_id">
            <input type="hidden" name="room_id" id="maintenance_room_id">
            <label style="display: block; margin-top: 1rem;">
                <span>Title</span>
                <input type="text" name="title" required class="modern-input">
            </label>
            <label style="display: block; margin-top: 1rem;">
                <span>Description</span>
                <textarea name="description" required class="modern-input" rows="3"></textarea>
            </label>
            <label style="display: block; margin-top: 1rem;">
                <span>Category</span>
                <select name="category" class="modern-select">
                    <option value="Electrical">Electrical</option>
                    <option value="Plumbing">Plumbing</option>
                    <option value="Furniture">Furniture</option>
                    <option value="HVAC">HVAC</option>
                    <option value="General">General</option>
                </select>
            </label>
            <label style="display: block; margin-top: 1rem;">
                <span>Priority</span>
                <select name="priority" class="modern-select">
                    <option value="low">Low</option>
                    <option value="normal" selected>Normal</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                </select>
            </label>
            <div style="display: flex; gap: 0.5rem; margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">Report</button>
                <button type="button" class="btn btn-outline" onclick="closeMaintenanceModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openMaintenanceModal(taskId, roomId) {
    document.getElementById('maintenance_task_id').value = taskId;
    document.getElementById('maintenance_room_id').value = roomId;
    document.getElementById('maintenanceModal').style.display = 'flex';
}

function closeMaintenanceModal() {
    document.getElementById('maintenanceModal').style.display = 'none';
}

// Close modal on backdrop click
document.getElementById('maintenanceModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeMaintenanceModal();
    }
});
</script>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

