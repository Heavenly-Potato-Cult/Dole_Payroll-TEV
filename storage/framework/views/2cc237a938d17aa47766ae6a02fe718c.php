<?php $__env->startSection('title', 'Divisions'); ?>
<?php $__env->startSection('page-title', 'Divisions'); ?>

<?php $__env->startSection('content'); ?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Divisions</h1>
        <p>DOLE RO9 organisational divisions (managed via HRIS API)</p>
    </div>
</div>


<div class="card mb-2" style="margin-bottom:18px;">
    <div class="card-body" style="padding:14px 20px;">
        <form method="GET" action="<?php echo e(route('divisions.index')); ?>"
              style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <input type="text" name="search" placeholder="Search name or code…"
                   value="<?php echo e($search); ?>"
                   style="max-width:320px;margin-bottom:0;">
            <button type="submit" class="btn btn-outline btn-sm">Search</button>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($search): ?>
                <a href="<?php echo e(route('divisions.index')); ?>" class="btn btn-sm"
                   style="background:var(--bg);border:1.5px solid var(--border);color:var(--text-mid);">
                    Clear
                </a>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </form>
    </div>
</div>


<div class="card">
    <div class="card-header">
        <h3>All Divisions</h3>
        <span class="text-muted" style="font-size:0.82rem;">
            <?php echo e($divisions->total()); ?> <?php echo e(Str::plural('division', $divisions->total())); ?>

        </span>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width:52px;">#</th>
                    <th style="width:90px;">Code</th>
                    <th>Division Name</th>
                    <th>Description</th>
                    <th style="width:90px;text-align:center;">Employees</th>
                    <th style="width:90px;text-align:center;">Status</th>
                    
                </tr>
            </thead>
            <tbody>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $divisions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $division): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <tr>
                    <td class="text-muted" style="font-size:0.80rem;">
                        <?php echo e($divisions->firstItem() + $loop->index); ?>

                    </td>
                    <td>
                        <code style="background:var(--navy-light);color:var(--navy);
                                     padding:2px 8px;border-radius:4px;font-size:0.78rem;
                                     font-weight:700;letter-spacing:0.04em;">
                            <?php echo e($division->code); ?>

                        </code>
                    </td>
                    <td class="fw-bold" style="color:var(--navy);">
                        <?php echo e($division->name); ?>

                    </td>
                    <td class="text-muted" style="font-size:0.84rem;">
                        <?php echo e(Str::limit($division->description, 80, '…') ?: '—'); ?>

                    </td>
                    <td style="text-align:center;">
                        <span class="badge" style="background:var(--navy-light);color:var(--navy);">
                            <?php echo e($division->employees_count); ?>

                        </span>
                    </td>
                    <td style="text-align:center;">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($division->is_active): ?>
                            <span class="badge badge-active">Active</span>
                        <?php else: ?>
                            <span class="badge badge-inactive">Inactive</span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </td>
                </tr>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                <tr>
                    <td colspan="6" style="text-align:center;padding:40px;color:var(--text-light);">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($search): ?>
                            No divisions matched "<strong><?php echo e($search); ?></strong>".
                        <?php else: ?>
                            No divisions found. Divisions are synced from the HRIS API.
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </td>
                </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($divisions->hasPages()): ?>
    <div style="padding:4px 20px 8px;">
        <?php echo e($divisions->links('pagination::custom')); ?>

    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Training_Ground\Visual Studio Code\DOLESYSTEM2\Dole_Payroll\Modules/Payroll\resources/views/divisions/index.blade.php ENDPATH**/ ?>