<?php

namespace App\Services\PMS;

use App\Repositories\ReservationRepository;
use App\Repositories\RoomRepository;
use App\Repositories\RoomTypeRepository;

class AvailabilityService
{
    protected RoomRepository $rooms;
    protected RoomTypeRepository $roomTypes;
    protected ReservationRepository $reservations;

    public function __construct()
    {
        $this->rooms = new RoomRepository();
        $this->roomTypes = new RoomTypeRepository();
        $this->reservations = new ReservationRepository();
    }

    public function search(string $startDate, string $endDate): array
    {
        $availableRooms = $this->rooms->groupedAvailability($startDate, $endDate);
        $types = $this->roomTypes->all();

        $results = [];
        foreach ($types as $type) {
            $typeName = $type['name'];
            $results[] = [
                'type' => $type,
                'rooms' => $availableRooms[$typeName]['rooms'] ?? [],
            ];
        }

        return $results;
    }
}


