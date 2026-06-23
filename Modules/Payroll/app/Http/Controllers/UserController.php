<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Restrict the entire controller to super admins.
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            if (!$user || !$user->hasRole('super_admin')) {
                abort(403, 'Only Super Admins can manage system users.');
            }

            return $next($request);
        });
    }

    // ── CRUD ─────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $tab = $request->query('tab', 'officers');

        // Calculate counts for tabs
        $officerCount = User::whereHas('roles', fn($q) => $q->where('name', '!=', 'employee'))->count();
        $employeeCount = User::whereHas('roles', fn($q) => $q->where('name', 'employee'))->count();

        if ($tab === 'employees') {
            $users = User::with(['roles', 'roleAssignments', 'employee'])
                ->whereHas('roles', fn($q) => $q->where('name', 'employee'))
                ->orderBy('name')
                ->get();
        } else {
            $users = User::with(['roles', 'roleAssignments', 'employee'])
                ->whereHas('roles', fn($q) => $q->where('name', '!=', 'employee'))
                ->orderBy('name')
                ->get();
        }

        return view('payroll::users.index', compact('users', 'tab', 'officerCount', 'employeeCount'));
    }

    public function create()
    {
        $roles = Role::orderBy('name')->get();
        $employees = \App\SharedKernel\Models\Employee::orderBy('last_name')->get();

        return view('payroll::users.create', compact('roles', 'employees'));
    }

    /**
     * Create a new user, assign their Spatie role, and create the
     * UserRoleAssignment tracking row.
     *
     * If a second (alternate) role is supplied, it is also assigned via Spatie
     * and gets its own tracking row — initially inactive so it doesn't
     * interfere with the primary active officer for that role.
     */
    public function store(Request $request)
    {
        $request->validate([
            'employee_id'    => ['required', 'exists:employees,id'],
            'role'           => ['required', 'string', 'exists:roles,name'],
            'secondary_role' => ['nullable', 'string', 'exists:roles,name', 'different:role'],
        ]);

        $employee = \App\SharedKernel\Models\Employee::findOrFail($request->employee_id);

        DB::transaction(function () use ($request, $employee) {
            $user = User::create([
                'name'              => $employee->first_name . ' ' . ($employee->middle_name ? $employee->middle_name . '. ' : '') . $employee->last_name,
                'email'             => null, // No email needed for HRIS SSO users
                'password'          => bcrypt('pass123'), // Default password for super admin management
                'email_verified_at' => null,
                'employee_id'       => $employee->id,
            ]);

            // ── Primary role ─────────────────────────────────────────────────
            $user->assignRole($request->role);

            UserRoleAssignment::create([
                'user_id'   => $user->id,
                'role_name' => $request->role,
                'is_active' => true,
            ]);

            // ── Secondary (alternate) role ───────────────────────────────────
            // Assigned but marked inactive by default — the Super Admin must
            // explicitly activate it via the toggle if/when the person acts
            // in that capacity.
            if ($request->filled('secondary_role')) {
                $user->assignRole($request->secondary_role);

                UserRoleAssignment::create([
                    'user_id'   => $user->id,
                    'role_name' => $request->secondary_role,
                    'is_active' => false,
                ]);
            }
        });

        return redirect()->route('users.index')
            ->with('success', "User {$employee->first_name} {$employee->last_name} created.");
    }

    public function show(User $user)
    {
        $user->load(['roles', 'roleAssignments.user']);

        return view('payroll::users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $roles = Role::orderBy('name')->get();
        $employees = \App\SharedKernel\Models\Employee::orderBy('last_name')->get();
        $user->load(['roles', 'roleAssignments']);

        return view('payroll::users.edit', compact('user', 'roles', 'employees'));
    }

    /**
     * Update user details and role assignments.
     *
     * syncRoles() on Spatie ensures the user only holds the declared roles.
     * We mirror that by deleting removed role assignments and upserting kept ones.
     */
    public function update(Request $request, User $user)
    {
        $isEmployee = $user->roles->count() === 1 && $user->hasRole('employee');

        if ($isEmployee) {
            // Employee: only password update
            $request->validate([
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            $user->password = bcrypt($request->password);
            $user->save();

            return redirect()->route('users.index', ['tab' => 'employees'])
                ->with('success', "Password updated for {$user->name}.");
        }

        // Officer: full role and password update
        $request->validate([
            'role'          => ['required', 'string', 'exists:roles,name'],
            'secondary_role'=> ['nullable', 'string', 'exists:roles,name', 'different:role'],
            'password'      => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        DB::transaction(function () use ($request, $user) {
            // No name/email updates needed - they come from employee record

            // Update password if provided
            if ($request->filled('password')) {
                $user->password = bcrypt($request->password);
                $user->save();
            }

            // Build the new set of roles
            $newRoles = array_filter([
                $request->role,
                $request->secondary_role ?: null,
            ]);

            // Sync Spatie roles
            $user->syncRoles($newRoles);

            // Remove tracking rows for roles no longer assigned
            $user->roleAssignments()
                 ->whereNotIn('role_name', $newRoles)
                 ->delete();

            // Upsert tracking rows for current roles
            // Primary role: always active
            UserRoleAssignment::updateOrCreate(
                ['user_id' => $user->id, 'role_name' => $request->role],
                ['is_active' => true]
            );

            // Secondary role: keep existing is_active state; create as inactive if new
            if ($request->filled('secondary_role')) {
                UserRoleAssignment::firstOrCreate(
                    ['user_id' => $user->id, 'role_name' => $request->secondary_role],
                    ['is_active' => false]
                );
            }
        });

        return redirect()->route('users.index', ['tab' => 'officers'])
            ->with('success', "User {$user->name} updated.");
    }

    public function destroy(User $user)
    {
        $authUser = Auth::user();

        if ($user->id === $authUser->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        // Prevent deleting HRIS-only employee accounts from here
        if ($user->roles->count() === 1 && $user->hasRole('employee')) {
            return back()->with('error', 'HRIS employee accounts cannot be removed from User Management.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('users.index')->with('success', "User {$name} has been removed.");
    }

    // ── Role activation toggle ────────────────────────────────────────────────

    /**
     * Toggle the is_active flag on a specific role assignment for a user.
     *
     * Activating a role for user X automatically deactivates that same role
     * for all other users — ensuring only one person is the acting officer
     * per role at any point in time.
     */
    public function activateRole(Request $request, User $user)
    {
        $request->validate([
            'role_name' => ['required', 'string', 'exists:roles,name'],
        ]);

        $roleName   = $request->role_name;
        $assignment = $user->roleAssignments()->where('role_name', $roleName)->firstOrFail();

        DB::transaction(function () use ($assignment, $roleName, $user) {
            if (!$assignment->is_active) {
                // Deactivate all other users' assignments for this role
                UserRoleAssignment::where('role_name', $roleName)
                                  ->where('user_id', '!=', $user->id)
                                  ->update(['is_active' => false]);
            }

            $assignment->update(['is_active' => !$assignment->is_active]);
        });

        $state = $assignment->fresh()->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "{$user->name} {$state} as {$roleName}.");
    }
}
