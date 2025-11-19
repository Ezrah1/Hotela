<?php
$pageTitle = 'Select Room to Edit | Hotela';
$rooms = $rooms ?? [];
$roomTypes = $roomTypes ?? [];

ob_start();
?>
<section class="card">
    <header class="booking-staff-header">
        <h2>Select Room to Edit</h2>
        <a class="btn btn-outline" href="<?= base_url('staff/dashboard/rooms'); ?>">Back to Rooms</a>
    </header>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert danger"><?= htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <div style="margin-top: 1.5rem;">
        <label>
            <span>Select a room to edit:</span>
            <select id="room-select" class="btn btn-outline" style="padding: 0.75rem 1.5rem; font-weight: 600; cursor: pointer; width: 100%; max-width: 500px; margin-top: 0.5rem;">
                <option value="">Choose a room...</option>
                <?php foreach ($rooms as $room): ?>
                    <option value="<?= (int)$room['id']; ?>">
                        <?= htmlspecialchars($room['room_number']); ?>
                        <?php if ($room['display_name']): ?>
                            - <?= htmlspecialchars($room['display_name']); ?>
                        <?php endif; ?>
                        (<?= htmlspecialchars($room['room_type_name']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <div style="margin-top: 1rem;">
            <button id="edit-room-btn" class="btn btn-primary" disabled>Edit Selected Room</button>
        </div>
    </div>
</section>

<script>
document.getElementById('room-select').addEventListener('change', function() {
    const roomId = this.value;
    const editBtn = document.getElementById('edit-room-btn');
    if (roomId) {
        editBtn.disabled = false;
        editBtn.onclick = function() {
            window.location.href = '<?= base_url('staff/dashboard/rooms/edit'); ?>?room_id=' + roomId;
        };
    } else {
        editBtn.disabled = true;
        editBtn.onclick = null;
    }
});
</script>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');

