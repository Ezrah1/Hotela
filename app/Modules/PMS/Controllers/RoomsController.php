<?php

namespace App\Modules\PMS\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\ReservationRepository;
use App\Repositories\RoomRepository;
use App\Repositories\RoomTypeRepository;
use App\Support\Auth;

class RoomsController extends Controller
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

    public function index(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager', 'service_agent', 'housekeeping']);

        $filter = $request->input('filter', 'all');
        $roomTypeId = $request->input('room_type_id') ? (int)$request->input('room_type_id') : null;

        $allRooms = $this->rooms->housekeepingBoard();
        
        // Filter by status
        if ($filter !== 'all') {
            $allRooms = array_filter($allRooms, function($room) use ($filter) {
                return $room['status'] === $filter;
            });
        }

        // Filter by room type
        if ($roomTypeId) {
            $allRooms = array_filter($allRooms, function($room) use ($roomTypeId) {
                return (int)$room['room_type_id'] === $roomTypeId;
            });
        }

        // Get current reservations for each room
        $today = date('Y-m-d');
        $activeReservations = $this->reservations->calendar($today, date('Y-m-d', strtotime('+30 days')));
        $reservationsByRoom = [];
        foreach ($activeReservations as $reservation) {
            if ($reservation['room_id']) {
                $reservationsByRoom[(int)$reservation['room_id']] = $reservation;
            }
        }

        // Add reservation info to rooms
        foreach ($allRooms as &$room) {
            $room['current_reservation'] = $reservationsByRoom[(int)$room['id']] ?? null;
        }

        $roomTypes = $this->roomTypes->all();

        $this->view('dashboard/rooms/index', [
            'rooms' => array_values($allRooms),
            'roomTypes' => $roomTypes,
            'filter' => $filter,
            'selectedRoomTypeId' => $roomTypeId,
        ]);
    }

    public function updateStatus(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager', 'service_agent', 'housekeeping']);
        
        $roomId = (int)$request->input('room_id');
        $status = $request->input('status');

        $allowedStatuses = ['available', 'occupied', 'maintenance', 'blocked'];
        if (!in_array($status, $allowedStatuses, true)) {
            header('Location: ' . base_url('dashboard/rooms?error=Invalid%20status'));
            return;
        }

        $this->rooms->updateStatus($roomId, $status);

        header('Location: ' . base_url('dashboard/rooms?success=updated'));
    }

    public function selectEdit(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager']);

        $allRooms = $this->rooms->all();
        $roomTypes = $this->roomTypes->all();

        $this->view('dashboard/rooms/select_edit', [
            'rooms' => $allRooms,
            'roomTypes' => $roomTypes,
        ]);
    }

    public function editRoom(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager']);

        $roomId = (int)$request->input('room_id');
        $room = $this->rooms->find($roomId);

        if (!$room) {
            header('Location: ' . base_url('dashboard/rooms/select-edit?error=Room%20not%20found'));
            return;
        }

        $roomTypes = $this->roomTypes->all();

        $this->view('dashboard/rooms/edit', [
            'room' => $room,
            'roomTypes' => $roomTypes,
        ]);
    }

    public function updateRoom(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager']);

        $roomId = (int)$request->input('room_id');
        $room = $this->rooms->find($roomId);

        if (!$room) {
            header('Location: ' . base_url('dashboard/rooms/select-edit?error=Room%20not%20found'));
            return;
        }

        $updateData = [];
        if ($request->input('room_number')) $updateData['room_number'] = trim($request->input('room_number'));
        if ($request->input('display_name') !== null) $updateData['display_name'] = trim($request->input('display_name')) ?: null;
        if ($request->input('room_type_id')) $updateData['room_type_id'] = (int)$request->input('room_type_id');
        if ($request->input('floor') !== null) $updateData['floor'] = trim($request->input('floor')) ?: null;
        if ($request->input('status')) $updateData['status'] = $request->input('status');

        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            try {
                $uploadService = new \App\Services\FileUploadService();
                $imagePath = $uploadService->uploadImage($_FILES['image'], 'rooms');
                if ($imagePath) {
                    // Delete old image if exists
                    if (!empty($room['image'])) {
                        $uploadService->deleteImage($room['image']);
                    }
                    $updateData['image'] = $imagePath;
                }
            } catch (\Exception $e) {
                header('Location: ' . base_url('dashboard/rooms/edit?room_id=' . $roomId . '&error=' . urlencode($e->getMessage())));
                return;
            }
        } elseif ($request->input('image') === '') {
            // Explicitly set to null if empty string (removing image)
            if (!empty($room['image'])) {
                $uploadService = new \App\Services\FileUploadService();
                $uploadService->deleteImage($room['image']);
            }
            $updateData['image'] = null;
        }

        $this->rooms->update($roomId, $updateData);

        header('Location: ' . base_url('dashboard/rooms?success=room_updated'));
    }

    public function roomTypes(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager']);

        $roomTypes = $this->roomTypes->all();

        $this->view('dashboard/rooms/types', [
            'roomTypes' => $roomTypes,
        ]);
    }

    public function editRoomType(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager']);

        $roomTypeId = (int)$request->input('room_type_id');
        $roomType = $this->roomTypes->find($roomTypeId);

        if (!$roomType) {
            header('Location: ' . base_url('staff/dashboard/rooms/types?error=Room%20type%20not%20found'));
            return;
        }

        // Parse amenities if it's a JSON string
        if (isset($roomType['amenities']) && is_string($roomType['amenities'])) {
            $decoded = json_decode($roomType['amenities'], true);
            $roomType['amenities'] = is_array($decoded) ? $decoded : [];
        }

        $this->view('dashboard/rooms/edit_type', [
            'roomType' => $roomType,
        ]);
    }

    public function updateRoomType(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager']);

        $roomTypeId = (int)$request->input('room_type_id');
        $roomType = $this->roomTypes->find($roomTypeId);

        if (!$roomType) {
            header('Location: ' . base_url('staff/dashboard/rooms/types?error=Room%20type%20not%20found'));
            return;
        }

        $updateData = [];
        if ($request->input('name')) $updateData['name'] = trim($request->input('name'));
        if ($request->input('description') !== null) $updateData['description'] = trim($request->input('description')) ?: null;
        if ($request->input('max_guests')) $updateData['max_guests'] = (int)$request->input('max_guests');
        if ($request->input('base_rate') !== null) $updateData['base_rate'] = (float)$request->input('base_rate');
        
        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            try {
                $uploadService = new \App\Services\FileUploadService();
                $imagePath = $uploadService->uploadImage($_FILES['image'], 'room-types');
                if ($imagePath) {
                    // Delete old image if exists
                    if (!empty($roomType['image'])) {
                        $uploadService->deleteImage($roomType['image']);
                    }
                    $updateData['image'] = $imagePath;
                }
            } catch (\Exception $e) {
                header('Location: ' . base_url('staff/dashboard/rooms/edit-type?room_type_id=' . $roomTypeId . '&error=' . urlencode($e->getMessage())));
                return;
            }
        } elseif ($request->input('image') === '') {
            // Explicitly set to null if empty string (removing image)
            if (!empty($roomType['image'])) {
                $uploadService = new \App\Services\FileUploadService();
                $uploadService->deleteImage($roomType['image']);
            }
            $updateData['image'] = null;
        }
        
        // Handle amenities
        if ($request->input('amenities') !== null) {
            $amenities = $request->input('amenities');
            if (is_string($amenities)) {
                $amenities = array_filter(array_map('trim', explode(',', $amenities)));
            }
            $updateData['amenities'] = $amenities;
        }

        $this->roomTypes->update($roomTypeId, $updateData);

        header('Location: ' . base_url('staff/dashboard/rooms/types?success=updated'));
    }

    public function createRoomType(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager']);

        if ($request->method() === 'POST') {
            $name = trim($request->input('name', ''));
            if ($name === '') {
                header('Location: ' . base_url('staff/dashboard/rooms/types?error=Name%20is%20required'));
                return;
            }

            $amenities = $request->input('amenities', '');
            $amenitiesArray = [];
            if ($amenities) {
                $amenitiesArray = array_filter(array_map('trim', explode(',', $amenities)));
            }

            $imagePath = null;
            // Handle image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                try {
                    $uploadService = new \App\Services\FileUploadService();
                    $imagePath = $uploadService->uploadImage($_FILES['image'], 'room-types');
                } catch (\Exception $e) {
                    header('Location: ' . base_url('staff/dashboard/rooms/types?error=' . urlencode($e->getMessage())));
                    return;
                }
            }

            $this->roomTypes->create([
                'name' => $name,
                'description' => trim($request->input('description', '')),
                'max_guests' => (int)$request->input('max_guests', 2),
                'base_rate' => (float)$request->input('base_rate', 0),
                'amenities' => $amenitiesArray,
                'image' => $imagePath,
            ]);

            header('Location: ' . base_url('staff/dashboard/rooms/types?success=created'));
            return;
        }

        // GET request - show form
        $this->view('dashboard/rooms/create_type');
    }

    public function deleteRoomType(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager']);

        $roomTypeId = (int)$request->input('room_type_id');
        $deleted = $this->roomTypes->delete($roomTypeId);

        if (!$deleted) {
            header('Location: ' . base_url('staff/dashboard/rooms/types?error=Cannot%20delete%20room%20type%20in%20use'));
            return;
        }

        header('Location: ' . base_url('staff/dashboard/rooms/types?success=deleted'));
    }

    public function replaceRoomTypes(Request $request): void
    {
        Auth::requireRoles(['admin', 'operation_manager']);

        try {
            // Get all current room types
            $allTypes = $this->roomTypes->all();
            
            // Group by price to determine new types
            $priceGroups = [];
            foreach ($allTypes as $type) {
                $rate = (float)($type['base_rate'] ?? 0);
                if ($rate >= 9000) {
                    $priceGroups['lux'][] = $type;
                } elseif ($rate >= 4500) {
                    $priceGroups['deluxe'][] = $type;
                } else {
                    $priceGroups['standard'][] = $type;
                }
            }

            // Create new standard room types
            $newTypes = [
                'standard' => [
                    'name' => 'Standard',
                    'description' => 'Comfortable standard rooms with essential amenities',
                    'max_guests' => 2,
                    'base_rate' => 3500,
                    'amenities' => ['WiFi', 'TV', 'AC', 'Complimentary breakfast'],
                    'image' => null,
                ],
                'deluxe' => [
                    'name' => 'Deluxe',
                    'description' => 'Spacious deluxe rooms with enhanced amenities',
                    'max_guests' => 2,
                    'base_rate' => 5000,
                    'amenities' => ['WiFi', 'TV', 'AC', 'Mini Bar', 'Complimentary breakfast', 'Garden views'],
                    'image' => null,
                ],
                'lux' => [
                    'name' => 'Lux',
                    'description' => 'Premium luxury suites with premium amenities',
                    'max_guests' => 3,
                    'base_rate' => 10000,
                    'amenities' => ['WiFi', 'TV', 'AC', 'Mini Bar', 'Complimentary breakfast', 'Garden views', 'Evening turndown service'],
                    'image' => null,
                ],
            ];

            $newTypeIds = [];
            foreach ($newTypes as $key => $typeData) {
                $newTypeIds[$key] = $this->roomTypes->create($typeData);
            }

            // Create mapping: old type id => new type id
            $mapping = [];
            foreach ($allTypes as $oldType) {
                $rate = (float)($oldType['base_rate'] ?? 0);
                if ($rate >= 9000) {
                    $mapping[(int)$oldType['id']] = $newTypeIds['lux'];
                } elseif ($rate >= 4500) {
                    $mapping[(int)$oldType['id']] = $newTypeIds['deluxe'];
                } else {
                    $mapping[(int)$oldType['id']] = $newTypeIds['standard'];
                }
            }

            // Replace all old types with new ones
            $this->roomTypes->replaceAll($mapping);

            header('Location: ' . base_url('staff/dashboard/rooms/types?success=replaced'));
        } catch (\Exception $e) {
            header('Location: ' . base_url('staff/dashboard/rooms/types?error=' . urlencode($e->getMessage())));
        }
    }
}

