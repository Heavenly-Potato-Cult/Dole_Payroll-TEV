<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Payroll\Models\PayrollBatch;
use Modules\Payroll\Policies\PayrollPolicy;
use Illuminate\Support\Facades\Blade;


class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });
        Gate::policy(PayrollBatch::class, PayrollPolicy::class);
    }
}