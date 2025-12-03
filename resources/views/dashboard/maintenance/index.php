<?php
$pageTitle = 'Maintenance Requests | Hotela';
$requests = $requests ?? [];
$statistics = $statistics ?? ['pending' => 0, 'in_progress' => 0, 'completed' => 0, 'cancelled' => 0, 'total' => 0];
$filters = $filters ?? ['status' => null, 'room_id' => null, 'assigned_to' => null];

ob_start();
?>
<section class="card">
    <header class="maintenance-header">
        <div>
            <h2>Maintenance Requests</h2>
            <p class="maintenance-subtitle">Track and manage property maintenance requests</p>
        </div>
        <a class="btn btn-primary" href="<?= base_url('staff/dashboard/maintenance/create'); ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            New Request
        </a>
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

    <!-- Statistics -->
    <div class="maintenance-stats">
        <div class="stat-card stat-pending">
            <div class="stat-icon">⏳</div>
            <div class="stat-content">
                <div class="stat-value"><?= number_format($statistics['pending'] ?? 0); ?></div>
                <div class="stat-label">Pending</div>
            </div>
        </div>
        <div class="stat-card stat-ops">
            <div class="stat-icon">👁️</div>
            <div class="stat-content">
                <div class="stat-value"><?= number_format($statistics['ops_review'] ?? 0); ?></div>
                <div class="stat-label">Ops Review</div>
            </div>
        </div>
        <div class="stat-card stat-finance">
            <div class="stat-icon">💰</div>
            <div class="stat-content">
                <div class="stat-value"><?= number_format($statistics['finance_review'] ?? 0); ?></div>
                <div class="stat-label">Finance Review</div>
            </div>
        </div>
        <div class="stat-card stat-progress">
            <div class="stat-icon">🔧</div>
            <div class="stat-content">
                <div class="stat-value"><?= number_format(($statistics['assigned'] ?? 0) + ($statistics['in_progress'] ?? 0)); ?></div>
                <div class="stat-label">Active</div>
            </div>
        </div>
        <div class="stat-card stat-completed">
            <div class="stat-icon">✅</div>
            <div class="stat-content">
                <div class="stat-value"><?= number_format($statistics['verified'] ?? 0); ?></div>
                <div class="stat-label">Verified</div>
            </div>
        </div>
        <div class="stat-card stat-total">
            <div class="stat-icon">📋</div>
            <div class="stat-content">
                <div class="stat-value"><?= number_format($statistics['total'] ?? 0); ?></div>
                <div class="stat-label">Total</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="maintenance-filters">
        <form method="get" action="<?= base_url('staff/dashboard/maintenance'); ?>" class="filter-form">
            <div class="filter-grid">
                <?php if ($canViewAll ?? false): ?>
                    <label>
                        <span>View</span>
                        <select name="filter" class="modern-select">
                            <option value="all" <?= ($filters['filter'] ?? 'all') === 'all' ? 'selected' : ''; ?>>All Requests</option>
                            <option value="department" <?= ($filters['filter'] ?? '') === 'department' ? 'selected' : ''; ?>>My Department</option>
                            <option value="mine" <?= ($filters['filter'] ?? '') === 'mine' ? 'selected' : ''; ?>>My Requests</option>
                        </select>
                    </label>
                <?php else: ?>
                    <label>
                        <span>View</span>
                        <select name="filter" class="modern-select">
                            <option value="department" <?= ($filters['filter'] ?? 'department') === 'department' ? 'selected' : ''; ?>>My Department</option>
                            <option value="mine" <?= ($filters['filter'] ?? '') === 'mine' ? 'selected' : ''; ?>>My Requests</option>
                        </select>
                    </label>
                <?php endif; ?>
                <label>
                    <span>Status</span>
                    <select name="status" class="modern-select">
                        <option value="">All Statuses</option>
                        <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending (Ops Review)</option>
                        <option value="ops_review" <?= ($filters['status'] ?? '') === 'ops_review' ? 'selected' : ''; ?>>Ops Review</option>
                        <option value="finance_review" <?= ($filters['status'] ?? '') === 'finance_review' ? 'selected' : ''; ?>>Finance Review</option>
                        <option value="approved" <?= ($filters['status'] ?? '') === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="assigned" <?= ($filters['status'] ?? '') === 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                        <option value="in_progress" <?= ($filters['status'] ?? '') === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="completed" <?= ($filters['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="verified" <?= ($filters['status'] ?? '') === 'verified' ? 'selected' : ''; ?>>Verified</option>
                        <option value="cancelled" <?= ($filters['status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </label>
                <label>
                    <span>Room</span>
                    <select name="room_id" class="modern-select">
                        <option value="">All Rooms</option>
                        <?php foreach ($allRooms ?? [] as $room): ?>
                            <option value="<?= $room['id']; ?>" <?= ($filters['room_id'] ?? null) === $room['id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($room['room_number']); ?> <?= $room['display_name'] ? '(' . htmlspecialchars($room['display_name']) . ')' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Assigned To</span>
                    <select name="assigned_to" class="modern-select">
                        <option value="">All Staff</option>
                        <?php foreach ($allStaff ?? [] as $staff): ?>
                            <option value="<?= $staff['id']; ?>" <?= ($filters['assigned_to'] ?? null) === $staff['id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($staff['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="filter-actions">
                    <button class="btn btn-primary" type="submit">Apply Filters</button>
                    <a href="<?= base_url('staff/dashboard/maintenance'); ?>" class="btn btn-outline">Clear</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Requests Table -->
    <?php if (empty($requests)): ?>
        <div class="empty-state">
            <svg class="empty-icon" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
            <h3>No maintenance requests found</h3>
            <p>No requests match your current filters. Create your first maintenance request to get started.</p>
            <a href="<?= base_url('staff/dashboard/maintenance/create'); ?>" class="btn btn-primary" style="margin-top: 1rem;">Create Request</a>
        </div>
    <?php else: ?>
        <div class="maintenance-table-wrapper">
            <table class="maintenance-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Title</th>
                        <th>Room</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Requested By</th>
                        <th>Assigned To</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $req): ?>
                        <tr>
                            <td>
                                <code style="background: #f1f5f9; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.875rem;">
                                    <?= htmlspecialchars($req['reference']); ?>
                                </code>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($req['title']); ?></strong>
                                <?php if (!empty($req['description'])): ?>
                                    <br><small style="color: #64748b;"><?= htmlspecialchars(substr($req['description'], 0, 60)); ?><?= strlen($req['description']) > 60 ? '...' : ''; ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($req['room_number']): ?>
                                    <?= htmlspecialchars($req['room_number']); ?>
                                    <?php if ($req['room_name']): ?>
                                        <br><small style="color: #64748b;"><?= htmlspecialchars($req['room_name']); ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: #94a3b8;">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $priorityColors = [
                                    'urgent' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
                                    'high' => ['bg' => '#fef3c7', 'text' => '#92400e'],
                                    'medium' => ['bg' => '#dbeafe', 'text' => '#1e40af'],
                                    'low' => ['bg' => '#f3f4f6', 'text' => '#374151'],
                                ];
                                $priority = strtolower($req['priority'] ?? 'medium');
                                $colors = $priorityColors[$priority] ?? $priorityColors['medium'];
                                ?>
                                <span class="priority-badge" style="background: <?= $colors['bg']; ?>; color: <?= $colors['text']; ?>; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.875rem; font-weight: 500; text-transform: capitalize;">
                                    <?= htmlspecialchars($priority); ?>
                                </span>
                            </td>
                            <td>
                                <?php
            $statusColors = [
                'pending' => ['bg' => '#fef3c7', 'text' => '#92400e'],
                'ops_review' => ['bg' => '#dbeafe', 'text' => '#1e40af'],
                'finance_review' => ['bg' => '#fef3c7', 'text' => '#92400e'],
                'approved' => ['bg' => '#d1fae5', 'text' => '#065f46'],
                'assigned' => ['bg' => '#e0e7ff', 'text' => '#3730a3'],
                'in_progress' => ['bg' => '#dbeafe', 'text' => '#1e40af'],
                'completed' => ['bg' => '#d1fae5', 'text' => '#065f46'],
                'verified' => ['bg' => '#d1fae5', 'text' => '#065f46'],
                'cancelled' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
            ];
                                $status = strtolower($req['status'] ?? 'pending');
                                $statusColor = $statusColors[$status] ?? $statusColors['pending'];
                                ?>
                                <?php
                                $statusLabels = [
                                    'pending' => 'Pending (Ops Review)',
                                    'ops_review' => 'Ops Review',
                                    'finance_review' => 'Finance Review',
                                    'approved' => 'Approved',
                                    'assigned' => 'Assigned',
                                    'in_progress' => 'In Progress',
                                    'completed' => 'Completed',
                                    'verified' => 'Verified',
                                    'cancelled' => 'Cancelled',
                                ];
                                $statusLabel = $statusLabels[$status] ?? str_replace('_', ' ', ucfirst($status));
                                ?>
                                <span class="status-badge" style="background: <?= $statusColor['bg']; ?>; color: <?= $statusColor['text']; ?>; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.875rem; font-weight: 500;">
                                    <?= htmlspecialchars($statusLabel); ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($req['requested_by_name'] ?? 'N/A'); ?></td>
                            <td><?= htmlspecialchars($req['assigned_to_name'] ?? 'Unassigned'); ?></td>
                            <td><?= date('M j, Y', strtotime($req['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="<?= base_url('staff/dashboard/maintenance/show?id=' . $req['id']); ?>" class="btn-icon" title="View">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </a>
                                    <?php
                                    $currentUser = \App\Support\Auth::user();
                                    $userRole = $currentUser['role_key'] ?? '';
                                    $status = strtolower($req['status'] ?? 'pending');
                                    $canVerifyOps = \App\Support\DepartmentHelper::canVerifyOperations($userRole);
                                    $canApproveFinance = \App\Support\DepartmentHelper::canApproveFinance($userRole);
                                    $canAssignSuppliers = \App\Support\DepartmentHelper::canAssignSuppliers($userRole);
                                    
                                    // Show workflow actions based on status and role
                                    if ($status === 'pending' && $canVerifyOps): ?>
                                        <a href="<?= base_url('staff/dashboard/maintenance/ops-review?id=' . $req['id']); ?>" class="btn-icon btn-primary-icon" title="Ops Review">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M9 11l3 3L22 4"></path>
                                                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                                            </svg>
                                        </a>
                                    <?php elseif ($status === 'finance_review' && $canApproveFinance): ?>
                                        <a href="<?= base_url('staff/dashboard/maintenance/finance-review?id=' . $req['id']); ?>" class="btn-icon btn-primary-icon" title="Finance Review">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                            </svg>
                                        </a>
                                    <?php elseif ($status === 'approved' && $canAssignSuppliers): ?>
                                        <a href="<?= base_url('staff/dashboard/maintenance/assign-supplier?id=' . $req['id']); ?>" class="btn-icon btn-primary-icon" title="Assign Supplier">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                                <circle cx="8.5" cy="7" r="4"></circle>
                                                <line x1="20" y1="8" x2="20" y2="14"></line>
                                                <line x1="23" y1="11" x2="17" y2="11"></line>
                                            </svg>
                                        </a>
                                    <?php elseif ($status === 'completed' && $canVerifyOps): ?>
                                        <a href="<?= base_url('staff/dashboard/maintenance/verify-work?id=' . $req['id']); ?>" class="btn-icon btn-primary-icon" title="Verify Work">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="20 6 9 17 4 12"></polyline>
                                            </svg>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<style>
.maintenance-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.maintenance-header h2 {
    margin: 0 0 0.25rem 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--dark);
}

.maintenance-subtitle {
    margin: 0;
    font-size: 0.95rem;
    color: #64748b;
}

.maintenance-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #fff;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
}

.stat-card.stat-pending {
    border-left: 4px solid #f59e0b;
}

.stat-card.stat-ops {
    border-left: 4px solid #3b82f6;
}

.stat-card.stat-finance {
    border-left: 4px solid #f59e0b;
}

.stat-card.stat-progress {
    border-left: 4px solid #6366f1;
}

.stat-card.stat-completed {
    border-left: 4px solid #10b981;
}

.stat-card.stat-total {
    border-left: 4px solid #6366f1;
}

.stat-icon {
    font-size: 2rem;
}

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.2;
}

.stat-label {
    font-size: 0.875rem;
    color: #64748b;
    margin-top: 0.25rem;
}

.maintenance-filters {
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
}

.maintenance-table-wrapper {
    overflow-x: auto;
}

.maintenance-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 0.5rem;
    overflow: hidden;
}

.maintenance-table thead {
    background: #f8fafc;
}

.maintenance-table th {
    padding: 0.75rem 1rem;
    text-align: left;
    font-weight: 600;
    font-size: 0.875rem;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.maintenance-table td {
    padding: 0.75rem 1rem;
    border-top: 1px solid #e2e8f0;
    font-size: 0.95rem;
    color: #1e293b;
}

.maintenance-table tbody tr:hover {
    background: #f8fafc;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.btn-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    padding: 0;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    color: #64748b;
    text-decoration: none;
    transition: all 0.2s ease;
}

.btn-icon:hover {
    background: #e2e8f0;
    color: #1e293b;
}

.btn-primary-icon {
    background: #8b5cf6;
    color: #fff;
    border-color: #8b5cf6;
}

.btn-primary-icon:hover {
    background: #7c3aed;
    color: #fff;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: #64748b;
}

.empty-icon {
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
    color: #1e293b;
}

.empty-state p {
    margin: 0;
    font-size: 0.95rem;
}

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

.modern-select:focus {
    outline: none;
    border-color: #8b5cf6;
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
}

@media (max-width: 768px) {
    .maintenance-stats {
        grid-template-columns: repeat(2, 1fr);
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

