<?php

namespace App\Modules\POS\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\FolioRepository;
use App\Repositories\PosItemRepository;
use App\Repositories\PosSaleRepository;
use App\Repositories\PosTillRepository;
use App\Repositories\SalesReportRepository;
use App\Repositories\ReservationRepository;
use App\Services\Inventory\InventoryService;
use App\Services\Notifications\NotificationService;
use App\Support\Auth;
use Exception;

class POSController extends Controller
{
    protected PosItemRepository $items;
    protected PosSaleRepository $sales;
    protected PosTillRepository $tills;
    protected ReservationRepository $reservations;
    protected FolioRepository $folios;
    protected NotificationService $notifications;
    protected InventoryService $inventoryService;

    public function __construct()
    {
        $this->items = new PosItemRepository();
        $this->sales = new PosSaleRepository();
        $this->tills = new PosTillRepository();
        $this->reservations = new ReservationRepository();
        $this->folios = new FolioRepository();
        $this->notifications = new NotificationService();
        $this->inventoryService = new InventoryService();
    }

	public function dashboard(Request $request): void
	{
		Auth::requireRoles(['admin', 'operation_manager', 'finance_manager', 'cashier', 'service_agent']);

		$start = $request->input('start') ?: date('Y-m-01');
		$end = $request->input('end') ?: date('Y-m-d');

		$reports = new SalesReportRepository();
		$summary = $reports->summary($start, $end);
		$payments = $reports->paymentBreakdown($start, $end);
		$trend = $reports->trend($start, $end);
		$topItems = $reports->topItems($start, $end, 5);
		$topCategories = $reports->topCategories($start, $end, 5);
		$topStaff = $reports->topStaff($start, $end, 5);

		$this->view('dashboard/pos/dashboard', [
			'filters' => ['start' => $start, 'end' => $end],
			'summary' => $summary,
			'payments' => $payments,
			'trend' => $trend,
			'topItems' => $topItems,
			'topCategories' => $topCategories,
			'topStaff' => $topStaff,
		]);
	}

    public function index(): void
    {
        Auth::requireRoles(['admin', 'cashier', 'service_agent']);

        // Pull POS items from Inventory (categories + stock + mapping to POS items).
        // We do not hide unavailable items here; they will be shown but greyed out in the UI.
        $invRepo = new \App\Repositories\InventoryRepository();
        $categories = $invRepo->posEnabledByCategory(false);

        $user = Auth::user();
        $this->view('dashboard/pos/index', [
            'categories' => $categories,
            'locations' => $this->inventoryService->locations(),
            'checkedInGuests' => $this->reservations->checkedInGuests(),
            'user' => $user,
        ]);
    }

    public function sale(Request $request): void
    {
        Auth::requireRoles(['admin', 'cashier', 'service_agent']);

        $itemIds = $request->input('item_ids', []);
        $quantities = $request->input('quantities', []);
        $prices = $request->input('prices', []);

        $lines = [];
        foreach ($itemIds as $index => $itemId) {
            $qty = isset($quantities[$index]) ? (int)$quantities[$index] : 1;
            $price = isset($prices[$index]) ? (float)$prices[$index] : 0;
            if ($qty <= 0 || $price <= 0) {
                continue;
            }
            $lines[] = [
                'item_id' => (int)$itemId,
                'quantity' => $qty,
                'price' => $price,
                'line_total' => $qty * $price,
            ];
        }

        if (empty($lines)) {
            header('Location: ' . base_url('dashboard/pos?error=Add%20items%20to%20order'));
            return;
        }

        $user = Auth::user();
        $paymentType = $request->input('payment_type', 'cash');
        $customerType = $request->input('customer_type', 'walkin');
        $reservationReference = trim($request->input('reservation_reference', ''));
        $reservationId = null;

        if ($reservationReference !== '') {
            $reservation = $this->reservations->findByReference($reservationReference);
            if ($reservation) {
                $reservationId = (int)$reservation['id'];
            } else {
                header('Location: ' . base_url('dashboard/pos?error=Reservation%20not%20found'));
                return;
            }
        }

        // If payment type is room charge, reservation must be linked
        if ($paymentType === 'room' && !$reservationId) {
            header('Location: ' . base_url('dashboard/pos?error=Room%20charge%20requires%20a%20guest%20selection'));
            return;
        }

        $total = array_sum(array_column($lines, 'line_total'));

        try {
            $saleId = $this->sales->create([
                'user_id' => $user['id'],
                'till_id' => null, // No longer using till - staff member is tracked via user_id
                'payment_type' => $paymentType,
                'total' => $total,
                'notes' => $request->input('notes'),
                'reservation_id' => $reservationId,
            ], $lines);

            $locationId = (int)$request->input('location_id', 0);
            if (!$locationId && !empty($locations = $this->inventoryService->locations())) {
                $locationId = (int)($locations[0]['id'] ?? 0);
            }
            $this->deductInventory($lines, $locationId, 'POS #' . $saleId);

            if ($reservationId) {
                $folio = $this->folios->findByReservation($reservationId);
                if (!$folio) {
                    $folioId = $this->folios->create($reservationId);
                    $folio = $this->folios->findByReservation($reservationId);
                }
                $this->folios->addEntry((int)$folio['id'], 'POS sale #' . $saleId, $total, 'charge', 'pos');
            }

            $this->notifications->notifyRole('finance_manager', 'POS Sale Recorded', sprintf(
                '%s processed a sale of KES %s via POS.',
                $user['name'],
                number_format($total, 2)
            ));

            header('Location: ' . base_url('dashboard/pos?success=1'));
        } catch (Exception $e) {
            header('Location: ' . base_url('dashboard/pos?error=' . urlencode($e->getMessage())));
        }
    }

    protected function deductInventory(array $lines, int $locationId, string $reference): void
    {
        foreach ($lines as $line) {
            $components = $this->fetchComponents($line['item_id']);
            foreach ($components as $component) {
                $qty = $component['quantity_per_sale'] * $line['quantity'];
                if ($qty > 0) {
                    $this->inventoryService->deductStock((int)$component['inventory_item_id'], $locationId, $qty, $reference, 'POS sale');
                }
            }
        }
    }

    protected function fetchComponents(int $posItemId): array
    {
        $stmt = db()->prepare('SELECT * FROM pos_item_components WHERE pos_item_id = :id');
        $stmt->execute(['id' => $posItemId]);

        return $stmt->fetchAll();
    }
}

