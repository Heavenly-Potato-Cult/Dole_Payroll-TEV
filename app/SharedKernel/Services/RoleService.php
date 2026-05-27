<?php

namespace App\SharedKernel\Services;

use App\Models\User;

class RoleService
{
    // ── Module-based Role Definitions ────────────────────────────────────────

    /**
     * Dashboard Module - All system roles can access dashboard
     */
    const MODULE_DASHBOARD = [
        'payroll_officer',
        'hrmo',
        'accountant',
        'ard',
        'cashier',
        'budget_officer',
        'chief_admin_officer',
        'super_admin'
    ];

    /**
     * Payroll Module - Core payroll management
     */
    const MODULE_PAYROLL = [
        'payroll_officer',
        'hrmo',
        'accountant',
        'ard',
        'cashier',
        'chief_admin_officer',
        'super_admin'
    ];

    /**
     * Payroll Creation - Only payroll officers can create batches
     */
    const MODULE_PAYROLL_CREATE = [
        'payroll_officer'
    ];

    /**
     * Special Payroll Module - Differential, Newly Hired, NOSI/NOSA
     */
    const MODULE_SPECIAL_PAYROLL = [
        'payroll_officer',
        'hrmo',
        'accountant',
        'ard',
        'cashier',
        'chief_admin_officer',
        'super_admin'
    ];

    /**
     * TEV Module - Travel Expense Voucher management
     */
    const MODULE_TEV = [
        'payroll_officer',
        'hrmo',
        'super_admin'
    ];

    /**
     * TEV Management - All TEV roles except payroll_officer
     */
    const MODULE_TEV_MANAGEMENT = [
        'hrmo',
        'accountant',
        'budget_officer',
        'ard',
        'cashier',
        'chief_admin_officer',
        'super_admin'
    ];

    /**
     * Employee Management Module
     */
    const MODULE_EMPLOYEE_MANAGEMENT = [
        'payroll_officer',
        'hrmo',
        'accountant',
        'chief_admin_officer',
        'super_admin'
    ];

    /**
     * Division Management Module
     */
    const MODULE_DIVISION_MANAGEMENT = [
        'payroll_officer',
        'hrmo',
        'super_admin'
    ];

    /**
     * Reports Module
     */
    const MODULE_REPORTS = [
        'payroll_officer',
        'super_admin'
    ];

    /**
     * Configuration Module - Deductions, Loans CMS
     */
    const MODULE_CONFIGURATION = [
        'payroll_officer',
        'super_admin'
    ];

    /**
     * Signatories Module - Dynamic signing officers on payslips/reports
     */
    const MODULE_SIGNATORIES = [
        'payroll_officer',
        'super_admin'
    ];

    /**
     * User Management Module - Super admin only
     */
    const MODULE_USER_MANAGEMENT = [
        'payroll_officer',
        'super_admin'
    ];

    /**
     * Approval Chain Module - ARD & CAO
     */
    const MODULE_APPROVAL = [
        'ard',
        'chief_admin_officer'
    ];

    /**
     * Finance/Disbursement Module
     */
    const MODULE_FINANCE = [
        'cashier',
        'budget_officer'
    ];

    // ── Role Group Helpers (for backward compatibility) ───────────────────────

    /**
     * Legacy: Roles that can access payroll management features
     */
    const PAYROLL_ROLES = self::MODULE_PAYROLL;

    /**
     * Legacy: Roles that can create payroll batches
     */
    const PAYROLL_CREATE_ROLES = self::MODULE_PAYROLL_CREATE;

    /**
     * Legacy: Roles that can manage special payroll
     */
    const SPECIAL_PAYROLL_ROLES = self::MODULE_SPECIAL_PAYROLL;

    /**
     * Legacy: Roles that can access TEV features
     */
    const TEV_ROLES = self::MODULE_TEV;

    // ── Module Access Methods ───────────────────────────────────────────────────

    /**
     * Check if user can access a specific module
     */
    public static function canAccessModule(User $user, string $module): bool
    {
        return match($module) {
            'dashboard' => $user->hasAnyRole(self::MODULE_DASHBOARD),
            'payroll' => $user->hasAnyRole(self::MODULE_PAYROLL),
            'payroll_create' => $user->hasAnyRole(self::MODULE_PAYROLL_CREATE),
            'special_payroll' => $user->hasAnyRole(self::MODULE_SPECIAL_PAYROLL),
            'tev' => $user->hasAnyRole(self::MODULE_TEV),
            'tev_management' => $user->hasAnyRole(self::MODULE_TEV_MANAGEMENT),
            'employee_management' => $user->hasAnyRole(self::MODULE_EMPLOYEE_MANAGEMENT),
            'division_management' => $user->hasAnyRole(self::MODULE_DIVISION_MANAGEMENT),
            'reports' => $user->hasAnyRole(self::MODULE_REPORTS),
            'configuration' => $user->hasAnyRole(self::MODULE_CONFIGURATION),
            'signatories' => $user->hasAnyRole(self::MODULE_SIGNATORIES),
            'user_management' => $user->hasAnyRole(self::MODULE_USER_MANAGEMENT),
            'approval' => $user->hasAnyRole(self::MODULE_APPROVAL),
            'finance' => $user->hasAnyRole(self::MODULE_FINANCE),
            default => false,
        };
    }

