<?php

namespace App\Modules\Platform\Controllers;

use App\Core\Controller;
use App\Core\Request;

class ContactController extends Controller
{
    public function index(Request $request): void
    {
        $pageTitle = 'Contact Developer | Hotela';
        include base_path('resources/includes/header.php');
        ?>
        <section class="contact-section" style="padding: 80px 20px; background: #0d9488; color: white; min-height: 80vh;">
            <div class="container" style="max-width: 800px; margin: 0 auto; text-align: center;">
                <h1 style="margin-bottom: 30px; font-size: 3em;">Contact Developer</h1>
                <p style="font-size: 1.3em; margin-bottom: 40px; opacity: 0.95;">Need support, customization, or have questions about Hotela?</p>
                <div style="background: rgba(255,255,255,0.15); padding: 40px; border-radius: 12px; margin-bottom: 40px; backdrop-filter: blur(10px);">
                    <div style="margin-bottom: 25px;">
                        <h3 style="font-size: 1.5em; margin-bottom: 10px;">Email Support</h3>
                        <p style="font-size: 1.2em;"><a href="mailto:support@hotela.com" style="color: white; text-decoration: underline;">support@hotela.com</a></p>
                    </div>
                    <div style="margin-bottom: 25px;">
                        <h3 style="font-size: 1.5em; margin-bottom: 10px;">Phone</h3>
                        <p style="font-size: 1.2em;">+254 XXX XXX XXX</p>
                    </div>
                    <div>
                        <h3 style="font-size: 1.5em; margin-bottom: 10px;">Business Hours</h3>
                        <p style="font-size: 1.2em;">Monday - Friday, 9 AM - 5 PM EAT</p>
                    </div>
                </div>
                <p style="font-size: 1.1em; opacity: 0.9; margin-bottom: 30px;">For technical support, feature requests, licensing inquiries, or custom development, please contact our development team.</p>
                <div>
                    <a href="<?= base_url('login'); ?>" style="display: inline-block; padding: 15px 40px; background: white; color: #0d9488; text-decoration: none; border-radius: 5px; font-weight: 600; margin-right: 15px;">Staff Login</a>
                    <a href="<?= base_url('features'); ?>" style="display: inline-block; padding: 15px 40px; background: rgba(255,255,255,0.2); color: white; text-decoration: none; border-radius: 5px; font-weight: 600; border: 2px solid white;">View Features</a>
                </div>
            </div>
        </section>
        <?php
        include base_path('resources/includes/footer.php');
    }
}

