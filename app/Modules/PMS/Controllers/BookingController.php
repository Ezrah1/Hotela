<?php

namespace App\Modules\PMS\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\FolioRepository;
use App\Repositories\ReservationRepository;
use App\Repositories\RoomRepository;
use App\Repositories\RoomTypeRepository;
use App\Services\PMS\CheckInService;
use App\Services\Notifications\NotificationService;
use App\Services\PMS\AvailabilityService;
use App\Support\Auth;
use App\Support\GuestPortal;
use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use Exception;

class BookingController extends Controller
{
    protected AvailabilityService $availability;
    protected ReservationRepository $reservations;
    protected RoomTypeRepository $roomTypes;
    protected RoomRepository $rooms;
    protected NotificationService $notifications;
    protected CheckInService $checkIns;
    protected FolioRepository $folios;

    public function __construct()
    {
        $this->availability = new AvailabilityService();
        $this->reservations = new ReservationRepository();
        $this->roomTypes = new RoomTypeRepository();
        $this->rooms = new RoomRepository();
        $this->notifications = new NotificationService();
        $this->checkIns = new CheckInService();
        $this->folios = new FolioRepository();
    }

    public function publicForm(Request $request): void
    {
        $guest = GuestPortal::user();
        $start = $request->input('check_in', date('Y-m-d', strtotime('+1 day')));
        $end = $request->input('check_out', date('Y-m-d', strtotime('+2 days')));
        $results = $this->availability->search($start, $end);

        $this->view('website/booking/form', [
            'availability' => $results,
            'roomTypes' => $this->roomTypes->all(),
            'filters' => [
                'check_in' => $start,
                'check_out' => $end,
                'adults' => (int)$request->input('adults', 1),
                'children' => (int)$request->input('children', 0),
            ],
            'guest' => $guest,
        ]);
    }

    public function store(Request $request): void
    {
        $guest = GuestPortal::user();
        try {
            $data = $request->all();

            $guestName = trim($data['guest_name'] ?? ($guest['guest_name'] ?? ''));
            $guestEmail = trim($data['guest_email'] ?? ($guest['guest_email'] ?? ''));
            $guestPhone = trim($data['guest_phone'] ?? ($guest['guest_phone'] ?? ''));

            if ($guestName === '' || ($guestEmail === '' && $guestPhone === '')) {
                http_response_code(422);
                echo 'Please provide your name and at least one contact (email or phone).';
                return;
            }

            $roomType = $this->roomTypes->find((int)$data['room_type_id']);
            if (!$roomType) {
                http_response_code(404);
                echo 'Room type not found.';
                return;
            }

            $checkIn = $data['check_in'] ?? null;
            $checkOut = $data['check_out'] ?? null;

            try {
                $start = new DateTimeImmutable($checkIn);
                $end = new DateTimeImmutable($checkOut);
            } catch (Exception $e) {
                http_response_code(422);
                echo 'Invalid dates provided.';
                return;
            }

            if ($end <= $start) {
                http_response_code(422);
                echo 'Check-out must be after check-in.';
                return;
            }

            $nights = max(1, $start->diff($end)->days);
            $website = settings('website', []);
            $promo = (float)($website['promo_discount'] ?? 0);
            $baseRate = (float)($roomType['base_rate'] ?? 0);
            $nightlyRate = $baseRate;
            if ($promo > 0) {
                $nightlyRate = max(0, $baseRate - ($baseRate * $promo / 100));
            }
            $totalAmount = round($nightlyRate * $nights, 2);

            $roomId = !empty($data['room_id']) ? (int)$data['room_id'] : null;
            if ($roomId && !$this->rooms->isAvailable($roomId, $checkIn, $checkOut)) {
                http_response_code(409);
                echo 'The selected room is no longer available for those dates.';
                return;
            }

            $specialRequests = trim($data['special_requests'] ?? '');
            $extras = [];
            if ($specialRequests !== '') {
                $extras['special_requests'] = $specialRequests;
            }

            $reference = 'HTL-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

            $reservationId = $this->reservations->create([
                'reference' => $reference,
                'guest_name' => $guestName,
                'guest_email' => $guestEmail,
                'guest_phone' => $guestPhone,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'adults' => (int)$data['adults'],
                'children' => (int)$data['children'],
                'room_type_id' => (int)$data['room_type_id'],
                'room_id' => $roomId,
                'extras' => $extras ? json_encode($extras) : null,
                'source' => 'website',
                'status' => 'pending',
                'total_amount' => $totalAmount,
                'deposit_amount' => 0,
                'payment_status' => 'unpaid',
            ]);

            $roomLabel = $roomId
                ? 'Room ' . $roomId
                : ($roomType['name'] ?? 'Room Type');

            $message = sprintf(
                '%s booked from %s to %s. %s',
                $guestName,
                $checkIn,
                $checkOut,
                $roomId ? 'Assigned to ' . $roomLabel : 'Awaiting assignment.'
            );

            $this->notifications->notifyRole('housekeeping', 'New reservation', $message, [
                'reference' => $reference,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'room_id' => $roomId,
                'room_type_id' => $data['room_type_id'],
            ]);

            GuestPortal::login([
                'guest_name' => $guestName,
                'guest_email' => $guestEmail,
                'guest_phone' => $guestPhone,
                'identifier' => $guestEmail ?: $guestPhone,
                'reference' => $reference,
            ]);

            header('Location: ' . base_url('booking?success=1&ref=' . urlencode($reference)));
            return;
        } catch (Exception $e) {
            http_response_code(500);
            echo 'Unable to create booking at this time: ' . htmlspecialchars($e->getMessage());
        }
    }

