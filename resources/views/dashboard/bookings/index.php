<?php
$pageTitle = 'Booking Dashboard | Hotela';
$reservations = $reservations ?? [];
$filter = $filter ?? 'upcoming';

ob_start();
?>
<section class="card">
    <header class="booking-staff-header">
        <h2>Reservations</h2>
        <div class="booking-header-actions">
            <select id="filter-select" class="btn btn-outline" style="padding: 0.75rem 1.5rem; font-weight: 600; cursor: pointer;">
                <option value="upcoming" <?= $filter === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                <option value="scheduled" <?= $filter === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                <option value="checked_in" <?= $filter === 'checked_in' ? 'selected' : ''; ?>>Checked In</option>
                <option value="checked_out" <?= $filter === 'checked_out' ? 'selected' : ''; ?>>Checked Out</option>
                <option value="" <?= $filter === '' ? 'selected' : ''; ?>>All</option>
            </select>
            <a class="btn btn-outline" href="<?= base_url('staff/dashboard/bookings/calendar-view'); ?>">Calendar</a>
            <a class="btn btn-outline" href="<?= base_url('booking'); ?>">Create booking</a>
        </div>
    </header>
    <?php if (!empty($_GET['error'])): ?>
        <div class="alert danger"><?= htmlspecialchars($_GET['error']); ?></div>
    <?php elseif (!empty($_GET['success'])): ?>
        <div class="alert success">
            <?php
            if ($_GET['success'] === 'checkin') {
                echo 'Guest checked in successfully.';
            } elseif ($_GET['success'] === 'checkout') {
                echo 'Guest checked out successfully.';
            } elseif ($_GET['success'] === 'updated') {
                echo 'Booking updated successfully.';
            }
            ?>
        </div>
    <?php endif; ?>
    <table class="table-lite">
        <thead>
        <tr>
            <th>Reference</th>
            <th>Guest</th>
            <th>Room</th>
            <th>Dates</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($reservations as $reservation): ?>
            <tr>
                <td><?= htmlspecialchars($reservation['reference']); ?></td>
                <td><?= htmlspecialchars($reservation['guest_name']); ?></td>
                <td>
                    <?php if ($reservation['room_number']): ?>
                        <?= htmlspecialchars($reservation['room_number']); ?>
                    <?php else: ?>
                        <?= htmlspecialchars($reservation['room_type_name']); ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?= htmlspecialchars($reservation['check_in']); ?> →
                    <?= htmlspecialchars($reservation['check_out']); ?>
                </td>
                <td>
                    <span class="status status-<?= htmlspecialchars($reservation['status']); ?>"><?= ucfirst(htmlspecialchars($reservation['status'])); ?></span>
                    <?php if ($reservation['check_in_status'] === 'checked_in'): ?>
                        <span class="status status-checked_in" style="margin-left: 0.5rem;">In House</span>
                    <?php endif; ?>
                </td>
                <td class="booking-actions">
                    <a class="btn btn-outline btn-small" href="<?= base_url('staff/dashboard/bookings/edit?reservation_id=' . (int)$reservation['id']); ?>">Edit</a>
                    <a class="btn btn-outline btn-small" href="<?= base_url('staff/dashboard/bookings/folio?reservation_id=' . (int)$reservation['id']); ?>">Folio</a>
                    <?php if ($reservation['check_in_status'] === 'scheduled'): ?>
                        <form method="post" action="<?= base_url('staff/dashboard/bookings/check-in'); ?>" style="display: inline;">
                            <input type="hidden" name="reservation_id" value="<?= (int)$reservation['id']; ?>">
                            <button class="btn btn-primary btn-small" type="submit">Check In</button>
                        </form>
                    <?php elseif ($reservation['check_in_status'] === 'checked_in'): ?>
                        <form method="post" action="<?= base_url('staff/dashboard/bookings/check-out'); ?>" style="display: inline;">
                            <input type="hidden" name="reservation_id" value="<?= (int)$reservation['id']; ?>">
                            <button class="btn btn-outline btn-small" type="submit">Check Out</button>
                        </form>
                    <?php else: ?>
                        <span class="muted">Checked Out</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (empty($reservations)): ?>
        <p class="text-center muted" style="padding: 2rem;">No reservations found.</p>
    <?php endif; ?>
</section>
<script>
document.getElementById('filter-select').addEventListener('change', function() {
    const filter = this.value;
    const url = new URL(window.location.href);
    if (filter) {
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

