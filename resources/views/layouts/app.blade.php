<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — DOLE RO9 Payroll</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
    /* ── Topbar User Pill Container ─────────────────────────────────── */
    .topbar-user-pill {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #f5f6f8;
        border: 0.5px solid rgba(0,0,0,0.1);
        border-radius: 999px;
        padding: 5px 6px 5px 10px;
    }

    /* ── User Info Section ─────────────────────────────────────────── */
    .user-info {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        text-align: right;
        margin-right: 4px;
    }

    .user-name {
        font-size: 12px;
        font-weight: 500;
        color: #2c3e50;
        line-height: 1.2;
    }

    .user-role {
        font-size: 10px;
        color: #7f8c8d;
        line-height: 1;
        margin-top: 1px;
    }

    /* ── User Avatar Circle ─────────────────────────────────────────── */
    .user-avatar {
        width: 32px;
        height: 32px;
        background: #0F1B4C;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 13px;
        font-weight: 500;
        flex-shrink: 0;
    }

    /* ── Vertical Divider ─────────────────────────────────────────── */
    .user-divider {
        width: 0.5px;
        height: 20px;
        background: #dfe6e9;
        flex-shrink: 0;
    }

    /* ── Sign Out Button ─────────────────────────────────────────── */
    .sign-out-btn {
        display: flex;
        align-items: center;
        gap: 4px;
        background: #c0392b;
        color: white;
        border: none;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.2s;
        white-space: nowrap;
    }

    .sign-out-btn:hover {
        background: #a93226;
    }

    .sign-out-icon {
        font-size: 11px;
        display: flex;
        align-items: center;
    }

    /* ── Switch Button (for super admin switching between modules) ───── */
    .btn-switch {
        display: block;
        background: #0F1B4C;
        color: white;
        border: none;
        border-radius: 6px;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        white-space: nowrap;
        text-align: center;
        width: 100%;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .btn-switch:hover {
        background: #1a2d6d;
        color: white !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    .btn-switch:active {
        transform: translateY(0);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    </style>
    @yield('styles')
</head>
<body>

<div class="app-shell">

    {{-- Mobile overlay (tap to close sidebar) --}}
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    {{-- ═══ SIDEBAR ═══ --}}
    <aside class="sidebar" id="sidebar">

        <div class="sidebar-brand">
            <div class="sidebar-logo-wrap">
                <img src="{{ asset('assets/img/dole_logo.png') }}" alt="DOLE" class="sidebar-logo">
            </div>
            <div class="sidebar-title">
                <strong>DOLE RO9 Payroll</strong>
                <span>Zamboanga Peninsula</span>
            </div>
        </div>

        <nav class="sidebar-nav">

            <a href="{{ route('payroll.dashboard') }}"
               class="nav-item {{ request()->routeIs('payroll.dashboard') ? 'active' : '' }}">
                <span class="nav-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="white" style="display: block;">
                        <path d="M21,3H3c-1.654,0-3,1.346-3,3v15H24V6c0-1.654-1.346-3-3-3Zm-13,14.5c-2.761,0-5-2.239-5-5,0-2.419,1.718-4.436,4-4.899v5.313l3.754,3.754c-.79,.523-1.736,.832-2.754,.832Zm4.168-2.246l-3.168-3.168V7.601c2.282,.463,4,2.48,4,4.899,0,1.019-.308,1.964-.832,2.754Zm8.832,1.746h-5v-2h5v2Zm0-4h-5v-2h5v2Zm0-4h-5v-2h5v2Z"/>
                    </svg>
                </span> Dashboard
            </a>

            {{-- ── Employees ─────────────────────────────────────────── --}}
            @role('payroll_officer|hrmo|accountant|chief_admin_officer|super_admin')
            <div class="nav-section-label">Employees</div>
            <a href="{{ route('employees.index') }}"
               class="nav-item {{ request()->routeIs('employees.*') ? 'active' : '' }}">
                <span class="nav-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="white" style="display: block;">
                        <path d="M16.043,14H7.957A4.963,4.963,0,0,0,3,18.957V24H21V18.957A4.963,4.963,0,0,0,16.043,14Z"/><circle cx="12" cy="6" r="6"/>
                    </svg>
                </span> Employees
            </a>
            @role('payroll_officer|hrmo|super_admin')
            <a href="{{ route('divisions.index') }}"
               class="nav-item {{ request()->routeIs('divisions.*') ? 'active' : '' }}">
                <span class="nav-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="white" style="display: block;">
                        <path d="m16.5 15.5c0-1.379 1.121-2.5 2.5-2.5s2.5 1.121 2.5 2.5-1.121 2.5-2.5 2.5-2.5-1.121-2.5-2.5zm7.5 6.5v2h-10v-2c0-1.654 1.346-3 3-3h4c1.654 0 3 1.346 3 3zm-12 2h-12v-21c0-1.654 1.346-3 3-3h10c1.654 0 3 1.346 3 3v9.17c-.914.824-1.5 2.005-1.5 3.33 0 .7.174 1.354.46 1.945-1.741.783-2.96 2.526-2.96 4.555zm-3-17h3v-2h-3zm0 4h3v-2h-3zm0 4h3v-2h-3zm0 4h3v-2h-3zm-2-2h-3v2h3zm0-4h-3v2h3zm0-4h-3v2h3zm0-4h-3v2h3z"/>
                    </svg>
                </span> Divisions
            </a>
            @endrole
            @endrole

            {{-- ── Payroll section ────────────────────────────────────── --}}
            {{--
                TWO separate blocks so the @else pattern is not needed:
                  1. Staff block  — payroll_officer, accountant, etc.
                  2. Employee block — anyone who is NOT in those roles
                     (HRIS-redirected employees who have no staff role).
                The "employee" role itself is also checked explicitly so
                that Super Admins who assigned that role can see it too.
            --}}

            {{-- Staff / officer payroll menu --}}
            @role('payroll_officer|hrmo|accountant|ard|cashier|chief_admin_officer|super_admin')
            <div class="nav-section-label">Payroll</div>
            <a href="{{ route('payroll.index') }}"
               class="nav-item {{ request()->routeIs('payroll.index') ? 'active' : '' }}">
                <span class="nav-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="white" style="display: block;">
                        <path d="m24,5v3H0v-3c0-1.654,1.346-3,3-3h3V0h2v2h8V0h2v2h3c1.654,0,3,1.346,3,3Zm-5,9h2c.552,0,1,.448,1,1h2c0-1.654-1.346-3-3-3v-2h-2v2c-1.654,0-3,1.346-3,3,0,1.359.974,2.51,2.315,2.733l3.04.506c.374.062.645.382.645.761,0,.552-.448,1-1,1h-2c-.552,0-1-.448-1-1h-2c0,1.654,1.346,3,3,3v2h2v-2c1.654,0,3-1.346,3-3,0-1.359-.974-2.51-2.315-2.733l-3.04-.506c-.374-.062-.645-.382-.645-.761,0-.552.448-1,1-1Zm-5,5v-4c0-2.045,1.237-3.802,3-4.576v-.424H0v14h17v-.424c-1.763-.774-3-2.531-3-4.576Z"/>
                    </svg>
                </span> Regular Payroll
            </a>

            <div class="nav-section-label" style="padding-left:12px; font-size:0.65rem;">Special Payroll</div>
            <a href="{{ route('special-payroll.newly-hired.index') }}"
               class="nav-item nav-indented {{ request()->routeIs('special-payroll.newly-hired.*') ? 'active' : '' }}">
                <span class="nav-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="white" style="display: block;">
                        <path d="m21,3H3c-1.654,0-3,1.346-3,3v15h24V6c0-1.654-1.346-3-3-3Zm-13.5,13h-1.6l-1.3-4.054v4.054h-1.6v-8h1.6v.009l1.3,4.054v-4.063h1.6v8Zm5.5-6.4h-1.9v1.801h1.9v1.6h-1.9v1.4h1.9v1.6h-3.5v-8h3.5v1.6Zm7.798,5.4c-.04.705-.439,1-.917,1-.318,0-.613-.242-.781-.64l-1.08-2.56-1.08,2.56c-.168.398-.463.64-.781.64-.479,0-.878-.295-.917-1l-.471-7h1.604v.006s.265,3.949.265,3.949l1.38-3.27,1.38,3.269.265-3.939v-.016s1.605,0,1.605,0l-.471,7Z"/>
                    </svg>
                </span> Newly Hired
            </a>
            <a href="{{ route('special-payroll.differential.index') }}"
               class="nav-item nav-indented {{ request()->routeIs('special-payroll.differential.*') ? 'active' : '' }}">
                <span class="nav-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="white" style="display: block;">
                        <path d="M18,16c0,.378,.271,.698,.644,.76l3.042,.507c1.341,.223,2.315,1.373,2.315,2.733,0,1.654-1.346,3-3,3v1h-2v-1c-1.654,0-3-1.346-3-3h2c0,.551,.449,1,1,1h2c.551,0,1-.449,1-1,0-.378-.271-.698-.644-.76l-3.042-.507c-1.341-.223-2.315-1.373-2.315-2.733,0-1.654,1.346-3,3-3v-1h2v1c1.654,0,3,1.346,3,3h-2c0-.551-.449-1-1-1h-2c-.551,0-1,.449-1,1ZM15,0h-1V10h10v-1C24,4.038,19.962,0,15,0Zm1.031,12h-4.031V2h-1C4.935,2,0,6.935,0,13s4.935,11,11,11c1.476,0,2.882-.297,4.169-.826-.719-.866-1.169-1.963-1.169-3.174v-4c0-1.641,.806-3.088,2.031-4Z"/>
                    </svg>
                </span> Salary Differential
            </a>
            <a href="{{ route('special-payroll.nosi-nosa.index') }}"
               class="nav-item nav-indented {{ request()->routeIs('special-payroll.nosi-nosa.*') ? 'active' : '' }}">
                <span class="nav-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="white" style="display: block;">
                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                    </svg>
                </span> NOSI / NOSA
            </a>
            @endrole

            {{-- Employee self-service payroll menu --}}
            {{--
                Visible when the user is NOT a staff/officer role.
                This covers two cases:
                  (a) HRIS-redirected user with no role assigned → treated as "employee"
                  (b) A user explicitly assigned the "employee" role by Super Admin
                We use @unlessrole so it only shows for pure employees.
            --}}
            @unlessrole('payroll_officer|hrmo|accountant|ard|cashier|chief_admin_officer|super_admin')
            <div class="nav-section-label">Payroll</div>
            <a href="{{ route('my-payslip') }}"
               class="nav-item {{ request()->routeIs('my-payslip') ? 'active' : '' }}">
                <span class="nav-icon">💰</span> My Payslip
            </a>
            @endunlessrole

            {{-- ── Deductions & Loans CMS ─────────────────────────────── --}}
            @role('payroll_officer|super_admin')
            <div class="nav-section-label">Configuration</div>
            <a href="{{ route('deduction-types.index') }}"
               class="nav-item {{ request()->routeIs('deduction-types.*') ? 'active' : '' }}">
                <span class="nav-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="white" style="display: block;">
                        <path d="M13.5,12.256c0,.827-.673,1.5-1.5,1.5s-1.5-.673-1.5-1.5,.673-1.5,1.5-1.5,1.5,.673,1.5,1.5Zm8.607-4.929l-.175,.625c-1.414,5.075-3.878,6.614-6.26,8.103-2.051,1.281-3.988,2.491-5.471,6.142l-.513,1.263L1.439,16.637l.276-.68c1.727-4.25,4.137-5.756,6.263-7.085,2.247-1.404,4.188-2.616,5.394-6.944l.414-1.484,8.321,6.883Zm-6.607,4.929c0-1.93-1.57-3.5-3.5-3.5s-3.5,1.57-3.5,3.5,1.57,3.5,3.5,3.5,3.5-1.57,3.5-3.5ZM1.359,3.885c-.219,.32-.359,.698-.359,1.115,0,.872,.564,1.607,1.344,1.88-.217,.32-.344,.705-.344,1.12,0,.86,.549,1.589,1.313,1.871,1.224-1.205,2.483-1.993,3.606-2.695,1.386-.866,2.464-1.545,3.324-2.902-.586-1.071-.896-2.023-.946-2.185-.392-1.27-1.49-2.089-2.797-2.089H2C.897,0,0,.897,0,2c0,.878,.572,1.617,1.359,1.885ZM21.911,14.703c-.124-.038-.49-.16-.972-.361-1.362,1.626-2.873,2.576-4.207,3.409-1.019,.637-1.922,1.211-2.732,2.057v.191c0,1.103,.897,2,2,2,.414,0,.8-.127,1.12-.344,.273,.78,1.008,1.344,1.88,1.344,.417,0,.795-.14,1.115-.359,.269,.788,1.008,1.359,1.885,1.359,1.103,0,2-.897,2-2v-4.5c0-1.307-.819-2.405-2.089-2.797Z"/>
                    </svg>
                </span> Deduction Types
            </a>
            @endrole

            {{-- ── Reports ─────────────────────────────────────────────── --}}
            @role('payroll_officer|super_admin')
            <div class="nav-section-label">Reports</div>
            <a href="{{ route('reports.index') }}"
               class="nav-item {{ request()->routeIs('reports.index') || request()->routeIs('reports.*') ? 'active' : '' }}">
                <span class="nav-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="white" style="display: block;">
                        <path d="M15,0H3C1.346,0,0,1.346,0,3V24H12.627l-3.74-3.864,4.312-4.172,3.08,3.184,1.72-1.696V3c0-1.654-1.346-3-3-3Zm-7,17H4v-2h4v2Zm6-5H4v-2H14v2Zm0-5H4v-2H14v2Zm2.289,17c-.555,0-1.076-.216-1.468-.609l-3.105-3.209,1.438-1.391,3.094,3.198,6.17-6.085,1.414,1.414-6.074,6.074c-.392,.392-.913,.608-1.468,.608Z"/>
                    </svg>
                </span> Reports
            </a>
            @endrole

            {{-- ── Administration ─────────────────────────────────────── --}}
            @role('payroll_officer|super_admin')
            <div class="nav-section-label">Administration</div>

            @role('super_admin')
            <a href="{{ route('users.index') }}"
               class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <span class="nav-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="white" style="display: block;">
                        <path d="m21,12c0-.537-.051-1.082-.153-1.625l3.052-1.755-2.99-5.202-3.051,1.754c-.841-.721-1.81-1.28-2.857-1.649V0h-6v3.522c-1.047.37-2.016.929-2.857,1.649l-3.05-1.754L.102,8.62l3.052,1.755c-.102.544-.153,1.088-.153,1.625s.051,1.082.153,1.625L.102,15.38l2.991,5.202,3.05-1.754c.841.721,1.81,1.28,2.857,1.649v3.522h6v-3.522c1.047-.37,2.016-.929,2.857-1.649l3.051,1.754,2.99-5.202-3.052-1.755c.102-.544.153-1.088.153-1.625Zm-9.505-4.949c.169-.017.332-.051.505-.051s.336.034.504.051c1.139.233,1.995,1.241,1.995,2.449,0,1.381-1.119,2.5-2.5,2.5s-2.5-1.119-2.5-2.5c0-1.208.856-2.215,1.995-2.449Zm2.505,9.525s-.5.424-2,.424-2-.424-2-.424c-.8-.351-1.481-.912-1.997-1.604.015-1.09.904-1.972,1.997-1.972h4c1.094,0,1.982.882,1.997,1.972-.516.692-1.197,1.253-1.997,1.604Z"/>
                    </svg>
                </span> User Management
            </a>
            @endrole

            <a href="{{ route('signatories.index') }}"
               class="nav-item {{ request()->routeIs('signatories.*') ? 'active' : '' }}">
                <span class="nav-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="white" style="display: block;">
                        <path d="M24,23c0,.55-.45,1-1,1-1.54,0-2.29-1.12-2.83-1.95-.5-.75-.75-1.05-1.17-1.05-.51,0-.9,.44-1.51,1.15-.7,.83-1.57,1.85-3.03,1.85s-2.32-1.03-3-1.87c-.58-.7-.96-1.13-1.46-1.13-.39,0-.63,.25-1.16,.91-.72,.88-1.71,2.09-3.84,2.09-2.76,0-5-2.24-5-5s2.24-5,5-5c.55,0,1,.45,1,1s-.45,1-1,1c-1.65,0-3,1.35-3,3s1.35,3,3,3c1.18,0,1.67-.6,2.29-1.36,.6-.73,1.34-1.64,2.71-1.64,1.47,0,2.32,1.03,3,1.87,.58,.7,.96,1.13,1.46,1.13s.9-.44,1.51-1.15c.7-.83,1.57-1.85,3.03-1.85s2.29,1.12,2.83,1.95c.5,.75,.75,1.05,1.17,1.05,.55,0,1,.45,1,1Zm-15.01-7h.94c1.06,0,2.08-.42,2.83-1.17l7.72-7.72-3.59-3.59-7.72,7.72c-.75,.75-1.17,1.77-1.17,2.83v.94c0,.55,.44,.99,.99,.99ZM23.26,4.33c.48-.48,.74-1.12,.74-1.8s-.26-1.32-.74-1.79c-.99-.99-2.6-.99-3.59,0l-1.36,1.36,3.59,3.59,1.36-1.36Z"/>
                    </svg>
                </span> Signatories
            </a>

            @endrole

        </nav>

        {{-- ═══ SIDEBAR FOOTER ═══ --}}
        @role('super_admin')
        <div class="sidebar-footer">
            <a href="{{ route('tev.dashboard') }}" class="btn-switch" title="Go to TEV">
                Go to TEV
            </a>
        </div>
        @endrole

    </aside>

    {{-- ═══ MAIN AREA ═══ --}}
    <div class="main-content">

        <header class="topbar">
            <div class="topbar-left">
                <button class="burger-btn" onclick="openSidebar()" aria-label="Open menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <span class="topbar-title">@yield('page-title', 'Dashboard')</span>
            </div>
            <div class="topbar-right">
                <div class="topbar-user-pill">
                    <div class="user-info">
                        {{--
                            For HRIS-redirected employees, show the name from the HRIS session
                            (stored when the JWT was validated) rather than the local User record,
                            which may not have a meaningful display name.
                        --}}
                        <div class="user-name">
                            {{ session('hris_employee_name') ?? auth()->user()->name }}
                        </div>
                        <div class="user-role">
                            {{--
                                HRIS-redirected employees have no assigned role record,
                                so fall back to "Employee" for display.
                            --}}
                            @php
                                $roleName = auth()->user()->getRoleNames()->first();
                            @endphp
                            {{ $roleName ? str_replace('_', ' ', ucwords($roleName)) : 'Employee' }}
                        </div>
                    </div>
                    <div class="user-avatar">
                        {{ strtoupper(substr(session('hris_employee_name') ?? auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="user-divider"></div>
                    @role('super_admin')
                    <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                        @csrf
                        <button type="submit" class="sign-out-btn">
                            <span class="sign-out-icon">⏻</span>
                            Sign Out
                        </button>
                    </form>
                    @else
                    {{--
                        For all non-super-admin users (including HRIS employees),
                        logging out also redirects back to the HRIS portal.
                    --}}
                    <form method="POST" action="{{ route('logout') }}"
                          onsubmit="setTimeout(() => { window.location.href = 'http://localhost:3001'; }, 100);"
                          style="display: inline;">
                        @csrf
                        <button type="submit" class="sign-out-btn">
                            <span class="sign-out-icon">←</span>
                            Back to HRIS
                        </button>
                    </form>
                    @endrole
                </div>
            </div>
        </header>

        <main class="page-body">

            @if (session('success'))
                <div class="alert alert-success">✓ {{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-error">⚠ {{ session('error') }}</div>
            @endif
            @if (session('warning'))
                <div class="alert alert-warning">⚠ {{ session('warning') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-error">
                    <div>
                        <strong>Please fix the following errors:</strong>
                        <ul style="margin-top:6px; padding-left:16px;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @yield('content')

        </main>
    </div>

</div>

<script src="{{ asset('js/app.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function openSidebar() {
    document.getElementById('sidebar').classList.add('open');
    document.getElementById('sidebarOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('show');
    document.body.style.overflow = '';
}
document.querySelectorAll('.nav-item').forEach(link => {
    link.addEventListener('click', closeSidebar);
});
</script>
@yield('scripts')

</body>
</html>
