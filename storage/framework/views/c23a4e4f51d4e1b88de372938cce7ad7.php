<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'Dashboard'); ?> — DOLE RO9 Payroll</title>
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

    /* ── Employee Navigation Bar ───────────────────────────────────── */
    .employee-nav {
        position: sticky;
        top: 0;
        z-index: 1000;
        background: var(--navy, #0F1B4C);
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        padding: 0;
    }

    .employee-nav-inner {
        max-width: 1400px;
        margin: 0 auto;
        padding: 12px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
    }

    /* ── Brand Section ─────────────────────────────────────────────── */
    .employee-nav-brand {
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
    }

    .employee-nav-brand-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: white;
        line-height: 1.2;
        letter-spacing: 0.5px;
    }

    .employee-nav-brand-subtitle {
        font-size: 0.75rem;
        color: rgba(255,255,255,0.75);
        font-weight: 500;
        margin-top: 2px;
    }

    /* ── Navigation Links ──────────────────────────────────────────── */
    .employee-nav-links {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .employee-nav-link {
        padding: 8px 16px;
        color: rgba(255,255,255,0.85);
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 500;
        border-radius: 6px;
        transition: all 0.2s;
        position: relative;
    }

    .employee-nav-link:hover {
        color: white;
        background: rgba(255,255,255,0.1);
    }

    .employee-nav-link.active {
        color: white;
        background: rgba(255,255,255,0.15);
        font-weight: 600;
    }

    .employee-nav-link.active::after {
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
    .employee-nav-user {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-shrink: 0;
    }

    .employee-nav-user-info {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        text-align: right;
    }

    .employee-nav-user-name {
        font-size: 0.85rem;
        font-weight: 600;
        color: white;
        line-height: 1.2;
    }

    .employee-nav-user-role {
        font-size: 0.7rem;
        color: rgba(255,255,255,0.7);
        font-weight: 500;
        margin-top: 2px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .employee-nav-avatar {
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

    .employee-nav-logout-btn {
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

    .employee-nav-logout-btn:hover {
        background: rgba(255,255,255,0.2);
        border-color: rgba(255,255,255,0.3);
    }

    /* ── Main Content Area ─────────────────────────────────────────── */
    .employee-wrapper {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    .employee-content {
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

    .btn-outline {
        background: transparent;
        color: var(--navy, #0F1B4C);
        border: 1px solid var(--navy, #0F1B4C);
    }

    .btn-outline:hover {
        background: var(--navy, #0F1B4C);
        color: white;
    }

    .btn-sm {
        padding: 6px 12px;
        font-size: 0.8rem;
    }

    /* ── Alerts ──────────────────────────────────────────────────── */
    .alert {
        padding: 12px 16px;
        border-radius: 6px;
        margin-bottom: 16px;
        font-size: 0.9rem;
    }

    .alert-success {
        background: var(--success-bg);
        color: var(--success);
        border: 1px solid var(--success);
    }

    .alert-error {
        background: var(--red-light);
        color: var(--red);
        border: 1px solid var(--red);
    }

    /* ── Responsive ───────────────────────────────────────────────── */
    @media (max-width: 768px) {
        .employee-nav-inner {
            padding: 12px 16px;
            flex-wrap: wrap;
        }

        .employee-nav-brand {
            width: 100%;
            margin-bottom: 8px;
        }

        .employee-nav-links {
            order: 3;
            width: 100%;
            justify-content: center;
            margin-top: 8px;
        }

        .employee-nav-user {
            gap: 8px;
        }

        .employee-nav-user-info {
            display: none;
        }

        .employee-nav-logout-btn span {
            display: none;
        }

        .employee-nav-logout-btn::before {
            content: 'Logout';
        }

        .page-header {
            flex-direction: column;
        }

        .employee-content {
            padding: 20px 16px;
        }
    }
    </style>
    <?php echo $__env->yieldContent('styles'); ?>
</head>
<body>

<div class="employee-wrapper">

    
    <nav class="employee-nav">
        <div class="employee-nav-inner">

            
            <div class="employee-nav-brand">
                <div class="employee-nav-brand-title">DOLE RO9 Payroll</div>
                <div class="employee-nav-brand-subtitle">Zamboanga Peninsula</div>
            </div>

            
            <div class="employee-nav-links">
                <a href="<?php echo e(route('my-payslip')); ?>" class="employee-nav-link <?php echo e(request()->routeIs('my-payslip') ? 'active' : ''); ?>">
                    My Payslip
                </a>
                <a href="<?php echo e(route('tev.requests.index')); ?>" class="employee-nav-link <?php echo e(request()->routeIs('tev.requests.*') ? 'active' : ''); ?>">
                    TEV Requests
                </a>
            </div>

            
            <div class="employee-nav-user">
                <div class="employee-nav-user-info">
                    <div class="employee-nav-user-name">
                        <?php echo e(session('hris_employee_name') ?? auth()->user()->name); ?>

                    </div>
                    <div class="employee-nav-user-role">
                        EMPLOYEE
                    </div>
                </div>
                <div class="employee-nav-avatar">
                    <?php echo e(strtoupper(substr(session('hris_employee_name') ?? auth()->user()->name, 0, 1))); ?>

                </div>
                <form method="POST" action="<?php echo e(route('logout')); ?>" style="display: inline;">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="employee-nav-logout-btn">
                        <span>Logout</span>
                    </button>
                </form>
            </div>

        </div>
    </nav>

    
    <main class="employee-content">

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('success')): ?>
            <div class="alert alert-success">✓ <?php echo e(session('success')); ?></div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('error')): ?>
            <div class="alert alert-error">✗ <?php echo e(session('error')); ?></div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php echo $__env->yieldContent('content'); ?>

        <script>
        <?php if(session('error')): ?>
            Swal.fire({ icon: 'error', title: 'Error', text: '<?php echo e(addslashes(session('error'))); ?>', confirmButtonColor: '#0F1B4C' });
        <?php endif; ?>
        <?php if(session('warning')): ?>
            Swal.fire({ icon: 'warning', title: 'Warning', text: '<?php echo e(addslashes(session('warning'))); ?>', confirmButtonColor: '#0F1B4C' });
        <?php endif; ?>
        <?php if($errors->any()): ?>
            Swal.fire({
                icon: 'error',
                title: 'Please fix the following:',
                html: '<ul style="text-align:left;margin:0;padding-left:18px;line-height:1.8;">' +
                      <?php echo json_encode($errors->all(), 15, 512) ?>->map(function(e) {
                          return '<li style="margin-bottom:4px;">' + e + '</li>';
                      }).join('') +
                      '</ul>',
                confirmButtonColor: '#0F1B4C',
                confirmButtonText: 'OK',
                customClass: { popup: 'swal-wide' }
            });
        <?php endif; ?>
        </script>

    </main>

</div>

<script src="<?php echo e(asset('js/app.js')); ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php echo $__env->yieldContent('scripts'); ?>

</body>
</html>
<?php /**PATH C:\Training_Ground\Visual Studio Code\DOLESYSTEM2\Dole_Payroll\resources\views/layouts/employee.blade.php ENDPATH**/ ?>