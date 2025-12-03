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
        $start = $request->input('check_in', date('Y-m-d'));
        $end = $request->input('check_out', date('Y-m-d', strtotime('+1 day')));
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

            if ($end < $start) {
                http_response_code(422);
                echo 'Check-out must be on or after check-in.';
                return;
            }

            // For same-day bookings, calculate as 1 night minimum
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

            // Determine booking status based on payment status
            // Only confirm booking if payment is successful (paid)
            // For pending/unpaid payments, booking remains pending until payment is confirmed
            $bookingStatus = ($paymentStatus === 'paid') ? 'confirmed' : 'pending';

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
                'status' => $bookingStatus,
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
                'payment_status' => $paymentStatus,
            ];
            
            // If payment is pending, notify staff to follow up
            if ($paymentStatus !== 'paid') {
                $pendingMessage = sprintf(
                    '⚠️ Payment Pending: %s booked from %s to %s. Payment status: %s. Please follow up to complete payment.',
                    $guestName,
                    $checkIn,
                    $checkOut,
                    ucfirst($paymentStatus)
                );
                
                // Notify receptionist and finance manager for payment follow-up
                $this->notifications->notifyRole('receptionist', 'Payment Pending - Follow Up Required', $pendingMessage, $notificationPayload);
                $this->notifications->notifyRole('finance_manager', 'Payment Pending - Follow Up Required', $pendingMessage, $notificationPayload);
                $this->notifications->notifyRole('cashier', 'Payment Pending - Follow Up Required', $pendingMessage, $notificationPayload);
                $this->notifications->notifyRole('admin', 'Payment Pending - Follow Up Required', $pendingMessage, $notificationPayload);
            } else {
                // Payment completed - notify for room preparation
                $this->notifications->notifyRole('housekeeping', 'New reservation', $message, $notificationPayload);
                $this->notifications->notifyRole('receptionist', 'New reservation', $message, $notificationPayload);
                $this->notifications->notifyRole('operation_manager', 'New reservation', $message, $notificationPayload);
                $this->notifications->notifyRole('admin', 'New reservation', $message, $notificationPayload);
            }

            // Send appropriate email based on payment status
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
                        'status' => $bookingStatus,
                        'payment_status' => $paymentStatus,
                        'payment_method' => $paymentMethod,
                        'room_type_name' => $roomType['name'] ?? 'Room Type',
                        'room_label' => $roomLabel,
                        'special_requests' => $specialRequests,
                        'mpesa_phone' => $mpesaPhone,
                    ];
                    
                    $guestData = [
                        'guest_name' => $guestName,
                        'guest_email' => $guestEmail,
                        'guest_phone' => $guestPhone,
                    ];
                    
                    // Send confirmation email only if payment is paid
                    // Send payment completion email if payment is pending
                    if ($paymentStatus === 'paid') {
                        $this->email->sendBookingConfirmation($bookingData, $guestData);
                    } else {
                        $this->email->sendPaymentCompletionEmail($bookingData, $guestData);
                    }
                } catch (\Exception $e) {
                    // Log error but don't fail the booking
                    error_log('Failed to send booking email: ' . $e->getMessage());
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

        if ($end < $start) {
            http_response_code(422);
            echo json_encode(['error' => 'Check-out must be on or after check-in.']);
            return;
        }

        // For same-day bookings, calculate as 1 night minimum
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
        Auth::requireRoles(['director', 'admin', 'operation_manager', 'service_agent', 'receptionist', 'cashier', 'finance_manager']);
        $user = Auth::user();
        $filter = $request->input('filter', 'upcoming');
        
        $start = $this->sanitizeDate($request->input('start'));
        $end = $this->sanitizeDate($request->input('end'));
        
        $reservations = $this->reservations->all($filter, 50, $start, $end);

        $this->view('dashboard/bookings/index', [
            'user' => $user,
            'roleConfig' => [
                'label' => 'Booking Dashboard',
            ],
            'reservations' => $reservations,
            'filter' => $filter,
            'filters' => [
                'start' => $start ?? date('Y-m-01'),
                'end' => $end ?? date('Y-m-d'),
            ],
        ]);
    }

    protected function sanitizeDate(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $ts = strtotime($value);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    public function checkIn(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'operation_manager', 'service_agent', 'receptionist', 'cashier', 'finance_manager']);
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
        Auth::requireRoles(['director', 'admin', 'operation_manager', 'service_agent', 'receptionist', 'cashier', 'finance_manager']);
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

    public function cancel(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'operation_manager', 'service_agent', 'receptionist', 'cashier', 'finance_manager']);
        
        $reservationId = (int)$request->input('reservation_id');
        $reason = trim($request->input('reason', ''));
        
        if (empty($reason)) {
            header('Location: ' . base_url('staff/dashboard/bookings?error=' . urlencode('Cancellation reason is required')));
            return;
        }

        $reservation = $this->reservations->findById($reservationId);
        if (!$reservation) {
            header('Location: ' . base_url('staff/dashboard/bookings?error=' . urlencode('Reservation not found')));
            return;
        }

        // Prevent cancellation if already checked in
        if ($reservation['check_in_status'] === 'checked_in' && $reservation['room_status'] === 'in_house') {
            header('Location: ' . base_url('staff/dashboard/bookings?error=' . urlencode('Cannot cancel a checked-in reservation. Please check out the guest first.')));
            return;
        }

        try {
            $user = Auth::user();
            $userId = (int)($user['id'] ?? 0);
            
            // Handle cancellation: process refunds and update room availability
            $this->handleBookingCancellation($reservation, $userId, $reason);
            
            // Update reservation status
            $this->reservations->updateStatus($reservationId, [
                'status' => 'cancelled',
                'check_in_status' => 'scheduled', // Reset check-in status
                'room_status' => 'pending', // Reset room status
            ]);
            
            // If room was assigned, release it
            if (!empty($reservation['room_id'])) {
                $this->rooms->updateStatus((int)$reservation['room_id'], 'available');
            }
            
            // Update folio if exists
            $folio = $this->folios->findByReservation($reservationId);
            if ($folio) {
                // Add cancellation entry to folio
                $this->folios->addEntry(
                    (int)$folio['id'],
                    'Booking Cancellation',
                    -(float)($reservation['total_amount'] ?? 0),
                    'charge',
                    'cancellation',
                    'Booking cancelled: ' . $reason
                );
            }
            
            // Notify relevant roles
            $this->notifications->notifyRole('operation_manager', 'Booking Cancelled',
                sprintf('Booking %s has been cancelled. Reason: %s', $reservation['reference'], $reason),
                ['reservation_id' => $reservationId, 'reference' => $reservation['reference']]
            );
            $this->notifications->notifyRole('finance_manager', 'Booking Cancelled',
                sprintf('Booking %s has been cancelled. Reason: %s', $reservation['reference'], $reason),
                ['reservation_id' => $reservationId, 'reference' => $reservation['reference']]
            );
            
            // Send email notification if email exists
            if (!empty($reservation['guest_email'])) {
                try {
                    $this->email->send(
                        $reservation['guest_email'],
                        'Booking Cancellation - ' . $reservation['reference'],
                        sprintf(
                            "Dear %s,\n\nYour booking %s has been cancelled.\n\nReason: %s\n\nIf you have any questions, please contact us.\n\nBest regards,\n%s",
                            $reservation['guest_name'],
                            $reservation['reference'],
                            $reason,
                            settings('brand_name', 'Hotel')
                        )
                    );
                } catch (Exception $e) {
                    error_log('Failed to send cancellation email: ' . $e->getMessage());
                }
            }
            
            header('Location: ' . base_url('staff/dashboard/bookings?success=' . urlencode('Booking cancelled successfully')));
        } catch (Exception $e) {
            header('Location: ' . base_url('staff/dashboard/bookings?error=' . urlencode($e->getMessage())));
        }
    }

    /**
     * Handle booking cancellation: process refunds
     */
    protected function handleBookingCancellation(array $reservation, int $userId, string $reason): void
    {
        $wasPaid = in_array($reservation['payment_status'] ?? '', ['paid', 'partial']);
        $totalAmount = (float)($reservation['total_amount'] ?? 0);
        $depositAmount = (float)($reservation['deposit_amount'] ?? 0);
        
        if ($wasPaid && ($totalAmount > 0 || $depositAmount > 0)) {
            $refundAmount = $totalAmount > 0 ? $totalAmount : $depositAmount;
            $paymentMethod = $reservation['payment_method'] ?? 'unknown';
            
            // Update payment status to refunded
            $this->reservations->updateStatus((int)$reservation['id'], [
                'payment_status' => 'refunded'
            ]);
            
            // Log refund in payments table if it exists
            try {
                $db = db();
                $stmt = $db->prepare('
                    INSERT INTO payments (reference, amount, payment_method, status, notes, created_by)
                    VALUES (:ref, :amount, :method, "refunded", :notes, :user)
                ');
                $stmt->execute([
                    'ref' => $reservation['reference'],
                    'amount' => $refundAmount,
                    'method' => $paymentMethod,
                    'notes' => 'Refund for cancelled booking: ' . $reason,
                    'user' => $userId,
                ]);
            } catch (\Exception $e) {
                // Payments table might not exist or have different structure
                error_log('Could not log refund payment: ' . $e->getMessage());
            }
            
            // If M-Pesa payment, note that refund needs to be processed manually
            if ($paymentMethod === 'mpesa' && !empty($reservation['mpesa_transaction_id'])) {
                $this->notifications->notifyRole('finance_manager', 'M-Pesa Refund Required',
                    sprintf(
                        'Booking %s was cancelled and requires M-Pesa refund of KES %s. Transaction ID: %s',
                        $reservation['reference'],
                        number_format($refundAmount, 2),
                        $reservation['mpesa_transaction_id']
                    ),
                    ['reservation_id' => (int)$reservation['id'], 'reference' => $reservation['reference']]
                );
            }
        }
    }

    public function folio(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'operation_manager', 'service_agent', 'receptionist', 'cashier', 'finance_manager']);
        
        // Support both reservation_id and ref (reference) parameters
        $reservationId = (int)$request->input('reservation_id');
        $ref = trim($request->input('ref', ''));
        
        $reservation = null;
        if ($reservationId > 0) {
            $reservation = $this->reservations->findById($reservationId);
        } elseif ($ref) {
            $reservation = $this->reservations->findByReference($ref);
            if ($reservation) {
                $reservationId = (int)$reservation['id'];
            }
        }

        if (!$reservation) {
            http_response_code(404);
            echo 'Reservation not found';
            return;
        }

        $folio = $this->folios->findByReservation($reservationId);
        if (!$folio) {
            $folioId = $this->folios->create(
                $reservationId,
                $reservation['guest_email'] ?? null,
                $reservation['guest_phone'] ?? null,
                $reservation['guest_name'] ?? null
            );
            // Add room charge
            $this->folios->addEntry($folioId, 'Room charges', (float)$reservation['total_amount'], 'charge', 'room');
            
            // If booking was already paid, add the payment to the folio
            if (!empty($reservation['payment_status']) && $reservation['payment_status'] === 'paid' && !empty($reservation['total_amount'])) {
                $paymentMethod = $reservation['payment_method'] ?? 'unknown';
                $paymentDescription = 'Booking Payment';
                if ($paymentMethod === 'mpesa') {
                    $paymentDescription = 'M-Pesa Payment - Booking';
                    if (!empty($reservation['mpesa_transaction_id'])) {
                        $paymentDescription .= ' (Transaction: ' . $reservation['mpesa_transaction_id'] . ')';
                    }
                } elseif ($paymentMethod === 'pay_on_arrival') {
                    $paymentDescription = 'Pay on Arrival - Booking';
                }
                
                $this->folios->addEntry($folioId, $paymentDescription, (float)$reservation['total_amount'], 'payment', $paymentMethod);
            }
            
            $folio = $this->folios->findByReservation($reservationId);
        } else {
            // Folio exists - check if booking payment needs to be synced
            $entries = $this->folios->entries($folio['id']);
            $hasBookingPayment = false;
            foreach ($entries as $entry) {
                if ($entry['type'] === 'payment' && 
                    (strpos($entry['description'], 'Booking Payment') !== false || 
                     strpos($entry['description'], 'M-Pesa Payment - Booking') !== false)) {
                    $hasBookingPayment = true;
                    break;
                }
            }
            
            // If booking is paid but folio doesn't have the payment entry, add it
            if (!$hasBookingPayment && !empty($reservation['payment_status']) && $reservation['payment_status'] === 'paid' && !empty($reservation['total_amount'])) {
                $paymentMethod = $reservation['payment_method'] ?? 'unknown';
                $paymentDescription = 'Booking Payment';
                if ($paymentMethod === 'mpesa') {
                    $paymentDescription = 'M-Pesa Payment - Booking';
                    if (!empty($reservation['mpesa_transaction_id'])) {
                        $paymentDescription .= ' (Transaction: ' . $reservation['mpesa_transaction_id'] . ')';
                    }
                } elseif ($paymentMethod === 'pay_on_arrival') {
                    $paymentDescription = 'Pay on Arrival - Booking';
                }
                
                $this->folios->addEntry($folio['id'], $paymentDescription, (float)$reservation['total_amount'], 'payment', $paymentMethod);
                
                // Reload folio to get updated balance (recalculate will sync booking status automatically)
                $folio = $this->folios->findByReservation($reservationId);
            } else {
                // Ensure folio is recalculated and booking status is synced
                $this->folios->recalculate((int)$folio['id']);
                $folio = $this->folios->findByReservation($reservationId);
            }
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
        Auth::requireRoles(['director', 'admin', 'operation_manager', 'service_agent', 'receptionist', 'cashier', 'finance_manager']);
        $reservationId = (int)$request->input('reservation_id');
        $reservation = $this->reservations->findById($reservationId);

        if (!$reservation) {
            header('Location: ' . base_url('staff/dashboard/bookings?error=Invalid%20reservation'));
            return;
        }

        $folio = $this->folios->findByReservation($reservationId);
        if (!$folio) {
            $folioId = $this->folios->create(
                $reservationId,
                $reservation['guest_email'] ?? null,
                $reservation['guest_phone'] ?? null,
                $reservation['guest_name'] ?? null
            );
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
        Auth::requireRoles(['director', 'admin', 'operation_manager', 'service_agent', 'receptionist', 'cashier', 'finance_manager']);
        
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
            $stmt = db()->prepare('
                INSERT INTO payment_transactions (transaction_type, reference_id, reference_code, payment_method, amount, phone_number, checkout_request_id, merchant_request_id, status)
                VALUES (:type, :reference_id, :reference_code, :method, :amount, :phone, :checkout_id, :merchant_id, :status)
            ');
            $stmt->execute([
                'type' => 'folio_payment',
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
        Auth::requireRoles(['director', 'admin', 'operation_manager', 'service_agent', 'receptionist', 'cashier', 'finance_manager']);
        
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
        Auth::requireRoles(['director', 'admin', 'operation_manager', 'service_agent', 'receptionist', 'cashier', 'finance_manager']);
        
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
        Auth::requireRoles(['director', 'admin', 'operation_manager', 'service_agent', 'receptionist', 'cashier']);
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
        Auth::requireRoles(['director', 'admin', 'operation_manager', 'service_agent', 'receptionist', 'cashier']);
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
        Auth::requireRoles(['director', 'admin', 'operation_manager', 'service_agent', 'receptionist']);
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
        Auth::requireRoles(['director', 'admin', 'operation_manager', 'service_agent', 'receptionist', 'cashier', 'finance_manager']);
        
        // Support both reservation_id and ref (reference) parameters
        $reservationId = (int)$request->input('reservation_id');
        $ref = trim($request->input('ref', ''));
        
        $reservation = null;
        if ($reservationId > 0) {
            $reservation = $this->reservations->findById($reservationId);
        } elseif ($ref) {
            $reservation = $this->reservations->findByReference($ref);
            if ($reservation) {
                $reservationId = (int)$reservation['id'];
            }
        }

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
                    // Add room_type_name to current room if not present
                    if (!isset($currentRoom['room_type_name'])) {
                        $roomType = $this->roomTypes->find((int)$currentRoom['room_type_id']);
                        $currentRoom['room_type_name'] = $roomType['name'] ?? 'Room Type';
                    }
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
        Auth::requireRoles(['director', 'admin', 'operation_manager', 'service_agent', 'receptionist', 'cashier', 'finance_manager']);
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
                if ($end < $start) {
                    header('Location: ' . base_url('staff/dashboard/bookings/edit?reservation_id=' . $reservationId . '&error=Check-out%20must%20be%20on%20or%20after%20check-in'));
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

        // Check if this is a print request - use receipt format
        $print = $request->input('print') === '1' || $request->input('receipt') === '1';
        
        if ($print) {
            // Use the same receipt format as the download receipt
            $this->renderReceiptForConfirmation($reservation, $roomType, $room);
            return;
        }

        $this->view('website/booking/confirmation', [
            'reservation' => $reservation,
            'roomType' => $roomType,
            'room' => $room,
        ]);
    }
    
    /**
     * Render receipt for confirmation page (same format as download receipt)
     */
    protected function renderReceiptForConfirmation(array $booking, ?array $roomType, ?array $room): void
    {
        $brandName = settings('branding.name', 'Hotela');
        $brandAddress = settings('branding.address', '');
        $brandPhone = settings('branding.contact_phone', '');
        $brandEmail = settings('branding.contact_email', '');
        
        $checkIn = new \DateTimeImmutable($booking['check_in'] ?? date('Y-m-d'));
        $checkOut = new \DateTimeImmutable($booking['check_out'] ?? date('Y-m-d'));
        $nights = max(1, $checkIn->diff($checkOut)->days);
        
        $reference = $booking['reference'] ?? 'N/A';
        $guestName = $booking['guest_name'] ?? 'Guest';
        $guestEmail = $booking['guest_email'] ?? '';
        $guestPhone = $booking['guest_phone'] ?? '';
        $totalAmount = (float)($booking['total_amount'] ?? 0);
        $paymentStatus = $booking['payment_status'] ?? 'unpaid';
        $paymentMethod = $booking['payment_method'] ?? 'pay_on_arrival';
        $roomTypeName = $roomType['name'] ?? 'Room Type';
        $roomNumber = $room ? ($room['room_number'] ?? $room['display_name'] ?? '') : '';
        
        $nightlyRate = $nights > 0 ? $totalAmount / $nights : $totalAmount;
        
        // Build QR code URL - link to online receipt
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        $receiptPath = base_url('guest/booking?ref=' . urlencode($reference) . '&download=receipt');
        $receiptUrl = $scheme . '://' . $host . $receiptPath;
        $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&margin=1&data=' . urlencode($receiptUrl);
        
        $businessAddressHtml = $brandAddress ? '<p>' . htmlspecialchars($brandAddress) . '</p>' : '';
        $businessPhoneHtml = $brandPhone ? '<p>Tel: ' . htmlspecialchars($brandPhone) . '</p>' : '';
        $businessEmailHtml = $brandEmail ? '<p>Email: ' . htmlspecialchars($brandEmail) . '</p>' : '';
        $guestEmailHtml = $guestEmail ? '<div class="detail-row"><span class="detail-label">Email:</span><span class="detail-value">' . htmlspecialchars($guestEmail) . '</span></div>' : '';
        $guestPhoneHtml = $guestPhone ? '<div class="detail-row"><span class="detail-label">Phone:</span><span class="detail-value">' . htmlspecialchars($guestPhone) . '</span></div>' : '';
        $roomNumberHtml = $roomNumber ? '<br><small>Room: ' . htmlspecialchars($roomNumber) . '</small>' : '';
        $paymentMethodText = $paymentMethod === 'mpesa' ? 'M-Pesa' : 'Pay on Arrival';
        
        // Use the same HTML structure as the download receipt
        $html = $this->buildReceiptHtml($brandName, $brandAddress, $brandPhone, $brandEmail, $reference, $guestName, $guestEmail, $guestPhone, $checkIn, $checkOut, $nights, $roomTypeName, $roomNumber, $nightlyRate, $totalAmount, $paymentMethodText, $paymentStatus, $qrCodeUrl, $businessAddressHtml, $businessPhoneHtml, $businessEmailHtml, $guestEmailHtml, $guestPhoneHtml, $roomNumberHtml);
        
        header('Content-Type: text/html; charset=UTF-8');
        echo $html;
    }
    
    /**
     * Build receipt HTML (shared with GuestPortalController format)
     */
    protected function buildReceiptHtml(string $brandName, string $brandAddress, string $brandPhone, string $brandEmail, string $reference, string $guestName, string $guestEmail, string $guestPhone, \DateTimeImmutable $checkIn, \DateTimeImmutable $checkOut, int $nights, string $roomTypeName, string $roomNumber, float $nightlyRate, float $totalAmount, string $paymentMethodText, string $paymentStatus, string $qrCodeUrl, string $businessAddressHtml, string $businessPhoneHtml, string $businessEmailHtml, string $guestEmailHtml, string $guestPhoneHtml, string $roomNumberHtml): string
    {
        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($brandName) . ' - Receipt</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif;
            line-height: 1.6;
            color: #1e293b;
            background: white;
            padding: 2rem;
        }
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            background: white;
            page-break-inside: avoid;
        }
        .receipt-header {
            text-align: center;
            border-bottom: 2px solid #1e293b;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }
        .receipt-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0;
        }
        .business-info {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #475569;
            font-size: 0.85rem;
        }
        .business-info p {
            margin: 0.2rem 0;
        }
        .receipt-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .detail-section h3 {
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            margin-bottom: 0.75rem;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 0.5rem;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }
        .detail-label {
            color: #64748b;
        }
        .detail-value {
            font-weight: 600;
            color: #1e293b;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1.5rem 0;
        }
        .items-table th {
            background: #f8fafc;
            padding: 0.75rem;
            text-align: left;
            font-weight: 600;
            color: #475569;
            border-bottom: 2px solid #e2e8f0;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .items-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #e2e8f0;
        }
        .items-table tr:last-child td {
            border-bottom: none;
        }
        .text-right {
            text-align: right;
        }
        .total-section {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid #1e293b;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 1.125rem;
            margin-bottom: 0.5rem;
        }
        .total-row.grand-total {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 2px solid #e2e8f0;
        }
        .payment-info {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 8px;
            margin: 1.5rem 0;
        }
        .payment-info h3 {
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            margin-bottom: 1rem;
        }
        .footer {
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            color: #64748b;
            font-size: 0.8rem;
        }
        .footer p {
            margin: 0.5rem 0;
        }
        @media print {
            @page {
                size: A4;
                margin: 0.3in;
            }
            * {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            body { 
                padding: 0 !important;
                margin: 0 !important;
                font-size: 11pt;
            }
            .receipt-container { 
                max-width: 100% !important;
                padding: 0.5rem !important;
                page-break-inside: avoid !important;
                page-break-after: avoid !important;
                height: auto !important;
            }
            .receipt-header {
                page-break-inside: avoid !important;
                page-break-after: avoid !important;
                margin-bottom: 0.75rem !important;
                padding-bottom: 0.75rem !important;
            }
            .receipt-header h1 {
                font-size: 1.25rem !important;
                margin-bottom: 0 !important;
            }
            .business-info {
                margin-bottom: 0.75rem !important;
                font-size: 0.75rem !important;
            }
            .business-info p {
                margin: 0.15rem 0 !important;
            }
            .receipt-details {
                page-break-inside: avoid !important;
                margin-bottom: 0.75rem !important;
                gap: 1rem !important;
            }
            .detail-section h3 {
                font-size: 0.75rem !important;
                margin-bottom: 0.5rem !important;
                padding-bottom: 0.25rem !important;
            }
            .detail-row {
                margin-bottom: 0.35rem !important;
                font-size: 0.85rem !important;
            }
            .items-table {
                page-break-inside: avoid !important;
                margin: 0.75rem 0 !important;
                font-size: 0.8rem !important;
            }
            .items-table th,
            .items-table td {
                padding: 0.4rem !important;
            }
            .items-table thead {
                display: table-header-group;
            }
            .items-table tbody {
                display: table-row-group;
            }
            .items-table tr {
                page-break-inside: avoid !important;
            }
            .total-section {
                page-break-inside: avoid !important;
                page-break-before: avoid !important;
                margin-top: 0.75rem !important;
                padding-top: 0.75rem !important;
            }
            .total-row {
                font-size: 0.9rem !important;
                margin-bottom: 0.35rem !important;
            }
            .total-row.grand-total {
                font-size: 1.1rem !important;
                margin-top: 0.5rem !important;
                padding-top: 0.5rem !important;
            }
            .payment-info {
                page-break-inside: avoid !important;
                margin: 0.75rem 0 !important;
                padding: 0.75rem !important;
            }
            .payment-info h3 {
                font-size: 0.75rem !important;
                margin-bottom: 0.5rem !important;
            }
            .footer {
                page-break-inside: avoid !important;
                margin-top: 0.75rem !important;
                padding-top: 0.75rem !important;
                font-size: 0.75rem !important;
            }
            .footer p {
                margin: 0.35rem 0 !important;
            }
            .footer img {
                width: 120px !important;
                height: 120px !important;
                max-width: 120px !important;
                max-height: 120px !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .footer div[style*="inline-block"] {
                page-break-inside: avoid !important;
                margin: 0.75rem 0 !important;
                padding: 0.5rem !important;
            }
            .footer div[style*="margin: 1.5rem"] {
                margin: 0.75rem 0 !important;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt-header">
            <h1>' . htmlspecialchars($brandName) . '</h1>
        </div>
        
        <div class="business-info">
            ' . $businessAddressHtml . '
            ' . $businessPhoneHtml . '
            ' . $businessEmailHtml . '
        </div>
        
        <div class="receipt-details">
            <div class="detail-section">
                <h3>Booking Information</h3>
                <div class="detail-row">
                    <span class="detail-label">Reference:</span>
                    <span class="detail-value">' . htmlspecialchars($reference) . '</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date:</span>
                    <span class="detail-value">' . $checkIn->format('F j, Y') . '</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Check-in:</span>
                    <span class="detail-value">' . $checkIn->format('F j, Y') . '</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Check-out:</span>
                    <span class="detail-value">' . $checkOut->format('F j, Y') . '</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Nights:</span>
                    <span class="detail-value">' . $nights . '</span>
                </div>
            </div>
            
            <div class="detail-section">
                <h3>Guest Information</h3>
                <div class="detail-row">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value">' . htmlspecialchars($guestName) . '</span>
                </div>
                ' . $guestEmailHtml . '
                ' . $guestPhoneHtml . '
            </div>
        </div>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Nights</th>
                    <th class="text-right">Rate</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>' . htmlspecialchars($roomTypeName) . '</strong>
                        ' . $roomNumberHtml . '
                    </td>
                    <td class="text-right">' . $nights . '</td>
                    <td class="text-right">KES ' . number_format($nightlyRate, 2) . '</td>
                    <td class="text-right"><strong>KES ' . number_format($totalAmount, 2) . '</strong></td>
                </tr>
            </tbody>
        </table>
        
        <div class="total-section">
            <div class="total-row">
                <span>Subtotal:</span>
                <span>KES ' . number_format($totalAmount, 2) . '</span>
            </div>
            <div class="total-row grand-total">
                <span>Total Amount:</span>
                <span>KES ' . number_format($totalAmount, 2) . '</span>
            </div>
        </div>
        
        <div class="payment-info">
            <h3>Payment Information</h3>
            <div class="detail-row">
                <span class="detail-label">Payment Method:</span>
                <span class="detail-value">' . htmlspecialchars($paymentMethodText) . '</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Payment Status:</span>
                <span class="detail-value">' . ucfirst($paymentStatus) . '</span>
            </div>
        </div>
        
        <div class="footer">
            <p>Thank you for your booking!</p>
            <p>This is an official receipt for your reservation.</p>
            <div style="margin: 1.5rem 0; text-align: center;">
                <p style="margin-bottom: 0.75rem; font-size: 0.9rem; color: #64748b; font-weight: 500;">Scan to view booking details</p>
                <div style="display: inline-block; padding: 1rem; background: white; border: 2px solid #e2e8f0; border-radius: 8px;">
                    <img src="' . htmlspecialchars($qrCodeUrl, ENT_QUOTES, 'UTF-8') . '" alt="QR Code - Booking Details" style="width: 200px; height: 200px; display: block; max-width: 100%;">
                </div>
            </div>
            <p style="margin-top: 1rem; font-size: 0.75rem; color: #64748b;">Generated on ' . date('F j, Y \a\t g:i A') . '</p>
        </div>
    </div>
    
    <script>
        // Auto-print when opened
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>';
    }

    /**
     * API endpoint to check payment status for real-time updates
     */
    public function checkPaymentStatus(Request $request): void
    {
        header('Content-Type: application/json');
        
        $reference = trim($request->input('reference', ''));
        if (empty($reference)) {
            http_response_code(400);
            echo json_encode(['error' => 'Booking reference required']);
            return;
        }

        $reservation = $this->reservations->findByReference($reference);
        if (!$reservation) {
            http_response_code(404);
            echo json_encode(['error' => 'Booking not found']);
            return;
        }

        echo json_encode([
            'success' => true,
            'payment_status' => $reservation['payment_status'] ?? 'unpaid',
            'mpesa_status' => $reservation['mpesa_status'] ?? null,
            'booking_status' => $reservation['status'] ?? 'pending',
            'mpesa_transaction_id' => $reservation['mpesa_transaction_id'] ?? null,
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

    public function guests(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'operation_manager', 'service_agent', 'receptionist', 'cashier', 'finance_manager']);

        // Get currently checked-in guests
        $checkedInGuests = $this->reservations->checkedInGuests();
        
        // Get upcoming arrivals (today and tomorrow)
        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        
        // Get all upcoming reservations
        $allUpcoming = $this->reservations->upcoming(50);
        
        // Filter for today and tomorrow arrivals (not yet checked in)
        $todayArrivals = [];
        $tomorrowArrivals = [];
        
        foreach ($allUpcoming as $res) {
            $checkInDate = $res['check_in'] ?? '';
            $checkInStatus = $res['check_in_status'] ?? '';
            
            if ($checkInDate === $today && $checkInStatus !== 'checked_in') {
                $todayArrivals[] = $res;
            } elseif ($checkInDate === $tomorrow && $checkInStatus !== 'checked_in') {
                $tomorrowArrivals[] = $res;
            }
        }

        $this->view('dashboard/guests/index', [
            'checkedInGuests' => $checkedInGuests ?: [],
            'todayArrivals' => $todayArrivals,
            'tomorrowArrivals' => $tomorrowArrivals,
        ]);
    }

    public function invoices(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'operation_manager', 'receptionist', 'cashier', 'finance_manager']);

        $status = $request->input('status', '');
        $startDate = $request->input('start', '');
        $endDate = $request->input('end', '');
        
        // Get all folios with reservation details
        $invoices = $this->folios->all(
            $status ?: null,
            $startDate ?: null,
            $endDate ?: null,
            200
        );

        // Calculate summary statistics
        $totalInvoices = count($invoices);
        $totalAmount = array_sum(array_column($invoices, 'total'));
        $totalBalance = array_sum(array_column($invoices, 'balance'));
        $openCount = count(array_filter($invoices, fn($inv) => ($inv['status'] ?? '') === 'open'));
        $closedCount = count(array_filter($invoices, fn($inv) => ($inv['status'] ?? '') === 'closed'));

        $this->view('dashboard/invoices/index', [
            'invoices' => $invoices,
            'status' => $status,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totalInvoices' => $totalInvoices,
            'totalAmount' => $totalAmount,
            'totalBalance' => $totalBalance,
            'openCount' => $openCount,
            'closedCount' => $closedCount,
        ]);
    }
}


