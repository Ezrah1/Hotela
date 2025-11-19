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
        ?>
        <section class="modules-section" style="padding: 80px 20px; background: white; min-height: 80vh;">
            <div class="container" style="max-width: 1200px; margin: 0 auto;">
                <h1 style="text-align: center; margin-bottom: 50px; font-size: 3em; color: #0d9488;">Available Modules</h1>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px;">
                    <div style="padding: 25px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #0d9488;">
                        <h3 style="color: #0d9488; margin-bottom: 10px; font-size: 1.3em;"><strong>PMS</strong></h3>
                        <p style="color: #666; margin: 0;">Property Management System - Complete hotel operations management</p>
                    </div>
                    <div style="padding: 25px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #0d9488;">
                        <h3 style="color: #0d9488; margin-bottom: 10px; font-size: 1.3em;"><strong>POS</strong></h3>
                        <p style="color: #666; margin: 0;">Point of Sale - Restaurant, bar, and retail sales processing</p>
                    </div>
                    <div style="padding: 25px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #0d9488;">
                        <h3 style="color: #0d9488; margin-bottom: 10px; font-size: 1.3em;"><strong>Inventory</strong></h3>
                        <p style="color: #666; margin: 0;">Stock Management - Track and manage all inventory items</p>
                    </div>
                    <div style="padding: 25px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #0d9488;">
                        <h3 style="color: #0d9488; margin-bottom: 10px; font-size: 1.3em;"><strong>Finance</strong></h3>
                        <p style="color: #666; margin: 0;">Accounting & Reports - Financial management and reporting</p>
                    </div>
                    <div style="padding: 25px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #0d9488;">
                        <h3 style="color: #0d9488; margin-bottom: 10px; font-size: 1.3em;"><strong>HR & Payroll</strong></h3>
                        <p style="color: #666; margin: 0;">Human Resources - Staff management and payroll processing</p>
                    </div>
                    <div style="padding: 25px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #0d9488;">
                        <h3 style="color: #0d9488; margin-bottom: 10px; font-size: 1.3em;"><strong>Tasks</strong></h3>
                        <p style="color: #666; margin: 0;">Task Management - Assign and track staff tasks</p>
                    </div>
                    <div style="padding: 25px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #0d9488;">
                        <h3 style="color: #0d9488; margin-bottom: 10px; font-size: 1.3em;"><strong>Messages</strong></h3>
                        <p style="color: #666; margin: 0;">Internal Communication - Staff messaging and announcements</p>
                    </div>
                    <div style="padding: 25px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #0d9488;">
                        <h3 style="color: #0d9488; margin-bottom: 10px; font-size: 1.3em;"><strong>Website</strong></h3>
                        <p style="color: #666; margin: 0;">Guest Portal Builder - Customizable public website</p>
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

