<?php
$pageTitle = 'New Task | Hotela';

ob_start();
?>
<section class="card">
    <header class="task-create-header">
        <div>
            <h2>Create New Task</h2>
            <p class="task-create-subtitle">Assign tasks to staff and track progress</p>
        </div>
        <a class="btn btn-ghost" href="<?= base_url('dashboard/tasks'); ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
            Back to Tasks
        </a>
    </header>

    <form method="post" action="#" class="task-create-form">
        <div class="form-section">
            <h3 class="section-title">Task Details</h3>
            <div class="form-grid">
                <label class="form-field-full">
                    <span>Task Title *</span>
                    <input type="text" name="title" placeholder="Enter a clear, concise task title" required>
                </label>
                <label class="form-field-full">
                    <span>Description</span>
                    <textarea name="description" rows="4" placeholder="Add details, requirements, or acceptance criteria..."></textarea>
                </label>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title">Assignment</h3>
            <div class="form-grid">
                <label>
                    <span>Department</span>
                    <select name="department" class="modern-select">
                        <option value="">Select department</option>
                        <option value="front_desk">Front Desk</option>
                        <option value="cashier">Cashier</option>
                        <option value="service">Service</option>
                        <option value="kitchen">Kitchen</option>
                        <option value="housekeeping">Housekeeping</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="security">Security</option>
                        <option value="management">Management</option>
                    </select>
                </label>
                <label class="form-field-full">
                    <span>Assign To</span>
                    <div class="assignees-container" id="assignees-container">
                        <div class="assignees-placeholder" id="assignees-placeholder">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                            <p>Select a department to see available staff</p>
                        </div>
                        <div class="assignees-list" id="assignees-list" style="display: none;"></div>
                    </div>
                    <small class="field-hint" id="assignee-hint">Choose staff members to assign this task to</small>
                </label>
                <label>
                    <span>Watchers Department</span>
                    <select name="watchers_department" id="watchers-department-select" class="modern-select">
                        <option value="">Select department</option>
                        <option value="front_desk">Front Desk</option>
                        <option value="cashier">Cashier</option>
                        <option value="service">Service</option>
                        <option value="kitchen">Kitchen</option>
                        <option value="housekeeping">Housekeeping</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="security">Security</option>
                        <option value="management">Management</option>
                    </select>
                    <small class="field-hint">Select a department to choose watchers from</small>
                </label>
                <label class="form-field-full">
                    <span>Watchers</span>
                    <div class="watchers-container" id="watchers-container">
                        <div class="watchers-placeholder" id="watchers-placeholder">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                            <p>Select a watchers department to see available staff</p>
                        </div>
                        <div class="watchers-list" id="watchers-list" style="display: none;"></div>
                    </div>
                    <small class="field-hint" id="watchers-hint">Choose staff members who should receive notifications about this task</small>
                </label>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title">Planning</h3>
            <div class="form-grid">
                <label>
                    <span>Priority</span>
                    <div class="priority-options">
                        <label class="priority-option">
                            <input type="radio" name="priority" value="low">
                            <span class="priority-label priority-low">Low</span>
                        </label>
                        <label class="priority-option">
                            <input type="radio" name="priority" value="normal" checked>
                            <span class="priority-label priority-normal">Normal</span>
                        </label>
                        <label class="priority-option">
                            <input type="radio" name="priority" value="high">
                            <span class="priority-label priority-high">High</span>
                        </label>
                        <label class="priority-option">
                            <input type="radio" name="priority" value="urgent">
                            <span class="priority-label priority-urgent">Urgent</span>
                        </label>
                    </div>
                </label>
                <label>
                    <span>Status</span>
                    <select name="status" class="modern-select">
                        <option value="open">Open</option>
                        <option value="in_progress">In Progress</option>
                        <option value="blocked">Blocked</option>
                        <option value="done">Done</option>
                    </select>
                </label>
                <label>
                    <span>Due Date</span>
                    <input type="date" name="due_on" class="modern-input">
                </label>
                <label>
                    <span>Due Time</span>
                    <input type="time" name="due_time" class="modern-input">
                </label>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title">Checklist</h3>
            <div class="checklist-container">
                <div class="checklist-input-group">
                    <input type="text" id="check-input" placeholder="Add a checklist item..." class="modern-input">
                    <button type="button" class="btn btn-outline" id="check-add">Add</button>
                </div>
                <ul id="check-list" class="checklist-items"></ul>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title">Additional Options</h3>
            <div class="form-grid">
                <label class="form-field-full">
                    <span>Attachments</span>
                    <div class="file-upload-area">
                        <input type="file" name="attachments[]" multiple id="file-input" style="display: none;">
                        <div class="file-upload-content" id="file-upload-content">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                            <p>Click to upload or drag and drop</p>
                            <small>Files will be attached to this task</small>
                        </div>
                    </div>
                </label>
                <div class="checkbox-group">
                    <label class="checkbox-option">
                        <input type="checkbox" name="notify_assignee" checked>
                        <span>Notify assignee when task is created</span>
                    </label>
                    <label class="checkbox-option">
                        <input type="checkbox" name="notify_watchers" checked>
                        <span>Notify watchers of updates</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                Create Task
            </button>
            <a class="btn btn-outline" href="<?= base_url('dashboard/tasks'); ?>">Cancel</a>
        </div>
    </form>
