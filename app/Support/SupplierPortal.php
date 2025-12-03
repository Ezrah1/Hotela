<?php

namespace App\Support;

class SupplierPortal
{
    protected const SESSION_KEY = '__supplier_portal';

    public static function user(): ?array
    {
        return $_SESSION[self::SESSION_KEY] ?? null;
    }

    public static function login(array $payload): void
    {
        $_SESSION[self::SESSION_KEY] = [
            'supplier_id' => $payload['supplier_id'] ?? null,
            'supplier_name' => $payload['supplier_name'] ?? null,
            'supplier_email' => $payload['supplier_email'] ?? null,
            'supplier_phone' => $payload['supplier_phone'] ?? null,
            'identifier' => $payload['identifier'] ?? ($payload['supplier_email'] ?? $payload['supplier_phone']),
            'identifier_type' => $payload['identifier_type'] ?? (isset($payload['supplier_email']) ? 'email' : 'phone'),
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
        header('Location: ' . base_url('supplier/login?redirect=' . urlencode($target)));
        exit;
    }

    public static function supplierId(): ?int
    {
        $user = self::user();
        return $user ? (int)($user['supplier_id'] ?? null) : null;
    }
}

