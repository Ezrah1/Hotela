<?php

namespace App\Modules\Kitchen\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\OrderRepository;
use App\Repositories\InventoryRepository;
use App\Support\Auth;

class KOTController extends Controller
{
    protected OrderRepository $orders;
    protected InventoryRepository $inventory;

    public function __construct()
    {
        $this->orders = new OrderRepository();
        $this->inventory = new InventoryRepository();
    }

    /**
     * Kitchen Order Tickets Dashboard
     */
    public function index(Request $request): void
    {
        Auth::requireRoles(['kitchen', 'director', 'operation_manager']);

        $status = $request->input('status');
        $kitchenOrders = $this->orders->getKitchenOrders($status);
        $statusCounts = $this->orders->getKitchenStatusCounts();
        $inventoryAlerts = $this->inventory->getLowStockAlerts();

        // If AJAX request, return JSON
        if ($request->input('ajax') === '1') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'orders' => $kitchenOrders,
                'status_counts' => $statusCounts,
                'inventory_alerts' => $inventoryAlerts,
            ]);
            return;
        }

        $this->view('dashboard/kitchen/kot', [
            'orders' => $kitchenOrders,
            'statusCounts' => $statusCounts,
            'inventoryAlerts' => $inventoryAlerts,
            'currentStatus' => $status,
        ]);
    }

    /**
     * Update order status (for kitchen workflow)
     */
    public function updateStatus(Request $request): void
    {
        Auth::requireRoles(['kitchen', 'director', 'operation_manager']);

        $orderId = (int)$request->input('order_id');
        $status = $request->input('status');
        $notes = $request->input('notes');

        if (!$orderId || !$status) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            return;
        }

        $user = Auth::user();
        $this->orders->updateStatus($orderId, $status, $user['id'] ?? null, $notes);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Order status updated successfully',
        ]);
    }

    /**
     * Order Status Overview Page
     */
    public function status(Request $request): void
    {
        Auth::requireRoles(['kitchen', 'director', 'operation_manager']);

        $statusCounts = $this->orders->getKitchenStatusCounts();
        $allOrders = $this->orders->getKitchenOrders();
        
        // Group orders by status
        $ordersByStatus = [
            'pending' => [],
            'confirmed' => [],
            'preparing' => [],
            'ready' => [],
        ];

        foreach ($allOrders as $order) {
            $orderStatus = $order['status'] ?? 'pending';
            if (isset($ordersByStatus[$orderStatus])) {
                $ordersByStatus[$orderStatus][] = $order;
            }
        }

        $this->view('dashboard/kitchen/status', [
            'statusCounts' => $statusCounts,
            'ordersByStatus' => $ordersByStatus,
        ]);
    }
}