    public function checkAvailability(Request $request): void
    {
        header('Content-Type: application/json');

        $checkIn = $request->input('check_in');
        $checkOut = $request->input('check_out');
        $roomTypeId = (int)$request->input('room_type_id');
        $roomId = $request->input('room_id') ? (int)$request->input('room_id') : null;

        if (!$checkIn || !$checkOut || !$roomTypeId) {
            http_response_code(422);
            echo json_encode(['error' => 'Check-in, check-out, and room type are required.']);
            return;
        }

        try {
            $start = new DateTimeImmutable($checkIn);
            $end = new DateTimeImmutable($checkOut);
        } catch (Exception $e) {
            http_response_code(422);
            echo json_encode(['error' => 'Invalid dates provided.']);
            return;
        }

        if ($end <= $start) {
            http_response_code(422);
            echo json_encode(['error' => 'Check-out must be after check-in.']);
            return;
        }

        $nights = max(1, $start->diff($end)->days);
        $results = $this->availability->search($checkIn, $checkOut);

        $selectedType = null;
        foreach ($results as $result) {
            if ((int)$result['type']['id'] === $roomTypeId) {
                $selectedType = $result;
                break;
            }
        }

        if (!$selectedType) {
            http_response_code(404);
            echo json_encode(['error' => 'Room type not found.']);
            return;
        }

        $availableRooms = $selectedType['rooms'] ?? [];
        $roomMatch = null;

        if ($roomId) {
            foreach ($availableRooms as $room) {
                if ((int)$room['id'] === $roomId) {
                    $roomMatch = $room;
                    break;
                }
            }
        } elseif (!empty($availableRooms)) {
            $roomMatch = $availableRooms[0];
        }

        $isAvailable = $roomMatch !== null;
        $roomType = $selectedType['type'];
        $website = settings('website', []);
        $promo = (float)($website['promo_discount'] ?? 0);
        $baseRate = (float)($roomType['base_rate'] ?? 0);
        $nightlyRate = $baseRate;
        $discountAmount = 0;

        if ($promo > 0) {
            $discountAmount = round($baseRate * $promo / 100, 2);
            $nightlyRate = max(0, $baseRate - $discountAmount);
        }

        $total = round($nightlyRate * $nights, 2);

        $suggestions = [];
        if (!$isAvailable) {
            foreach ($results as $result) {
                if ((int)$result['type']['id'] === $roomTypeId) {
                    continue;
                }
                if (empty($result['rooms'])) {
                    continue;
                }

                $candidate = $result['rooms'][0];
                $suggestions[] = [
                    'room_type_id' => (int)$result['type']['id'],
                    'room_type_name' => $result['type']['name'],
                    'room_id' => (int)$candidate['id'],
                    'room_name' => $candidate['display_name'] ?? $candidate['room_number'],
                    'base_rate' => (float)($result['type']['base_rate'] ?? 0),
                ];

                if (count($suggestions) >= 3) {
                    break;
                }
            }
        }

        echo json_encode([
            'available' => $isAvailable,
            'room' => $roomMatch,
            'room_type' => [
                'id' => $roomType['id'],
                'name' => $roomType['name'],
            ],
            'pricing' => [
                'nights' => $nights,
                'nightly_rate' => $nightlyRate,
                'base_rate' => $baseRate,
                'discount_amount' => $discountAmount,
                'discount_percent' => $promo,
                'total' => $total,
            ],
            'suggestions' => $suggestions,
        ]);
    }

