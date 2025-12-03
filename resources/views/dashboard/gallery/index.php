<?php
$pageTitle = $pageTitle ?? 'Gallery Management';
$roleConfig = config('roles.admin');
$success = $_GET['success'] ?? null;
$error = $_GET['error'] ?? null;
ob_start();
?>

<div class="dashboard-slot">
    <div class="dashboard-header">
        <div class="header-left">
            <div class="header-breadcrumb">
                <h1>Gallery Management</h1>
                <p class="header-subtitle">Manage gallery images and stories</p>
            </div>
        </div>
        <div class="header-right">
            <a href="<?= base_url('staff/dashboard/gallery/create'); ?>" class="btn btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Add Gallery Item
            </a>
        </div>
    </div>

    <div class="dashboard-content">
        <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom: 1.5rem; padding: 0.75rem 1rem; background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; border-radius: 0.5rem;">
                <?= htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error" style="margin-bottom: 1.5rem; padding: 0.75rem 1rem; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; border-radius: 0.5rem;">
                <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($items)): ?>
            <div class="card" style="text-align: center; padding: 3rem 2rem;">
                <p style="color: #64748b; margin-bottom: 1.5rem;">No gallery items yet.</p>
                <a href="<?= base_url('staff/dashboard/gallery/create'); ?>" class="btn btn-primary">Add Your First Gallery Item</a>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Order</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <img src="<?= htmlspecialchars($item['image_url']); ?>" 
                                             alt="<?= htmlspecialchars($item['title']); ?>"
                                             style="width: 80px; height: 60px; object-fit: cover; border-radius: 4px;">
                                    </td>
                                    <td><strong><?= htmlspecialchars($item['title']); ?></strong></td>
                                    <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?= htmlspecialchars($item['description'] ?? ''); ?>
                                    </td>
                                    <td><?= (int)$item['display_order']; ?></td>
                                    <td>
                                        <span class="badge <?= $item['status'] === 'published' ? 'badge-success' : 'badge-secondary'; ?>">
                                            <?= ucfirst($item['status']); ?>
                                        </span>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($item['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="<?= base_url('staff/dashboard/gallery/edit?id=' . (int)$item['id']); ?>" class="btn btn-outline btn-small">
                                                Edit
                                            </a>
                                            <form method="post" action="<?= base_url('staff/dashboard/gallery/delete'); ?>" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this gallery item?');">
                                                <input type="hidden" name="id" value="<?= (int)$item['id']; ?>">
                                                <button type="submit" class="btn btn-outline btn-small btn-danger">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>
