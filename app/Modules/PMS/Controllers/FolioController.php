<?php

namespace App\Modules\PMS\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\FolioRepository;
use App\Repositories\ReservationRepository;
use App\Support\Auth;

class FolioController extends Controller
{
    protected FolioRepository $folios;
    protected ReservationRepository $reservations;

    public function __construct()
    {
        $this->folios = new FolioRepository();
        $this->reservations = new ReservationRepository();
    }

    public function index(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'finance_manager', 'cashier', 'receptionist', 'operation_manager']);

        $status = $request->input('status', '');
        $startDate = $request->input('start_date', '');
        $endDate = $request->input('end_date', '');
        $search = trim($request->input('search', ''));
        $guestEmail = trim($request->input('guest_email', ''));
        $guestPhone = trim($request->input('guest_phone', ''));
        $limit = (int)$request->input('limit', 50);

        // Build query parameters
        $params = [];
        $conditions = [];

        if ($status) {
            $conditions[] = 'folios.status = :status';
            $params['status'] = $status;
        }

        if ($startDate) {
            $conditions[] = '(reservations.check_in >= :start_date OR folios.created_at >= :start_date)';
            $params['start_date'] = $startDate;
        }

        if ($endDate) {
            $conditions[] = '(reservations.check_in <= :end_date OR folios.created_at <= :end_date)';
            $params['end_date'] = $endDate;
        }

        if ($search) {
            $conditions[] = '(reservations.reference LIKE :search OR COALESCE(folios.guest_name, reservations.guest_name) LIKE :search OR COALESCE(folios.guest_email, reservations.guest_email) LIKE :search OR COALESCE(folios.guest_phone, reservations.guest_phone) LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        // Guest email filter
        if ($guestEmail) {
            $conditions[] = '(COALESCE(folios.guest_email, reservations.guest_email) = :guest_email)';
            $params['guest_email'] = strtolower(trim($guestEmail));
        }

        // Guest phone filter
        if ($guestPhone) {
            $sanitizedPhone = preg_replace('/[^0-9]/', '', $guestPhone);
            $conditions[] = '(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(folios.guest_phone, reservations.guest_phone), " ", ""), "-", ""), "(", ""), ")", ""), "+", "") = :guest_phone)';
            $params['guest_phone'] = $sanitizedPhone;
        }

        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $sql = "
            SELECT 
                folios.*,
                reservations.id AS reservation_id,
                reservations.reference,
                COALESCE(folios.guest_name, reservations.guest_name) AS guest_name,
                COALESCE(folios.guest_email, reservations.guest_email) AS guest_email,
                COALESCE(folios.guest_phone, reservations.guest_phone) AS guest_phone,
                reservations.check_in,
                reservations.check_out,
                reservations.status AS reservation_status,
                rooms.room_number,
                rooms.display_name AS room_display_name,
                room_types.name AS room_type_name
            FROM folios
            LEFT JOIN reservations ON reservations.id = folios.reservation_id
            LEFT JOIN rooms ON rooms.id = reservations.room_id
            LEFT JOIN room_types ON room_types.id = reservations.room_type_id
            {$whereClause}
            ORDER BY folios.created_at DESC
            LIMIT " . (int)$limit . "
        ";

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $folios = $stmt->fetchAll();

        // Get statistics
        $stats = $this->getStatistics();

        $this->view('dashboard/folios/index', [
            'folios' => $folios,
            'stats' => $stats,
            'filters' => [
                'status' => $status,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'search' => $search,
                'guest_email' => $guestEmail,
                'guest_phone' => $guestPhone,
                'limit' => $limit,
            ],
        ]);
    }

    public function show(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'finance_manager', 'cashier', 'receptionist', 'operation_manager']);

        $folioId = (int)$request->input('id');
        $reservationId = (int)$request->input('reservation_id');
        $ref = trim($request->input('ref', ''));

        $folio = null;
        $reservation = null;

        if ($folioId > 0) {
            $sql = "
            SELECT 
                folios.*,
                reservations.id AS reservation_id,
                reservations.reference,
                COALESCE(folios.guest_name, reservations.guest_name) AS guest_name,
                COALESCE(folios.guest_email, reservations.guest_email) AS guest_email,
                COALESCE(folios.guest_phone, reservations.guest_phone) AS guest_phone,
                reservations.check_in,
                reservations.check_out,
                reservations.status AS reservation_status,
                rooms.room_number,
                rooms.display_name AS room_display_name,
                room_types.name AS room_type_name
            FROM folios
            LEFT JOIN reservations ON reservations.id = folios.reservation_id
            LEFT JOIN rooms ON rooms.id = reservations.room_id
            LEFT JOIN room_types ON room_types.id = reservations.room_type_id
            WHERE folios.id = :folio_id
            LIMIT 1
        ";
            $stmt = db()->prepare($sql);
            $stmt->execute(['folio_id' => $folioId]);
            $folio = $stmt->fetch();

            if ($folio) {
                $reservation = $this->reservations->findById((int)$folio['reservation_id']);
            }
        } elseif ($reservationId > 0) {
            $reservation = $this->reservations->findById($reservationId);
            if ($reservation) {
                $folio = $this->folios->findByReservation($reservationId);
            }
        } elseif ($ref) {
            $reservation = $this->reservations->findByReference($ref);
            if ($reservation) {
                $folio = $this->folios->findByReservation((int)$reservation['id']);
            }
        }

        if (!$folio) {
            header('Location: ' . base_url('staff/dashboard/folios?error=' . urlencode('Folio not found')));
            return;
        }

        // If no reservation, get guest info from folio
        if (!$reservation && $folio['guest_email']) {
            $reservation = [
                'id' => null,
                'reference' => 'GUEST-' . $folio['id'],
                'guest_name' => $folio['guest_name'] ?? 'Guest',
                'guest_email' => $folio['guest_email'],
                'guest_phone' => $folio['guest_phone'],
                'check_in' => null,
                'check_out' => null,
                'status' => 'guest_folio',
            ];
        }

        if (!$reservation) {
            header('Location: ' . base_url('staff/dashboard/folios?error=' . urlencode('Folio not found')));
            return;
        }

        // Ensure folio balance is up-to-date and sync booking payment status
        $this->folios->recalculate((int)$folio['id']);
        
        // Reload folio and reservation to get updated data
        $folio = $this->folios->findByReservation((int)$folio['reservation_id']) ?? $folio;
        if ($folio['reservation_id']) {
            $reservation = $this->reservations->findById((int)$folio['reservation_id']);
        }

        $entries = $this->folios->entries((int)$folio['id']);

        $this->view('dashboard/folios/view', [
            'folio' => $folio,
            'reservation' => $reservation,
            'entries' => $entries,
        ]);
    }

    protected function getStatistics(): array
    {
        $sql = "
            SELECT 
                COUNT(*) AS total_folios,
                SUM(CASE WHEN folios.status = 'open' THEN 1 ELSE 0 END) AS open_folios,
                SUM(CASE WHEN folios.status = 'closed' THEN 1 ELSE 0 END) AS closed_folios,
                SUM(CASE WHEN folios.balance > 0 THEN 1 ELSE 0 END) AS outstanding_folios,
                SUM(CASE WHEN folios.balance > 0 THEN folios.balance ELSE 0 END) AS total_outstanding,
                SUM(folios.total) AS total_charges
            FROM folios
        ";

        $stmt = db()->query($sql);
        $row = $stmt->fetch() ?: [];

        return [
            'total_folios' => (int)($row['total_folios'] ?? 0),
            'open_folios' => (int)($row['open_folios'] ?? 0),
            'closed_folios' => (int)($row['closed_folios'] ?? 0),
            'outstanding_folios' => (int)($row['outstanding_folios'] ?? 0),
            'total_outstanding' => (float)($row['total_outstanding'] ?? 0),
            'total_charges' => (float)($row['total_charges'] ?? 0),
        ];
    }
}

