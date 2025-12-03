<?php
$pageTitle = 'Audit Logs | Hotela';
ob_start();
?>

<section class="card">
    <header class="booking-staff-header">
        <div>
            <h2>Audit Logs</h2>
            <p>Track all system activities and changes</p>
        </div>
    </header>

    <!-- Quick Filter Buttons -->
    <div class="card" style="margin-bottom: 1rem;">
        <div class="card-body">
            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
                <span style="font-weight: 600; margin-right: 0.5rem;">Quick Filters:</span>
                <a href="?time=today" class="btn btn-sm <?= (isset($_GET['time']) && $_GET['time'] === 'today') ? 'btn-primary' : 'btn-outline'; ?>">Today</a>
                <a href="?time=hour" class="btn btn-sm <?= (isset($_GET['time']) && $_GET['time'] === 'hour') ? 'btn-primary' : 'btn-outline'; ?>">Last Hour</a>
                <a href="?time=week" class="btn btn-sm <?= (isset($_GET['time']) && $_GET['time'] === 'week') ? 'btn-primary' : 'btn-outline'; ?>">This Week</a>
                <a href="?time=month" class="btn btn-sm <?= (isset($_GET['time']) && $_GET['time'] === 'month') ? 'btn-primary' : 'btn-outline'; ?>">This Month</a>
                <a href="?action=login" class="btn btn-sm <?= (isset($_GET['action']) && $_GET['action'] === 'login') ? 'btn-primary' : 'btn-outline'; ?>">Logins</a>
                <a href="?action=create" class="btn btn-sm <?= (isset($_GET['action']) && $_GET['action'] === 'create') ? 'btn-primary' : 'btn-outline'; ?>">Creates</a>
                <a href="?action=update" class="btn btn-sm <?= (isset($_GET['action']) && $_GET['action'] === 'update') ? 'btn-primary' : 'btn-outline'; ?>">Updates</a>
                <a href="?action=delete" class="btn btn-sm <?= (isset($_GET['action']) && $_GET['action'] === 'delete') ? 'btn-primary' : 'btn-outline'; ?>">Deletes</a>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card" style="margin-bottom: 1.5rem;">
        <div class="card-header">
            <h3>Advanced Filters</h3>
            <button type="button" class="btn btn-sm btn-outline" onclick="resetFilters()">Reset</button>
        </div>
        <div class="card-body">
            <form method="GET" action="<?= base_url('staff/dashboard/audit-logs'); ?>" id="filterForm">
                <div class="filter-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <!-- Search -->
                    <div>
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="modern-input" 
                               placeholder="Search logs..." 
                               value="<?= htmlspecialchars($filters['search'] ?? ''); ?>">
                    </div>
                    
                    <!-- Time Filter -->
                    <div>
                        <label class="form-label">Time Range</label>
                        <select name="time" class="modern-input" onchange="applyTimeFilter(this.value)">
                            <option value="">Custom Date Range</option>
                            <option value="hour" <?= (isset($_GET['time']) && $_GET['time'] === 'hour') ? 'selected' : ''; ?>>Last Hour</option>
                            <option value="today" <?= (isset($_GET['time']) && $_GET['time'] === 'today') ? 'selected' : ''; ?>>Today</option>
                            <option value="yesterday" <?= (isset($_GET['time']) && $_GET['time'] === 'yesterday') ? 'selected' : ''; ?>>Yesterday</option>
                            <option value="week" <?= (isset($_GET['time']) && $_GET['time'] === 'week') ? 'selected' : ''; ?>>This Week</option>
                            <option value="month" <?= (isset($_GET['time']) && $_GET['time'] === 'month') ? 'selected' : ''; ?>>This Month</option>
                            <option value="year" <?= (isset($_GET['time']) && $_GET['time'] === 'year') ? 'selected' : ''; ?>>This Year</option>
                        </select>
                    </div>
                    
                    <!-- Date Range -->
                    <div>
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" id="start_date" class="modern-input" 
                               value="<?= htmlspecialchars($filters['start_date'] ?? ''); ?>">
                    </div>
                    
                    <div>
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" id="end_date" class="modern-input" 
                               value="<?= htmlspecialchars($filters['end_date'] ?? ''); ?>">
                    </div>
                    
                    <!-- User -->
                    <div>
                        <label class="form-label">User</label>
                        <select name="user_id" class="modern-input" style="width: 100%;">
                            <option value="">All Users</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= (int)$user['user_id']; ?>" 
                                        <?= (isset($filters['user_id']) && (int)$filters['user_id'] === (int)$user['user_id']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($user['user_name'] ?? 'Unknown'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Role -->
                    <div>
                        <label class="form-label">Role</label>
                        <select name="role_key" class="modern-input" style="width: 100%;">
                            <option value="">All Roles</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= htmlspecialchars($role); ?>" 
                                        <?= (isset($filters['role_key']) && $filters['role_key'] === $role) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $role))); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Action -->
                    <div>
                        <label class="form-label">Action</label>
                        <select name="action" class="modern-input" style="width: 100%;">
                            <option value="">All Actions</option>
                            <?php foreach ($actions as $action): ?>
                                <option value="<?= htmlspecialchars($action); ?>" 
                                        <?= (isset($filters['action']) && $filters['action'] === $action) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars(ucfirst($action)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Entity Type -->
                    <div>
                        <label class="form-label">Entity Type</label>
                        <select name="entity_type" class="modern-input" style="width: 100%;">
                            <option value="">All Types</option>
                            <?php foreach ($entityTypes as $type): ?>
                                <option value="<?= htmlspecialchars($type); ?>" 
                                        <?= (isset($filters['entity_type']) && $filters['entity_type'] === $type) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars(ucfirst($type)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <button type="button" class="btn btn-outline" onclick="resetFilters()">Clear All</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Card -->
    <div class="card">
        <div class="card-header">
            <h3>Logs (<?= number_format($total); ?> total)</h3>
            <div class="card-header-actions">
                <?php if ($page > 1): ?>
                    <a href="?<?= http_build_query(array_merge($filters, ['page' => $page - 1])); ?>" class="btn btn-sm btn-outline">Previous</a>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?<?= http_build_query(array_merge($filters, ['page' => $page + 1])); ?>" class="btn btn-sm btn-outline">Next</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($logs)): ?>
                <div class="empty-state">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="opacity: 0.3;">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    <p>No audit logs found</p>
                    <p class="text-muted">Try adjusting your filters</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>User</th>
                                <th>Role</th>
                                <th>Action</th>
                                <th>Entity</th>
                                <th>Description</th>
                                <th>IP Address</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td>
                                        <strong><?= date('Y-m-d', strtotime($log['created_at'])); ?></strong><br>
                                        <small class="text-muted"><?= date('H:i:s', strtotime($log['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($log['user_name'] ?? 'System'); ?>
                                        <?php if ($log['user_id']): ?>
                                            <br><small class="text-muted">ID: <?= (int)$log['user_id']; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($log['role_key']): ?>
                                            <span class="badge badge-info">
                                                <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $log['role_key']))); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $actionColors = [
                                            'create' => 'success',
                                            'update' => 'primary',
                                            'delete' => 'danger',
                                            'login' => 'info',
                                            'logout' => 'secondary',
                                            'view' => 'outline',
                                        ];
                                        $color = $actionColors[strtolower($log['action'])] ?? 'outline';
                                        ?>
                                        <span class="badge badge-<?= $color; ?>">
                                            <?= htmlspecialchars(ucfirst($log['action'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($log['entity_type']): ?>
                                            <strong><?= htmlspecialchars(ucfirst($log['entity_type'])); ?></strong>
                                            <?php if ($log['entity_id']): ?>
                                                <br><small class="text-muted">ID: <?= (int)$log['entity_id']; ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($log['description'] ?? '-'); ?>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= htmlspecialchars($log['ip_address'] ?? '-'); ?></small>
                                    </td>
                                    <td>
                                        <?php if (!empty($log['old_values']) || !empty($log['new_values'])): ?>
                                            <button type="button" class="btn btn-sm btn-outline" 
                                                    onclick="showLogDetails(<?= htmlspecialchars(json_encode($log)); ?>)">
                                                View Details
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div style="margin-top: 1.5rem; display: flex; justify-content: center; align-items: center; gap: 0.5rem;">
                        <span class="text-muted">Page <?= $page; ?> of <?= $totalPages; ?></span>
                        <?php if ($page > 1): ?>
                            <a href="?<?= http_build_query(array_merge($filters, ['page' => $page - 1])); ?>" class="btn btn-sm btn-outline">Previous</a>
                        <?php endif; ?>
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <a href="?<?= http_build_query(array_merge($filters, ['page' => $i])); ?>" 
                               class="btn btn-sm <?= $i === $page ? 'btn-primary' : 'btn-outline'; ?>">
                                <?= $i; ?>
                            </a>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                            <a href="?<?= http_build_query(array_merge($filters, ['page' => $page + 1])); ?>" class="btn btn-sm btn-outline">Next</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Log Details Modal -->
<div id="logDetailsModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="card" style="max-width: 800px; max-height: 90vh; overflow-y: auto; margin: 2rem;">
        <div class="card-header">
            <h3>Log Details</h3>
            <button type="button" class="btn btn-sm btn-outline" onclick="closeLogDetails()">Close</button>
        </div>
        <div class="card-body" id="logDetailsContent">
            <!-- Content will be populated by JavaScript -->
        </div>
    </div>
</div>

<script>
function resetFilters() {
    window.location.href = '<?= base_url('staff/dashboard/audit-logs'); ?>';
}

function applyTimeFilter(timeValue) {
    if (timeValue) {
        window.location.href = '<?= base_url('staff/dashboard/audit-logs'); ?>?time=' + timeValue;
    } else {
        // Clear time filter but keep other filters
        const url = new URL(window.location.href);
        url.searchParams.delete('time');
        window.location.href = url.toString();
    }
}

// Auto-submit form when time filter changes
document.addEventListener('DOMContentLoaded', function() {
    const timeSelect = document.querySelector('select[name="time"]');
    if (timeSelect) {
        timeSelect.addEventListener('change', function() {
            if (this.value) {
                applyTimeFilter(this.value);
            }
        });
    }
});

function showLogDetails(log) {
    const modal = document.getElementById('logDetailsModal');
    const content = document.getElementById('logDetailsContent');
    
    let html = '<div class="info-grid">';
    
    html += '<div><strong>Date & Time:</strong><p>' + new Date(log.created_at).toLocaleString() + '</p></div>';
    html += '<div><strong>User:</strong><p>' + (log.user_name || 'System') + '</p></div>';
    html += '<div><strong>Role:</strong><p>' + (log.role_key ? log.role_key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) : '-') + '</p></div>';
    html += '<div><strong>Action:</strong><p>' + log.action + '</p></div>';
    html += '<div><strong>Entity Type:</strong><p>' + (log.entity_type || '-') + '</p></div>';
    html += '<div><strong>Entity ID:</strong><p>' + (log.entity_id || '-') + '</p></div>';
    html += '<div><strong>IP Address:</strong><p>' + (log.ip_address || '-') + '</p></div>';
    html += '<div><strong>User Agent:</strong><p>' + (log.user_agent || '-') + '</p></div>';
    
    if (log.description) {
        html += '<div style="grid-column: 1 / -1;"><strong>Description:</strong><p>' + log.description + '</p></div>';
    }
    
    if (log.old_values && Object.keys(log.old_values).length > 0) {
        html += '<div style="grid-column: 1 / -1;"><strong>Old Values:</strong><pre style="background: #f8f9fa; padding: 1rem; border-radius: 4px; overflow-x: auto;">' + JSON.stringify(log.old_values, null, 2) + '</pre></div>';
    }
    
    if (log.new_values && Object.keys(log.new_values).length > 0) {
        html += '<div style="grid-column: 1 / -1;"><strong>New Values:</strong><pre style="background: #f8f9fa; padding: 1rem; border-radius: 4px; overflow-x: auto;">' + JSON.stringify(log.new_values, null, 2) + '</pre></div>';
    }
    
    html += '</div>';
    
    content.innerHTML = html;
    modal.style.display = 'flex';
}

function closeLogDetails() {
    document.getElementById('logDetailsModal').style.display = 'none';
}

// Close modal on outside click
document.getElementById('logDetailsModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeLogDetails();
    }
});
</script>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

