<?php

namespace Modules\Tev\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Modules\Tev\Policies\TevPolicy;
use Modules\Tev\Policies\OfficeOrderPolicy;
use Modules\Tev\Models\TevRequest;
use App\SharedKernel\Models\OfficeOrder;

class TevServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Tev';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'tev';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    // protected array $commands = [];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    /**
     * Define module schedules.
     *
     * @param $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }

    /**
     * Register the TEV module's policies.
     */
    public function boot(): void
    {
        parent::boot();

        Gate::policy(TevRequest::class, TevPolicy::class);
        Gate::policy(OfficeOrder::class, OfficeOrderPolicy::class);
    }
}
