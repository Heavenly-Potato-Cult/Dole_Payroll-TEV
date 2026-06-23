@extends('layouts.app')

@section('title', 'User Management')
@section('page-title', 'User Management')

@section('styles')
<style>
/* ── User Management Table ─────────────────────────────────── */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 24px;
}

/* ── Tab Navigation ─────────────────────────────────────────── */
.tab-navigation {
    display: flex;
    gap: 4px;
    margin-bottom: 24px;
    border-bottom: 2px solid #e5e7eb;
}

.tab-btn {
    padding: 12px 24px;
    background: transparent;
    border: none;
    border-bottom: 2px solid transparent;
    font-size: 0.9rem;
    font-weight: 600;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s;
    margin-bottom: -2px;
}

.tab-btn:hover {
    color: #1e3a5f;
}

.tab-btn.active {
    color: #1e3a5f;
    border-bottom-color: #1e3a5f;
}

.tab-btn .count {
    background: #f3f4f6;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    margin-left: 8px;
    color: #6b7280;
}

.tab-btn.active .count {
    background: #1e3a5f;
    color: white;
}

.page-header-left h1 {
    margin: 0 0 4px 0;
    font-size: 1.5rem;
    font-weight: 700;
    color: #2c3e50;
}

.page-header-left p {
    margin: 0;
    font-size: 0.9rem;
    color: #7f8c8d;
}

/* ── Search and Filter Section ─────────────────────────────── */
.search-filter-section {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    align-items: flex-end;
    flex-wrap: wrap;
}

.search-filter-section .ff-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.search-filter-section .ff-group label {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #6b7280;
    line-height: 1;
    margin: 0;
}

.search-input {
    height: 38px;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 0.9rem;
    background: white;
    box-sizing: border-box;
    margin-bottom: 0 !important;
    flex: 1;
    min-width: 200px;
}

.search-input:focus {
    outline: none;
    border-color: #1e3a5f;
    box-shadow: 0 0 0 3px rgba(30, 58, 95, 0.1);
}

.role-filter {
    height: 38px;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 0.9rem;
    background: white;
    width: 180px;
    box-sizing: border-box;
    margin-bottom: 0 !important;
}

.role-filter option {
    padding: 8px 12px;
    font-size: 0.85rem;
}

/* ── Users Table ───────────────────────────────────────────── */
.users-table {
    width: 100%;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.users-table thead {
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
}

.users-table th {
    padding: 12px 16px;
    text-align: left;
    font-weight: 600;
    font-size: 0.85rem;
    color: white;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.users-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: middle;
}

.users-table tbody tr:hover {
    background: #f9fafb;
}

.users-table tbody tr:last-child td {
    border-bottom: none;
}

/* ── User Cell Styles ───────────────────────────────────────── */
.users-table .user-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.users-table .user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #1e3a5f;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.9rem;
    flex-shrink: 0;
}

.users-table .user-details {
    flex: 1;
    min-width: 0;
}

.users-table .user-name {
    font-weight: 600;
    font-size: 0.9rem;
    color: #1f2937;
    margin: 0 0 2px 0;
    display: flex;
    align-items: center;
    gap: 6px;
}

.users-table .user-email {
    font-size: 0.8rem;
    color: #6b7280;
    margin: 0;
}

.users-table .user-employee-no {
    font-size: 0.8rem;
    color: #6b7280;
    margin: 0;
}

