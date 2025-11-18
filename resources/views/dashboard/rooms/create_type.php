<?php
$pageTitle = 'Create Room Type | Hotela';

ob_start();
?>
<section class="card">
    <header class="booking-staff-header">
        <h2>Create New Room Type</h2>
        <a class="btn btn-outline" href="<?= base_url('dashboard/rooms/types'); ?>">Back to Room Types</a>
    </header>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert danger"><?= htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <form method="post" action="<?= base_url('dashboard/rooms/create-type'); ?>" enctype="multipart/form-data" style="margin-top: 1.5rem;">
        <div class="form-grid">
            <label>
                <span>Name *</span>
                <input type="text" name="name" required placeholder="e.g. Standard, Deluxe, Lux">
            </label>
            <label>
                <span>Max Guests *</span>
                <input type="number" name="max_guests" value="2" min="1" required>
            </label>
            <label>
                <span>Base Rate (per night) *</span>
                <input type="number" name="base_rate" min="0" step="0.01" required placeholder="3500.00">
            </label>
        </div>

        <label>
            <span>Description</span>
            <textarea name="description" rows="3" placeholder="Room type description..."></textarea>
        </label>

        <label>
            <span>Amenities (comma-separated)</span>
            <input type="text" name="amenities" placeholder="e.g. WiFi, TV, AC, Mini Bar">
            <small style="display: block; margin-top: 0.25rem; color: #64748b;">Enter amenities separated by commas</small>
        </label>

        <label>
            <span>Room Type Image</span>
            <div class="image-upload-wrapper">
                <div class="image-upload-area" id="room-type-image-upload">
                    <input type="file" name="image" id="room-type-image-input" accept="image/*" style="display: none;">
                    <div class="image-upload-content">
                        <svg class="image-upload-icon" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        <p class="image-upload-text">Click to upload or drag and drop</p>
                        <p class="image-upload-hint">PNG, JPG, GIF up to 5MB</p>
                    </div>
                    <div class="image-preview" id="room-type-image-preview" style="display: none;">
                        <img id="room-type-image-preview-img" src="" alt="Preview">
                        <button type="button" class="image-remove-btn" id="room-type-image-remove">×</button>
                    </div>
                </div>
            </div>
        </label>

        <div style="margin-top: 1.5rem; display: flex; gap: 0.5rem;">
            <button class="btn btn-primary" type="submit">Create Room Type</button>
            <a class="btn btn-outline" href="<?= base_url('dashboard/rooms/types'); ?>">Cancel</a>
        </div>
    </form>
</section>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');

