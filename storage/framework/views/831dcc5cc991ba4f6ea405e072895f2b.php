<?php $__env->startSection('title', 'Signatories'); ?>
<?php $__env->startSection('page-title', 'Signatories'); ?>

<?php $__env->startSection('styles'); ?>
<style>
/* ── Signatory cards ──────────────────────────────────────── */
.sig-list { display: flex; flex-direction: column; gap: 8px; margin-bottom: 32px; }

.sig-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px 16px;
    background: white;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    transition: box-shadow 0.15s;
}
.sig-card:hover { box-shadow: var(--shadow-md); }
.sig-card.is-active { border-left: 3px solid #2E7D52; }
.sig-card.is-inactive { opacity: 0.65; }

.sig-avatar {
    width: 40px; height: 40px;
    border-radius: 50%;
    background: var(--navy-light);
    color: var(--navy);
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 0.88rem;
    flex-shrink: 0;
}
.sig-card.is-active .sig-avatar {
    background: #E8F5E9;
    color: #2E7D52;
}

.sig-info { flex: 1; min-width: 0; }
.sig-name {
    font-size: 0.90rem; font-weight: 600; color: var(--navy);
    display: flex; align-items: center; gap: 6px; flex-wrap: wrap;
}
.sig-meta {
    font-size: 0.78rem; color: var(--text-light);
    margin-top: 1px;
}

.sig-role-tag {
    font-size: 0.68rem; font-weight: 700;
    padding: 2px 8px; border-radius: 20px;
    background: var(--navy-light); color: var(--navy);
    border: 1px solid rgba(26,43,107,0.15);
    flex-shrink: 0;
}

.active-pill {
    font-size: 0.65rem; font-weight: 700;
    padding: 1px 8px; border-radius: 20px;
    background: #E8F5E9; color: #2E7D52;
    border: 1px solid #A5D6A7;
}

.sig-actions { display: flex; gap: 6px; flex-shrink: 0; }

/* ── Role group header ────────────────────────────────────── */
.role-group-header {
    font-size: 0.70rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.08em;
    color: var(--text-light);
    padding: 4px 0 8px;
    margin-top: 8px;
    border-bottom: 1px solid var(--border);
    margin-bottom: 10px;
}
.role-group-header:first-child { margin-top: 0; }

/* ── Info banner ──────────────────────────────────────────── */
.sig-info-banner {
    background: #EEF1FA;
    border: 1px solid #C8D2EE;
    border-radius: var(--radius);
    padding: 14px 18px;
    font-size: 0.83rem;
    color: var(--navy);
    margin-bottom: 24px;
    line-height: 1.6;
}
.sig-info-banner strong { color: var(--navy); }

/* ── Responsive ──────────────────────────────────────────── */
@media (max-width: 600px) {
    .sig-card { flex-wrap: wrap; }
    .sig-actions { width: 100%; justify-content: flex-end; }
}
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Signatories</h1>
        <p>Signing officers shown on payslips and official reports</p>
    </div>
    <a href="<?php echo e(route('signatories.create')); ?>" class="btn btn-primary">+ Add Signatory</a>
</div>


<div class="sig-info-banner">
    <strong>How this works:</strong>
    Only <strong>one signatory per role can be active</strong> at a time.
    Activating a new person automatically deactivates the previous one for that role.
    The active signatory's name appears on all payslips and reports generated from that point forward.
    When a designate changes, simply activate the new officer here — no code changes needed.
</div>


<?php
    $roleLabels = [
        'hrmo_designate' => 'HRMO Designate',
        'accountant'     => 'Accountant',
        'ard'            => 'ARD / RD',
        'cashier'        => 'Cashier',
    ];
    $grouped = $signatories->groupBy('role_type');
?>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($signatories->isEmpty()): ?>
    <div style="padding:48px; text-align:center; background:white; border:1px solid var(--border);
                border-radius:var(--radius); color:var(--text-light);">
        <div style="font-size:2rem; margin-bottom:12px;">✍</div>
        <p>No signatories yet.</p>
        <a href="<?php echo e(route('signatories.create')); ?>" class="btn btn-primary" style="margin-top:12px;">
            + Add the first signatory
        </a>
    </div>
<?php else: ?>
    <div class="sig-list">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $grouped; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $roleType => $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>

            <div class="role-group-header">
                <?php echo e($roleLabels[$roleType] ?? ucwords(str_replace('_', ' ', $roleType))); ?>

                <span style="font-weight:400; margin-left:6px; font-size:0.68rem;">
                    (<?php echo e($group->where('is_active', true)->count()); ?> active
                    of <?php echo e($group->count()); ?>)
                </span>
            </div>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $group; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sig): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
            <?php
                $initials = collect(explode(' ', trim($sig->full_name)))
                    ->filter()->take(2)
                    ->map(fn($w) => strtoupper(substr($w, 0, 1)))
                    ->join('');
            ?>

            <div class="sig-card <?php echo e($sig->is_active ? 'is-active' : 'is-inactive'); ?>">

                <div class="sig-avatar"><?php echo e($initials); ?></div>

                <div class="sig-info">
                    <div class="sig-name">
                        <?php echo e($sig->full_name); ?>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sig->is_active): ?>
                            <span class="active-pill">✓ Active</span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <div class="sig-meta">
                        <?php echo e($sig->position_title ?? '—'); ?>

                        · Added <?php echo e($sig->created_at->format('M d, Y')); ?>

                    </div>
                </div>

                <div class="sig-role-tag">
                    <?php echo e($roleLabels[$sig->role_type] ?? ucwords(str_replace('_', ' ', $sig->role_type))); ?>

                </div>

                <div class="sig-actions">

                    
                    <form id="toggleForm-<?php echo e($sig->id); ?>" method="POST" action="<?php echo e(route('signatories.toggle', $sig)); ?>">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PATCH'); ?>
                        <button type="button"
                                class="btn btn-sm <?php echo e($sig->is_active ? 'btn-outline' : 'btn-primary'); ?>"
                                title="<?php echo e($sig->is_active ? 'Deactivate' : 'Set as Active'); ?>"
                                onclick="confirmToggleSignatory(<?php echo e($sig->id); ?>, '<?php echo e(addslashes($sig->full_name)); ?>', <?php echo e($sig->is_active ? 'true' : 'false'); ?>)">
                            <?php echo e($sig->is_active ? '⏸ Deactivate' : '▶ Set Active'); ?>

                        </button>
                    </form>

                    <a href="<?php echo e(route('signatories.edit', $sig)); ?>"
                       class="btn btn-outline btn-sm">✎ Edit</a>

                    <form id="deleteForm-<?php echo e($sig->id); ?>" method="POST" action="<?php echo e(route('signatories.destroy', $sig)); ?>">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button type="button" class="btn btn-danger btn-sm"
                                onclick="confirmDeleteSignatory(<?php echo e($sig->id); ?>, '<?php echo e(addslashes($sig->full_name)); ?>')">✕</button>
                    </form>

                </div>
            </div>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
    </div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
