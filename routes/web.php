<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\IncidentUpdateController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\PublicStatusController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\MaintenanceController as AdminMaintenanceController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EnsureUserIsAdmin;

// Homepage (landing/marketing)
Route::get('/', [HomeController::class, 'index'])->name('home');

// Public status page routes (no authentication required)
Route::get('/status/{organization:slug}', [PublicStatusController::class, 'show'])->name('status.public');

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
        Route::patch('incidents/{incident}/resolve', [IncidentController::class, 'resolve'])->name('incidents.resolve');
        Route::get('incidents/{incident}/updates', [IncidentUpdateController::class, 'index'])->name('incidents.updates.index');
        Route::post('incidents/{incident}/updates', [IncidentUpdateController::class, 'store'])->name('incidents.updates.store');
        
        // Maintenance
        Route::resource('maintenances', MaintenanceController::class);
        Route::patch('maintenances/{maintenance}/start', [MaintenanceController::class, 'start'])->name('maintenances.start');
        Route::patch('maintenances/{maintenance}/complete', [MaintenanceController::class, 'complete'])->name('maintenances.complete');
        
        // Teams
        Route::resource('teams', TeamController::class);
        Route::post('teams/{team}/join', [TeamController::class, 'join'])->name('teams.join');
        Route::delete('teams/{team}/leave', [TeamController::class, 'leave'])->name('teams.leave');
        Route::post('teams/{team}/members', [TeamController::class, 'addMember'])->name('teams.members.add');
        Route::delete('teams/{team}/members/{user}', [TeamController::class, 'removeMember'])->name('teams.members.remove');
        Route::patch('teams/{team}/members/{user}/role', [TeamController::class, 'updateMemberRole'])->name('teams.members.role');
        
        // User Management (Admin/Owner only)
        Route::middleware(['can:viewAny,App\Models\User'])->group(function () {
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
            Route::post('/users', [UserController::class, 'store'])->name('users.store');
            Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.role');
            Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        });
        
        // Settings
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    });
});


// Admin-only routes
Route::middleware(['auth', 'organization.context', EnsureUserIsAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('organizations', OrganizationController::class)->only(['index', 'show']);
    Route::resource('maintenance', AdminMaintenanceController::class)->only(['index']);
});

// Auth routes (Breeze)
require __DIR__.'/auth.php';
require __DIR__.'/settings.php';
