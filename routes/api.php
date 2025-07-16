<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StatusController as ApiStatusController;
use App\Http\Controllers\Api\ServiceController as ApiServiceController;
use App\Http\Controllers\Api\IncidentController as ApiIncidentController;
use App\Http\Controllers\Api\MaintenanceController as ApiMaintenanceController;

// Public API routes (no authentication)
Route::prefix('v1')->group(function () {
    Route::get('/status/{organization:slug}', [ApiStatusController::class, 'show']);
    Route::get('/status/{organization:slug}/services', [ApiStatusController::class, 'services']);
    Route::get('/status/{organization:slug}/incidents', [ApiStatusController::class, 'incidents']);
});

// Authenticated API routes
Route::middleware(['auth:sanctum', 'organization.context'])->prefix('v1')->group(function () {
    Route::apiResource('services', ApiServiceController::class);
    Route::apiResource('incidents', ApiIncidentController::class);
    Route::apiResource('maintenance', ApiMaintenanceController::class);
    // Special endpoints
    Route::post('/services/{service}/status', [ApiServiceController::class, 'updateStatus']);
    Route::post('/incidents/{incident}/resolve', [ApiIncidentController::class, 'resolve']);
}); 