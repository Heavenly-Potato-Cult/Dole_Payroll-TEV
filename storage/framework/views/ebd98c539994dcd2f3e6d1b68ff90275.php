<?php $__env->startSection('title', 'Edit Employee'); ?>
<?php $__env->startSection('page-title', 'Edit Employee'); ?>

<?php $__env->startSection('styles'); ?>
<style>
.form-layout   { display: grid; grid-template-columns: 1fr 340px; gap: 20px; align-items: start; }
.name-grid     { display: grid; grid-template-columns: 1fr 1fr 1fr 80px; gap: 14px; }
.salary-grid   { display: grid; grid-template-columns: 120px 100px 140px 1fr; gap: 14px; align-items: end; }
.position-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.form-col      { display: flex; flex-direction: column; gap: 20px; }

@media (max-width: 900px) {
    .form-layout { grid-template-columns: 1fr; }
}
@media (max-width: 700px) {
    .name-grid     { grid-template-columns: 1fr 1fr; }
    .salary-grid   { grid-template-columns: 1fr 1fr; }
    .position-grid { grid-template-columns: 1fr; }
}
@media (max-width: 420px) {
    .name-grid { grid-template-columns: 1fr; }
}
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Edit Employee</h1>
        <p><?php echo e($employee->full_name); ?></p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?php echo e(route('employees.show', $employee)); ?>" class="btn btn-outline">← Profile</a>
        <a href="<?php echo e(route('employees.index')); ?>" class="btn btn-outline">← All</a>
    </div>
</div>

<form method="POST" action="<?php echo e(route('employees.update', $employee)); ?>" id="employeeForm">
<?php echo csrf_field(); ?>
<?php echo method_field('PUT'); ?>

