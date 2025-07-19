<?php

namespace App\Listeners;

use App\Events\ServiceStatusChanged;
use App\Models\ServiceStatusLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LogServiceStatusChange
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
    public function handle(ServiceStatusChanged $event): void
    {
        try {
            $service = $event->service;
            
            // Get the previous status from the service's original attributes
            $previousStatus = $service->getOriginal('status');
            $currentStatus = $service->status;
            
            // Only log if status actually changed
            if ($previousStatus !== $currentStatus) {
                ServiceStatusLog::logStatusChange(
                    service: $service,
                    fromStatus: $previousStatus,
                    toStatus: $currentStatus,
                    changedBy: Auth::id(),
                    reason: 'Status updated via ' . (request()->route() ? 'web interface' : 'API')
                );
                
                Log::info('Service status change logged', [
                    'service_id' => $service?->id,
                    'service_name' => $service?->name,
                    'from_status' => $previousStatus,
                    'to_status' => $currentStatus,
                    'changed_by' => Auth::id(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to log service status change', [
                'service_id' => $event->service?->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
