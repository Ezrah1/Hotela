<?php
$pageTitle = 'Create Announcement | Hotela';
$users = $users ?? [];

$error = $_GET['error'] ?? null;

ob_start();
?>
<section class="card">
    <header class="create-header">
        <h2>Create Announcement</h2>
        <a href="<?= base_url('staff/dashboard/announcements'); ?>" class="btn btn-outline">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Back to Announcements
        </a>
    </header>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <?= htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= base_url('staff/dashboard/announcements/create'); ?>" class="announcement-form">
        <div class="form-group">
            <label>
                <span>Title</span>
                <input type="text" name="title" class="modern-input" required placeholder="Enter announcement title...">
            </label>
        </div>

        <div class="form-group">
            <label>
                <span>Content</span>
                <textarea name="content" class="modern-textarea" rows="12" required placeholder="Enter announcement content..."></textarea>
            </label>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>
                    <span>Target Audience</span>
                    <select name="target_audience" id="target_audience" class="modern-select" required>
                        <option value="all">All Users</option>
                        <option value="roles">Specific Roles</option>
                        <option value="users">Specific Users</option>
                    </select>
                </label>
            </div>

            <div class="form-group">
                <label>
                    <span>Priority</span>
                    <select name="priority" class="modern-select" required>
                        <option value="low">Low</option>
                        <option value="normal" selected>Normal</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </label>
            </div>
        </div>

        <div class="form-group" id="roles_group" style="display: none;">
            <label>
                <span>Select Roles</span>
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="target_roles[]" value="admin">
                        <span>Admin</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="target_roles[]" value="finance_manager">
                        <span>Finance Manager</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="target_roles[]" value="operation_manager">
                        <span>Operation Manager</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="target_roles[]" value="receptionist">
                        <span>Receptionist</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="target_roles[]" value="cashier">
                        <span>Cashier</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="target_roles[]" value="service_agent">
                        <span>Service Agent</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="target_roles[]" value="kitchen">
                        <span>Kitchen</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="target_roles[]" value="housekeeping">
                        <span>Housekeeping</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="target_roles[]" value="ground">
                        <span>Ground/Maintenance</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="target_roles[]" value="security">
                        <span>Security</span>
                    </label>
                </div>
            </label>
        </div>

        <div class="form-group" id="users_group" style="display: none;">
            <label>
                <span>Select Users</span>
                <select name="target_users[]" id="target_users" class="modern-select" multiple size="8">
                    <?php foreach ($users as $user): ?>
                        <option value="<?= $user['id']; ?>">
                            <?= htmlspecialchars($user['name']); ?> (<?= htmlspecialchars($user['email']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <small>Hold Ctrl (Cmd on Mac) to select multiple users</small>
            </label>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>
                    <span>Publish Date (Optional)</span>
                    <input type="datetime-local" name="publish_at" class="modern-input">
                </label>
            </div>

            <div class="form-group">
                <label>
                    <span>Expiry Date (Optional)</span>
                    <input type="datetime-local" name="expires_at" class="modern-input">
                </label>
            </div>
        </div>

        <div class="form-group">
            <label>
                <span>Status</span>
                <select name="status" class="modern-select" required>
                    <option value="draft">Draft</option>
                    <option value="published" selected>Published</option>
                </select>
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                Create Announcement
            </button>
            <a href="<?= base_url('staff/dashboard/announcements'); ?>" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</section>

<script>
document.getElementById('target_audience').addEventListener('change', function() {
    const audience = this.value;
    const rolesGroup = document.getElementById('roles_group');
    const usersGroup = document.getElementById('users_group');
    
    if (audience === 'roles') {
        rolesGroup.style.display = 'block';
        usersGroup.style.display = 'none';
    } else if (audience === 'users') {
        rolesGroup.style.display = 'none';
        usersGroup.style.display = 'block';
    } else {
        rolesGroup.style.display = 'none';
        usersGroup.style.display = 'none';
    }
});
</script>

<style>
.create-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    flex-wrap: wrap;
    gap: 1rem;
}

.create-header h2 {
    margin: 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--dark);
}

.announcement-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group label span {
    display: block;
    font-weight: 600;
    color: #475569;
    font-size: 0.95rem;
    margin-bottom: 0.5rem;
}

.modern-select,
.modern-input,
.modern-textarea {
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

.modern-textarea {
    resize: vertical;
    min-height: 300px;
}

.modern-select:focus,
.modern-input:focus,
.modern-textarea:focus {
    outline: none;
    border-color: #8b5cf6;
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
}

.checkbox-group {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.75rem;
    padding: 1rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
    font-weight: 500;
    color: #475569;
}

.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.form-group small {
    color: #64748b;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.form-actions {
    display: flex;
    gap: 0.75rem;
    padding-top: 1rem;
    border-top: 1px solid #e2e8f0;
}

.alert {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
    font-size: 0.95rem;
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.alert svg {
    flex-shrink: 0;
}

@media (max-width: 768px) {
    .create-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .form-row {
        grid-template-columns: 1fr;
    }

    .form-actions {
        flex-direction: column;
    }

    .form-actions .btn {
        width: 100%;
    }
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

