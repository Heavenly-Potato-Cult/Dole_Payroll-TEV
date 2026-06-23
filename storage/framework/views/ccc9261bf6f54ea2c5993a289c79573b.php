<?php $__env->startSection('title', $employee->full_name); ?>
<?php $__env->startSection('page-title', 'Employee Profile'); ?>

<?php $__env->startSection('styles'); ?>
<style>
/* ── Header Section ──────────────────────────────────────── */
.employee-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 24px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border);
    flex-wrap: wrap;
}

.employee-header-right {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.employee-header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.employee-avatar {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: 600;
    color: #6c757d;
    flex-shrink: 0;
}

.employee-info h1 {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
    color: var(--text);
}

.employee-info p {
    margin: 4px 0 0 0;
    color: var(--text-mid);
    font-size: 0.9rem;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 14px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-badge.active {
    background: #212529;
    color: #fff;
}

.status-badge.inactive {
    background: #dc3545;
    color: #fff;
}

.status-badge.vacant {
    background: #6c757d;
    color: #fff;
}

/* ── Tabs Navigation ──────────────────────────────────────── */
.tabs-nav {
    display: flex;
    gap: 0;
    border-bottom: 1px solid var(--border);
    margin: 0 auto 24px auto;
    max-width: 800px;
    justify-content: center;
}

.tab-btn {
    padding: 12px 20px;
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    font-size: 0.9rem;
    font-weight: 500;
    color: var(--text-mid);
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}

.tab-btn:hover {
    color: var(--text);
}

.tab-btn.active {
    color: var(--text);
    border-bottom-color: #212529;
}

/* ── Tab Panels ───────────────────────────────────────────── */
.tab-panel {
    display: none;
}

.tab-panel.active {
    display: block;
}

/* ── Info Card ────────────────────────────────────────────── */
.info-card {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 24px 32px;
    max-width: 800px;
    margin: 0 auto;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    padding: 14px 0;
    border-bottom: 1px solid var(--border);
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    color: var(--text-light);
    font-size: 0.875rem;
    font-weight: 500;
    min-width: 160px;
}

.info-value {
    color: var(--text);
    font-size: 0.875rem;
    font-weight: 500;
    text-align: right;
}

.info-value.mono {
    font-family: monospace;
}

.info-value.bold {
    font-weight: 700;
}

/* ── History Table ───────────────────────────────────────── */
.history-card {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 8px;
    overflow: hidden;
    max-width: 800px;
    margin: 0 auto;
}

.history-card .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 24px;
    border-bottom: 1px solid var(--border);
}

.history-card .card-header h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
}

.record-count {
    font-size: 0.8rem;
    color: var(--text-mid);
}

/* ── Footer Meta ───────────────────────────────────────────── */
.footer-meta {
    margin: 24px auto 0 auto;
    padding-top: 16px;
    border-top: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    font-size: 0.78rem;
    color: var(--text-light);
    max-width: 800px;
}

@media (max-width: 600px) {
    .tabs-nav {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .info-card {
        padding: 16px 20px;
    }

    .info-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }

    .info-value {
        text-align: left;
    }
}
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="employee-header">
    <div class="employee-header-left">
        <div class="employee-avatar">
            <?php echo e(strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1))); ?>

        </div>
        <div class="employee-info">
            <h1><?php echo e($employee->full_name); ?></h1>
            <p><?php echo e($employee->position_title); ?></p>
        </div>
    </div>
    <div class="employee-header-right">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if (\Illuminate\Support\Facades\Blade::check('role', 'payroll_officer|hrmo')): ?>
        <a href="<?php echo e(route('employees.deductions', $employee)); ?>" class="btn btn-outline">💳 Deductions</a>
        <a href="<?php echo e(route('payroll.employees.allowances', $employee)); ?>" class="btn btn-outline">🎫 Allowances</a>
        <a href="<?php echo e(route('employees.edit', $employee)); ?>" class="btn btn-primary">✎ Edit</a>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <a href="<?php echo e(route('employees.index')); ?>" class="btn btn-outline">← Back</a>
    </div>
</div>


<div class="tabs-nav">
    <button class="tab-btn active" data-tab="personal">Personal Information</button>
    <button class="tab-btn" data-tab="position">Position & Assignment</button>
    <button class="tab-btn" data-tab="salary">Salary Information</button>
    <button class="tab-btn" data-tab="government">Government IDs</button>
</div>


<div class="tab-panel active" id="tab-personal">
    <div class="info-card">
        <div class="info-row">
            <span class="info-label">Full Name</span>
            <span class="info-value bold"><?php echo e($employee->full_name); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Last Name</span>
            <span class="info-value"><?php echo e($employee->last_name); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">First Name</span>
            <span class="info-value"><?php echo e($employee->first_name); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Middle Name</span>
            <span class="info-value"><?php echo e($employee->middle_name ?: '—'); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Suffix</span>
            <span class="info-value"><?php echo e($employee->suffix ?: '—'); ?></span>
        </div>
    </div>
