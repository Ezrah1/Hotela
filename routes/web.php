<?php

use App\Modules\Admin\Controllers\SettingsController;
use App\Modules\Auth\Controllers\LoginController;
use App\Modules\Dashboard\Controllers\DashboardController;
use App\Modules\Inventory\Controllers\InventoryController;
use App\Modules\PMS\Controllers\BookingController;
use App\Modules\POS\Controllers\POSController;
use App\Modules\Reports\Controllers\SalesReportController;
use App\Modules\Tasks\Controllers\TaskController;
use App\Modules\SysAdmin\Controllers\SysAdminController;
use App\Modules\Website\Controllers\GuestController;
use App\Modules\Website\Controllers\GuestPortalController;
use App\Modules\Website\Controllers\OrderController;

return [
    // Public guest pages
    ['GET', '/', [GuestController::class, 'home']],
    ['GET', '/rooms', [GuestController::class, 'rooms']],
    ['GET', '/drinks-food', [GuestController::class, 'food']],
    ['GET', '/about', [GuestController::class, 'about']],
    ['GET', '/contact', [GuestController::class, 'contact']],
    ['GET', '/order', [OrderController::class, 'show']],
    ['GET', '/order/cart', [OrderController::class, 'cart']],
    ['POST', '/order/cart', [OrderController::class, 'cart']],
    ['GET', '/order/availability', [OrderController::class, 'availability']],
    ['POST', '/order/checkout', [OrderController::class, 'checkout']],
    ['GET', '/booking', [BookingController::class, 'publicForm']],
    ['POST', '/booking', [BookingController::class, 'store']],
    ['GET', '/guest/login', [GuestPortalController::class, 'showLogin']],
    ['POST', '/guest/login', [GuestPortalController::class, 'authenticate']],
    ['POST', '/guest/logout', [GuestPortalController::class, 'logout']],
    ['GET', '/guest/portal', [GuestPortalController::class, 'dashboard']],

    // Authentication
    ['GET', '/login', [LoginController::class, 'show']],
    ['POST', '/login', [LoginController::class, 'authenticate']],
    ['GET', '/logout', [LoginController::class, 'logout']],

    // Staff / authenticated routes
    ['GET', '/dashboard', [DashboardController::class, 'index']],
    ['GET', '/dashboard/bookings', [BookingController::class, 'staffIndex']],
    ['GET', '/dashboard/bookings/calendar-view', [BookingController::class, 'calendarView']],
    ['GET', '/dashboard/bookings/calendar', [BookingController::class, 'calendar']],
    ['POST', '/dashboard/bookings/check-in', [BookingController::class, 'checkIn']],
    ['POST', '/dashboard/bookings/check-out', [BookingController::class, 'checkOut']],
    ['GET', '/dashboard/bookings/folio', [BookingController::class, 'folio']],
    ['POST', '/dashboard/bookings/folio-entry', [BookingController::class, 'addFolioEntry']],
    ['POST', '/dashboard/bookings/assign-room', [BookingController::class, 'assignRoom']],
    ['GET', '/dashboard/bookings/edit', [BookingController::class, 'edit']],
    ['POST', '/dashboard/bookings/update', [BookingController::class, 'update']],
    ['GET', '/dashboard/rooms', [\App\Modules\PMS\Controllers\RoomsController::class, 'index']],
    ['POST', '/dashboard/rooms/update-status', [\App\Modules\PMS\Controllers\RoomsController::class, 'updateStatus']],
    ['GET', '/dashboard/rooms/select-edit', [\App\Modules\PMS\Controllers\RoomsController::class, 'selectEdit']],
    ['GET', '/dashboard/rooms/edit', [\App\Modules\PMS\Controllers\RoomsController::class, 'editRoom']],
    ['POST', '/dashboard/rooms/update', [\App\Modules\PMS\Controllers\RoomsController::class, 'updateRoom']],
    ['GET', '/dashboard/rooms/types', [\App\Modules\PMS\Controllers\RoomsController::class, 'roomTypes']],
    ['GET', '/dashboard/rooms/create-type', [\App\Modules\PMS\Controllers\RoomsController::class, 'createRoomType']],
    ['POST', '/dashboard/rooms/create-type', [\App\Modules\PMS\Controllers\RoomsController::class, 'createRoomType']],
    ['GET', '/dashboard/rooms/edit-type', [\App\Modules\PMS\Controllers\RoomsController::class, 'editRoomType']],
    ['POST', '/dashboard/rooms/update-type', [\App\Modules\PMS\Controllers\RoomsController::class, 'updateRoomType']],
    ['POST', '/dashboard/rooms/delete-type', [\App\Modules\PMS\Controllers\RoomsController::class, 'deleteRoomType']],
    ['POST', '/dashboard/rooms/replace-types', [\App\Modules\PMS\Controllers\RoomsController::class, 'replaceRoomTypes']],
    ['GET', '/dashboard/pos', [POSController::class, 'index']],
    ['GET', '/dashboard/pos/dashboard', [POSController::class, 'dashboard']],
    ['POST', '/dashboard/pos/sale', [POSController::class, 'sale']],
    ['GET', '/dashboard/orders', [\App\Modules\Orders\Controllers\OrdersController::class, 'index']],
    ['GET', '/dashboard/orders/show', [\App\Modules\Orders\Controllers\OrdersController::class, 'show']],
    // Tasks
    ['GET', '/dashboard/tasks', [TaskController::class, 'index']],
    ['GET', '/dashboard/tasks/create', [TaskController::class, 'create']],
    // Reports
    ['GET', '/dashboard/reports/sales', [SalesReportController::class, 'index']],
    ['GET', '/dashboard/inventory/requisitions', [InventoryController::class, 'requisitions']],
    ['POST', '/dashboard/inventory/requisitions', [InventoryController::class, 'storeRequisition']],
    ['POST', '/dashboard/inventory/requisitions/status', [InventoryController::class, 'updateRequisitionStatus']],
    ['POST', '/dashboard/inventory/purchase-orders/receive', [InventoryController::class, 'receivePurchaseOrder']],
    ['GET', '/admin/settings', [SettingsController::class, 'index']],
    ['POST', '/admin/settings', [SettingsController::class, 'update']],

    // Super admin routes
    ['GET', '/sysadmin/login', [SysAdminController::class, 'login']],
    ['POST', '/sysadmin/login', [SysAdminController::class, 'login']],
    ['GET', '/sysadmin/logout', [SysAdminController::class, 'logout']],
    ['GET', '/sysadmin/dashboard', [SysAdminController::class, 'dashboard']],
];


