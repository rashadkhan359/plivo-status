<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Organization;
use App\Models\Service;
use App\Models\Incident;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use App\Enums\ServiceStatus;
use App\Services\ServiceStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceStatusFromIncidentIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_status_changes_when_incident_created_directly()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $service = Service::factory()->for($organization)->create([
            'status' => ServiceStatus::OPERATIONAL,
        ]);

        // Create incident directly
        $incident = Incident::factory()->for($organization)->create([
            'status' => IncidentStatus::INVESTIGATING->value,
            'severity' => IncidentSeverity::CRITICAL->value,
            'created_by' => $user->id,
        ]);

        // Attach service to incident
        $incident->services()->attach($service->id);
        $incident->load('services');

        // Call the service directly
        $statusService = new ServiceStatusService();
        $statusService->updateServiceStatusFromIncident($incident);

        $service->refresh();
        $this->assertEquals(ServiceStatus::MAJOR_OUTAGE->value, $service->status);
    }

    public function test_service_status_changes_when_incident_created_via_controller()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $service = Service::factory()->for($organization)->create([
            'status' => ServiceStatus::OPERATIONAL,
        ]);

        // Associate user with organization and set permissions
        $organization->users()->attach($user->id, ['role' => 'admin', 'is_active' => true]);
        $permissionService = app(\App\Services\PermissionService::class);
        $permissionService->assignDefaultOrganizationPermissions($user, $organization, 'admin');

        // Create incident using the controller logic
        $incident = $organization->incidents()->create([
            'service_id' => $service->id,
            'title' => 'Test Incident',
            'description' => 'Test Description',
            'status' => IncidentStatus::INVESTIGATING->value,
            'severity' => IncidentSeverity::CRITICAL->value,
            'created_by' => $user->id,
        ]);

        // Attach services to incident
        $incident->services()->attach([$service->id]);
        $incident->load('services');

        // Update service statuses based on incident severity
        $statusService = app(\App\Services\ServiceStatusService::class);
        $statusService->updateServiceStatusFromIncident($incident);

        $service->refresh();
        $this->assertEquals(ServiceStatus::MAJOR_OUTAGE->value, $service->status);
    }
} 