<?php $__env->startSection('title', 'Pro-Rated Payroll — Newly Hired'); ?>
<?php $__env->startSection('page-title', 'Special Payroll'); ?>

<?php $__env->startSection('styles'); ?>
<style>
/* ─────────────────────────────────────────────────────
   FILTER FORM
───────────────────────────────────────────────────── */
.filter-form {
    display: flex;
    gap: 10px;
    align-items: flex-end;
    flex-wrap: wrap;
}
.filter-form .ff-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.filter-form .ff-group label {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: var(--text-mid);
    line-height: 1;
    margin: 0;
}
.filter-form input,
.filter-form select {
    height: 38px;
    margin-bottom: 0 !important;
    box-sizing: border-box;
}
.filter-form .ff-btns {
    display: flex;
    gap: 8px;
    align-items: center;
    height: 38px;
}
.filter-form .ff-btns .btn,
.filter-form .ff-btns .btn-sm {
    height: 38px;
    padding-top: 0;
    padding-bottom: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
    white-space: nowrap;
}

/* ─────────────────────────────────────────────────────
   RESPONSIVE TABLE
───────────────────────────────────────────────────── */
.nh-detail-row { display: none !important; }
.nh-expand-btn { display: none !important; }

/* ── DESKTOP (≥ 769px) ── */
@media (min-width: 769px) {
    .nh-table              { display: table; width: 100%; border-collapse: collapse; }
    .nh-table thead        { display: table-header-group; }
    .nh-table tbody        { display: table-row-group; }
    .nh-table tr           { display: table-row; }
    .nh-table th,
    .nh-table td           { display: table-cell; }
}

