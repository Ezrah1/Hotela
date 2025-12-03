<?php
$pageTitle = 'Booking Calendar | Hotela';
$reservations = $reservations ?? [];
$availableRooms = $availableRooms ?? [];
$start = $range['start'];
$end = $range['end'];

function dateRangeArray($start, $end) {
    $dates = [];
    $current = strtotime($start);
    $endTs = strtotime($end);
    while ($current <= $endTs) {
        $dates[] = date('Y-m-d', $current);
        $current = strtotime('+1 day', $current);
    }
    return $dates;
}

$dates = dateRangeArray($start, $end);

ob_start();
?>
<section class="card">
    <header class="booking-staff-header">
        <div>
            <h2>Assignment Calendar</h2>
            <p><?= htmlspecialchars($start); ?> → <?= htmlspecialchars($end); ?></p>
        </div>
        <a class="btn btn-outline" href="<?= base_url('staff/dashboard/bookings'); ?>">Back to bookings</a>
    </header>
    <?php if (!empty($_GET['error'])): ?>
        <div class="alert danger"><?= htmlspecialchars($_GET['error']); ?></div>
    <?php elseif (!empty($_GET['success'])): ?>
        <div class="alert success">Room assigned successfully.</div>
    <?php endif; ?>
    <table class="table-lite calendar-table">
        <thead>
        <tr>
            <th>Reservation</th>
            <?php foreach ($dates as $date): ?>
                <th><?= date('M d', strtotime($date)); ?></th>
            <?php endforeach; ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($reservations as $reservation): ?>
            <tr>
                <td>
                    <strong><?= htmlspecialchars($reservation['guest_name']); ?></strong><br>
                    <?= htmlspecialchars($reservation['reference']); ?><br>
                    Room: <?= htmlspecialchars($reservation['room_number'] ?? 'Unassigned'); ?>
                </td>
                <?php foreach ($dates as $date): ?>
                    <?php
                    $inRange = $reservation['check_in'] <= $date && $reservation['check_out'] > $date;
                    ?>
                    <td class="<?= $inRange ? 'calendar-slot-active' : ''; ?>">
                        <?php if ($inRange && !$reservation['room_number']): ?>
                            <form method="post" action="<?= base_url('staff/dashboard/bookings/assign-room'); ?>">
                                <input type="hidden" name="reservation_id" value="<?= (int)$reservation['id']; ?>">
                                <select name="room_id">
                                    <?php foreach ($availableRooms as $room): ?>
                                        <option value="<?= (int)$room['id']; ?>">
                                            <?= htmlspecialchars($room['display_name'] ?? $room['room_number']); ?> (<?= htmlspecialchars($room['room_type_name'] ?? 'Room Type'); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-small btn-primary" type="submit">Assign</button>
                            </form>
                        <?php elseif ($inRange): ?>
                            <?= htmlspecialchars($reservation['room_number']); ?>
                        <?php endif; ?>
                    </td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');

