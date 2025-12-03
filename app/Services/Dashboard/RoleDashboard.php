<?php

namespace App\Services\Dashboard;

use App\Repositories\DashboardRepository;
use App\Repositories\RoomRepository;
use App\Services\Inventory\InventoryService;
use App\Services\Notifications\NotificationService;
use App\Support\Auth;

class RoleDashboard
{
    protected NotificationService $notifications;
    protected RoomRepository $rooms;
    protected InventoryService $inventory;
    protected DashboardRepository $metrics;

    public function __construct()
    {
        $this->notifications = new NotificationService();
        $this->rooms = new RoomRepository();
        $this->inventory = new InventoryService();
        $this->metrics = new DashboardRepository();
    }
    public function config(string $role): array
    {
        $roles = config('roles', []);

        if (!isset($roles[$role])) {
            $role = 'director';
        }

        return $roles[$role];
    }

    public function view(string $role): string
    {
        $config = $this->config($role);

        return $config['dashboard_view'] ?? 'dashboard/roles/director';
    }

    public function data(string $role): array
    {
        return match ($role) {
            'director' => $this->directorData(),
            'admin' => $this->adminData(),
            'tech' => $this->techData(),
            'finance_manager' => $this->financeData(),
            'operation_manager' => $this->operationsData(),
            'cashier' => $this->cashierData(),
            'service_agent' => $this->serviceData(),
            'kitchen' => $this->kitchenData(),
            'housekeeping' => $this->housekeepingData(),
            'ground' => $this->groundData(),
            'security' => $this->securityData(),
            default => $this->directorData(),
        };
    }

    protected function currency(float $amount, int $decimals = 0): string
    {
        return format_currency($amount, null, $decimals);
    }

    protected function alertsFromNotifications(string $role, int $limit = 5): array
    {
        $notifications = $this->metrics->notificationsForRole($role, $limit);

        if (!$notifications) {
            return ['All clear.'];
        }

        return array_map(
            fn(array $note) => trim(($note['title'] ?? 'Notice') . ' — ' . ($note['message'] ?? '')),
            $notifications
        );
    }

    protected function techData(): array
    {
        $alerts = $this->alertsFromNotifications('tech');
        return [
            'kpis' => [
                ['label' => 'System Uptime', 'value' => '99.98%', 'trend' => 'Last 30 days'],
                ['label' => 'Pending Backups', 'value' => '2 jobs', 'trend' => 'Next run 02:00'],
                ['label' => 'Errors (24h)', 'value' => '3 critical', 'trend' => '-1 vs avg'],
            ],
            'alerts' => $alerts,
            'actions' => [
                'Review application error logs',
                'Kick off manual backup before maintenance',
            ],
        ];
    }

