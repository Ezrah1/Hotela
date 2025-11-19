<?php
$pageTitle = 'Task Manager | Hotela';
$filters = $filters ?? ['view' => 'my', 'status' => 'open', 'q' => ''];
$tasks = $tasks ?? [];

ob_start();
?>
<section class="card">
    <header class="tasks-header">
        <div>
            <h2>Task Manager</h2>
            <p class="tasks-subtitle">Create, assign, and track tasks across departments</p>
        </div>
        <a class="btn btn-primary" href="<?= base_url('staff/dashboard/tasks/create'); ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            New Task
        </a>
    </header>

    <div class="tasks-filters">
        <form method="get" action="<?= base_url('staff/dashboard/tasks'); ?>" class="filter-form">
            <div class="filter-inputs">
                <label>
                    <span>View</span>
                    <select name="view" class="filter-select">
                        <option value="my" <?= ($filters['view'] ?? '') === 'my' ? 'selected' : ''; ?>>My Tasks</option>
                        <option value="team" <?= ($filters['view'] ?? '') === 'team' ? 'selected' : ''; ?>>Team</option>
                        <option value="all" <?= ($filters['view'] ?? '') === 'all' ? 'selected' : ''; ?>>All</option>
                    </select>
                </label>
                <label>
                    <span>Status</span>
                    <select name="status" class="filter-select">
                        <option value="open" <?= ($filters['status'] ?? '') === 'open' ? 'selected' : ''; ?>>Open</option>
                        <option value="in_progress" <?= ($filters['status'] ?? '') === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="blocked" <?= ($filters['status'] ?? '') === 'blocked' ? 'selected' : ''; ?>>Blocked</option>
                        <option value="done" <?= ($filters['status'] ?? '') === 'done' ? 'selected' : ''; ?>>Done</option>
                    </select>
                </label>
                <label>
                    <span>Search</span>
                    <input type="text" name="q" value="<?= htmlspecialchars($filters['q'] ?? ''); ?>" placeholder="Search tasks..." class="filter-input">
                </label>
                <button class="btn btn-outline" type="submit">Apply Filters</button>
                <?php if (($filters['view'] ?? '') !== 'my' || ($filters['status'] ?? '') !== 'open' || !empty($filters['q'] ?? '')): ?>
                    <a href="<?= base_url('staff/dashboard/tasks'); ?>" class="btn btn-ghost">Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <?php if (empty($tasks)): ?>
        <div class="empty-state">
            <svg class="empty-icon" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M9 11l3 3L22 4"></path>
                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
            </svg>
            <h3>No tasks found</h3>
            <p>No tasks match your current filters. Create your first task to get started.</p>
            <a href="<?= base_url('staff/dashboard/tasks/create'); ?>" class="btn btn-primary" style="margin-top: 1rem;">Create Task</a>
        </div>
    <?php else: ?>
        <div class="tasks-table-wrapper">
            <table class="tasks-table">
                <thead>
                <tr>
                    <th>Task</th>
                    <th>Assigned To</th>
                    <th>Department</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($tasks as $task): ?>
                    <?php
                    $status = $task['status'] ?? 'open';
                    $priority = $task['priority'] ?? 'normal';
                    $dueDate = $task['due_on'] ?? null;
                    $isOverdue = $dueDate && strtotime($dueDate) < time() && $status !== 'done';
                    ?>
                    <tr class="<?= $isOverdue ? 'row-overdue' : ''; ?>">
                        <td>
                            <div class="task-title-cell">
                                <strong><?= htmlspecialchars($task['title']); ?></strong>
                                <?php if (!empty($task['description'])): ?>
                                    <span class="task-description"><?= htmlspecialchars(substr($task['description'], 0, 60)); ?><?= strlen($task['description']) > 60 ? '...' : ''; ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php if (!empty($task['assignee_name'])): ?>
                                <div class="assignee-cell">
                                    <div class="assignee-avatar">
                                        <span><?= strtoupper(substr($task['assignee_name'], 0, 1)); ?></span>
                                    </div>
                                    <span><?= htmlspecialchars($task['assignee_name']); ?></span>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">Unassigned</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($task['department'])): ?>
                                <span class="department-badge"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $task['department']))); ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($dueDate): ?>
                                <span class="due-date <?= $isOverdue ? 'due-overdue' : ''; ?>">
                                    <?= date('M j, Y', strtotime($dueDate)); ?>
                                    <?php if ($isOverdue): ?>
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12 6 12 12 16 14"></polyline>
                                        </svg>
                                    <?php endif; ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">No due date</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-badge status-<?= $status; ?>">
                                <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $status))); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($priority): ?>
                                <span class="priority-badge priority-<?= $priority; ?>">
                                    <?= htmlspecialchars(ucfirst($priority)); ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= base_url('staff/dashboard/tasks/view?id=' . (int)($task['id'] ?? 0)); ?>" class="task-action-link">
                                View
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<style>
.tasks-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.tasks-header h2 {
    margin: 0 0 0.25rem 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--dark);
}

