<?php

namespace App\Services\PMS;

use App\Repositories\FolioRepository;
use App\Repositories\HousekeepingRepository;
use App\Repositories\ReservationRepository;
use App\Repositories\RoomRepository;
use App\Services\Notifications\NotificationService;
use App\Support\Auth;
use RuntimeException;

class CheckInService
{
    protected ReservationRepository $reservations;
    protected FolioRepository $folios;
    protected NotificationService $notifications;
    protected RoomRepository $rooms;
    protected HousekeepingRepository $housekeeping;

    public function __construct()
    {
        $this->reservations = new ReservationRepository();
        $this->folios = new FolioRepository();
        $this->notifications = new NotificationService();
        $this->rooms = new RoomRepository();
        $this->housekeeping = new HousekeepingRepository();
    }

    public function checkIn(int $reservationId): void
    {
        $reservation = $this->reservations->findById($reservationId);

        if (!$reservation) {
            throw new RuntimeException('Reservation not found');
        }

        if (!$reservation['room_id']) {
            throw new RuntimeException('Assign a room before check-in');
        }

        // Check if payment is confirmed before allowing check-in
        $folio = $this->folios->findByReservation($reservationId);
        if ($folio) {
            $balance = (float)($folio['balance'] ?? 0);
            if ($balance > 0) {
                throw new RuntimeException(sprintf(
                    'Payment must be confirmed before check-in. Outstanding balance: KES %s',
                    number_format($balance, 2)
                ));
            }
        } else {
            // If no folio exists yet, check if booking payment status is paid
            // If not paid and total_amount > 0, payment is required
            $paymentStatus = $reservation['payment_status'] ?? 'unpaid';
            $totalAmount = (float)($reservation['total_amount'] ?? 0);
            if ($totalAmount > 0 && !in_array($paymentStatus, ['paid', 'partial'])) {
                throw new RuntimeException('Payment must be confirmed before check-in. Please process payment first.');
            }
        }

        // Re-fetch folio after validation
        $folio = $this->folios->findByReservation($reservationId);
        if (!$folio) {
            $folioId = $this->folios->create(
                $reservationId,
                $reservation['guest_email'] ?? null,
                $reservation['guest_phone'] ?? null,
                $reservation['guest_name'] ?? null
            );
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
        }

        $this->reservations->updateStatus($reservationId, [
            'check_in_status' => 'checked_in',
            'room_status' => 'in_house',
        ]);

        $roomId = (int)$reservation['room_id'];
        $user = Auth::check() ? Auth::user() : null;
        $staffId = $user['id'] ?? null;
        
        // Update room status with logging
        $this->housekeeping->updateRoomStatus($roomId, 'occupied', $staffId, 'Guest checked in', $reservationId);
        
        // Deactivate any DND status for this room
        if ($this->housekeeping->isRoomDND($roomId)) {
            $this->housekeeping->setDNDStatus($roomId, false, $reservationId, $staffId, 'Guest checked in - DND deactivated');
        }

        $this->notifications->notifyRole('housekeeping', 'Guest checked in', sprintf(
            '%s checked into %s. Prepare turn-down service.',
            $reservation['guest_name'],
            $reservation['room_number'] ?? $reservation['room_type_name']
        ), [
            'room_id' => $roomId,
        ]);
    }

    public function checkOut(int $reservationId): void
    {
        $reservation = $this->reservations->findById($reservationId);

        if (!$reservation) {
            throw new RuntimeException('Reservation not found');
        }

        $folio = $this->folios->findByReservation($reservationId);
        if ($folio && (float)$folio['balance'] > 0) {
            // Redirect to folio payment page instead of throwing error
            $balance = (float)$folio['balance'];
            throw new \App\Exceptions\OutstandingBalanceException($reservationId, $balance);
        }

        $this->reservations->updateStatus($reservationId, [
            'check_in_status' => 'checked_out',
            'room_status' => 'departed',
            'status' => 'checked_out',
        ]);

        // Auto-assign housekeeping task for room cleaning
        if ($reservation['room_id']) {
            try {
                $workflowService = new \App\Services\Workflow\WorkflowService();
                $roomNumber = $reservation['room_number'] ?? $reservation['display_name'] ?? 'Room ' . $reservation['room_id'];
                $workflowService->assignHousekeepingTask(
                    (int)$reservation['room_id'],
                    $roomNumber,
                    $reservationId
                );
            } catch (\Exception $e) {
                // Log error but don't fail checkout
                error_log('Failed to assign housekeeping task: ' . $e->getMessage());
            }
        }

        // Send checkout follow-up email
        if (!empty($reservation['guest_email'])) {
            try {
                $emailService = new \App\Services\Email\EmailService();
                $bookingData = [
                    'reference' => $reservation['reference'] ?? '',
                    'check_in' => $reservation['check_in'] ?? '',
                    'check_out' => $reservation['check_out'] ?? '',
                ];
                $guestData = [
                    'guest_name' => $reservation['guest_name'] ?? 'Guest',
                    'guest_email' => $reservation['guest_email'] ?? '',
                    'guest_phone' => $reservation['guest_phone'] ?? '',
                ];
                $emailService->sendCheckoutFollowUp($bookingData, $guestData);
            } catch (\Exception $e) {
                // Log error but don't fail checkout
                error_log('Failed to send checkout follow-up email: ' . $e->getMessage());
            }
        }

        if ($reservation['room_id']) {
            $roomId = (int)$reservation['room_id'];
            $user = Auth::check() ? Auth::user() : null;
            $staffId = $user['id'] ?? null;
            $roomNumber = $reservation['room_number'] ?? $reservation['display_name'] ?? 'Room ' . $roomId;
            
            // Auto-assign housekeeping task via workflow system
            try {
                $workflowService = new \App\Services\Workflow\WorkflowService();
                $workflowService->assignHousekeepingTask($roomId, $roomNumber, $reservationId);
            } catch (\Exception $e) {
                // Log error but continue with housekeeping module task creation
                error_log('Failed to create workflow housekeeping task: ' . $e->getMessage());
            }
            
            // Also create housekeeping module task for compatibility
            try {
                $taskId = $this->housekeeping->createTask([
                    'room_id' => $roomId,
                    'task_type' => 'cleaning',
                    'status' => 'pending',
                    'priority' => 'normal',
                    'scheduled_date' => date('Y-m-d'),
                    'notes' => sprintf('Guest %s checked out. Room needs cleaning.', $reservation['guest_name']),
                    'created_by' => $staffId,
                ]);
                
                // Mark room as dirty (as per housekeeping module requirements) with task reference
                $this->housekeeping->updateRoomStatus($roomId, 'dirty', $staffId, 'Guest checked out', $reservationId, $taskId);
            } catch (\Exception $e) {
                // Log error but don't fail checkout
                error_log('Failed to create housekeeping module task: ' . $e->getMessage());
            }
        }

        $this->notifications->notifyRole('housekeeping', 'Room ready for cleaning', sprintf(
            '%s departed from %s. Room marked as dirty and cleaning task created.',
            $reservation['guest_name'],
            $reservation['room_number'] ?? $reservation['room_type_name']
        ), [
            'room_id' => $reservation['room_id'],
        ]);
    }
}