    protected function directorData(): array
    {
        $snapshot = $this->metrics->occupancySnapshot();
        $revenue = $this->metrics->revenueSummary();
        $alerts = $this->alertsFromNotifications('director');
        $arrivals = $this->metrics->arrivals(10);
        $departures = $this->metrics->departures(10);
        $pos = $this->metrics->posSalesSummary();
        $outstanding = $this->metrics->outstandingBalance();
        $pendingPayments = $this->metrics->pendingPayments(5);
        $pendingPaymentsTotal = $this->metrics->pendingPaymentsTotal();
        $pendingPaymentsCount = $this->metrics->pendingPaymentsCount();
        $lowStock = $this->metrics->lowStockItems(5);
        $housekeeping = $this->metrics->housekeepingQueue(5);
        $activeUsers = $this->metrics->activeUsers();
        $unreadNotifications = $this->metrics->unreadNotificationsCount('director');
        
        // Get booking statistics
        $bookingStats = $this->getBookingStatistics();
        
        // Get recent transactions
        $recentTransactions = $this->getRecentTransactions(10);
        
        // Get revenue trends
        $revenueTrend = $this->getRevenueTrend();

        return [
            'kpis' => [
                ['label' => 'Today\'s Revenue', 'value' => $this->currency($revenue['today'] + $pos['today_total']), 'trend' => 'Bookings + POS', 'icon' => 'revenue'],
                ['label' => 'Monthly Revenue', 'value' => $this->currency($revenue['month'] + $pos['month_total']), 'trend' => 'This month', 'icon' => 'calendar'],
                ['label' => 'Occupancy Rate', 'value' => $snapshot['occupancy_percent'] . '%', 'trend' => sprintf('%d / %d rooms', $snapshot['occupied'], $snapshot['total']), 'icon' => 'rooms'],
                ['label' => 'RevPAR', 'value' => $this->currency($revenue['revpar']), 'trend' => 'Today', 'icon' => 'chart'],
                ['label' => 'ADR', 'value' => $this->currency($revenue['adr']), 'trend' => 'Average daily rate', 'icon' => 'money'],
                ['label' => 'Outstanding Balance', 'value' => $this->currency($outstanding), 'trend' => 'Total amount owed', 'icon' => 'alert'],
                ['label' => 'Pending Payments', 'value' => $this->currency($pendingPaymentsTotal), 'trend' => sprintf('%d open folio(s)', $pendingPaymentsCount), 'icon' => 'payment'],
            ],
            'stats' => [
                'total_bookings' => $bookingStats['total'],
                'confirmed_bookings' => $bookingStats['confirmed'],
                'checked_in' => $bookingStats['checked_in'],
                'pending_payments' => $pendingPaymentsCount,
                'low_stock_items' => count($lowStock),
                'rooms_need_cleaning' => $snapshot['needs_cleaning'],
                'active_staff' => $activeUsers,
                'unread_notifications' => $unreadNotifications,
            ],
            'alerts' => $alerts,
            'actions' => array_filter([
                sprintf('%d arrivals through next 48h', count($arrivals)),
                sprintf('Outstanding folios %s', $this->currency($outstanding)),
                sprintf('POS revenue today %s', $this->currency($pos['today_total'])),
                count($lowStock) > 0 ? sprintf('%d items need restocking', count($lowStock)) : null,
                $snapshot['needs_cleaning'] > 0 ? sprintf('%d rooms need cleaning', $snapshot['needs_cleaning']) : null,
            ]),
            'arrivals' => $arrivals,
            'departures' => $departures,
            'pending_payments' => $pendingPayments,
            'low_stock' => $lowStock,
            'housekeeping' => $housekeeping,
            'recent_transactions' => $recentTransactions,
            'revenue_trend' => $revenueTrend,
            'pos_summary' => $pos,
        ];
    }
    
    protected function getBookingStatistics(): array
    {
        $sql = "
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                SUM(CASE WHEN status = 'checked_in' THEN 1 ELSE 0 END) as checked_in,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
            FROM reservations
            WHERE DATE(check_in) >= CURDATE() - INTERVAL 30 DAY
        ";
        
        $stmt = db()->query($sql);
        $row = $stmt->fetch() ?: [];
        
        return [
            'total' => (int)($row['total'] ?? 0),
            'confirmed' => (int)($row['confirmed'] ?? 0),
            'checked_in' => (int)($row['checked_in'] ?? 0),
            'pending' => (int)($row['pending'] ?? 0),
        ];
    }
    
    protected function getRecentTransactions(int $limit = 10): array
    {
        $limit = (int)$limit; // Ensure it's an integer
        $sql = "
            SELECT 
                'booking' as type,
                r.reference,
                r.guest_name,
                r.total_amount as amount,
                r.payment_status,
                r.created_at,
                NULL as payment_method
            FROM reservations r
            WHERE r.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            UNION ALL
            SELECT 
                'pos' as type,
                CONCAT('POS-', ps.id) as reference,
                COALESCE(res.guest_name, CONCAT('Customer #', ps.id)) as guest_name,
                ps.total as amount,
                COALESCE(ps.payment_status, 'paid') as payment_status,
                ps.created_at,
                ps.payment_type as payment_method
            FROM pos_sales ps
            LEFT JOIN reservations res ON res.id = ps.reservation_id
            WHERE ps.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY created_at DESC
            LIMIT {$limit}
        ";
        
        $stmt = db()->query($sql);
        
        return $stmt->fetchAll() ?: [];
    }
    
