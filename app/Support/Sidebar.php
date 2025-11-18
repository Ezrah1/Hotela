<?php

namespace App\Support;

class Sidebar
{
    public static function linksFor(?string $role): array
    {
			// Role-specific link blueprints (aligns with role-based blueprint; some routes may be stubs)
        $map = [
				// Super Admin / Developer (System Owner – Highest Level)
				// Note: System Owner has its own system dashboard separate from tenant UI.
				// Including here for completeness if ever rendered within tenant shell.
				'super_admin' => [
					['href' => 'sysadmin', 'label' => 'System Dashboard'],
					['href' => 'sysadmin/tenants', 'label' => 'Tenant Management'],
					['href' => 'sysadmin/billing', 'label' => 'Tenant Billing'],
					['href' => 'sysadmin/settings', 'label' => 'Tenant Settings'],
					['href' => 'sysadmin/logs', 'label' => 'System Logs'],
					['href' => 'sysadmin/updates', 'label' => 'System Updates'],
					['href' => 'sysadmin/debug', 'label' => 'Debug Tools'],
					['href' => 'sysadmin/impersonate', 'label' => 'User Impersonation'],
					['href' => 'sysadmin/channels', 'label' => 'Email/SMS Gateway Settings'],
					['href' => 'sysadmin/analytics', 'label' => 'Global Analytics'],
					['href' => 'sysadmin/support', 'label' => 'Global Support Tickets'],
				],

            // Admin (Hotel Owner)
            'admin' => [
				['href' => 'dashboard', 'label' => 'Dashboard Overview'],
				['href' => 'dashboard/pos/dashboard', 'label' => 'POS Dashboard'],
				['href' => 'dashboard/tasks', 'label' => 'Task Manager'],
				['href' => 'dashboard/bookings', 'label' => 'Reservations / Bookings'],
				['href' => 'dashboard/rooms', 'label' => 'Rooms Management'],
				['href' => 'dashboard/pos', 'label' => 'POS System'],
				['href' => 'dashboard/orders', 'label' => 'Orders'],
				['href' => 'dashboard/payments', 'label' => 'Payments & Transactions'],
				['href' => 'dashboard/suppliers', 'label' => 'Suppliers'],
				['href' => 'dashboard/bills', 'label' => 'Bills'],
				['href' => 'dashboard/expenses', 'label' => 'Expenses'],
				['href' => 'dashboard/petty-cash', 'label' => 'Petty Cash'],
				['href' => 'dashboard/inventory', 'label' => 'Inventory'],
				['href' => 'dashboard/inventory/requisitions', 'label' => 'Requisitions'],
				['href' => 'dashboard/staff', 'label' => 'Staff Management'],
				['href' => 'dashboard/hr', 'label' => 'Human Resources'],
				['href' => 'dashboard/payroll', 'label' => 'Payroll Management'],
				['href' => 'dashboard/roles', 'label' => 'Roles & Permissions'],
				['href' => 'dashboard/reports/finance', 'label' => 'Finance Reports'],
				['href' => 'dashboard/reports/sales', 'label' => 'Sales Reports'],
				['href' => 'dashboard/website', 'label' => 'Website Content Management'],
				['href' => 'admin/settings', 'label' => 'Settings'],
				['href' => 'dashboard/messages', 'label' => 'Messages'],
				['href' => 'dashboard/announcements', 'label' => 'Announcements'],
				['href' => 'dashboard/notifications', 'label' => 'Notifications Center'],
				['href' => 'dashboard/audit-logs', 'label' => 'Audit Logs'],
            ],

            // Finance Manager
            'finance_manager' => [
				['href' => 'dashboard', 'label' => 'Dashboard'],
				['href' => 'dashboard/pos/dashboard', 'label' => 'POS Dashboard'],
				['href' => 'dashboard/tasks', 'label' => 'Task Manager'],
				['href' => 'dashboard/payroll', 'label' => 'Payroll Management'],
				['href' => 'dashboard/reports/sales', 'label' => 'Sales Reports'],
				['href' => 'dashboard/reports/finance', 'label' => 'Finance Reports'],
				['href' => 'dashboard/payments', 'label' => 'Payments & Transactions'],
				['href' => 'dashboard/suppliers', 'label' => 'Supplier Accounts'],
				['href' => 'dashboard/bills', 'label' => 'Bills'],
				['href' => 'dashboard/expenses', 'label' => 'Expenses'],
				['href' => 'dashboard/petty-cash', 'label' => 'Petty Cash'],
				['href' => 'dashboard/inventory', 'label' => 'Inventory (View + Update cost price)'],
				['href' => 'dashboard/pos', 'label' => 'POS (View Orders)'],
				['href' => 'dashboard/inventory/requisitions', 'label' => 'Requisitions (Approve high-level)'],
				['href' => 'dashboard/payslip', 'label' => 'My Payslip'],
				['href' => 'dashboard/messages', 'label' => 'Messages'],
				['href' => 'dashboard/announcements', 'label' => 'Announcements'],
				['href' => 'dashboard/notifications', 'label' => 'Notifications'],
            ],

            // Operations Manager
            'operation_manager' => [
				['href' => 'dashboard', 'label' => 'Dashboard'],
				['href' => 'dashboard/pos/dashboard', 'label' => 'POS Dashboard'],
				['href' => 'dashboard/tasks', 'label' => 'Task Manager'],
				['href' => 'dashboard/bookings', 'label' => 'Reservations / Check-ins / Check-outs'],
				['href' => 'dashboard/rooms', 'label' => 'Rooms Status'],
				['href' => 'dashboard/hr', 'label' => 'Human Resources'],
				['href' => 'dashboard/inventory/requisitions', 'label' => 'Requisitions (Approve / Reject)'],
				['href' => 'dashboard/inventory', 'label' => 'Inventory (View + Update quantities)'],
				['href' => 'dashboard/pos', 'label' => 'POS (View Orders)'],
				['href' => 'dashboard/staff/attendance', 'label' => 'Staff Attendance Overview'],
				['href' => 'dashboard/maintenance', 'label' => 'Maintenance Requests'],
				['href' => 'dashboard/reports/operations', 'label' => 'Reports (Operational)'],
				['href' => 'dashboard/payslip', 'label' => 'My Payslip'],
				['href' => 'dashboard/messages', 'label' => 'Messages'],
				['href' => 'dashboard/announcements', 'label' => 'Announcements'],
				['href' => 'dashboard/notifications', 'label' => 'Notifications'],
            ],

				// Front Desk / Receptionist
				'receptionist' => [
					['href' => 'dashboard', 'label' => 'Dashboard'],
					['href' => 'dashboard/tasks', 'label' => 'Task Manager'],
					['href' => 'dashboard/bookings', 'label' => 'Guest Reservations'],
					['href' => 'dashboard/checkin', 'label' => 'Check-in / Check-out'],
					['href' => 'dashboard/guests', 'label' => 'Current Guests'],
					['href' => 'dashboard/rooms', 'label' => 'Room Status'],
					['href' => 'dashboard/pos/dashboard', 'label' => 'POS Dashboard'],
					['href' => 'dashboard/pos', 'label' => 'POS (Front desk orders)'],
					['href' => 'dashboard/payments', 'label' => 'Payments'],
					['href' => 'dashboard/invoices', 'label' => 'Guest Invoices'],
					['href' => 'dashboard/payslip', 'label' => 'My Payslip'],
					['href' => 'dashboard/messages', 'label' => 'Messages'],
					['href' => 'dashboard/announcements', 'label' => 'Announcements'],
					['href' => 'dashboard/notifications', 'label' => 'Notifications'],
				],

            // Cashier
            'cashier' => [
                ['href' => 'dashboard', 'label' => 'Dashboard'],
				['href' => 'dashboard/tasks', 'label' => 'Task Manager'],
				['href' => 'dashboard/pos/dashboard', 'label' => 'POS Dashboard'],
                ['href' => 'dashboard/pos', 'label' => 'POS'],
				['href' => 'dashboard/orders', 'label' => 'Order History'],
				['href' => 'dashboard/payments', 'label' => 'Payments'],
				['href' => 'dashboard/petty-cash', 'label' => 'Petty Cash'],
				['href' => 'dashboard/reports/daily-sales', 'label' => 'Daily Sales Report'],
				['href' => 'dashboard/payslip', 'label' => 'My Payslip'],
				['href' => 'dashboard/messages', 'label' => 'Messages'],
				['href' => 'dashboard/announcements', 'label' => 'Announcements'],
				['href' => 'dashboard/notifications', 'label' => 'Notifications'],
            ],

            // Service (waiters/bar)
            'service_agent' => [
                ['href' => 'dashboard', 'label' => 'Dashboard'],
				['href' => 'dashboard/tasks', 'label' => 'Task Manager'],
				['href' => 'dashboard/pos/dashboard', 'label' => 'POS Dashboard'],
				['href' => 'dashboard/pos', 'label' => 'POS (Restaurant/Bar)'],
				['href' => 'dashboard/orders/my', 'label' => 'My Orders'],
				['href' => 'dashboard/orders/updates', 'label' => 'Order Updates'],
				['href' => 'dashboard/payslip', 'label' => 'My Payslip'],
				['href' => 'dashboard/messages', 'label' => 'Messages'],
				['href' => 'dashboard/announcements', 'label' => 'Announcements'],
				['href' => 'dashboard/notifications', 'label' => 'Notifications'],
            ],

            // Kitchen
            'kitchen' => [
                ['href' => 'dashboard', 'label' => 'Dashboard'],
				['href' => 'dashboard/tasks', 'label' => 'Task Manager'],
				['href' => 'dashboard/pos/dashboard', 'label' => 'POS Dashboard'],
				['href' => 'dashboard/kot', 'label' => 'Kitchen Orders (KOT)'],
				['href' => 'dashboard/kot/status', 'label' => 'Order Status Update'],
				['href' => 'dashboard/inventory/requisitions', 'label' => 'Inventory Requests'],
				['href' => 'dashboard/payslip', 'label' => 'My Payslip'],
				['href' => 'dashboard/messages', 'label' => 'Messages'],
				['href' => 'dashboard/announcements', 'label' => 'Announcements'],
				['href' => 'dashboard/notifications', 'label' => 'Notifications'],
            ],

            // Housekeeping
            'housekeeping' => [
                ['href' => 'dashboard', 'label' => 'Dashboard'],
				['href' => 'dashboard/tasks', 'label' => 'Task Manager'],
				['href' => 'dashboard/rooms/cleaning', 'label' => 'Room Cleaning List'],
				['href' => 'dashboard/rooms/update-status', 'label' => 'Update Room Status'],
				['href' => 'dashboard/inventory/requisitions', 'label' => 'Inventory Requests'],
				['href' => 'dashboard/lost-found', 'label' => 'Lost & Found Log'],
				['href' => 'dashboard/payslip', 'label' => 'My Payslip'],
				['href' => 'dashboard/messages', 'label' => 'Messages'],
				['href' => 'dashboard/announcements', 'label' => 'Announcements'],
				['href' => 'dashboard/notifications', 'label' => 'Notifications'],
            ],

            // Ground keeping / maintenance
            'ground' => [
                ['href' => 'dashboard', 'label' => 'Dashboard'],
				['href' => 'dashboard/tasks', 'label' => 'Task Manager'],
				['href' => 'dashboard/pos/dashboard', 'label' => 'POS Dashboard'],
				['href' => 'dashboard/maintenance', 'label' => 'Maintenance Requests'],
				['href' => 'dashboard/maintenance/update-status', 'label' => 'Update Maintenance Status'],
				['href' => 'dashboard/inventory/requisitions', 'label' => 'Inventory Requests'],
				['href' => 'dashboard/payslip', 'label' => 'My Payslip'],
				['href' => 'dashboard/messages', 'label' => 'Messages'],
				['href' => 'dashboard/announcements', 'label' => 'Announcements'],
				['href' => 'dashboard/notifications', 'label' => 'Notifications'],
            ],

            // Security
            'security' => [
                ['href' => 'dashboard', 'label' => 'Dashboard'],
				['href' => 'dashboard/tasks', 'label' => 'Task Manager'],
				['href' => 'dashboard/pos/dashboard', 'label' => 'POS Dashboard'],
				['href' => 'dashboard/staff/attendance', 'label' => 'Staff Attendance (Check-in/Check-out)'],
				['href' => 'dashboard/visitors', 'label' => 'Visitor Log'],
				['href' => 'dashboard/incidents', 'label' => 'Incident Reporting'],
				['href' => 'dashboard/payslip', 'label' => 'My Payslip'],
				['href' => 'dashboard/messages', 'label' => 'Messages'],
				['href' => 'dashboard/announcements', 'label' => 'Announcements'],
				['href' => 'dashboard/notifications', 'label' => 'Notifications'],
            ],
        ];

        return $map[$role] ?? [
            ['href' => 'dashboard', 'label' => 'Dashboard'],
        ];
    }
}

