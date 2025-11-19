<?php
$pageTitle = 'Edit Booking | Hotela';
$reservation = $reservation ?? null;
$availableRooms = $availableRooms ?? [];
$roomTypes = $roomTypes ?? [];

if (!$reservation) {
    http_response_code(404);
    echo 'Reservation not found';
    return;
}

ob_start();
?>
<section class="card">
    <header class="booking-staff-header">
        <h2>Edit Booking: <?= htmlspecialchars($reservation['reference']); ?></h2>
        <a class="btn btn-outline" href="<?= base_url('staff/dashboard/bookings'); ?>">Back to Bookings</a>
    </header>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert danger"><?= htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <form method="post" action="<?= base_url('staff/dashboard/bookings/update'); ?>" style="margin-top: 1.5rem;">
        <input type="hidden" name="reservation_id" value="<?= (int)$reservation['id']; ?>">

        <div class="form-grid">
            <label>
                <span>Guest Name</span>
                <input type="text" name="guest_name" value="<?= htmlspecialchars($reservation['guest_name']); ?>" required>
            </label>
            <label>
                <span>Email</span>
                <input type="email" name="guest_email" value="<?= htmlspecialchars($reservation['guest_email'] ?? ''); ?>">
            </label>
            <label>
                <span>Phone</span>
                <input type="tel" name="guest_phone" value="<?= htmlspecialchars($reservation['guest_phone'] ?? ''); ?>">
            </label>
        </div>

        <div class="form-grid">
            <label>
                <span>Check-in Date</span>
                <input type="date" name="check_in" value="<?= htmlspecialchars($reservation['check_in']); ?>" required>
            </label>
            <label>
                <span>Check-out Date</span>
                <input type="date" name="check_out" value="<?= htmlspecialchars($reservation['check_out']); ?>" required>
            </label>
            <label>
                <span>Adults</span>
                <input type="number" name="adults" value="<?= (int)$reservation['adults']; ?>" min="1" required>
            </label>
            <label>
                <span>Children</span>
                <input type="number" name="children" value="<?= (int)$reservation['children']; ?>" min="0">
            </label>
        </div>

        <div class="form-grid">
            <label>
                <span>Room Type</span>
                <select name="room_type_id" required>
                    <?php foreach ($roomTypes as $roomType): ?>
                        <option value="<?= (int)$roomType['id']; ?>" <?= (int)$roomType['id'] === (int)$reservation['room_type_id'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($roomType['name']); ?> - KES <?= number_format((float)($roomType['base_rate'] ?? 0), 2); ?>/night
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Room Assignment</span>
                <select name="room_id">
                    <option value="">No room assigned</option>
                    <?php foreach ($availableRooms as $room): ?>
                        <?php if ((int)$room['room_type_id'] === (int)$reservation['room_type_id']): ?>
                            <option value="<?= (int)$room['id']; ?>" <?= (int)$room['id'] === (int)($reservation['room_id'] ?? 0) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($room['display_name'] ?? $room['room_number']); ?> (<?= htmlspecialchars($room['room_type_name']); ?>)
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                <small style="display: block; margin-top: 0.25rem; color: #64748b;">
                    Only rooms matching the selected room type are shown. Change dates to see availability.
                </small>
            </label>
        </div>

        <div class="form-grid">
            <label>
                <span>Status</span>
                <select name="status">
                    <option value="pending" <?= $reservation['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="confirmed" <?= $reservation['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="checked_in" <?= $reservation['status'] === 'checked_in' ? 'selected' : ''; ?>>Checked In</option>
                    <option value="checked_out" <?= $reservation['status'] === 'checked_out' ? 'selected' : ''; ?>>Checked Out</option>
                    <option value="cancelled" <?= $reservation['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </label>
        </div>

        <label>
            <span>Notes</span>
            <textarea name="notes" rows="3" placeholder="Special requests, notes..."><?= htmlspecialchars($reservation['notes'] ?? ''); ?></textarea>
        </label>

        <div style="margin-top: 1.5rem; display: flex; gap: 0.5rem;">
            <button class="btn btn-primary" type="submit">Update Booking</button>
            <a class="btn btn-outline" href="<?= base_url('staff/dashboard/bookings'); ?>">Cancel</a>
        </div>
    </form>
</section>

<script>
document.querySelector('input[name="check_in"], input[name="check_out"], select[name="room_type_id"]').addEventListener('change', function() {
    // When dates or room type change, we should reload available rooms
    // For now, just show a message that user should refresh
    const checkIn = document.querySelector('input[name="check_in"]').value;
    const checkOut = document.querySelector('input[name="check_out"]').value;
    const roomTypeId = document.querySelector('select[name="room_type_id"]').value;
    
    if (checkIn && checkOut && roomTypeId) {
        // Filter room dropdown to show only matching room type
        const roomSelect = document.querySelector('select[name="room_id"]');
        const options = roomSelect.querySelectorAll('option');
        options.forEach(opt => {
            if (opt.value === '') return;
            const roomTypeMatch = opt.textContent.includes(roomTypeId);
            // Simple check - in production, you'd want to reload from server
        });
    }
});
</script>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');

