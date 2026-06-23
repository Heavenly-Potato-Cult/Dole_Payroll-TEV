<?php $__env->startSection('title', 'Allowance Types'); ?>
<?php $__env->startSection('page-title', 'Configuration'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <div class="page-header-left">
        <h1>Allowance Types</h1>
        <p>Define allowance line items used in employee enrollments, batches, and payslips.</p>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="<?php echo e(route('payroll.allowances.index')); ?>" class="btn btn-outline">Allowance Batches</a>
        <a href="<?php echo e(route('payroll.allowances.types.create')); ?>" class="btn btn-primary">+ New Type</a>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        <table class="table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <tr>
                    <td><?php echo e($type->display_order); ?></td>
                    <td><code><?php echo e($type->code); ?></code></td>
                    <td><?php echo e($type->name); ?></td>
                    <td><?php echo e($type->is_active ? 'Active' : 'Inactive'); ?></td>
                    <td style="white-space:nowrap;text-align:right;">
                        <a href="<?php echo e(route('payroll.allowances.types.edit', $type)); ?>" class="btn btn-sm btn-outline">Edit</a>
                        <form method="POST" action="<?php echo e(route('payroll.allowances.types.toggle', $type)); ?>" style="display:inline;"><?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                            <button class="btn btn-sm btn-outline"><?php echo e($type->is_active ? 'Deactivate' : 'Activate'); ?></button>
                        </form>
                    </td>
                </tr>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                <tr><td colspan="5" style="text-align:center;padding:24px;">No allowance types yet. Run the seeder or create one.</td></tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Training_Ground\Visual Studio Code\DOLESYSTEM2\Dole_Payroll\Modules/Payroll\resources/views/allowances/types/index.blade.php ENDPATH**/ ?>