<?php

use App\Modules\Admin\Controllers\SettingsController;
use App\Modules\AuditLogs\Controllers\AuditLogsController;
use App\Modules\Auth\Controllers\LoginController;
use App\Modules\Backups\Controllers\BackupController;
use App\Modules\CashBanking\Controllers\CashBankingController;
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
use App\Modules\Suppliers\Controllers\SupplierPortalController;

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
    ['POST', '/guest/login/request-code', [GuestPortalController::class, 'requestCode']],
    ['POST', '/guest/logout', [GuestPortalController::class, 'logout']],
    ['GET', '/guest/portal', [GuestPortalController::class, 'dashboard']],

    // Supplier Portal
    ['GET', '/supplier/login', [SupplierPortalController::class, 'showLogin']],
    ['POST', '/supplier/login', [SupplierPortalController::class, 'authenticate']],
    ['POST', '/supplier/login/request-code', [SupplierPortalController::class, 'requestCode']],
    ['POST', '/supplier/logout', [SupplierPortalController::class, 'logout']],
    ['GET', '/supplier/portal', [SupplierPortalController::class, 'dashboard']],
    ['GET', '/supplier/purchase-orders', [SupplierPortalController::class, 'purchaseOrders']],
    ['GET', '/supplier/purchase-order', [SupplierPortalController::class, 'showPurchaseOrder']],

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
    // Cash Banking
    ['GET', '/dashboard/cash-banking', [CashBankingController::class, 'index']],
    ['POST', '/dashboard/cash-banking/close-shift', [CashBankingController::class, 'closeShift']],
    ['GET', '/dashboard/cash-banking/unbanked-shifts', [CashBankingController::class, 'unbankedShifts']],
    ['POST', '/dashboard/cash-banking/create-batch', [CashBankingController::class, 'createBatch']],
    ['GET', '/dashboard/cash-banking/batch', [CashBankingController::class, 'batch']],
    ['POST', '/dashboard/cash-banking/reconcile', [CashBankingController::class, 'reconcile']],
    ['POST', '/dashboard/cash-banking/approve-reconciliation', [CashBankingController::class, 'approveReconciliation']],
    ['POST', '/dashboard/cash-banking/mark-banked', [CashBankingController::class, 'markBanked']],
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
    
    // Backup Management
    ['GET', '/staff/dashboard/backups', [BackupController::class, 'index']],
    ['POST', '/staff/dashboard/backups/create', [BackupController::class, 'create']],
    ['GET', '/staff/dashboard/backups/download', [BackupController::class, 'download']],
    ['POST', '/staff/dashboard/backups/delete', [BackupController::class, 'delete']],
    
    // Audit Logs
    ['GET', '/staff/dashboard/audit-logs', [AuditLogsController::class, 'index']],

    // Super admin routes
    ['GET', '/sysadmin/login', [SysAdminController::class, 'login']],
    ['POST', '/sysadmin/login', [SysAdminController::class, 'login']],
    ['GET', '/sysadmin/logout', [SysAdminController::class, 'logout']],
    ['GET', '/sysadmin/dashboard', [SysAdminController::class, 'dashboard']],
];


