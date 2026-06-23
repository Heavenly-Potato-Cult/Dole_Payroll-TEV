<?php $__env->startSection('title', 'TEV Officer Dashboard'); ?>
<?php $__env->startSection('page-title', 'TEV Officer Dashboard'); ?>

<?php $__env->startSection('styles'); ?>
<style>
/* ════════════════════════════════════════════════════════════════
   TEV OFFICER DASHBOARD — scoped with .tod- prefix
   Mobile-first, fully responsive
   ════════════════════════════════════════════════════════════════ */

/* ── Greeting ─────────────────────────────────────────────────── */
.tod-greeting {
    margin-bottom: 20px;
    padding: 20px;
    background: linear-gradient(135deg, var(--navy) 0%, #1a2d6d 100%);
    border-radius: var(--radius);
    color: #fff;
    position: relative;
    overflow: hidden;
}
.tod-greeting::after {
    content: '';
    position: absolute;
    right: -30px; top: -30px;
    width: 140px; height: 140px;
    background: rgba(249,168,37,0.12);
    border-radius: 50%;
}

.tod-greeting-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
    gap: 12px;
}

.tod-greeting h1 {
    font-size: clamp(1.1rem, 3vw, 1.4rem);
    margin: 0;
    font-weight: 700;
    color: #fff;
    line-height: 1.2;
}

.tod-greeting .tod-role-pill {
    display: inline-block;
    background: rgba(249,168,37,0.22);
    border: 1px solid rgba(249,168,37,0.45);
    color: var(--gold);
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    padding: 3px 10px;
    border-radius: 20px;
    flex-shrink: 0;
}

.tod-greeting-body {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.tod-greeting-date {
    font-size: 0.9rem;
    color: rgba(255,255,255,0.8);
    font-weight: 500;
}

.tod-greeting-location {
    font-size: 0.82rem;
    color: rgba(255,255,255,0.65);
}

/* Mobile responsiveness */
@media (max-width: 480px) {
    .tod-greeting-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .tod-greeting .tod-role-pill {
        align-self: flex-start;
    }
}

/* ── Stat Grid ────────────────────────────────────────────────── */
.tod-stat-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
    margin-bottom: 18px;
}
@media (min-width: 480px) { .tod-stat-grid { grid-template-columns: repeat(2, 1fr); } }
@media (min-width: 768px) { .tod-stat-grid { grid-template-columns: repeat(4, 1fr); } }

.tod-stat {
    background: #fff;
    border: 0.5px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.1rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    min-width: 0;
    display: flex;
    align-items: stretch;
    gap: 0;
}

.tod-stat-left {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 90px;
    padding-right: 12px;
}

.tod-stat-divider {
    width: 0.5px;
    background: #e2e8f0;
    flex-shrink: 0;
}

.tod-stat-right {
    display: flex;
    align-items: center;
    justify-content: center;
    padding-left: 12px;
    min-width: 70px;
}

.tod-stat-title {
    font-size: 16px;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 4px;
}

.tod-stat-subtitle {
    font-size: 13px;
    color: #94a3b8;
}

.tod-stat-tag {
    display: inline-flex;
    font-size: 12px;
    font-weight: 500;
    padding: 4px 10px;
    border-radius: 4px;
    align-self: flex-start;
}

.tod-stat.purple .tod-stat-tag {
    background: #EEEDFE;
    color: #534AB7;
}

.tod-stat.coral .tod-stat-tag {
    background: #FAECE7;
    color: #993C1D;
}

.tod-stat.neutral .tod-stat-tag {
    background: #EEEDFE;
    color: #534AB7;
}

.tod-stat-value {
    font-size: 56px;
    font-weight: 600;
    letter-spacing: -3px;
    line-height: 1;
    color: #534AB7;
}

