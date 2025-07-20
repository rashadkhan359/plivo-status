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
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\Admin\OrganizationController;

use App\Http\Controllers\TeamController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EnsureUserIsAdmin;

// Health check for Render
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()]);
});

// Homepage (landing/marketing)
Route::get('/', [HomeController::class, 'index'])->name('home');

// Public status page routes (no authentication required)
Route::get('/status/{organization:slug}', [PublicStatusController::class, 'show'])->name('status.public');

// Invitation routes (no authentication required)
Route::get('/invitation/{token}', [InvitationController::class, 'show'])->name('invitation.show');
Route::post('/invitation/{token}', [InvitationController::class, 'accept'])->name('invitation.accept');

// Authenticated routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Organization context routes (including dashboard)
    Route::middleware(['organization.context'])->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        // Services
        Route::resource('services', ServiceController::class);
        Route::patch('services/{service}/status', [ServiceController::class, 'updateStatus'])->name('services.status');
        
        // Incidents
        Route::resource('incidents', IncidentController::class);
        Route::patch('incidents/{incident}/resolve', [IncidentController::class, 'resolve'])->name('incidents.resolve');
        Route::get('incidents/{incident}/updates', [IncidentUpdateController::class, 'index'])->name('incidents.updates.index');
        Route::post('incidents/{incident}/updates', [IncidentUpdateController::class, 'store'])->name('incidents.updates.store');
        
        // Maintenance
        Route::resource('maintenances', MaintenanceController::class);
        Route::patch('maintenances/{maintenance}/start', [MaintenanceController::class, 'start'])->name('maintenances.start');
        Route::patch('maintenances/{maintenance}/complete', [MaintenanceController::class, 'complete'])->name('maintenances.complete');
        
        // Teams - specific routes must come before resource routes
        Route::get('teams/available-permissions', [TeamController::class, 'getAvailablePermissions'])->name('teams.available-permissions');
        Route::resource('teams', TeamController::class);
        Route::post('teams/{team}/join', [TeamController::class, 'join'])->name('teams.join');
        Route::delete('teams/{team}/leave', [TeamController::class, 'leave'])->name('teams.leave');
        Route::post('teams/{team}/members', [TeamController::class, 'addMember'])->name('teams.members.add');
        Route::delete('teams/{team}/members/{user}', [TeamController::class, 'removeMember'])->name('teams.members.remove');
        Route::patch('teams/{team}/members/{user}/role', [TeamController::class, 'updateMemberRole'])->name('teams.members.role');
        Route::patch('teams/{team}/permissions', [TeamController::class, 'updateRolePermissions'])->name('teams.permissions');
        Route::patch('teams/{team}/services', [TeamController::class, 'updateServices'])->name('teams.services');
        
        
        // Settings
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    });
});


// Admin-only routes (no organization context needed)
Route::middleware(['auth', EnsureUserIsAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('organizations', OrganizationController::class)->only(['index', 'show']);
});

// Auth routes (Breeze)
require __DIR__.'/auth.php';
require __DIR__.'/settings.php';
