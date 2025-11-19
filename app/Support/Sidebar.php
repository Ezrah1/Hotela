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

            // Director
            'director' => [
				['href' => 'staff/dashboard', 'label' => 'Dashboard Overview'],
				['href' => 'staff/dashboard/pos/dashboard', 'label' => 'POS Dashboard'],
				['href' => 'staff/dashboard/tasks', 'label' => 'Task Manager'],
				['href' => 'staff/dashboard/bookings', 'label' => 'Reservations / Bookings'],
				['href' => 'staff/dashboard/rooms', 'label' => 'Rooms Management'],
				['href' => 'staff/dashboard/pos', 'label' => 'POS System'],
				['href' => 'staff/dashboard/orders', 'label' => 'Orders'],
				['href' => 'staff/dashboard/payments', 'label' => 'Payments & Transactions'],
				['href' => 'staff/dashboard/cash-banking', 'label' => 'Cash Banking'],
				['href' => 'staff/dashboard/suppliers', 'label' => 'Suppliers'],
				['href' => 'staff/dashboard/bills', 'label' => 'Bills'],
				['href' => 'staff/dashboard/expenses', 'label' => 'Expenses'],
				['href' => 'staff/dashboard/petty-cash', 'label' => 'Petty Cash'],
				['href' => 'staff/dashboard/inventory', 'label' => 'Inventory'],
				['href' => 'staff/dashboard/inventory/requisitions', 'label' => 'Requisitions'],
				['href' => 'staff/dashboard/maintenance', 'label' => 'Maintenance Requests'],
				['href' => 'staff/dashboard/staff', 'label' => 'Staff Management'],
				['href' => 'staff/dashboard/attendance', 'label' => 'Attendance Management'],
				['href' => 'staff/dashboard/hr', 'label' => 'Human Resources'],
				['href' => 'staff/dashboard/payroll', 'label' => 'Payroll Management'],
				['href' => 'staff/dashboard/roles', 'label' => 'Roles & Permissions'],
				['href' => 'staff/dashboard/reports/finance', 'label' => 'Finance Reports'],
				['href' => 'staff/dashboard/reports/sales', 'label' => 'Sales Reports'],
				['href' => 'staff/dashboard/reports/operations', 'label' => 'Operations Reports'],
				['href' => 'staff/dashboard/payment-gateway', 'label' => 'Payment Gateways'],
				['href' => 'staff/admin/settings', 'label' => 'Settings'],
				['href' => 'staff/dashboard/messages', 'label' => 'Messages'],
				['href' => 'staff/dashboard/announcements', 'label' => 'Announcements'],
				['href' => 'staff/dashboard/notifications', 'label' => 'Notifications Center'],
				['href' => 'staff/dashboard/audit-logs', 'label' => 'Audit Logs'],
            ],

            // Admin (Hotel Owner)
            'admin' => [
				['href' => 'staff/dashboard', 'label' => 'Dashboard Overview'],
				['href' => 'staff/dashboard/pos/dashboard', 'label' => 'POS Dashboard'],
				['href' => 'staff/dashboard/tasks', 'label' => 'Task Manager'],
				['href' => 'staff/dashboard/bookings', 'label' => 'Reservations / Bookings'],
				['href' => 'staff/dashboard/rooms', 'label' => 'Rooms Management'],
				['href' => 'staff/dashboard/pos', 'label' => 'POS System'],
				['href' => 'staff/dashboard/orders', 'label' => 'Orders'],
				['href' => 'staff/dashboard/payments', 'label' => 'Payments & Transactions'],
				['href' => 'staff/dashboard/cash-banking', 'label' => 'Cash Banking'],
				['href' => 'staff/dashboard/suppliers', 'label' => 'Suppliers'],
				['href' => 'staff/dashboard/bills', 'label' => 'Bills'],
				['href' => 'staff/dashboard/expenses', 'label' => 'Expenses'],
				['href' => 'staff/dashboard/petty-cash', 'label' => 'Petty Cash'],
				['href' => 'staff/dashboard/inventory', 'label' => 'Inventory'],
				['href' => 'staff/dashboard/inventory/requisitions', 'label' => 'Requisitions'],
				['href' => 'staff/dashboard/maintenance', 'label' => 'Maintenance Requests'],
				['href' => 'staff/dashboard/staff', 'label' => 'Staff Management'],
				['href' => 'staff/dashboard/attendance', 'label' => 'Attendance Management'],
				['href' => 'staff/dashboard/hr', 'label' => 'Human Resources'],
				['href' => 'staff/dashboard/payroll', 'label' => 'Payroll Management'],
				['href' => 'staff/dashboard/roles', 'label' => 'Roles & Permissions'],
				['href' => 'staff/dashboard/reports/finance', 'label' => 'Finance Reports'],
				['href' => 'staff/dashboard/reports/sales', 'label' => 'Sales Reports'],
				['href' => 'staff/dashboard/reports/operations', 'label' => 'Operations Reports'],
				['href' => 'staff/dashboard/payment-gateway', 'label' => 'Payment Gateways'],
				['href' => 'staff/admin/settings', 'label' => 'Settings'],
				['href' => 'staff/dashboard/messages', 'label' => 'Messages'],
				['href' => 'staff/dashboard/announcements', 'label' => 'Announcements'],
				['href' => 'staff/dashboard/notifications', 'label' => 'Notifications Center'],
				['href' => 'staff/dashboard/audit-logs', 'label' => 'Audit Logs'],
            ],

            // Finance Manager
            'finance_manager' => [
				['href' => 'staff/dashboard', 'label' => 'Dashboard'],
				['href' => 'staff/dashboard/pos/dashboard', 'label' => 'POS Dashboard'],
				['href' => 'staff/dashboard/tasks', 'label' => 'Task Manager'],
				['href' => 'staff/dashboard/payroll', 'label' => 'Payroll Management'],
				['href' => 'staff/dashboard/reports/sales', 'label' => 'Sales Reports'],
				['href' => 'staff/dashboard/reports/finance', 'label' => 'Finance Reports'],
				['href' => 'staff/dashboard/payments', 'label' => 'Payments & Transactions'],
				['href' => 'staff/dashboard/cash-banking', 'label' => 'Cash Banking'],
				['href' => 'staff/dashboard/suppliers', 'label' => 'Supplier Accounts'],
				['href' => 'staff/dashboard/bills', 'label' => 'Bills'],
				['href' => 'staff/dashboard/expenses', 'label' => 'Expenses'],
				['href' => 'staff/dashboard/petty-cash', 'label' => 'Petty Cash'],
				['href' => 'staff/dashboard/inventory', 'label' => 'Inventory (View + Update cost price)'],
				['href' => 'staff/dashboard/pos', 'label' => 'POS (View Orders)'],
				['href' => 'staff/dashboard/inventory/requisitions', 'label' => 'Requisitions (Approve/Review)'],
				['href' => 'staff/dashboard/maintenance', 'label' => 'Maintenance (Finance Review)'],
				['href' => 'staff/dashboard/payslip', 'label' => 'My Payslip'],
				['href' => 'staff/dashboard/messages', 'label' => 'Messages'],
				['href' => 'staff/dashboard/announcements', 'label' => 'Announcements'],
				['href' => 'staff/dashboard/notifications', 'label' => 'Notifications'],
            ],

            // Operations Manager
            'operation_manager' => [
				['href' => 'staff/dashboard', 'label' => 'Dashboard'],
				['href' => 'staff/dashboard/pos/dashboard', 'label' => 'POS Dashboard'],
				['href' => 'staff/dashboard/tasks', 'label' => 'Task Manager'],
				['href' => 'staff/dashboard/bookings', 'label' => 'Reservations / Check-ins / Check-outs'],
				['href' => 'staff/dashboard/rooms', 'label' => 'Rooms Status'],
				['href' => 'staff/dashboard/hr', 'label' => 'Human Resources'],
				['href' => 'staff/dashboard/inventory', 'label' => 'Inventory (View + Update quantities)'],
				['href' => 'staff/dashboard/inventory/requisitions', 'label' => 'Requisitions (Verify & Approve)'],
				['href' => 'staff/dashboard/maintenance', 'label' => 'Maintenance Requests'],
				['href' => 'staff/dashboard/suppliers', 'label' => 'Suppliers'],
				['href' => 'staff/dashboard/pos', 'label' => 'POS (View Orders)'],
				['href' => 'staff/dashboard/reports/operations', 'label' => 'Operations Reports'],
				['href' => 'staff/dashboard/payslip', 'label' => 'My Payslip'],
				['href' => 'staff/dashboard/messages', 'label' => 'Messages'],
				['href' => 'staff/dashboard/announcements', 'label' => 'Announcements'],
				['href' => 'staff/dashboard/notifications', 'label' => 'Notifications'],
            ],

				// Front Desk / Receptionist
				'receptionist' => [
					['href' => 'staff/dashboard', 'label' => 'Dashboard'],
					['href' => 'staff/dashboard/tasks', 'label' => 'Task Manager'],
					['href' => 'staff/dashboard/bookings', 'label' => 'Guest Reservations'],
					['href' => 'staff/dashboard/checkin', 'label' => 'Check-in / Check-out'],
					['href' => 'staff/dashboard/guests', 'label' => 'Current Guests'],
					['href' => 'staff/dashboard/rooms', 'label' => 'Room Status'],
					['href' => 'staff/dashboard/pos/dashboard', 'label' => 'POS Dashboard'],
					['href' => 'staff/dashboard/pos', 'label' => 'POS (Front desk orders)'],
					['href' => 'staff/dashboard/payments', 'label' => 'Payments'],
					['href' => 'staff/dashboard/invoices', 'label' => 'Guest Invoices'],
					['href' => 'staff/dashboard/maintenance', 'label' => 'Maintenance Requests'],
					['href' => 'staff/dashboard/inventory/requisitions', 'label' => 'Inventory Requests'],
					['href' => 'staff/dashboard/payslip', 'label' => 'My Payslip'],
					['href' => 'staff/dashboard/messages', 'label' => 'Messages'],
					['href' => 'staff/dashboard/announcements', 'label' => 'Announcements'],
					['href' => 'staff/dashboard/notifications', 'label' => 'Notifications'],
				],

            // Cashier
            'cashier' => [
                ['href' => 'staff/dashboard', 'label' => 'Dashboard'],
				['href' => 'staff/dashboard/tasks', 'label' => 'Task Manager'],
				['href' => 'staff/dashboard/pos/dashboard', 'label' => 'POS Dashboard'],
                ['href' => 'staff/dashboard/pos', 'label' => 'POS'],
				['href' => 'staff/dashboard/cash-banking', 'label' => 'Cash Banking'],
				['href' => 'staff/dashboard/orders', 'label' => 'Order History'],
				['href' => 'staff/dashboard/payments', 'label' => 'Payments'],
				['href' => 'staff/dashboard/petty-cash', 'label' => 'Petty Cash'],
				['href' => 'staff/dashboard/reports/daily-sales', 'label' => 'Daily Sales Report'],
				['href' => 'staff/dashboard/payslip', 'label' => 'My Payslip'],
				['href' => 'staff/dashboard/messages', 'label' => 'Messages'],
				['href' => 'staff/dashboard/announcements', 'label' => 'Announcements'],
				['href' => 'staff/dashboard/notifications', 'label' => 'Notifications'],
            ],

            // Service (waiters/bar)
            'service_agent' => [
                ['href' => 'staff/dashboard', 'label' => 'Dashboard'],
				['href' => 'staff/dashboard/tasks', 'label' => 'Task Manager'],
				['href' => 'staff/dashboard/pos/dashboard', 'label' => 'POS Dashboard'],
				['href' => 'staff/dashboard/pos', 'label' => 'POS (Restaurant/Bar)'],
				['href' => 'staff/dashboard/orders/my', 'label' => 'My Orders'],
				['href' => 'staff/dashboard/orders/updates', 'label' => 'Order Updates'],
				['href' => 'staff/dashboard/payslip', 'label' => 'My Payslip'],
				['href' => 'staff/dashboard/messages', 'label' => 'Messages'],
				['href' => 'staff/dashboard/announcements', 'label' => 'Announcements'],
				['href' => 'staff/dashboard/notifications', 'label' => 'Notifications'],
            ],

            // Kitchen
            'kitchen' => [
                ['href' => 'staff/dashboard', 'label' => 'Dashboard'],
				['href' => 'staff/dashboard/tasks', 'label' => 'Task Manager'],
				['href' => 'staff/dashboard/pos/dashboard', 'label' => 'POS Dashboard'],
				['href' => 'staff/dashboard/kot', 'label' => 'Kitchen Orders (KOT)'],
				['href' => 'staff/dashboard/kot/status', 'label' => 'Order Status Update'],
				['href' => 'staff/dashboard/inventory/requisitions', 'label' => 'Inventory Requests'],
				['href' => 'staff/dashboard/payslip', 'label' => 'My Payslip'],
				['href' => 'staff/dashboard/messages', 'label' => 'Messages'],
				['href' => 'staff/dashboard/announcements', 'label' => 'Announcements'],
				['href' => 'staff/dashboard/notifications', 'label' => 'Notifications'],
            ],

            // Housekeeping
            'housekeeping' => [
                ['href' => 'staff/dashboard', 'label' => 'Dashboard'],
				['href' => 'staff/dashboard/tasks', 'label' => 'Task Manager'],
				['href' => 'staff/dashboard/rooms/cleaning', 'label' => 'Room Cleaning List'],
				['href' => 'staff/dashboard/rooms/update-status', 'label' => 'Update Room Status'],
				['href' => 'staff/dashboard/inventory/requisitions', 'label' => 'Inventory Requests'],
				['href' => 'staff/dashboard/lost-found', 'label' => 'Lost & Found Log'],
				['href' => 'staff/dashboard/payslip', 'label' => 'My Payslip'],
				['href' => 'staff/dashboard/messages', 'label' => 'Messages'],
				['href' => 'staff/dashboard/announcements', 'label' => 'Announcements'],
				['href' => 'staff/dashboard/notifications', 'label' => 'Notifications'],
            ],

            // Ground keeping / maintenance
            'ground' => [
                ['href' => 'staff/dashboard', 'label' => 'Dashboard'],
				['href' => 'staff/dashboard/tasks', 'label' => 'Task Manager'],
				['href' => 'staff/dashboard/pos/dashboard', 'label' => 'POS Dashboard'],
				['href' => 'staff/dashboard/maintenance', 'label' => 'Maintenance Requests'],
				['href' => 'staff/dashboard/inventory/requisitions', 'label' => 'Inventory Requests'],
				['href' => 'staff/dashboard/payslip', 'label' => 'My Payslip'],
				['href' => 'staff/dashboard/messages', 'label' => 'Messages'],
				['href' => 'staff/dashboard/announcements', 'label' => 'Announcements'],
				['href' => 'staff/dashboard/notifications', 'label' => 'Notifications'],
            ],

            // Security
            'security' => [
                ['href' => 'staff/dashboard', 'label' => 'Dashboard'],
				['href' => 'staff/dashboard/tasks', 'label' => 'Task Manager'],
				['href' => 'staff/dashboard/pos/dashboard', 'label' => 'POS Dashboard'],
				['href' => 'staff/dashboard/visitors', 'label' => 'Visitor Log'],
				['href' => 'staff/dashboard/incidents', 'label' => 'Incident Reporting'],
				['href' => 'staff/dashboard/payslip', 'label' => 'My Payslip'],
				['href' => 'staff/dashboard/messages', 'label' => 'Messages'],
				['href' => 'staff/dashboard/announcements', 'label' => 'Announcements'],
				['href' => 'staff/dashboard/notifications', 'label' => 'Notifications'],
            ],
        ];

        $links = $map[$role] ?? [
            ['href' => 'staff/dashboard', 'label' => 'Dashboard'],
        ];

        // Add "My Attendance" link to all roles (if not already present)
        $hasMyAttendance = false;
        foreach ($links as $link) {
            if (strpos($link['href'], 'attendance/my-attendance') !== false || strpos($link['label'], 'My Attendance') !== false) {
                $hasMyAttendance = true;
                break;
            }
        }
        
        if (!$hasMyAttendance) {
            // Insert "My Attendance" before "My Payslip" if it exists, otherwise at the end
            $insertIndex = -1;
            foreach ($links as $index => $link) {
                if (strpos($link['label'], 'Payslip') !== false) {
                    $insertIndex = $index;
                    break;
                }
            }
            
            $myAttendanceLink = ['href' => 'staff/dashboard/attendance/my-attendance', 'label' => 'My Attendance'];
            
            if ($insertIndex >= 0) {
                array_splice($links, $insertIndex, 0, [$myAttendanceLink]);
            } else {
                $links[] = $myAttendanceLink;
            }
        }

        return $links;
    }
}