/* ── Pulsing dot ─────────────────────────────────────────────── */
.tod-dot {
    display: inline-block;
    width: 8px; height: 8px;
    background: var(--red);
    border-radius: 50%;
    flex-shrink: 0;
    animation: todpulse 1.8s ease-in-out infinite;
}
@keyframes todpulse {
    0%,100% { opacity:1; transform:scale(1); }
    50%      { opacity:0.4; transform:scale(0.65); }
}

/* ── Layout ───────────────────────────────────────────────────── */
.tod-main { display: flex; flex-direction: column; gap: 16px; margin-bottom: 16px; }

.tod-row {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
}
@media (min-width: 768px) { .tod-row { grid-template-columns: 1fr 1fr; } }

/* ── Cards ───────────────────────────────────────────────────── */
.tod-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    min-width: 0;
}
.tod-card-head {
    padding: 11px 16px;
    border-bottom: 1px solid var(--border);
    background: #FAFBFF;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    flex-shrink: 0;
}
.tod-card-head h3 {
    font-size: 0.88rem;
    font-weight: 600;
    margin: 0;
    color: var(--navy);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.tod-card-body { flex: 1; }

/* ── Queue Grid Layout ───────────────────────────────────────── */
.tod-queue-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    margin-bottom: 16px;
}

@media (max-width: 767px) {
    .tod-queue-grid {
        grid-template-columns: 1fr;
    }
}

/* ── Queue List ───────────────────────────────────────────────── */
.tod-queue-section {
    background: #fff;
    border: 0.5px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
}

.tod-queue-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border-bottom: 0.5px solid #e2e8f0;
}

.tod-queue-label {
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: var(--text);
}

.tod-queue-viewall {
    font-size: 12px;
    color: #534AB7;
    text-decoration: none;
}

.tod-queue-viewall:hover {
    text-decoration: underline;
}

.tod-queue-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border-bottom: 0.5px solid #e2e8f0;
    cursor: pointer;
    text-decoration: none;
    transition: background 0.12s;
}

.tod-queue-row:hover,
.tod-queue-row:hover .tod-queue-label-text,
.tod-queue-row:hover .tod-queue-subtitle,
.tod-queue-row:hover .tod-queue-number,
.tod-queue-row:hover .tod-queue-chevron {
    text-decoration: none;
}

.tod-queue-row:last-child {
    border-bottom: none;
}

.tod-queue-row:hover {
    background: #f8fafc;
}

.tod-queue-left {
    display: flex;
    flex-direction: column;
}

.tod-queue-label-text {
    font-size: 13px;
    font-weight: 500;
    color: var(--text);
}

.tod-queue-subtitle {
    font-size: 11px;
    color: #94a3b8;
}

.tod-queue-right {
    display: flex;
    align-items: center;
    gap: 8px;
}

.tod-queue-number {
    font-size: 20px;
    font-weight: 500;
    letter-spacing: -1px;
    color: var(--text);
}

.tod-queue-chevron {
    font-size: 1.2rem;
    color: #cbd5e1;
}

.tod-queue-badge {
    font-size: 10px;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 4px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* ── Urgent row styling (Pending RD) ─────────────────────────── */
.tod-queue-row.urgent {
    background: #FFFAF9;
}

.tod-queue-row.urgent:hover {
    background: #FFF5F2;
}

.tod-queue-row.urgent .tod-queue-label-text {
    color: #D85A30;
}

.tod-queue-row.urgent .tod-queue-number {
    color: #D85A30;
}

.tod-queue-row.urgent .tod-queue-chevron {
    color: #D85A30;
}

.tod-queue-row.urgent .tod-queue-badge {
    background: #FAECE7;
    color: #993C1D;
}

/* ── List rows ───────────────────────────────────────────────── */
.tod-list { list-style: none; width: 100%; }
.tod-list-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 16px;
    border-bottom: 1px solid var(--border);
    min-width: 0;
    transition: background 0.1s;
}
.tod-list-item:last-child { border-bottom: none; }
.tod-list-item:hover { background: var(--navy-light); }
.tod-list-main { flex: 1; min-width: 0; }
.tod-list-title {
    font-size: 0.83rem;
    font-weight: 600;
    color: var(--navy);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.tod-list-sub {
    font-size: 0.72rem;
    color: var(--text-light);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-top: 1px;
}
.tod-list-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
    flex-shrink: 0;
}
.tod-view-link {
    font-size: 0.7rem;
    color: var(--navy);
    opacity: 0.45;
    text-decoration: none;
    white-space: nowrap;
}
.tod-view-link:hover { opacity: 1; text-decoration: underline; }

