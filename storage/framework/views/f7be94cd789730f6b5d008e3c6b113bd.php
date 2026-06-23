<?php $__env->startSection('title', 'Employee Allowances'); ?>
<?php $__env->startSection('page-title', 'Employees'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <div class="page-header-left">
        <h1><?php echo e($employee->full_name); ?></h1>
        <p>Standing allowance enrollments for this employee.</p>
    </div>
    <a href="<?php echo e(route('employees.show', $employee)); ?>" class="btn btn-outline">← Back to Profile</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?php echo e(route('payroll.employees.allowances.update', $employee)); ?>">
            <?php echo csrf_field(); ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Enabled</th>
                        <th>Allowance</th>
                        <th>Amount (₱)</th>
                        <th>Effectivity</th>
                        <th>Expiry</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <?php $enrollment = $enrollments->get($type->id); ?>
                    <tr>
                        <td>
                            <input type="checkbox" name="allowances[<?php echo e($type->id); ?>][enabled]" value="1"
                                <?php if(old("allowances.{$type->id}.enabled", (bool) $enrollment)): echo 'checked'; endif; ?>>
                        </td>
                        <td>
                            <strong><?php echo e($type->name); ?></strong><br>
                            <code style="font-size:0.75rem;"><?php echo e($type->code); ?></code>
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" name="allowances[<?php echo e($type->id); ?>][amount]"
                                   value="<?php echo e(old("allowances.{$type->id}.amount", $enrollment->amount ?? ($type->code === 'PERA' ? $employee->pera : ''))); ?>">
                        </td>
                        <td>
                            <input type="date" name="allowances[<?php echo e($type->id); ?>][effectivity_date]"
                                   value="<?php echo e(old("allowances.{$type->id}.effectivity_date", optional($enrollment?->effectivity_date)->toDateString() ?? now()->toDateString())); ?>">
                        </td>
                        <td>
                            <input type="date" name="allowances[<?php echo e($type->id); ?>][expiry_date]"
                                   value="<?php echo e(old("allowances.{$type->id}.expiry_date", optional($enrollment?->expiry_date)->toDateString())); ?>">
                        </td>
                        <td>
                            <input type="text" name="allowances[<?php echo e($type->id); ?>][remarks]"
                                   value="<?php echo e(old("allowances.{$type->id}.remarks", $enrollment->remarks ?? '')); ?>">
                        </td>
                    </tr>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </tbody>
            </table>
            <button type="submit" class="btn btn-primary" style="margin-top:16px;">Save Allowances</button>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Training_Ground\Visual Studio Code\DOLESYSTEM2\Dole_Payroll\Modules/Payroll\resources/views/allowances/employees/allowances.blade.php ENDPATH**/ ?>