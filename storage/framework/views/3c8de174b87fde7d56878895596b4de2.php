<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'TEV Dashboard'); ?> — DOLE RO9 Payroll</title>
    <link rel="stylesheet" href="<?php echo e(asset('css/app.css')); ?>">
    <style>
    /* ── Layout fixes ───────────────────────────────────────────────────── */
    .main-content {
        margin-left: 260px;
        padding-top: 60px;
        transition: margin-left 0.3s ease;
        position: relative;
        z-index: 101;
    }
    
    .main-content.no-sidebar {
        margin-left: 0 !important;
    }
    
    @media (max-width: 768px) {
        .main-content {
            margin-left: 0;
        }
    }
    
    /* ── Topbar User Pill Container (replicated from main dashboard) ─────────────────────────────────── */
    .topbar-user-pill {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #f5f6f8;
        border: 0.5px solid rgba(0,0,0,0.1);
        border-radius: 999px;
        padding: 5px 6px 5px 10px;
    }

    /* ── User Info Section (replicated from main dashboard) ─────────────────────────────────────────── */
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

    /* ── User Avatar Circle (replicated from main dashboard) ─────────────────────────────────────────── */
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

    /* ── Vertical Divider (replicated from main dashboard) ─────────────────────────────────────────── */
    .user-divider {
        width: 0.5px;
        height: 20px;
        background: #dfe6e9;
        flex-shrink: 0;
    }

    /* ── Sign Out Button (replicated from main dashboard) ─────────────────────────────────────────── */
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

    /* ── Switch Button (for super admin switching between modules) ─────────────────────────────────────── */
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
    <?php echo $__env->yieldContent('styles'); ?>
</head>
<body>

<div class="app-shell">

    
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!isset($isEmployee) || !$isEmployee): ?>
    <aside class="sidebar" id="sidebar">

        <div class="sidebar-brand">
            <div class="sidebar-logo-wrap">
                <img src="<?php echo e(asset('assets/img/dole_logo.png')); ?>" alt="DOLE" class="sidebar-logo">
            </div>
            <div class="sidebar-title">
                <strong>DOLE RO9 TEV</strong>
                <span>Travel & Expense Voucher</span>
            </div>
        </div>

        <nav class="sidebar-nav">

            <a href="<?php echo e(route('tev.dashboard')); ?>"
               class="nav-item <?php echo e(request()->routeIs('tev.dashboard') ? 'active' : ''); ?>">
                <span class="nav-icon">⊞</span> TEV Dashboard
            </a>

            
            <div class="nav-section-label">Travel Management</div>
            <a href="<?php echo e(route('tev.requests.index')); ?>"
               class="nav-item <?php echo e(request()->routeIs('tev.requests.*') ? 'active' : ''); ?>">
                <span class="nav-icon">✈</span> TEV Requests
            </a>

            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if (\Illuminate\Support\Facades\Blade::check('role', 'hrmo|accountant|budget_officer|ard|cashier|chief_admin_officer|super_admin')): ?>
            <a href="<?php echo e(route('tev.office-orders.index')); ?>"
               class="nav-item <?php echo e(request()->routeIs('tev.office-orders.*') ? 'active' : ''); ?>">
                <span class="nav-icon">📝</span> Office Orders
            </a>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if (\Illuminate\Support\Facades\Blade::check('role', 'hrmo|accountant|budget_officer|ard|cashier|chief_admin_officer|super_admin')): ?>
            
            
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            
        </nav>

        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if (! (auth()->user()->hasRole('payroll_officer'))): ?>
        <div class="sidebar-footer">
            <a href="<?php echo e(route('payroll.dashboard')); ?>" class="btn-switch" title="Go to Payroll">
                Go to Payroll
            </a>
        </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </aside>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <div class="main-content <?php if(isset($isEmployee) && $isEmployee): ?> no-sidebar <?php endif; ?>">

        <header class="topbar">
            <div class="topbar-left">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!isset($isEmployee) || !$isEmployee): ?>
                <button class="burger-btn" onclick="openSidebar()" aria-label="Open menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <span class="topbar-title"><?php echo $__env->yieldContent('page-title', 'TEV Dashboard'); ?></span>
            </div>
            <div class="topbar-right">
                <div class="topbar-user-pill">
                    <div class="user-info">
                        <div class="user-name"><?php echo e(auth()->user()->name); ?></div>
                        <div class="user-role"><?php echo e(str_replace('_', ' ', ucwords(auth()->user()->getRoleNames()->first() ?? ''))); ?></div>
                    </div>
                    <div class="user-avatar">
                        <?php echo e(strtoupper(substr(auth()->user()->name, 0, 1))); ?>

                    </div>
                    <div class="user-divider"></div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if (\Illuminate\Support\Facades\Blade::check('role', 'super_admin')): ?>
                    <form method="POST" action="<?php echo e(route('logout')); ?>" style="display: inline;">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="sign-out-btn">
                            <span class="sign-out-icon">⏻</span>
                            Sign Out
                        </button>
                    </form>
                    <?php else: ?>
                    <form method="POST" action="<?php echo e(route('logout')); ?>" style="display: inline;">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="sign-out-btn">
                            <span class="sign-out-icon">←</span>
                            Logout
                        </button>
                    </form>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
        </header>

        <main class="page-body">

            <?php echo $__env->yieldContent('content'); ?>

            <script>
            <?php if(session('error')): ?>
                Swal.fire({ icon: 'error', title: 'Error', text: '<?php echo e(addslashes(session('error'))); ?>', confirmButtonColor: '#0F1B4C' });
            <?php endif; ?>
            <?php if(session('warning')): ?>
                Swal.fire({ icon: 'warning', title: 'Warning', text: '<?php echo e(addslashes(session('warning'))); ?>', confirmButtonColor: '#0F1B4C' });
            <?php endif; ?>
            </script>

        </main>
    </div>

</div>

<script src="<?php echo e(asset('js/app.js')); ?>"></script>
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
<?php echo $__env->yieldContent('scripts'); ?>

</body>
</html>
<?php /**PATH C:\Training_Ground\Visual Studio Code\DOLESYSTEM2\Dole_Payroll\resources\views/layouts/tev.blade.php ENDPATH**/ ?>