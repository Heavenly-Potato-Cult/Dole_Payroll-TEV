<?php $__env->startSection('title', 'Sign In'); ?>

<?php $__env->startPush('styles'); ?>
<style>
.login-form {
    animation: fadeIn 0.3s ease-in-out;
}

.login-hint {
    margin-top: 12px;
    padding: 8px 12px;
    background: #f0f7ff;
    border-radius: 6px;
    text-align: center;
}

.login-hint small {
    color: #6b7280;
    font-size: 12px;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="login-page">

    
    <div class="login-grid-bg"></div>

    
    <div class="login-left">

        
        <div class="login-logo-row">
            <div class="login-logo-box">
                <img src="<?php echo e(asset('assets/img/dole_logo.png')); ?>" alt="DOLE Logo" class="login-logo-img">
            </div>
            <div class="login-logo-label">
                <span class="login-logo-abbr">DOLE — REGION IX</span>
                <span class="login-logo-city">Zamboanga Peninsula</span>
            </div>
        </div>

        
        <div class="login-headline">
            <h1>
                Payroll &amp; <br>
                <span class="login-headline-gold">Travel Expense</span><br>
                Management
            </h1>
            <p class="login-headline-sub">
                Centralized payroll processing and TEV workflow for<br>
                DOLE Regional Office IX — Zamboanga City.
            </p>
        </div>

        
        <ul class="login-features">
            <li>Semi-monthly payroll computation for 82 employees</li>
            <li>Automated GSIS, HDMF, PhilHealth remittance reports</li>
            <li>End-to-end TEV workflow with digital approval chain</li>
            <li>Role-based access: HR &rarr; Accountant &rarr; RD/ARD &rarr; Cashier</li>
        </ul>

    </div>

    
    <div class="login-right">
        <div class="login-card">

            <div class="login-card-header">
                <h2>Sign in</h2>
                <p>Choose your login type below.</p>
            </div>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('error')): ?>
                <div class="alert alert-error" style="margin-bottom:16px;">⚠ <?php echo e(session('error')); ?></div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($errors->any()): ?>
                <div class="alert alert-error" style="margin-bottom:16px;">⚠ <?php echo e($errors->first()); ?></div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <!-- Unified Login Form -->
            <form method="POST" action="<?php echo e(route('login.post')); ?>" autocomplete="off" class="login-form">
                <?php echo csrf_field(); ?>

                <div class="lf-group">
                    <label for="login_field">Employee ID or Email</label>
                    <input
                        type="text"
                        id="login_field"
                        name="login_field"
                        value="<?php echo e(old('employee_id') ?? old('email')); ?>"
                        placeholder="Employee ID (e.g. EMP001) or Email"
                        required
                        autofocus
                        class="<?php echo e($errors->has('employee_id') || $errors->has('email') ? 'is-invalid' : ''); ?>"
                    >
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['employee_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <div class="lf-group">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="••••••••"
                        required
                        class="<?php echo e($errors->has('password') ? 'is-invalid' : ''); ?>"
                    >
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <button type="submit" class="login-submit">Sign In</button>

                <!-- <div class="login-hint">
                    <small>Employees: Use your Employee ID (EMP001-EMP082) with password "pass123"<br>
                    Admins: Use your email address and assigned password</small>
                </div> -->
            </form>

            <div class="login-card-footer">
                Forgot your password? Contact the Super Admin for account assistance.
            </div>

        </div>
    </div>

</div>


<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Training_Ground\Visual Studio Code\DOLESYSTEM2\Dole_Payroll\resources\views/auth/login.blade.php ENDPATH**/ ?>