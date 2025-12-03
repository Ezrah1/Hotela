<?php
$pageTitle = 'Booking Dashboard | Hotela';
$reservations = $reservations ?? [];
$filter = $filter ?? 'upcoming';
$filters = $filters ?? ['start' => date('Y-m-01'), 'end' => date('Y-m-d')];

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

    <!-- Time Period Quick Filters -->
    <div class="time-period-filters" style="display:flex;gap:0.5rem;margin-bottom:1rem;padding:0.75rem;background:#f8fafc;border-radius:0.5rem;border:1px solid #e5e7eb;">
        <button type="button" class="time-period-btn" data-period="custom">Custom</button>
        <button type="button" class="time-period-btn" data-period="today">Today</button>
        <button type="button" class="time-period-btn" data-period="week">This Week</button>
        <button type="button" class="time-period-btn" data-period="month">This Month</button>
        <button type="button" class="time-period-btn" data-period="year">This Year</button>
        <button type="button" class="time-period-btn" data-period="all">All Time</button>
    </div>

    <form method="get" action="<?= base_url('staff/dashboard/bookings'); ?>" id="report-filter-form" class="filter-grid" style="margin-bottom:1.5rem;padding:0.75rem;background:#f8fafc;border-radius:0.5rem;">
        <input type="hidden" name="filter" id="filter-input" value="<?= htmlspecialchars($filter); ?>">
        <label>
            <span>Start Date</span>
            <input type="date" name="start" id="date-start" value="<?= htmlspecialchars($filters['start']); ?>" class="modern-input">
        </label>
        <label>
            <span>End Date</span>
            <input type="date" name="end" id="date-end" value="<?= htmlspecialchars($filters['end']); ?>" class="modern-input">
        </label>
        <div class="filter-actions" style="display:flex;gap:0.5rem;align-items:flex-end;">
            <button class="btn btn-primary" type="submit">Apply Filters</button>
            <button type="button" class="btn btn-outline" id="clear-filters">Clear</button>
        </div>
    </form>
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
            } elseif (strpos($_GET['success'], 'cancelled') !== false) {
                echo 'Booking cancelled successfully.';
            }
            ?>
        </div>
    <?php endif; ?>
    <div class="table-responsive">
        <table class="table-lite data-table">
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
                    <td data-label="Reference"><?= htmlspecialchars($reservation['reference']); ?></td>
                    <td data-label="Guest"><?= htmlspecialchars($reservation['guest_name']); ?></td>
                    <td data-label="Room">
                        <?php if ($reservation['room_number']): ?>
                            <?= htmlspecialchars($reservation['room_number']); ?>
                        <?php else: ?>
                            <?= htmlspecialchars($reservation['room_type_name']); ?>
                        <?php endif; ?>
                    </td>
                    <td data-label="Dates">
                        <?= htmlspecialchars($reservation['check_in']); ?> →
                        <?= htmlspecialchars($reservation['check_out']); ?>
                    </td>
                    <td data-label="Status">
                        <span class="status status-<?= htmlspecialchars($reservation['status']); ?>"><?= ucfirst(htmlspecialchars($reservation['status'])); ?></span>
                        <?php if ($reservation['check_in_status'] === 'checked_in'): ?>
                            <span class="status status-checked_in" style="margin-left: 0.5rem;">In House</span>
                        <?php endif; ?>
                    </td>
                    <td data-label="Actions" class="booking-actions action-buttons">
                        <a class="btn btn-outline btn-small" href="<?= base_url('staff/dashboard/bookings/edit?reservation_id=' . (int)$reservation['id']); ?>">Edit</a>
                        <?php if ($reservation['status'] !== 'cancelled' && $reservation['status'] !== 'checked_out' && $reservation['check_in_status'] !== 'checked_in'): ?>
                            <button class="btn btn-outline btn-small" style="color: #dc2626; border-color: #dc2626;" onclick="cancelBooking(<?= (int)$reservation['id']; ?>, '<?= htmlspecialchars($reservation['reference'], ENT_QUOTES); ?>')">Cancel</button>
                        <?php endif; ?>
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
    </div>
    <?php if (empty($reservations)): ?>
        <p class="text-center muted" style="padding: 2rem;">No reservations found.</p>
    <?php endif; ?>
</section>
<script>
let activeTimePeriod = null;

function formatLocalDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function setTimePeriod(period) {
    activeTimePeriod = period;
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    let dateFrom = '';
    let dateTo = formatLocalDate(today);
    
    switch(period) {
        case 'custom':
            document.getElementById('date-start')?.focus();
            activeTimePeriod = 'custom';
            document.querySelectorAll('.time-period-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.period === 'custom');
            });
            return;
        case 'today':
            dateFrom = dateTo;
            break;
        case 'week':
            const weekAgo = new Date(today);
            weekAgo.setDate(today.getDate() - 7);
            dateFrom = formatLocalDate(weekAgo);
            break;
        case 'month':
            const monthAgo = new Date(today);
            monthAgo.setMonth(today.getMonth() - 1);
            dateFrom = formatLocalDate(monthAgo);
            break;
        case 'year':
            const yearAgo = new Date(today);
            yearAgo.setFullYear(today.getFullYear() - 1);
            dateFrom = formatLocalDate(yearAgo);
            break;
        case 'all':
            dateFrom = '';
            dateTo = '';
            break;
    }
    
    document.getElementById('date-start').value = dateFrom;
    document.getElementById('date-end').value = dateTo;
    
    document.querySelectorAll('.time-period-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.period === period);
    });
    
    document.getElementById('report-filter-form').submit();
}

function initFilters() {
    const urlParams = new URLSearchParams(window.location.search);
    const dateFrom = urlParams.get('start') || '';
    const dateTo = urlParams.get('end') || '';
    
    if (dateFrom && dateTo) {
        const today = formatLocalDate(new Date());
        const weekAgoDate = new Date();
        weekAgoDate.setDate(weekAgoDate.getDate() - 7);
        const weekAgo = formatLocalDate(weekAgoDate);
        const monthAgoDate = new Date();
        monthAgoDate.setMonth(monthAgoDate.getMonth() - 1);
        const monthAgo = formatLocalDate(monthAgoDate);
        const yearAgoDate = new Date();
        yearAgoDate.setFullYear(yearAgoDate.getFullYear() - 1);
        const yearAgo = formatLocalDate(yearAgoDate);
        
        if (dateFrom === today && dateTo === today) {
            activeTimePeriod = 'today';
        } else if (dateFrom === weekAgo && dateTo === today) {
            activeTimePeriod = 'week';
        } else if (dateFrom === monthAgo && dateTo === today) {
            activeTimePeriod = 'month';
        } else if (dateFrom === yearAgo && dateTo === today) {
            activeTimePeriod = 'year';
        } else {
            activeTimePeriod = 'custom';
        }
    } else if (!dateFrom && !dateTo) {
        activeTimePeriod = 'all';
    }
    
    document.querySelectorAll('.time-period-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.period === activeTimePeriod);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    initFilters();
    
    // Status filter
    document.getElementById('filter-select').addEventListener('change', function() {
        document.getElementById('filter-input').value = this.value;
        document.getElementById('report-filter-form').submit();
    });
    
    // Time period buttons
    document.querySelectorAll('.time-period-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            setTimePeriod(this.dataset.period);
        });
    });
    
    // Clear filters
    document.getElementById('clear-filters')?.addEventListener('click', function() {
        window.location.href = '<?= base_url('staff/dashboard/bookings'); ?>';
    });
    
    // Date inputs - set custom when manually changed
    document.getElementById('date-start')?.addEventListener('change', function() {
        activeTimePeriod = null;
        document.querySelectorAll('.time-period-btn').forEach(btn => {
            btn.classList.remove('active');
        });
    });
    document.getElementById('date-end')?.addEventListener('change', function() {
        activeTimePeriod = null;
        document.querySelectorAll('.time-period-btn').forEach(btn => {
            btn.classList.remove('active');
        });
    });
});

function cancelBooking(reservationId, reference) {
    const reason = prompt('Please provide a reason for cancelling booking ' + reference + ':');
    if (!reason || reason.trim() === '') {
        alert('Cancellation reason is required.');
        return;
    }
    
    if (!confirm('Are you sure you want to cancel booking ' + reference + '? This action cannot be undone.')) {
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= base_url('staff/dashboard/bookings/cancel'); ?>';
    
    const reservationIdInput = document.createElement('input');
    reservationIdInput.type = 'hidden';
    reservationIdInput.name = 'reservation_id';
    reservationIdInput.value = reservationId;
    form.appendChild(reservationIdInput);
    
    const reasonInput = document.createElement('input');
    reasonInput.type = 'hidden';
    reasonInput.name = 'reason';
    reasonInput.value = reason.trim();
    form.appendChild(reasonInput);
    
    document.body.appendChild(form);
    form.submit();
}
</script>

<style>
.time-period-btn {
    padding: 0.5rem 1rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    background: white;
    color: #374151;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.time-period-btn:hover {
    background: #f3f4f6;
    border-color: #9ca3af;
}

.time-period-btn.active {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

.modern-input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 0.95rem;
    transition: all 0.2s ease;
    font-family: inherit;
}

.modern-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(138, 106, 63, 0.1);
}
</style>
<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');

