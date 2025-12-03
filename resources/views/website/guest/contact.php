<?php
ob_start();
$contactPhone = settings('branding.contact_phone', '');
$contactEmail = settings('branding.contact_email', '');
$website = settings('website', []);
?>
<div>
    <div style="margin-bottom: 1.5rem;">
        <a href="<?= base_url('guest/portal'); ?>" class="guest-btn guest-btn-outline" style="display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
            ← Back to Dashboard
        </a>
        <h1 class="guest-page-title">Contact Us</h1>
        <p class="guest-page-subtitle">We're here to help with any questions or concerns</p>
    </div>

    <?php if (!empty($_GET['error'])): ?>
        <div class="guest-card" style="padding: 1rem; background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; margin-bottom: 1.5rem;">
            <?php
            $errors = [
                'invalid_method' => 'Invalid request method.',
                'missing_fields' => 'Please fill in all required fields.',
            ];
            echo htmlspecialchars($errors[$_GET['error']] ?? 'An error occurred.');
            ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET['success'])): ?>
        <div class="guest-card" style="padding: 1rem; background: #dcfce7; border: 1px solid #bbf7d0; color: #166534; margin-bottom: 1.5rem;">
            Thank you! Your message has been sent. We'll get back to you as soon as possible.
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
        <!-- Contact Information -->
        <div class="guest-card">
            <h2 class="guest-card-title">Get in Touch</h2>
            <div style="display: grid; gap: 1.5rem;">
                <?php if ($contactPhone): ?>
                    <div>
                        <h3 style="font-size: 0.875rem; font-weight: 600; color: var(--guest-text-light); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Phone</h3>
                        <p style="font-size: 1.125rem;">
                            <a href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/', '', $contactPhone)); ?>" style="color: var(--guest-primary); text-decoration: none;">
                                <?= htmlspecialchars($contactPhone); ?>
                            </a>
                        </p>
                    </div>
                <?php endif; ?>

                <?php if ($contactEmail): ?>
                    <div>
                        <h3 style="font-size: 0.875rem; font-weight: 600; color: var(--guest-text-light); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Email</h3>
                        <p style="font-size: 1.125rem;">
                            <a href="mailto:<?= htmlspecialchars($contactEmail); ?>" style="color: var(--guest-primary); text-decoration: none;">
                                <?= htmlspecialchars($contactEmail); ?>
                            </a>
                        </p>
                    </div>
                <?php endif; ?>

                <?php if ($website['contact_address'] ?? ''): ?>
                    <div>
                        <h3 style="font-size: 0.875rem; font-weight: 600; color: var(--guest-text-light); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Address</h3>
                        <p style="font-size: 1rem; color: var(--guest-text);">
                            <?= nl2br(htmlspecialchars($website['contact_address'])); ?>
                        </p>
                    </div>
                <?php endif; ?>

                <?php if ($website['contact_whatsapp'] ?? ''): ?>
                    <div>
                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $website['contact_whatsapp']); ?>" target="_blank" rel="noopener" class="guest-btn" style="display: inline-block;">
                            Chat on WhatsApp
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="guest-card">
            <h2 class="guest-card-title">Send a Message</h2>
            <form method="post" action="<?= base_url('guest/contact'); ?>" style="display: grid; gap: 1.5rem;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--guest-text);">
                        Subject <span style="color: var(--guest-danger);">*</span>
                    </label>
                    <select name="subject" required class="modern-input" style="width: 100%; padding: 0.75rem; border: 1px solid var(--guest-border); border-radius: 0.5rem; font-size: 1rem;">
                        <option value="">Select a subject...</option>
                        <option value="booking_inquiry">Booking Inquiry</option>
                        <option value="modify_booking">Modify Booking</option>
                        <option value="cancel_booking">Cancel Booking</option>
                        <option value="room_service">Room Service</option>
                        <option value="complaint">Complaint</option>
                        <option value="compliment">Compliment</option>
                        <option value="general">General Question</option>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--guest-text);">
                        Booking Reference (Optional)
                    </label>
                    <input type="text" name="booking_reference" placeholder="e.g. HTL-1A2B3C" class="modern-input" style="width: 100%; padding: 0.75rem; border: 1px solid var(--guest-border); border-radius: 0.5rem; font-size: 1rem;">
                </div>

                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--guest-text);">
                        Message <span style="color: var(--guest-danger);">*</span>
                    </label>
                    <textarea name="message" rows="6" required placeholder="Please provide details about your inquiry..." class="modern-input" style="width: 100%; padding: 0.75rem; border: 1px solid var(--guest-border); border-radius: 0.5rem; font-size: 1rem; font-family: inherit; resize: vertical;"></textarea>
                </div>

                <button type="submit" class="guest-btn">Send Message</button>
            </form>
        </div>
    </div>

    <!-- Business Hours -->
    <?php if ($website['contact_hours'] ?? ''): ?>
        <div class="guest-card">
            <h2 class="guest-card-title">Business Hours</h2>
            <p style="color: var(--guest-text);"><?= nl2br(htmlspecialchars($website['contact_hours'])); ?></p>
        </div>
    <?php endif; ?>
</div>

<?php
$slot = ob_get_clean();
include view_path('layouts/guest.php');
?>

