<?php
$pageTitle = $pageTitle ?? ($item ? 'Edit Gallery Item' : 'Add Gallery Item');
$roleConfig = config('roles.admin');
$item = $item ?? null;
$error = $_GET['error'] ?? null;
$isEdit = $item !== null;
ob_start();
?>

<div class="dashboard-slot">
    <div class="dashboard-header">
        <div class="header-left">
            <div class="header-breadcrumb">
                <h1><?= $isEdit ? 'Edit Gallery Item' : 'Add Gallery Item'; ?></h1>
                <p class="header-subtitle"><?= $isEdit ? 'Update gallery item details' : 'Create a new gallery post with image and story'; ?></p>
            </div>
        </div>
        <div class="header-right">
            <a href="<?= base_url('staff/dashboard/gallery'); ?>" class="btn btn-outline">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
                Cancel
            </a>
        </div>
    </div>

    <div class="dashboard-content">
        <?php if ($error): ?>
            <div class="alert alert-error" style="margin-bottom: 1.5rem; padding: 0.75rem 1rem; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; border-radius: 0.5rem;">
                <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <form method="post" action="<?= base_url($isEdit ? 'staff/dashboard/gallery/update' : 'staff/dashboard/gallery/store'); ?>" enctype="multipart/form-data">
                <?php if ($isEdit): ?>
                    <input type="hidden" name="id" value="<?= (int)$item['id']; ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <label style="grid-column: 1 / -1;">
                        <span>Title *</span>
                        <input type="text" name="title" value="<?= htmlspecialchars($item['title'] ?? ''); ?>" required placeholder="Enter a descriptive title for this gallery item">
                    </label>

                    <label style="grid-column: 1 / -1;">
                        <span>Story / Description</span>
                        <textarea name="description" rows="6" placeholder="Tell the story behind this image..."><?= htmlspecialchars($item['description'] ?? ''); ?></textarea>
                        <small style="display: block; margin-top: 0.5rem; color: #64748b;">Add a compelling story or description to accompany the image.</small>
                    </label>

                    <label style="grid-column: 1 / -1;">
                        <span>Image *</span>
                        <div class="image-upload-wrapper">
                            <div class="image-upload-area" id="gallery-image-upload">
                                <input type="file" name="image" id="gallery-image-input" accept="image/*" style="display: none;">
                                <div class="image-upload-content">
                                    <svg class="image-upload-icon" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="17 8 12 3 7 8"></polyline>
                                        <line x1="12" y1="3" x2="12" y2="15"></line>
                                    </svg>
                                    <p class="image-upload-text">Click to upload or drag and drop</p>
                                    <p class="image-upload-hint">PNG, JPG, GIF up to 5MB</p>
                                </div>
                                <div class="image-preview" id="gallery-image-preview" style="display: none;">
                                    <img id="gallery-image-preview-img" src="" alt="Preview">
                                    <button type="button" class="image-remove-btn" id="gallery-image-remove">×</button>
                                </div>
                            </div>
                            <?php if ($isEdit && !empty($item['image_url'])): ?>
                                <div style="margin-top: 1rem;">
                                    <p style="margin-bottom: 0.5rem; color: #64748b; font-size: 0.875rem;">Current Image:</p>
                                    <img src="<?= htmlspecialchars($item['image_url']); ?>" alt="Current" style="max-width: 300px; height: auto; border-radius: 8px; border: 1px solid #e2e8f0;">
                                </div>
                                <div style="margin-top: 0.75rem;">
                                    <label>
                                        <span>Or enter image URL</span>
                                        <input type="url" name="image_url" value="<?= htmlspecialchars($item['image_url']); ?>" placeholder="https://example.com/image.jpg">
                                    </label>
                                </div>
                            <?php else: ?>
                                <div style="margin-top: 0.75rem;">
                                    <label>
                                        <span>Or enter image URL</span>
                                        <input type="url" name="image_url" placeholder="https://example.com/image.jpg">
                                    </label>
                                </div>
                            <?php endif; ?>
                        </div>
                    </label>

                    <label>
                        <span>Display Order</span>
                        <input type="number" name="display_order" value="<?= (int)($item['display_order'] ?? 0); ?>" min="0" placeholder="0">
                        <small style="display: block; margin-top: 0.5rem; color: #64748b;">Lower numbers appear first. Leave as 0 for default ordering.</small>
                    </label>

                    <label>
                        <span>Status</span>
                        <select name="status">
                            <option value="published" <?= ($item['status'] ?? 'published') === 'published' ? 'selected' : ''; ?>>Published</option>
                            <option value="draft" <?= ($item['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        </select>
                    </label>
                </div>

                <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: flex-end;">
                    <a href="<?= base_url('staff/dashboard/gallery'); ?>" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <?= $isEdit ? 'Update Gallery Item' : 'Create Gallery Item'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadArea = document.getElementById('gallery-image-upload');
    const fileInput = document.getElementById('gallery-image-input');
    const preview = document.getElementById('gallery-image-preview');
    const previewImg = document.getElementById('gallery-image-preview-img');
    const removeBtn = document.getElementById('gallery-image-remove');

    if (uploadArea && fileInput) {
        uploadArea.addEventListener('click', () => fileInput.click());
        
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.style.borderColor = '#8b5cf6';
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.style.borderColor = '#e2e8f0';
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.style.borderColor = '#e2e8f0';
            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                handleFileSelect(e.dataTransfer.files[0]);
            }
        });

        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleFileSelect(e.target.files[0]);
            }
        });

        if (removeBtn) {
            removeBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                fileInput.value = '';
                preview.style.display = 'none';
                uploadArea.querySelector('.image-upload-content').style.display = 'block';
            });
        }
    }

    function handleFileSelect(file) {
        if (!file.type.startsWith('image/')) {
            alert('Please select an image file');
            return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
            uploadArea.querySelector('.image-upload-content').style.display = 'none';
        };
        reader.readAsDataURL(file);
    }
});
</script>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

