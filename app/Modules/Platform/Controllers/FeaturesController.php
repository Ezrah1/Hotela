<?php

namespace App\Modules\Platform\Controllers;

use App\Core\Controller;
use App\Core\Request;

class FeaturesController extends Controller
{
    public function index(Request $request): void
    {
        $pageTitle = 'Features | Hotela';
        include base_path('resources/includes/header.php');
        
        $currentFeatures = [
            [
                'title' => 'Property Management System (PMS)',
                'description' => 'Complete PMS with room management, reservations, check-in/out, folio tracking, and room status management. Manage your entire property from one centralized system.',
                'features' => ['Room & Room Type Management', 'Reservation Management', 'Check-in/Check-out', 'Folio & Billing', 'Room Status Tracking', 'Calendar View', 'Guest History']
            ],
            [
                'title' => 'Point of Sale (POS)',
                'description' => 'Integrated POS system for restaurant, bar, and retail operations with real-time inventory tracking. Process sales quickly and efficiently.',
                'features' => ['Restaurant & Bar Sales', 'Retail Operations', 'Inventory Integration', 'Sales Reports', 'Receipt Generation']
            ],
            [
                'title' => 'Inventory Management',
                'description' => 'Real-time inventory tracking, automated requisitions, purchase orders, and supplier management. Never run out of stock again.',
                'features' => ['Real-time Stock Levels', 'Automated Requisitions', 'Purchase Orders', 'Supplier Management', 'Stock Movements', 'Low Stock Alerts']
            ],
            [
                'title' => 'Financial Management',
                'description' => 'Complete financial control with expenses, bills, payments, petty cash, payroll, and comprehensive financial reports.',
                'features' => ['Expense Management', 'Bill Tracking', 'Payment Processing', 'Petty Cash', 'Payroll', 'Financial Reports', 'Cash Banking']
            ],
            [
                'title' => 'Staff Management & Attendance',
                'description' => 'Comprehensive staff management with role-based access control, attendance tracking, duty rosters, and HR records.',
                'features' => ['Role-based Access Control', 'Attendance Tracking', 'Security Desk Check-in/out', 'Duty Roster Management', 'HR Records', 'Task Management', 'Staff Profiles']
            ],
            [
                'title' => 'Guest Portal',
                'description' => 'Customizable guest website with room listings, online ordering, booking capabilities, and guest account management.',
                'features' => ['Online Booking', 'Room Listings', 'Food & Beverage Ordering', 'Guest Dashboard', 'Booking History', 'Receipt Downloads', 'Guest Reviews']
            ],
            [
                'title' => 'Workflow & Task Management',
                'description' => 'Automated task assignment and workflow management integrated with housekeeping, maintenance, and inventory operations.',
                'features' => ['Automated Task Assignment', 'Workflow Routing', 'Approval Workflows', 'Task Tracking', 'Department Integration', 'Real-time Notifications']
            ],
            [
                'title' => 'Payment Gateway Integration',
                'description' => 'Seamless payment processing with M-Pesa integration, real-time payment status updates, and multiple payment methods.',
                'features' => ['M-Pesa Integration', 'Real-time Payment Updates', 'Multiple Payment Methods', 'Payment History', 'Transaction Tracking']
            ],
            [
                'title' => 'Housekeeping Management',
                'description' => 'Complete housekeeping operations with room status management, task assignment, maintenance requests, and DND handling.',
                'features' => ['Room Status Management', 'Cleaning Tasks', 'Maintenance Requests', 'DND Status', 'Task Assignment', 'Room Inspections']
            ],
            [
                'title' => 'Order Management',
                'description' => 'Streamlined order processing for food, beverages, and services with real-time updates and status tracking.',
                'features' => ['Order Processing', 'Status Tracking', 'Staff Assignment', 'Order History', 'Real-time Updates']
            ],
            [
                'title' => 'Maintenance Management',
                'description' => 'Complete maintenance workflow from request to completion with operations and finance approval processes.',
                'features' => ['Maintenance Requests', 'Workflow Approvals', 'Supplier Assignment', 'Work Verification', 'Status Tracking']
            ],
            [
                'title' => 'Reports & Analytics',
                'description' => 'Comprehensive reporting across sales, finance, operations, and guest analytics for data-driven decisions.',
                'features' => ['Sales Reports', 'Financial Reports', 'Operations Reports', 'Guest Analytics', 'Custom Date Ranges']
            ]
        ];
        
        $comingSoonFeatures = [
            [
                'title' => 'Mobile App',
                'description' => 'Native mobile applications for iOS and Android to manage your hotel on the go.',
                'status' => 'In Development'
            ],
            [
                'title' => 'Channel Manager Integration',
                'description' => 'Connect with major booking platforms like Booking.com, Expedia, and Airbnb for seamless inventory synchronization.',
                'status' => 'Coming Soon'
            ],
            [
                'title' => 'Revenue Management',
                'description' => 'Advanced pricing strategies, dynamic rate management, and revenue optimization tools.',
                'status' => 'Coming Soon'
            ],
            [
                'title' => 'Guest Loyalty Program',
                'description' => 'Build customer loyalty with points, rewards, and membership tiers.',
                'status' => 'Coming Soon'
            ],
            [
                'title' => 'SMS Notifications',
                'description' => 'Send automated SMS notifications for bookings, check-ins, and important updates.',
                'status' => 'Coming Soon'
            ],
            [
                'title' => 'Advanced Analytics Dashboard',
                'description' => 'AI-powered insights, predictive analytics, and business intelligence dashboards.',
                'status' => 'Coming Soon'
            ],
            [
                'title' => 'Multi-property Management',
                'description' => 'Manage multiple properties from a single dashboard with centralized reporting.',
                'status' => 'Coming Soon'
            ],
            [
                'title' => 'API & Integrations',
                'description' => 'RESTful API for third-party integrations and custom development.',
                'status' => 'Coming Soon'
            ]
        ];
        ?>
        <section class="features-section" style="padding: 80px 20px; background: #f8f9fa; min-height: 80vh;">
            <div class="container" style="max-width: 1200px; margin: 0 auto;">
                <h1 style="text-align: center; margin-bottom: 20px; font-size: 3em; color: #0d9488;">Platform Features</h1>
                <p style="text-align: center; margin-bottom: 60px; font-size: 1.2em; color: #666;">Comprehensive hospitality management solution for modern hotels</p>
                
                <!-- Current Features -->
                <div style="margin-bottom: 80px;">
                    <h2 style="text-align: center; margin-bottom: 40px; font-size: 2.2em; color: #111827; border-bottom: 3px solid #0d9488; padding-bottom: 15px; display: inline-block; width: 100%;">
                        Current Features
                    </h2>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 30px;">
                        <?php foreach ($currentFeatures as $feature): ?>
                            <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-left: 4px solid #10b981; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 20px rgba(0,0,0,0.15)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 10px rgba(0,0,0,0.1)'">
                                <div style="display: flex; align-items: start; margin-bottom: 15px;">
                                    <span style="background: #10b981; color: white; padding: 5px 10px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; margin-right: 10px;">ACTIVE</span>
                                    <h3 style="color: #0d9488; margin: 0; font-size: 1.5em; flex: 1;"><?= htmlspecialchars($feature['title']); ?></h3>
                                </div>
                                <p style="line-height: 1.6; color: #666; margin-bottom: 15px;"><?= htmlspecialchars($feature['description']); ?></p>
                                <?php if (!empty($feature['features'])): ?>
                                    <ul style="margin: 0; padding-left: 20px; color: #555; font-size: 0.9em;">
                                        <?php foreach ($feature['features'] as $item): ?>
                                            <li style="margin-bottom: 8px;"><?= htmlspecialchars($item); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Coming Soon Features -->
                <div style="margin-bottom: 60px;">
                    <h2 style="text-align: center; margin-bottom: 40px; font-size: 2.2em; color: #111827; border-bottom: 3px solid #f59e0b; padding-bottom: 15px; display: inline-block; width: 100%;">
                        Coming Soon
                    </h2>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
                        <?php foreach ($comingSoonFeatures as $feature): ?>
                            <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-left: 4px solid #f59e0b; opacity: 0.9;">
                                <div style="display: flex; align-items: start; margin-bottom: 15px;">
                                    <span style="background: #f59e0b; color: white; padding: 5px 10px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; margin-right: 10px;"><?= htmlspecialchars($feature['status']); ?></span>
                                    <h3 style="color: #0d9488; margin: 0; font-size: 1.5em; flex: 1;"><?= htmlspecialchars($feature['title']); ?></h3>
                                </div>
                                <p style="line-height: 1.6; color: #666;"><?= htmlspecialchars($feature['description']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 50px; padding-top: 40px; border-top: 2px solid #e5e7eb;">
                    <a href="<?= base_url('staff/login'); ?>" style="display: inline-block; padding: 15px 40px; background: #0d9488; color: white; text-decoration: none; border-radius: 5px; font-weight: 600; font-size: 1.1em; margin-right: 15px;">Staff Login</a>
                    <a href="<?= base_url('contact'); ?>" style="display: inline-block; padding: 15px 40px; background: transparent; color: #0d9488; text-decoration: none; border-radius: 5px; font-weight: 600; font-size: 1.1em; border: 2px solid #0d9488;">Contact Us</a>
                </div>
            </div>
        </section>
        <?php
        include base_path('resources/includes/footer.php');
    }
}

