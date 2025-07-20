<?php

namespace App\Listeners;

use App\Events\ServiceCreated;
use App\Models\ServiceStatusLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LogServiceCreation
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ServiceCreated $event): void
    {
        try {
            $service = $event->service;
            
            // Create initial status log entry
            ServiceStatusLog::logStatusChange(
                service: $service,
                fromStatus: null, // No previous status since this is creation
                toStatus: $service->status,
                changedBy: Auth::id(),
                reason: 'Service created'
            );
            
            Log::info('Initial service status logged', [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'initial_status' => $service->status,
                'created_by' => Auth::id(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log initial service status', [
                'service_id' => $event->service?->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
} 