    public function staffIndex(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager', 'service_agent', 'cashier', 'finance_manager']);
        $user = Auth::user();
        $filter = $request->input('filter', 'upcoming');
        $reservations = $this->reservations->all($filter, 50);

        $this->view('dashboard/bookings/index', [
            'user' => $user,
            'roleConfig' => [
                'label' => 'Booking Dashboard',
            ],
            'reservations' => $reservations,
            'filter' => $filter,
        ]);
    }

    public function checkIn(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager', 'service_agent', 'cashier', 'finance_manager']);
        $reservationId = (int)$request->input('reservation_id');

        try {
            $this->checkIns->checkIn($reservationId);
            header('Location: ' . base_url('dashboard/bookings?success=checkin'));
        } catch (Exception $e) {
            header('Location: ' . base_url('dashboard/bookings?error=' . urlencode($e->getMessage())));
        }
    }

    public function checkOut(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager', 'service_agent', 'cashier', 'finance_manager']);
        $reservationId = (int)$request->input('reservation_id');

        try {
            $this->checkIns->checkOut($reservationId);
            header('Location: ' . base_url('dashboard/bookings?success=checkout'));
        } catch (Exception $e) {
            header('Location: ' . base_url('dashboard/bookings?error=' . urlencode($e->getMessage())));
        }
    }

    public function folio(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager', 'service_agent', 'cashier', 'finance_manager']);
        $reservationId = (int)$request->input('reservation_id');
        $reservation = $this->reservations->findById($reservationId);

        if (!$reservation) {
            http_response_code(404);
            echo 'Reservation not found';
            return;
        }

        $folio = $this->folios->findByReservation($reservationId);
        if (!$folio) {
            $folioId = $this->folios->create($reservationId);
            $folio = $this->folios->findByReservation($reservationId);
        }

        $entries = $this->folios->entries($folio['id']);

        $this->view('dashboard/bookings/folio', [
            'reservation' => $reservation,
            'folio' => $folio,
            'entries' => $entries,
        ]);
    }

    public function addFolioEntry(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager', 'service_agent', 'cashier', 'finance_manager']);
        $reservationId = (int)$request->input('reservation_id');
        $reservation = $this->reservations->findById($reservationId);

        if (!$reservation) {
            header('Location: ' . base_url('dashboard/bookings?error=Invalid%20reservation'));
            return;
        }

        $folio = $this->folios->findByReservation($reservationId);
        if (!$folio) {
            $folioId = $this->folios->create($reservationId);
            $folio = $this->folios->findByReservation($reservationId);
        }

        $amount = (float)$request->input('amount', 0);
        $type = $request->input('type', 'charge');
        $description = trim($request->input('description', ''));

        if ($amount <= 0 || $description === '') {
            header('Location: ' . base_url('dashboard/bookings/folio?reservation_id=' . $reservationId . '&error=Invalid%20entry'));
            return;
        }

        $this->folios->addEntry((int)$folio['id'], $description, $amount, $type, $request->input('source'));
        header('Location: ' . base_url('dashboard/bookings/folio?reservation_id=' . $reservationId . '&success=1'));
    }