/* ── Badges ──────────────────────────────────────────────────── */
.tod-badge {
    display: inline-block;
    padding: 2px 7px;
    border-radius: 20px;
    font-size: 0.62rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    white-space: nowrap;
}
.tod-b-draft    { background: #ECEFF1; color: #607D8B; }
.tod-b-computed { background: #E3F2FD; color: #0D47A1; }
.tod-b-pending  { background: #FFF9C4; color: #B45309; }
.tod-b-released { background: var(--success-bg); color: var(--success); }
.tod-b-locked   { background: var(--navy-light); color: var(--navy); }
.tod-b-navy     { background: var(--navy); color: #fff; }
.tod-b-gold     { background: var(--gold-light); color: var(--gold-dark); }
.tod-b-red      { background: var(--red-light); color: var(--red); }
.tod-b-teal     { background: #E0F7FA; color: #00838F; }

/* ── Empty states ────────────────────────────────────────────── */
.tod-empty {
    padding: 26px 16px;
    text-align: center;
    color: var(--text-light);
    font-size: 0.82rem;
}
.tod-empty-icon { font-size: 1.5rem; margin-bottom: 6px; }

/* ── Quick Actions ───────────────────────────────────────────── */
.tod-actions { padding: 12px 14px 14px; display: flex; flex-direction: column; gap: 7px; }
.tod-action-btn {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 9px 13px;
    border-radius: 6px;
    border: 1.5px solid var(--border);
    background: white;
    color: var(--navy);
    font-size: 0.83rem;
    font-weight: 500;
    text-decoration: none;
    transition: background 0.12s, border-color 0.12s;
    gap: 8px;
    min-width: 0;
}
.tod-action-btn:hover { background: var(--navy-light); border-color: var(--navy); color: var(--navy); text-decoration: none; }
.tod-action-btn.primary { background: var(--navy); color: #fff; border-color: var(--navy); }
.tod-action-btn.primary:hover { background: #1a2d6d; color: #fff; }
.tod-action-btn.gold-btn { background: var(--gold); color: #fff; border-color: var(--gold); }
.tod-action-btn.gold-btn:hover { background: #e5961f; color: #fff; }
.tod-action-left {
    display: flex; align-items: center; gap: 7px;
    min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.tod-action-count {
    background: var(--red);
    color: white;
    font-size: 0.62rem;
    font-weight: 700;
    padding: 1px 6px;
    border-radius: 10px;
    flex-shrink: 0;
    animation: todpulse 1.8s ease-in-out infinite;
}
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>


<div class="tod-greeting">
    <div class="tod-greeting-header">
        <h1>TEV Officer Dashboard</h1>
        <span class="tod-role-pill"><?php echo e(str_replace('_', ' ', strtoupper(auth()->user()->getRoleNames()->first() ?? 'user'))); ?></span>
    </div>
    <div class="tod-greeting-body">
        <div class="tod-greeting-date"><?php echo e(now()->format('l, F j, Y')); ?></div>
        <div class="tod-greeting-location">Travel & Expense Voucher Management</div>
    </div>
</div>


<div class="tod-stat-grid">

    
    <div class="tod-stat purple">
        <div class="tod-stat-left">
            <div>
                <div class="tod-stat-title">Active Employees</div>
                <div class="tod-stat-subtitle">Plantilla items</div>
            </div>
            <div class="tod-stat-tag">HRIS</div>
        </div>
        <div class="tod-stat-divider"></div>
        <div class="tod-stat-right">
            <div class="tod-stat-value"><?php echo e(number_format($totalEmployees)); ?></div>
        </div>
    </div>

    
    <div class="tod-stat neutral">
        <div class="tod-stat-left">
            <div>
                <div class="tod-stat-title">TEV This Month</div>
                <div class="tod-stat-subtitle"><?php echo e($currentMonth); ?></div>
            </div>
            <div class="tod-stat-tag">Travel</div>
        </div>
        <div class="tod-stat-divider"></div>
        <div class="tod-stat-right">
            <div class="tod-stat-value"><?php echo e($tevThisMonth); ?></div>
        </div>
    </div>

    
    <div class="tod-stat coral">
        <div class="tod-stat-left">
            <div>
                <div class="tod-stat-title">Pending Action</div>
                <div class="tod-stat-subtitle">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($pendingApprovals > 0): ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($pendingTev > 0): ?><?php echo e($pendingTev); ?> TEV <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($pendingLiquidation > 0): ?><?php echo e($pendingLiquidation); ?> Liq. <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php else: ?>
                        All clear
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
            <div class="tod-stat-tag">Resolve Now</div>
        </div>
        <div class="tod-stat-divider"></div>
        <div class="tod-stat-right">
            <div class="tod-stat-value"><?php echo e($pendingApprovals); ?></div>
        </div>
    </div>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if (\Illuminate\Support\Facades\Blade::check('role', 'accountant')): ?>
    <div class="tod-stat neutral">
        <div class="tod-stat-left">
            <div>
                <div class="tod-stat-title">TEV for Certification</div>
                <div class="tod-stat-subtitle">Awaiting accountant cert.</div>
            </div>
            <div class="tod-stat-tag">Travel</div>
        </div>
        <div class="tod-stat-divider"></div>
        <div class="tod-stat-right">
            <div class="tod-stat-value"><?php echo e($pendingTev); ?></div>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if (\Illuminate\Support\Facades\Blade::check('role', 'ard|chief_admin_officer')): ?>
    <div class="tod-stat neutral">
        <div class="tod-stat-left">
            <div>
                <div class="tod-stat-title">TEV for Approval</div>
                <div class="tod-stat-subtitle">Acct. certified, needs RD</div>
            </div>
            <div class="tod-stat-tag">Travel</div>
        </div>
        <div class="tod-stat-divider"></div>
        <div class="tod-stat-right">
            <div class="tod-stat-value"><?php echo e($pendingTev); ?></div>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if (\Illuminate\Support\Facades\Blade::check('role', 'cashier')): ?>
    <div class="tod-stat neutral">
        <div class="tod-stat-left">
            <div>
                <div class="tod-stat-title">Liquidations Pending</div>
                <div class="tod-stat-subtitle">Awaiting cashier approval</div>
            </div>
            <div class="tod-stat-tag">Travel</div>
        </div>
        <div class="tod-stat-divider"></div>
        <div class="tod-stat-right">
            <div class="tod-stat-value"><?php echo e($pendingLiquidation); ?></div>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if (\Illuminate\Support\Facades\Blade::check('role', 'budget_officer')): ?>
    <div class="tod-stat neutral">
        <div class="tod-stat-left">
            <div>
                <div class="tod-stat-title">TEV Submissions</div>
                <div class="tod-stat-subtitle">Submitted, for monitoring</div>
            </div>
            <div class="tod-stat-tag">Travel</div>
        </div>
        <div class="tod-stat-divider"></div>
        <div class="tod-stat-right">
            <div class="tod-stat-value"><?php echo e($pendingTev); ?></div>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if (\Illuminate\Support\Facades\Blade::check('role', 'hrmo')): ?>
    <div class="tod-stat neutral">
        <div class="tod-stat-left">
            <div>
                <div class="tod-stat-title">Cash Advance TEVs</div>
                <div class="tod-stat-subtitle">Released, need liquidation</div>
            </div>
            <div class="tod-stat-tag">Travel</div>
        </div>
        <div class="tod-stat-divider"></div>
        <div class="tod-stat-right">
            <div class="tod-stat-value"><?php echo e($pendingTev); ?></div>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if (\Illuminate\Support\Facades\Blade::check('role', 'super_admin')): ?>
    <div class="tod-stat purple">
        <div class="tod-stat-left">
            <div>
                <?php 
                $officerRoles = ['super_admin', 'payroll_officer', 'hrmo', 'cashier', 'accountant', 'ard', 'budget_officer', 'chief_admin_officer'];
                $totalUsers = \App\Models\User::whereHas('roles', function($query) use ($officerRoles) {
                    $query->whereIn('name', $officerRoles);
                })->count();
                ?>
                <div class="tod-stat-title">System Users</div>
                <div class="tod-stat-subtitle">Registered accounts</div>
            </div>
            <div class="tod-stat-tag">Admin</div>
        </div>
        <div class="tod-stat-divider"></div>
        <div class="tod-stat-right">
            <div class="tod-stat-value"><?php echo e($totalUsers); ?></div>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

</div>
    

<div class="tod-main">


<div class="tod-queue-grid">

    
    <div class="tod-queue-section">
        <div class="tod-queue-header">
            <span class="tod-queue-label">TEV Queue</span>
            <a href="<?php echo e(route('tev.requests.index')); ?>" class="tod-queue-viewall">View all →</a>
        </div>
        <a href="<?php echo e(route('tev.requests.index')); ?>?status=submitted" class="tod-queue-row">
            <div class="tod-queue-left">
                <div class="tod-queue-label-text">Submitted</div>
                <div class="tod-queue-subtitle">Awaiting Budget Officer review</div>
            </div>
            <div class="tod-queue-right">
                <div class="tod-queue-number"><?php echo e($tevSubmitted); ?></div>
                <span class="tod-queue-chevron">›</span>
            </div>
        </a>
        <a href="<?php echo e(route('tev.requests.index')); ?>?status=budget_officer_approved" class="tod-queue-row">
            <div class="tod-queue-left">
                <div class="tod-queue-label-text">Budget Officer Approved</div>
                <div class="tod-queue-subtitle">Ready for Chief review</div>
            </div>
            <div class="tod-queue-right">
                <div class="tod-queue-number"><?php echo e($tevBudgetApproved); ?></div>
                <span class="tod-queue-chevron">›</span>
            </div>
        </a>
        <a href="<?php echo e(route('tev.requests.index')); ?>?status=chief_approved" class="tod-queue-row">
            <div class="tod-queue-left">
                <div class="tod-queue-label-text">Chief Approved</div>
                <div class="tod-queue-subtitle">Ready for RD approval</div>
            </div>
            <div class="tod-queue-right">
                <div class="tod-queue-number"><?php echo e($tevChiefApproved); ?></div>
                <span class="tod-queue-chevron">›</span>
            </div>
        </a>
        <a href="<?php echo e(route('tev.requests.index')); ?>?status=rd_approved" class="tod-queue-row <?php echo e($tevRdApproved > 0 ? 'urgent' : ''); ?>">
            <div class="tod-queue-left">
                <div class="tod-queue-label-text">RD Approved</div>
                <div class="tod-queue-subtitle">Ready for cashier release</div>
            </div>
            <div class="tod-queue-right">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($tevRdApproved > 0): ?>
                <span class="tod-queue-badge">Action required</span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <div class="tod-queue-number"><?php echo e($tevRdApproved); ?></div>
                <span class="tod-queue-chevron">›</span>
            </div>
        </a>
        <a href="<?php echo e(route('tev.requests.index')); ?>?status=liquidation_filed" class="tod-queue-row">
            <div class="tod-queue-left">
                <div class="tod-queue-label-text">Liquidation Filed</div>
                <div class="tod-queue-subtitle">Awaiting cashier approval</div>
            </div>
            <div class="tod-queue-right">
                <div class="tod-queue-number"><?php echo e($tevLiqFiled); ?></div>
                <span class="tod-queue-chevron">›</span>
            </div>
        </a>
    </div>

    
    <div class="tod-queue-section">
        <div class="tod-queue-header">
            <span class="tod-queue-label">Liquidation Queue</span>
            <a href="<?php echo e(route('tev.requests.index')); ?>?track=cash_advance" class="tod-queue-viewall">View all →</a>
        </div>
        <a href="<?php echo e(route('tev.requests.index')); ?>?status=cashier_released&track=cash_advance" class="tod-queue-row">
            <div class="tod-queue-left">
                <div class="tod-queue-label-text">Cash Advances Released</div>
                <div class="tod-queue-subtitle">Awaiting liquidation filing</div>
            </div>
            <div class="tod-queue-right">
                <div class="tod-queue-number"><?php echo e($tevCashReleased); ?></div>
                <span class="tod-queue-chevron">›</span>
            </div>
        </a>
        <a href="<?php echo e(route('tev.requests.index')); ?>?status=liquidated" class="tod-queue-row">
            <div class="tod-queue-left">
                <div class="tod-queue-label-text">Fully Liquidated</div>
                <div class="tod-queue-subtitle">Completed cash advances</div>
            </div>
            <div class="tod-queue-right">
                <div class="tod-queue-number"><?php echo e($tevLiquidated); ?></div>
                <span class="tod-queue-chevron">›</span>
            </div>
        </a>
        <a href="<?php echo e(route('tev.requests.index')); ?>?track=reimbursement" class="tod-queue-row">
            <div class="tod-queue-left">
                <div class="tod-queue-label-text">Reimbursement Track</div>
                <div class="tod-queue-subtitle">No liquidation required</div>
            </div>
            <div class="tod-queue-right">
                <div class="tod-queue-number"><?php echo e($tevReimbursement); ?></div>
                <span class="tod-queue-chevron">›</span>
            </div>
        </a>
    </div>

</div>


<div class="tod-row">
    <div class="tod-card">
        <div class="tod-card-head">
            <h3>📋 Recent TEV Requests</h3>
            <a href="<?php echo e(route('tev.requests.index')); ?>" class="btn btn-outline btn-sm" style="flex-shrink:0;">View All</a>
        </div>
        <div class="tod-card-body">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($recentTev->isEmpty()): ?>
                <div class="tod-empty"><div class="tod-empty-icon">✈</div>No TEV requests yet.</div>
            <?php else: ?>
                <ul class="tod-list">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $recentTev; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tev): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <?php
                        $tevSt = ['draft'=>['Draft','tod-b-draft'],'submitted'=>['Submitted','tod-b-pending'],'accountant_certified'=>['Acct. Cert.','tod-b-computed'],'rd_approved'=>['RD Approved','tod-b-released'],'cashier_released'=>['CA Released','tod-b-gold'],'reimbursed'=>['Reimbursed','tod-b-released'],'liquidation_filed'=>['Liq. Filed','tod-b-pending'],'liquidated'=>['Liquidated','tod-b-locked'],'rejected'=>['Rejected','tod-b-red']];
                        $t  = $tevSt[$tev->status] ?? [ucwords(str_replace('_',' ',$tev->status)),'tod-b-draft'];
                        $en = $tev->employee ? $tev->employee->last_name.', '.substr($tev->employee->first_name,0,1).'.' : '—';
                    ?>
                    <li class="tod-list-item">
                        <div class="tod-list-main">
                            <div class="tod-list-title"><?php echo e($tev->tev_no); ?></div>
                            <div class="tod-list-sub"><?php echo e($en); ?> • <?php echo e($tev->destination); ?></div>
                        </div>
                        <div class="tod-list-right">
                            <span class="tod-badge <?php echo e($t[1]); ?>"><?php echo e($t[0]); ?></span>
                            <a href="<?php echo e(route('tev.requests.show', $tev)); ?>" class="tod-view-link">View →</a>
                        </div>
                    </li>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </ul>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>

    
    <div class="tod-card">
        <div class="tod-card-head">
            <h3>⚡ Quick Actions</h3>
        </div>
        <div class="tod-actions">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if (\Illuminate\Support\Facades\Blade::check('role', 'accountant')): ?>
            <a href="<?php echo e(route('tev.requests.index')); ?>?status=submitted" class="tod-action-btn">
                <div class="tod-action-left">
                    <span>📝</span>
                    <span>Review Submitted TEVs</span>
                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($tevSubmitted > 0): ?>
                <span class="tod-action-count"><?php echo e($tevSubmitted); ?></span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </a>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if (\Illuminate\Support\Facades\Blade::check('role', 'ard|chief_admin_officer')): ?>
            <a href="<?php echo e(route('tev.requests.index')); ?>?status=accountant_certified" class="tod-action-btn">
                <div class="tod-action-left">
                    <span>✅</span>
                    <span>Approve TEVs</span>
                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($tevCertified > 0): ?>
                <span class="tod-action-count"><?php echo e($tevCertified); ?></span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </a>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if (\Illuminate\Support\Facades\Blade::check('role', 'cashier')): ?>
            <a href="<?php echo e(route('tev.requests.index')); ?>?status=rd_approved" class="tod-action-btn primary">
                <div class="tod-action-left">
                    <span>💰</span>
                    <span>Release Cash Advances</span>
                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($tevRdApproved > 0): ?>
                <span class="tod-action-count"><?php echo e($tevRdApproved); ?></span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </a>
            <a href="<?php echo e(route('tev.requests.index')); ?>?status=liquidation_filed" class="tod-action-btn gold-btn">
                <div class="tod-action-left">
                    <span>📊</span>
                    <span>Approve Liquidations</span>
                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($tevLiqFiled > 0): ?>
                <span class="tod-action-count"><?php echo e($tevLiqFiled); ?></span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </a>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if (\Illuminate\Support\Facades\Blade::check('role', 'hrmo')): ?>
            <a href="<?php echo e(route('tev.requests.index')); ?>?status=cashier_released&track=cash_advance" class="tod-action-btn">
                <div class="tod-action-left">
                    <span>📋</span>
                    <span>Monitor Liquidations</span>
                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($tevCashReleased > 0): ?>
                <span class="tod-action-count"><?php echo e($tevCashReleased); ?></span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </a>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if (\Illuminate\Support\Facades\Blade::check('role', 'budget_officer')): ?>
            <a href="<?php echo e(route('tev.requests.index')); ?>" class="tod-action-btn">
                <div class="tod-action-left">
                    <span>📊</span>
                    <span>Monitor All TEVs</span>
                </div>
            </a>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if (\Illuminate\Support\Facades\Blade::check('role', 'super_admin')): ?>
            <a href="<?php echo e(route('tev.requests.index')); ?>" class="tod-action-btn">
                <div class="tod-action-left">
                    <span>🔍</span>
                    <span>TEV Administration</span>
                </div>
            </a>
            <a href="<?php echo e(route('tev.office-orders.index')); ?>" class="tod-action-btn">
                <div class="tod-action-left">
                    <span>📄</span>
                    <span>Office Orders</span>
                </div>
            </a>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            
            <a href="<?php echo e(route('reports.tev-register')); ?>" class="tod-action-btn">
                <div class="tod-action-left">
                    <span>📈</span>
                    <span>TEV Reports</span>
                </div>
            </a>
        </div>
    </div>

</div>

</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.tev', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Training_Ground\Visual Studio Code\DOLESYSTEM2\Dole_Payroll\Modules/Tev\resources/views/officer-dashboard.blade.php ENDPATH**/ ?>