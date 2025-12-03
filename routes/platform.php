<?php

use App\Modules\Admin\Controllers\SettingsController;
use App\Modules\AuditLogs\Controllers\AuditLogsController;
use App\Modules\Auth\Controllers\LoginController;
use App\Modules\Backups\Controllers\BackupController;
use App\Modules\CashBanking\Controllers\CashBankingController;
use App\Modules\Dashboard\Controllers\DashboardController;
use App\Modules\Housekeeping\Controllers\HousekeepingController;
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
use App\Modules\Suppliers\Controllers\SupplierPortalController;

return [
    // Guest Website (main public website - root path)
    ['GET', '/', [GuestController::class, 'home']],
    ['GET', '/rooms', [GuestController::class, 'rooms']],
    ['GET', '/drinks-food', [GuestController::class, 'food']],
    ['GET', '/about', [GuestController::class, 'about']],
    ['GET', '/contact', [GuestController::class, 'contact']],
    ['GET', '/conferencing', [GuestController::class, 'conferencing']],
    ['GET', '/events', [GuestController::class, 'events']],
    ['GET', '/gallery', [GuestController::class, 'gallery']],
    // Gallery Management (Admin)
    ['GET', '/staff/dashboard/gallery', [\App\Modules\Website\Controllers\GalleryController::class, 'index']],
    ['GET', '/staff/dashboard/gallery/create', [\App\Modules\Website\Controllers\GalleryController::class, 'create']],
    ['POST', '/staff/dashboard/gallery/store', [\App\Modules\Website\Controllers\GalleryController::class, 'store']],
    ['GET', '/staff/dashboard/gallery/edit', [\App\Modules\Website\Controllers\GalleryController::class, 'edit']],
    ['POST', '/staff/dashboard/gallery/update', [\App\Modules\Website\Controllers\GalleryController::class, 'update']],
    ['POST', '/staff/dashboard/gallery/delete', [\App\Modules\Website\Controllers\GalleryController::class, 'delete']],
    ['GET', '/order', [OrderController::class, 'show']],
    ['GET', '/order/cart', [OrderController::class, 'cart']],
    ['POST', '/order/cart', [OrderController::class, 'cart']],
    ['GET', '/order/availability', [OrderController::class, 'availability']],
    ['POST', '/order/checkout', [OrderController::class, 'checkout']],
    ['GET', '/order/payment-waiting', [OrderController::class, 'paymentWaiting']],
    ['GET', '/order/check-payment-status', [OrderController::class, 'checkPaymentStatus']],
    ['POST', '/order/confirm-payment', [OrderController::class, 'confirmPayment']],
    ['GET', '/booking', [BookingController::class, 'publicForm']],
    ['POST', '/booking/check', [BookingController::class, 'checkAvailability']],
    ['POST', '/booking', [BookingController::class, 'store']],
    ['GET', '/booking/confirmation', [BookingController::class, 'confirmation']],
    ['GET', '/api/booking/payment-status', [BookingController::class, 'checkPaymentStatus']],
    ['GET', '/guest/login', [GuestPortalController::class, 'showLogin']],
    ['POST', '/guest/login', [GuestPortalController::class, 'authenticate']],
    ['POST', '/guest/login/request-code', [GuestPortalController::class, 'requestCode']],
    ['GET', '/guest/logout', [GuestPortalController::class, 'logout']],
    ['POST', '/guest/logout', [GuestPortalController::class, 'logout']],
    ['GET', '/guest/setup-password', [GuestPortalController::class, 'showSetupPassword']],
    ['POST', '/guest/setup-password', [GuestPortalController::class, 'setupPassword']],
    ['GET', '/guest/forgot-password', [GuestPortalController::class, 'showForgotPassword']],
    ['POST', '/guest/forgot-password', [GuestPortalController::class, 'requestPasswordReset']],
    ['GET', '/guest/reset-password', [GuestPortalController::class, 'showResetPassword']],
    ['POST', '/guest/reset-password', [GuestPortalController::class, 'resetPassword']],
    ['GET', '/guest/portal', [GuestPortalController::class, 'dashboard']],
    ['GET', '/guest/upcoming-bookings', [GuestPortalController::class, 'upcomingBookings']],
    ['GET', '/guest/past-bookings', [GuestPortalController::class, 'pastBookings']],
    ['GET', '/guest/active-orders', [GuestPortalController::class, 'activeOrders']],
    ['GET', '/guest/booking', [GuestPortalController::class, 'booking']],
    ['POST', '/guest/booking/pay', [GuestPortalController::class, 'payBooking']],
    ['GET', '/guest/orders', [GuestPortalController::class, 'orders']],
    ['GET', '/guest/room-services', [GuestPortalController::class, 'roomServices']],
    ['POST', '/guest/request-service', [GuestPortalController::class, 'requestService']],
    ['POST', '/guest/toggle-dnd', [GuestPortalController::class, 'toggleDND']],
    ['GET', '/guest/order', [GuestPortalController::class, 'order']],
    ['GET', '/guest/order/pay', [GuestPortalController::class, 'payOrder']],
    ['POST', '/guest/order/change-payment', [GuestPortalController::class, 'changePaymentMethod']],
    ['GET', '/guest/profile', [GuestPortalController::class, 'profile']],
    ['GET', '/guest/folios', [GuestPortalController::class, 'folios']],
    ['GET', '/guest/notifications', [GuestPortalController::class, 'notifications']],
    ['GET', '/guest/reviews', [GuestPortalController::class, 'reviews']],
    ['POST', '/guest/reviews/create', [GuestPortalController::class, 'createReview']],
    ['GET', '/guest/contact', [GuestPortalController::class, 'contact']],
    ['POST', '/guest/contact', [GuestPortalController::class, 'submitContact']],
    
    // Supplier Portal
    ['GET', '/supplier/login', [SupplierPortalController::class, 'showLogin']],
    ['POST', '/supplier/login', [SupplierPortalController::class, 'authenticate']],
    ['POST', '/supplier/login/request-code', [SupplierPortalController::class, 'requestCode']],
    ['POST', '/supplier/logout', [SupplierPortalController::class, 'logout']],
    ['GET', '/supplier/portal', [SupplierPortalController::class, 'dashboard']],
    ['GET', '/supplier/purchase-orders', [SupplierPortalController::class, 'purchaseOrders']],
    ['GET', '/supplier/purchase-order', [SupplierPortalController::class, 'showPurchaseOrder']],
    
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
	['GET', '/staff/dashboard/guests', [BookingController::class, 'guests']],
	['GET', '/staff/dashboard/invoices', [BookingController::class, 'invoices']],
    ['GET', '/staff/dashboard/bookings/calendar-view', [BookingController::class, 'calendarView']],
    ['GET', '/staff/dashboard/bookings/calendar', [BookingController::class, 'calendar']],
    ['POST', '/staff/dashboard/bookings/check-in', [BookingController::class, 'checkIn']],
    ['POST', '/staff/dashboard/bookings/check-out', [BookingController::class, 'checkOut']],
    ['POST', '/staff/dashboard/bookings/cancel', [BookingController::class, 'cancel']],
    ['GET', '/staff/dashboard/bookings/folio', [BookingController::class, 'folio']],
    ['POST', '/staff/dashboard/bookings/folio-entry', [BookingController::class, 'addFolioEntry']],
    ['POST', '/staff/dashboard/bookings/folio-mpesa-payment', [BookingController::class, 'folioMpesaPayment']],
    ['POST', '/staff/dashboard/bookings/folio-confirm-payment', [BookingController::class, 'confirmFolioPayment']],
    ['GET', '/staff/dashboard/bookings/folio-query-payment', [BookingController::class, 'queryFolioPaymentStatus']],
    
    // Folio Management (Centralized)
    ['GET', '/staff/dashboard/folios', [\App\Modules\PMS\Controllers\FolioController::class, 'index']],
    ['GET', '/staff/dashboard/folios/view', [\App\Modules\PMS\Controllers\FolioController::class, 'show']],
    ['POST', '/staff/dashboard/bookings/assign-room', [BookingController::class, 'assignRoom']],
    ['GET', '/staff/dashboard/bookings/edit', [BookingController::class, 'edit']],
    ['POST', '/staff/dashboard/bookings/update', [BookingController::class, 'update']],
    ['GET', '/staff/dashboard/rooms', [\App\Modules\PMS\Controllers\RoomsController::class, 'index']],
    ['POST', '/staff/dashboard/rooms/update-status', [\App\Modules\PMS\Controllers\RoomsController::class, 'updateStatus']],
    ['GET', '/staff/dashboard/rooms/select-edit', [\App\Modules\PMS\Controllers\RoomsController::class, 'selectEdit']],
    ['GET', '/staff/dashboard/rooms/edit', [\App\Modules\PMS\Controllers\RoomsController::class, 'editRoom']],
    ['POST', '/staff/dashboard/rooms/update', [\App\Modules\PMS\Controllers\RoomsController::class, 'updateRoom']],
    
    // Housekeeping routes
    ['GET', '/staff/dashboard/housekeeping', [\App\Modules\Housekeeping\Controllers\HousekeepingController::class, 'index']],
    ['POST', '/staff/dashboard/housekeeping/update-room-status', [\App\Modules\Housekeeping\Controllers\HousekeepingController::class, 'updateRoomStatus']],
    ['POST', '/staff/dashboard/housekeeping/create-task', [\App\Modules\Housekeeping\Controllers\HousekeepingController::class, 'createTask']],
    ['POST', '/staff/dashboard/housekeeping/update-task', [\App\Modules\Housekeeping\Controllers\HousekeepingController::class, 'updateTask']],
    ['POST', '/staff/dashboard/housekeeping/report-maintenance', [\App\Modules\Housekeeping\Controllers\HousekeepingController::class, 'reportMaintenance']],
    ['POST', '/staff/dashboard/housekeeping/set-dnd', [\App\Modules\Housekeeping\Controllers\HousekeepingController::class, 'setDND']],
    ['POST', '/staff/dashboard/housekeeping/update-guest-request', [\App\Modules\Housekeeping\Controllers\HousekeepingController::class, 'updateGuestRequest']],
    ['GET', '/staff/dashboard/housekeeping/room', [\App\Modules\Housekeeping\Controllers\HousekeepingController::class, 'viewRoom']],
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
    ['GET', '/staff/dashboard/orders/my', [\App\Modules\Orders\Controllers\OrdersController::class, 'my']],
    ['GET', '/staff/dashboard/orders/updates', [\App\Modules\Orders\Controllers\OrdersController::class, 'updates']],
    ['GET', '/staff/dashboard/orders/show', [\App\Modules\Orders\Controllers\OrdersController::class, 'show']],
    ['POST', '/staff/dashboard/orders/update-status', [\App\Modules\Orders\Controllers\OrdersController::class, 'updateStatus']],
    ['POST', '/staff/dashboard/orders/assign-staff', [\App\Modules\Orders\Controllers\OrdersController::class, 'assignStaff']],
    ['POST', '/staff/dashboard/orders/add-comment', [\App\Modules\Orders\Controllers\OrdersController::class, 'addComment']],
    ['POST', '/staff/dashboard/orders/cancel', [\App\Modules\Orders\Controllers\OrdersController::class, 'cancel']],
    ['POST', '/staff/dashboard/orders/confirm-payment', [\App\Modules\Orders\Controllers\OrdersController::class, 'confirmPayment']],
    ['POST', '/staff/dashboard/orders/request-payment', [\App\Modules\Orders\Controllers\OrdersController::class, 'requestPayment']],
    ['GET', '/staff/dashboard/orders/poll', [\App\Modules\Orders\Controllers\OrdersController::class, 'poll']],
    
    // Kitchen Order Tickets (KOT)
    ['GET', '/staff/dashboard/kot', [\App\Modules\Kitchen\Controllers\KOTController::class, 'index']],
    ['GET', '/staff/dashboard/kot/status', [\App\Modules\Kitchen\Controllers\KOTController::class, 'status']],
    ['POST', '/staff/dashboard/kot/update-status', [\App\Modules\Kitchen\Controllers\KOTController::class, 'updateStatus']],
    
	['GET', '/staff/dashboard/staff', [StaffController::class, 'index']],
	['GET', '/staff/dashboard/staff/create', [StaffController::class, 'create']],
	['POST', '/staff/dashboard/staff/store', [StaffController::class, 'store']],
	['GET', '/staff/dashboard/staff/profile', [StaffController::class, 'profile']],
	['GET', '/staff/dashboard/staff/edit', [StaffController::class, 'edit']],
	['POST', '/staff/dashboard/staff/update', [StaffController::class, 'update']],
	// Attendance
	['GET', '/staff/dashboard/attendance', [\App\Modules\Attendance\Controllers\AttendanceController::class, 'index']],
	['POST', '/staff/dashboard/attendance/check-in', [\App\Modules\Attendance\Controllers\AttendanceController::class, 'checkIn']],
	['POST', '/staff/dashboard/attendance/check-out', [\App\Modules\Attendance\Controllers\AttendanceController::class, 'checkOut']],
	['POST', '/staff/dashboard/attendance/grant-override', [\App\Modules\Attendance\Controllers\AttendanceController::class, 'grantOverride']],
	['POST', '/staff/dashboard/attendance/ignore-anomaly', [\App\Modules\Attendance\Controllers\AttendanceController::class, 'ignoreAnomaly']],
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
	['GET', '/staff/dashboard/reports/daily-sales', [SalesReportController::class, 'dailySales']],
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
	['POST', '/staff/dashboard/inventory/requisitions/director-approve', [InventoryController::class, 'approveDirectorRequisition']],
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
    // Inventory Category Management
    ['POST', '/staff/dashboard/inventory/category/create', [InventoryController::class, 'createCategory']],
    
    // POS Item Management
    ['GET', '/staff/dashboard/pos/items', [POSController::class, 'items']],
    ['GET', '/staff/dashboard/pos/items/create', [POSController::class, 'createItem']],
    ['POST', '/staff/dashboard/pos/items/store', [POSController::class, 'storeItem']],
    ['GET', '/staff/dashboard/pos/items/edit', [POSController::class, 'editItem']],
    ['POST', '/staff/dashboard/pos/items/update', [POSController::class, 'updateItem']],
    ['POST', '/staff/dashboard/pos/items/delete', [POSController::class, 'deleteItem']],
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
    
    // Backup Management
    ['GET', '/staff/dashboard/backups', [BackupController::class, 'index']],
    ['POST', '/staff/dashboard/backups/create', [BackupController::class, 'create']],
    ['GET', '/staff/dashboard/backups/download', [BackupController::class, 'download']],
    ['POST', '/staff/dashboard/backups/delete', [BackupController::class, 'delete']],
    
    // Audit Logs
    ['GET', '/staff/dashboard/audit-logs', [AuditLogsController::class, 'index']],

    // License Management
    ['GET', '/staff/dashboard/license', [\App\Modules\License\Controllers\LicenseController::class, 'index']],
    ['POST', '/staff/dashboard/license/activate', [\App\Modules\License\Controllers\LicenseController::class, 'activate']],
    ['POST', '/staff/dashboard/license/fetch', [\App\Modules\License\Controllers\LicenseController::class, 'fetchLicense']],
    ['GET', '/staff/dashboard/license/manage', [\App\Modules\License\Controllers\LicenseController::class, 'manage']],
    ['POST', '/staff/dashboard/license/upgrade', [\App\Modules\License\Controllers\LicenseController::class, 'upgrade']],
    
    // Sys admin console
    ['GET', '/sysadmin', function() {
        header('Location: ' . base_url('sysadmin/login'));
        exit;
    }],
    ['GET', '/sysadmin/login', [SysAdminController::class, 'login']],
    ['POST', '/sysadmin/login', [SysAdminController::class, 'login']],
    ['GET', '/sysadmin/logout', [SysAdminController::class, 'logout']],
    ['GET', '/sysadmin/dashboard', [SysAdminController::class, 'dashboard']],
    ['GET', '/sysadmin/tenants', [SysAdminController::class, 'tenants']],
    ['GET', '/sysadmin/tenants/view', [SysAdminController::class, 'viewTenant']],
    ['GET', '/sysadmin/licenses', [SysAdminController::class, 'licenses']],
    ['GET', '/sysadmin/licenses/view', [SysAdminController::class, 'viewLicense']],
    ['POST', '/sysadmin/licenses/upgrade', [SysAdminController::class, 'upgradeLicense']],
    ['POST', '/sysadmin/licenses/revoke', [SysAdminController::class, 'revokeLicense']],
    ['POST', '/sysadmin/licenses/generate', [SysAdminController::class, 'generateLicense']],
    ['POST', '/sysadmin/licenses/send', [SysAdminController::class, 'sendLicense']],
    ['GET', '/sysadmin/packages/assign', [SysAdminController::class, 'assignPackage']],
    ['POST', '/sysadmin/packages/assign', [SysAdminController::class, 'assignPackage']],
    ['GET', '/sysadmin/logs', [SysAdminController::class, 'logs']],
    ['GET', '/sysadmin/health', [SysAdminController::class, 'health']],
    ['GET', '/sysadmin/analytics', [SysAdminController::class, 'analytics']],
    ['GET', '/sysadmin/settings', [SysAdminController::class, 'settings']],
    ['POST', '/sysadmin/settings', [SysAdminController::class, 'settings']],
    ['POST', '/sysadmin/settings/setup-2fa', [SysAdminController::class, 'setup2FA']],
    ['GET', '/sysadmin/packages', [SysAdminController::class, 'packages']],
    ['POST', '/sysadmin/packages', [SysAdminController::class, 'packages']],
];
