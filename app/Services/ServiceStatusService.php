<?php

namespace App\Services;

use App\Models\Service;
use App\Models\Incident;
use App\Models\ServiceStatusLog;
use App\Enums\ServiceStatus;
use App\Enums\IncidentSeverity;
use App\Events\ServiceStatusChanged;
use Illuminate\Support\Facades\DB;

class ServiceStatusService
{
    /**
     * Update service status based on incident severity
     */
    public function updateServiceStatusFromIncident(Incident $incident): void
    {
        // Ensure services are loaded
        if (!$incident->relationLoaded('services')) {
            $incident->load('services');
        }
        
        $services = $incident->services;
        
        foreach ($services as $service) {
            $newStatus = $this->determineServiceStatusFromSeverity($incident->severity);
            
            // Only update if the new status is worse than current status
            if ($this->isStatusWorse($newStatus, ServiceStatus::from($service->status))) {
                $oldStatus = $service->status;
                
                            $service->update([
                'status' => $newStatus->value,
                'status_message' => "Status changed due to incident: {$incident->title}",
            ]);
                
                // Log the status change
                ServiceStatusLog::logStatusChange(
                    $service,
                    $oldStatus,
                    $newStatus->value,
                    $incident->created_by,
                    "Status changed due to incident: {$incident->title}"
                );
                
                // Broadcast the status change
                event(new ServiceStatusChanged($service, $oldStatus, $newStatus->value));
            }
        }
    }
    
    /**
     * Recalculate service status based on all active incidents
     */
    public function recalculateServiceStatus(Service $service): void
    {
        $oldStatus = $service->status;
        
        // Get all active incidents for this service
        $activeIncidents = $service->incidents()
            ->whereIn('status', ['investigating', 'identified', 'monitoring'])
            ->get();
        
        if ($activeIncidents->isEmpty()) {
            // No active incidents, set to operational
            $newStatus = ServiceStatus::OPERATIONAL;
            $message = 'All incidents resolved';
        } else {
            // Find the worst severity among active incidents
            $worstSeverity = $activeIncidents->max('severity');
            $newStatus = $this->determineServiceStatusFromSeverity($worstSeverity);
            $message = "Status based on {$activeIncidents->count()} active incident(s)";
        }
        
        // Only update if status changed
        if ($newStatus->value !== $oldStatus) {
            $service->update([
                'status' => $newStatus->value,
                'status_message' => $message,
            ]);
            
                            // Log the status change
                ServiceStatusLog::logStatusChange(
                    $service,
                    $oldStatus,
                    $newStatus->value,
                    null,
                    $message
                );
            
                            // Broadcast the status change
                event(new ServiceStatusChanged($service, $oldStatus, $newStatus->value));
        }
    }
    
    /**
     * Determine service status from incident severity
     */
    private function determineServiceStatusFromSeverity(string $severity): ServiceStatus
    {
        return match ($severity) {
            IncidentSeverity::CRITICAL->value,
            IncidentSeverity::HIGH->value => ServiceStatus::MAJOR_OUTAGE,
            
            IncidentSeverity::MEDIUM->value => ServiceStatus::PARTIAL_OUTAGE,
            
            IncidentSeverity::LOW->value => ServiceStatus::DEGRADED,
            
            default => ServiceStatus::OPERATIONAL,
        };
    }
    
    /**
     * Check if one status is worse than another
     */
    private function isStatusWorse(ServiceStatus $newStatus, ServiceStatus $currentStatus): bool
    {
        $severityOrder = [
            ServiceStatus::OPERATIONAL->value => 0,
            ServiceStatus::DEGRADED->value => 1,
            ServiceStatus::PARTIAL_OUTAGE->value => 2,
            ServiceStatus::MAJOR_OUTAGE->value => 3,
        ];
        
        return $severityOrder[$newStatus->value] > $severityOrder[$currentStatus->value];
    }
    
    /**
     * Update service status when incident is resolved
     */
    public function handleIncidentResolved(Incident $incident): void
    {
        // Ensure services are loaded
        if (!$incident->relationLoaded('services')) {
            $incident->load('services');
        }
        
        $services = $incident->services;
        
        foreach ($services as $service) {
            $this->recalculateServiceStatus($service);
        }
    }
    
    /**
     * Update service status when incident is updated
     */
    public function handleIncidentUpdated(Incident $incident): void
    {
        // Ensure services are loaded
        if (!$incident->relationLoaded('services')) {
            $incident->load('services');
        }
        
        $services = $incident->services;
        
        foreach ($services as $service) {
            $this->recalculateServiceStatus($service);
        }
    }
    
    /**
     * Bulk update all services status based on their active incidents
     */
    public function recalculateAllServicesStatus(int $organizationId): void
    {
        $services = Service::where('organization_id', $organizationId)->get();
        
        foreach ($services as $service) {
            $this->recalculateServiceStatus($service);
        }
    }
} 