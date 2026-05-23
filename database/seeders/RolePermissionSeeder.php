<?php

namespace Database\Seeders;

use App\SharedKernel\Enums\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission as SpatiePermission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create all permissions from the enum
        foreach (Permission::cases() as $permission) {
            SpatiePermission::firstOrCreate([
                'name' => $permission->value,
                'guard_name' => 'web',
            ]);
        }

        // Create roles and assign permissions
        $payrollOfficer = Role::firstOrCreate(['name' => 'payroll_officer', 'guard_name' => 'web']);
        $payrollOfficer->syncPermissions([
            // Module/UI Navigation Access
            Permission::PAYROLL_ACCESS->value,
            Permission::CONFIGURATION_ACCESS->value,
            Permission::REPORTS_ACCESS->value,
            Permission::ADMINISTRATION_ACCESS->value,
            // Payroll functional permissions
            Permission::PAYROLL_VIEW->value,
            Permission::PAYROLL_CREATE->value,
            Permission::PAYROLL_DELETE_DRAFT->value,
            Permission::PAYROLL_COMPUTE->value,
            Permission::PAYROLL_SUBMIT->value,
            Permission::PAYROLL_SPECIAL_MANAGE->value,
            Permission::PAYROLL_REPORTS_VIEW->value,
            // Employees
            Permission::EMPLOYEES_VIEW->value,
            Permission::EMPLOYEES_MANAGE->value,
            // System
            Permission::USERS_MANAGE->value,
        ]);

        $hrmo = Role::firstOrCreate(['name' => 'hrmo', 'guard_name' => 'web']);
        $hrmo->syncPermissions([
            // Module/UI Navigation Access
            Permission::PAYROLL_ACCESS->value,
            Permission::TEV_ACCESS->value,
            // Payroll functional permissions
            Permission::PAYROLL_VIEW->value,
            // TEV Office Orders
            Permission::TEV_OFFICE_ORDERS_VIEW->value,
            Permission::TEV_OFFICE_ORDERS_PULL->value,
            Permission::TEV_OFFICE_ORDERS_CANCEL->value,
            // TEV Vouchers
            Permission::TEV_VOUCHERS_VIEW->value,
            Permission::TEV_VOUCHERS_CREATE->value,
            // TEV Reports
            Permission::TEV_REPORTS_VIEW->value,
            // Employees
            Permission::EMPLOYEES_VIEW->value,
            Permission::EMPLOYEES_MANAGE->value,
        ]);

        $accountant = Role::firstOrCreate(['name' => 'accountant', 'guard_name' => 'web']);
        $accountant->syncPermissions([
            // Module/UI Navigation Access
            Permission::PAYROLL_ACCESS->value,
            Permission::TEV_ACCESS->value,
            // Payroll functional permissions
            Permission::PAYROLL_VIEW->value,
            // TEV Vouchers
            Permission::TEV_VOUCHERS_VIEW->value,
            Permission::TEV_VOUCHERS_CERTIFY->value,
            // TEV Reports
            Permission::TEV_REPORTS_VIEW->value,
        ]);

        $ard = Role::firstOrCreate(['name' => 'ard', 'guard_name' => 'web']);
        $ard->syncPermissions([
            // Module/UI Navigation Access
            Permission::PAYROLL_ACCESS->value,
            Permission::TEV_ACCESS->value,
            // Payroll functional permissions
            Permission::PAYROLL_VIEW->value,
            Permission::PAYROLL_APPROVE->value,
            // TEV Office Orders
            Permission::TEV_OFFICE_ORDERS_VIEW->value,
            Permission::TEV_OFFICE_ORDERS_APPROVE->value,
            Permission::TEV_OFFICE_ORDERS_CANCEL->value,
            // TEV Vouchers
            Permission::TEV_VOUCHERS_VIEW->value,
            Permission::TEV_VOUCHERS_APPROVE->value,
            // TEV Reports
            Permission::TEV_REPORTS_VIEW->value,
        ]);

        $cashier = Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web']);
        $cashier->syncPermissions([
            // Module/UI Navigation Access
            Permission::PAYROLL_ACCESS->value,
            Permission::TEV_ACCESS->value,
            // Payroll functional permissions
            Permission::PAYROLL_VIEW->value,
            // TEV Vouchers
            Permission::TEV_VOUCHERS_VIEW->value,
            Permission::TEV_VOUCHERS_DISBURSE->value,
        ]);

        $budgetOfficer = Role::firstOrCreate(['name' => 'budget_officer', 'guard_name' => 'web']);
        $budgetOfficer->syncPermissions([
            // Module/UI Navigation Access
            Permission::TEV_ACCESS->value,
            Permission::REPORTS_ACCESS->value,
            // TEV Vouchers
            Permission::TEV_VOUCHERS_VIEW->value,
            // TEV Reports
            Permission::TEV_REPORTS_VIEW->value,
        ]);

        $chiefAdminOfficer = Role::firstOrCreate(['name' => 'chief_admin_officer', 'guard_name' => 'web']);
        $chiefAdminOfficer->syncPermissions([
            // Module/UI Navigation Access
            Permission::PAYROLL_ACCESS->value,
            Permission::TEV_ACCESS->value,
            // Payroll functional permissions
            Permission::PAYROLL_VIEW->value,
            Permission::PAYROLL_APPROVE->value,
            // TEV Office Orders
            Permission::TEV_OFFICE_ORDERS_VIEW->value,
            Permission::TEV_OFFICE_ORDERS_CANCEL->value,
            // TEV Vouchers
            Permission::TEV_VOUCHERS_VIEW->value,
        ]);

        $employee = Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
        $employee->syncPermissions([
            // TEV Vouchers (own only - this is handled by policy, not permission)
            Permission::TEV_VOUCHERS_VIEW->value,
        ]);

        // Super Admin gets ALL permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $allPermissions = array_map(fn($permission) => $permission->value, Permission::cases());
        $superAdmin->syncPermissions($allPermissions);
    }
}
