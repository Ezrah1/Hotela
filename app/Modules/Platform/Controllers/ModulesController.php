<?php

namespace App\Modules\Platform\Controllers;

use App\Core\Controller;
use App\Core\Request;

class ModulesController extends Controller
{
    public function index(Request $request): void
    {
        $pageTitle = 'Modules | Hotela';
        include base_path('resources/includes/header.php');
        
        $currentModules = [
            [
                'name' => 'PMS',
                'fullName' => 'Property Management System',
                'description' => 'Complete hotel operations management with reservations, check-in/out, room management, and folio tracking.',
                'icon' => '🏨',
                'category' => 'Operations'
            ],
            [
                'name' => 'POS',
                'fullName' => 'Point of Sale',
                'description' => 'Restaurant, bar, and retail sales processing with integrated inventory and receipt generation.',
                'icon' => '💳',
                'category' => 'Sales'
            ],
            [
                'name' => 'Inventory',
                'fullName' => 'Inventory Management',
                'description' => 'Real-time stock tracking, automated requisitions, purchase orders, and supplier management.',
                'icon' => '📦',
                'category' => 'Operations'
            ],
            [
                'name' => 'Housekeeping',
                'fullName' => 'Housekeeping Management',
                'description' => 'Room status management, cleaning tasks, maintenance requests, and DND handling.',
                'icon' => '🧹',
                'category' => 'Operations'
            ],
            [
                'name' => 'Orders',
                'fullName' => 'Order Management',
                'description' => 'Streamlined order processing for food, beverages, and services with real-time status tracking.',
                'icon' => '📋',
                'category' => 'Operations'
            ],
            [
                'name' => 'Maintenance',
                'fullName' => 'Maintenance Management',
                'description' => 'Complete maintenance workflow from request to completion with approval processes.',
                'icon' => '🔧',
                'category' => 'Operations'
            ],
            [
                'name' => 'Payments',
                'fullName' => 'Payment Processing',
                'description' => 'Payment gateway integration with M-Pesa, real-time payment updates, and transaction tracking.',
                'icon' => '💵',
                'category' => 'Finance'
            ],
            [
                'name' => 'Cash Banking',
                'fullName' => 'Cash & Banking',
                'description' => 'Cash management, banking operations, and financial transaction tracking.',
                'icon' => '🏦',
                'category' => 'Finance'
            ],
            [
                'name' => 'Petty Cash',
                'fullName' => 'Petty Cash Management',
                'description' => 'Track and manage petty cash transactions and reimbursements.',
                'icon' => '💰',
                'category' => 'Finance'
            ],
            [
                'name' => 'Bills',
                'fullName' => 'Bill Management',
                'description' => 'Track and manage bills, invoices, and vendor payments.',
                'icon' => '📄',
                'category' => 'Finance'
            ],
            [
                'name' => 'Expenses',
                'fullName' => 'Expense Management',
                'description' => 'Track and categorize business expenses with approval workflows.',
                'icon' => '📊',
                'category' => 'Finance'
            ],
            [
                'name' => 'Payroll',
                'fullName' => 'Payroll Management',
                'description' => 'Staff payroll processing, payslip generation, and salary management.',
                'icon' => '💼',
                'category' => 'HR'
            ],
            [
                'name' => 'HR',
                'fullName' => 'Human Resources',
                'description' => 'Staff records, profiles, and HR management.',
                'icon' => '👥',
                'category' => 'HR'
            ],
            [
                'name' => 'Attendance',
                'fullName' => 'Attendance System',
                'description' => 'Staff check-in/out tracking, duty rosters, and attendance management with security desk integration.',
                'icon' => '⏰',
                'category' => 'HR'
            ],
            [
                'name' => 'Tasks',
                'fullName' => 'Task Management',
                'description' => 'Assign, track, and manage staff tasks with workflow automation.',
                'icon' => '✅',
                'category' => 'Operations'
            ],
            [
                'name' => 'Reports',
                'fullName' => 'Reports & Analytics',
                'description' => 'Comprehensive reporting across sales, finance, operations, and guest analytics.',
                'icon' => '📈',
                'category' => 'Analytics'
            ],
            [
                'name' => 'Website',
                'fullName' => 'Guest Portal',
                'description' => 'Customizable public website with online booking, ordering, and guest account management.',
                'icon' => '🌐',
                'category' => 'Guest Services'
            ],
            [
                'name' => 'Messages',
                'fullName' => 'Internal Messaging',
                'description' => 'Staff messaging, announcements, and internal communication.',
                'icon' => '💬',
                'category' => 'Communication'
            ],
            [
                'name' => 'Notifications',
                'fullName' => 'Notification System',
                'description' => 'Real-time notifications and alerts for staff and management.',
                'icon' => '🔔',
                'category' => 'Communication'
            ],
            [
                'name' => 'Announcements',
                'fullName' => 'Announcements',
                'description' => 'Company-wide announcements and important updates.',
                'icon' => '📢',
                'category' => 'Communication'
            ],
            [
                'name' => 'Roles',
                'fullName' => 'Role Management',
                'description' => 'Role-based access control and permission management.',
                'icon' => '🔐',
                'category' => 'Admin'
            ],
            [
                'name' => 'Staff',
                'fullName' => 'Staff Management',
                'description' => 'Staff profiles, roles, and access management.',
                'icon' => '👤',
                'category' => 'HR'
            ],
            [
                'name' => 'Suppliers',
                'fullName' => 'Supplier Management',
                'description' => 'Manage suppliers, vendor information, and procurement relationships.',
                'icon' => '🚚',
                'category' => 'Operations'
            ],
            [
                'name' => 'Dashboard',
                'fullName' => 'Dashboard',
                'description' => 'Role-based dashboards with key metrics and quick access to modules.',
                'icon' => '📊',
                'category' => 'Admin'
            ],
            [
                'name' => 'Settings',
                'fullName' => 'System Settings',
                'description' => 'Configure system settings, branding, and preferences.',
                'icon' => '⚙️',
                'category' => 'Admin'
            ]
        ];
        
        $comingSoonModules = [
            [
                'name' => 'Channel Manager',
                'description' => 'Connect with major booking platforms like Booking.com, Expedia, and Airbnb for seamless inventory synchronization.',
                'icon' => '🔗',
                'status' => 'Coming Soon'
            ],
            [
                'name' => 'Revenue Management',
                'description' => 'Advanced pricing strategies, dynamic rate management, and revenue optimization tools.',
                'icon' => '📉',
                'status' => 'Coming Soon'
            ],
            [
                'name' => 'Loyalty Program',
                'description' => 'Build customer loyalty with points, rewards, and membership tiers.',
                'icon' => '⭐',
                'status' => 'Coming Soon'
            ],
            [
                'name' => 'SMS Gateway',
                'description' => 'Send automated SMS notifications for bookings, check-ins, and important updates.',
                'icon' => '📱',
                'status' => 'Coming Soon'
            ],
            [
                'name' => 'Mobile App',
                'description' => 'Native mobile applications for iOS and Android to manage your hotel on the go.',
                'icon' => '📲',
                'status' => 'In Development'
            ],
            [
                'name' => 'API & Webhooks',
                'description' => 'RESTful API for third-party integrations and custom development.',
                'icon' => '🔌',
                'status' => 'Coming Soon'
            ],
            [
                'name' => 'Advanced Analytics',
                'description' => 'AI-powered insights, predictive analytics, and business intelligence dashboards.',
                'icon' => '🤖',
                'status' => 'Coming Soon'
            ],
            [
                'name' => 'Multi-property',
                'description' => 'Manage multiple properties from a single dashboard with centralized reporting.',
                'icon' => '🏢',
                'status' => 'Coming Soon'
            ]
        ];
        
        // Group modules by category
        $modulesByCategory = [];
        foreach ($currentModules as $module) {
            $category = $module['category'];
            if (!isset($modulesByCategory[$category])) {
                $modulesByCategory[$category] = [];
            }
            $modulesByCategory[$category][] = $module;
        }
        ?>
        <section class="modules-section" style="padding: 80px 20px; background: #f8f9fa; min-height: 80vh;">
            <div class="container" style="max-width: 1200px; margin: 0 auto;">
                <h1 style="text-align: center; margin-bottom: 20px; font-size: 3em; color: #0d9488;">Available Modules</h1>
                <p style="text-align: center; margin-bottom: 60px; font-size: 1.2em; color: #666;">Comprehensive modular system for complete hotel management</p>
                
                <!-- Current Modules by Category -->
                <div style="margin-bottom: 80px;">
                    <h2 style="text-align: center; margin-bottom: 40px; font-size: 2.2em; color: #111827; border-bottom: 3px solid #10b981; padding-bottom: 15px; display: inline-block; width: 100%;">
                        Current Modules
                    </h2>
                    
                    <?php foreach ($modulesByCategory as $category => $modules): ?>
                        <div style="margin-bottom: 50px;">
                            <h3 style="font-size: 1.8em; color: #0d9488; margin-bottom: 25px; padding-left: 10px; border-left: 4px solid #0d9488;">
                                <?= htmlspecialchars($category); ?>
                            </h3>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px;">
                                <?php foreach ($modules as $module): ?>
                                    <div style="padding: 25px; background: white; border-radius: 8px; border-left: 4px solid #10b981; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 20px rgba(0,0,0,0.15)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 10px rgba(0,0,0,0.1)'">
                                        <div style="display: flex; align-items: center; margin-bottom: 15px;">
                                            <span style="font-size: 2em; margin-right: 15px;"><?= htmlspecialchars($module['icon']); ?></span>
                                            <div>
                                                <h4 style="color: #0d9488; margin: 0 0 5px 0; font-size: 1.3em; font-weight: 600;"><?= htmlspecialchars($module['name']); ?></h4>
                                                <p style="color: #999; margin: 0; font-size: 0.9em;"><?= htmlspecialchars($module['fullName']); ?></p>
                                            </div>
                    </div>
                                        <p style="color: #666; margin: 0; line-height: 1.6; font-size: 0.95em;"><?= htmlspecialchars($module['description']); ?></p>
                                        <span style="display: inline-block; margin-top: 10px; background: #10b981; color: white; padding: 4px 10px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">ACTIVE</span>
                    </div>
                                <?php endforeach; ?>
                    </div>
                    </div>
                    <?php endforeach; ?>
                    </div>
                
                <!-- Coming Soon Modules -->
                <div style="margin-bottom: 60px;">
                    <h2 style="text-align: center; margin-bottom: 40px; font-size: 2.2em; color: #111827; border-bottom: 3px solid #f59e0b; padding-bottom: 15px; display: inline-block; width: 100%;">
                        Coming Soon
                    </h2>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px;">
                        <?php foreach ($comingSoonModules as $module): ?>
                            <div style="padding: 25px; background: white; border-radius: 8px; border-left: 4px solid #f59e0b; box-shadow: 0 2px 10px rgba(0,0,0,0.1); opacity: 0.9;">
                                <div style="display: flex; align-items: center; margin-bottom: 15px;">
                                    <span style="font-size: 2em; margin-right: 15px;"><?= htmlspecialchars($module['icon']); ?></span>
                                    <h4 style="color: #0d9488; margin: 0; font-size: 1.3em; font-weight: 600;"><?= htmlspecialchars($module['name']); ?></h4>
                    </div>
                                <p style="color: #666; margin: 0 0 10px 0; line-height: 1.6; font-size: 0.95em;"><?= htmlspecialchars($module['description']); ?></p>
                                <span style="display: inline-block; background: #f59e0b; color: white; padding: 4px 10px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;"><?= htmlspecialchars($module['status']); ?></span>
                    </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 50px; padding-top: 40px; border-top: 2px solid #e5e7eb;">
                    <a href="<?= base_url('staff/login'); ?>" style="display: inline-block; padding: 15px 40px; background: #0d9488; color: white; text-decoration: none; border-radius: 5px; font-weight: 600; font-size: 1.1em; margin-right: 15px;">Staff Login</a>
                    <a href="<?= base_url('features'); ?>" style="display: inline-block; padding: 15px 40px; background: transparent; color: #0d9488; text-decoration: none; border-radius: 5px; font-weight: 600; font-size: 1.1em; border: 2px solid #0d9488; margin-right: 15px;">View Features</a>
                    <a href="<?= base_url('contact'); ?>" style="display: inline-block; padding: 15px 40px; background: transparent; color: #0d9488; text-decoration: none; border-radius: 5px; font-weight: 600; font-size: 1.1em; border: 2px solid #0d9488;">Contact Us</a>
                </div>
            </div>
        </section>
        <?php
        include base_path('resources/includes/footer.php');
    }
}

