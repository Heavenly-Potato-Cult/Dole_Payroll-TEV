<?php

namespace App\SharedKernel\Services;

use App\SharedKernel\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class EmployeeAuthService
{
    /**
     * Authenticate employee using employee_id and password.
     * Returns employee data if valid, null otherwise.
     */
    public function authenticate(string $employeeId, string $password): ?Employee
    {
        $employee = Employee::where('employee_no', $employeeId)->first();
        
        if (!$employee) {
            Log::warning('Employee login failed: employee not found', [
                'employee_id' => $employeeId,
            ]);
            return null;
        }

        // For now, use the same demo password as HRIS: "pass123"
        // In production, this should be replaced with proper password hashing
        if ($password !== 'pass123') {
            Log::warning('Employee login failed: invalid password', [
                'employee_id' => $employeeId,
            ]);
            return null;
        }

        Log::info('Employee authenticated successfully', [
            'employee_id' => $employeeId,
            'name' => $employee->full_name,
        ]);

        return $employee;
    }

    /**
     * Determine if employee is an officer based on position/title.
     * Returns true for payroll officers, HRMO, Accountant, etc.
     */
    public function isOfficer(Employee $employee): bool
    {
        $officerKeywords = [
            'officer',
            'manager',
            'supervisor',
            'director',
            'chief',
            'head',
            'hrmo',
            'accountant',
            'payroll',
        ];

        $positionTitle = strtolower($employee->position_title ?? '');
        
        foreach ($officerKeywords as $keyword) {
            if (str_contains($positionTitle, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve or create User account for authenticated employee.
     * Similar to HRIS resolveHrisUser logic but uses Employee model directly.
     */
    public function resolveUser(Employee $employee): User
    {
        // Check if user already exists
        $user = User::where('employee_id', $employee->id)->first();

        if ($user) {
            Log::info('Existing user found for employee', [
                'employee_id' => $employee->employee_no,
                'user_id' => $user->id,
                'roles' => $user->getRoleNames()->toArray(),
            ]);
            return $user;
        }

        // Create new user
        $user = User::create([
            'name' => $employee->full_name,
            'email' => null, // Employees may not have email accounts
            'password' => Hash::make(uniqid()), // Random password, they login via employee auth
            'employee_id' => $employee->id,
        ]);

        // Assign role based on position
        if ($this->isOfficer($employee)) {
            $user->assignRole('payroll_officer');
            Log::info('New user created as officer', [
                'employee_id' => $employee->employee_no,
                'position' => $employee->position_title,
            ]);
        } else {
            $user->assignRole('employee');
            Log::info('New user created as employee', [
                'employee_id' => $employee->employee_no,
                'position' => $employee->position_title,
            ]);
        }

        return $user;
    }

    /**
     * Get redirect destination after login based on user roles.
     */
    public function getRedirectDestination(User $user): string
    {
        $isEmployee = $user->hasRole('employee');
        $isOfficer = $user->hasAnyRole(RoleService::getRoleGroup('payroll'));

        if ($isEmployee && !$isOfficer) {
            return route('my-payslip');
        } else {
            return route('payroll.dashboard');
        }
    }
}
