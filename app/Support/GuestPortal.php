<?php

namespace App\Support;

class GuestPortal
{
    protected const SESSION_KEY = '__guest_portal';

    public static function user(): ?array
    {
        return $_SESSION[self::SESSION_KEY] ?? null;
    }

    public static function login(array $payload): void
    {
        $_SESSION[self::SESSION_KEY] = [
            'guest_name' => $payload['guest_name'] ?? null,
            'guest_email' => $payload['guest_email'] ?? null,
            'guest_phone' => $payload['guest_phone'] ?? null,
            'identifier' => $payload['identifier'] ?? ($payload['guest_email'] ?? $payload['guest_phone']),
            'identifier_type' => $payload['identifier_type'] ?? (isset($payload['guest_email']) ? 'email' : 'phone'),
            'reference' => $payload['reference'] ?? null,
        ];
    }

    public static function logout(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
    }

    public static function check(): bool
    {
        return isset($_SESSION[self::SESSION_KEY]);
    }

    public static function requireLogin(?string $redirectUri = null): void
    {
        if (self::check()) {
            return;
        }

        $target = $redirectUri ?? ($_SERVER['REQUEST_URI'] ?? '/');
        if (!preg_match('#^https?://#', $target)) {
            $target = base_url(ltrim($target, '/'));
        }
        header('Location: ' . base_url('guest/login?redirect=' . urlencode($target)));
        exit;
    }
}

