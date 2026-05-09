<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — DOLE RO9 Payroll</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <style>
    /* ── CSS Variables ─────────────────────────────────────────────── */
    :root {
        --navy:        #0F1B4C;
        --navy-mid:    #1A2B6B;
        --navy-light:  #E8EAF6;
        --navy-surface:#162040;
        --red:         #B71C1C;
        --red-light:   #FFEBEE;
        --gold:        #F9A825;
        --gold-dark:   #C87800;
        --gold-light:  #FFF8E1;
        --white:       #FFFFFF;
        --bg:          #F2F4FB;
        --surface:     #FFFFFF;
        --border:      #DDE1EE;
        --text:        #1A1A2E;
        --text-mid:    #4A4A6A;
        --text-light:  #9090AA;
        --success:     #1B5E20;
        --success-bg:  #E8F5E9;
        --warning:     #E65100;
        --warning-bg:  #FFF3E0;
        --radius:      8px;
        --shadow:      0 2px 8px rgba(15,27,76,0.09);
        --font:        'Source Sans 3', 'Segoe UI', system-ui, sans-serif;
    }

    /* ── CSS Reset ─────────────────────────────────────────────────── */
    *, *::before, *::after {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    html {
        font-size: 15px;
    }

    body {
        font-family: var(--font);
        background: var(--bg);
        color: var(--text);
        line-height: 1.6;
        -webkit-font-smoothing: antialiased;
        width: 100%;
        min-height: 100vh;
    }

    /* ── Top Navigation Bar ─────────────────────────────────────────── */
    .top-nav {
        position: sticky;
        top: 0;
        z-index: 1000;
        background: var(--navy, #0F1B4C);
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        padding: 0;
    }

    .top-nav-inner {
        max-width: 1400px;
        margin: 0 auto;
        padding: 12px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
    }

    /* ── Logo Section ─────────────────────────────────────────────── */
    .top-nav-brand {
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
    }

    .top-nav-brand-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: white;
        line-height: 1.2;
        letter-spacing: 0.5px;
    }

    .top-nav-brand-subtitle {
        font-size: 0.75rem;
        color: rgba(255,255,255,0.75);
        font-weight: 500;
        margin-top: 2px;
    }

    /* ── Navigation Links ──────────────────────────────────────────── */
    .top-nav-links {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .top-nav-link {
        padding: 8px 16px;
        color: rgba(255,255,255,0.85);
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 500;
        border-radius: 6px;
        transition: all 0.2s;
        position: relative;
    }

    .top-nav-link:hover {
        color: white;
        background: rgba(255,255,255,0.1);
    }

    .top-nav-link.active {
        color: white;
        background: rgba(255,255,255,0.15);
        font-weight: 600;
    }

    .top-nav-link.active::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 50%;
        transform: translateX(-50%);
        width: 20px;
        height: 2px;
        background: white;
        border-radius: 1px;
    }

    /* ── User Section ─────────────────────────────────────────────── */
    .top-nav-user {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-shrink: 0;
    }

    .top-nav-user-info {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        text-align: right;
    }

    .top-nav-user-name {
        font-size: 0.85rem;
        font-weight: 600;
        color: white;
        line-height: 1.2;
    }

    .top-nav-user-role {
        font-size: 0.7rem;
        color: rgba(255,255,255,0.7);
        font-weight: 500;
        margin-top: 2px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .top-nav-avatar {
        width: 36px;
        height: 36px;
        background: rgba(255,255,255,0.15);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 0.9rem;
        font-weight: 600;
        flex-shrink: 0;
        border: 2px solid rgba(255,255,255,0.2);
    }

    .top-nav-back-btn {
        display: flex;
        align-items: center;
        gap: 6px;
        background: rgba(255,255,255,0.1);
        color: white;
        border: 1px solid rgba(255,255,255,0.2);
        border-radius: 6px;
        padding: 8px 14px;
        font-size: 0.8rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        white-space: nowrap;
    }

    .top-nav-back-btn:hover {
        background: rgba(255,255,255,0.2);
        border-color: rgba(255,255,255,0.3);
    }

    /* ── Main Content Area ─────────────────────────────────────────── */
    .top-nav-wrapper {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    .top-nav-content {
        flex-grow: 1;
        max-width: 1400px;
        width: 100%;
        margin: 0 auto;
        padding: 32px 24px;
        box-sizing: border-box;
    }

    /* ── Page Header ─────────────────────────────────────────────── */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 24px;
        gap: 16px;
    }

    .page-header-left h1 {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--navy, #0F1B4C);
        margin: 0 0 8px 0;
        line-height: 1.2;
    }

    .page-header-left p {
        font-size: 0.95rem;
        color: var(--text-mid, #6b7280);
        margin: 0;
    }

    /* ── Card ─────────────────────────────────────────────────────── */
    .card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-bottom: 16px;
    }

    .card-header {
        padding: 20px 24px;
        border-bottom: 1px solid var(--border, #e5e7eb);
    }

    .card-header h3 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--navy, #0F1B4C);
    }

    .card-body {
        padding: 24px;
    }

    /* ── Table ────────────────────────────────────────────────────── */
    .table {
        width: 100%;
        border-collapse: collapse;
    }

    .table th,
    .table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid var(--border, #e5e7eb);
    }

    .table th {
        background: var(--surface, #f9fafb);
        font-weight: 600;
        color: var(--text-mid, #6b7280);
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .table tbody tr:hover {
        background: var(--surface, #f9fafb);
    }

    /* ── Badge ────────────────────────────────────────────────────── */
    .badge {
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .badge-released {
        background: #d4edda;
        color: #155724;
    }

    .badge-locked {
        background: #cce5ff;
        color: #004085;
    }

    /* ── Buttons ──────────────────────────────────────────────────── */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 10px 20px;
        font-size: 0.9rem;
        font-weight: 500;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
    }

    .btn-primary {
        background: var(--navy, #0F1B4C);
        color: white;
    }

    .btn-primary:hover {
        background: #1a2d6d;
    }

    .btn-sm {
        padding: 6px 12px;
        font-size: 0.8rem;
    }

    /* ── Responsive ───────────────────────────────────────────────── */
    @media (max-width: 768px) {
        .top-nav-inner {
            padding: 12px 16px;
            flex-wrap: wrap;
        }

        .top-nav-brand {
            width: 100%;
            margin-bottom: 8px;
        }

        .top-nav-links {
            order: 3;
            width: 100%;
            justify-content: center;
            margin-top: 8px;
        }

        .top-nav-user {
            gap: 8px;
        }

        .top-nav-user-info {
            display: none;
        }

        .top-nav-back-btn span {
            display: none;
        }

        .top-nav-back-btn::before {
            content: 'Logout';
        }

        .page-header {
            flex-direction: column;
        }

        .page-body {
            padding: 20px 16px;
        }
    }
    </style>
    @yield('styles')
</head>
<body>

<div class="top-nav-wrapper">

    {{-- ═══ TOP NAVIGATION ═══ --}}
    <nav class="top-nav">
        <div class="top-nav-inner">

            {{-- Logo Section --}}
            <div class="top-nav-brand">
                <div class="top-nav-brand-title">DOLE RO9 Payroll</div>
                <div class="top-nav-brand-subtitle">Zamboanga Peninsula</div>
            </div>

            {{-- User Section --}}
            <div class="top-nav-user">
                <div class="top-nav-user-info">
                    <div class="top-nav-user-name">
                        {{ session('hris_employee_name') ?? auth()->user()->name }}
                    </div>
                    <div class="top-nav-user-role">
                        @php
                            $roleName = auth()->user()->getRoleNames()->first();
                        @endphp
                        {{ $roleName ? str_replace('_', ' ', ucwords($roleName)) : 'Employee' }}
                    </div>
                </div>
                <div class="top-nav-avatar">
                    {{ strtoupper(substr(session('hris_employee_name') ?? auth()->user()->name, 0, 1)) }}
                </div>
                <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                    @csrf
                    <button type="submit" class="top-nav-back-btn">
                        <span>Logout</span>
                    </button>
                </form>
            </div>

        </div>
    </nav>

    {{-- ═══ MAIN CONTENT ═══ --}}
    <main class="top-nav-content">

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

<script src="{{ asset('js/app.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@yield('scripts')

</body>
</html>