</div>


<div class="tab-panel" id="tab-position">
    <div class="info-card">
        <div class="info-row">
            <span class="info-label">Plantilla Item No.</span>
            <span class="info-value mono"><?php echo e($employee->plantilla_item_no ?: '—'); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Position Title</span>
            <span class="info-value"><?php echo e($employee->position_title); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Division</span>
            <span class="info-value"><?php echo e($employee->division ? $employee->division->code . ' — ' . $employee->division->name : '—'); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Hire Date</span>
            <span class="info-value"><?php echo e($employee->hire_date ? $employee->hire_date->format('F d, Y') : '—'); ?></span>
        </div>
    </div>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($employee->promotionHistory->count()): ?>
    <div class="history-card" style="margin-top: 20px; margin-left: auto; margin-right: auto;">
        <div class="card-header">
            <h3>Promotion / Step History</h3>
            <span class="record-count">
                <?php echo e($employee->promotionHistory->count()); ?> <?php echo e(Str::plural('record', $employee->promotionHistory->count())); ?>

            </span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Effective Date</th>
                        <th>SG</th>
                        <th>Step</th>
                        <th style="text-align:right;">Amount</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $employee->promotionHistory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hist): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <tr>
                        <td style="white-space:nowrap;"><?php echo e(\Carbon\Carbon::parse($hist->effectivity_date)->format('M d, Y')); ?></td>
                        <td><?php echo e($hist->new_salary_grade); ?></td>
                        <td><?php echo e($hist->new_step); ?></td>
                        <td style="text-align:right;font-family:monospace;">₱<?php echo e(number_format($hist->new_basic_salary, 2)); ?></td>
                        <td style="font-size:0.82rem;color:var(--text-mid);"><?php echo e($hist->remarks ?? '—'); ?></td>
                    </tr>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>


<div class="tab-panel" id="tab-salary">
    <div class="info-card">
        <div class="info-row">
            <span class="info-label">Salary Grade</span>
            <span class="info-value">SG <?php echo e($employee->salary_grade); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Step</span>
            <span class="info-value">Step <?php echo e($employee->step); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">SIT Year</span>
            <span class="info-value">CY <?php echo e($employee->sit_year); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Basic Salary</span>
            <span class="info-value bold">₱<?php echo e(number_format($employee->basic_salary, 2)); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">PERA</span>
            <span class="info-value">₱<?php echo e(number_format($employee->pera, 2)); ?></span>
        </div>
        <hr style="border:none;border-top:1px solid var(--border);margin:8px 0;">
        <div class="info-row">
            <span class="info-label">Daily Rate (÷22)</span>
            <span class="info-value mono">₱<?php echo e(number_format($employee->daily_rate, 4)); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Hourly Rate (÷22÷8)</span>
            <span class="info-value mono">₱<?php echo e(number_format($employee->hourly_rate, 4)); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Minute Rate</span>
            <span class="info-value mono">₱<?php echo e(number_format($employee->minute_rate, 6)); ?></span>
        </div>
        
        
    </div>
</div>


<div class="tab-panel" id="tab-government">
    <div class="info-card">
        <div class="info-row">
            <span class="info-label">TIN</span>
            <span class="info-value mono"><?php echo e($employee->tin ?: '—'); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">GSIS No.</span>
            <span class="info-value mono"><?php echo e($employee->gsis_bp_no ?: '—'); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Pag-IBIG</span>
            <span class="info-value mono"><?php echo e($employee->pagibig_no ?: '—'); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">PhilHealth</span>
            <span class="info-value mono"><?php echo e($employee->philhealth_no ?: '—'); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">SSS No.</span>
            <span class="info-value mono"><?php echo e($employee->sss_no ?: '—'); ?></span>
        </div>
    </div>
</div>

<div class="footer-meta">
    <span>Record created: <?php echo e($employee->created_at->format('M d, Y g:i A')); ?></span>
    <span>Last updated: <?php echo e($employee->updated_at->format('M d, Y g:i A')); ?></span>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
// Tab switching functionality
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        // Remove active class from all tabs and panels
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));

        // Add active class to clicked tab
        btn.classList.add('active');

        // Show corresponding panel
        const tabId = 'tab-' + btn.dataset.tab;
        document.getElementById(tabId).classList.add('active');
    });
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Training_Ground\Visual Studio Code\DOLESYSTEM2\Dole_Payroll\Modules/Payroll\resources/views/employees/show.blade.php ENDPATH**/ ?>