<?php
$pageTitle = 'Current Guests | Hotela';
ob_start();
?>
<section class="card">
    <header class="booking-staff-header">
        <h2>Current Guests</h2>
        <p class="text-muted" style="margin-top: 0.5rem;">View all checked-in guests and upcoming arrivals</p>
    </header>

    <div class="guests-tabs" style="margin-bottom: 2rem; border-bottom: 1px solid #e2e8f0;">
        <button class="tab-btn active" data-tab="checked-in">Checked In (<?= count($checkedInGuests ?? []); ?>)</button>
        <button class="tab-btn" data-tab="arrivals-today">Arrivals Today (<?= count($todayArrivals ?? []); ?>)</button>
        <button class="tab-btn" data-tab="arrivals-tomorrow">Arrivals Tomorrow (<?= count($tomorrowArrivals ?? []); ?>)</button>
    </div>

    <!-- Checked In Guests -->
    <div class="tab-content active" id="checked-in">
        <?php if (empty($checkedInGuests)): ?>
            <div class="empty-state" style="text-align: center; padding: 3rem; color: #64748b;">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 1rem;">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <h3>No guests currently checked in</h3>
                <p>All guests have checked out or no check-ins have been processed yet.</p>
            </div>
        <?php else: ?>
            <div class="guests-table-wrapper">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Guest Name</th>
                            <th>Room</th>
                            <th>Contact</th>
                            <th>Reference</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($checkedInGuests as $guest): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, var(--primary) 0%, #a67c52 100%); display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 600; font-size: 0.875rem;">
                                            <?= strtoupper(substr($guest['guest_name'] ?? 'G', 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 600; color: var(--dark);"><?= htmlspecialchars($guest['guest_name'] ?? 'Guest'); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="room-badge"><?= htmlspecialchars($guest['display_name'] ?? $guest['room_number'] ?? 'Unassigned'); ?></span>
                                </td>
                                <td>
                                    <div style="font-size: 0.875rem;">
                                        <?php if (!empty($guest['guest_email'])): ?>
                                            <div><?= htmlspecialchars($guest['guest_email']); ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($guest['guest_phone'])): ?>
                                            <div style="color: #64748b; margin-top: 0.25rem;"><?= htmlspecialchars($guest['guest_phone']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <code style="background: #f1f5f9; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.875rem;">
                                        <?= htmlspecialchars($guest['reference'] ?? 'N/A'); ?>
                                    </code>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <a href="<?= base_url('staff/dashboard/bookings/folio?reservation_id=' . (int)$guest['id']); ?>" class="btn btn-outline btn-small" title="View Folio">
                                            Folio
                                        </a>
                                        <a href="<?= base_url('staff/dashboard/bookings/edit?reservation_id=' . (int)$guest['id']); ?>" class="btn btn-outline btn-small" title="View Details">
                                            View
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Arrivals Today -->
    <div class="tab-content" id="arrivals-today" style="display: none;">
        <?php if (empty($todayArrivals)): ?>
            <div class="empty-state" style="text-align: center; padding: 3rem; color: #64748b;">
                <h3>No arrivals scheduled for today</h3>
            </div>
        <?php else: ?>
            <div class="guests-table-wrapper">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Guest Name</th>
                            <th>Room Type</th>
                            <th>Check-in</th>
                            <th>Reference</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($todayArrivals as $arrival): ?>
                            <tr>
                                <td><?= htmlspecialchars($arrival['guest_name'] ?? 'Guest'); ?></td>
                                <td><?= htmlspecialchars($arrival['room_type_name'] ?? 'N/A'); ?></td>
                                <td><?= htmlspecialchars(date('M j, Y', strtotime($arrival['check_in']))); ?></td>
                                <td>
                                    <code style="background: #f1f5f9; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.875rem;">
                                        <?= htmlspecialchars($arrival['reference'] ?? 'N/A'); ?>
                                    </code>
                                </td>
                                <td>
                                    <a href="<?= base_url('staff/dashboard/bookings/edit?reservation_id=' . (int)$arrival['id']); ?>" class="btn btn-outline btn-small">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Arrivals Tomorrow -->
    <div class="tab-content" id="arrivals-tomorrow" style="display: none;">
        <?php if (empty($tomorrowArrivals)): ?>
            <div class="empty-state" style="text-align: center; padding: 3rem; color: #64748b;">
                <h3>No arrivals scheduled for tomorrow</h3>
            </div>
        <?php else: ?>
            <div class="guests-table-wrapper">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Guest Name</th>
                            <th>Room Type</th>
                            <th>Check-in</th>
                            <th>Reference</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tomorrowArrivals as $arrival): ?>
                            <tr>
                                <td><?= htmlspecialchars($arrival['guest_name'] ?? 'Guest'); ?></td>
                                <td><?= htmlspecialchars($arrival['room_type_name'] ?? 'N/A'); ?></td>
                                <td><?= htmlspecialchars(date('M j, Y', strtotime($arrival['check_in']))); ?></td>
                                <td>
                                    <code style="background: #f1f5f9; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.875rem;">
                                        <?= htmlspecialchars($arrival['reference'] ?? 'N/A'); ?>
                                    </code>
                                </td>
                                <td>
                                    <a href="<?= base_url('staff/dashboard/bookings/edit?reservation_id=' . (int)$arrival['id']); ?>" class="btn btn-outline btn-small">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
.booking-staff-header {
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.booking-staff-header h2 {
    margin: 0 0 0.5rem 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--dark);
}

.guests-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
}

.tab-btn {
    padding: 0.75rem 1.5rem;
    background: transparent;
    border: none;
    border-bottom: 2px solid transparent;
    color: #64748b;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.95rem;
}

.tab-btn:hover {
    color: var(--primary);
}

.tab-btn.active {
    color: var(--primary);
    border-bottom-color: var(--primary);
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.guests-table-wrapper {
    overflow-x: auto;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
}

.modern-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
}

.modern-table thead {
    background: #f8fafc;
}

.modern-table th {
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--dark);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.modern-table td {
    padding: 1rem;
    border-top: 1px solid #e2e8f0;
    font-size: 0.95rem;
}

.modern-table tbody tr:hover {
    background: #f8fafc;
}

.room-badge {
    display: inline-block;
    padding: 0.375rem 0.75rem;
    background: rgba(138, 106, 63, 0.1);
    border-radius: 0.375rem;
    color: var(--primary);
    font-weight: 600;
    font-size: 0.875rem;
}

.btn-small {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const targetTab = this.dataset.tab;

            // Remove active class from all buttons and contents
            tabButtons.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => {
                c.classList.remove('active');
                c.style.display = 'none';
            });

            // Add active class to clicked button and corresponding content
            this.classList.add('active');
            const targetContent = document.getElementById(targetTab);
            if (targetContent) {
                targetContent.classList.add('active');
                targetContent.style.display = 'block';
            }
        });
    });
});
</script>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

