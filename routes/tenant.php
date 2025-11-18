<?php

use App\Modules\PMS\Controllers\BookingController;
use App\Modules\Website\Controllers\GuestController;
use App\Modules\Website\Controllers\GuestPortalController;

return [
    ['GET', '/', [GuestController::class, 'home']],
    ['GET', '/rooms', [GuestController::class, 'rooms']],
    ['GET', '/drinks-food', [GuestController::class, 'food']],
    ['GET', '/about', [GuestController::class, 'about']],
    ['GET', '/contact', [GuestController::class, 'contact']],
    ['GET', '/order', [GuestController::class, 'order']],
    ['GET', '/booking', [BookingController::class, 'publicForm']],
    ['POST', '/booking/check', [BookingController::class, 'checkAvailability']],
    ['POST', '/booking', [BookingController::class, 'store']],
    ['GET', '/guest/login', [GuestPortalController::class, 'showLogin']],
    ['POST', '/guest/login', [GuestPortalController::class, 'authenticate']],
    ['POST', '/guest/logout', [GuestPortalController::class, 'logout']],
    ['GET', '/guest/portal', [GuestPortalController::class, 'dashboard']],
];