    public function calendar(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager', 'service_agent', 'cashier']);
        $start = $request->input('start', date('Y-m-d'));
        $end = $request->input('end', date('Y-m-d', strtotime('+7 days')));

        $calendarReservations = $this->reservations->calendar($start, $end);

        $grouped = [];
        foreach ($calendarReservations as $reservation) {
            $key = $reservation['room_number'] ?? $reservation['room_type_name'];
            $grouped[$key][] = $reservation;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'rooms' => $grouped,
            'range' => [
                'start' => $start,
                'end' => $end,
            ],
        ]);
    }

    public function calendarView(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager', 'service_agent', 'cashier']);
        $start = $request->input('start', date('Y-m-d'));
        $end = $request->input('end', date('Y-m-d', strtotime('+7 days')));

        $reservations = $this->reservations->calendar($start, $end);
        $availableRooms = $this->rooms->allAvailableBetween($start, $end);

        $this->view('dashboard/bookings/calendar_view', [
            'reservations' => $reservations,
            'availableRooms' => $availableRooms,
            'range' => ['start' => $start, 'end' => $end],
        ]);
    }

    public function assignRoom(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager', 'service_agent']);
        $reservationId = (int)$request->input('reservation_id');
        $roomId = (int)$request->input('room_id');

        $reservation = $this->reservations->findById($reservationId);
        $room = $this->rooms->find($roomId);

        if (!$reservation || !$room) {
            header('Location: ' . base_url('dashboard/bookings/calendar-view?error=Invalid%20selection'));
            return;
        }

        if (!$this->rooms->isAvailable($roomId, $reservation['check_in'], $reservation['check_out'])) {
            header('Location: ' . base_url('dashboard/bookings/calendar-view?error=Room%20not%20available'));
            return;
        }

        $this->reservations->updateStatus($reservationId, [
            'room_id' => $roomId,
            'room_status' => 'pending',
        ]);

        header('Location: ' . base_url('dashboard/bookings/calendar-view?success=1'));
    }

    public function edit(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager', 'service_agent', 'cashier', 'finance_manager']);
        $reservationId = (int)$request->input('reservation_id');
        $reservation = $this->reservations->findById($reservationId);

        if (!$reservation) {
            header('Location: ' . base_url('dashboard/bookings?error=Reservation%20not%20found'));
            return;
        }

        // Get available rooms for the current dates (excluding current reservation's room if assigned)
        $checkIn = $request->input('check_in', $reservation['check_in']);
        $checkOut = $request->input('check_out', $reservation['check_out']);
        $availableRooms = $this->rooms->allAvailableBetween($checkIn, $checkOut);
        
        // Also include the currently assigned room even if it appears unavailable
        if ($reservation['room_id']) {
            $currentRoom = $this->rooms->find((int)$reservation['room_id']);
            if ($currentRoom) {
                $found = false;
                foreach ($availableRooms as $room) {
                    if ((int)$room['id'] === (int)$reservation['room_id']) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $availableRooms[] = $currentRoom;
                }
            }
        }
        
        $roomTypes = $this->roomTypes->all();

        $this->view('dashboard/bookings/edit', [
            'reservation' => $reservation,
            'availableRooms' => $availableRooms,
            'roomTypes' => $roomTypes,
        ]);
    }

    public function update(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager', 'service_agent', 'cashier', 'finance_manager']);
        $reservationId = (int)$request->input('reservation_id');
        $reservation = $this->reservations->findById($reservationId);

        if (!$reservation) {
            header('Location: ' . base_url('dashboard/bookings?error=Reservation%20not%20found'));
            return;
        }

        try {
            $checkIn = $request->input('check_in');
            $checkOut = $request->input('check_out');
            $roomId = $request->input('room_id') ? (int)$request->input('room_id') : null;

            // Validate dates
            if ($checkIn && $checkOut) {
                $start = new DateTimeImmutable($checkIn);
                $end = new DateTimeImmutable($checkOut);
                if ($end <= $start) {
                    header('Location: ' . base_url('dashboard/bookings/edit?reservation_id=' . $reservationId . '&error=Check-out%20must%20be%20after%20check-in'));
                    return;
                }
            }

            // If room is being changed or dates are changed, check availability
            $checkInDate = $checkIn ?: $reservation['check_in'];
            $checkOutDate = $checkOut ?: $reservation['check_out'];
            $finalRoomId = $roomId ?: $reservation['room_id'];
            
            if ($finalRoomId) {
                // Check if room is available, but exclude current reservation from conflict check
                $params = [
                    'room' => (int)$finalRoomId,
                    'start' => $checkInDate,
                    'end' => $checkOutDate,
                    'exclude_reservation' => $reservationId,
                ];
                $sql = '
                    SELECT COUNT(*) FROM reservations
                    WHERE room_id = :room
                    AND id != :exclude_reservation
                    AND NOT (check_out <= :start OR check_in >= :end)
                ';
                $tenantId = \App\Support\Tenant::id();
                if ($tenantId !== null) {
                    $sql .= ' AND tenant_id = :tenant_id';
                    $params['tenant_id'] = $tenantId;
                }
                $stmt = db()->prepare($sql);
                $stmt->execute($params);
                
                if ((int)$stmt->fetchColumn() > 0) {
                    header('Location: ' . base_url('dashboard/bookings/edit?reservation_id=' . $reservationId . '&error=Room%20not%20available%20for%20selected%20dates'));
                    return;
                }
            }

            $updateData = [];
            if ($request->input('guest_name')) $updateData['guest_name'] = trim($request->input('guest_name'));
            if ($request->input('guest_email')) $updateData['guest_email'] = trim($request->input('guest_email'));
            if ($request->input('guest_phone')) $updateData['guest_phone'] = trim($request->input('guest_phone'));
            if ($checkIn) $updateData['check_in'] = $checkIn;
            if ($checkOut) $updateData['check_out'] = $checkOut;
            if ($request->input('adults')) $updateData['adults'] = (int)$request->input('adults');
            if ($request->input('children') !== null) $updateData['children'] = (int)$request->input('children');
            if ($request->input('room_id') !== null) $updateData['room_id'] = $roomId;
            if ($request->input('room_type_id')) $updateData['room_type_id'] = (int)$request->input('room_type_id');
            if ($request->input('status')) $updateData['status'] = $request->input('status');
            if ($request->input('notes') !== null) $updateData['notes'] = trim($request->input('notes'));

            // Recalculate total if dates or room changed
            if (isset($updateData['check_in']) || isset($updateData['check_out']) || isset($updateData['room_type_id'])) {
                $finalCheckIn = $updateData['check_in'] ?? $reservation['check_in'];
                $finalCheckOut = $updateData['check_out'] ?? $reservation['check_out'];
                $finalRoomTypeId = $updateData['room_type_id'] ?? $reservation['room_type_id'];
                
                $roomType = $this->roomTypes->find($finalRoomTypeId);
                if ($roomType) {
                    $nights = (new DateTimeImmutable($finalCheckIn))->diff(new DateTimeImmutable($finalCheckOut))->days;
                    $updateData['total_amount'] = (float)($roomType['base_rate'] ?? 0) * $nights;
                }
            }

            $this->reservations->update($reservationId, $updateData);

            header('Location: ' . base_url('dashboard/bookings?success=updated'));
        } catch (Exception $e) {
            header('Location: ' . base_url('dashboard/bookings/edit?reservation_id=' . $reservationId . '&error=' . urlencode($e->getMessage())));
        }
    }
}