.tasks-subtitle {
    margin: 0;
    font-size: 0.95rem;
    color: #64748b;
}

.tasks-header .btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.tasks-filters {
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.filter-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.filter-inputs {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    align-items: flex-end;
}

.filter-select,
.filter-input {
    width: 100%;
    padding: 0.625rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 0.95rem;
    transition: all 0.2s ease;
}

.filter-select:focus,
.filter-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(138, 106, 63, 0.1);
}

.filter-select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    padding-right: 2.5rem;
}

.btn-ghost {
    padding: 0.625rem 1rem;
    background: transparent;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    color: #64748b;
    text-decoration: none;
    font-size: 0.95rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn-ghost:hover {
    border-color: var(--primary);
    color: var(--primary);
    background: rgba(138, 106, 63, 0.05);
}

.tasks-table-wrapper {
    overflow-x: auto;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
}

.tasks-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
}

.tasks-table thead {
    background: #f8fafc;
}

.tasks-table th {
    padding: 1rem;
    text-align: left;
    font-size: 0.875rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 1px solid #e2e8f0;
}

.tasks-table td {
    padding: 1rem;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.95rem;
    color: var(--dark);
}

.tasks-table tbody tr:last-child td {
    border-bottom: none;
}

.tasks-table tbody tr:hover {
    background: #f8fafc;
}

.tasks-table tbody tr.row-overdue {
    background: #fef2f2;
}

.tasks-table tbody tr.row-overdue:hover {
    background: #fee2e2;
}

.task-title-cell {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.task-title-cell strong {
    font-weight: 600;
    color: var(--dark);
}

.task-description {
    font-size: 0.875rem;
    color: #64748b;
}

.assignee-cell {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.assignee-avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary) 0%, #a67c52 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 0.75rem;
    font-weight: 700;
    flex-shrink: 0;
}

.department-badge {
    display: inline-block;
    padding: 0.25rem 0.625rem;
    background: rgba(138, 106, 63, 0.1);
    border-radius: 0.25rem;
    color: var(--primary);
    font-weight: 500;
    font-size: 0.875rem;
}

.due-date {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.875rem;
}

.due-date.due-overdue {
    color: #ef4444;
    font-weight: 600;
}

.status-badge {
    display: inline-block;
    padding: 0.375rem 0.75rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.status-open {
    background: #dbeafe;
    color: #2563eb;
}

.status-in_progress {
    background: #fef3c7;
    color: #d97706;
}

.status-blocked {
    background: #fee2e2;
    color: #dc2626;
}

.status-done {
    background: #dcfce7;
    color: #16a34a;
}

.priority-badge {
    display: inline-block;
    padding: 0.25rem 0.625rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.priority-low {
    background: #f1f5f9;
    color: #64748b;
}

.priority-normal {
    background: #dbeafe;
    color: #2563eb;
}

.priority-high {
    background: #fef3c7;
    color: #d97706;
}

.priority-urgent {
    background: #fee2e2;
    color: #dc2626;
}

.task-action-link {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    color: var(--primary);
    text-decoration: none;
    font-weight: 500;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.task-action-link:hover {
    color: #a67c52;
    gap: 0.5rem;
}

.text-muted {
    color: #94a3b8;
    font-style: italic;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-icon {
    color: #cbd5e1;
    margin-bottom: 1.5rem;
}

.empty-state h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--dark);
}

.empty-state p {
    margin: 0;
    color: #64748b;
}

@media (max-width: 768px) {
    .tasks-header {
        flex-direction: column;
        gap: 1rem;
    }

    .filter-inputs {
        grid-template-columns: 1fr;
    }

    .tasks-table-wrapper {
        overflow-x: scroll;
    }

    .tasks-table {
        min-width: 900px;
    }
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>
