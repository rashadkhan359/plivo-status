<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use App\Models\Service;
use App\Models\Incident;
use App\Models\Maintenance;
use App\Models\Organization;
use App\Models\IncidentUpdate;
use App\Policies\ServicePolicy;
use App\Policies\IncidentPolicy;
use App\Policies\MaintenancePolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\IncidentUpdatePolicy;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Service::class, ServicePolicy::class);
        Gate::policy(Incident::class, IncidentPolicy::class);
        Gate::policy(Maintenance::class, MaintenancePolicy::class);
        Gate::policy(Organization::class, OrganizationPolicy::class);
        Gate::policy(IncidentUpdate::class, IncidentUpdatePolicy::class);
    }
}
