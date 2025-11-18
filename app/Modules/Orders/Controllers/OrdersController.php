<?php

namespace App\Modules\Orders\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\PosSaleRepository;
use App\Support\Auth;

class OrdersController extends Controller
{
    protected PosSaleRepository $sales;

    public function __construct()
    {
        $this->sales = new PosSaleRepository();
    }

    public function index(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager', 'cashier', 'service_agent', 'kitchen', 'finance_manager']);

        $filter = $request->input('filter', 'today');
        $orders = $this->sales->all($filter, 50);

        $this->view('dashboard/orders/index', [
            'orders' => $orders,
            'filter' => $filter,
        ]);
    }

    public function show(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager', 'cashier', 'service_agent', 'kitchen', 'finance_manager']);

        $orderId = (int)$request->input('id');
        $order = $this->sales->findById($orderId);

        if (!$order) {
            header('Location: ' . base_url('dashboard/orders?error=Order%20not%20found'));
            return;
        }

        $items = $this->sales->getItems($orderId);

        $this->view('dashboard/orders/show', [
            'order' => $order,
            'items' => $items,
        ]);
    }
}

