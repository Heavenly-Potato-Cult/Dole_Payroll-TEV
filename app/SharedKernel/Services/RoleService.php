<?php

namespace App\SharedKernel\Services;

use App\Models\User;

class RoleService
{
    /**
     * Base role names.
     */
    const EMPLOYEE_ROLES = [
        'employee',
    ];

    /**
     * Roles that can access payroll management features
     */
    const PAYROLL_ROLES = [
        'payroll_officer',
        'hrmo', 
        'accountant',
        'ard',
        'cashier',
        'chief_admin_officer',
        'super_admin'
    ];

    /**
     * Roles that can create payroll batches
     */
    const PAYROLL_CREATE_ROLES = [
        'payroll_officer'
    ];

    /**
     * Roles that can manage special payroll
     */
    const SPECIAL_PAYROLL_ROLES = [
        'payroll_officer',
        'hrmo',
        'accountant',
        'ard',
        'cashier',
        'chief_admin_officer',
        'super_admin'
    ];

    /**
     * Roles that can access TEV features
     */
    const TEV_ROLES = [
        'payroll_officer',
        'hrmo',
        'super_admin'
    ];

    /**
     * Check if user has any of the specified role groups
     */
    public static function hasAnyRoleGroup(User $user, array $roleGroups): bool
    {
        foreach ($roleGroups as $group) {
            if (self::hasRoleGroup($user, $group)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has a specific role group
     */
    public static function hasRoleGroup(User $user, string $group): bool
    {
        return match($group) {
            'payroll' => $user->hasAnyRole(self::PAYROLL_ROLES),
            'payroll_create' => $user->hasAnyRole(self::PAYROLL_CREATE_ROLES),
            'special_payroll' => $user->hasAnyRole(self::SPECIAL_PAYROLL_ROLES),
            'tev' => $user->hasAnyRole(self::TEV_ROLES),
            default => false,
        };
    }

    /**
     * Get roles for a specific group
     */
    public static function getRoleGroup(string $group): array
    {
        return match($group) {
            'payroll' => self::PAYROLL_ROLES,
            'payroll_create' => self::PAYROLL_CREATE_ROLES,
            'special_payroll' => self::SPECIAL_PAYROLL_ROLES,
            'tev' => self::TEV_ROLES,
            default => [],
        };
    }

    /**
     * Check if user can access payroll management
     */
    public static function canAccessPayroll(User $user): bool
    {
        return self::hasRoleGroup($user, 'payroll');
    }

    /**
     * Check if user can create payroll batches
     */
    public static function canCreatePayroll(User $user): bool
    {
        return self::hasRoleGroup($user, 'payroll_create');
    }

    /**
     * Check if user can access special payroll
     */
    public static function canAccessSpecialPayroll(User $user): bool
    {
        return self::hasRoleGroup($user, 'special_payroll');
    }

    /**
     * Check if user can access TEV features
     */
    public static function canAccessTev(User $user): bool
    {
        return self::hasRoleGroup($user, 'tev');
    }

    /**
     * Check if user is a pure employee (non-officer account).
     *
     * NOTE: Some users may have multiple roles; this only checks the existence
     * of the `employee` role.
     */
    public static function isEmployee(User $user): bool
    {
        return $user->hasAnyRole(self::EMPLOYEE_ROLES);
    }

    /**
     * Check if user is a cashier.
     */
    public static function isCashier(User $user): bool
    {
        return $user->hasRole('cashier');
    }

    /**
     * Generic module access checker used by legacy code paths.
     *
     * @param string $module Role group key (e.g. `payroll`, `special_payroll`, `tev`)
     */
    public static function canAccessModule(User $user, string $module): bool
    {
        return self::hasRoleGroup($user, $module);
    }
}
