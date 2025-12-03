<?php

namespace App\Support;

class Sidebar
{
    public static function linksFor(?string $role, ?array $allRoles = null): array
    {
			// Role-specific link blueprints (aligns with role-based blueprint; some routes may be stubs)
        $map = [
				// Super Admin / Developer (System Owner – Highest Level)
				// Note: System Owner has its own system dashboard separate from tenant UI.
				// Including here for completeness if ever rendered within tenant shell.
				// Super Admin / Developer (System Owner)
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

            // Director - Full access, organized by importance
            'director' => [
				// Core Dashboard
				['href' => 'staff/dashboard', 'label' => 'Dashboard'],
				
			// Operations Section (Most Critical)
			['href' => 'staff/dashboard/bookings', 'label' => 'Reservations & Bookings'],
			['href' => 'staff/dashboard/rooms', 'label' => 'Rooms Management'],
			['href' => 'staff/dashboard/housekeeping', 'label' => 'Housekeeping'],
			['href' => 'staff/dashboard/orders', 'label' => 'Orders Management'],
			['href' => 'staff/dashboard/pos', 'label' => 'POS System'],
			['href' => 'staff/dashboard/pos/dashboard', 'label' => 'POS Sales'],
			['href' => 'staff/dashboard/pos/items', 'label' => 'POS Items'],
			
			// Financial Section
				['href' => 'staff/dashboard/folios', 'label' => 'Folio Management'],
				['href' => 'staff/dashboard/payments', 'label' => 'Payments & Transactions'],
				['href' => 'staff/dashboard/cash-banking', 'label' => 'Cash Banking'],
				['href' => 'staff/dashboard/petty-cash', 'label' => 'Petty Cash'],
				['href' => 'staff/dashboard/bills', 'label' => 'Bills'],
				['href' => 'staff/dashboard/expenses', 'label' => 'Expenses'],
				
				// Inventory & Suppliers
				['href' => 'staff/dashboard/inventory', 'label' => 'Inventory'],
				['href' => 'staff/dashboard/inventory/requisitions', 'label' => 'Requisitions'],
				['href' => 'staff/dashboard/suppliers', 'label' => 'Suppliers'],
				
				// Human Resources
				['href' => 'staff/dashboard/staff', 'label' => 'Staff Management'],
				['href' => 'staff/dashboard/attendance', 'label' => 'Attendance'],
				['href' => 'staff/dashboard/hr', 'label' => 'Human Resources'],
				['href' => 'staff/dashboard/payroll', 'label' => 'Payroll'],
				['href' => 'staff/dashboard/roles', 'label' => 'Roles & Permissions'],
				
				// Reports & Analytics
				['href' => 'staff/dashboard/reports/sales', 'label' => 'Sales Reports'],
				['href' => 'staff/dashboard/reports/finance', 'label' => 'Finance Reports'],
				['href' => 'staff/dashboard/reports/operations', 'label' => 'Operations Reports'],
				
				// Maintenance
				['href' => 'staff/dashboard/maintenance', 'label' => 'Maintenance'],
				['href' => 'staff/dashboard/tasks', 'label' => 'Task Manager'],
				
				// System Settings
				['href' => 'staff/admin/settings', 'label' => 'Settings'],
				['href' => 'staff/dashboard/license/manage', 'label' => 'License Management'],
				['href' => 'staff/dashboard/backups', 'label' => 'Backup Management'],
				['href' => 'staff/dashboard/payment-gateway', 'label' => 'Payment Gateways'],
				['href' => 'staff/dashboard/audit-logs', 'label' => 'Audit Logs'],
				
				// Communication
				['href' => 'staff/dashboard/messages', 'label' => 'Messages'],
				['href' => 'staff/dashboard/announcements', 'label' => 'Announcements'],
				['href' => 'staff/dashboard/notifications', 'label' => 'Notifications'],
            ],

            // Technical Administrator - System maintenance focus
            'tech' => [
				// Core Dashboard
				['href' => 'staff/dashboard', 'label' => 'Dashboard'],
				
				// System Management (Primary Role)
				['href' => 'staff/admin/settings', 'label' => 'System Settings'],
				['href' => 'staff/dashboard/backups', 'label' => 'Backup Management'],
				['href' => 'staff/dashboard/audit-logs', 'label' => 'Audit Logs'],
				['href' => 'staff/dashboard/roles', 'label' => 'Roles & Permissions'],
				
				// Technical Operations
				['href' => 'staff/dashboard/staff', 'label' => 'Staff Management'],
				['href' => 'staff/dashboard/attendance', 'label' => 'Attendance Management'],
				
				// Personal
				['href' => 'staff/dashboard/payslip', 'label' => 'My Payslip'],
				
				// Communication
				['href' => 'staff/dashboard/messages', 'label' => 'Messages'],
				['href' => 'staff/dashboard/announcements', 'label' => 'Announcements'],
				['href' => 'staff/dashboard/notifications', 'label' => 'Notifications'],
            ],

            // 'admin' role is deprecated - use 'director' instead
            // Keeping for backward compatibility but not shown in tenant UIs
            'admin' => [
				// Core Dashboard
				['href' => 'staff/dashboard', 'label' => 'Dashboard'],
				
				// Operations Section (Most Critical)
				['href' => 'staff/dashboard/bookings', 'label' => 'Reservations & Bookings'],
				['href' => 'staff/dashboard/rooms', 'label' => 'Rooms Management'],
				['href' => 'staff/dashboard/housekeeping', 'label' => 'Housekeeping'],
				['href' => 'staff/dashboard/gallery', 'label' => 'Gallery Management'],
				['href' => 'staff/dashboard/orders', 'label' => 'Orders Management'],
				['href' => 'staff/dashboard/pos', 'label' => 'POS System'],
				['href' => 'staff/dashboard/pos/dashboard', 'label' => 'POS Sales'],
				
				// Financial Section
				['href' => 'staff/dashboard/folios', 'label' => 'Folio Management'],
				['href' => 'staff/dashboard/payments', 'label' => 'Payments & Transactions'],
				['href' => 'staff/dashboard/cash-banking', 'label' => 'Cash Banking'],
				['href' => 'staff/dashboard/petty-cash', 'label' => 'Petty Cash'],
				['href' => 'staff/dashboard/bills', 'label' => 'Bills'],
				['href' => 'staff/dashboard/expenses', 'label' => 'Expenses'],
				
				// Inventory & Suppliers
				['href' => 'staff/dashboard/inventory', 'label' => 'Inventory'],
				['href' => 'staff/dashboard/inventory/requisitions', 'label' => 'Requisitions'],
				['href' => 'staff/dashboard/suppliers', 'label' => 'Suppliers'],
				
				// Human Resources
				['href' => 'staff/dashboard/staff', 'label' => 'Staff Management'],
				['href' => 'staff/dashboard/attendance', 'label' => 'Attendance'],
				['href' => 'staff/dashboard/hr', 'label' => 'Human Resources'],
				['href' => 'staff/dashboard/payroll', 'label' => 'Payroll'],
				['href' => 'staff/dashboard/roles', 'label' => 'Roles & Permissions'],
				
				// Reports & Analytics
				['href' => 'staff/dashboard/reports/sales', 'label' => 'Sales Reports'],
				['href' => 'staff/dashboard/reports/finance', 'label' => 'Finance Reports'],
				['href' => 'staff/dashboard/reports/operations', 'label' => 'Operations Reports'],
				
				// Maintenance
				['href' => 'staff/dashboard/maintenance', 'label' => 'Maintenance'],
				['href' => 'staff/dashboard/tasks', 'label' => 'Task Manager'],
				
				// System Settings
				['href' => 'staff/admin/settings', 'label' => 'Settings'],
				['href' => 'staff/dashboard/backups', 'label' => 'Backup Management'],
				['href' => 'staff/dashboard/payment-gateway', 'label' => 'Payment Gateways'],
				['href' => 'staff/dashboard/audit-logs', 'label' => 'Audit Logs'],
				
				// Communication
				['href' => 'staff/dashboard/messages', 'label' => 'Messages'],
				['href' => 'staff/dashboard/announcements', 'label' => 'Announcements'],
				['href' => 'staff/dashboard/notifications', 'label' => 'Notifications'],
            ],

            // Finance Manager - Financial operations focus
            'finance_manager' => [
				// Core Dashboard
				['href' => 'staff/dashboard', 'label' => 'Dashboard'],
				
				// Financial Operations (Primary Focus)
				['href' => 'staff/dashboard/folios', 'label' => 'Folio Management'],
				['href' => 'staff/dashboard/payments', 'label' => 'Payments & Transactions'],
				['href' => 'staff/dashboard/cash-banking', 'label' => 'Cash Banking'],
				['href' => 'staff/dashboard/petty-cash', 'label' => 'Petty Cash'],
				['href' => 'staff/dashboard/bills', 'label' => 'Bills'],
				['href' => 'staff/dashboard/expenses', 'label' => 'Expenses'],
				
				// Financial Reports
				['href' => 'staff/dashboard/reports/finance', 'label' => 'Finance Reports'],
				['href' => 'staff/dashboard/reports/sales', 'label' => 'Sales Reports'],
				
				// Supplier & Accounts Payable
				['href' => 'staff/dashboard/suppliers', 'label' => 'Suppliers'],
				
				// Inventory Management (Cost Control)
				['href' => 'staff/dashboard/inventory', 'label' => 'Inventory (Cost Management)'],
				['href' => 'staff/dashboard/inventory/requisitions', 'label' => 'Requisitions (Approve)'],
				
				// View Orders & POS (For Financial Tracking)
				['href' => 'staff/dashboard/pos/dashboard', 'label' => 'POS Sales'],
				['href' => 'staff/dashboard/pos', 'label' => 'POS Orders'],
				
				// Maintenance Review (Financial Approval)
				['href' => 'staff/dashboard/maintenance', 'label' => 'Maintenance (Review)'],
				
				// Payroll & HR
				['href' => 'staff/dashboard/payroll', 'label' => 'Payroll Management'],
				['href' => 'staff/dashboard/hr', 'label' => 'HR (Finance View)'],
				
				// Personal
				['href' => 'staff/dashboard/payslip', 'label' => 'My Payslip'],
				
				// Communication
				['href' => 'staff/dashboard/messages', 'label' => 'Messages'],
				['href' => 'staff/dashboard/announcements', 'label' => 'Announcements'],
				['href' => 'staff/dashboard/notifications', 'label' => 'Notifications'],
            ],

            // Operations Manager - Day-to-day operations focus
            'operation_manager' => [
				// Core Dashboard
				['href' => 'staff/dashboard', 'label' => 'Dashboard'],
				
				// Guest Operations (Highest Priority)
				['href' => 'staff/dashboard/bookings', 'label' => 'Reservations & Check-ins'],
				['href' => 'staff/dashboard/folios', 'label' => 'Folio Management'],
				['href' => 'staff/dashboard/rooms', 'label' => 'Rooms Status'],
				
				// Housekeeping & Maintenance
				['href' => 'staff/dashboard/housekeeping', 'label' => 'Housekeeping'],
				['href' => 'staff/dashboard/maintenance', 'label' => 'Maintenance Requests'],
				
				// F&B Operations
				['href' => 'staff/dashboard/orders', 'label' => 'Orders Management'],
				['href' => 'staff/dashboard/pos/dashboard', 'label' => 'POS Sales'],
				
				// Inventory Management
				['href' => 'staff/dashboard/inventory', 'label' => 'Inventory'],
				['href' => 'staff/dashboard/inventory/requisitions', 'label' => 'Requisitions (Approve)'],
				['href' => 'staff/dashboard/suppliers', 'label' => 'Suppliers'],
				
				// Task Management
				['href' => 'staff/dashboard/tasks', 'label' => 'Task Manager'],
				
				// Human Resources (Operations View)
				['href' => 'staff/dashboard/hr', 'label' => 'Human Resources'],
				
				// Reports
				['href' => 'staff/dashboard/reports/operations', 'label' => 'Operations Reports'],
				
				// Personal
				['href' => 'staff/dashboard/payslip', 'label' => 'My Payslip'],
				
				// Communication
				['href' => 'staff/dashboard/messages', 'label' => 'Messages'],
				['href' => 'staff/dashboard/announcements', 'label' => 'Announcements'],
				['href' => 'staff/dashboard/notifications', 'label' => 'Notifications'],
            ],

				// Front Desk / Receptionist - Guest service focus
				'receptionist' => [
					// Core Dashboard
					['href' => 'staff/dashboard', 'label' => 'Dashboard'],
					
					// Guest Services (Primary Role)
					['href' => 'staff/dashboard/bookings', 'label' => 'Reservations'],
					['href' => 'staff/dashboard/folios', 'label' => 'Folio Management'],
					['href' => 'staff/dashboard/guests', 'label' => 'Current Guests'],
					['href' => 'staff/dashboard/invoices', 'label' => 'Guest Invoices'],
					
					// Room Management
					['href' => 'staff/dashboard/rooms', 'label' => 'Room Status'],
					['href' => 'staff/dashboard/housekeeping', 'label' => 'Housekeeping Status'],
					
					// Front Desk Sales
					['href' => 'staff/dashboard/pos', 'label' => 'POS System'],
					['href' => 'staff/dashboard/pos/dashboard', 'label' => 'POS Sales'],
					
					// Service Requests
					['href' => 'staff/dashboard/maintenance', 'label' => 'Maintenance Requests'],
					
					// Tasks
					['href' => 'staff/dashboard/tasks', 'label' => 'Task Manager'],
					
					// Personal
					['href' => 'staff/dashboard/payslip', 'label' => 'My Payslip'],
					
					// Communication
					['href' => 'staff/dashboard/messages', 'label' => 'Messages'],
					['href' => 'staff/dashboard/announcements', 'label' => 'Announcements'],
					['href' => 'staff/dashboard/notifications', 'label' => 'Notifications'],
				],

            // Cashier - Payment processing focus
            'cashier' => [
				// Core Dashboard
                ['href' => 'staff/dashboard', 'label' => 'Dashboard'],
				
				// POS & Sales (Primary Role)
				['href' => 'staff/dashboard/pos', 'label' => 'POS System'],
				['href' => 'staff/dashboard/orders', 'label' => 'Order History'],
				
				// Payment Processing
				['href' => 'staff/dashboard/folios', 'label' => 'Folio Management'],
				['href' => 'staff/dashboard/payments', 'label' => 'Payments'],
				['href' => 'staff/dashboard/cash-banking', 'label' => 'Cash Banking'],
				['href' => 'staff/dashboard/petty-cash', 'label' => 'Petty Cash'],
				
				// Reports
				['href' => 'staff/dashboard/reports/daily-sales', 'label' => 'Daily Sales Report'],
				
				// Personal
				['href' => 'staff/dashboard/payslip', 'label' => 'My Payslip'],
				
				// Communication
				['href' => 'staff/dashboard/messages', 'label' => 'Messages'],
				['href' => 'staff/dashboard/announcements', 'label' => 'Announcements'],
				['href' => 'staff/dashboard/notifications', 'label' => 'Notifications'],
            ],

            // Service Agent (Waiters/Bar) - Order service focus
            'service_agent' => [
				// Core Dashboard
                ['href' => 'staff/dashboard', 'label' => 'Dashboard'],
				
				// Order Management (Primary Role)
				['href' => 'staff/dashboard/orders/my', 'label' => 'My Orders'],
				['href' => 'staff/dashboard/orders/updates', 'label' => 'Order Updates'],
				['href' => 'staff/dashboard/pos', 'label' => 'POS (Restaurant/Bar)'],
				
				// Tasks
				['href' => 'staff/dashboard/tasks', 'label' => 'Task Manager'],
				
				// Personal
				['href' => 'staff/dashboard/payslip', 'label' => 'My Payslip'],
				
				// Communication
				['href' => 'staff/dashboard/messages', 'label' => 'Messages'],
				['href' => 'staff/dashboard/announcements', 'label' => 'Announcements'],
				['href' => 'staff/dashboard/notifications', 'label' => 'Notifications'],
            ],

            // Kitchen - Food preparation focus
            'kitchen' => [
				// Core Dashboard
                ['href' => 'staff/dashboard', 'label' => 'Dashboard'],
				
				// Kitchen Orders (Primary Role)
				['href' => 'staff/dashboard/kot', 'label' => 'Kitchen Orders (KOT)'],
				['href' => 'staff/dashboard/kot/status', 'label' => 'Order Status'],
				
				// Inventory
				['href' => 'staff/dashboard/inventory/requisitions', 'label' => 'Inventory Requests'],
				
				// Tasks
				['href' => 'staff/dashboard/tasks', 'label' => 'Task Manager'],
				
				// Personal
				['href' => 'staff/dashboard/payslip', 'label' => 'My Payslip'],
				
				// Communication
				['href' => 'staff/dashboard/messages', 'label' => 'Messages'],
				['href' => 'staff/dashboard/announcements', 'label' => 'Announcements'],
				['href' => 'staff/dashboard/notifications', 'label' => 'Notifications'],
            ],

            // Housekeeping - Room cleaning focus
            'housekeeping' => [
				// Core Dashboard
                ['href' => 'staff/dashboard', 'label' => 'Dashboard'],
				
				// Housekeeping Operations (Primary Role)
				['href' => 'staff/dashboard/housekeeping', 'label' => 'Housekeeping Dashboard'],
				['href' => 'staff/dashboard/rooms', 'label' => 'Rooms Status'],
				['href' => 'staff/dashboard/tasks', 'label' => 'Task Manager'],
				
				// Maintenance & Supplies
				['href' => 'staff/dashboard/maintenance', 'label' => 'Maintenance Requests'],
				['href' => 'staff/dashboard/inventory/requisitions', 'label' => 'Inventory Requests'],
				
				// Personal
				['href' => 'staff/dashboard/payslip', 'label' => 'My Payslip'],
				
				// Communication
				['href' => 'staff/dashboard/messages', 'label' => 'Messages'],
				['href' => 'staff/dashboard/announcements', 'label' => 'Announcements'],
				['href' => 'staff/dashboard/notifications', 'label' => 'Notifications'],
            ],

            // Ground & Maintenance - Facility maintenance focus
            'ground' => [
				// Core Dashboard
                ['href' => 'staff/dashboard', 'label' => 'Dashboard'],
				
				// Maintenance Operations (Primary Role)
				['href' => 'staff/dashboard/maintenance', 'label' => 'Maintenance Requests'],
				['href' => 'staff/dashboard/tasks', 'label' => 'Task Manager'],
				
				// Related Operations
				['href' => 'staff/dashboard/housekeeping', 'label' => 'Housekeeping (Coordination)'],
				['href' => 'staff/dashboard/inventory/requisitions', 'label' => 'Inventory Requests'],
				
				// Personal
				['href' => 'staff/dashboard/payslip', 'label' => 'My Payslip'],
				
				// Communication
				['href' => 'staff/dashboard/messages', 'label' => 'Messages'],
				['href' => 'staff/dashboard/announcements', 'label' => 'Announcements'],
				['href' => 'staff/dashboard/notifications', 'label' => 'Notifications'],
            ],

            // Security - Staff attendance focus
            'security' => [
				// Core Dashboard
                ['href' => 'staff/dashboard', 'label' => 'Dashboard'],
				
				// Attendance Management (Primary Role)
				['href' => 'staff/dashboard/attendance', 'label' => 'Staff Check-in / Check-out'],
				
				// Personal
				['href' => 'staff/dashboard/payslip', 'label' => 'My Payslip'],
				
				// Communication
				['href' => 'staff/dashboard/messages', 'label' => 'Messages'],
				['href' => 'staff/dashboard/announcements', 'label' => 'Announcements'],
				['href' => 'staff/dashboard/notifications', 'label' => 'Notifications'],
            ],
        ];

        // Always use allRoles if provided (even if single role)
        // This ensures we check all assigned roles, not just the primary one
        // Users with multiple roles will see links from ALL their roles combined
        if ($allRoles && !empty($allRoles)) {
            $combinedLinks = [];
            $seenHrefs = [];
            
            // Combine links from all roles, avoiding duplicates
            // Order is preserved: links from first role appear first, then second role, etc.
            foreach ($allRoles as $userRole) {
                $roleLinks = $map[$userRole] ?? [];
                foreach ($roleLinks as $link) {
                    $href = $link['href'];
                    // Only add if we haven't seen this href before (avoid duplicates)
                    if (!isset($seenHrefs[$href])) {
                        $combinedLinks[] = $link;
                        $seenHrefs[$href] = true;
                    }
                }
            }
            
            // If no links found from any role, use primary role as fallback
            if (empty($combinedLinks)) {
                $combinedLinks = $map[$role] ?? [['href' => 'staff/dashboard', 'label' => 'Dashboard']];
            }
            
            $links = $combinedLinks;
        } else {
            // Fallback: Single role - use existing logic (for backward compatibility)
            // This handles cases where allRoles is not provided (shouldn't happen in normal flow)
            $links = $map[$role] ?? [
                ['href' => 'staff/dashboard', 'label' => 'Dashboard'],
            ];
        }

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
