<?php
$pageTitle = 'Orders Management | Hotela';
$orders = $orders ?? [];
$filters = $filters ?? [];
$statusCounts = $statusCounts ?? [];

ob_start();
?>
<section class="card">
    <header class="orders-header">
        <div>
            <h2>Orders Management</h2>
            <p class="orders-subtitle">Manage all orders from website, POS, room service, and front desk</p>
        </div>
        <div class="orders-actions">
            <button type="button" class="btn btn-outline" id="refresh-orders">Refresh</button>
        </div>
    </header>

    <!-- Time Period Quick Filters -->
    <div class="time-period-filters">
        <button type="button" class="time-period-btn" data-period="custom" id="custom-period-btn">Custom</button>
        <button type="button" class="time-period-btn" data-period="today">Today</button>
        <button type="button" class="time-period-btn" data-period="week">This Week</button>
        <button type="button" class="time-period-btn" data-period="month">This Month</button>
        <button type="button" class="time-period-btn" data-period="year">This Year</button>
        <button type="button" class="time-period-btn" data-period="all">All Time</button>
    </div>

    <!-- Filters & Search Bar -->
    <div class="orders-filters-bar">
        <form method="get" action="<?= base_url('staff/dashboard/orders'); ?>" class="filters-form" id="orders-filter-form">
            <!-- Search -->
            <div class="filter-group">
                <input type="text" name="search" placeholder="Search by ID, name, room, phone..." 
                       value="<?= htmlspecialchars($filters['search'] ?? ''); ?>" 
                       class="filter-input">
            </div>

            <!-- Status Filter -->
            <div class="filter-group">
                <select name="status" class="filter-select">
                    <option value="">All Statuses</option>
                    <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="confirmed" <?= ($filters['status'] ?? '') === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="preparing" <?= ($filters['status'] ?? '') === 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                    <option value="ready" <?= ($filters['status'] ?? '') === 'ready' ? 'selected' : ''; ?>>Ready</option>
                    <option value="delivered" <?= ($filters['status'] ?? '') === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                    <option value="completed" <?= ($filters['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?= ($filters['status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>

            <!-- Order Type Filter -->
            <div class="filter-group">
                <select name="order_type" class="filter-select">
                    <option value="">All Types</option>
                    <option value="room_service" <?= ($filters['order_type'] ?? '') === 'room_service' ? 'selected' : ''; ?>>Room Service</option>
                    <option value="restaurant" <?= ($filters['order_type'] ?? '') === 'restaurant' ? 'selected' : ''; ?>>Restaurant</option>
                    <option value="takeaway" <?= ($filters['order_type'] ?? '') === 'takeaway' ? 'selected' : ''; ?>>Takeaway</option>
                    <option value="website_delivery" <?= ($filters['order_type'] ?? '') === 'website_delivery' ? 'selected' : ''; ?>>Website Delivery</option>
                    <option value="pos_order" <?= ($filters['order_type'] ?? '') === 'pos_order' ? 'selected' : ''; ?>>POS Order</option>
                </select>
            </div>

            <!-- Date Range -->
            <div class="filter-group">
                <input type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from'] ?? ''); ?>" 
                       class="filter-input" placeholder="From">
            </div>
            <div class="filter-group">
                <input type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to'] ?? ''); ?>" 
                       class="filter-input" placeholder="To">
            </div>

            <button type="submit" class="btn btn-primary">Apply Filters</button>
            <button type="button" class="btn btn-outline" id="clear-filters">Clear</button>
        </form>
    </div>

    <!-- Status Counts -->
    <?php if (!empty($statusCounts)): ?>
        <div class="status-counts">
            <?php foreach ($statusCounts as $status => $count): ?>
                <div class="status-badge status-<?= htmlspecialchars($status); ?>">
                    <span class="status-label"><?= ucfirst($status); ?></span>
                    <span class="status-count"><?= $count; ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert danger"><?= htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <div id="orders-container">
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <svg class="empty-icon" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M9 12l2 2 4-4"></path>
                    <path d="M21 12c-1 0-3-1-3-3s2-3 3-3 3 1 3 3-2 3-3 3"></path>
                    <path d="M3 12c1 0 3-1 3-3s-2-3-3-3-3 1-3 3 2 3 3 3"></path>
                </svg>
                <h3>No orders found</h3>
                <p>There are no orders matching your filters.</p>
            </div>
        <?php else: ?>
            <div class="orders-list">
            <?php foreach ($orders as $order): ?>
                <?php include view_path('dashboard/orders/_order-card.php'); ?>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>


<style>
/* ... existing styles ... */
.time-period-filters {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
    padding: 0.75rem;
    background: #f8fafc;
    border-radius: 0.5rem;
    border: 1px solid #e5e7eb;
}

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

.orders-filters-bar {
    background: #f8fafc;
    padding: 1.5rem;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
}

.filters-form {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    align-items: flex-end;
}

.filter-group {
    flex: 1;
    min-width: 150px;
}

.filter-input, .filter-select {
    width: 100%;
    padding: 0.625rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 0.95rem;
}

.status-counts {
    display: flex;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.status-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
}

.status-badge.status-pending { background: #fef3c7; color: #d97706; }
.status-badge.status-confirmed { background: #dbeafe; color: #2563eb; }
.status-badge.status-preparing { background: #e0e7ff; color: #6366f1; }
.status-badge.status-ready { background: #dcfce7; color: #16a34a; }
.status-badge.status-delivered { background: #cffafe; color: #0891b2; }
.status-badge.status-completed { background: #d1fae5; color: #059669; }
.status-badge.status-cancelled { background: #fee2e2; color: #dc2626; }

.order-age,
.status-duration {
    font-size: 0.875rem;
    color: #64748b;
    font-weight: 500;
}

.status-duration {
    color: #3b82f6;
    background: #eff6ff;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.8rem;
}

.order-card[data-status="pending"] .status-duration {
    color: #d97706;
    background: #fef3c7;
}

.order-card[data-status="preparing"] .status-duration {
    color: #6366f1;
    background: #e0e7ff;
}

.order-card[data-status="ready"] .status-duration {
    color: #16a34a;
    background: #dcfce7;
}

.order-type-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    margin-left: 0.5rem;
    text-transform: uppercase;
}

.order-status-section {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.5rem;
}

.order-status-badge {
    padding: 0.375rem 0.75rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.quick-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.btn-quick-action {
    padding: 0.5rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    background: white;
    color: #475569;
    transition: all 0.2s;
    white-space: nowrap;
}

.btn-quick-action:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    transform: translateY(-1px);
}

.btn-quick-action:active {
    transform: translateY(0);
}

.btn-quick-action[data-status="confirmed"] {
    background: #dbeafe;
    color: #2563eb;
    border-color: #93c5fd;
}

.btn-quick-action[data-status="confirmed"]:hover {
    background: #bfdbfe;
}

.btn-quick-action[data-status="preparing"] {
    background: #e0e7ff;
    color: #6366f1;
    border-color: #a5b4fc;
}

.btn-quick-action[data-status="preparing"]:hover {
    background: #c7d2fe;
}

.btn-quick-action[data-status="ready"] {
    background: #dcfce7;
    color: #16a34a;
    border-color: #86efac;
}

.btn-quick-action[data-status="ready"]:hover {
    background: #bbf7d0;
}

.btn-quick-action[data-status="delivered"] {
    background: #cffafe;
    color: #0891b2;
    border-color: #67e8f9;
}

.btn-quick-action[data-status="delivered"]:hover {
    background: #a5f3fc;
}

.btn-quick-action[data-status="completed"] {
    background: #d1fae5;
    color: #059669;
    border-color: #6ee7b7;
}

.btn-quick-action[data-status="completed"]:hover {
    background: #a7f3d0;
}

.btn-quick-action.btn-cancel {
    background: #fee2e2;
    color: #dc2626;
    border-color: #fca5a5;
}

.btn-quick-action.btn-cancel:hover {
    background: #fecaca;
}

</style>

<script>
// Real-time polling and filtering
let currentFilters = {};
let activeTimePeriod = null;
let autoRefreshInterval;

// Initialize current filters from URL or form
function initFilters() {
    const urlParams = new URLSearchParams(window.location.search);
    currentFilters = {
        status: urlParams.get('status') || '',
        order_type: urlParams.get('order_type') || '',
        date_from: urlParams.get('date_from') || '',
        date_to: urlParams.get('date_to') || '',
        search: urlParams.get('search') || ''
    };
    
    // Check if a time period is active
    if (currentFilters.date_from && currentFilters.date_to) {
        // Format dates in local time (not UTC)
        const formatLocalDate = (date) => {
            const d = new Date(date);
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };
        
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
        
        if (currentFilters.date_from === today && currentFilters.date_to === today) {
            activeTimePeriod = 'today';
        } else if (currentFilters.date_from === weekAgo && currentFilters.date_to === today) {
            activeTimePeriod = 'week';
        } else if (currentFilters.date_from === monthAgo && currentFilters.date_to === today) {
            activeTimePeriod = 'month';
        } else if (currentFilters.date_from === yearAgo && currentFilters.date_to === today) {
            activeTimePeriod = 'year';
        } else {
            // Custom date range
            activeTimePeriod = 'custom';
        }
    } else if (!currentFilters.date_from && !currentFilters.date_to) {
        activeTimePeriod = 'all';
    }
    
    // Update button states
    document.querySelectorAll('.time-period-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.period === activeTimePeriod);
    });
}

// Set time period filters
function setTimePeriod(period) {
    if (period === 'custom') {
        // For custom, just focus on date inputs and highlight the button
        activeTimePeriod = 'custom';
        document.querySelectorAll('.time-period-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.period === 'custom');
        });
        // Focus on date_from input
        const dateFromInput = document.querySelector('input[name="date_from"]');
        if (dateFromInput) {
            dateFromInput.focus();
            dateFromInput.click();
        }
        return;
    }
    
    activeTimePeriod = period;
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    // Format date as YYYY-MM-DD in local time (not UTC)
    const formatLocalDate = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };
    
    let dateFrom = '';
    let dateTo = formatLocalDate(today);
    
    switch(period) {
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
    
    // Update form fields
    const dateFromInput = document.querySelector('input[name="date_from"]');
    const dateToInput = document.querySelector('input[name="date_to"]');
    if (dateFromInput) dateFromInput.value = dateFrom;
    if (dateToInput) dateToInput.value = dateTo;
    
    // Update current filters
    currentFilters.date_from = dateFrom;
    currentFilters.date_to = dateTo;
    
    // Update button states
    document.querySelectorAll('.time-period-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.period === period);
    });
    
    // Load orders
    loadOrders();
}

// Load orders via AJAX
function loadOrders() {
    // Use currentFilters or get from form
    const form = document.getElementById('orders-filter-form');
    if (form) {
        const formData = new FormData(form);
        // Update currentFilters from form
        currentFilters = {
            status: formData.get('status') || '',
            order_type: formData.get('order_type') || '',
            date_from: formData.get('date_from') || '',
            date_to: formData.get('date_to') || '',
            search: formData.get('search') || ''
        };
    }
    
    const params = new URLSearchParams();
    
    // Build params from currentFilters
    if (currentFilters.status) params.append('status', currentFilters.status);
    if (currentFilters.order_type) params.append('order_type', currentFilters.order_type);
    if (currentFilters.date_from) params.append('date_from', currentFilters.date_from);
    if (currentFilters.date_to) params.append('date_to', currentFilters.date_to);
    if (currentFilters.search) params.append('search', currentFilters.search);
    
    // Show loading state
    const container = document.getElementById('orders-container');
    if (!container) return;
    
    container.style.opacity = '0.6';
    container.style.pointerEvents = 'none';
    
    // Build URL
    const baseUrl = '<?= base_url('staff/dashboard/orders'); ?>';
    const queryString = params.toString();
    // Add cache-busting parameter to ensure fresh data
    const cacheBuster = '&_t=' + Date.now();
    const fullUrl = baseUrl + (queryString ? '?' + queryString + '&ajax=1' + cacheBuster : '?ajax=1' + cacheBuster);
    
    // Update URL without reload
    window.history.pushState({}, '', baseUrl + (queryString ? '?' + queryString : ''));
    
    // Fetch orders with no-cache headers
    fetch(fullUrl, {
        method: 'GET',
        cache: 'no-cache',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(r => {
            if (!r.ok) {
                throw new Error('Network response was not ok');
            }
            return r.json();
        })
        .then(data => {
            if (data.success) {
                // Update orders container
                container.innerHTML = data.orders_html || '<div class="empty-state"><h3>No orders found</h3></div>';
                
                // Update status counts
                if (data.status_counts_html) {
                    const statusCountsContainer = document.querySelector('.status-counts');
                    if (statusCountsContainer) {
                        statusCountsContainer.outerHTML = data.status_counts_html;
                    } else {
                        // Insert before orders container
                        const statusCountsDiv = document.createElement('div');
                        statusCountsDiv.innerHTML = data.status_counts_html;
                        container.parentNode.insertBefore(statusCountsDiv.firstElementChild, container);
                    }
                }
                
                // Re-initialize quick action buttons
                initQuickActions();
            }
            
            // Restore UI state
            container.style.opacity = '1';
            container.style.pointerEvents = 'auto';
        })
        .catch(err => {
            console.error('Error loading orders:', err);
            container.style.opacity = '1';
            container.style.pointerEvents = 'auto';
        });
}

// Initialize quick action buttons
function initQuickActions() {
    document.querySelectorAll('.btn-quick-action').forEach(button => {
        // Remove existing listeners by cloning
        const newButton = button.cloneNode(true);
        button.parentNode.replaceChild(newButton, button);
        
        newButton.addEventListener('click', function() {
            const orderId = this.dataset.orderId;
            const status = this.dataset.status;
            
            if (!status || !orderId) return;
            
            // Disable button during processing
            this.disabled = true;
            this.style.opacity = '0.6';
            
            if (status === 'cancelled') {
                const reason = prompt('Please provide cancellation reason:');
                if (!reason) {
                    this.disabled = false;
                    this.style.opacity = '1';
                    return;
                }
                updateOrderStatus(orderId, status, reason);
            } else {
                updateOrderStatus(orderId, status);
            }
        });
    });
}


function updateOrderStatus(orderId, status, notes = '') {
    fetch('<?= base_url('staff/dashboard/orders/update-status'); ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `order_id=${orderId}&status=${status}&notes=${encodeURIComponent(notes)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Reload orders via AJAX
            loadOrders();
        } else {
            alert('Error: ' + (data.message || 'Failed to update order status'));
        }
    })
    .catch(err => {
        console.error('Update error:', err);
        alert('Error updating order status. Please try again.');
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initFilters();
    initQuickActions();
    
    // Time period buttons
    document.querySelectorAll('.time-period-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            setTimePeriod(this.dataset.period);
        });
    });
    
    // Form submission - prevent default and use AJAX
    document.getElementById('orders-filter-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        // Clear active time period when manually filtering
        activeTimePeriod = null;
        document.querySelectorAll('.time-period-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        // Update current filters from form
        const formData = new FormData(this);
        currentFilters = {
            status: formData.get('status') || '',
            order_type: formData.get('order_type') || '',
            date_from: formData.get('date_from') || '',
            date_to: formData.get('date_to') || '',
            search: formData.get('search') || ''
        };
        loadOrders();
    });
    
    // Clear filters button
    document.getElementById('clear-filters')?.addEventListener('click', function() {
        document.getElementById('orders-filter-form').reset();
        activeTimePeriod = null;
        document.querySelectorAll('.time-period-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        currentFilters = {};
        window.location.href = '<?= base_url('staff/dashboard/orders'); ?>';
    });
    
    // Date inputs - set custom period when manually changed
    document.querySelectorAll('input[name="date_from"], input[name="date_to"]').forEach(input => {
        input.addEventListener('change', function() {
            // If both dates are set, activate custom period
            const dateFrom = document.querySelector('input[name="date_from"]').value;
            const dateTo = document.querySelector('input[name="date_to"]').value;
            if (dateFrom && dateTo) {
                activeTimePeriod = 'custom';
                document.querySelectorAll('.time-period-btn').forEach(btn => {
                    btn.classList.toggle('active', btn.dataset.period === 'custom');
                });
            } else if (!dateFrom && !dateTo) {
                activeTimePeriod = 'all';
                document.querySelectorAll('.time-period-btn').forEach(btn => {
                    btn.classList.toggle('active', btn.dataset.period === 'all');
                });
            } else {
                activeTimePeriod = null;
                document.querySelectorAll('.time-period-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
            }
        });
    });
    
    // Refresh button
    document.getElementById('refresh-orders')?.addEventListener('click', () => {
        loadOrders();
    });
    
    // Auto-refresh orders every 10 seconds using AJAX
    autoRefreshInterval = setInterval(() => {
        if (!document.hidden) {
            loadOrders();
        }
    }, 10000);
    
    // Also start auto-refresh immediately after a short delay to catch any updates
    setTimeout(() => {
        loadOrders();
    }, 2000);
    
    // Log that auto-refresh is active (for debugging)
    console.log('Orders auto-refresh active - refreshing every 10 seconds');
});
</script>

<?php
// Helper function for next statuses
function getNextStatuses($currentStatus) {
    $workflow = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['preparing', 'cancelled'],
        'preparing' => ['ready', 'cancelled'],
        'ready' => ['delivered', 'cancelled'],
        'delivered' => ['completed'],
    ];
    return $workflow[$currentStatus] ?? [];
}
$this->getNextStatuses = function($status) {
    return getNextStatuses($status);
};
?>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>
