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
        $arrivals = $this->metrics->arrivals();
        $departures = $this->metrics->departures();
        $pos = $this->metrics->posSalesSummary();
        $outstanding = $this->metrics->outstandingBalance();

        return [
            'kpis' => [
                ['label' => 'Occupancy', 'value' => $snapshot['occupancy_percent'] . '%', 'trend' => sprintf('%d / %d rooms', $snapshot['occupied'], $snapshot['total'])],
                ['label' => 'RevPAR', 'value' => $this->currency($revenue['revpar']), 'trend' => 'Today'],
                ['label' => 'ADR (30d)', 'value' => $this->currency($revenue['adr']), 'trend' => 'Rolling'],
            ],
            'alerts' => $alerts,
            'actions' => [
                sprintf('%d arrivals through next 48h', count($arrivals)),
                sprintf('Outstanding folios %s', $this->currency($outstanding)),
                sprintf('POS revenue today %s', $this->currency($pos['today_total'])),
            ],
            'arrivals' => $arrivals,
            'departures' => $departures,
        ];
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
        $pending = $this->metrics->pendingPayments();
        $poPending = $this->pendingPurchaseOrdersCount();
        $movements = $this->recentInventoryMovements(5);

        return [
            'kpis' => [
                ['label' => 'Daily Room Revenue', 'value' => $this->currency($revenue['today']), 'trend' => 'Today'],
                ['label' => 'POS Sales', 'value' => $this->currency($pos['today_total']), 'trend' => sprintf('%d tickets', $pos['today_count'])],
                ['label' => 'Outstanding Folios', 'value' => $this->currency($outstanding), 'trend' => 'Open'],
            ],
            'alerts' => $alerts,
            'actions' => [
                sprintf('Approve %d pending folios', count($pending)),
                sprintf('Monitor inventory valuation %s', $this->currency($this->inventory->valuation())),
                sprintf('Review %d pending POs', $poPending),
            ],
            'inventory_value' => $this->inventory->valuation(),
            'low_stock' => $this->inventory->lowStockItems(5),
            'pending_payments' => $pending,
            'po_pending' => $poPending,
            'recent_movements' => $movements,
        ];
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
        $arrivals = $this->metrics->arrivals();
        $pending = $this->metrics->pendingPayments();
        $notifications = $this->alertsFromNotifications('cashier');

        return [
            'shortcuts' => [
                ['label' => 'New POS Sale', 'action' => base_url('dashboard/pos')],
                ['label' => 'Check-In Guest', 'action' => base_url('dashboard/bookings')],
                ['label' => 'Check-Out / Folio', 'action' => base_url('dashboard/bookings/folio')],
                ['label' => 'Issue Receipt', 'action' => base_url('dashboard/pos')],
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
                ];
            }, $arrivals),
            'notifications' => $notifications,
        ];
    }

    protected function serviceData(): array
    {
        $arrivals = $this->metrics->arrivals();
        $alerts = $this->alertsFromNotifications('service_agent');

        return [
            'reservations' => $arrivals,
            'room_status' => array_map(function (array $room) {
                return [
                    'room' => $room['display_name'] ?? $room['room_number'] ?? 'Room',
                    'status' => 'Needs cleaning',
                ];
            }, $this->metrics->housekeepingQueue(3)),
            'requests' => $alerts,
        ];
    }

    protected function kitchenData(): array
    {
        return [
            'orders' => [
                ['ticket' => 'KOT-1208', 'items' => 'Nyama Choma x2, Ugali', 'status' => 'In Prep'],
                ['ticket' => 'KOT-1209', 'items' => 'Tilapia Fry, Salad', 'status' => 'Waiting'],
            ],
            'inventory_alerts' => [
                'Tomatoes below par level',
                'Cooking oil reorder triggered',
            ],
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
        $tenantId = \App\Support\Tenant::id();
        $sql = '
            SELECT m.type, m.quantity, m.reference, m.notes, m.created_at,
                   i.name AS item_name, l.name AS location_name
            FROM inventory_movements m
            INNER JOIN inventory_items i ON i.id = m.item_id
            INNER JOIN inventory_locations l ON l.id = m.location_id
        ';
        $params = [];
        $where = [];
        if ($tenantId !== null) {
            $where[] = '(m.tenant_id = :tenant OR m.tenant_id IS NULL)';
            $params['tenant'] = $tenantId;
        }
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY m.created_at DESC LIMIT ' . (int)$limit;
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }
}


