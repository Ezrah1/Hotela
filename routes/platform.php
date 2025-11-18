<?php

use App\Modules\Admin\Controllers\SettingsController;
use App\Modules\Auth\Controllers\LoginController;
use App\Modules\Dashboard\Controllers\DashboardController;
use App\Modules\Inventory\Controllers\InventoryController;
use App\Modules\PMS\Controllers\BookingController;
use App\Modules\POS\Controllers\POSController;
use App\Modules\Reports\Controllers\SalesReportController;
use App\Modules\Roles\Controllers\RolesController;
use App\Modules\Staff\Controllers\StaffController;
use App\Modules\Tasks\Controllers\TaskController;
use App\Modules\Platform\Controllers\MarketingController;
use App\Modules\SysAdmin\Controllers\SysAdminController;

return [
    // Public marketing site for platform
    ['GET', '/', [MarketingController::class, 'home']],

    // Authentication
    ['GET', '/login', [LoginController::class, 'show']],
    ['POST', '/login', [LoginController::class, 'authenticate']],
    ['GET', '/logout', [LoginController::class, 'logout']],

    // Staff dashboards
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
    ['GET', '/dashboard/staff', [StaffController::class, 'index']],
    ['GET', '/dashboard/staff/edit', [StaffController::class, 'edit']],
    ['POST', '/dashboard/staff/update', [StaffController::class, 'update']],
    ['GET', '/dashboard/roles', [RolesController::class, 'index']],
    ['GET', '/dashboard/roles/edit', [RolesController::class, 'editRole']],
    ['POST', '/dashboard/roles/update', [RolesController::class, 'updateRole']],
	// Website Management
	['GET', '/dashboard/website', [\App\Modules\Website\Controllers\WebsiteController::class, 'index']],
	['POST', '/dashboard/website/update', [\App\Modules\Website\Controllers\WebsiteController::class, 'update']],
	// Task Manager
	['GET', '/dashboard/tasks', [TaskController::class, 'index']],
	['GET', '/dashboard/tasks/create', [TaskController::class, 'create']],
	['GET', '/dashboard/tasks/staff-by-department', [TaskController::class, 'getStaffByDepartment']],
	// Reports
	['GET', '/dashboard/reports/sales', [SalesReportController::class, 'index']],
	['GET', '/dashboard/reports/finance', [\App\Modules\Reports\Controllers\FinanceReportController::class, 'index']],
	// Payslip
	['GET', '/dashboard/payslip', [\App\Modules\Payslip\Controllers\PayslipController::class, 'index']],
	['GET', '/dashboard/payslip/view', [\App\Modules\Payslip\Controllers\PayslipController::class, 'show']],
	// HR
	['GET', '/dashboard/hr', [\App\Modules\HR\Controllers\HRController::class, 'index']],
	['GET', '/dashboard/hr/employee', [\App\Modules\HR\Controllers\HRController::class, 'employee']],
	['POST', '/dashboard/hr/employee/record', [\App\Modules\HR\Controllers\HRController::class, 'addRecord']],
	// Payroll
	['GET', '/dashboard/payroll', [\App\Modules\Payroll\Controllers\PayrollController::class, 'index']],
	['POST', '/dashboard/payroll/generate', [\App\Modules\Payroll\Controllers\PayrollController::class, 'generate']],
	['GET', '/dashboard/payroll/edit', [\App\Modules\Payroll\Controllers\PayrollController::class, 'edit']],
	['POST', '/dashboard/payroll/update', [\App\Modules\Payroll\Controllers\PayrollController::class, 'update']],
	// Payments
	['GET', '/dashboard/payments', [\App\Modules\Payments\Controllers\PaymentsController::class, 'index']],
	['GET', '/dashboard/payments/record', [\App\Modules\Payments\Controllers\PaymentsController::class, 'record']],
	['POST', '/dashboard/payments/record', [\App\Modules\Payments\Controllers\PaymentsController::class, 'store']],
	['GET', '/dashboard/payments/manage', [\App\Modules\Payments\Controllers\PaymentsController::class, 'manage']],
	// Notifications
	['GET', '/dashboard/notifications', [\App\Modules\Notifications\Controllers\NotificationsController::class, 'index']],
	['GET', '/dashboard/notifications/mark-read', [\App\Modules\Notifications\Controllers\NotificationsController::class, 'markAsRead']],
	['GET', '/dashboard/notifications/mark-all-read', [\App\Modules\Notifications\Controllers\NotificationsController::class, 'markAllAsRead']],
	['GET', '/dashboard/notifications/delete', [\App\Modules\Notifications\Controllers\NotificationsController::class, 'delete']],
	// Messages
	['GET', '/dashboard/messages', [\App\Modules\Messages\Controllers\MessagesController::class, 'index']],
	['GET', '/dashboard/messages/compose', [\App\Modules\Messages\Controllers\MessagesController::class, 'compose']],
	['POST', '/dashboard/messages/compose', [\App\Modules\Messages\Controllers\MessagesController::class, 'send']],
	['GET', '/dashboard/messages/view', [\App\Modules\Messages\Controllers\MessagesController::class, 'show']],
	['GET', '/dashboard/messages/mark-read', [\App\Modules\Messages\Controllers\MessagesController::class, 'markAsRead']],
	['GET', '/dashboard/messages/delete', [\App\Modules\Messages\Controllers\MessagesController::class, 'delete']],
	// Announcements
	['GET', '/dashboard/announcements', [\App\Modules\Announcements\Controllers\AnnouncementsController::class, 'index']],
	['GET', '/dashboard/announcements/create', [\App\Modules\Announcements\Controllers\AnnouncementsController::class, 'create']],
	['POST', '/dashboard/announcements/create', [\App\Modules\Announcements\Controllers\AnnouncementsController::class, 'store']],
	['GET', '/dashboard/announcements/view', [\App\Modules\Announcements\Controllers\AnnouncementsController::class, 'show']],
	['GET', '/dashboard/announcements/edit', [\App\Modules\Announcements\Controllers\AnnouncementsController::class, 'edit']],
	['POST', '/dashboard/announcements/edit', [\App\Modules\Announcements\Controllers\AnnouncementsController::class, 'update']],
	['GET', '/dashboard/announcements/delete', [\App\Modules\Announcements\Controllers\AnnouncementsController::class, 'delete']],
	// Suppliers
	['GET', '/dashboard/suppliers', [\App\Modules\Suppliers\Controllers\SuppliersController::class, 'index']],
	['GET', '/dashboard/suppliers/create', [\App\Modules\Suppliers\Controllers\SuppliersController::class, 'create']],
	['POST', '/dashboard/suppliers/create', [\App\Modules\Suppliers\Controllers\SuppliersController::class, 'store']],
	['GET', '/dashboard/suppliers/edit', [\App\Modules\Suppliers\Controllers\SuppliersController::class, 'edit']],
	['POST', '/dashboard/suppliers/edit', [\App\Modules\Suppliers\Controllers\SuppliersController::class, 'update']],
	['GET', '/dashboard/suppliers/show', [\App\Modules\Suppliers\Controllers\SuppliersController::class, 'show']],
	['POST', '/dashboard/suppliers/delete', [\App\Modules\Suppliers\Controllers\SuppliersController::class, 'delete']],
	// Expenses
	['GET', '/dashboard/expenses', [\App\Modules\Expenses\Controllers\ExpensesController::class, 'index']],
	['GET', '/dashboard/expenses/create', [\App\Modules\Expenses\Controllers\ExpensesController::class, 'create']],
	['POST', '/dashboard/expenses/create', [\App\Modules\Expenses\Controllers\ExpensesController::class, 'store']],
	['GET', '/dashboard/expenses/edit', [\App\Modules\Expenses\Controllers\ExpensesController::class, 'edit']],
	['POST', '/dashboard/expenses/edit', [\App\Modules\Expenses\Controllers\ExpensesController::class, 'update']],
	['GET', '/dashboard/expenses/show', [\App\Modules\Expenses\Controllers\ExpensesController::class, 'show']],
	['GET', '/dashboard/expenses/approve', [\App\Modules\Expenses\Controllers\ExpensesController::class, 'approve']],
	['GET', '/dashboard/expenses/mark-paid', [\App\Modules\Expenses\Controllers\ExpensesController::class, 'markPaid']],
	// Bills
	['GET', '/dashboard/bills', [\App\Modules\Bills\Controllers\BillsController::class, 'index']],
	['GET', '/dashboard/bills/create', [\App\Modules\Bills\Controllers\BillsController::class, 'create']],
	['POST', '/dashboard/bills/create', [\App\Modules\Bills\Controllers\BillsController::class, 'store']],
	['GET', '/dashboard/bills/edit', [\App\Modules\Bills\Controllers\BillsController::class, 'edit']],
	['POST', '/dashboard/bills/edit', [\App\Modules\Bills\Controllers\BillsController::class, 'update']],
	['GET', '/dashboard/bills/show', [\App\Modules\Bills\Controllers\BillsController::class, 'show']],
	['GET', '/dashboard/bills/approve', [\App\Modules\Bills\Controllers\BillsController::class, 'approve']],
	['GET', '/dashboard/bills/mark-paid', [\App\Modules\Bills\Controllers\BillsController::class, 'markPaid']],
	// Petty Cash
	['GET', '/dashboard/petty-cash', [\App\Modules\PettyCash\Controllers\PettyCashController::class, 'index']],
	['GET', '/dashboard/petty-cash/deposit', [\App\Modules\PettyCash\Controllers\PettyCashController::class, 'deposit']],
	['POST', '/dashboard/petty-cash/deposit', [\App\Modules\PettyCash\Controllers\PettyCashController::class, 'processDeposit']],
	['GET', '/dashboard/petty-cash/settings', [\App\Modules\PettyCash\Controllers\PettyCashController::class, 'settings']],
	['POST', '/dashboard/petty-cash/settings', [\App\Modules\PettyCash\Controllers\PettyCashController::class, 'updateSettings']],
	['POST', '/dashboard/petty-cash/link-expense', [\App\Modules\PettyCash\Controllers\PettyCashController::class, 'linkExpense']],
    ['GET', '/dashboard/inventory', [InventoryController::class, 'index']],
    ['GET', '/dashboard/inventory/requisitions', [InventoryController::class, 'requisitions']],
    ['POST', '/dashboard/inventory/requisitions', [InventoryController::class, 'storeRequisition']],
    ['POST', '/dashboard/inventory/requisitions/status', [InventoryController::class, 'updateRequisitionStatus']],
    ['POST', '/dashboard/inventory/requisitions/complete', [InventoryController::class, 'completeRequisition']],
    ['POST', '/dashboard/inventory/purchase-orders/receive', [InventoryController::class, 'receivePurchaseOrder']],
    ['POST', '/dashboard/inventory/auto-import', [InventoryController::class, 'autoImport']],
    ['GET', '/admin/settings', [SettingsController::class, 'index']],
    ['POST', '/admin/settings', [SettingsController::class, 'update']],

    // Sys admin console
    ['GET', '/sysadmin/login', [SysAdminController::class, 'login']],
    ['POST', '/sysadmin/login', [SysAdminController::class, 'login']],
    ['GET', '/sysadmin/logout', [SysAdminController::class, 'logout']],
    ['GET', '/sysadmin/dashboard', [SysAdminController::class, 'dashboard']],
];