<div class="form-layout">

    
    <div class="form-col">

        <div class="card">
            <div class="card-header"><h3>Personal Information</h3></div>
            <div class="card-body">
                <div class="name-grid">
                    <div class="form-group" style="margin-bottom:0;">
                        <label for="last_name">Last Name <span style="color:var(--red)">*</span></label>
                        <input type="text" id="last_name" name="last_name"
                               value="<?php echo e(old('last_name', $employee->last_name)); ?>"
                               class="<?php echo e($errors->has('last_name') ? 'is-invalid' : ''); ?>"
                               required maxlength="100" style="text-transform:uppercase;">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['last_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label for="first_name">First Name <span style="color:var(--red)">*</span></label>
                        <input type="text" id="first_name" name="first_name"
                               value="<?php echo e(old('first_name', $employee->first_name)); ?>"
                               class="<?php echo e($errors->has('first_name') ? 'is-invalid' : ''); ?>"
                               required maxlength="100">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['first_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label for="middle_name">Middle Name</label>
                        <input type="text" id="middle_name" name="middle_name"
                               value="<?php echo e(old('middle_name', $employee->middle_name)); ?>" maxlength="100">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label for="suffix">Suffix</label>
                        <input type="text" id="suffix" name="suffix"
                               value="<?php echo e(old('suffix', $employee->suffix)); ?>" maxlength="20">
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3>Position & Assignment</h3></div>
            <div class="card-body">
                <div class="form-group">
                    <label for="plantilla_item_no">Plantilla Item No. <span style="color:var(--red)">*</span></label>
                    <input type="text" id="plantilla_item_no" name="plantilla_item_no"
                           value="<?php echo e(old('plantilla_item_no', $employee->plantilla_item_no)); ?>"
                           class="<?php echo e($errors->has('plantilla_item_no') ? 'is-invalid' : ''); ?>"
                           required maxlength="100">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['plantilla_item_no'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
                <div class="position-grid">
                    <div class="form-group" style="margin-bottom:0;">
                        <label for="position_title">Position Title <span style="color:var(--red)">*</span></label>
                        <input type="text" id="position_title" name="position_title"
                               value="<?php echo e(old('position_title', $employee->position_title)); ?>"
                               class="<?php echo e($errors->has('position_title') ? 'is-invalid' : ''); ?>"
                               required maxlength="200">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['position_title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label for="division_id">Division <span style="color:var(--red)">*</span></label>
                        <select id="division_id" name="division_id"
                                class="<?php echo e($errors->has('division_id') ? 'is-invalid' : ''); ?>" required>
                            <option value="">— Select —</option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $divisions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $div): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <option value="<?php echo e($div->id); ?>"
                                    <?php echo e(old('division_id', $employee->division_id) == $div->id ? 'selected' : ''); ?>>
                                    <?php echo e($div->code); ?> — <?php echo e($div->name); ?>

                                </option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </select>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['division_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Salary</h3>
                <span class="text-muted" style="font-size:0.80rem;">Change SG/Step to re-lookup from SIT</span>
            </div>
            <div class="card-body">
                <div class="salary-grid">
                    <div class="form-group" style="margin-bottom:0;">
                        <label for="salary_grade">Salary Grade <span style="color:var(--red)">*</span></label>
                        <select id="salary_grade" name="salary_grade" required>
                            <option value="">—</option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php for($sg = 1; $sg <= 33; $sg++): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <option value="<?php echo e($sg); ?>"
                                    <?php echo e(old('salary_grade', $employee->salary_grade) == $sg ? 'selected' : ''); ?>>
                                    SG <?php echo e($sg); ?>

                                </option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endfor; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label for="step">Step <span style="color:var(--red)">*</span></label>
                        <select id="step" name="step" required>
                            <option value="">—</option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php for($s = 1; $s <= 8; $s++): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <option value="<?php echo e($s); ?>"
                                    <?php echo e(old('step', $employee->step) == $s ? 'selected' : ''); ?>>
                                    Step <?php echo e($s); ?>

                                </option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endfor; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label for="sit_year">SIT Year <span style="color:var(--red)">*</span></label>
                        <select id="sit_year" name="sit_year" required>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $sitYears; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $yr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <option value="<?php echo e($yr); ?>"
                                    <?php echo e(old('sit_year', $employee->sit_year) == $yr ? 'selected' : ''); ?>>
                                    CY <?php echo e($yr); ?>

                                </option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label for="basic_salary">
                            Basic Salary <span style="color:var(--red)">*</span>
                            <span id="sit_status" style="font-weight:400;font-size:0.76rem;color:var(--success);margin-left:6px;"></span>
                        </label>
                        <input type="hidden" id="basic_salary_raw" name="basic_salary"
                               value="<?php echo e(old('basic_salary', $employee->basic_salary)); ?>">
                        <input type="text" id="basic_salary"
                               value="<?php echo e(old('basic_salary', number_format($employee->basic_salary, 2))); ?>"
                               data-raw-amount="<?php echo e(old('basic_salary', $employee->basic_salary)); ?>"
                               class="<?php echo e($errors->has('basic_salary') ? 'is-invalid' : ''); ?>"
                               readonly style="background:var(--bg);font-family:monospace;">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['basic_salary'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>

                <div style="margin-top:14px;">
                    <label for="pera">PERA</label>
                    <input type="number" id="pera" name="pera"
                           value="<?php echo e(old('pera', $employee->pera)); ?>"
                           min="0" step="0.01" style="max-width:180px;">
                </div>

                <div style="margin-top:14px;background:var(--bg);border-radius:6px;
                             padding:12px 16px;font-size:0.80rem;color:var(--text-mid);
                             display:flex;gap:16px;flex-wrap:wrap;">
                    <span><strong>Daily:</strong> ₱<?php echo e(number_format($employee->daily_rate, 4)); ?></span>
                    <span><strong>Hourly:</strong> ₱<?php echo e(number_format($employee->hourly_rate, 4)); ?></span>
                </div>
            </div>
        </div>

    </div>

    
    <div class="form-col">

        <div class="card">
            <div class="card-header"><h3>Employment</h3></div>
            <div class="card-body">
                <div class="form-group">
                    <label for="hire_date">Hire / Appointment Date</label>
                    <input type="date" id="hire_date" name="hire_date"
                           value="<?php echo e(old('hire_date', $employee->hire_date?->format('Y-m-d'))); ?>">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label for="status">Status <span style="color:var(--red)">*</span></label>
                    <select id="status" name="status" required>
                        <option value="active"   <?php echo e(old('status', $employee->status) === 'active'   ? 'selected' : ''); ?>>Active</option>
                        <option value="inactive" <?php echo e(old('status', $employee->status) === 'inactive' ? 'selected' : ''); ?>>Inactive</option>
                        <option value="vacant"   <?php echo e(old('status', $employee->status) === 'vacant'   ? 'selected' : ''); ?>>Vacant</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3>Government IDs</h3></div>
            <div class="card-body">
                <div class="form-group">
                    <label for="tin">TIN</label>
                    <input type="text" id="tin" name="tin"
                           value="<?php echo e(old('tin', $employee->tin)); ?>" maxlength="50">
                </div>
                <div class="form-group">
                    <label for="gsis_bp_no">GSIS Number</label>
                    <input type="text" id="gsis_bp_no" name="gsis_bp_no"
                           value="<?php echo e(old('gsis_bp_no', $employee->gsis_bp_no)); ?>" maxlength="50">
                </div>
                <div class="form-group">
                    <label for="pagibig_no">Pag-IBIG Number</label>
                    <input type="text" id="pagibig_no" name="pagibig_no"
                           value="<?php echo e(old('pagibig_no', $employee->pagibig_no)); ?>" maxlength="50">
                </div>
                <div class="form-group">
                    <label for="pagibig_id">Pag-IBIG ID / RTN</label>
                    <input type="text" id="pagibig_id" name="pagibig_id"
                           value="<?php echo e(old('pagibig_id', $employee->pagibig_id)); ?>" maxlength="50">
                </div>
                <div class="form-group">
                    <label for="pagibig_mid_no">Pag-IBIG MID Number</label>
                    <input type="text" id="pagibig_mid_no" name="pagibig_mid_no"
                           value="<?php echo e(old('pagibig_mid_no', $employee->pagibig_mid_no)); ?>" maxlength="50">
                </div>
                <div class="form-group">
                    <label for="mp2_account_no">MP2 Account Number</label>
                    <input type="text" id="mp2_account_no" name="mp2_account_no"
                           value="<?php echo e(old('mp2_account_no', $employee->mp2_account_no)); ?>" maxlength="50">
                </div>
                <div class="form-group">
                    <label for="hdmf_mpl_app_no">HDMF MPL Application Number</label>
                    <input type="text" id="hdmf_mpl_app_no" name="hdmf_mpl_app_no"
                           value="<?php echo e(old('hdmf_mpl_app_no', $employee->hdmf_mpl_app_no)); ?>" maxlength="80">
                </div>
                <div class="form-group">
                    <label for="hdmf_cal_app_no">HDMF Calamity Application Number</label>
                    <input type="text" id="hdmf_cal_app_no" name="hdmf_cal_app_no"
                           value="<?php echo e(old('hdmf_cal_app_no', $employee->hdmf_cal_app_no)); ?>" maxlength="80">
                </div>
                <div class="form-group">
                    <label for="hdmf_housing_app_no">HDMF Housing Application Number</label>
                    <input type="text" id="hdmf_housing_app_no" name="hdmf_housing_app_no"
                           value="<?php echo e(old('hdmf_housing_app_no', $employee->hdmf_housing_app_no)); ?>" maxlength="80">
                </div>
                <div class="form-group">
                    <label for="philhealth_no">PhilHealth Number</label>
                    <input type="text" id="philhealth_no" name="philhealth_no"
                           value="<?php echo e(old('philhealth_no', $employee->philhealth_no)); ?>" maxlength="50">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label for="sss_no">SSS Number</label>
                    <input type="text" id="sss_no" name="sss_no"
                           value="<?php echo e(old('sss_no', $employee->sss_no)); ?>" maxlength="50">
                </div>
            </div>
        </div>

        <div class="card" style="background:var(--bg);">
            <div class="card-body" style="font-size:0.78rem;color:var(--text-light);">
                <strong style="color:var(--text-mid);">Created:</strong>
                <?php echo e($employee->created_at->format('M d, Y g:i A')); ?><br>
                <strong style="color:var(--text-mid);">Last updated:</strong>
                <?php echo e($employee->updated_at->format('M d, Y g:i A')); ?>

            </div>
        </div>

        <div style="display:flex;flex-direction:column;gap:10px;">
            <button type="submit" class="btn btn-primary btn-lg w-100">✓ Save Changes</button>
            <a href="<?php echo e(route('employees.show', $employee)); ?>" class="btn btn-outline w-100">Cancel</a>
        </div>

    </div>

</div>
</form>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script src="<?php echo e(asset('js/sit-lookup.js')); ?>"></script>
<script>
SITLookup.init({
    sgId     : 'salary_grade',
    stepId   : 'step',
    yearId   : 'sit_year',
    salaryId : 'basic_salary',
    statusId : 'sit_status',
    apiUrl   : '<?php echo e(route("api.sit")); ?>',
});

document.addEventListener('DOMContentLoaded', function () {
    const display = document.getElementById('basic_salary');
    const raw     = document.getElementById('basic_salary_raw');

    const observer = new MutationObserver(function () {
        if (display.dataset.rawAmount) raw.value = display.dataset.rawAmount;
    });
    observer.observe(display, { attributes: true, attributeFilter: ['data-raw-amount'] });

    display.addEventListener('change', function () {
        raw.value = this.dataset.rawAmount || this.value.replace(/,/g, '');
    });

    ['last_name', 'first_name', 'middle_name'].forEach(function (id) {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', function () {
            const p = this.selectionStart;
            this.value = this.value.toUpperCase();
            this.setSelectionRange(p, p);
        });
    });
});
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Training_Ground\Visual Studio Code\DOLESYSTEM2\Dole_Payroll\Modules/Payroll\resources/views/employees/edit.blade.php ENDPATH**/ ?>