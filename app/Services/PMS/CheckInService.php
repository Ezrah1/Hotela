<?php

namespace App\Services\PMS;

use App\Repositories\FolioRepository;
use App\Repositories\ReservationRepository;
use App\Repositories\RoomRepository;
use App\Services\Notifications\NotificationService;
use RuntimeException;

class CheckInService
{
    protected ReservationRepository $reservations;
    protected FolioRepository $folios;
    protected NotificationService $notifications;
    protected RoomRepository $rooms;

    public function __construct()
    {
        $this->reservations = new ReservationRepository();
        $this->folios = new FolioRepository();
        $this->notifications = new NotificationService();
        $this->rooms = new RoomRepository();
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

        $folio = $this->folios->findByReservation($reservationId);
        if (!$folio) {
            $folioId = $this->folios->create($reservationId);
            $this->folios->addEntry($folioId, 'Room charges', (float)$reservation['total_amount'], 'charge', 'room');
        }

        $this->reservations->updateStatus($reservationId, [
            'check_in_status' => 'checked_in',
            'room_status' => 'in_house',
        ]);

        $this->rooms->updateStatus((int)$reservation['room_id'], 'occupied');

        $this->notifications->notifyRole('housekeeping', 'Guest checked in', sprintf(
            '%s checked into %s. Prepare turn-down service.',
            $reservation['guest_name'],
            $reservation['room_number'] ?? $reservation['room_type_name']
        ));
    }

    public function checkOut(int $reservationId): void
    {
        $reservation = $this->reservations->findById($reservationId);

        if (!$reservation) {
            throw new RuntimeException('Reservation not found');
        }

        $folio = $this->folios->findByReservation($reservationId);
        if ($folio && (float)$folio['balance'] > 0) {
            throw new RuntimeException('Outstanding balance must be settled before check-out');
        }

        $this->reservations->updateStatus($reservationId, [
            'check_in_status' => 'checked_out',
            'room_status' => 'departed',
            'status' => 'checked_out',
        ]);

        if ($reservation['room_id']) {
            $this->rooms->updateStatus((int)$reservation['room_id'], 'needs_cleaning');
        }

        $this->notifications->notifyRole('housekeeping', 'Room ready for cleaning', sprintf(
            '%s departed from %s. Room needs cleaning.',
            $reservation['guest_name'],
            $reservation['room_number'] ?? $reservation['room_type_name']
        ), [
            'room_id' => $reservation['room_id'],
        ]);
    }
}


