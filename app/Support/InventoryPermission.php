<?php

namespace App\Support;

class InventoryPermission
{
    // Capability map by role_key
    protected static array $capabilities = [
        'admin' => [
            'inventory.view',
            'inventory.edit_item',
            'inventory.delete_item',
            'inventory.manage_categories',
            'inventory.adjust_stock',
            'inventory.view_logs',
            'inventory.manage_suppliers',
            'inventory.view_valuation',
            'inventory.approve_po',
            'inventory.requisitions.view',
            'inventory.requisitions.create',
            'inventory.requisitions.receive',
        ],
        'finance_manager' => [
            'inventory.view',
            'inventory.edit_price',
            'inventory.view_valuation',
            'inventory.view_logs',
            'inventory.approve_po',
            'inventory.requisitions.view',
        ],
        'operation_manager' => [
            'inventory.view',
            'inventory.adjust_stock',
            'inventory.requisitions.view',
            'inventory.requisitions.create',
            'inventory.requisitions.receive',
        ],
        'cashier' => [
            'inventory.view_list',
            'inventory.requisitions.view',
            'inventory.requisitions.create',
        ],
        'housekeeping' => [
            // optionally grant 'inventory.view_list_supplies' via future settings
            'inventory.requisitions.view',
            'inventory.requisitions.create',
        ],
        'ground' => [
            'inventory.requisitions.view',
            'inventory.requisitions.create',
        ],
        'security' => [
            'inventory.requisitions.view',
            'inventory.requisitions.create',
        ],
    ];

    public static function can(string $action, ?string $roleKey = null): bool
    {
        $role = $roleKey ?? (Auth::user()['role_key'] ?? Auth::user()['role'] ?? null);
        if (!$role) {
            return false;
        }
        $allowed = self::$capabilities[$role] ?? [];
        return in_array($action, $allowed, true) || in_array('*', $allowed, true);
    }

    public static function require(string $action, ?string $roleKey = null): void
    {
        if (!self::can($action, $roleKey)) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
    }
}


