@extends('layouts.auth')

@section('title', 'Sign In')

@push('styles')
<style>
.login-form {
    animation: fadeIn 0.3s ease-in-out;
}

.login-hint {
    margin-top: 12px;
    padding: 8px 12px;
    background: #f0f7ff;
    border-radius: 6px;
    text-align: center;
}

.login-hint small {
    color: #6b7280;
    font-size: 12px;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
@endpush

@section('content')
<div class="login-page">

    {{-- Subtle grid overlay --}}
    <div class="login-grid-bg"></div>

    {{-- ── LEFT PANE ── --}}
    <div class="login-left">

        {{-- Logo + agency label --}}
        <div class="login-logo-row">
            <div class="login-logo-box">
                <img src="{{ asset('assets/img/dole_logo.png') }}" alt="DOLE Logo" class="login-logo-img">
            </div>
            <div class="login-logo-label">
                <span class="login-logo-abbr">DOLE — REGION IX</span>
                <span class="login-logo-city">Zamboanga Peninsula</span>
            </div>
        </div>

        {{-- Headline --}}
        <div class="login-headline">
            <h1>
                Payroll &amp; <br>
                <span class="login-headline-gold">Travel Expense</span><br>
                Management
            </h1>
            <p class="login-headline-sub">
                Centralized payroll processing and TEV workflow for<br>
                DOLE Regional Office IX — Zamboanga City.
            </p>
        </div>

        {{-- Feature list --}}
        <ul class="login-features">
            <li>Semi-monthly payroll computation for 82 employees</li>
            <li>Automated GSIS, HDMF, PhilHealth remittance reports</li>
            <li>End-to-end TEV workflow with digital approval chain</li>
            <li>Role-based access: HR &rarr; Accountant &rarr; RD/ARD &rarr; Cashier</li>
        </ul>

    </div>

    {{-- ── RIGHT PANE ── --}}
    <div class="login-right">
        <div class="login-card">

            <div class="login-card-header">
                <h2>Sign in</h2>
                <p>Choose your login type below.</p>
            </div>

            @if (session('error'))
                <div class="alert alert-error" style="margin-bottom:16px;">⚠ {{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-error" style="margin-bottom:16px;">⚠ {{ $errors->first() }}</div>
            @endif

            <!-- Unified Login Form -->
            <form method="POST" action="{{ route('login.post') }}" autocomplete="off" class="login-form">
                @csrf

                <div class="lf-group">
                    <label for="login_field">Employee ID or Email</label>
                    <input
                        type="text"
                        id="login_field"
                        name="login_field"
                        value="{{ old('employee_id') ?? old('email') }}"
                        placeholder="Employee ID (e.g. EMP001) or Email"
                        required
                        autofocus
                        class="{{ $errors->has('employee_id') || $errors->has('email') ? 'is-invalid' : '' }}"
                    >
                    @error('employee_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="lf-group">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="••••••••"
                        required
                        class="{{ $errors->has('password') ? 'is-invalid' : '' }}"
                    >
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="login-submit">Sign In</button>

                <div class="login-hint">
                    <small>Employees: Use your Employee ID (EMP001-EMP082) with password "pass123"<br>
                    Admins: Use your email address and assigned password</small>
                </div>
            </form>

            <div class="login-card-footer">
                Forgot your password? Contact the Payroll Officer<br>
                or ICT Unit for account assistance.
            </div>

        </div>
    </div>

</div>


@endsection
