<?php

namespace App\Modules\Website\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\ReservationRepository;
use App\Support\GuestPortal;

class GuestPortalController extends Controller
{
    protected ReservationRepository $reservations;

    public function __construct()
    {
        $this->reservations = new ReservationRepository();
    }

    public function showLogin(Request $request): void
    {
        if (GuestPortal::check()) {
            header('Location: ' . base_url('guest/portal'));
            return;
        }

        $this->view('website/guest/login', [
            'redirect' => $request->input('redirect', base_url('guest/portal')),
            'pageTitle' => 'Guest Portal Login | ' . settings('branding.name', 'Hotela'),
        ]);
    }

    public function authenticate(Request $request): void
    {
        $reference = trim((string)$request->input('reference'));
        $identifier = trim((string)$request->input('identifier'));
        $redirect = $request->input('redirect', base_url('guest/portal'));

        if ($reference === '' || $identifier === '') {
            header('Location: ' . base_url('guest/login?redirect=' . urlencode($redirect) . '&error=missing'));
            return;
        }

        $reservation = $this->reservations->validateGuestAccess($reference, $identifier);
        if (!$reservation) {
            header('Location: ' . base_url('guest/login?redirect=' . urlencode($redirect) . '&error=invalid'));
            return;
        }

        GuestPortal::login([
            'guest_name' => $reservation['guest_name'],
            'guest_email' => $reservation['guest_email'],
            'guest_phone' => $reservation['guest_phone'],
            'identifier' => str_contains($identifier, '@') ? strtolower($identifier) : preg_replace('/[^0-9]/', '', $identifier),
            'identifier_type' => str_contains($identifier, '@') ? 'email' : 'phone',
            'reference' => $reservation['reference'],
        ]);

        header('Location: ' . $redirect);
    }

    public function logout(Request $request): void
    {
        GuestPortal::logout();
        $redirect = $request->input('redirect', base_url('/'));
        header('Location: ' . $redirect);
    }

    public function dashboard(): void
    {
        GuestPortal::requireLogin('guest/portal');
        $session = GuestPortal::user();
        $identifier = $session['identifier'] ?? '';

        $reservations = $identifier ? $this->reservations->listForGuest($identifier) : [];

        $this->view('website/guest/dashboard', [
            'guest' => $session,
            'reservations' => $reservations,
            'pageTitle' => 'My Stays & Orders | ' . settings('branding.name', 'Hotela'),
        ]);
    }
}

