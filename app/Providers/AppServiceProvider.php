<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Event;
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
use App\Events\ServiceStatusChanged;
use App\Events\ServiceCreated;
use App\Listeners\LogServiceStatusChange;
use App\Listeners\LogServiceCreation;

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
        // Fix for MySQL 5.7+ and MariaDB 10.2+
        Schema::defaultStringLength(191);

        if (config('app.env') === 'production') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Register policies
        Gate::policy(Service::class, ServicePolicy::class);
        Gate::policy(Incident::class, IncidentPolicy::class);
        Gate::policy(Maintenance::class, MaintenancePolicy::class);
        Gate::policy(Organization::class, OrganizationPolicy::class);
        Gate::policy(IncidentUpdate::class, IncidentUpdatePolicy::class);

        // Register event listeners
        Event::listen(ServiceStatusChanged::class, LogServiceStatusChange::class);
        Event::listen(ServiceCreated::class, LogServiceCreation::class);

        // Ensure system admin exists on application boot
        $this->ensureSystemAdminExists();
    }

    /**
     * Ensure a system admin exists in the application.
     */
    protected function ensureSystemAdminExists(): void
    {
        try {
            // Only run this if we're not in console or if we're running migrations
            if (app()->runningInConsole() && !app()->runningUnitTests()) {
                return;
            }

            // Check if we have any users in the database
            if (!\App\Models\User::exists()) {
                return;
            }

            // Use the service to ensure system admin exists
            $adminService = app(\App\Services\SystemAdminService::class);
            $admin = $adminService->ensureExists();

            if ($admin) {
                Log::info("System admin ensured: {$admin->email}");
            }
        } catch (\Exception $e) {
            Log::error('Failed to ensure system admin exists: ' . $e->getMessage());
        }
    }
}
