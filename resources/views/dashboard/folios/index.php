<?php
use App\Support\Auth;
$pageTitle = 'Folio Management';
$roleConfig = config('roles', [])[Auth::role()] ?? [];

ob_start();
?>
<section class="card">
    <header class="page-header">
        <div>
            <h2>Folio Management</h2>
            <p>Manage guest folios, charges, and payments</p>
        </div>
    </header>

    <?php if (!empty($_GET['success'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_GET['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <div class="stat-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 0.75rem; padding: 1.25rem;">
            <h4 style="font-size: 0.875rem; color: #64748b; margin: 0 0 0.5rem 0;">Total Folios</h4>
            <p style="font-size: 1.875rem; font-weight: 700; color: #1e293b; margin: 0;"><?= number_format($stats['total_folios']); ?></p>
        </div>
        <div class="stat-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 0.75rem; padding: 1.25rem;">
            <h4 style="font-size: 0.875rem; color: #64748b; margin: 0 0 0.5rem 0;">Open Folios</h4>
            <p style="font-size: 1.875rem; font-weight: 700; color: #3b82f6; margin: 0;"><?= number_format($stats['open_folios']); ?></p>
        </div>
        <div class="stat-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 0.75rem; padding: 1.25rem;">
            <h4 style="font-size: 0.875rem; color: #64748b; margin: 0 0 0.5rem 0;">Outstanding</h4>
            <p style="font-size: 1.875rem; font-weight: 700; color: #dc2626; margin: 0;"><?= number_format($stats['outstanding_folios']); ?></p>
        </div>
        <div class="stat-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 0.75rem; padding: 1.25rem;">
            <h4 style="font-size: 0.875rem; color: #64748b; margin: 0 0 0.5rem 0;">Total Outstanding</h4>
            <p style="font-size: 1.875rem; font-weight: 700; color: #dc2626; margin: 0;"><?= format_currency($stats['total_outstanding']); ?></p>
        </div>
    </div>

    <!-- Filters -->
    <form method="get" action="<?= base_url('staff/dashboard/folios'); ?>" class="filters-form" style="margin-bottom: 2rem; padding: 1.5rem; background: #f8fafc; border-radius: 0.75rem;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
            <label>
                <span>Search</span>
                <input type="text" name="search" value="<?= htmlspecialchars($filters['search'] ?? ''); ?>" placeholder="Reference, guest name, email, phone...">
            </label>
            <label>
                <span>Status</span>
                <select name="status">
                    <option value="">All Status</option>
                    <option value="open" <?= ($filters['status'] ?? '') === 'open' ? 'selected' : ''; ?>>Open</option>
                    <option value="closed" <?= ($filters['status'] ?? '') === 'closed' ? 'selected' : ''; ?>>Closed</option>
                </select>
            </label>
            <label>
                <span>Guest Email</span>
                <input type="email" name="guest_email" value="<?= htmlspecialchars($filters['guest_email'] ?? ''); ?>" placeholder="guest@example.com">
            </label>
            <label>
                <span>Guest Phone</span>
                <input type="tel" name="guest_phone" value="<?= htmlspecialchars($filters['guest_phone'] ?? ''); ?>" placeholder="254700000000">
            </label>
            <label>
                <span>Start Date</span>
                <input type="date" name="start_date" value="<?= htmlspecialchars($filters['start_date'] ?? ''); ?>">
            </label>
            <label>
                <span>End Date</span>
                <input type="date" name="end_date" value="<?= htmlspecialchars($filters['end_date'] ?? ''); ?>">
            </label>
            <label>
                <span>Limit</span>
                <select name="limit">
                    <option value="25" <?= ($filters['limit'] ?? 50) == 25 ? 'selected' : ''; ?>>25</option>
                    <option value="50" <?= ($filters['limit'] ?? 50) == 50 ? 'selected' : ''; ?>>50</option>
                    <option value="100" <?= ($filters['limit'] ?? 50) == 100 ? 'selected' : ''; ?>>100</option>
                    <option value="200" <?= ($filters['limit'] ?? 50) == 200 ? 'selected' : ''; ?>>200</option>
                </select>
            </label>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <button type="submit" class="btn btn-primary">Apply Filters</button>
            <a href="<?= base_url('staff/dashboard/folios'); ?>" class="btn btn-outline">Reset</a>
        </div>
    </form>

    <!-- Folios Table -->
    <?php if (empty($folios)): ?>
        <div class="empty-state" style="text-align: center; padding: 3rem;">
            <p style="color: #64748b;">No folios found matching your criteria.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table-lite">
                <thead>
                <tr>
                    <th>Reference</th>
                    <th>Guest</th>
                    <th>Room</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Total Charges</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($folios as $folio): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($folio['reference'] ?? 'GUEST-' . $folio['id']); ?></strong>
                        </td>
                        <td>
                            <div>
                                <strong><?= htmlspecialchars($folio['guest_name'] ?? 'Guest'); ?></strong>
                                <?php if (!empty($folio['guest_email'])): ?>
                                    <br><small style="color: #64748b;"><?= htmlspecialchars($folio['guest_email']); ?></small>
                                <?php endif; ?>
                                <?php if (!empty($folio['guest_phone'])): ?>
                                    <br><small style="color: #64748b;"><?= htmlspecialchars($folio['guest_phone']); ?></small>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?= htmlspecialchars($folio['room_display_name'] ?? $folio['room_number'] ?? ($folio['reservation_id'] ? 'Unassigned' : 'N/A')); ?>
                        </td>
                        <td><?= $folio['check_in'] ? date('M j, Y', strtotime($folio['check_in'])) : 'N/A'; ?></td>
                        <td><?= $folio['check_out'] ? date('M j, Y', strtotime($folio['check_out'])) : 'N/A'; ?></td>
                        <td><strong><?= format_currency($folio['total'] ?? 0); ?></strong></td>
                        <td>
                            <?php if ((float)($folio['balance'] ?? 0) > 0): ?>
                                <span style="color: #dc2626; font-weight: 600;"><?= format_currency($folio['balance']); ?></span>
                            <?php else: ?>
                                <span style="color: #059669;">Settled</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-<?= ($folio['status'] ?? 'open') === 'open' ? 'primary' : 'success'; ?>">
                                <?= ucfirst($folio['status'] ?? 'open'); ?>
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.5rem;">
                                <a href="<?= base_url('staff/dashboard/folios/view?id=' . (int)$folio['id']); ?>" class="btn btn-small btn-primary">View</a>
                                <?php if (!empty($folio['reservation_id'])): ?>
                                    <a href="<?= base_url('staff/dashboard/bookings/folio?reservation_id=' . (int)$folio['reservation_id']); ?>" class="btn btn-small btn-outline">Manage</a>
                                <?php else: ?>
                                    <a href="<?= base_url('staff/dashboard/folios/view?id=' . (int)$folio['id']); ?>" class="btn btn-small btn-outline">Manage</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

