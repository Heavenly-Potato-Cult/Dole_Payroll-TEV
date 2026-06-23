<?php $__env->startSection('title', 'Employees'); ?>
<?php $__env->startSection('page-title', 'Employees'); ?>

<?php $__env->startSection('styles'); ?>
<style>
/* ─────────────────────────────────────────────────────
   FILTER FORM — buttons match input/select height
───────────────────────────────────────────────────── */
.filter-form {
    display: flex;
    gap: 10px;
    align-items: flex-end;   /* all children bottom-aligned */
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
/* Button group: NO label above, so it just aligns to flex-end naturally */
.filter-form .ff-btns {
    display: flex;
    gap: 8px;
    align-items: center;
    /* height matches inputs so flex-end works perfectly */
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

/* Detail rows + expand button: always hidden unless mobile overrides */
.emp-detail-row { display: none !important; }
.emp-expand-btn { display: none !important; }

/* Fix pagination to prevent oversized angle brackets */
.pagination { font-size: 0.875rem !important; }
.pagination .page-link { font-size: 0.85rem !important; }

/* ── MOBILE (≤ 768px): card rows ── */
@media (max-width: 768px) {

    /* Filter form: stack vertically on small screens */
    .filter-form              { flex-direction: column; align-items: stretch; }
    .filter-form .ff-group,
    .filter-form .ff-btns     { width: 100%; }
    .filter-form .ff-btns     { height: auto; }
    .filter-form .ff-btns .btn,
    .filter-form .ff-btns .btn-sm { flex: 1; }

    /* Kill horizontal scroll */
    .table-wrap { overflow: visible; }

    /* Table becomes a block list */
    .emp-table        { display: block; }
    .emp-table thead  { display: none; }
    .emp-table tbody  { display: block; }

    /* Each data row = card row */
    .emp-table tr.emp-main-row {
        display: flex;
        align-items: center;
        gap: 0;
        padding: 14px 16px;
        border-bottom: 1px solid var(--border);
        cursor: pointer;
        transition: background .15s;
        min-height: 64px;
    }
    .emp-table tr.emp-main-row:active { background: var(--bg); }

    /* Hide columns that live in the expanded detail panel */
    .emp-table tr.emp-main-row td.col-plantilla,
    .emp-table tr.emp-main-row td.col-position,
    .emp-table tr.emp-main-row td.col-sg,
    .emp-table tr.emp-main-row td.col-step,
    .emp-table tr.emp-main-row td.col-salary,
    .emp-table tr.emp-main-row td.col-actions {
        display: none;
    }

    /* Name — takes all remaining space */
    .emp-table tr.emp-main-row td.col-name {
        flex: 1;
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;          /* allow text truncation */
        padding: 0;
    }
    .emp-table tr.emp-main-row td.col-name a {
        font-weight: 700;
        font-size: 0.92rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Division badge — fixed width, centred */
    .emp-table tr.emp-main-row td.col-division {
        flex: 0 0 auto;
        padding: 0 10px;
        display: flex;
        align-items: center;
    }

    /* Status badge — fixed width, right-aligned */
    .emp-table tr.emp-main-row td.col-status {
        flex: 0 0 auto;
        display: flex;
        align-items: center;
        justify-content: flex-end;
    }

    /* Expand chevron button — show on mobile */
    .emp-expand-btn {
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
        margin-left: 10px;   /* gap from status badge */
    }
    .emp-main-row.open .emp-expand-btn {
        transform: rotate(180deg);
        background: var(--navy-light, #e8ecf4);
        border-color: var(--navy);
        color: var(--navy);
    }

    /* ── Expanded detail panel ── */
    tr.emp-detail-row.open {
        display: block !important;
        border-bottom: 1px solid var(--border);
        background: var(--bg, #f8f9fb);
    }
    tr.emp-detail-row.open td {
        display: block;
        padding: 12px 16px 16px;
    }
    .emp-detail-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px 20px;
        margin-bottom: 14px;
    }
    .emp-detail-item label {
        display: block;
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: var(--text-light);
        margin-bottom: 3px;
    }
    .emp-detail-item span {
        font-size: 0.85rem;
        color: var(--text);
        font-weight: 500;
    }
    .emp-detail-actions {
        display: flex;
        gap: 8px;
    }
    .emp-detail-actions .btn,
    .emp-detail-actions button {
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
        <h1>Employees</h1>
        <p>DOLE RO9 Regular Plantilla — <?php echo e($employees->total()); ?> <?php echo e(Str::plural('record', $employees->total())); ?></p>
    </div>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if (\Illuminate\Support\Facades\Blade::check('role', 'payroll_officer|hrmo|super_admin')): ?>
    <form id="syncHrisForm" method="POST" action="<?php echo e(route('employees.pullFromApi')); ?>" style="display:inline;">
        <?php echo csrf_field(); ?>
        <button type="button" class="btn btn-primary" style="padding-left: 12px;" onclick="confirmSyncHris()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="white" style="display: inline-block; margin-right: 4px; vertical-align: -2px;">
                <path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.7 2.8l1.46 1.46C19.54 15.03 20 13.57 20 12c0-4.42-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.7-2.8L5.24 7.74C4.46 8.97 4 10.43 4 12c0 4.42 3.58 8 8 8v3l4-4-4-4v3z"/>
            </svg>
            Sync from HRIS
        </button>
    </form>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>


<div class="card" style="margin-bottom:18px;">
    <div class="card-body" style="padding:14px 20px;">
        <form method="GET" action="<?php echo e(route('employees.index')); ?>"
              class="filter-form">

            <div class="ff-group" style="flex:1;min-width:200px;">
                <label>Search</label>
                <input type="text" name="search"
                       value="<?php echo e($search); ?>"
                       placeholder="Name, plantilla no., position…">
            </div>

            <div class="ff-group" style="min-width:180px;">
                <label>Division</label>
                <select name="division_id">
                    <option value="">All Divisions</option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $divisions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $div): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <option value="<?php echo e($div->id); ?>"
                            <?php echo e($divisionId == $div->id ? 'selected' : ''); ?>>
                            <?php echo e($div->code); ?> — <?php echo e($div->name); ?>

                        </option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </select>
            </div>

            <div class="ff-group" style="min-width:130px;">
                <label>Status</label>
                <select name="status">
                    <option value="">All Statuses</option>
                    <option value="active"   <?php echo e($status === 'active'   ? 'selected' : ''); ?>>Active</option>
                    <option value="inactive" <?php echo e($status === 'inactive' ? 'selected' : ''); ?>>Inactive</option>
                    <option value="vacant"   <?php echo e($status === 'vacant'   ? 'selected' : ''); ?>>Vacant</option>
                </select>
            </div>

            
            <div class="ff-btns">
                <button type="submit" class="btn btn-outline btn-sm">Search</button>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($search || $divisionId || $status): ?>
                    <a href="<?php echo e(route('employees.index')); ?>"
                       class="btn btn-sm" style="background:var(--bg);border:1.5px solid var(--border);color:var(--text-mid);">
                        Clear
                    </a>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

        </form>
    </div>
</div>


<div class="card">
    <div class="card-header">
        <h3>Plantilla</h3>
        <span class="text-muted" style="font-size:0.82rem;">
            Showing <?php echo e($employees->firstItem()); ?>–<?php echo e($employees->lastItem()); ?>

            of <?php echo e($employees->total()); ?>

        </span>
    </div>

    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="width:160px;">Plantilla No.</th>
                    <th style="width:100px;">Employee ID</th>
                    <th>Name</th>
                    <th>Position</th>
                    <th style="width:130px;">Division</th>
                    <th style="width:70px;text-align:center;">SG</th>
                    <th style="width:60px;text-align:center;">Step</th>
                    <th style="width:130px;text-align:right;">Basic Salary</th>
                    <th style="width:90px;text-align:center;">Status</th>
                    <th style="width:110px;text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $emp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>

                
                <tr class="emp-main-row" data-id="<?php echo e($emp->id); ?>" onclick="toggleEmpRow(this)">
                    <td class="col-plantilla">
                        <code style="font-size:0.76rem;color:var(--text-mid);">
                            <?php echo e($emp->plantilla_item_no); ?>

                        </code>
                    </td>
                    <td class="col-employee-id">
                        <code style="font-size:0.76rem;color:var(--text-mid);">
                            <?php echo e($emp->employee_no); ?>

                        </code>
                    </td>
                    <td class="col-name">
                        <span class="emp-expand-btn" aria-label="Expand">▼</span>
                        <a href="<?php echo e(route('employees.show', $emp)); ?>"
                           onclick="event.stopPropagation();"
                           style="font-weight:600;color:var(--navy);">
                            <?php echo e($emp->full_name); ?>

                        </a>
                    </td>
                    <td class="col-position" style="font-size:0.85rem;"><?php echo e($emp->position_title); ?></td>
                    <td class="col-division">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($emp->division): ?>
                            <span class="badge" style="background:var(--navy-light);color:var(--navy);">
                                <?php echo e($emp->division->code); ?>

                            </span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </td>
                    <td class="col-sg" style="text-align:center;font-weight:600;"><?php echo e($emp->salary_grade); ?></td>
                    <td class="col-step" style="text-align:center;"><?php echo e($emp->step); ?></td>
                    <td class="col-salary" style="text-align:right;font-family:monospace;font-size:0.85rem;">
                        ₱<?php echo e(number_format($emp->basic_salary, 2)); ?>

                    </td>
                    <td class="col-status" style="text-align:center;">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($emp->status === 'active'): ?>
                            <span class="badge badge-active">Active</span>
                        <?php elseif($emp->status === 'inactive'): ?>
                            <span class="badge badge-inactive">Inactive</span>
                        <?php else: ?>
                            <span class="badge badge-draft">Vacant</span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </td>
                    <td class="col-actions" style="text-align:center;">
                        <div class="d-flex gap-2" style="justify-content:center;">
                            <a href="<?php echo e(route('employees.show', $emp)); ?>"
                               class="btn btn-outline btn-sm" title="View">👁</a>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if (\Illuminate\Support\Facades\Blade::check('role', 'payroll_officer|hrmo|super_admin')): ?>
                            <a href="<?php echo e(route('employees.edit', $emp)); ?>"
                               class="btn btn-outline btn-sm" title="Edit">✎</a>
                            <!-- <button type="button" 
                                    class="btn btn-sm <?php echo e($emp->is_excluded ? 'btn-success' : 'btn-warning'); ?>"
                                    onclick="toggleExclusion(<?php echo e($emp->id); ?>, <?php echo e($emp->is_excluded ? 'false' : 'true'); ?>, '<?php echo e(addslashes($emp->full_name)); ?>')"
                                    title="<?php echo e($emp->is_excluded ? 'Include in payroll' : 'Exclude from payroll'); ?>">
                                <?php echo e($emp->is_excluded ? '✓' : '✕'); ?>

                            </button> -->
                            <form method="POST" action="<?php echo e(route('employees.destroy', $emp)); ?>"
                                  onsubmit="return confirm('Remove <?php echo e(addslashes($emp->full_name)); ?> from the active plantilla?\n(Soft delete — record is preserved.)')">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="btn btn-danger btn-sm" title="Remove">🗑</button>
                            </form>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </td>
                </tr>

                
                <tr class="emp-detail-row" id="detail-<?php echo e($emp->id); ?>">
                    <td colspan="10">
                        <div class="emp-detail-grid">
                            <div class="emp-detail-item">
                                <label>Plantilla No.</label>
                                <span>
                                    <code style="font-size:0.78rem;color:var(--text-mid);">
                                        <?php echo e($emp->plantilla_item_no); ?>

                                    </code>
                                </span>
                            </div>
                            <div class="emp-detail-item">
                                <label>Position</label>
                                <span><?php echo e($emp->position_title); ?></span>
                            </div>
                            <div class="emp-detail-item">
                                <label>Salary Grade</label>
                                <span style="font-weight:600;">SG-<?php echo e($emp->salary_grade); ?>, Step <?php echo e($emp->step); ?></span>
                            </div>
                            <div class="emp-detail-item">
                                <label>Basic Salary</label>
                                <span style="font-family:monospace;">₱<?php echo e(number_format($emp->basic_salary, 2)); ?></span>
                            </div>
                        </div>
                        <div class="emp-detail-actions">
                            <a href="<?php echo e(route('employees.show', $emp)); ?>" class="btn btn-outline btn-sm">👁 View</a>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if (\Illuminate\Support\Facades\Blade::check('role', 'payroll_officer|hrmo|super_admin')): ?>
                            <a href="<?php echo e(route('employees.edit', $emp)); ?>" class="btn btn-outline btn-sm">✎ Edit</a>
                            <form method="POST" action="<?php echo e(route('employees.destroy', $emp)); ?>" style="flex:1;"
                                  onsubmit="return confirm('Remove <?php echo e(addslashes($emp->full_name)); ?> from the active plantilla?\n(Soft delete — record is preserved.)')">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="btn btn-danger btn-sm" style="width:100%;" title="Remove">✕ Remove</button>
                            </form>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </td>
                </tr>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                <tr>
                    <td colspan="10" style="text-align:center;padding:40px;color:var(--text-light);">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($search || $divisionId || $status): ?>
                            No employees matched your filters.
                            <a href="<?php echo e(route('employees.index')); ?>">Clear filters →</a>
                        <?php else: ?>
                            No employees yet.
                            <a href="<?php echo e(route('employees.create')); ?>">Add the first employee →</a>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </td>
                </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($employees->hasPages()): ?>
    <div style="padding:4px 20px 8px;">
        <?php echo e($employees->links('pagination::custom')); ?>

    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
function toggleEmpRow(mainRow) {
    const id      = mainRow.dataset.id;
    const detail  = document.getElementById('detail-' + id);
    const isOpen  = mainRow.classList.contains('open');

    // Only works on mobile — on desktop detail rows are always hidden
    if (window.innerWidth > 768) return;

    // Close all open rows first
    document.querySelectorAll('.emp-main-row.open').forEach(r => r.classList.remove('open'));
    document.querySelectorAll('.emp-detail-row.open').forEach(r => r.classList.remove('open'));

    // Toggle clicked row (unless it was already open)
    if (!isOpen) {
        mainRow.classList.add('open');
        detail.classList.add('open');
    }
}

function confirmSyncHris() {
    Swal.fire({
        title: 'Sync from HRIS?',
        text: 'This will update existing employees and add new ones.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sync Now',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#0F1B4C',
        cancelButtonColor: '#6B7280',
        reverseButtons: true,
        focusCancel: true
    }).then((result) => {
        if (result.isConfirmed) {
            executeHrisSync();
        }
    });
}

async function executeHrisSync() {
    // Show loading modal with progress bar
    let progress = 0;
    const progressInterval = setInterval(() => {
        if (progress < 90) {
            progress += Math.random() * 15;
            if (progress > 90) progress = 90;
            Swal.update({
                html: `<div style="margin-top:10px;">
                    <div style="background:#e5e7eb;border-radius:4px;height:8px;overflow:hidden;">
                        <div style="background:#0F1B4C;height:100%;width:${progress}%;transition:width 0.3s;"></div>
                    </div>
                    <p style="margin-top:8px;font-size:0.9rem;color:#6b7280;">${Math.round(progress)}%</p>
                </div>`
            });
        }
    }, 800);

    Swal.fire({
        title: '<span style="color:#0F1B4C;">Syncing from HRIS...</span>',
        html: `<div style="margin-top:10px;">
            <div style="background:#e5e7eb;border-radius:4px;height:8px;overflow:hidden;">
                <div style="background:#0F1B4C;height:100%;width:0%;transition:width 0.3s;"></div>
            </div>
            <p style="margin-top:8px;font-size:0.9rem;color:#6b7280;">0%</p>
        </div>`,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        showCancelButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const form = document.getElementById('syncHrisForm');
        const formData = new FormData(form);
        const csrfToken = formData.get('_token');

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 60000); // 60 second timeout

        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData,
            signal: controller.signal
        });

        clearTimeout(timeoutId);
        clearInterval(progressInterval);

        // Complete progress to 100%
        Swal.update({
            html: `<div style="margin-top:10px;">
                <div style="background:#e5e7eb;border-radius:4px;height:8px;overflow:hidden;">
                    <div style="background:#10B981;height:100%;width:100%;transition:width 0.3s;"></div>
                </div>
                <p style="margin-top:8px;font-size:0.9rem;color:#10B981;">100% Complete!</p>
            </div>`
        });

        await new Promise(resolve => setTimeout(resolve, 500));

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        // Check if we got JSON or a redirect (HTML)
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            const data = await response.json();
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Sync Complete!',
                    html: `<div style="text-align:left;">
                        <p><strong>${data.synced || 'Employees'}</strong> synced successfully</p>
                        ${data.updated ? `<p style="color:#6b7280;font-size:0.9rem;">${data.updated} updated</p>` : ''}
                    </div>`,
                    confirmButtonColor: '#0F1B4C'
                }).then(() => {
                    window.location.reload();
                });
            } else {
                throw new Error(data.message || 'Sync failed');
            }
        } else {
            // HTML response (traditional redirect) - success
            Swal.fire({
                icon: 'success',
                title: 'Sync Complete!',
                text: 'Employees have been synchronized successfully.',
                confirmButtonColor: '#0F1B4C'
            }).then(() => {
                window.location.reload();
            });
        }

    } catch (error) {
        clearInterval(progressInterval);

        let errorTitle = 'Sync Failed';
        let errorMessage = 'An unexpected error occurred.';
        let errorIcon = 'error';

        if (error.name === 'AbortError' || error.message.includes('abort')) {
            errorTitle = 'Connection Timeout';
            errorMessage = 'The HRIS API is taking too long to respond. Please try again later.';
        } else if (error.message.includes('NetworkError') || error.message.includes('fetch')) {
            errorTitle = 'Connection Error';
            errorMessage = 'Unable to connect to the HRIS API. Please check your internet connection or the API endpoint may be down.';
        } else if (error.message.includes('500')) {
            errorTitle = 'Server Error';
            errorMessage = 'The HRIS API server encountered an error. Please contact the system administrator.';
        } else if (error.message.includes('404')) {
            errorTitle = 'API Not Found';
            errorMessage = 'The HRIS API endpoint could not be found. Please verify the API configuration.';
        } else if (error.message) {
            errorMessage = error.message;
        }

        Swal.fire({
            icon: errorIcon,
            title: errorTitle,
            html: `<div style="text-align:left;">
                <p>${errorMessage}</p>
                <p style="margin-top:12px;font-size:0.85rem;color:#6b7280;">
                    <strong>Troubleshooting:</strong><br>
                    • Check your internet connection<br>
                    • Verify the HRIS API is running<br>
                    • Contact IT if the problem persists
                </p>
            </div>`,
            confirmButtonText: 'Try Again',
            confirmButtonColor: '#0F1B4C',
            showCancelButton: true,
            cancelButtonText: 'Cancel',
            cancelButtonColor: '#6B7280'
        }).then((result) => {
            if (result.isConfirmed) {
                executeHrisSync();
            }
        });
    }

    // Toggle employee exclusion from payroll
    async function toggleExclusion(employeeId, shouldExclude, employeeName) {
        const action = shouldExclude ? 'exclude' : 'include';
        const message = shouldExclude 
            ? `Exclude ${employeeName} from payroll processing?` 
            : `Include ${employeeName} in payroll processing?`;

        const result = await Swal.fire({
            title: `${action.charAt(0).toUpperCase() + action.slice(1)} Employee`,
            html: `<div style="text-align:left;">
                <div style="font-weight:700;color:#dc3545;margin-bottom:6px;">${employeeName}</div>
                <div style="color:#6b7280;font-size:0.92rem;">${message}</div>
            </div>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: shouldExclude ? 'Exclude' : 'Include',
            cancelButtonText: 'Cancel',
            confirmButtonColor: shouldExclude ? '#dc3545' : '#0F1B4C',
            cancelButtonColor: '#6B7280',
            reverseButtons: true,
            focusCancel: true
        });

        if (!result.isConfirmed) return;

        Swal.fire({
            title: `<span style="color:#0F1B4C;">${action.charAt(0).toUpperCase() + action.slice(1)}ing…</span>`,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => Swal.showLoading(),
        });

        try {
            const response = await fetch(`/employees/${employeeId}/toggle-exclusion`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ is_excluded: shouldExclude }),
            });

            if (response.ok) {
                Swal.fire({
                    icon: 'success',
                    title: `${action.charAt(0).toUpperCase() + action.slice(1)}d!`,
                    text: `${employeeName} has been ${action}d from payroll processing.`,
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    window.location.reload();
                });
            } else {
                const errorText = await response.text();
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: `Failed to ${action} employee: ${errorText}`,
                });
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: `An error occurred: ${error.message}`,
            });
        }
    }
}
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Training_Ground\Visual Studio Code\DOLESYSTEM2\Dole_Payroll\Modules/Payroll\resources/views/employees/index.blade.php ENDPATH**/ ?>