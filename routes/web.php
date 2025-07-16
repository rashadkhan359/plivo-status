<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\IncidentUpdateController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\PublicStatusController;
use App\Http\Controllers\SettingsController;

// Public status page routes (no authentication required)
Route::get('/status/{organization:slug}', [PublicStatusController::class, 'show'])->name('status.public');
Route::get('/status/{organization:slug}/api', [PublicStatusController::class, 'api'])->name('status.api');

// Authenticated routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Organization context routes
    Route::middleware(['organization.context'])->group(function () {
        // Services
        Route::resource('services', ServiceController::class);
        // Incidents
        Route::resource('incidents', IncidentController::class);
        Route::post('/incidents/{incident}/updates', [IncidentUpdateController::class, 'store'])->name('incidents.updates.store');
        // Maintenance
        Route::resource('maintenance', MaintenanceController::class);
        // Settings
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    });
});

// Auth routes (Breeze)
require __DIR__.'/auth.php';
require __DIR__.'/settings.php';