    protected function getRevenueTrend(): array
    {
        $sql = "
            SELECT 
                DATE(created_at) as date,
                SUM(total) as revenue
            FROM pos_sales
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ";
        
        $stmt = db()->query($sql);
        $posData = $stmt->fetchAll() ?: [];
        
        $sql = "
            SELECT 
                DATE(check_in) as date,
                SUM(total_amount) as revenue
            FROM reservations
            WHERE check_in >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                AND status IN ('confirmed', 'checked_in', 'checked_out')
            GROUP BY DATE(check_in)
            ORDER BY date ASC
        ";
        
        $stmt = db()->query($sql);
        $bookingData = $stmt->fetchAll() ?: [];
        
        // Combine data by date
        $combined = [];
        foreach ($posData as $row) {
            $date = $row['date'];
            $combined[$date] = ($combined[$date] ?? 0) + (float)$row['revenue'];
        }
        foreach ($bookingData as $row) {
            $date = $row['date'];
            $combined[$date] = ($combined[$date] ?? 0) + (float)$row['revenue'];
        }
        
        ksort($combined);
        
        return array_map(function($date, $revenue) {
            return ['date' => $date, 'revenue' => $revenue];
        }, array_keys($combined), $combined);
    }

    protected function adminData(): array
    {
        $snapshot = $this->metrics->occupancySnapshot();
        $alerts = $this->alertsFromNotifications('admin');
        $arrivals = $this->metrics->arrivals();
        $pendingBalances = $this->metrics->pendingPayments();

        return [
            'kpis' => [
                ['label' => 'Active Users', 'value' => (string)$this->metrics->activeUsers(), 'trend' => 'Tenant staff'],
                ['label' => 'Unread Alerts', 'value' => (string)$this->metrics->unreadNotificationsCount(), 'trend' => 'Across modules'],
                ['label' => 'Occupancy', 'value' => $snapshot['occupancy_percent'] . '%', 'trend' => sprintf('%d rooms busy', $snapshot['occupied'])],
            ],
            'alerts' => $alerts,
            'actions' => [
                sprintf('Prep %d arrivals today', count($arrivals)),
                sprintf('Follow up on %d pending balances', count($pendingBalances)),
                sprintf('Resolve %d low stock notices', $this->metrics->lowStockCount()),
            ],
            'arrivals' => $arrivals,
            'pending_balances' => $pendingBalances,
        ];
    }

