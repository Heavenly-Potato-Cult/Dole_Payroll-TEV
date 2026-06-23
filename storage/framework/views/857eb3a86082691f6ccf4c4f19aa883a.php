<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'Login'); ?> — DOLE RO9 Payroll</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="<?php echo e(asset('css/app.css')); ?>">
    <?php echo $__env->yieldContent('styles'); ?>
</head>
<body style="margin:0; padding:0; min-height:100vh;">
    <?php echo $__env->yieldContent('content'); ?>
</body>
</html>
<?php /**PATH C:\Training_Ground\Visual Studio Code\DOLESYSTEM2\Dole_Payroll\resources\views/layouts/auth.blade.php ENDPATH**/ ?>