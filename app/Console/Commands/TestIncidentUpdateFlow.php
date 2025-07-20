<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Organization;
use App\Models\Service;
use App\Models\Incident;
use App\Models\IncidentUpdate;
use App\Models\User;
use App\Enums\ServiceStatus;
use App\Enums\IncidentStatus;
use App\Enums\IncidentSeverity;

class TestIncidentUpdateFlow extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'test:incident-update-flow {organization_id?}';

    /**
     * The console command description.
     */
    protected $description = 'Test incident update flow and service status changes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $organizationId = $this->argument('organization_id') ?? 1;
        
        $organization = Organization::find($organizationId);
        if (!$organization) {
            $this->error("Organization with ID {$organizationId} not found");
            return 1;
        }
        
        $this->info("Testing incident update flow for organization: {$organization->name}");
        
        // Get or create a test service
        $service = Service::where('organization_id', $organizationId)
            ->where('name', 'Test Service')
            ->first();
            
        if (!$service) {
            $service = Service::create([
                'organization_id' => $organizationId,
                'name' => 'Test Service',
                'description' => 'Service for testing incident updates',
                'status' => ServiceStatus::OPERATIONAL->value,
                'visibility' => 'public',
                'created_by' => 1,
            ]);
            $this->info("Created test service: {$service->name}");
        }
        
        $this->info("Initial service status: {$service->status}");
        
        // Create a test incident
        $incident = Incident::create([
            'organization_id' => $organizationId,
            'service_id' => $service->id,
            'title' => 'Test Incident for Update Flow',
            'description' => 'Testing incident update service status changes',
            'status' => IncidentStatus::INVESTIGATING->value,
            'severity' => IncidentSeverity::HIGH->value,
            'created_by' => 1,
        ]);
        
        // Attach service to incident
        $incident->services()->attach($service->id);
        $incident->load('services');
        
        $this->info("Created incident: {$incident->title} (Status: {$incident->status}, Severity: {$incident->severity})");
        
        // Trigger service status update
        $statusService = app(\App\Services\ServiceStatusService::class);
        $statusService->updateServiceStatusFromIncident($incident);
        
        $service->refresh();
        $this->info("Service status after incident creation: {$service->status}");
        
        // Create an incident update that changes status to resolved
        $this->info("\nCreating incident update with 'resolved' status...");
        
        $originalStatus = $incident->status;
        
        $incidentUpdate = $incident->updates()->create([
            'description' => 'Issue has been resolved',
            'status' => IncidentStatus::RESOLVED->value,
            'created_by' => 1,
        ]);
        
        // Update incident status
        $incident->update(['status' => IncidentStatus::RESOLVED->value]);
        $incident->refresh();
        
        $this->info("Incident status changed from '{$originalStatus}' to '{$incident->status}'");
        
        // Handle service status changes
        if ($originalStatus !== $incident->status) {
            if ($incident->status === IncidentStatus::RESOLVED->value) {
                $incident->update(['resolved_by' => 1, 'resolved_at' => now()]);
                $statusService->handleIncidentResolved($incident);
            } else {
                $changes = [
                    'status' => [
                        'old' => $originalStatus,
                        'new' => $incident->status
                    ]
                ];
                $statusService->handleIncidentUpdated($incident, $changes);
            }
        }
        
        $service->refresh();
        $this->info("Service status after incident resolution: {$service->status}");
        
        // Clean up
        $this->info("\nCleaning up test data...");
        $incident->delete();
        $this->info("Test completed successfully!");
        
        return 0;
    }
} 