/* ── MOBILE (≤ 768px) ── */
@media (max-width: 768px) {

    .filter-form              { flex-direction: column; align-items: stretch; }
    .filter-form .ff-group,
    .filter-form .ff-btns     { width: 100%; }
    .filter-form .ff-btns     { height: auto; }
    .filter-form .ff-btns .btn,
    .filter-form .ff-btns .btn-sm { flex: 1; }

    .table-wrap { overflow: visible; }

    .nh-table        { display: block; }
    .nh-table thead  { display: none; }
    .nh-table tbody  { display: block; }

    /* Card row */
    .nh-table tr.nh-main-row {
        display: flex;
        align-items: center;
        gap: 0;
        padding: 14px 16px;
        border-bottom: 1px solid var(--border);
        cursor: pointer;
        transition: background .15s;
        min-height: 64px;
    }
    .nh-table tr.nh-main-row:active { background: var(--bg); }

    /* Hide columns moved to detail panel */
    .nh-table tr.nh-main-row td.col-position,
    .nh-table tr.nh-main-row td.col-effectivity,
    .nh-table tr.nh-main-row td.col-period,
    .nh-table tr.nh-main-row td.col-year,
    .nh-table tr.nh-main-row td.col-gross,
    .nh-table tr.nh-main-row td.col-deductions,
    .nh-table tr.nh-main-row td.col-net,
    .nh-table tr.nh-main-row td.col-actions {
        display: none;
    }

    /* Employee name — takes all space */
    .nh-table tr.nh-main-row td.col-employee {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 3px;
        padding: 0;
        min-width: 0;
    }
    .nh-table tr.nh-main-row td.col-employee .nh-name-label {
        font-weight: 700;
        font-size: 0.92rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .nh-table tr.nh-main-row td.col-employee .nh-name-sub {
        font-size: 0.74rem;
        color: var(--text-mid);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Status badge */
    .nh-table tr.nh-main-row td.col-status {
        flex: 0 0 auto;
        display: flex;
        align-items: center;
        padding: 0 10px;
    }

    /* Expand button */
    .nh-expand-btn {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        width: 26px;
        height: 26px;
        flex-shrink: 0;
        border-radius: 50%;
        background: transparent;
        border: 1.5px solid var(--border);
        cursor: pointer;
        font-size: 0.65rem;
        color: var(--text-mid);
        transition: transform .2s, background .15s, border-color .15s;
        margin-left: 6px;
    }
    .nh-main-row.open .nh-expand-btn {
        transform: rotate(180deg);
        background: var(--navy-light, #e8ecf4);
        border-color: var(--navy);
        color: var(--navy);
    }

    /* Expanded detail panel */
    tr.nh-detail-row.open {
        display: block !important;
        border-bottom: 1px solid var(--border);
        background: var(--bg, #f8f9fb);
    }
    tr.nh-detail-row.open td {
        display: block;
        padding: 12px 16px 16px;
    }
    .nh-detail-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px 20px;
        margin-bottom: 14px;
    }
    .nh-detail-item label {
        display: block;
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: var(--text-light);
        margin-bottom: 3px;
    }
    .nh-detail-item span {
        font-size: 0.85rem;
        color: var(--text);
        font-weight: 500;
    }
    .nh-detail-item span.mono { font-family: monospace; }
    .nh-detail-actions {
        display: flex;
        gap: 8px;
    }
    .nh-detail-actions .btn,
    .nh-detail-actions button {
        flex: 1;
        justify-content: center;
        text-align: center;
    }
}
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Pro-Rated Payroll — Newly Hired / Transferee</h1>
        <p>Individual payroll records for employees who started mid-period.</p>
    </div>
<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->hasRole('payroll_officer')): ?>
    <a href="<?php echo e(route('special-payroll.newly-hired.create')); ?>" class="btn btn-primary">
        + New Entry
    </a>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('warning')): ?>
    <div class="alert alert-warning"><?php echo e(session('warning')); ?></div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>


<div class="card mb-3">
    <div class="card-body" style="padding:14px 20px;">
        <form method="GET" action="<?php echo e(route('special-payroll.newly-hired.index')); ?>" class="filter-form">

            <div class="ff-group" style="min-width:120px;">
                <label for="year">Year</label>
                <select name="year" id="year">
                    <option value="">All Years</option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = range($currentYear, $currentYear - 3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $y): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <option value="<?php echo e($y); ?>" <?php echo e(request('year') == $y ? 'selected' : ''); ?>>
                            <?php echo e($y); ?>

                        </option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </select>
            </div>

            <div class="ff-group" style="min-width:160px;">
                <label for="status">Status</label>
                <select name="status" id="status">
                    <option value="">All Statuses</option>
                    <option value="draft"    <?php echo e(request('status') === 'draft'    ? 'selected' : ''); ?>>Draft</option>
                    <option value="approved" <?php echo e(request('status') === 'approved' ? 'selected' : ''); ?>>Approved</option>
                    <option value="released" <?php echo e(request('status') === 'released' ? 'selected' : ''); ?>>Released</option>
                </select>
            </div>

            <div class="ff-btns">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <a href="<?php echo e(route('special-payroll.newly-hired.index')); ?>" class="btn btn-outline btn-sm">Reset</a>
            </div>

        </form>
    </div>
</div>


<div class="card">
    <div class="card-body" style="padding:0;">
        <div class="table-wrap">
            <table class="nh-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Position</th>
                        <th>Assumption to Duty</th>
                        <th>Period Covered</th>
                        <th>Year</th>
                        <th>Status</th>
                        <th class="text-right">Gross Earned</th>
                        <th class="text-right">Deductions</th>
                        <th class="text-right">Net Amount</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $batches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $batch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <?php
                            $emp = $batch->employee;

                            $statusClass = match ($batch->status) {
                                'approved' => 'badge-released',
                                'released' => 'badge-locked',
                                default    => 'badge-draft',
                            };
                            $statusLabel = match ($batch->status) {
                                'draft'    => 'Draft',
                                'approved' => 'Approved',
                                'released' => 'Released',
                                default    => ucfirst($batch->status),
                            };

                            $periodStart = $batch->period_start ? $batch->period_start->format('M d') : '—';
                            $periodEnd   = $batch->period_end   ? $batch->period_end->format('d, Y')   : '—';
                        ?>

                        
                        <tr class="nh-main-row" data-id="<?php echo e($batch->id); ?>" onclick="toggleNhRow(this)">

                            <td class="col-employee">
                                <span class="nh-name-label">
                                    <?php echo e(optional($emp)->last_name); ?>,
                                    <?php echo e(optional($emp)->first_name); ?>

                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(optional($emp)->middle_name): ?>
                                        <?php echo e(substr($emp->middle_name, 0, 1)); ?>.
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </span>

                            </td>

                            <td class="col-position text-muted" style="font-size:0.82rem;">
                                <?php echo e(optional($emp)->position_title ?? '—'); ?>

                            </td>

                            <td class="col-effectivity">
                                <?php echo e($batch->effectivity_date ? $batch->effectivity_date->format('M d, Y') : '—'); ?>

                            </td>

                            <td class="col-period text-muted" style="font-size:0.82rem;">
                                <?php echo e($periodStart); ?>–<?php echo e($periodEnd); ?>

                            </td>

                            <td class="col-year"><?php echo e($batch->year); ?></td>

                            <td class="col-status">
                                <span class="badge <?php echo e($statusClass); ?>"><?php echo e($statusLabel); ?></span>
                            </td>

                            <td class="col-gross text-right">
                                ₱<?php echo e(number_format($batch->gross_amount, 2)); ?>

                            </td>

                            <td class="col-deductions text-right" style="color:#B71C1C;">
                                ₱<?php echo e(number_format($batch->deductions_amount, 2)); ?>

                            </td>

                            <td class="col-net text-right fw-bold" style="color:#1B5E20;">
                                ₱<?php echo e(number_format($batch->net_amount, 2)); ?>

                            </td>

                            <td class="col-actions">
                                <div class="d-flex gap-2" style="justify-content:center;">
                                    <a href="<?php echo e(route('special-payroll.newly-hired.show', $batch->id)); ?>"
                                       class="btn btn-outline btn-sm"
                                       onclick="event.stopPropagation();">View</a>
<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->hasRole('payroll_officer|super_admin')): ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($batch->status === 'draft'): ?>
                                            <form id="deleteForm-<?php echo e($batch->id); ?>" method="POST"
                                                  action="<?php echo e(route('special-payroll.newly-hired.destroy', $batch->id)); ?>">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('DELETE'); ?>
                                                <button type="button" class="btn btn-danger btn-sm"
                                                        title="Delete"
                                                        onclick="event.stopPropagation(); confirmDeleteNewlyHired(<?php echo e($batch->id); ?>, '<?php echo e(addslashes(optional($batch->employee)->last_name ?? '')); ?>')">✕</button>
                                            </form>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>

                                
                                <span class="nh-expand-btn" aria-label="Expand">▼</span>
                            </td>

                        </tr>

                        
                        <tr class="nh-detail-row" id="nh-detail-<?php echo e($batch->id); ?>">
                            <td colspan="10">
                                <div class="nh-detail-grid">
                                    <div class="nh-detail-item">
                                        <label>Position</label>
                                        <span><?php echo e(optional($emp)->position_title ?? '—'); ?></span>
                                    </div>
                                    <div class="nh-detail-item">
                                        <label>Year</label>
                                        <span><?php echo e($batch->year); ?></span>
                                    </div>
                                    <div class="nh-detail-item">
                                        <label>Assumption to Duty</label>
                                        <span><?php echo e($batch->effectivity_date ? $batch->effectivity_date->format('M d, Y') : '—'); ?></span>
                                    </div>
                                    <div class="nh-detail-item">
                                        <label>Period Covered</label>
                                        <span><?php echo e($periodStart); ?>–<?php echo e($periodEnd); ?></span>
                                    </div>
                                    <div class="nh-detail-item">
                                        <label>Status</label>
                                        <span><span class="badge <?php echo e($statusClass); ?>"><?php echo e($statusLabel); ?></span></span>
                                    </div>
                                    <div class="nh-detail-item">
                                        <label>Gross Earned</label>
                                        <span class="mono">₱<?php echo e(number_format($batch->gross_amount, 2)); ?></span>
                                    </div>
                                    <div class="nh-detail-item">
                                        <label>Deductions</label>
                                        <span class="mono" style="color:#B71C1C;">₱<?php echo e(number_format($batch->deductions_amount, 2)); ?></span>
                                    </div>
                                    <div class="nh-detail-item">
                                        <label>Net Amount</label>
                                        <span class="mono" style="color:#1B5E20; font-weight:700;">₱<?php echo e(number_format($batch->net_amount, 2)); ?></span>
                                    </div>
                                </div>
                                <div class="nh-detail-actions">
                                    <a href="<?php echo e(route('special-payroll.newly-hired.show', $batch->id)); ?>"
                                       class="btn btn-outline btn-sm">View</a>