</section>

<style>
.task-create-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.task-create-header h2 {
    margin: 0 0 0.25rem 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--dark);
}

.task-create-subtitle {
    margin: 0;
    font-size: 0.95rem;
    color: #64748b;
}

.task-create-form {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.form-section {
    padding: 1.5rem;
    background: #f8fafc;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
}

.section-title {
    margin: 0 0 1.25rem 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--dark);
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.25rem;
}

.form-field-full {
    grid-column: 1 / -1;
}

label {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

label span {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--dark);
}

.modern-input,
.modern-select,
textarea {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 0.95rem;
    font-family: inherit;
    background: #fff;
    transition: all 0.2s ease;
}

.modern-input:focus,
.modern-select:focus,
textarea:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(138, 106, 63, 0.1);
}

.modern-select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    padding-right: 2.5rem;
}

.modern-select:disabled {
    background-color: #f1f5f9;
    color: #94a3b8;
    cursor: not-allowed;
    opacity: 0.7;
}

.watchers-container {
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    background: #fff;
    min-height: 120px;
    max-height: 300px;
    overflow-y: auto;
    transition: all 0.2s ease;
}

.watchers-container:focus-within {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(138, 106, 63, 0.1);
}

.watchers-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    text-align: center;
    color: #94a3b8;
    min-height: 120px;
}

.watchers-placeholder svg {
    margin-bottom: 0.75rem;
    opacity: 0.5;
}

.watchers-placeholder p {
    margin: 0;
    font-size: 0.875rem;
}

.watchers-list {
    padding: 0.75rem;
    display: grid;
    gap: 0.5rem;
}

.watcher-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    transition: all 0.2s ease;
    cursor: pointer;
}

.watcher-item:hover {
    background: #f1f5f9;
    border-color: var(--primary);
}

.watcher-item input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
    flex-shrink: 0;
}

.watcher-item-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.watcher-item-name {
    font-weight: 600;
    font-size: 0.95rem;
    color: var(--dark);
}

.watcher-item-role {
    font-size: 0.875rem;
    color: #64748b;
}

.watchers-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    color: #64748b;
    gap: 0.75rem;
}

.watchers-loading svg {
    animation: spin 1s linear infinite;
}

.assignees-container {
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    background: #fff;
    min-height: 120px;
    max-height: 300px;
    overflow-y: auto;
    transition: all 0.2s ease;
}

.assignees-container:focus-within {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(138, 106, 63, 0.1);
}

.assignees-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    text-align: center;
    color: #94a3b8;
    min-height: 120px;
}

.assignees-placeholder svg {
    margin-bottom: 0.75rem;
    opacity: 0.5;
}

.assignees-placeholder p {
    margin: 0;
    font-size: 0.875rem;
}

.assignees-list {
    padding: 0.75rem;
    display: grid;
    gap: 0.5rem;
}

