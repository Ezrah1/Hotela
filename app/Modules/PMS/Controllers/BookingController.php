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
use App\Services\Email\EmailService;
use App\Services\Payments\MpesaService;
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
    protected EmailService $email;

    public function __construct()
    {
        $this->availability = new AvailabilityService();
        $this->reservations = new ReservationRepository();
        $this->roomTypes = new RoomTypeRepository();
        $this->rooms = new RoomRepository();
        $this->notifications = new NotificationService();
        $this->checkIns = new CheckInService();
        $this->folios = new FolioRepository();
        $this->email = new EmailService();
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

            // Handle payment method using unified payment processing service
            $paymentMethod = trim($data['payment_method'] ?? 'pay_on_arrival');
            $paymentProcessor = new \App\Services\Payments\PaymentProcessingService();
            
            $mpesaPhone = null;
            $mpesaCheckoutRequestId = null;
            $mpesaMerchantRequestId = null;
            $mpesaStatus = null;
            $paymentStatus = 'unpaid';

            try {
                $paymentOptions = [
                    'reference' => $reference,
                    'description' => 'Booking Payment - ' . $reference,
                    'reservation_id' => null, // Will be set after reservation is created
                ];
                
                // Add phone for M-Pesa
                if ($paymentMethod === 'mpesa') {
                    $mpesaPhone = trim($data['mpesa_phone'] ?? $guestPhone);
                    if (empty($mpesaPhone)) {
                        http_response_code(422);
                        echo 'Phone number is required for M-Pesa payment.';
                        return;
                    }
                    $paymentOptions['phone'] = $mpesaPhone;
                }
                
                $paymentResult = $paymentProcessor->processPayment($paymentMethod, $totalAmount, $paymentOptions);
                
                $mpesaPhone = $paymentResult['mpesa_phone'] ?? null;
                $mpesaCheckoutRequestId = $paymentResult['mpesa_checkout_request_id'] ?? null;
                $mpesaMerchantRequestId = $paymentResult['mpesa_merchant_request_id'] ?? null;
                $mpesaStatus = $paymentResult['mpesa_status'] ?? null;
                $paymentStatus = $paymentResult['payment_status'] ?? 'unpaid';
                
                // Create payment transaction record for M-Pesa
                if ($paymentMethod === 'mpesa' && $mpesaCheckoutRequestId) {
                    $this->createPaymentTransaction('booking', 0, $reference, 'mpesa', $totalAmount, $mpesaPhone, $mpesaCheckoutRequestId, $mpesaMerchantRequestId);
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo 'Payment processing failed: ' . $e->getMessage();
                return;
            }

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
                'payment_status' => $paymentStatus,
                'payment_method' => $paymentMethod,
                'mpesa_phone' => $mpesaPhone,
                'mpesa_checkout_request_id' => $mpesaCheckoutRequestId,
                'mpesa_merchant_request_id' => $mpesaMerchantRequestId,
                'mpesa_status' => $mpesaStatus,
            ]);

            // Update payment transaction with reservation ID
            if ($mpesaCheckoutRequestId) {
                $this->updatePaymentTransactionReference($mpesaCheckoutRequestId, $reservationId, $reference);
            }

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

            // Notify multiple roles about new booking
            $notificationPayload = [
                'reference' => $reference,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'room_id' => $roomId,
                'room_type_id' => $data['room_type_id'],
                'guest_name' => $guestName,
            ];
            
            // Notify housekeeping for room preparation
            $this->notifications->notifyRole('housekeeping', 'New reservation', $message, $notificationPayload);
            
            // Notify receptionist for check-in preparation
            $this->notifications->notifyRole('receptionist', 'New reservation', $message, $notificationPayload);
            
            // Notify operations manager for oversight
            $this->notifications->notifyRole('operation_manager', 'New reservation', $message, $notificationPayload);
            
            // Notify admin for all bookings
            $this->notifications->notifyRole('admin', 'New reservation', $message, $notificationPayload);

            // Send booking confirmation email to guest
            if (!empty($guestEmail)) {
                try {
                    $nights = max(1, $start->diff($end)->days);
                    $bookingData = [
                        'reference' => $reference,
                        'check_in' => $checkIn,
                        'check_out' => $checkOut,
                        'adults' => (int)$data['adults'],
                        'children' => (int)$data['children'],
                        'room_type_id' => (int)$data['room_type_id'],
                        'room_id' => $roomId,
                        'total_amount' => $totalAmount,
                        'nightly_rate' => $nightlyRate,
                        'nights' => $nights,
                        'status' => 'pending',
                        'room_type_name' => $roomType['name'] ?? 'Room Type',
                        'room_label' => $roomLabel,
                        'special_requests' => $specialRequests,
                    ];
                    
                    $guestData = [
                        'guest_name' => $guestName,
                        'guest_email' => $guestEmail,
                        'guest_phone' => $guestPhone,
                    ];
                    
                    $this->email->sendBookingConfirmation($bookingData, $guestData);
                } catch (\Exception $e) {
                    // Log error but don't fail the booking
                    error_log('Failed to send booking confirmation email: ' . $e->getMessage());
                }
            }

            GuestPortal::login([
                'guest_name' => $guestName,
                'guest_email' => $guestEmail,
                'guest_phone' => $guestPhone,
                'identifier' => $guestEmail ?: $guestPhone,
                'reference' => $reference,
            ]);

            // Redirect to booking confirmation page
            header('Location: ' . base_url('booking/confirmation?reference=' . urlencode($reference)));
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
            header('Location: ' . base_url('staff/dashboard/bookings?success=checkin'));
        } catch (Exception $e) {
            header('Location: ' . base_url('staff/dashboard/bookings?error=' . urlencode($e->getMessage())));
        }
    }

    public function checkOut(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager', 'service_agent', 'cashier', 'finance_manager']);
        $reservationId = (int)$request->input('reservation_id');

        try {
            $this->checkIns->checkOut($reservationId);
            header('Location: ' . base_url('staff/dashboard/bookings?success=checkout'));
        } catch (\App\Exceptions\OutstandingBalanceException $e) {
            // Redirect to folio page to settle balance
            $balance = number_format($e->getBalance(), 2);
            header('Location: ' . base_url('staff/dashboard/bookings/folio?reservation_id=' . $e->getReservationId() . '&checkout_pending=1&balance=' . urlencode($balance)));
        } catch (Exception $e) {
            header('Location: ' . base_url('staff/dashboard/bookings?error=' . urlencode($e->getMessage())));
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
        
        // Get pending M-Pesa payments for this reservation
        $pendingPayments = $this->getPendingMpesaPayments($reservationId);

        $this->view('dashboard/bookings/folio', [
            'reservation' => $reservation,
            'folio' => $folio,
            'entries' => $entries,
            'pendingPayments' => $pendingPayments,
        ]);
    }

    protected function getPendingMpesaPayments(int $reservationId): array
    {
        $stmt = db()->prepare('
            SELECT pt.*
            FROM payment_transactions pt
            WHERE pt.reference_id = :reservation_id
            AND pt.reference_code LIKE :pattern
            AND pt.status = :status
            AND pt.payment_method = :method
            ORDER BY pt.created_at DESC
        ');
        $stmt->execute([
            'reservation_id' => $reservationId,
            'pattern' => 'FOLIO-%',
            'status' => 'pending',
            'method' => 'mpesa'
        ]);
        return $stmt->fetchAll();
    }

    public function addFolioEntry(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager', 'service_agent', 'cashier', 'finance_manager']);
        $reservationId = (int)$request->input('reservation_id');
        $reservation = $this->reservations->findById($reservationId);

        if (!$reservation) {
            header('Location: ' . base_url('staff/dashboard/bookings?error=Invalid%20reservation'));
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
        $paymentMethod = trim($request->input('source', 'cash'));

        if ($amount <= 0 || $description === '') {
            header('Location: ' . base_url('staff/dashboard/bookings/folio?reservation_id=' . $reservationId . '&error=Invalid%20entry'));
            return;
        }

        // For payments, process through unified payment service if needed
        // For charges, just add directly to folio
        if ($type === 'payment') {
            // Non-M-Pesa payments are added directly (M-Pesa is handled separately via initiateFolioMpesaPayment)
            // This handles: cash, card, bank_transfer, cheque, corporate, room
            $this->folios->addEntry((int)$folio['id'], $description, $amount, $type, $paymentMethod);
        } else {
            // Charges are added directly
            $this->folios->addEntry((int)$folio['id'], $description, $amount, $type, $paymentMethod);
        }
        
        // Check if checkout was pending
        $checkoutPending = !empty($request->input('checkout_pending'));
        
        // Recalculate folio to get updated balance
        $folio = $this->folios->findByReservation($reservationId);
        
        if ($folio && (float)$folio['balance'] <= 0 && $checkoutPending) {
            // Balance is now settled, redirect to checkout
            header('Location: ' . base_url('staff/dashboard/bookings/folio?reservation_id=' . $reservationId . '&success=1&balance_settled=1'));
        } else {
            $balance = $folio ? number_format((float)$folio['balance'], 2) : '0.00';
            header('Location: ' . base_url('staff/dashboard/bookings/folio?reservation_id=' . $reservationId . '&success=1' . ($checkoutPending ? '&checkout_pending=1&balance=' . urlencode($balance) : '')));
        }
    }

    public function folioMpesaPayment(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager', 'service_agent', 'cashier', 'finance_manager']);
        
        header('Content-Type: application/json');
        
        $reservationId = (int)$request->input('reservation_id');
        $amount = (float)$request->input('amount', 0);
        $phone = trim($request->input('phone', ''));
        $description = trim($request->input('description', 'Folio Payment'));
        $checkoutPending = (bool)$request->input('checkout_pending', false);

        if (!$reservationId || $amount <= 0 || empty($phone)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Reservation ID, amount, and phone number are required'
            ]);
            return;
        }

        $reservation = $this->reservations->findById($reservationId);
        if (!$reservation) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Reservation not found'
            ]);
            return;
        }

        try {
            $paymentProcessor = new \App\Services\Payments\PaymentProcessingService();
            $reference = 'FOLIO-' . $reservation['reference'];
            
            $paymentResult = $paymentProcessor->processPayment('mpesa', $amount, [
                'phone' => $phone,
                'reference' => $reference,
                'description' => $description,
                'reservation_id' => $reservationId
            ]);
            
            $mpesaCheckoutRequestId = $paymentResult['mpesa_checkout_request_id'] ?? null;
            $mpesaMerchantRequestId = $paymentResult['mpesa_merchant_request_id'] ?? null;
            
            // Create payment transaction record
            // Note: Using 'other' as transaction_type since 'folio_payment' may not be in enum
            // We'll identify it by checking reference_code pattern
            $stmt = db()->prepare('
                INSERT INTO payment_transactions (transaction_type, reference_id, reference_code, payment_method, amount, phone_number, checkout_request_id, merchant_request_id, status)
                VALUES (:type, :reference_id, :reference_code, :method, :amount, :phone, :checkout_id, :merchant_id, :status)
            ');
            $stmt->execute([
                'type' => 'other', // Will identify as folio_payment by reference_code starting with 'FOLIO-'
                'reference_id' => $reservationId,
                'reference_code' => $reference,
                'method' => 'mpesa',
                'amount' => $amount,
                'phone' => $phone,
                'checkout_id' => $mpesaCheckoutRequestId,
                'merchant_id' => $mpesaMerchantRequestId,
                'status' => 'pending'
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'M-Pesa payment request sent successfully',
                'data' => [
                    'checkout_request_id' => $mpesaCheckoutRequestId,
                    'merchant_request_id' => $mpesaMerchantRequestId
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'M-Pesa payment failed: ' . $e->getMessage()
            ]);
        }
    }

    public function confirmFolioPayment(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager', 'service_agent', 'cashier', 'finance_manager']);
        
        $transactionId = (int)$request->input('transaction_id');
        $reservationId = (int)$request->input('reservation_id');
        
        if (!$transactionId || !$reservationId) {
            header('Location: ' . base_url('staff/dashboard/bookings/folio?reservation_id=' . $reservationId . '&error=Invalid%20request'));
            return;
        }
        
        // Get payment transaction
        $stmt = db()->prepare('SELECT * FROM payment_transactions WHERE id = :id AND reference_id = :reservation_id LIMIT 1');
        $stmt->execute(['id' => $transactionId, 'reservation_id' => $reservationId]);
        $payment = $stmt->fetch();
        
        if (!$payment) {
            header('Location: ' . base_url('staff/dashboard/bookings/folio?reservation_id=' . $reservationId . '&error=Payment%20transaction%20not%20found'));
            return;
        }
        
        // Get or create folio
        $folioRepo = new \App\Repositories\FolioRepository();
        $folio = $folioRepo->findByReservation($reservationId);
        
        if (!$folio) {
            $folioRepo->create($reservationId);
            $folio = $folioRepo->findByReservation($reservationId);
        }
        
        if ($folio) {
            // Add payment entry
            $description = 'M-Pesa Payment - ' . ($payment['reference_code'] ?? 'Folio Payment');
            if ($payment['mpesa_transaction_id']) {
                $description .= ' (TXN: ' . $payment['mpesa_transaction_id'] . ')';
            }
            $folioRepo->addEntry((int)$folio['id'], $description, (float)$payment['amount'], 'payment', 'mpesa');
            
            // Update payment transaction status
            $updateStmt = db()->prepare('UPDATE payment_transactions SET status = :status WHERE id = :id');
            $updateStmt->execute(['status' => 'completed', 'id' => $transactionId]);
            
            // Check if checkout was pending
            $folio = $folioRepo->findByReservation($reservationId);
            $checkoutPending = !empty($_GET['checkout_pending']);
            
            if ($folio && (float)$folio['balance'] <= 0 && $checkoutPending) {
                header('Location: ' . base_url('staff/dashboard/bookings/folio?reservation_id=' . $reservationId . '&success=1&balance_settled=1'));
            } else {
                header('Location: ' . base_url('staff/dashboard/bookings/folio?reservation_id=' . $reservationId . '&success=Payment%20confirmed'));
            }
        } else {
            header('Location: ' . base_url('staff/dashboard/bookings/folio?reservation_id=' . $reservationId . '&error=Could%20not%20process%20payment'));
        }
    }

    public function queryFolioPaymentStatus(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager', 'service_agent', 'cashier', 'finance_manager']);
        
        header('Content-Type: application/json');
        
        $checkoutRequestId = $request->input('checkout_request_id');
        $transactionId = (int)$request->input('transaction_id');
        
        if (empty($checkoutRequestId) || !$transactionId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Checkout request ID and transaction ID are required']);
            return;
        }
        
        try {
            $mpesaService = new \App\Services\Payments\MpesaService();
            $result = $mpesaService->queryStkStatus($checkoutRequestId);
            
            // Update payment transaction with latest status
            $status = ($result['result_code'] ?? '') === '0' ? 'completed' : 'pending';
            $stmt = db()->prepare('UPDATE payment_transactions SET status = :status WHERE id = :id');
            $stmt->execute(['status' => $status, 'id' => $transactionId]);
            
            if ($status === 'completed') {
                // Payment confirmed, add to folio
                $stmt = db()->prepare('SELECT * FROM payment_transactions WHERE id = :id LIMIT 1');
                $stmt->execute(['id' => $transactionId]);
                $payment = $stmt->fetch();
                
                if ($payment) {
                    $folioRepo = new \App\Repositories\FolioRepository();
                    $folio = $folioRepo->findByReservation((int)$payment['reference_id']);
                    
                    if (!$folio) {
                        $folioRepo->create((int)$payment['reference_id']);
                        $folio = $folioRepo->findByReservation((int)$payment['reference_id']);
                    }
                    
                    if ($folio) {
                        $description = 'M-Pesa Payment - ' . ($payment['reference_code'] ?? 'Folio Payment');
                        $folioRepo->addEntry((int)$folio['id'], $description, (float)$payment['amount'], 'payment', 'mpesa');
                    }
                }
            }
            
            echo json_encode([
                'success' => true,
                'status' => $status,
                'data' => $result['data'] ?? []
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to query status: ' . $e->getMessage()
            ]);
        }
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
            header('Location: ' . base_url('staff/dashboard/bookings/calendar-view?error=Invalid%20selection'));
            return;
        }

        if (!$this->rooms->isAvailable($roomId, $reservation['check_in'], $reservation['check_out'])) {
            header('Location: ' . base_url('staff/dashboard/bookings/calendar-view?error=Room%20not%20available'));
            return;
        }

        $this->reservations->updateStatus($reservationId, [
            'room_id' => $roomId,
            'room_status' => 'pending',
        ]);

        header('Location: ' . base_url('staff/dashboard/bookings/calendar-view?success=1'));
    }

    public function edit(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager', 'service_agent', 'cashier', 'finance_manager']);
        $reservationId = (int)$request->input('reservation_id');
        $reservation = $this->reservations->findById($reservationId);

        if (!$reservation) {
            header('Location: ' . base_url('staff/dashboard/bookings?error=Reservation%20not%20found'));
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
            header('Location: ' . base_url('staff/dashboard/bookings?error=Reservation%20not%20found'));
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
                    header('Location: ' . base_url('staff/dashboard/bookings/edit?reservation_id=' . $reservationId . '&error=Check-out%20must%20be%20after%20check-in'));
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
                // Single installation - no tenant filtering needed
                $stmt = db()->prepare($sql);
                $stmt->execute($params);
                
                if ((int)$stmt->fetchColumn() > 0) {
                    header('Location: ' . base_url('staff/dashboard/bookings/edit?reservation_id=' . $reservationId . '&error=Room%20not%20available%20for%20selected%20dates'));
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

            header('Location: ' . base_url('staff/dashboard/bookings?success=updated'));
        } catch (Exception $e) {
            header('Location: ' . base_url('staff/dashboard/bookings/edit?reservation_id=' . $reservationId . '&error=' . urlencode($e->getMessage())));
        }
    }

    public function confirmation(Request $request): void
    {
        $reference = trim($request->input('reference', ''));
        if (empty($reference)) {
            header('Location: ' . base_url('booking?error=Booking%20reference%20required'));
            return;
        }

        // Always fetch fresh data from database to get latest payment status
        $reservation = $this->reservations->findByReference($reference);
        if (!$reservation) {
            header('Location: ' . base_url('booking?error=Booking%20not%20found'));
            return;
        }

        $roomType = $this->roomTypes->find((int)$reservation['room_type_id']);
        $room = $reservation['room_id'] ? $this->rooms->find((int)$reservation['room_id']) : null;

        $this->view('website/booking/confirmation', [
            'reservation' => $reservation,
            'roomType' => $roomType,
            'room' => $room,
        ]);
    }

    protected function createPaymentTransaction(string $type, int $referenceId, string $referenceCode, string $method, float $amount, ?string $phone, ?string $checkoutRequestId, ?string $merchantRequestId): void
    {
        $stmt = db()->prepare('
            INSERT INTO payment_transactions (transaction_type, reference_id, reference_code, payment_method, amount, phone_number, checkout_request_id, merchant_request_id, status)
            VALUES (:type, :ref_id, :ref_code, :method, :amount, :phone, :checkout_id, :merchant_id, :status)
        ');
        $stmt->execute([
            'type' => $type,
            'ref_id' => $referenceId,
            'ref_code' => $referenceCode,
            'method' => $method,
            'amount' => $amount,
            'phone' => $phone,
            'checkout_id' => $checkoutRequestId,
            'merchant_id' => $merchantRequestId,
            'status' => 'pending',
        ]);
    }

    protected function updatePaymentTransactionReference(string $checkoutRequestId, int $referenceId, string $referenceCode): void
    {
        $stmt = db()->prepare('
            UPDATE payment_transactions 
            SET reference_id = :ref_id, reference_code = :ref_code
            WHERE checkout_request_id = :checkout_id
        ');
        $stmt->execute([
            'ref_id' => $referenceId,
            'ref_code' => $referenceCode,
            'checkout_id' => $checkoutRequestId,
        ]);
    }
}