    /**
     * Get roles for a specific module
     */
    public static function getModuleRoles(string $module): array
    {
        return match($module) {
            'dashboard' => self::MODULE_DASHBOARD,
            'payroll' => self::MODULE_PAYROLL,
            'payroll_create' => self::MODULE_PAYROLL_CREATE,
            'special_payroll' => self::MODULE_SPECIAL_PAYROLL,
            'tev' => self::MODULE_TEV,
            'tev_management' => self::MODULE_TEV_MANAGEMENT,
            'employee_management' => self::MODULE_EMPLOYEE_MANAGEMENT,
            'division_management' => self::MODULE_DIVISION_MANAGEMENT,
            'reports' => self::MODULE_REPORTS,
            'configuration' => self::MODULE_CONFIGURATION,
            'signatories' => self::MODULE_SIGNATORIES,
            'user_management' => self::MODULE_USER_MANAGEMENT,
            'approval' => self::MODULE_APPROVAL,
            'finance' => self::MODULE_FINANCE,
            default => [],
        };
    }

    // ── Legacy Role Group Methods (for backward compatibility) ─────────────────

    /**
     * @deprecated Use Permission enum instead. This method is for backward compatibility only.
     */
    public static function hasRoleGroup(User $user, string $group): bool
    {
        return match($group) {
            'payroll' => self::canAccessModule($user, 'payroll'),
            'payroll_create' => self::canAccessModule($user, 'payroll_create'),
            'special_payroll' => self::canAccessModule($user, 'special_payroll'),
            'tev' => self::canAccessModule($user, 'tev'),
            'payroll_management' => self::canAccessModule($user, 'payroll'),
            'approval' => self::canAccessModule($user, 'approval'),
            'finance' => self::canAccessModule($user, 'finance'),
            'tev_management' => self::canAccessModule($user, 'tev_management'),
            'payroll_officer_admin' => self::isPayrollOfficerOrAdmin($user),
            default => false,
        };
    }

    /**
     * @deprecated Use Permission enum instead. This method is for backward compatibility only.
     */
    public static function getRoleGroup(string $group): array
    {
        return match($group) {
            'payroll' => self::MODULE_PAYROLL,
            'payroll_create' => self::MODULE_PAYROLL_CREATE,
            'special_payroll' => self::MODULE_SPECIAL_PAYROLL,
            'tev' => self::MODULE_TEV,
            'payroll_management' => self::MODULE_PAYROLL,
            'approval' => self::MODULE_APPROVAL,
            'finance' => self::MODULE_FINANCE,
            'tev_management' => self::MODULE_TEV_MANAGEMENT,
            'payroll_officer_admin' => self::MODULE_PAYROLL_CREATE,
            default => [],
        };
    }

    // ── Convenience Methods (legacy - for backward compatibility) ───────────────

    /**
     * @deprecated Use Permission enum instead. This method is for backward compatibility only.
     */
    public static function isPayrollOfficerOrAdmin(User $user): bool
    {
        return self::hasRoleGroup($user, 'payroll_officer_admin');
    }

    // ── Single Role Checks (for specific role-based logic) ─────────────────────

    /**
     * @deprecated Use Permission enum instead. This method is for backward compatibility only.
     */
    public static function isEmployee(User $user): bool
    {
        return $user->hasRole('employee');
    }

    /**
     * @deprecated Use Permission enum instead. This method is for backward compatibility only.
     */
    public static function isCashier(User $user): bool
    {
        return $user->hasRole('cashier');
    }

    /**
     * @deprecated Use Permission enum instead. This method is for backward compatibility only.
     */
    public static function isAccountant(User $user): bool
    {
        return $user->hasRole('accountant');
    }

    /**
     * @deprecated Use Permission enum instead. This method is for backward compatibility only.
     */
    public static function isHrmo(User $user): bool
    {
        return $user->hasRole('hrmo');
    }

    /**
     * @deprecated Use Permission enum instead. This method is for backward compatibility only.
     */
    public static function isPayrollOfficer(User $user): bool
    {
        return $user->hasRole('payroll_officer');
    }

    /**
     * @deprecated Use Permission enum instead. This method is for backward compatibility only.
     */
    public static function isApprovalRole(User $user): bool
    {
        return self::canAccessModule($user, 'approval');
    }

    /**
     * @deprecated Use Permission enum instead. This method is for backward compatibility only.
     */
    public static function isSuperAdmin(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * @deprecated Use Permission enum instead. This method is for backward compatibility only.
     */
    public static function hasAnyRoles(User $user, array $roles): bool
    {
        return $user->hasAnyRole($roles);
    }

    /**
     * Check if user can access payroll module
     */
    public static function canAccessPayroll(User $user): bool
    {
        return self::canAccessModule($user, 'payroll');
    }

    /**
     * Check if user can create payroll batches
     */
    public static function canCreatePayroll(User $user): bool
    {
        return self::canAccessModule($user, 'payroll_create');
    }
}
