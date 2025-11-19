<?php
$pageTitle = 'Room Types Management | Hotela';
$roomTypes = $roomTypes ?? [];

ob_start();
?>
<section class="card">
    <header class="booking-staff-header">
        <h2>Room Types</h2>
        <div class="booking-header-actions">
            <form method="post" action="<?= base_url('staff/dashboard/rooms/replace-types'); ?>" style="display: inline;" onsubmit="return confirm('This will replace all current room types with Standard, Deluxe, and Lux. All rooms and reservations will be updated. Continue?');">
                <button class="btn btn-outline" type="submit">Replace with Standard Types</button>
            </form>
            <a class="btn btn-outline" href="<?= base_url('staff/dashboard/rooms/create-type'); ?>">Create New Type</a>
            <a class="btn btn-outline" href="<?= base_url('staff/dashboard/rooms'); ?>">Back to Rooms</a>
        </div>
    </header>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert danger"><?= htmlspecialchars($_GET['error']); ?></div>
    <?php elseif (!empty($_GET['success'])): ?>
        <div class="alert success">
            <?php
            if ($_GET['success'] === 'updated') {
                echo 'Room type updated successfully.';
            } elseif ($_GET['success'] === 'created') {
                echo 'Room type created successfully.';
            } elseif ($_GET['success'] === 'deleted') {
                echo 'Room type deleted successfully.';
            } elseif ($_GET['success'] === 'replaced') {
                echo 'Room types replaced successfully. All rooms and reservations have been updated.';
            }
            ?>
        </div>
    <?php endif; ?>

    <table class="table-lite" style="margin-top: 1.5rem;">
        <thead>
        <tr>
            <th>Name</th>
            <th>Description</th>
            <th>Max Guests</th>
            <th>Base Rate (per night)</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($roomTypes as $roomType): ?>
            <tr>
                <td><strong><?= htmlspecialchars($roomType['name']); ?></strong></td>
                <td><?= htmlspecialchars($roomType['description'] ?? '-'); ?></td>
                <td><?= (int)($roomType['max_guests'] ?? 2); ?></td>
                <td>KES <?= number_format((float)($roomType['base_rate'] ?? 0), 2); ?></td>
                <td>
                    <a class="btn btn-outline btn-small" href="<?= base_url('staff/dashboard/rooms/edit-type?room_type_id=' . (int)$roomType['id']); ?>">Edit</a>
                    <form method="post" action="<?= base_url('staff/dashboard/rooms/delete-type'); ?>" style="display: inline; margin-left: 0.5rem;" onsubmit="return confirm('Are you sure you want to delete this room type? This can only be done if no rooms or reservations are using it.');">
                        <input type="hidden" name="room_type_id" value="<?= (int)$roomType['id']; ?>">
                        <button class="btn btn-outline btn-small" type="submit" style="color: #ef4444; border-color: #ef4444;">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (empty($roomTypes)): ?>
        <p class="text-center muted" style="padding: 2rem;">No room types found.</p>
    <?php endif; ?>
</section>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');

