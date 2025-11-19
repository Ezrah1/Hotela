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
        ?>
        <section class="features-section" style="padding: 80px 20px; background: #f8f9fa; min-height: 80vh;">
            <div class="container" style="max-width: 1200px; margin: 0 auto;">
                <h1 style="text-align: center; margin-bottom: 50px; font-size: 3em; color: #0d9488;">Platform Features</h1>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
                    <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <h3 style="color: #0d9488; margin-bottom: 15px; font-size: 1.5em;">Property Management</h3>
                        <p style="line-height: 1.6; color: #666;">Complete PMS with room management, reservations, check-in/out, and folio tracking. Manage your entire property from one centralized system.</p>
                    </div>
                    <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <h3 style="color: #0d9488; margin-bottom: 15px; font-size: 1.5em;">Point of Sale</h3>
                        <p style="line-height: 1.6; color: #666;">Integrated POS system for restaurant, bar, and retail operations with inventory tracking. Process sales quickly and efficiently.</p>
                    </div>
                    <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <h3 style="color: #0d9488; margin-bottom: 15px; font-size: 1.5em;">Inventory Management</h3>
                        <p style="line-height: 1.6; color: #666;">Real-time inventory tracking, requisitions, purchase orders, and supplier management. Never run out of stock again.</p>
                    </div>
                    <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <h3 style="color: #0d9488; margin-bottom: 15px; font-size: 1.5em;">Financial Management</h3>
                        <p style="line-height: 1.6; color: #666;">Expenses, bills, payments, petty cash, payroll, and comprehensive financial reports. Complete financial control.</p>
                    </div>
                    <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <h3 style="color: #0d9488; margin-bottom: 15px; font-size: 1.5em;">Staff Management</h3>
                        <p style="line-height: 1.6; color: #666;">Role-based access control, task management, HR records, and attendance tracking. Manage your team effectively.</p>
                    </div>
                    <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <h3 style="color: #0d9488; margin-bottom: 15px; font-size: 1.5em;">Guest Portal</h3>
                        <p style="line-height: 1.6; color: #666;">Customizable guest website with room listings, online ordering, and booking capabilities. Enhance guest experience.</p>
                    </div>
                </div>
                <div style="text-align: center; margin-top: 50px;">
                    <a href="<?= base_url('login'); ?>" style="display: inline-block; padding: 15px 40px; background: #0d9488; color: white; text-decoration: none; border-radius: 5px; font-weight: 600;">Staff Login</a>
                </div>
            </div>
        </section>
        <?php
        include base_path('resources/includes/footer.php');
    }
}

