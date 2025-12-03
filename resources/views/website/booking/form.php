<?php
$website = settings('website', []);
$guest = $guest ?? \App\Support\GuestPortal::user();
$onlinePaymentEnabled = !empty($website['online_payment_enabled']);
$slot = function () use ($filters, $availability, $guest, $onlinePaymentEnabled) {
    ob_start(); ?>
    <section class="page-hero page-hero-simple">
        <div class="container">
            <h1>Book Your Stay</h1>
            <p>Enter your dates and guest details to check availability.</p>
        </div>
    </section>

    <section class="container" style="margin-top: 2rem; margin-bottom: 2rem;">
        <?php if (!empty($_GET['success'])): ?>
            <div class="alert success" style="margin-bottom: 2rem; padding: 1rem 1.5rem; background: #d1fae5; color: #065f46; border-radius: 8px; border-left: 4px solid #16a34a;">
                <strong>Booking received!</strong> Reference: <strong><?= htmlspecialchars($_GET['ref'] ?? ''); ?></strong>
            </div>
        <?php endif; ?>

        <div class="card" style="margin-bottom: 2rem; padding: 2rem;">
            <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem; color: #1e293b;">Guest Information</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 1rem;" id="guestProfile">
                <label style="display: block;">
                    <span style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #475569; font-size: 0.9rem;">Full name</span>
                    <input type="text" name="guest_profile_name" value="<?= htmlspecialchars($guest['guest_name'] ?? ''); ?>" placeholder="e.g. Jane Mwangi" style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 1rem; transition: border-color 0.2s;" onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='#e2e8f0'">
                </label>
                <label style="display: block;">
                    <span style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #475569; font-size: 0.9rem;">Email</span>
                    <input type="email" name="guest_profile_email" value="<?= htmlspecialchars($guest['guest_email'] ?? ''); ?>" placeholder="you@example.com" style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 1rem; transition: border-color 0.2s;" onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='#e2e8f0'">
                </label>
                <label style="display: block;">
                    <span style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #475569; font-size: 0.9rem;">Phone</span>
                    <input type="tel" name="guest_profile_phone" value="<?= htmlspecialchars($guest['guest_phone'] ?? ''); ?>" placeholder="+254700000000" style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 1rem; transition: border-color 0.2s;" onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='#e2e8f0'">
                </label>
            </div>
            <p style="color: #64748b; font-size: 0.875rem; margin: 0;">An account will be created or updated for you automatically after checkout.</p>
        </div>

        <div class="card" style="padding: 2rem;">
            <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem; color: #1e293b;">Search Availability</h2>
            <form method="get" action="<?= base_url('booking'); ?>" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; align-items: end;">
                <label style="display: block;">
                    <span style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #475569; font-size: 0.9rem;">Check-in</span>
                    <input type="date" name="check_in" value="<?= htmlspecialchars($filters['check_in']); ?>" min="<?= date('Y-m-d'); ?>" style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 1rem; transition: border-color 0.2s;" onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='#e2e8f0'">
                </label>
                <label style="display: block;">
                    <span style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #475569; font-size: 0.9rem;">Check-out</span>
                    <input type="date" name="check_out" value="<?= htmlspecialchars($filters['check_out']); ?>" min="<?= date('Y-m-d'); ?>" style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 1rem; transition: border-color 0.2s;" onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='#e2e8f0'">
                </label>
                <label style="display: block;">
                    <span style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #475569; font-size: 0.9rem;">Adults</span>
                    <input type="number" name="adults" min="1" value="<?= htmlspecialchars($filters['adults']); ?>" style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 1rem; transition: border-color 0.2s;" onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='#e2e8f0'">
                </label>
                <label style="display: block;">
                    <span style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #475569; font-size: 0.9rem;">Children</span>
                    <input type="number" name="children" min="0" value="<?= htmlspecialchars($filters['children']); ?>" style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 1rem; transition: border-color 0.2s;" onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='#e2e8f0'">
                </label>
                <button class="btn btn-primary" type="submit" style="padding: 0.75rem 2rem; font-size: 1rem; font-weight: 500; white-space: nowrap;">Check Availability</button>
            </form>
        </div>
    </section>
    <section class="container" style="margin-top: 3rem; margin-bottom: 3rem;">
        <?php if (empty($availability)): ?>
            <div class="card" style="text-align: center; padding: 3rem 2rem;">
                <div style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;">🏨</div>
                <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 0.5rem; color: #1e293b;">No rooms available</h2>
                <p style="color: #64748b; margin-bottom: 1.5rem;">Please try different dates or check back later.</p>
                <a href="<?= base_url('booking'); ?>" class="btn btn-primary">Search Again</a>
            </div>
        <?php else: ?>
            <h2 style="font-size: 1.75rem; font-weight: 600; margin-bottom: 2rem; color: #1e293b;">Available Rooms</h2>
            <div style="display: grid; gap: 2rem;">
                <?php foreach ($availability as $option): ?>
                    <article class="card" style="padding: 2rem; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); transition: box-shadow 0.2s, transform 0.2s;" onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'; this.style.transform='translateY(-2px)'" onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,0.05)'; this.style.transform='translateY(0)'">
                        <header style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid #e2e8f0;">
                            <div>
                                <h3 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 0.5rem; color: #1e293b;"><?= htmlspecialchars($option['type']['name']); ?></h3>
                                <p style="color: #64748b; font-size: 0.95rem; margin: 0;"><?= htmlspecialchars($option['type']['description'] ?? ''); ?></p>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 1.25rem; font-weight: 700; color: var(--primary);">
                                    KES <?= number_format($option['type']['base_rate'], 2); ?>
                                </div>
                                <div style="color: #64748b; font-size: 0.875rem; margin-top: 0.25rem;">per night</div>
                            </div>
                        </header>
                        <div>
                            <?php if (!empty($option['rooms'])): ?>
                                <p style="font-weight: 500; color: #475569; margin-bottom: 1rem;">Available rooms:</p>
                                <div style="display: grid; gap: 1rem;">
                                    <?php foreach ($option['rooms'] as $room): ?>
                                        <div style="padding: 1.5rem; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                                <div>
                                                    <strong style="font-size: 1.125rem; color: #1e293b;"><?= htmlspecialchars($room['display_name'] ?? $room['room_number']); ?></strong>
                                                    <?php if ($room['floor']): ?>
                                                        <span style="display: block; color: #64748b; font-size: 0.875rem; margin-top: 0.25rem;">Floor <?= htmlspecialchars($room['floor']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <form method="post" action="<?= base_url('booking'); ?>" class="booking-form">
                                            <input type="hidden" name="check_in" value="<?= htmlspecialchars($filters['check_in']); ?>">
                                            <input type="hidden" name="check_out" value="<?= htmlspecialchars($filters['check_out']); ?>">
                                            <input type="hidden" name="adults" value="<?= htmlspecialchars($filters['adults']); ?>">
                                            <input type="hidden" name="children" value="<?= htmlspecialchars($filters['children']); ?>">
                                            <input type="hidden" name="room_type_id" value="<?= (int)$option['type']['id']; ?>">
                                            <input type="hidden" name="room_id" value="<?= (int)$room['id']; ?>">
                                            <input type="hidden" name="guest_name" value="<?= htmlspecialchars($guest['guest_name'] ?? ''); ?>">
                                            <input type="hidden" name="guest_email" value="<?= htmlspecialchars($guest['guest_email'] ?? ''); ?>">
                                            <input type="hidden" name="guest_phone" value="<?= htmlspecialchars($guest['guest_phone'] ?? ''); ?>">
                                            
                                                <?php if ($onlinePaymentEnabled): ?>
                                                <div class="payment-method-section" style="margin-bottom: 1rem; padding: 1rem; background: white; border-radius: 6px; border: 1px solid #e2e8f0;">
                                                    <label style="display: block; margin-bottom: 0.75rem; font-weight: 500; color: #1e293b; font-size: 0.9rem;">
                                                        <span>Payment Method</span>
                                                    </label>
                                                    <select name="payment_method" class="payment-method-select" style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 0.95rem; background: white;">
                                                        <option value="pay_on_arrival">Pay on Arrival</option>
                                                        <option value="mpesa">M-Pesa</option>
                                                    </select>
                                                    <div class="mpesa-phone-field" style="display: none; margin-top: 0.75rem;">
                                                        <label style="display: block; margin-bottom: 0.5rem; color: #475569; font-size: 0.875rem; font-weight: 500;">
                                                            <span>M-Pesa Phone Number</span>
                                                        </label>
                                                        <input type="tel" name="mpesa_phone" placeholder="254700000000" pattern="[0-9+]{10,15}" style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 0.95rem;">
                                                        <small style="display: block; margin-top: 0.25rem; color: #64748b; font-size: 0.8rem;">Enter phone number (e.g., 254700000000 or 0700000000)</small>
                                                    </div>
                                                </div>
                                                <?php else: ?>
                                                <input type="hidden" name="payment_method" value="pay_on_arrival">
                                                <?php endif; ?>
                                                
                                                <button class="btn btn-primary booking-submit-form" type="submit" style="width: 100%; padding: 0.875rem 1.5rem; font-weight: 500; font-size: 1rem;">Book Room <?= htmlspecialchars($room['room_number']); ?></button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div style="padding: 1.5rem; background: #fef3c7; border-radius: 8px; border: 1px solid #fde68a; margin-bottom: 1rem;">
                                    <p style="color: #92400e; margin-bottom: 1rem; font-weight: 500;">No specific rooms available right now, but you can request this room type.</p>
                                    <form method="post" action="<?= base_url('booking'); ?>" class="booking-form">
                                        <input type="hidden" name="check_in" value="<?= htmlspecialchars($filters['check_in']); ?>">
                                        <input type="hidden" name="check_out" value="<?= htmlspecialchars($filters['check_out']); ?>">
                                        <input type="hidden" name="adults" value="<?= htmlspecialchars($filters['adults']); ?>">
                                        <input type="hidden" name="children" value="<?= htmlspecialchars($filters['children']); ?>">
                                        <input type="hidden" name="room_type_id" value="<?= (int)$option['type']['id']; ?>">
                                        <input type="hidden" name="room_id" value="">
                                        <input type="hidden" name="guest_name" value="<?= htmlspecialchars($guest['guest_name'] ?? ''); ?>">
                                        <input type="hidden" name="guest_email" value="<?= htmlspecialchars($guest['guest_email'] ?? ''); ?>">
                                        <input type="hidden" name="guest_phone" value="<?= htmlspecialchars($guest['guest_phone'] ?? ''); ?>">
                                        
                                        <?php if ($onlinePaymentEnabled): ?>
                                        <div class="payment-method-section" style="margin-bottom: 1rem; padding: 1rem; background: white; border-radius: 6px; border: 1px solid #e2e8f0;">
                                            <label style="display: block; margin-bottom: 0.75rem; font-weight: 500; color: #1e293b; font-size: 0.9rem;">
                                                <span>Payment Method</span>
                                            </label>
                                            <select name="payment_method" class="payment-method-select" style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 0.95rem; background: white;">
                                                <option value="pay_on_arrival">Pay on Arrival</option>
                                                <option value="mpesa">M-Pesa</option>
                                            </select>
                                            <div class="mpesa-phone-field" style="display: none; margin-top: 0.75rem;">
                                                <label style="display: block; margin-bottom: 0.5rem; color: #475569; font-size: 0.875rem; font-weight: 500;">
                                                    <span>M-Pesa Phone Number</span>
                                                </label>
                                                <input type="tel" name="mpesa_phone" placeholder="254700000000" pattern="[0-9+]{10,15}" style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 0.95rem;">
                                                <small style="display: block; margin-top: 0.25rem; color: #64748b; font-size: 0.8rem;">Enter phone number (e.g., 254700000000 or 0700000000)</small>
                                            </div>
                                        </div>
                                        <?php else: ?>
                                        <input type="hidden" name="payment_method" value="pay_on_arrival">
                                        <?php endif; ?>
                                        
                                        <button class="btn btn-outline booking-submit-form" type="submit" style="width: 100%; padding: 0.875rem 1.5rem; font-weight: 500; font-size: 1rem;">Request this room type</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const profile = document.getElementById('guestProfile');
            if (!profile) return;

            const nameInput = profile.querySelector('[name="guest_profile_name"]');
            const emailInput = profile.querySelector('[name="guest_profile_email"]');
            const phoneInput = profile.querySelector('[name="guest_profile_phone"]');

            const syncForm = (form) => {
                form.addEventListener('submit', event => {
                    const name = (nameInput.value || '').trim();
                    const email = (emailInput.value || '').trim();
                    const phone = (phoneInput.value || '').trim();

                    if (!name || (!email && !phone)) {
                        event.preventDefault();
                        alert('Please enter your name and at least one contact method before booking.');
                        return;
                    }

                    form.querySelector('input[name="guest_name"]').value = name;
                    form.querySelector('input[name="guest_email"]').value = email;
                    form.querySelector('input[name="guest_phone"]').value = phone;
                });
            };

            document.querySelectorAll('.booking-results form').forEach(syncForm);

            // Show/hide M-Pesa phone field based on payment method selection
            document.querySelectorAll('.payment-method-select').forEach(select => {
                select.addEventListener('change', function() {
                    const form = this.closest('.booking-form');
                    const mpesaField = form.querySelector('.mpesa-phone-field');
                    const mpesaInput = form.querySelector('input[name="mpesa_phone"]');
                    
                    if (this.value === 'mpesa') {
                        mpesaField.style.display = 'block';
                        if (mpesaInput) {
                            mpesaInput.required = true;
                            // Pre-fill with guest phone if available
                            if (!mpesaInput.value && phoneInput && phoneInput.value) {
                                mpesaInput.value = phoneInput.value.replace(/[^0-9+]/g, '');
                            }
                        }
                    } else {
                        mpesaField.style.display = 'none';
                        if (mpesaInput) {
                            mpesaInput.required = false;
                        }
                    }
                });
            });
        });
    </script>
    <?php
    return ob_get_clean();
};

$pageTitle = 'Book Your Stay | ' . settings('branding.name', 'Hotela');
include view_path('layouts/public.php');
