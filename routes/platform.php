<?php

use App\Modules\Admin\Controllers\SettingsController;
use App\Modules\Auth\Controllers\LoginController;
use App\Modules\CashBanking\Controllers\CashBankingController;
use App\Modules\Dashboard\Controllers\DashboardController;
use App\Modules\Inventory\Controllers\InventoryController;
use App\Modules\PMS\Controllers\BookingController;
use App\Modules\POS\Controllers\POSController;
use App\Modules\Reports\Controllers\SalesReportController;
use App\Modules\Roles\Controllers\RolesController;
use App\Modules\Staff\Controllers\StaffController;
use App\Modules\Tasks\Controllers\TaskController;
use App\Modules\Platform\Controllers\MarketingController;
use App\Modules\Platform\Controllers\FeaturesController;
use App\Modules\Platform\Controllers\ModulesController;
use App\Modules\Platform\Controllers\ContactController;
use App\Modules\SysAdmin\Controllers\SysAdminController;
use App\Modules\Website\Controllers\GuestController;
use App\Modules\Website\Controllers\GuestPortalController;
use App\Modules\Website\Controllers\OrderController;

return [
    // Guest Website (main public website - root path)
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
    ['POST', '/booking/check', [BookingController::class, 'checkAvailability']],
    ['POST', '/booking', [BookingController::class, 'store']],
    ['GET', '/booking/confirmation', [BookingController::class, 'confirmation']],
    ['GET', '/guest/login', [GuestPortalController::class, 'showLogin']],
    ['POST', '/guest/login', [GuestPortalController::class, 'authenticate']],
    ['POST', '/guest/logout', [GuestPortalController::class, 'logout']],
    ['GET', '/guest/portal', [GuestPortalController::class, 'dashboard']],
    
    // Business information pages
    ['GET', '/features', [FeaturesController::class, 'index']],
    ['GET', '/modules', [ModulesController::class, 'index']],
    ['GET', '/contact-developer', [ContactController::class, 'index']],

    // Staff area (all staff functionality under /staff)
    ['GET', '/staff', [LoginController::class, 'show']],
    ['GET', '/staff/login', [LoginController::class, 'show']],
    ['POST', '/staff/login', [LoginController::class, 'authenticate']],
    ['GET', '/staff/logout', [LoginController::class, 'logout']],

    // Staff dashboards (all under /staff/dashboard)
    ['GET', '/staff/dashboard', [DashboardController::class, 'index']],
    ['GET', '/staff/dashboard/bookings', [BookingController::class, 'staffIndex']],
    ['GET', '/staff/dashboard/bookings/calendar-view', [BookingController::class, 'calendarView']],
    ['GET', '/staff/dashboard/bookings/calendar', [BookingController::class, 'calendar']],
    ['POST', '/staff/dashboard/bookings/check-in', [BookingController::class, 'checkIn']],
    ['POST', '/staff/dashboard/bookings/check-out', [BookingController::class, 'checkOut']],
    ['GET', '/staff/dashboard/bookings/folio', [BookingController::class, 'folio']],
    ['POST', '/staff/dashboard/bookings/folio-entry', [BookingController::class, 'addFolioEntry']],
    ['POST', '/staff/dashboard/bookings/folio-mpesa-payment', [BookingController::class, 'folioMpesaPayment']],
    ['POST', '/staff/dashboard/bookings/folio-confirm-payment', [BookingController::class, 'confirmFolioPayment']],
    ['GET', '/staff/dashboard/bookings/folio-query-payment', [BookingController::class, 'queryFolioPaymentStatus']],
    ['POST', '/staff/dashboard/bookings/assign-room', [BookingController::class, 'assignRoom']],
    ['GET', '/staff/dashboard/bookings/edit', [BookingController::class, 'edit']],
    ['POST', '/staff/dashboard/bookings/update', [BookingController::class, 'update']],
    ['GET', '/staff/dashboard/rooms', [\App\Modules\PMS\Controllers\RoomsController::class, 'index']],
    ['POST', '/staff/dashboard/rooms/update-status', [\App\Modules\PMS\Controllers\RoomsController::class, 'updateStatus']],
    ['GET', '/staff/dashboard/rooms/select-edit', [\App\Modules\PMS\Controllers\RoomsController::class, 'selectEdit']],
    ['GET', '/staff/dashboard/rooms/edit', [\App\Modules\PMS\Controllers\RoomsController::class, 'editRoom']],
    ['POST', '/staff/dashboard/rooms/update', [\App\Modules\PMS\Controllers\RoomsController::class, 'updateRoom']],
    ['GET', '/staff/dashboard/rooms/types', [\App\Modules\PMS\Controllers\RoomsController::class, 'roomTypes']],
    ['GET', '/staff/dashboard/rooms/create-type', [\App\Modules\PMS\Controllers\RoomsController::class, 'createRoomType']],
    ['POST', '/staff/dashboard/rooms/create-type', [\App\Modules\PMS\Controllers\RoomsController::class, 'createRoomType']],
    ['GET', '/staff/dashboard/rooms/edit-type', [\App\Modules\PMS\Controllers\RoomsController::class, 'editRoomType']],
    ['POST', '/staff/dashboard/rooms/update-type', [\App\Modules\PMS\Controllers\RoomsController::class, 'updateRoomType']],
    ['POST', '/staff/dashboard/rooms/delete-type', [\App\Modules\PMS\Controllers\RoomsController::class, 'deleteRoomType']],
    ['POST', '/staff/dashboard/rooms/replace-types', [\App\Modules\PMS\Controllers\RoomsController::class, 'replaceRoomTypes']],
    ['GET', '/staff/dashboard/pos', [POSController::class, 'index']],
    ['GET', '/staff/dashboard/pos/dashboard', [POSController::class, 'dashboard']],
    ['POST', '/staff/dashboard/pos/sale', [POSController::class, 'sale']],
    ['GET', '/staff/dashboard/pos/receipt', [POSController::class, 'receipt']],
    // Cash Banking
    ['GET', '/staff/dashboard/cash-banking', [CashBankingController::class, 'index']],
    ['POST', '/staff/dashboard/cash-banking/close-shift', [CashBankingController::class, 'closeShift']],
    ['GET', '/staff/dashboard/cash-banking/unbanked-shifts', [CashBankingController::class, 'unbankedShifts']],
    ['POST', '/staff/dashboard/cash-banking/create-batch', [CashBankingController::class, 'createBatch']],
    ['GET', '/staff/dashboard/cash-banking/batch', [CashBankingController::class, 'batch']],
    ['POST', '/staff/dashboard/cash-banking/reconcile', [CashBankingController::class, 'reconcile']],
    ['POST', '/staff/dashboard/cash-banking/approve-reconciliation', [CashBankingController::class, 'approveReconciliation']],
    ['POST', '/staff/dashboard/cash-banking/mark-banked', [CashBankingController::class, 'markBanked']],
    ['GET', '/staff/dashboard/orders', [\App\Modules\Orders\Controllers\OrdersController::class, 'index']],
    ['GET', '/staff/dashboard/orders/show', [\App\Modules\Orders\Controllers\OrdersController::class, 'show']],
	['GET', '/staff/dashboard/staff', [StaffController::class, 'index']],
	['GET', '/staff/dashboard/staff/edit', [StaffController::class, 'edit']],
	['POST', '/staff/dashboard/staff/update', [StaffController::class, 'update']],
	// Attendance
	['GET', '/staff/dashboard/attendance', [\App\Modules\Attendance\Controllers\AttendanceController::class, 'index']],
	['POST', '/staff/dashboard/attendance/check-in', [\App\Modules\Attendance\Controllers\AttendanceController::class, 'checkIn']],
	['POST', '/staff/dashboard/attendance/check-out', [\App\Modules\Attendance\Controllers\AttendanceController::class, 'checkOut']],
	['POST', '/staff/dashboard/attendance/grant-override', [\App\Modules\Attendance\Controllers\AttendanceController::class, 'grantOverride']],
	['GET', '/staff/dashboard/attendance/my-attendance', [\App\Modules\Attendance\Controllers\AttendanceController::class, 'myAttendance']],
    ['GET', '/staff/dashboard/roles', [RolesController::class, 'index']],
    ['GET', '/staff/dashboard/roles/edit', [RolesController::class, 'editRole']],
    ['POST', '/staff/dashboard/roles/update', [RolesController::class, 'updateRole']],
	// Website Management is now handled in Settings page
	// Task Manager
	['GET', '/staff/dashboard/tasks', [TaskController::class, 'index']],
	['GET', '/staff/dashboard/tasks/create', [TaskController::class, 'create']],
	['GET', '/staff/dashboard/tasks/staff-by-department', [TaskController::class, 'getStaffByDepartment']],
	// Reports
	['GET', '/staff/dashboard/reports/sales', [SalesReportController::class, 'index']],
	['GET', '/staff/dashboard/reports/finance', [\App\Modules\Reports\Controllers\FinanceReportController::class, 'index']],
	['GET', '/staff/dashboard/reports/operations', [\App\Modules\Reports\Controllers\OperationsReportController::class, 'index']],
	// Maintenance
	['GET', '/staff/dashboard/maintenance', [\App\Modules\Maintenance\Controllers\MaintenanceController::class, 'index']],
	['GET', '/staff/dashboard/maintenance/create', [\App\Modules\Maintenance\Controllers\MaintenanceController::class, 'create']],
	['POST', '/staff/dashboard/maintenance/create', [\App\Modules\Maintenance\Controllers\MaintenanceController::class, 'create']],
	['GET', '/staff/dashboard/maintenance/show', [\App\Modules\Maintenance\Controllers\MaintenanceController::class, 'show']],
	['GET', '/staff/dashboard/maintenance/edit', [\App\Modules\Maintenance\Controllers\MaintenanceController::class, 'edit']],
	['POST', '/staff/dashboard/maintenance/edit', [\App\Modules\Maintenance\Controllers\MaintenanceController::class, 'edit']],
	['POST', '/staff/dashboard/maintenance/update-status', [\App\Modules\Maintenance\Controllers\MaintenanceController::class, 'updateStatus']],
	['GET', '/staff/dashboard/maintenance/ops-review', [\App\Modules\Maintenance\Controllers\MaintenanceController::class, 'opsReview']],
	['POST', '/staff/dashboard/maintenance/ops-review', [\App\Modules\Maintenance\Controllers\MaintenanceController::class, 'opsReview']],
	['GET', '/staff/dashboard/maintenance/finance-review', [\App\Modules\Maintenance\Controllers\MaintenanceController::class, 'financeReview']],
	['POST', '/staff/dashboard/maintenance/finance-review', [\App\Modules\Maintenance\Controllers\MaintenanceController::class, 'financeReview']],
	['GET', '/staff/dashboard/maintenance/assign-supplier', [\App\Modules\Maintenance\Controllers\MaintenanceController::class, 'assignSupplier']],
	['POST', '/staff/dashboard/maintenance/assign-supplier', [\App\Modules\Maintenance\Controllers\MaintenanceController::class, 'assignSupplier']],
	['GET', '/staff/dashboard/maintenance/verify-work', [\App\Modules\Maintenance\Controllers\MaintenanceController::class, 'verifyWork']],
	['POST', '/staff/dashboard/maintenance/verify-work', [\App\Modules\Maintenance\Controllers\MaintenanceController::class, 'verifyWork']],
	// Payslip
	['GET', '/staff/dashboard/payslip', [\App\Modules\Payslip\Controllers\PayslipController::class, 'index']],
	['GET', '/staff/dashboard/payslip/view', [\App\Modules\Payslip\Controllers\PayslipController::class, 'show']],
	// HR
	['GET', '/staff/dashboard/hr', [\App\Modules\HR\Controllers\HRController::class, 'index']],
	['GET', '/staff/dashboard/hr/employee', [\App\Modules\HR\Controllers\HRController::class, 'employee']],
	['POST', '/staff/dashboard/hr/employee/record', [\App\Modules\HR\Controllers\HRController::class, 'addRecord']],
	// Payroll
	['GET', '/staff/dashboard/payroll', [\App\Modules\Payroll\Controllers\PayrollController::class, 'index']],
	['POST', '/staff/dashboard/payroll/generate', [\App\Modules\Payroll\Controllers\PayrollController::class, 'generate']],
	['GET', '/staff/dashboard/payroll/edit', [\App\Modules\Payroll\Controllers\PayrollController::class, 'edit']],
	['POST', '/staff/dashboard/payroll/update', [\App\Modules\Payroll\Controllers\PayrollController::class, 'update']],
	// Payments
	['GET', '/staff/dashboard/payments', [\App\Modules\Payments\Controllers\PaymentsController::class, 'index']],
	['GET', '/staff/dashboard/payments/record', [\App\Modules\Payments\Controllers\PaymentsController::class, 'record']],
	['POST', '/staff/dashboard/payments/record', [\App\Modules\Payments\Controllers\PaymentsController::class, 'store']],
	['GET', '/staff/dashboard/payments/manage', [\App\Modules\Payments\Controllers\PaymentsController::class, 'manage']],
	// Notifications
	['GET', '/staff/dashboard/notifications', [\App\Modules\Notifications\Controllers\NotificationsController::class, 'index']],
	['GET', '/staff/dashboard/notifications/check', [\App\Modules\Notifications\Controllers\NotificationsController::class, 'check']],
	['GET', '/staff/dashboard/notifications/mark-read', [\App\Modules\Notifications\Controllers\NotificationsController::class, 'markAsRead']],
	['GET', '/staff/dashboard/notifications/mark-all-read', [\App\Modules\Notifications\Controllers\NotificationsController::class, 'markAllAsRead']],
	['GET', '/staff/dashboard/notifications/delete', [\App\Modules\Notifications\Controllers\NotificationsController::class, 'delete']],
	// Messages
	['GET', '/staff/dashboard/messages', [\App\Modules\Messages\Controllers\MessagesController::class, 'index']],
	['GET', '/staff/dashboard/messages/compose', [\App\Modules\Messages\Controllers\MessagesController::class, 'compose']],
	['POST', '/staff/dashboard/messages/compose', [\App\Modules\Messages\Controllers\MessagesController::class, 'send']],
	['GET', '/staff/dashboard/messages/view', [\App\Modules\Messages\Controllers\MessagesController::class, 'show']],
	['GET', '/staff/dashboard/messages/mark-read', [\App\Modules\Messages\Controllers\MessagesController::class, 'markAsRead']],
	['GET', '/staff/dashboard/messages/delete', [\App\Modules\Messages\Controllers\MessagesController::class, 'delete']],
	// Announcements
	['GET', '/staff/dashboard/announcements', [\App\Modules\Announcements\Controllers\AnnouncementsController::class, 'index']],
	['GET', '/staff/dashboard/announcements/create', [\App\Modules\Announcements\Controllers\AnnouncementsController::class, 'create']],
	['POST', '/staff/dashboard/announcements/create', [\App\Modules\Announcements\Controllers\AnnouncementsController::class, 'store']],
	['GET', '/staff/dashboard/announcements/view', [\App\Modules\Announcements\Controllers\AnnouncementsController::class, 'show']],
	['GET', '/staff/dashboard/announcements/edit', [\App\Modules\Announcements\Controllers\AnnouncementsController::class, 'edit']],
	['POST', '/staff/dashboard/announcements/edit', [\App\Modules\Announcements\Controllers\AnnouncementsController::class, 'update']],
	['GET', '/staff/dashboard/announcements/delete', [\App\Modules\Announcements\Controllers\AnnouncementsController::class, 'delete']],
	// Suppliers
	['GET', '/staff/dashboard/suppliers', [\App\Modules\Suppliers\Controllers\SuppliersController::class, 'index']],
	['GET', '/staff/dashboard/suppliers/create', [\App\Modules\Suppliers\Controllers\SuppliersController::class, 'create']],
	['POST', '/staff/dashboard/suppliers/create', [\App\Modules\Suppliers\Controllers\SuppliersController::class, 'store']],
	['GET', '/staff/dashboard/suppliers/edit', [\App\Modules\Suppliers\Controllers\SuppliersController::class, 'edit']],
	['POST', '/staff/dashboard/suppliers/edit', [\App\Modules\Suppliers\Controllers\SuppliersController::class, 'update']],
	['GET', '/staff/dashboard/suppliers/show', [\App\Modules\Suppliers\Controllers\SuppliersController::class, 'show']],
	['POST', '/staff/dashboard/suppliers/delete', [\App\Modules\Suppliers\Controllers\SuppliersController::class, 'delete']],
	// Expenses
	['GET', '/staff/dashboard/expenses', [\App\Modules\Expenses\Controllers\ExpensesController::class, 'index']],
	['GET', '/staff/dashboard/expenses/create', [\App\Modules\Expenses\Controllers\ExpensesController::class, 'create']],
	['POST', '/staff/dashboard/expenses/create', [\App\Modules\Expenses\Controllers\ExpensesController::class, 'store']],
	['GET', '/staff/dashboard/expenses/edit', [\App\Modules\Expenses\Controllers\ExpensesController::class, 'edit']],
	['POST', '/staff/dashboard/expenses/edit', [\App\Modules\Expenses\Controllers\ExpensesController::class, 'update']],
	['GET', '/staff/dashboard/expenses/show', [\App\Modules\Expenses\Controllers\ExpensesController::class, 'show']],
	['GET', '/staff/dashboard/expenses/approve', [\App\Modules\Expenses\Controllers\ExpensesController::class, 'approve']],
	['GET', '/staff/dashboard/expenses/mark-paid', [\App\Modules\Expenses\Controllers\ExpensesController::class, 'markPaid']],
	// Bills
	['GET', '/staff/dashboard/bills', [\App\Modules\Bills\Controllers\BillsController::class, 'index']],
	['GET', '/staff/dashboard/bills/create', [\App\Modules\Bills\Controllers\BillsController::class, 'create']],
	['POST', '/staff/dashboard/bills/create', [\App\Modules\Bills\Controllers\BillsController::class, 'store']],
	['GET', '/staff/dashboard/bills/edit', [\App\Modules\Bills\Controllers\BillsController::class, 'edit']],
	['POST', '/staff/dashboard/bills/edit', [\App\Modules\Bills\Controllers\BillsController::class, 'update']],
	['GET', '/staff/dashboard/bills/show', [\App\Modules\Bills\Controllers\BillsController::class, 'show']],
	['GET', '/staff/dashboard/bills/approve', [\App\Modules\Bills\Controllers\BillsController::class, 'approve']],
	['GET', '/staff/dashboard/bills/mark-paid', [\App\Modules\Bills\Controllers\BillsController::class, 'markPaid']],
	// Petty Cash
	['GET', '/staff/dashboard/petty-cash', [\App\Modules\PettyCash\Controllers\PettyCashController::class, 'index']],
	['GET', '/staff/dashboard/petty-cash/deposit', [\App\Modules\PettyCash\Controllers\PettyCashController::class, 'deposit']],
	['POST', '/staff/dashboard/petty-cash/deposit', [\App\Modules\PettyCash\Controllers\PettyCashController::class, 'processDeposit']],
	['GET', '/staff/dashboard/petty-cash/settings', [\App\Modules\PettyCash\Controllers\PettyCashController::class, 'settings']],
	['POST', '/staff/dashboard/petty-cash/settings', [\App\Modules\PettyCash\Controllers\PettyCashController::class, 'updateSettings']],
	['POST', '/staff/dashboard/petty-cash/link-expense', [\App\Modules\PettyCash\Controllers\PettyCashController::class, 'linkExpense']],
    ['GET', '/staff/dashboard/inventory', [InventoryController::class, 'index']],
	['GET', '/staff/dashboard/inventory/requisitions', [InventoryController::class, 'requisitions']],
	['POST', '/staff/dashboard/inventory/requisitions', [InventoryController::class, 'storeRequisition']],
	['POST', '/staff/dashboard/inventory/requisitions/status', [InventoryController::class, 'updateRequisitionStatus']],
	['POST', '/staff/dashboard/inventory/requisitions/complete', [InventoryController::class, 'completeRequisition']],
	['POST', '/staff/dashboard/inventory/requisitions/ops-verify', [InventoryController::class, 'verifyOpsRequisition']],
	['POST', '/staff/dashboard/inventory/requisitions/finance-approve', [InventoryController::class, 'approveFinanceRequisition']],
	['POST', '/staff/dashboard/inventory/requisitions/assign-supplier', [InventoryController::class, 'assignSupplierRequisition']],
	['POST', '/staff/dashboard/inventory/requisitions/create-po', [InventoryController::class, 'createPOFromRequisition']],
    ['POST', '/staff/dashboard/inventory/purchase-orders/receive', [InventoryController::class, 'receivePurchaseOrder']],
    ['POST', '/staff/dashboard/inventory/auto-import', [InventoryController::class, 'autoImport']],
    // Inventory Items CRUD
    ['GET', '/staff/dashboard/inventory/item/create', [InventoryController::class, 'createItem']],
    ['GET', '/staff/dashboard/inventory/item/edit', [InventoryController::class, 'editItem']],
    ['POST', '/staff/dashboard/inventory/item/store', [InventoryController::class, 'storeItem']],
    ['POST', '/staff/dashboard/inventory/item/update', [InventoryController::class, 'updateItem']],
    ['POST', '/staff/dashboard/inventory/item/delete', [InventoryController::class, 'deleteItem']],
    // Payment Gateway
    ['GET', '/staff/dashboard/payment-gateway', [\App\Modules\PaymentGateway\Controllers\PaymentGatewayController::class, 'index']],
    ['POST', '/staff/dashboard/payment-gateway/update', [\App\Modules\PaymentGateway\Controllers\PaymentGatewayController::class, 'update']],
    // M-Pesa Test
    ['GET', '/staff/dashboard/payment-gateway/mpesa-test', [\App\Modules\PaymentGateway\Controllers\MpesaTestController::class, 'index']],
    ['POST', '/staff/dashboard/payment-gateway/mpesa-test/stk-push', [\App\Modules\PaymentGateway\Controllers\MpesaTestController::class, 'testStkPush']],
    ['GET', '/staff/dashboard/payment-gateway/mpesa-test/query-status', [\App\Modules\PaymentGateway\Controllers\MpesaTestController::class, 'queryStatus']],
    // M-Pesa Callback (public endpoint for Safaricom)
    ['POST', '/api/mpesa/callback', [\App\Modules\PaymentGateway\Controllers\MpesaCallbackController::class, 'handle']],
    ['GET', '/staff/admin/settings', [SettingsController::class, 'index']],
    ['POST', '/staff/admin/settings', [SettingsController::class, 'update']],
    ['POST', '/staff/admin/settings/upload-image', [SettingsController::class, 'uploadImage']],

    // License Management
    ['GET', '/staff/dashboard/license', [\App\Modules\License\Controllers\LicenseController::class, 'index']],
    ['POST', '/staff/dashboard/license/activate', [\App\Modules\License\Controllers\LicenseController::class, 'activate']],
    
    // Sys admin console
    ['GET', '/sysadmin/login', [SysAdminController::class, 'login']],
    ['POST', '/sysadmin/login', [SysAdminController::class, 'login']],
    ['GET', '/sysadmin/logout', [SysAdminController::class, 'logout']],
    ['GET', '/sysadmin/dashboard', [SysAdminController::class, 'dashboard']],
];