.you-badge {
    font-size: 0.65rem;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 12px;
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

/* ── Role Badges ───────────────────────────────────────────── */
.role-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 16px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.role-super-admin { background: #e3f2fd; color: #1565c0; }
.role-payroll-officer { background: #e8f5e8; color: #2e7d32; }
.role-hrmo { background: #fff3e0; color: #ef6c00; }
.role-cashier { background: #fce4ec; color: #c2185b; }
.role-accountant { background: #f3e5f5; color: #7b1fa2; }
.role-ard { background: #e0f2f1; color: #00695c; }
.role-budget-officer { background: #e1f5fe; color: #0277bd; }
.role-chief-admin-officer { background: #f1f8e9; color: #558b2f; }
.role-employee { background: #f5f5f5; color: #616161; }
.role-none { background: #ffebee; color: #c62828; }

/* ── Status Badge ─────────────────────────────────────────── */
.status-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 16px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-active {
    background: #e8f5e8;
    color: #2e7d32;
}

/* ── Action Buttons ─────────────────────────────────────────── */
.action-buttons {
    display: flex;
    gap: 8px;
}

.action-btn {
    width: 32px;
    height: 32px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}

.action-btn:hover {
    background: #f9fafb;
    border-color: #1e3a5f;
}

.action-btn.edit:hover {
    color: #1e3a5f;
}

.action-btn.delete {
    color: #dc2626;
    border-color: #dc2626;
}

.action-btn.delete:hover {
    background: #dc2626;
    color: white;
}

/* ── Collapsible Role Guide ─────────────────────────────────── */
.role-guide-section {
    margin-top: 32px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: white;
    overflow: hidden;
}

.role-guide-header {
    padding: 16px 20px;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: background 0.2s;
}

.role-guide-header:hover {
    background: #f3f4f6;
}

.role-guide-title {
    font-weight: 600;
    font-size: 0.95rem;
    color: #1f2937;
    margin: 0;
}

.collapse-icon {
    transition: transform 0.3s;
    color: #7f8c8d;
}

.role-guide-header.collapsed .collapse-icon {
    transform: rotate(-90deg);
}

.role-guide-content {
    padding: 20px;
    max-height: 1000px;
    overflow: hidden;
    transition: max-height 0.3s ease-out, padding 0.3s ease-out;
}

.role-guide-content.collapsed {
    max-height: 0;
    padding: 0 20px;
}

.role-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
}

.role-card {
    padding: 16px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    border-top: 3px solid #1e3a5f;
    background: white;
}

.role-card-name {
    font-weight: 700;
    font-size: 0.9rem;
    color: #1f2937;
    margin-bottom: 8px;
}

.role-card-desc {
    font-size: 0.8rem;
    color: #6b7280;
    line-height: 1.5;
    margin-bottom: 12px;
}

.access-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.access-tag {
    font-size: 0.7rem;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 12px;
    background: #f9fafb;
    color: #374151;
    border: 1px solid #e5e7eb;
}

/* ── Responsive Design ─────────────────────────────────────── */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }
    
    .search-filter-section {
        flex-direction: column;
        align-items: stretch;
    }
    
    .users-table {
        font-size: 0.85rem;
    }
    
    .users-table th,
    .users-table td {
        padding: 10px 12px;
    }
    
    .role-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .user-cell {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .action-buttons {
        flex-direction: column;
        gap: 4px;
    }
}
</style>
@endsection

@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>User Management</h1>
        <p>Manage officer accounts and employee passwords</p>
    </div>
    @if ($tab === 'officers')
    <a href="{{ route('users.create') }}" class="btn btn-primary">+ Add Officer</a>
    @endif
</div>

{{-- ── Tab Navigation ─────────────────────────────────────────── --}}
<div class="tab-navigation">
    <button class="tab-btn {{ $tab === 'officers' ? 'active' : '' }}" onclick="switchTab('officers')">
        Officers <span class="count">{{ User::whereHas('roles', fn($q) => $q->where('name', '!=', 'employee'))->count() }}</span>
    </button>
    <button class="tab-btn {{ $tab === 'employees' ? 'active' : '' }}" onclick="switchTab('employees')">
        Employees <span class="count">{{ User::whereHas('roles', fn($q) => $q->where('name', 'employee'))->count() }}</span>
    </button>
</div>

{{-- ── Search and Filter Section ─────────────────────────────── --}}
<div class="search-filter-section">
    <div class="ff-group">
        <label for="userSearch">SEARCH</label>
        <input type="text" class="search-input" placeholder="Search by name or email..." id="userSearch">
    </div>
    <div class="ff-group">
        <label for="roleFilter">ROLE</label>
        <select class="role-filter" id="roleFilter">
            <option value="">All Roles</option>
            <option value="super_admin">Super Admin</option>
            <option value="payroll_officer">Payroll Officer</option>
            <option value="hrmo">HRMO</option>
            <option value="cashier">Cashier</option>
            <option value="accountant">Accountant</option>
            <option value="ard">ARD</option>
            <option value="budget_officer">Budget Officer</option>
            <option value="chief_admin_officer">Chief Admin Officer</option>
            <option value="employee">Employee</option>
            <option value="none">No Role</option>
        </select>
    </div>
</div>

{{-- ── Users Table ───────────────────────────────────────────── --}}
<table class="users-table" id="usersTable">
    <thead>
        <tr>
            <th style="width: 35%;">User</th>
            <th style="width: 25%;">Email</th>
            <th style="width: 20%;">Role</th>
            <th style="width: 10%;">Status</th>
            <th style="width: 10%;">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($users as $user)
        <tr data-name="@if ($user->employee) {{ $user->employee->first_name }} {{ $user->employee->last_name }} @else {{ $user->name }} @endif" data-email="{{ $user->email }}" data-role="@if ($user->roles->isNotEmpty()) {{ $user->roles->first()->name }} @else none @endif">
            <td>
                <div class="user-cell">
                    <div class="user-avatar">
                        @php
                            if ($user->employee) {
                                $fullName = $user->employee->first_name . ' ' . ($user->employee->middle_name ? $user->employee->middle_name . '. ' : '') . $user->employee->last_name;
                            } else {
                                $fullName = $user->name;
                            }
                            $parts = explode(' ', trim($fullName));
                            echo strtoupper(substr($parts[0], 0, 1)) . strtoupper(substr($parts[1] ?? '', 0, 1));
                        @endphp
                    </div>
                    <div class="user-details">
                        <div class="user-name">
                            @if ($user->employee)
                                {{ $user->employee->first_name }} {{ $user->employee->middle_name ? $user->employee->middle_name . '. ' : '' }}{{ $user->employee->last_name }}
                            @else
                                {{ $user->name }}
                            @endif
                            @if ($user->id === auth()->id())
                                <span class="you-badge">You</span>
                            @endif
                        </div>
                        <div class="user-email">{{ $user->email }}</div>
                        @if ($user->employee && $user->employee->employee_no)
                            <div class="user-employee-no">{{ $user->employee->employee_no }}</div>
                        @endif
                    </div>
                </div>
            </td>
            <td>{{ $user->email }}</td>
            <td>
                @foreach ($user->roles as $role)
                    <span class="role-badge role-{{ str_replace('_', '-', $role->name) }}">
                        {{ ucwords(str_replace('_', ' ', $role->name)) }}
                    </span>
                @endforeach
                @if ($user->roles->isEmpty())
                    <span class="role-badge role-none">No Role</span>
                @endif
            </td>
            <td>
                <span class="status-badge status-active">Active</span>
            </td>
            <td>
                <div class="action-buttons">
                    <a href="{{ route('users.edit', $user) }}" class="action-btn edit" title="Edit">
                        ✎
                    </a>
                    @if ($user->id !== auth()->id())
                    <form method="POST" action="{{ route('users.destroy', $user) }}" id="deleteForm-{{ $user->id }}" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="action-btn delete" title="Delete" 
                                onclick="confirmDeleteUser({{ $user->id }}, '{{ addslashes($user->name) }}')">
                            ✕
                        </button>
                    </form>
                    @endif
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="5" style="text-align: center; padding: 40px; color: #7f8c8d;">
                @if ($tab === 'employees')
                    No employee accounts yet. Employees will be auto-created when they first log in.
                @else
                    No officers yet. <a href="{{ route('users.create') }}">Add the first officer →</a>
                @endif
            </td>
        </tr>
        @endforelse
    </tbody>
</table>

{{-- ── Collapsible Role Permissions Guide ─────────────────────── --}}
<div class="role-guide-section">
    <div class="role-guide-header" id="roleGuideHeader" onclick="toggleRoleGuide()">
        <h3 class="role-guide-title">Role Permissions Guide</h3>
        <span class="collapse-icon">▼</span>
    </div>
    <div class="role-guide-content" id="roleGuideContent">
        <div class="role-grid">

            <div class="role-card">
                <div class="role-card-name">Super Admin</div>
                <div class="role-card-desc">Manages system users and accounts. Can view all modules across the system. No payroll or TEV action rights.</div>
                <div class="access-tags">
                    <span class="access-tag">User Management</span>
                    <span class="access-tag">View All Modules</span>
                </div>
            </div>

            <div class="role-card">
                <div class="role-card-name">Payroll Officer</div>
                <div class="role-card-desc">Manages payroll processing and employee records. No access to TEV.</div>
                <div class="access-tags">
                    <span class="access-tag">Employees</span>
                    <span class="access-tag">Payroll</span>
                    <span class="access-tag">Special Payroll</span>
                    <span class="access-tag">Payroll Reports</span>
                </div>
            </div>

            <div class="role-card">
                <div class="role-card-name">HRMO</div>
                <div class="role-card-desc">Manages employee records, views payroll, creates Office Orders, and files TEV requests on behalf of traveling employees.</div>
                <div class="access-tags">
                    <span class="access-tag">Employees</span>
                    <span class="access-tag">View Payroll</span>
                    <span class="access-tag">Office Orders</span>
                    <span class="access-tag">TEV (create & submit)</span>
                </div>
            </div>

            <div class="role-card">
                <div class="role-card-name">Accountant</div>
                <div class="role-card-desc">First approver in the TEV workflow after submission. Certifies fund availability, generates remittance reports, and views payroll entries.</div>
                <div class="access-tags">
                    <span class="access-tag">View Payroll</span>
                    <span class="access-tag">Payroll Reports</span>
                    <span class="access-tag">TEV (1st approval)</span>
                    <span class="access-tag">TEV Reports</span>
                </div>
            </div>

            <div class="role-card">
                <div class="role-card-name">Budget Officer</div>
                <div class="role-card-desc">Views TEV spending and Office Orders for budget monitoring purposes. No approval actions in any workflow.</div>
                <div class="access-tags">
                    <span class="access-tag">View Office Orders</span>
                    <span class="access-tag">View TEV</span>
                    <span class="access-tag">TEV Reports</span>
                </div>
            </div>

            <div class="role-card">
                <div class="role-card-name">Chief Admin Officer</div>
                <div class="role-card-desc">Division-level approver for both payroll and TEV.</div>
                <div class="access-tags">
                    <span class="access-tag">View Payroll</span>
                    <span class="access-tag">Approve Payroll</span>
                    <span class="access-tag">TEV (division approval)</span>
                </div>
            </div>

            <div class="role-card">
                <div class="role-card-name">ARD</div>
                <div class="role-card-desc">Final approver for both payroll and TEV. Can generate payroll release.</div>
                <div class="access-tags">
                    <span class="access-tag">Final Payroll Approval</span>
                    <span class="access-tag">Payroll Reports</span>
                    <span class="access-tag">Final TEV Approval</span>
                    <span class="access-tag">TEV Reports</span>
                </div>
            </div>

            <div class="role-card">
                <div class="role-card-name">Cashier</div>
                <div class="role-card-desc">Marks payroll and TEV as released or paid. Final step in both workflows.</div>
                <div class="access-tags">
                    <span class="access-tag">Release Payroll</span>
                    <span class="access-tag">TEV (release/reimburse)</span>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
// Switch between Officers and Employees tabs
function switchTab(tab) {
    const url = new URL(window.location);
    url.searchParams.set('tab', tab);
    window.location.href = url.toString();
}

// Toggle role guide collapse/expand
function toggleRoleGuide() {
    const header = document.getElementById('roleGuideHeader');
    const content = document.getElementById('roleGuideContent');
    
    header.classList.toggle('collapsed');
    content.classList.toggle('collapsed');
}

// Confirm delete user with SweetAlert
function confirmDeleteUser(userId, userName) {
    const formId = 'deleteForm-' + userId;
    
    Swal.fire({
        title: 'Remove User?',
        html: `<div style="text-align:center;">
            <p style="margin-bottom:8px;">Are you sure you want to remove <strong>${userName}</strong>?</p>
            <p style="color:#dc2626;font-size:0.9rem;">This action cannot be undone.</p>
        </div>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, remove',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById(formId).submit();
        }
    });
}

// Search and filter functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('userSearch');
    const roleFilter = document.getElementById('roleFilter');
    const tableRows = document.querySelectorAll('#usersTable tbody tr');
    
    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedRole = roleFilter.value;
        
        tableRows.forEach(row => {
            const name = row.dataset.name ? row.dataset.name.toLowerCase() : '';
            const email = row.dataset.email ? row.dataset.email.toLowerCase() : '';
            const role = row.dataset.role || '';
            
            const matchesSearch = name.includes(searchTerm) || email.includes(searchTerm);
            const matchesRole = selectedRole === '' || role === selectedRole;
            
            if (matchesSearch && matchesRole) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    searchInput.addEventListener('input', filterTable);
    roleFilter.addEventListener('change', filterTable);
});
</script>

@endsection
