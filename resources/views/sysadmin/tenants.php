<?php
$pageTitle = 'Tenant Management';
ob_start();
?>

<div class="sysadmin-page-header">
    <div>
        <h2>Tenant Management</h2>
        <p class="page-subtitle">Manage hotel installations and their configurations</p>
    </div>
    <div>
        <button onclick="document.getElementById('addTenantModal').style.display='flex'" class="btn btn-primary">
            + Add New Tenant
        </button>
    </div>
</div>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error" style="margin-bottom: 2rem;">
        <?php
        $errors = [
            'name_required' => 'Tenant name is required.',
            'domain_exists' => 'This domain is already in use. Please choose another.',
        ];
        echo htmlspecialchars($errors[$_GET['error']] ?? 'An error occurred.');
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success" style="margin-bottom: 2rem;">
        <?php
        $messages = [
            'tenant_created' => 'Tenant created successfully.',
        ];
        echo htmlspecialchars($messages[$_GET['success']] ?? 'Operation completed successfully.');
        ?>
    </div>
<?php endif; ?>

<div class="sysadmin-card">
    <div class="card-body">
        <?php if (empty($tenants)): ?>
            <div class="empty-state">
                <p>No tenants found</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="sysadmin-table">
                    <thead>
                        <tr>
                            <th>Tenant Name</th>
                            <th>Domain</th>
                            <th>Status</th>
                            <th>Users</th>
                            <th>Bookings (30d)</th>
                            <th>License</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tenants as $tenant): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($tenant['name']); ?></strong>
                                </td>
                                <td><?= htmlspecialchars($tenant['domain']); ?></td>
                                <td>
                                    <span class="badge badge-<?= $tenant['status'] === 'active' ? 'success' : 'warning'; ?>">
                                        <?= ucfirst($tenant['status']); ?>
                                    </span>
                                </td>
                                <td><?= $tenant['stats']['user_count'] ?? 0; ?></td>
                                <td><?= $tenant['stats']['booking_count_30d'] ?? 0; ?></td>
                                <td>
                                    <span class="badge badge-<?= ($tenant['stats']['license_status'] ?? 'inactive') === 'active' ? 'success' : 'warning'; ?>">
                                        <?= ucfirst($tenant['stats']['license_status'] ?? 'inactive'); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="<?= base_url('sysadmin/tenants/view?id=' . $tenant['id']); ?>" class="btn btn-sm btn-outline">View</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.sysadmin-page-header {
    margin-bottom: 2rem;
}

.sysadmin-page-header h2 {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 0.5rem 0;
}

.page-subtitle {
    color: #64748b;
    margin: 0;
}

.sysadmin-table {
    width: 100%;
    border-collapse: collapse;
}

.sysadmin-table th {
    text-align: left;
    padding: 0.75rem;
    font-weight: 600;
    color: #475569;
    border-bottom: 2px solid #e2e8f0;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.sysadmin-table td {
    padding: 1rem 0.75rem;
    border-bottom: 1px solid #f1f5f9;
    color: #1e293b;
}

.sysadmin-table tr:hover {
    background: #f8fafc;
}

.badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.badge-success {
    background: #f0fdf4;
    color: #166534;
}

.badge-warning {
    background: #fffbeb;
    color: #d97706;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #64748b;
}

.sysadmin-page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
}

.btn-outline {
    background: white;
    color: #667eea;
    border: 2px solid #667eea;
}

.btn-outline:hover {
    background: #667eea;
    color: white;
}

.alert {
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
}

.alert-error {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.alert-success {
    background: #f0fdf4;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    border-radius: 0.75rem;
    padding: 2rem;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.5rem;
    color: #1e293b;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #64748b;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.25rem;
}

.modal-close:hover {
    background: #f1f5f9;
    color: #1e293b;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #374151;
}

.form-group input {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 1rem;
    transition: border-color 0.2s;
}

.form-group input:focus {
    outline: none;
    border-color: #667eea;
}

.form-actions {
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e2e8f0;
}
</style>

<!-- Add Tenant Modal -->
<div id="addTenantModal" class="modal" onclick="if(event.target === this) this.style.display='none'">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3>Add New Tenant</h3>
            <button class="modal-close" onclick="document.getElementById('addTenantModal').style.display='none'">&times;</button>
        </div>
        <form method="POST" action="<?= base_url('sysadmin/tenants'); ?>">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label for="name">Tenant Name *</label>
                <input type="text" id="name" name="name" required placeholder="e.g., Joyce Resorts">
            </div>
            <div class="form-group">
                <label for="domain">Domain</label>
                <input type="text" id="domain" name="domain" placeholder="e.g., joyce.hotela.local">
                <small style="color: #64748b; font-size: 0.875rem; display: block; margin-top: 0.25rem;">Leave empty to auto-generate</small>
            </div>
            <div class="form-group">
                <label for="contact_email">Contact Email</label>
                <input type="email" id="contact_email" name="contact_email" placeholder="contact@example.com">
            </div>
            <div class="form-group">
                <label for="contact_phone">Contact Phone</label>
                <input type="text" id="contact_phone" name="contact_phone" placeholder="+1234567890">
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('addTenantModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Tenant</button>
            </div>
        </form>
    </div>
</div>
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/sysadmin.php');
?>