.assignee-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    transition: all 0.2s ease;
    cursor: pointer;
}

.assignee-item:hover {
    background: #f1f5f9;
    border-color: var(--primary);
}

.assignee-item input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
    flex-shrink: 0;
}

.assignee-item-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.assignee-item-name {
    font-weight: 600;
    font-size: 0.95rem;
    color: var(--dark);
}

.assignee-item-role {
    font-size: 0.875rem;
    color: #64748b;
}

.assignees-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    color: #64748b;
    gap: 0.75rem;
}

.assignees-loading svg {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

textarea {
    resize: vertical;
    min-height: 100px;
}

.field-hint {
    font-size: 0.75rem;
    color: #64748b;
    margin-top: 0.25rem;
}

.priority-options {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.priority-option {
    flex: 1;
    min-width: 80px;
    position: relative;
}

.priority-option input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.priority-label {
    display: block;
    padding: 0.625rem 1rem;
    text-align: center;
    border: 2px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    background: #fff;
}

.priority-option input[type="radio"]:checked + .priority-label {
    border-color: var(--primary);
    background: rgba(138, 106, 63, 0.1);
    color: var(--primary);
}

.priority-low {
    color: #64748b;
}

.priority-normal {
    color: #2563eb;
}

.priority-high {
    color: #d97706;
}

.priority-urgent {
    color: #dc2626;
}

.priority-option input[type="radio"]:checked + .priority-label.priority-low {
    border-color: #64748b;
    background: #f1f5f9;
    color: #64748b;
}

.priority-option input[type="radio"]:checked + .priority-label.priority-normal {
    border-color: #2563eb;
    background: #dbeafe;
    color: #2563eb;
}

.priority-option input[type="radio"]:checked + .priority-label.priority-high {
    border-color: #d97706;
    background: #fef3c7;
    color: #d97706;
}

.priority-option input[type="radio"]:checked + .priority-label.priority-urgent {
    border-color: #dc2626;
    background: #fee2e2;
    color: #dc2626;
}

.checklist-container {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.checklist-input-group {
    display: flex;
    gap: 0.75rem;
}

.checklist-input-group .modern-input {
    flex: 1;
}

.checklist-items {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.checklist-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
}

.checklist-item input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.checklist-item-text {
    flex: 1;
    font-size: 0.95rem;
    color: var(--dark);
}

.checklist-item-remove {
    padding: 0.25rem 0.5rem;
    background: transparent;
    border: 1px solid #e2e8f0;
    border-radius: 0.25rem;
    color: #64748b;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.checklist-item-remove:hover {
    background: #fee2e2;
    border-color: #ef4444;
    color: #dc2626;
}

.file-upload-area {
    border: 2px dashed #e2e8f0;
    border-radius: 0.75rem;
    background: #fff;
    transition: all 0.2s ease;
    cursor: pointer;
}

.file-upload-area:hover {
    border-color: var(--primary);
    background: #fefefe;
}

.file-upload-content {
    padding: 2rem;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.file-upload-content svg {
    color: #94a3b8;
}

.file-upload-content p {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--dark);
}

.file-upload-content small {
    font-size: 0.875rem;
    color: #64748b;
}

.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    grid-column: 1 / -1;
}

.checkbox-option {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.checkbox-option:hover {
    border-color: var(--primary);
    background: rgba(138, 106, 63, 0.05);
}

.checkbox-option input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.checkbox-option span {
    font-size: 0.95rem;
    color: var(--dark);
    font-weight: 400;
}

.form-actions {
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
    padding-top: 1.5rem;
    border-top: 1px solid #e2e8f0;
}

.form-actions .btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

@media (max-width: 768px) {
    .task-create-header {
        flex-direction: column;
        gap: 1rem;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .priority-options {
        flex-direction: column;
    }

    .priority-option {
        min-width: auto;
    }

    .form-actions {
        flex-direction: column-reverse;
    }

    .form-actions .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
// Checklist functionality
const checkInput = document.getElementById('check-input');
const checkAdd = document.getElementById('check-add');
const checkList = document.getElementById('check-list');

function addChecklistItem(text) {
    if (!text || !text.trim()) return;
    
    const id = 'chk_' + Math.random().toString(36).slice(2, 9);
    const li = document.createElement('li');
    li.className = 'checklist-item';
    li.dataset.id = id;
    
    li.innerHTML = `
        <input type="checkbox" aria-label="Complete">
        <input type="hidden" name="checklist[]" value="${text.trim()}">
        <span class="checklist-item-text">${text.trim()}</span>
        <button type="button" class="checklist-item-remove" data-remove="${id}">Remove</button>
    `;
    
    checkList.appendChild(li);
    checkInput.value = '';
    checkInput.focus();
}

checkAdd?.addEventListener('click', () => {
    addChecklistItem(checkInput.value);
});

checkInput?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        addChecklistItem(checkInput.value);
    }
});

checkList?.addEventListener('click', (e) => {
    if (e.target && e.target.dataset.remove) {
        const li = e.target.closest('.checklist-item');
        if (li) li.remove();
    }
});

// File upload
const fileInput = document.getElementById('file-input');
const fileUploadContent = document.getElementById('file-upload-content');

fileUploadContent?.addEventListener('click', () => {
    fileInput?.click();
});

fileInput?.addEventListener('change', (e) => {
    const files = e.target.files;
    if (files && files.length > 0) {
        const fileNames = Array.from(files).map(f => f.name).join(', ');
        fileUploadContent.innerHTML = `
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="17 8 12 3 7 8"></polyline>
                <line x1="12" y1="3" x2="12" y2="15"></line>
            </svg>
            <p>${files.length} file(s) selected</p>
            <small>${fileNames}</small>
        `;
    }
});

// Department to Staff Assignment
const departmentSelect = document.querySelector('select[name="department"]');
const assigneesContainer = document.getElementById('assignees-container');
const assigneesPlaceholder = document.getElementById('assignees-placeholder');
const assigneesList = document.getElementById('assignees-list');
const assigneeHint = document.getElementById('assignee-hint');
const watchersDepartmentSelect = document.getElementById('watchers-department-select');
const watchersContainer = document.getElementById('watchers-container');
const watchersPlaceholder = document.getElementById('watchers-placeholder');
const watchersList = document.getElementById('watchers-list');
const watchersHint = document.getElementById('watchers-hint');

async function loadStaffForDepartment(department, targetType = 'assignee') {
    if (!department) {
        if (targetType === 'assignee') {
            // Reset Assign To
            assigneesPlaceholder.style.display = 'flex';
            assigneesPlaceholder.innerHTML = `
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <p>Select a department to see available staff</p>
            `;
            assigneesList.style.display = 'none';
            assigneesList.innerHTML = '';
            assigneeHint.textContent = 'Choose staff members to assign this task to';
        } else {
            // Reset Watchers
            watchersPlaceholder.style.display = 'flex';
            watchersPlaceholder.innerHTML = `
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <p>Select a watchers department to see available staff</p>
            `;
            watchersList.style.display = 'none';
            watchersList.innerHTML = '';
            watchersHint.textContent = 'Choose staff members who should receive notifications about this task';
        }
        return;
    }

    if (targetType === 'assignee') {
        // Show loading state for Assign To
        assigneesPlaceholder.style.display = 'none';
        assigneesList.style.display = 'block';
        assigneesList.innerHTML = `
            <div class="assignees-loading">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="2" x2="12" y2="6"></line>
                    <line x1="12" y1="18" x2="12" y2="22"></line>
                    <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line>
                    <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line>
                    <line x1="2" y1="12" x2="6" y2="12"></line>
                    <line x1="18" y1="12" x2="22" y2="12"></line>
                    <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line>
                    <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line>
                </svg>
                <span>Loading staff members...</span>
            </div>
        `;
        assigneeHint.textContent = 'Loading staff members...';
    } else {
        // Show loading state for Watchers
        watchersPlaceholder.style.display = 'none';
        watchersList.style.display = 'block';
        watchersList.innerHTML = `
            <div class="watchers-loading">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="2" x2="12" y2="6"></line>
                    <line x1="12" y1="18" x2="12" y2="22"></line>
                    <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line>
                    <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line>
                    <line x1="2" y1="12" x2="6" y2="12"></line>
                    <line x1="18" y1="12" x2="22" y2="12"></line>
                    <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line>
                    <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line>
                </svg>
                <span>Loading staff members...</span>
            </div>
        `;
        watchersHint.textContent = 'Loading staff members...';
    }

    try {
        const response = await fetch(`<?= base_url('dashboard/tasks/staff-by-department'); ?>?department=${encodeURIComponent(department)}`);
        const data = await response.json();
        
        if (data.staff && data.staff.length > 0) {
            if (targetType === 'assignee') {
                // Populate Assign To with checkboxes
                assigneesList.innerHTML = '';
                data.staff.forEach(staff => {
                    const assigneeItem = document.createElement('label');
                    assigneeItem.className = 'assignee-item';
                    assigneeItem.innerHTML = `
                        <input type="checkbox" name="assignees[]" value="${staff.id}">
                        <div class="assignee-item-info">
                            <span class="assignee-item-name">${staff.name}</span>
                            <span class="assignee-item-role">${staff.role_name || staff.role_key}</span>
                        </div>
                    `;
                    assigneesList.appendChild(assigneeItem);
                });
                assigneesPlaceholder.style.display = 'none';
                assigneesList.style.display = 'grid';
                assigneeHint.textContent = `Select staff members to assign. ${data.staff.length} available.`;
            } else {
                // Populate Watchers with checkboxes
                watchersList.innerHTML = '';
                data.staff.forEach(staff => {
                    const watcherItem = document.createElement('label');
                    watcherItem.className = 'watcher-item';
                    watcherItem.innerHTML = `
                        <input type="checkbox" name="watchers[]" value="${staff.id}">
                        <div class="watcher-item-info">
                            <span class="watcher-item-name">${staff.name}</span>
                            <span class="watcher-item-role">${staff.role_name || staff.role_key}</span>
                        </div>
                    `;
                    watchersList.appendChild(watcherItem);
                });
                watchersPlaceholder.style.display = 'none';
                watchersList.style.display = 'grid';
                watchersHint.textContent = `Select staff members to notify. ${data.staff.length} available.`;
            }
        } else {
            if (targetType === 'assignee') {
                assigneesPlaceholder.style.display = 'flex';
                assigneesPlaceholder.innerHTML = `
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <p>No staff members found in this department</p>
                `;
                assigneesList.style.display = 'none';
                assigneesList.innerHTML = '';
                assigneeHint.textContent = 'No active staff members found for this department';
            } else {
                watchersPlaceholder.style.display = 'flex';
                watchersPlaceholder.innerHTML = `
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <p>No staff members found in this department</p>
                `;
                watchersList.style.display = 'none';
                watchersList.innerHTML = '';
                watchersHint.textContent = 'No active staff members found for this department';
            }
        }
    } catch (error) {
        console.error('Error loading staff:', error);
        if (targetType === 'assignee') {
            assigneesPlaceholder.style.display = 'flex';
            assigneesPlaceholder.innerHTML = `
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <p>Error loading staff. Please try again.</p>
            `;
            assigneesList.style.display = 'none';
            assigneesList.innerHTML = '';
            assigneeHint.textContent = 'Error loading staff. Please try again.';
        } else {
            watchersPlaceholder.style.display = 'flex';
            watchersPlaceholder.innerHTML = `
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <p>Error loading staff. Please try again.</p>
            `;
            watchersList.style.display = 'none';
            watchersList.innerHTML = '';
            watchersHint.textContent = 'Error loading staff. Please try again.';
        }
    }
}

// Handle main department change (for Assign To)
departmentSelect?.addEventListener('change', function() {
    loadStaffForDepartment(this.value, 'assignee');
});

// Handle watchers department change
watchersDepartmentSelect?.addEventListener('change', function() {
    loadStaffForDepartment(this.value, 'watchers');
});
</script>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>
