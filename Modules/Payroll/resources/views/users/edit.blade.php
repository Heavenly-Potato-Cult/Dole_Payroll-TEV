@extends('layouts.app')

@section('title', 'Edit User')
@section('page-title', 'Edit User')

@section('styles')
<style>
.role-assignment-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 14px;
    background: white;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    margin-bottom: 8px;
}
.role-assignment-row.is-active {
    border-left: 3px solid #2E7D52;
    background: #F6FBF7;
}
.role-assignment-name {
    font-size: 0.88rem;
    font-weight: 600;
    color: var(--navy);
}
.role-assignment-meta {
    font-size: 0.75rem;
    color: var(--text-light);
    margin-top: 2px;
}
</style>
@endsection

@section('content')

@php
    $isEmployee = $user->roles->count() === 1 && $user->hasRole('employee');
@endphp

<div class="page-header">
    <div class="page-header-left">
        <h1>Edit {{ $isEmployee ? 'Employee' : 'Officer' }}</h1>
        <p>{{ $user->email ?? 'No email' }}</p>
    </div>
    <a href="{{ route('users.index', ['tab' => $isEmployee ? 'employees' : 'officers']) }}" class="btn btn-outline">← Back</a>
</div>

<div style="max-width: 1200px; margin: 0 auto;">

    {{-- ── Linked Employee Info ─────────────────────────────────────── --}}
    @if ($user->employee)
    <div class="card" style="margin-bottom:20px; background:#F8FAFF; border-color:#E3E8FF;">
        <div class="card-header" style="background:#F0F4FF;">
            <h3>📋 Linked Employee</h3>
        </div>
        <div class="card-body">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:40px; height:40px; border-radius:50%; background:#E3E8FF; color:#4A5FC1; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.92rem;">
                    {{ strtoupper(substr($user->employee->first_name, 0, 1)) }}{{ strtoupper(substr($user->employee->last_name, 0, 1)) }}
                </div>
                <div>
                    <div style="font-weight:600; color:#1E293B;">
                        {{ $user->employee->first_name }} {{ $user->employee->middle_name ? $user->employee->middle_name . '. ' : '' }}{{ $user->employee->last_name }}
                    </div>
                    <div style="font-size:0.78rem; color:#64748B;">
                        {{ $user->employee->employee_no }} • {{ $user->employee->position_title }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Two-column layout for form and role assignments ───────────── --}}
    @if ($isEmployee)
        {{-- ── Employee: Simple password-only form ───────────────────────── --}}
        <div class="card" style="max-width: 600px; margin: 0 auto;">
            <div class="card-header"><h3>Change Password</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('users.update', $user) }}">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="password">New Password <span style="color:var(--red)">*</span></label>
                        <input type="password" id="password" name="password"
                               class="{{ $errors->has('password') ? 'is-invalid' : '' }}"
                               placeholder="Enter new password" required>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation">Confirm Password <span style="color:var(--red)">*</span></label>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                               placeholder="Confirm new password" required>
                    </div>

                    <div style="font-size:0.78rem; color:var(--text-light); margin-bottom:16px;">
                        Employees use their Employee ID and password to login to view their payslips.
                    </div>

                    <div style="display:flex;gap:10px;margin-top:24px;">
                        <button type="submit" class="btn btn-primary">✓ Update Password</button>
                        <a href="{{ route('users.index', ['tab' => 'employees']) }}" class="btn btn-outline">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    @else
        {{-- ── Officer: Full form with role assignments ────────────────────── --}}
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: start;">

            {{-- ── Account details form ──────────────────────────────────────────── --}}
            <div class="card">
                <div class="card-header"><h3>Account Details</h3></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('users.update', $user) }}">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name"
                                   class="{{ $errors->has('name') ? 'is-invalid' : '' }}"
                                   value="{{ old('name', $user->name) }}" readonly
                                   style="background-color: #f8f9fa; cursor: not-allowed;">
                            <div style="font-size:0.78rem; color:var(--text-light); margin-top:4px;">
                                Name is automatically synced from the linked employee record
                            </div>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address <span style="color:var(--red)">*</span></label>
                            <input type="email" id="email" name="email"
                                   class="{{ $errors->has('email') ? 'is-invalid' : '' }}"
                                   value="{{ old('email', $user->email) }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        @role('super_admin')
                        <div class="form-group">
                            <label for="password">Password <span style="font-weight:400; color:var(--text-light); font-size:0.82rem;">(optional)</span></label>
                            <input type="password" id="password" name="password"
                                   class="{{ $errors->has('password') ? 'is-invalid' : '' }}"
                                   placeholder="Leave blank to keep current password">
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label for="password_confirmation">Confirm Password</label>
                            <input type="password" id="password_confirmation" name="password_confirmation"
                                   placeholder="Leave blank to keep current password">
                        </div>
                        @endrole

                        {{-- ── Primary role ──────────────────────────────────────── --}}
                        <div class="form-group">
                            <label for="role">Primary Role <span style="color:var(--red)">*</span></label>
                            <select id="role" name="role"
                                    class="{{ $errors->has('role') ? 'is-invalid' : '' }}" required>
                                <option value="">— Select a role —</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->name }}"
                                        {{ (old('role', $user->roleAssignments->where('is_active', true)->first()?->role_name) === $role->name) ? 'selected' : '' }}>
                                        {{ ucwords(str_replace('_', ' ', $role->name)) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- ── Secondary role ────────────────────────────────────── --}}
                        @php
                            $primaryRole    = $user->roleAssignments->where('is_active', true)->first()?->role_name;
                            $secondaryRole  = $user->roleAssignments->firstWhere('is_active', false)?->role_name;
                        @endphp
                        <div class="form-group">
                            <label for="secondary_role">
                                Secondary Role
                                <span style="font-weight:400; color:var(--text-light); font-size:0.82rem;">(optional)</span>
                            </label>
                            <select id="secondary_role" name="secondary_role"
                                    class="{{ $errors->has('secondary_role') ? 'is-invalid' : '' }}">
                                <option value="">— None —</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->name }}"
                                            {{ (old('secondary_role', $secondaryRole) === $role->name) ? 'selected' : '' }}>
                                        {{ ucwords(str_replace('_', ' ', $role->name)) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('secondary_role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div style="font-size:0.78rem; color:var(--text-light); margin-top:4px;">
                                Inactive by default — activate from the role assignments panel.
                            </div>
                        </div>

                        <div style="display:flex;gap:10px;margin-top:24px;">
                            <button type="submit" class="btn btn-primary">✓ Save Changes</button>
                            <a href="{{ route('users.index', ['tab' => 'officers']) }}" class="btn btn-outline">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ── Role assignment status panel ─────────────────────────────────── --}}
            @if ($user->roleAssignments->isNotEmpty())
            <div class="card">
                <div class="card-header">
                    <h3>Role Assignments</h3>
                    <p style="font-size:0.78rem; color:var(--text-light); margin-top:2px;">
                        Toggle which role this user is currently acting in.
                        Activating a role deactivates all other users in that same role.
                    </p>
                </div>
                <div class="card-body">
                    @foreach ($user->roleAssignments as $assignment)
                    <div class="role-assignment-row {{ $assignment->is_active ? 'is-active' : '' }}">
                        <div>
                            <div class="role-assignment-name">
                                {{ ucwords(str_replace('_', ' ', $assignment->role_name)) }}
                                @if ($assignment->is_active)
                                    <span class="active-pill" style="font-size:0.65rem;">✓ Active</span>
                                @endif
                            </div>
                            <div class="role-assignment-meta">
                                {{ $assignment->is_active ? 'Currently acting in this role' : 'Assigned but not currently active' }}
                            </div>
                        </div>

                        <form method="POST" action="{{ route('users.activate-role', $user) }}"
                              onsubmit="return confirm('{{ $assignment->is_active
                                  ? 'Deactivate ' . addslashes($user->employee ? $user->employee->first_name . ' ' . ($user->employee->middle_name ? $user->employee->middle_name . '. ' : '') . $user->employee->last_name : $user->name) . ' from ' . $assignment->role_name . '?'
                                  : 'Set ' . addslashes($user->employee ? $user->employee->first_name . ' ' . ($user->employee->middle_name ? $user->employee->middle_name . '. ' : '') . $user->employee->last_name : $user->name) . ' as the active ' . $assignment->role_name . '? Other users currently active in this role will be deactivated.' }}')">
                            @csrf
                            <input type="hidden" name="role_name" value="{{ $assignment->role_name }}">
                            <button type="submit"
                                    class="btn btn-sm {{ $assignment->is_active ? 'btn-outline' : 'btn-primary' }}">
                                {{ $assignment->is_active ? '⏸ Deactivate' : '▶ Set Active' }}
                            </button>
                        </form>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

        </div>
    @endif

</div>

@endsection
