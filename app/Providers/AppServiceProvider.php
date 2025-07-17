<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use App\Models\Service;
use App\Policies\ServicePolicy;
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
    }
}
