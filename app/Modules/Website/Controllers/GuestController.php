<?php

namespace App\Modules\Website\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\PosItemRepository;
use App\Repositories\RoomRepository;
use App\Repositories\RoomTypeRepository;
use App\Support\GuestPortal;

class GuestController extends Controller
{
    protected RoomRepository $rooms;
    protected RoomTypeRepository $roomTypes;
    protected PosItemRepository $menu;

    public function __construct()
    {
        $this->rooms = new RoomRepository();
        $this->roomTypes = new RoomTypeRepository();
        $this->menu = new PosItemRepository();
    }

    public function home(Request $request): void
    {
        $types = $this->roomTypes->all();
        $featuredTypes = array_slice($types, 0, 3);

        $this->view('website/home', [
            'roomTypes' => $featuredTypes,
            'website' => settings('website', []),
        ]);
    }

    public function rooms(Request $request): void
    {
        $this->guardPage('rooms');

        $typeId = $request->input('type');
        $selectedTypeId = $typeId !== null ? (int)$typeId : null;
        $roomTypes = $this->roomTypes->all();
        $activeType = null;

        if ($selectedTypeId !== null) {
            foreach ($roomTypes as $type) {
                if ((int)$type['id'] === $selectedTypeId) {
                    $activeType = $type;
                    break;
                }
            }
        }
        $this->view('website/rooms', [
            'rooms' => $this->rooms->listWithTypes(null, $selectedTypeId),
            'roomTypes' => $roomTypes,
            'selectedTypeId' => $selectedTypeId,
            'activeType' => $activeType,
            'website' => settings('website', []),
        ]);
    }

    public function food(Request $request): void
    {
        $this->guardPage('food');

        $this->view('website/food', [
            'categories' => $this->menu->categoriesWithItems(),
            'website' => settings('website', []),
        ]);
    }

    public function about(Request $request): void
    {
        $this->guardPage('about');

        $this->view('website/about', [
            'website' => settings('website', []),
        ]);
    }

    public function contact(Request $request): void
    {
        $this->guardPage('contact');

        $this->view('website/contact', [
            'website' => settings('website', []),
        ]);
    }

    public function order(Request $request): void
    {
        $this->guardPage('order');

        $this->view('website/order', [
            'categories' => $this->menu->categoriesWithItems(),
            'website' => settings('website', []),
            'guest' => GuestPortal::user(),
        ]);
    }

    protected function guardPage(string $page): void
    {
        $pages = settings('website.pages', []);
        if (isset($pages[$page]) && !$pages[$page]) {
            http_response_code(404);
            echo 'Page disabled';
            exit;
        }
    }

}

