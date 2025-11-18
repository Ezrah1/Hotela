<?php
$pageTitle = 'Rooms Management | Hotela';
$rooms = $rooms ?? [];
$roomTypes = $roomTypes ?? [];
$filter = $filter ?? 'all';
$selectedRoomTypeId = $selectedRoomTypeId ?? null;

ob_start();
?>
<section class="card">
    <header class="booking-staff-header">
        <h2>Rooms Management</h2>
        <div class="booking-header-actions">
            <select id="filter-select" class="btn btn-outline" style="padding: 0.75rem 1.5rem; font-weight: 600; cursor: pointer;">
                <option value="all" <?= $filter === 'all' ? 'selected' : ''; ?>>All Rooms</option>
                <option value="available" <?= $filter === 'available' ? 'selected' : ''; ?>>Available</option>
                <option value="occupied" <?= $filter === 'occupied' ? 'selected' : ''; ?>>Occupied</option>
                <option value="maintenance" <?= $filter === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                <option value="blocked" <?= $filter === 'blocked' ? 'selected' : ''; ?>>Blocked</option>
            </select>
            <select id="room-type-select" class="btn btn-outline" style="padding: 0.75rem 1.5rem; font-weight: 600; cursor: pointer;">
                <option value="">All Room Types</option>
                <?php foreach ($roomTypes as $roomType): ?>
                    <option value="<?= (int)$roomType['id']; ?>" <?= (int)$roomType['id'] === $selectedRoomTypeId ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($roomType['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php
            $user = \App\Support\Auth::user();
            $canEdit = in_array($user['role'] ?? '', ['admin', 'operation_manager'], true);
            if ($canEdit):
            ?>
                <a class="btn btn-outline" href="<?= base_url('dashboard/rooms/select-edit'); ?>">Edit Room</a>
                <a class="btn btn-outline" href="<?= base_url('dashboard/rooms/types'); ?>">Room Types</a>
            <?php endif; ?>
        </div>
    </header>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert danger"><?= htmlspecialchars($_GET['error']); ?></div>
    <?php elseif (!empty($_GET['success'])): ?>
        <div class="alert success">
            <?php
            if ($_GET['success'] === 'updated') {
                echo 'Room status updated successfully.';
            } elseif ($_GET['success'] === 'room_updated') {
                echo 'Room updated successfully.';
            }
            ?>
        </div>
    <?php endif; ?>

    <table class="table-lite" style="margin-top: 1.5rem;">
        <thead>
        <tr>
            <th>Room Number</th>
            <th>Display Name</th>
            <th>Room Type</th>
            <th>Floor</th>
            <th>Status</th>
            <th>Current Guest</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($rooms as $room): ?>
            <tr>
                <td><strong><?= htmlspecialchars($room['room_number']); ?></strong></td>
                <td><?= htmlspecialchars($room['display_name'] ?? '-'); ?></td>
                <td><?= htmlspecialchars($room['room_type_name']); ?></td>
                <td><?= htmlspecialchars($room['floor'] ?? '-'); ?></td>
                <td>
                    <span class="status status-<?= htmlspecialchars($room['status']); ?>">
                        <?= ucfirst(htmlspecialchars($room['status'])); ?>
                    </span>
                </td>
                <td>
                    <?php if (!empty($room['current_reservation'])): ?>
                        <div>
                            <strong><?= htmlspecialchars($room['current_reservation']['guest_name']); ?></strong>
                            <br>
                            <small>
                                <?= htmlspecialchars($room['current_reservation']['check_in']); ?> → 
                                <?= htmlspecialchars($room['current_reservation']['check_out']); ?>
                            </small>
                            <br>
                            <small style="color: #64748b;">
                                <?= htmlspecialchars($room['current_reservation']['reference']); ?>
                            </small>
                        </div>
                    <?php else: ?>
                        <span class="muted">No guest</span>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="post" action="<?= base_url('dashboard/rooms/update-status'); ?>" style="display: inline;">
                        <input type="hidden" name="room_id" value="<?= (int)$room['id']; ?>">
                        <select name="status" onchange="this.form.submit()" style="padding: 0.35rem 0.5rem; border: 1px solid #cbd5f5; border-radius: 0.375rem;">
                            <option value="available" <?= $room['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                            <option value="occupied" <?= $room['status'] === 'occupied' ? 'selected' : ''; ?>>Occupied</option>
                            <option value="maintenance" <?= $room['status'] === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                            <option value="blocked" <?= $room['status'] === 'blocked' ? 'selected' : ''; ?>>Blocked</option>
                        </select>
                    </form>
                    <?php if (!empty($room['current_reservation'])): ?>
                        <a class="btn btn-outline btn-small" href="<?= base_url('dashboard/bookings/folio?reservation_id=' . (int)$room['current_reservation']['id']); ?>" style="margin-left: 0.5rem;">View Booking</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (empty($rooms)): ?>
        <p class="text-center muted" style="padding: 2rem;">No rooms found.</p>
    <?php endif; ?>
</section>

<script>
document.getElementById('filter-select').addEventListener('change', function() {
    const filter = this.value;
    const roomType = document.getElementById('room-type-select').value;
    const url = new URL(window.location.href);
    if (filter && filter !== 'all') {
        url.searchParams.set('filter', filter);
    } else {
        url.searchParams.delete('filter');
    }
    if (roomType) {
        url.searchParams.set('room_type_id', roomType);
    } else {
        url.searchParams.delete('room_type_id');
    }
    window.location.href = url.toString();
});

document.getElementById('room-type-select').addEventListener('change', function() {
    const roomType = this.value;
    const filter = document.getElementById('filter-select').value;
    const url = new URL(window.location.href);
    if (roomType) {
        url.searchParams.set('room_type_id', roomType);
    } else {
        url.searchParams.delete('room_type_id');
    }
    if (filter && filter !== 'all') {
        url.searchParams.set('filter', filter);
    } else {
        url.searchParams.delete('filter');
    }
    window.location.href = url.toString();
});
</script>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');

