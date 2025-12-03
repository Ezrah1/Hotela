<?php

namespace App\Support;

/**
 * Helper class to map roles to departments and vice versa
 */
class DepartmentHelper
{
    /**
     * Map role_key to department name
     */
    public static function getDepartmentFromRole(string $roleKey): ?string
    {
        $roleDepartmentMap = [
            'service_agent' => 'service',
            'receptionist' => 'front_desk',
            'cashier' => 'cashier',
            'kitchen' => 'kitchen',
            'housekeeping' => 'housekeeping',
            'ground' => 'maintenance',
            'security' => 'security',
            'director' => 'management',
            'operation_manager' => 'management',
            'finance_manager' => 'management',
            'admin' => 'management',
            'tech' => 'management',
        ];

        return $roleDepartmentMap[$roleKey] ?? null;
    }

    /**
     * Get all role keys for a department
     */
    public static function getRolesForDepartment(string $department): array
    {
        $departmentRoleMap = [
            'front_desk' => ['service_agent', 'receptionist'],
            'cashier' => ['cashier'],
            'service' => ['service_agent', 'receptionist'],
            'kitchen' => ['kitchen'],
            'housekeeping' => ['housekeeping'],
            'maintenance' => ['ground'],
            'security' => ['security'],
            'management' => ['director', 'operation_manager', 'finance_manager', 'admin', 'tech'],
        ];

        return $departmentRoleMap[$department] ?? [];
    }

    /**
     * Check if a role can view all departments (management roles)
     * Supports multiple roles - returns true if user has any management role
     */
    public static function canViewAllDepartments(?string $roleKey = null): bool
    {
        if ($roleKey) {
            return in_array($roleKey, ['director', 'operation_manager', 'finance_manager', 'admin'], true);
        }
        
        // Check all user roles
        return \App\Support\Auth::hasRole('director') || 
               \App\Support\Auth::hasRole('operation_manager') || 
               \App\Support\Auth::hasRole('finance_manager') || 
               \App\Support\Auth::hasRole('admin');
    }

    /**
     * Check if a role can approve requisitions
     * Supports multiple roles - returns true if user has any approval role
     */
    public static function canApproveRequisitions(?string $roleKey = null): bool
    {
        if ($roleKey) {
            return in_array($roleKey, ['director', 'operation_manager', 'finance_manager', 'admin'], true);
        }
        
        return \App\Support\Auth::hasRole('director') || 
               \App\Support\Auth::hasRole('operation_manager') || 
               \App\Support\Auth::hasRole('finance_manager') || 
               \App\Support\Auth::hasRole('admin');
    }

    /**
     * Check if a role can verify operations
     * Supports multiple roles - returns true if user has any ops verification role
     */
    public static function canVerifyOperations(?string $roleKey = null): bool
    {
        if ($roleKey) {
            return in_array($roleKey, ['director', 'operation_manager', 'admin'], true);
        }
        
        return \App\Support\Auth::hasRole('director') || 
               \App\Support\Auth::hasRole('operation_manager') || 
               \App\Support\Auth::hasRole('admin');
    }

    /**
     * Check if a role can approve finance
     * Supports multiple roles - returns true if user has any finance approval role
     */
    public static function canApproveFinance(?string $roleKey = null): bool
    {
        if ($roleKey) {
            return in_array($roleKey, ['director', 'finance_manager', 'admin'], true);
        }
        
        return \App\Support\Auth::hasRole('director') || 
               \App\Support\Auth::hasRole('finance_manager') || 
               \App\Support\Auth::hasRole('admin');
    }

    /**
     * Check if a role can assign suppliers
     * Supports multiple roles - returns true if user has any supplier assignment role
     */
    public static function canAssignSuppliers(?string $roleKey = null): bool
    {
        if ($roleKey) {
            return in_array($roleKey, ['director', 'operation_manager', 'finance_manager', 'admin'], true);
        }
        
        return \App\Support\Auth::hasRole('director') || 
               \App\Support\Auth::hasRole('operation_manager') || 
               \App\Support\Auth::hasRole('finance_manager') || 
               \App\Support\Auth::hasRole('admin');
    }
}