    protected function financeData(): array
    {
        $revenue = $this->metrics->revenueSummary();
        $outstanding = $this->metrics->outstandingBalance();
        $pos = $this->metrics->posSalesSummary();
        $alerts = $this->alertsFromNotifications('finance_manager');
        $pending = $this->metrics->pendingPayments(10);
        $poPending = $this->pendingPurchaseOrdersCount();
        $movements = $this->recentInventoryMovements(5);
        
        // Get financial statistics
        $financialStats = $this->getFinancialStatistics();
        
        // Get recent payments
        $recentPayments = $this->getRecentPayments(10);
        
        // Get expenses summary
        $expensesSummary = $this->getExpensesSummary();
        
        // Get bills summary
        $billsSummary = $this->getBillsSummary();
        
        // Get payment breakdown
        $paymentBreakdown = $this->getPaymentBreakdown();
        
        // Get revenue trend
        $revenueTrend = $this->getRevenueTrend();

        return [
            'kpis' => [
                ['label' => 'Total Revenue Today', 'value' => $this->currency($revenue['today'] + $pos['today_total']), 'trend' => 'Bookings + POS', 'icon' => 'revenue'],
                ['label' => 'Monthly Revenue', 'value' => $this->currency($revenue['month'] + $pos['month_total']), 'trend' => 'This month', 'icon' => 'calendar'],
                ['label' => 'Outstanding Folios', 'value' => $this->currency($outstanding), 'trend' => sprintf('%d open', count($pending)), 'icon' => 'alert'],
                ['label' => 'POS Sales Today', 'value' => $this->currency($pos['today_total']), 'trend' => sprintf('%d tickets', $pos['today_count']), 'icon' => 'pos'],
                ['label' => 'Monthly Expenses', 'value' => $this->currency($expensesSummary['month']), 'trend' => 'This month', 'icon' => 'expense'],
                ['label' => 'Pending Bills', 'value' => $this->currency($billsSummary['pending']), 'trend' => sprintf('%d bills', $billsSummary['pending_count']), 'icon' => 'bills'],
            ],
            'stats' => [
                'total_revenue' => $revenue['today'] + $pos['today_total'],
                'monthly_revenue' => $revenue['month'] + $pos['month_total'],
                'outstanding_balance' => $outstanding,
                'pending_payments_count' => count($pending),
                'pending_po_count' => $poPending,
                'inventory_value' => $this->inventory->valuation(),
                'monthly_expenses' => $expensesSummary['month'],
                'pending_bills' => $billsSummary['pending_count'],
                'total_payments_today' => $financialStats['payments_today'],
            ],
            'alerts' => $alerts,
            'actions' => array_filter([
                sprintf('Review %d pending folios', count($pending)),
                sprintf('Monitor inventory valuation %s', $this->currency($this->inventory->valuation())),
                sprintf('Approve %d pending POs', $poPending),
                $billsSummary['pending_count'] > 0 ? sprintf('Process %d pending bills', $billsSummary['pending_count']) : null,
                $expensesSummary['pending'] > 0 ? sprintf('Review %d pending expenses', $expensesSummary['pending']) : null,
            ]),
            'inventory_value' => $this->inventory->valuation(),
            'low_stock' => $this->inventory->lowStockItems(5),
            'pending_payments' => $pending,
            'po_pending' => $poPending,
            'recent_movements' => $movements,
            'recent_payments' => $recentPayments,
            'expenses_summary' => $expensesSummary,
            'bills_summary' => $billsSummary,
            'payment_breakdown' => $paymentBreakdown,
            'revenue_trend' => $revenueTrend,
        ];
    }
    
