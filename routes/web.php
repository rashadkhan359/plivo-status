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

// Temporary debug route - REMOVE AFTER DEBUGGING
Route::get('/debug-env', function () {
    if (app()->environment('production')) {
        return response()->json([
            'error' => 'Debug route disabled in production'
        ]);
    }
    
    return response()->json([
        'app_url' => config('app.url'),
        'app_env' => config('app.env'),
        'mail_mailer' => config('mail.default'),
        'mail_host' => config('mail.mailers.smtp.host'),
        'mail_port' => config('mail.mailers.smtp.port'),
        'mail_encryption' => config('mail.mailers.smtp.scheme'),
        'mail_username_set' => !empty(config('mail.mailers.smtp.username')),
        'mail_password_set' => !empty(config('mail.mailers.smtp.password')),
        'queue_connection' => config('queue.default'),
        'broadcast_connection' => config('broadcasting.default'),
        'pusher_app_id_set' => !empty(config('broadcasting.connections.pusher.app_id')),
        'pusher_app_key_set' => !empty(config('broadcasting.connections.pusher.key')),
        'pusher_app_secret_set' => !empty(config('broadcasting.connections.pusher.secret')),
        'pusher_cluster_set' => !empty(config('broadcasting.connections.pusher.options.cluster')),
        'session_secure' => config('session.secure'),
        'vite_pusher_key' => env('VITE_PUSHER_APP_KEY'),
        'vite_pusher_cluster' => env('VITE_PUSHER_APP_CLUSTER'),
    ]);
});

// Temporary log viewer - REMOVE AFTER DEBUGGING
Route::get('/logs', function () {
    $logFiles = [
        'laravel' => storage_path('logs/laravel.log'),
        'queue' => storage_path('logs/laravel-queue.log'),
        'mail' => storage_path('logs/laravel-mail.log'),
    ];
    
    $requestedFile = request('file', 'laravel');
    $logFile = $logFiles[$requestedFile] ?? $logFiles['laravel'];
    
    if (!file_exists($logFile)) {
        return response()->json([
            'error' => 'Log file not found',
            'requested_file' => $requestedFile,
            'path' => $logFile,
            'available_files' => array_keys($logFiles)
        ]);
    }
    
    // Get last 100 lines of the log file
    $lines = [];
    $file = new SplFileObject($logFile);
    $file->seek(PHP_INT_MAX);
    $totalLines = $file->key();
    
    $startLine = max(0, $totalLines - 100);
    $file->seek($startLine);
    
    while (!$file->eof()) {
        $line = $file->current();
        if (trim($line) !== '') {
            $lines[] = trim($line);
        }
        $file->next();
    }
    
    // Filter logs if search term provided
    $search = request('search');
    if ($search) {
        $lines = array_filter($lines, function($line) use ($search) {
            return stripos($line, $search) !== false;
        });
        $lines = array_values($lines); // Re-index array
    }
    
    return response()->json([
        'log_file' => $logFile,
        'requested_file' => $requestedFile,
        'total_lines' => $totalLines,
        'showing_last' => count($lines),
        'search' => $search,
        'available_files' => array_keys($logFiles),
        'lines' => $lines
    ]);
});

// Temporary log viewer with HTML output for easier reading
Route::get('/logs/html', function () {
    $logFiles = [
        'laravel' => storage_path('logs/laravel.log'),
        'queue' => storage_path('logs/laravel-queue.log'),
        'mail' => storage_path('logs/laravel-mail.log'),
    ];
    
    $requestedFile = request('file', 'laravel');
    $logFile = $logFiles[$requestedFile] ?? $logFiles['laravel'];
    
    if (!file_exists($logFile)) {
        return response()->json([
            'error' => 'Log file not found',
            'requested_file' => $requestedFile,
            'path' => $logFile,
            'available_files' => array_keys($logFiles)
        ]);
    }
    
    // Get last 100 lines of the log file
    $lines = [];
    $file = new SplFileObject($logFile);
    $file->seek(PHP_INT_MAX);
    $totalLines = $file->key();
    
    $startLine = max(0, $totalLines - 100);
    $file->seek($startLine);
    
    while (!$file->eof()) {
        $line = $file->current();
        if (trim($line) !== '') {
            $lines[] = trim($line);
        }
        $file->next();
    }
    
    // Filter logs if search term provided
    $search = request('search');
    if ($search) {
        $lines = array_filter($lines, function($line) use ($search) {
            return stripos($line, $search) !== false;
        });
        $lines = array_values($lines);
    }
    
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <title>Log Viewer - ' . $requestedFile . '</title>
        <style>
            body { font-family: monospace; font-size: 12px; margin: 20px; background: #1a1a1a; color: #e0e0e0; }
            .header { background: #333; padding: 10px; margin-bottom: 20px; border-radius: 5px; }
            .log-line { padding: 2px 5px; border-bottom: 1px solid #333; }
            .log-line:hover { background: #2a2a2a; }
            .error { color: #ff6b6b; }
            .warning { color: #ffd93d; }
            .info { color: #6bcf7f; }
            .debug { color: #4dabf7; }
            .search { margin-bottom: 20px; }
            .search input { padding: 5px; width: 300px; background: #333; color: #e0e0e0; border: 1px solid #555; }
            .file-selector { margin-bottom: 20px; }
            .file-selector select { padding: 5px; background: #333; color: #e0e0e0; border: 1px solid #555; }
        </style>
    </head>
    <body>
        <div class="header">
            <h2>Log Viewer - ' . $requestedFile . '</h2>
            <p>Showing last ' . count($lines) . ' lines (of ' . $totalLines . ' total)</p>
        </div>
        
        <div class="file-selector">
            <form method="GET">
                <label>Log File: </label>
                <select name="file" onchange="this.form.submit()">';
    
    foreach ($logFiles as $name => $path) {
        $selected = ($name === $requestedFile) ? 'selected' : '';
        $html .= '<option value="' . $name . '" ' . $selected . '>' . $name . '</option>';
    }
    
    $html .= '</select>
            </form>
        </div>
        
        <div class="search">
            <form method="GET">
                <input type="hidden" name="file" value="' . $requestedFile . '">
                <label>Search: </label>
                <input type="text" name="search" value="' . htmlspecialchars($search ?? '') . '" placeholder="Search logs...">
                <input type="submit" value="Search">
                <a href="?file=' . $requestedFile . '">Clear</a>
            </form>
        </div>
        
        <div class="logs">';
    
    foreach ($lines as $line) {
        $class = '';
        if (stripos($line, 'ERROR') !== false || stripos($line, 'CRITICAL') !== false) {
            $class = 'error';
        } elseif (stripos($line, 'WARNING') !== false) {
            $class = 'warning';
        } elseif (stripos($line, 'INFO') !== false) {
            $class = 'info';
        } elseif (stripos($line, 'DEBUG') !== false) {
            $class = 'debug';
        }
        
        $html .= '<div class="log-line ' . $class . '">' . htmlspecialchars($line) . '</div>';
    }
    
    $html .= '</div>
    </body>
    </html>';
    
    return $html;
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
