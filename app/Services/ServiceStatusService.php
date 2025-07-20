<?php

namespace App\Services;

use App\Models\Service;
use App\Models\Incident;
use App\Models\IncidentUpdate;
use App\Models\ServiceStatusLog;
use App\Enums\ServiceStatus;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
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
     * Handle when an incident is updated (status, severity, or other changes)
     */
    public function handleIncidentUpdated(Incident $incident, array $changes = []): void
    {
        // If no changes provided, assume all aspects might have changed
        $hasStatusChange = empty($changes) || isset($changes['status']);
        $hasSeverityChange = empty($changes) || isset($changes['severity']);
        
        // Only recalculate service status if meaningful changes occurred
        if ($hasStatusChange || $hasSeverityChange) {
            // Ensure services are loaded
            if (!$incident->relationLoaded('services')) {
                $incident->load('services');
            }
            
            $services = $incident->services;
            
            foreach ($services as $service) {
                $this->recalculateServiceStatus($service);
            }
        }
    }
    
    /**
     * Handle when an incident update is created
     */
    public function handleIncidentUpdateCreated(IncidentUpdate $incidentUpdate): void
    {
        // Load the incident with services
        $incident = $incidentUpdate->incident()->with('services')->first();
        
        if (!$incident) {
            return;
        }
        
        // Check if the update's status is different from current incident status
        $incidentStatus = $incident->status;
        $updateStatus = $incidentUpdate->status;
        
        // If status is different, this update will change the incident status
        if ($incidentStatus !== $updateStatus) {
            // The incident status should be updated to match the update
            // But that's handled in the controller, so we just need to recalculate services
            // based on what the status will become
            
            if ($updateStatus === IncidentStatus::RESOLVED->value) {
                // If this update resolves the incident, handle resolution
                foreach ($incident->services as $service) {
                    $this->recalculateServiceStatus($service);
                }
            } else {
                // For other status changes, recalculate based on all incidents
                foreach ($incident->services as $service) {
                    $this->recalculateServiceStatus($service);
                }
            }
        }
    }
    
    /**
     * Handle when an incident's severity changes
     */
    public function handleIncidentSeverityChanged(Incident $incident, string $oldSeverity, string $newSeverity): void
    {
        // Ensure services are loaded
        if (!$incident->relationLoaded('services')) {
            $incident->load('services');
        }
        
        $services = $incident->services;
        $oldServiceStatus = $this->determineServiceStatusFromSeverity($oldSeverity);
        $newServiceStatus = $this->determineServiceStatusFromSeverity($newSeverity);
        
        // If severity change affects service status, recalculate
        if ($oldServiceStatus !== $newServiceStatus) {
            foreach ($services as $service) {
                $this->recalculateServiceStatus($service);
            }
        }
    }
    
    /**
     * Recalculate service status based on all active incidents
     */
    public function recalculateServiceStatus(Service $service): void
    {
        $oldStatus = $service->status;
        
        // Get all active incidents for this service using enum values
        $activeIncidents = $service->incidents()
            ->whereIn('status', [
                IncidentStatus::INVESTIGATING->value,
                IncidentStatus::IDENTIFIED->value,
                IncidentStatus::MONITORING->value
            ])
            ->get();
        
        if ($activeIncidents->isEmpty()) {
            // No active incidents, set to operational
            $newStatus = ServiceStatus::OPERATIONAL;
            $message = 'All incidents resolved';
        } else {
            // Find the highest severity among active incidents
            $highestSeverity = $this->getHighestSeverityFromIncidents($activeIncidents);
            $newStatus = $this->determineServiceStatusFromSeverity($highestSeverity);
            $message = "Status based on {$activeIncidents->count()} active incident(s) with highest severity: {$highestSeverity}";
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
     * Get the highest severity from a collection of incidents
     */
    private function getHighestSeverityFromIncidents($incidents): string
    {
        $severityRanking = $this->getSeverityRanking();
        $highestRank = -1;
        $highestSeverity = IncidentSeverity::LOW->value;
        
        foreach ($incidents as $incident) {
            $rank = $severityRanking[$incident->severity] ?? -1;
            if ($rank > $highestRank) {
                $highestRank = $rank;
                $highestSeverity = $incident->severity;
            }
        }
        
        return $highestSeverity;
    }
    
    /**
     * Get severity ranking for comparison (higher number = more severe)
     */
    private function getSeverityRanking(): array
    {
        return [
            IncidentSeverity::LOW->value => 1,
            IncidentSeverity::MEDIUM->value => 2,
            IncidentSeverity::HIGH->value => 3,
            IncidentSeverity::CRITICAL->value => 4,
        ];
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
     * Comprehensive incident change handler
     * Determines what changed and handles accordingly
     */
    public function handleIncidentChanges(Incident $incident, array $originalAttributes = []): void
    {
        $currentAttributes = $incident->getAttributes();
        $changes = [];
        
        // Detect what changed
        foreach (['status', 'severity'] as $attribute) {
            if (isset($originalAttributes[$attribute]) && 
                $originalAttributes[$attribute] !== $currentAttributes[$attribute]) {
                $changes[$attribute] = [
                    'old' => $originalAttributes[$attribute],
                    'new' => $currentAttributes[$attribute]
                ];
            }
        }
        
        if (empty($changes)) {
            return; // No meaningful changes for service status
        }
        
        // Handle specific changes
        if (isset($changes['status'])) {
            $oldStatus = $changes['status']['old'];
            $newStatus = $changes['status']['new'];
            
            // If resolved, handle resolution
            if ($newStatus === IncidentStatus::RESOLVED->value) {
                $this->handleIncidentResolved($incident);
                return;
            }
            
            // If changed from resolved to active, or between active statuses
            if ($oldStatus === IncidentStatus::RESOLVED->value || 
                in_array($newStatus, [
                    IncidentStatus::INVESTIGATING->value,
                    IncidentStatus::IDENTIFIED->value,
                    IncidentStatus::MONITORING->value
                ])) {
                $this->handleIncidentUpdated($incident, $changes);
                return;
            }
        }
        
        // Handle severity changes
        if (isset($changes['severity'])) {
            $this->handleIncidentSeverityChanged(
                $incident, 
                $changes['severity']['old'], 
                $changes['severity']['new']
            );
            return;
        }
        
        // Fallback: general update handling
        $this->handleIncidentUpdated($incident, $changes);
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