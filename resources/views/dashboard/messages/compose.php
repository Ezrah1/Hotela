<?php
$pageTitle = 'Compose Message | Hotela';
$users = $users ?? [];
$recipientId = $recipientId ?? null;
$recipientRole = $recipientRole ?? null;

$error = $_GET['error'] ?? null;

ob_start();
?>
<section class="card">
    <header class="compose-header">
        <h2>Compose Message</h2>
        <a href="<?= base_url('staff/dashboard/messages'); ?>" class="btn btn-outline">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Back to Messages
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

    <form method="post" action="<?= base_url('staff/dashboard/messages/compose'); ?>" class="compose-form">
        <div class="form-group">
            <label>
                <span>Send To</span>
                <select name="recipient_type" id="recipient_type" class="modern-select" required>
                    <option value="user" <?= $recipientId ? 'selected' : ''; ?>>Specific User</option>
                    <option value="role" <?= $recipientRole ? 'selected' : ''; ?>>Role</option>
                </select>
            </label>
        </div>

        <div class="form-group" id="user_recipient_group" style="<?= $recipientRole ? 'display: none;' : ''; ?>">
            <label>
                <span>Select User</span>
                <select name="recipient_id" id="recipient_id" class="modern-select">
                    <option value="">Choose a user...</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= $user['id']; ?>" <?= $recipientId == $user['id'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($user['name']); ?> (<?= htmlspecialchars($user['email']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <div class="form-group" id="role_recipient_group" style="<?= !$recipientRole ? 'display: none;' : ''; ?>">
            <label>
                <span>Select Role</span>
                <select name="recipient_role" id="recipient_role" class="modern-select">
                    <option value="">Choose a role...</option>
                    <option value="director" <?= $recipientRole === 'director' ? 'selected' : ''; ?>>Director</option>
                    <option value="finance_manager" <?= $recipientRole === 'finance_manager' ? 'selected' : ''; ?>>Finance Manager</option>
                    <option value="operation_manager" <?= $recipientRole === 'operation_manager' ? 'selected' : ''; ?>>Operation Manager</option>
                    <option value="receptionist" <?= $recipientRole === 'receptionist' ? 'selected' : ''; ?>>Receptionist</option>
                    <option value="cashier" <?= $recipientRole === 'cashier' ? 'selected' : ''; ?>>Cashier</option>
                    <option value="service_agent" <?= $recipientRole === 'service_agent' ? 'selected' : ''; ?>>Service Agent</option>
                    <option value="kitchen" <?= $recipientRole === 'kitchen' ? 'selected' : ''; ?>>Kitchen</option>
                    <option value="housekeeping" <?= $recipientRole === 'housekeeping' ? 'selected' : ''; ?>>Housekeeping</option>
                    <option value="ground" <?= $recipientRole === 'ground' ? 'selected' : ''; ?>>Ground/Maintenance</option>
                    <option value="security" <?= $recipientRole === 'security' ? 'selected' : ''; ?>>Security</option>
                </select>
            </label>
        </div>

        <div class="form-group">
            <label>
                <span>Subject</span>
                <input type="text" name="subject" class="modern-input" required placeholder="Enter message subject...">
            </label>
        </div>

        <div class="form-group">
            <label>
                <span>Message</span>
                <textarea name="message" class="modern-textarea" rows="10" required placeholder="Type your message here..."></textarea>
            </label>
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_important" value="1">
                <span>Mark as Important</span>
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="22" y1="2" x2="11" y2="13"></line>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                </svg>
                Send Message
            </button>
            <a href="<?= base_url('staff/dashboard/messages'); ?>" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</section>

<script>
document.getElementById('recipient_type').addEventListener('change', function() {
    const type = this.value;
    const userGroup = document.getElementById('user_recipient_group');
    const roleGroup = document.getElementById('role_recipient_group');
    
    if (type === 'user') {
        userGroup.style.display = 'block';
        roleGroup.style.display = 'none';
        document.getElementById('recipient_role').required = false;
        document.getElementById('recipient_id').required = true;
    } else {
        userGroup.style.display = 'none';
        roleGroup.style.display = 'block';
        document.getElementById('recipient_id').required = false;
        document.getElementById('recipient_role').required = true;
    }
});
</script>

<style>
.compose-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    flex-wrap: wrap;
    gap: 1rem;
}

.compose-header h2 {
    margin: 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--dark);
}

.compose-form {
    display: flex;
    flex-direction: column;
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
    min-height: 200px;
}

.modern-select:focus,
.modern-input:focus,
.modern-textarea:focus {
    outline: none;
    border-color: #8b5cf6;
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
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
    .compose-header {
        flex-direction: column;
        align-items: flex-start;
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