    protected function getFinancialStatistics(): array
    {
        $sql = "
            SELECT 
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN amount ELSE 0 END) as payments_today,
                SUM(CASE WHEN YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE()) THEN amount ELSE 0 END) as payments_month
            FROM payments
            WHERE status = 'completed'
        ";
        
        $stmt = db()->query($sql);
        $row = $stmt->fetch() ?: [];
        
        return [
            'payments_today' => (float)($row['payments_today'] ?? 0),
            'payments_month' => (float)($row['payments_month'] ?? 0),
        ];
    }
    
    protected function getRecentPayments(int $limit = 10): array
    {
        $limit = (int)$limit;
        
        // Check if tables exist before joining
        $tables = [];
        try {
            db()->query("SELECT 1 FROM expenses LIMIT 1");
            $tables['expenses'] = true;
        } catch (\Exception $e) {
            $tables['expenses'] = false;
        }
        
        try {
            db()->query("SELECT 1 FROM bills LIMIT 1");
            $tables['bills'] = true;
        } catch (\Exception $e) {
            $tables['bills'] = false;
        }
        
        try {
            db()->query("SELECT 1 FROM suppliers LIMIT 1");
            $tables['suppliers'] = true;
        } catch (\Exception $e) {
            $tables['suppliers'] = false;
        }
        
        $joins = [];
        $selects = ['p.*'];
        
        if ($tables['expenses']) {
            $joins[] = "LEFT JOIN expenses e ON e.id = p.expense_id";
            $selects[] = "e.description as expense_description";
        }
        
        if ($tables['bills']) {
            $joins[] = "LEFT JOIN bills b ON b.id = p.bill_id";
            $selects[] = "b.description as bill_description";
        }
        
        if ($tables['suppliers']) {
            $joins[] = "LEFT JOIN suppliers s ON s.id = p.supplier_id";
            $selects[] = "s.name as supplier_name";
        }
        
        $joins[] = "LEFT JOIN users u ON u.id = p.processed_by";
        $selects[] = "u.name as processed_by_name";
        
        $sql = "
            SELECT " . implode(', ', $selects) . "
            FROM payments p
            " . implode(' ', $joins) . "
            WHERE p.status = 'completed'
            ORDER BY p.created_at DESC
            LIMIT {$limit}
        ";
        
        $stmt = db()->query($sql);
        return $stmt->fetchAll() ?: [];
    }
    
    protected function getExpensesSummary(): array
    {
        try {
            $sql = "
                SELECT 
                    SUM(CASE WHEN YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE()) THEN amount ELSE 0 END) as month,
                    SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count
                FROM expenses
            ";
            
            $stmt = db()->query($sql);
            $row = $stmt->fetch() ?: [];
            
            return [
                'month' => (float)($row['month'] ?? 0),
                'pending' => (float)($row['pending'] ?? 0),
                'pending_count' => (int)($row['pending_count'] ?? 0),
            ];
        } catch (\Exception $e) {
            return [
                'month' => 0,
                'pending' => 0,
                'pending_count' => 0,
            ];
        }
    }
    
    protected function getBillsSummary(): array
    {
        try {
            $sql = "
                SELECT 
                    SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                    SUM(CASE WHEN status = 'paid' AND YEAR(paid_at) = YEAR(CURDATE()) AND MONTH(paid_at) = MONTH(CURDATE()) THEN amount ELSE 0 END) as month_paid
                FROM bills
            ";
            
            $stmt = db()->query($sql);
            $row = $stmt->fetch() ?: [];
            
            return [
                'pending' => (float)($row['pending'] ?? 0),
                'pending_count' => (int)($row['pending_count'] ?? 0),
                'month_paid' => (float)($row['month_paid'] ?? 0),
            ];
        } catch (\Exception $e) {
            return [
                'pending' => 0,
                'pending_count' => 0,
                'month_paid' => 0,
            ];
        }
    }
    
    protected function getPaymentBreakdown(): array
    {
        $sql = "
            SELECT 
                payment_method,
                SUM(amount) as total,
                COUNT(*) as count
            FROM payments
            WHERE status = 'completed'
                AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY payment_method
            ORDER BY total DESC
        ";
        
        $stmt = db()->query($sql);
        return $stmt->fetchAll() ?: [];
    }

    protected function operationsData(): array
    {
        $snapshot = $this->metrics->occupancySnapshot();
        $alerts = $this->alertsFromNotifications('operation_manager');
        $arrivals = $this->metrics->arrivals();
        $departures = $this->metrics->departures();
        $queue = $this->metrics->housekeepingQueue();
        $poPending = $this->pendingPurchaseOrdersCount();
        $movements = $this->recentInventoryMovements(5);

        return [
            'kpis' => [
                ['label' => 'Rooms Occupied', 'value' => sprintf('%d / %d', $snapshot['occupied'], $snapshot['total']), 'trend' => 'Capacity'],
                ['label' => 'Housekeeping Ready', 'value' => sprintf('%d rooms', $snapshot['available']), 'trend' => 'Available stock'],
                ['label' => 'Needs Cleaning', 'value' => (string)$snapshot['needs_cleaning'], 'trend' => 'Queue'],
            ],
            'alerts' => $alerts,
            'actions' => [
                sprintf('Coordinate %d arrivals today', count($arrivals)),
                sprintf('%d departures to clear', count($departures)),
                sprintf('%d rooms need cleaning', count($queue)),
                sprintf('POs awaiting approval: %d', $poPending),
            ],
            'arrivals' => $arrivals,
            'departures' => $departures,
            'housekeeping_queue' => $queue,
            'low_stock' => $this->inventory->lowStockItems(),
            'po_pending' => $poPending,
            'recent_movements' => $movements,
        ];
    }

    protected function cashierData(): array
    {
        $arrivals = $this->metrics->arrivals(10);
        $pending = $this->metrics->pendingPayments(10);
        $notifications = $this->alertsFromNotifications('cashier');
        $pos = $this->metrics->posSalesSummary();
        $revenue = $this->metrics->revenueSummary();
        
        // Get today's sales stats
        $todayStats = $this->getTodaySalesStats();

        return [
            'shortcuts' => [
                ['label' => 'New POS Sale', 'action' => base_url('staff/dashboard/pos')],
                ['label' => 'View Payments', 'action' => base_url('staff/dashboard/payments')],
                ['label' => 'Check-In Guest', 'action' => base_url('staff/dashboard/bookings')],
                ['label' => 'Daily Sales Report', 'action' => base_url('staff/dashboard/reports/daily-sales')],
                ['label' => 'Cash Banking', 'action' => base_url('staff/dashboard/cash-banking')],
                ['label' => 'Order History', 'action' => base_url('staff/dashboard/orders')],
            ],
            'pending_payments' => array_map(function (array $row) {
                $room = $row['display_name'] ?? $row['room_number'] ?? 'Unassigned';
                return [
                    'guest' => $row['guest_name'],
                    'reference' => $row['reference'],
                    'room' => $room,
                    'balance' => $this->currency((float)$row['balance']),
                ];
            }, $pending),
            'arrivals' => array_map(function (array $row) {
                $room = $row['display_name'] ?? $row['room_number'] ?? ($row['room_type_name'] ?? 'Unassigned');
                return [
                    'guest' => $row['guest_name'],
                    'room' => $room,
                    'check_in' => $row['check_in'],
                    'reference' => $row['reference'] ?? '',
                ];
            }, $arrivals),
            'notifications' => $notifications,
            'today_stats' => $todayStats,
            'pos_summary' => $pos,
            'revenue_summary' => $revenue,
        ];
    }
    
    protected function getTodaySalesStats(): array
    {
        $sql = "
            SELECT 
                COUNT(*) as total_orders,
                SUM(total) as total_revenue,
                AVG(total) as avg_order,
                COUNT(DISTINCT user_id) as active_staff
            FROM pos_sales
            WHERE DATE(created_at) = CURDATE()
        ";
        
        try {
            $stmt = db()->query($sql);
            $row = $stmt->fetch() ?: [];
            
            return [
                'total_orders' => (int)($row['total_orders'] ?? 0),
                'total_revenue' => (float)($row['total_revenue'] ?? 0),
                'avg_order' => (float)($row['avg_order'] ?? 0),
                'active_staff' => (int)($row['active_staff'] ?? 0),
            ];
        } catch (\Exception $e) {
            return [
                'total_orders' => 0,
                'total_revenue' => 0,
                'avg_order' => 0,
                'active_staff' => 0,
            ];
        }
    }

    protected function serviceData(): array
    {
        $arrivals = $this->metrics->arrivals() ?? [];
        $alerts = $this->alertsFromNotifications('service_agent') ?? [];
        $housekeepingQueue = $this->metrics->housekeepingQueue(3) ?? [];

        return [
            'reservations' => is_array($arrivals) ? $arrivals : [],
            'room_status' => array_map(function (array $room) {
                return [
                    'room' => $room['display_name'] ?? $room['room_number'] ?? 'Room',
                    'status' => 'Needs cleaning',
                ];
            }, is_array($housekeepingQueue) ? $housekeepingQueue : []),
            'requests' => is_array($alerts) ? $alerts : [],
        ];
    }

    protected function kitchenData(): array
    {
        $orderRepo = new \App\Repositories\OrderRepository();
        $inventoryRepo = new \App\Repositories\InventoryRepository();
        
        $kitchenOrders = $orderRepo->getKitchenOrders();
        
        // Format orders for dashboard view
        $formattedOrders = [];
        foreach ($kitchenOrders as $order) {
            $items = [];
            if (!empty($order['items'])) {
                foreach ($order['items'] as $item) {
                    $items[] = sprintf('%s x%s', $item['item_name'], $item['quantity']);
                }
            }
            
            $formattedOrders[] = [
                'ticket' => $order['reference'] ?? 'N/A',
                'items' => !empty($items) ? implode(', ', $items) : 'No items',
                'status' => ucfirst($order['status'] ?? 'pending'),
                'id' => $order['id'],
                'created_at' => $order['created_at'],
                'customer_name' => $order['customer_name'] ?? $order['reservation_guest_name'] ?? 'Walk-in',
                'room_number' => $order['room_number'] ?? null,
            ];
        }
        
        return [
            'orders' => $formattedOrders,
            'inventory_alerts' => $inventoryRepo->getLowStockAlerts(10),
        ];
    }

    protected function housekeepingData(): array
    {
        $rooms = $this->rooms->housekeepingBoard();
        $tasks = [];

        foreach ($rooms as $room) {
            if ($room['status'] === 'needs_cleaning') {
                $tasks[] = sprintf('%s needs cleaning', $room['display_name'] ?? $room['room_number']);
            }
        }

        if (!$tasks) {
            $tasks[] = 'All rooms are up to date.';
        }

        return [
            'rooms' => $rooms,
            'tasks' => $tasks,
            'notifications' => $this->notifications->latestForRole('housekeeping', 5),
        ];
    }

    protected function groundData(): array
    {
        return [
            'tasks' => [
                ['task' => 'Pool pump inspection', 'status' => 'Due today'],
                ['task' => 'Garden irrigation check', 'status' => 'In progress'],
            ],
            'issues' => [
                'Generator oil change scheduled Friday',
                'Golf cart #3 battery low',
            ],
        ];
    }

    protected function securityData(): array
    {
        return [
            'attendance' => [
                ['name' => 'Peter Njoroge', 'status' => 'Clocked In 06:55'],
                ['name' => 'Joyce Achieng', 'status' => 'Clocked Out 07:05'],
            ],
            'incidents' => [
                'Minor guest dispute resolved at lobby',
                'Visitor badge #221 not returned',
            ],
            'actions' => [
                'Log night shift patrol report',
                'Verify CCTV alert near service gate',
            ],
        ];
    }

    protected function pendingPurchaseOrdersCount(): int
    {
		$sql = 'SELECT COUNT(*) FROM purchase_orders WHERE status IN (:s1, :s2)';
		$stmt = db()->prepare($sql);
		$stmt->execute([
			's1' => 'draft',
			's2' => 'sent',
		]);
		return (int)$stmt->fetchColumn();
    }

    protected function recentInventoryMovements(int $limit = 5): array
    {
        
        $sql = '
            SELECT m.type, m.quantity, m.reference, m.notes, m.created_at,
                   i.name AS item_name, l.name AS location_name
            FROM inventory_movements m
            INNER JOIN inventory_items i ON i.id = m.item_id
            INNER JOIN inventory_locations l ON l.id = m.location_id
        ';
        $params = [];
        $where = [];
        
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY m.created_at DESC LIMIT ' . (int)$limit;
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }
}


