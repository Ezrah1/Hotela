<?php
$pageTitle = 'Edit Room | Hotela';
$room = $room ?? null;
$roomTypes = $roomTypes ?? [];

if (!$room) {
    http_response_code(404);
    echo 'Room not found';
    return;
}

ob_start();
?>
<section class="card">
    <header class="booking-staff-header">
        <h2>Edit Room: <?= htmlspecialchars($room['room_number']); ?></h2>
        <a class="btn btn-outline" href="<?= base_url('dashboard/rooms'); ?>">Back to Rooms</a>
    </header>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert danger"><?= htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <form method="post" action="<?= base_url('dashboard/rooms/update'); ?>" enctype="multipart/form-data" style="margin-top: 1.5rem;">
        <input type="hidden" name="room_id" value="<?= (int)$room['id']; ?>">

        <div class="form-grid">
            <label>
                <span>Room Number *</span>
                <input type="text" name="room_number" value="<?= htmlspecialchars($room['room_number']); ?>" required>
            </label>
            <label>
                <span>Display Name</span>
                <input type="text" name="display_name" value="<?= htmlspecialchars($room['display_name'] ?? ''); ?>" placeholder="Optional display name">
            </label>
            <label>
                <span>Room Type *</span>
                <select name="room_type_id" required>
                    <?php foreach ($roomTypes as $roomType): ?>
                        <option value="<?= (int)$roomType['id']; ?>" <?= (int)$roomType['id'] === (int)$room['room_type_id'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($roomType['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Floor</span>
                <input type="text" name="floor" value="<?= htmlspecialchars($room['floor'] ?? ''); ?>" placeholder="e.g. 1, 2, Ground">
            </label>
            <label>
                <span>Status *</span>
                <select name="status" required>
                    <option value="available" <?= $room['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                    <option value="occupied" <?= $room['status'] === 'occupied' ? 'selected' : ''; ?>>Occupied</option>
                    <option value="maintenance" <?= $room['status'] === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                    <option value="blocked" <?= $room['status'] === 'blocked' ? 'selected' : ''; ?>>Blocked</option>
                </select>
            </label>
        </div>

        <label>
            <span>Room Image</span>
            <div class="image-upload-wrapper">
                <div class="image-upload-area" id="room-image-upload">
                    <input type="file" name="image" id="room-image-input" accept="image/*" style="display: none;">
                    <div class="image-upload-content">
                        <svg class="image-upload-icon" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        <p class="image-upload-text">Click to upload or drag and drop</p>
                        <p class="image-upload-hint">PNG, JPG, GIF up to 5MB</p>
                    </div>
                    <div class="image-preview" id="room-image-preview" style="display: none;">
                        <img id="room-image-preview-img" src="" alt="Preview">
                        <button type="button" class="image-remove-btn" id="room-image-remove">×</button>
                    </div>
                </div>
                <?php
                $roomImage = $room['image'] ?? null;
                $roomTypeImage = null;
                foreach ($roomTypes as $rt) {
                    if ((int)$rt['id'] === (int)$room['room_type_id']) {
                        $roomTypeImage = $rt['image'] ?? null;
                        break;
                    }
                }
                $displayImage = $roomImage ?: $roomTypeImage;
                if ($displayImage):
                ?>
                    <div class="current-image" style="margin-top: 1rem;">
                        <p style="font-size: 0.875rem; color: #64748b; margin-bottom: 0.5rem;">Current image:</p>
                        <img src="<?= asset($displayImage); ?>" alt="Current room image" class="current-image-preview">
                        <p style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.5rem;">Upload a new image to replace this one</p>
                    </div>
                <?php endif; ?>
            </div>
        </label>

        <div style="margin-top: 1.5rem; display: flex; gap: 0.5rem;">
            <button class="btn btn-primary" type="submit">Update Room</button>
            <a class="btn btn-outline" href="<?= base_url('dashboard/rooms'); ?>">Cancel</a>
        </div>
    </form>
</section>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');