function confirmToggleSignatory(sigId, fullName, isActive) {
    const formId = 'toggleForm-' + sigId;
    const action = isActive ? 'Deactivate' : 'Set Active';
    const title = isActive ? 'Deactivate Signatory?' : 'Set as Active Signatory?';
    const message = isActive
        ? `Payslips will show no active signatory for this role until another is activated.`
        : `The current active person will be deactivated.`;
    Swal.fire({
        title: title,
        html: `<div style="text-align:center;">
            <div style="font-size:1.2rem;font-weight:600;color:${isActive ? '#dc3545' : '#10B981'};margin-bottom:8px;">${fullName}</div>
            <p style="color:#6b7280;font-size:0.95rem;">${message}</p>
        </div>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: action,
        cancelButtonText: 'Cancel',
        confirmButtonColor: isActive ? '#dc3545' : '#10B981',
        cancelButtonColor: '#6B7280',
        reverseButtons: true,
        focusCancel: true
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.getElementById(formId);
            if (form) {
                const buttons = form.querySelectorAll('button');
                buttons.forEach(btn => {
                    btn.disabled = true;
                    btn.textContent = 'Processing...';
                });
                form.submit();
            }
        }
    });
}

function confirmDeleteSignatory(sigId, fullName) {
    const formId = 'deleteForm-' + sigId;
    Swal.fire({
        title: 'Remove Signatory?',
        html: `<div style="text-align:center;">
            <div style="font-size:1.2rem;font-weight:600;color:#dc3545;margin-bottom:8px;">${fullName}</div>
            <p style="color:#6b7280;font-size:0.95rem;">This cannot be undone.</p>
        </div>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Remove',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6B7280',
        reverseButtons: true,
        focusCancel: true
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.getElementById(formId);
            if (form) {
                const buttons = form.querySelectorAll('button');
                buttons.forEach(btn => {
                    btn.disabled = true;
                    btn.textContent = 'Removing...';
                });
                form.submit();
            }
        }
    });
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Training_Ground\Visual Studio Code\DOLESYSTEM2\Dole_Payroll\Modules/Payroll\resources/views/signatories/index.blade.php ENDPATH**/ ?>