<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->hasRole('payroll_officer|super_admin')): ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($batch->status === 'draft'): ?>
                                            <form id="deleteFormMobile-<?php echo e($batch->id); ?>" method="POST"
                                                  action="<?php echo e(route('special-payroll.newly-hired.destroy', $batch->id)); ?>"
                                                  style="flex:1;">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('DELETE'); ?>
                                                <button type="button" class="btn btn-danger btn-sm" style="width:100%;"
                                                        onclick="confirmDeleteNewlyHired(<?php echo e($batch->id); ?>, '<?php echo e(addslashes(optional($batch->employee)->last_name ?? '')); ?>', true)">✕ Delete</button>
                                            </form>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            </td>
                        </tr>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        <tr>
                            <td colspan="10" style="text-align:center; padding:40px; color:var(--text-light);">
                                No records found.
<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->hasRole('payroll_officer|super_admin')): ?>
    <a href="<?php echo e(route('special-payroll.newly-hired.create')); ?>">
        Create one now →
    </a>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div style="margin-top:12px;"><?php echo e($batches->links()); ?></div>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
function toggleNhRow(mainRow) {
    if (window.innerWidth > 768) return;

    const id     = mainRow.dataset.id;
    const detail = document.getElementById('nh-detail-' + id);
    const isOpen = mainRow.classList.contains('open');

    document.querySelectorAll('.nh-main-row.open').forEach(r => r.classList.remove('open'));
    document.querySelectorAll('.nh-detail-row.open').forEach(r => r.classList.remove('open'));

    if (!isOpen) {
        mainRow.classList.add('open');
        detail.classList.add('open');
    }
}

function confirmDeleteNewlyHired(batchId, employeeName, isMobile = false) {
    const formId = isMobile ? 'deleteFormMobile-' + batchId : 'deleteForm-' + batchId;
    Swal.fire({
        title: 'Delete Payroll Record?',
        html: `<div style="text-align:center;">
            <div style="font-size:1.2rem;font-weight:600;color:#dc3545;margin-bottom:8px;">${employeeName || 'Unknown'}</div>
            <p style="color:#6b7280;font-size:0.95rem;">This will permanently delete this draft payroll record and cannot be undone.</p>
        </div>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Delete Record',
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
                    btn.textContent = 'Deleting...';
                });
                form.submit();
            }
        }
    });
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Training_Ground\Visual Studio Code\DOLESYSTEM2\Dole_Payroll\Modules/Payroll\resources/views/special-payroll/newly-hired-index.blade.php ENDPATH**/ ?>