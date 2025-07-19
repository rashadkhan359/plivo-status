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

class ServiceStatusFromIncidentTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_status_changes_when_critical_incident_created()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $service = Service::factory()->for($organization)->create([
            'status' => ServiceStatus::OPERATIONAL,
        ]);

        $organization->users()->attach($user->id, ['role' => 'admin', 'is_active' => true]);
        
        // Assign default permissions for admin role
        $permissionService = app(\App\Services\PermissionService::class);
        $permissionService->assignDefaultOrganizationPermissions($user, $organization, 'admin');

        // Create incident using the controller logic directly
        $incident = $organization->incidents()->create([
            'service_id' => $service->id,
            'title' => 'Critical Database Issue',
            'description' => 'Database is completely down',
            'status' => IncidentStatus::INVESTIGATING->value,
            'severity' => IncidentSeverity::CRITICAL->value,
            'created_by' => $user->id,
        ]);

        // Attach services to incident
        $incident->services()->attach([$service->id]);
        $incident->load('services');

        // Update service statuses based on incident severity
        $statusService = app(ServiceStatusService::class);
        $statusService->updateServiceStatusFromIncident($incident);

        $service->refresh();
        $this->assertEquals(ServiceStatus::MAJOR_OUTAGE->value, $service->status);
    }

    public function test_service_status_changes_when_high_incident_created()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $service = Service::factory()->for($organization)->create([
            'status' => ServiceStatus::OPERATIONAL,
        ]);

        $organization->users()->attach($user->id, ['role' => 'admin', 'is_active' => true]);
        
        // Assign default permissions for admin role
        $permissionService = app(\App\Services\PermissionService::class);
        $permissionService->assignDefaultOrganizationPermissions($user, $organization, 'admin');

        // Create incident using the controller logic directly
        $incident = $organization->incidents()->create([
            'service_id' => $service->id,
            'title' => 'High Priority Issue',
            'description' => 'Service experiencing major problems',
            'status' => IncidentStatus::INVESTIGATING->value,
            'severity' => IncidentSeverity::HIGH->value,
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

    public function test_service_status_changes_when_medium_incident_created()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $service = Service::factory()->for($organization)->create([
            'status' => ServiceStatus::OPERATIONAL,
        ]);

        $organization->users()->attach($user->id, ['role' => 'admin', 'is_active' => true]);
        
        // Assign default permissions for admin role
        $permissionService = app(\App\Services\PermissionService::class);
        $permissionService->assignDefaultOrganizationPermissions($user, $organization, 'admin');

        // Create incident using the controller logic directly
        $incident = $organization->incidents()->create([
            'service_id' => $service->id,
            'title' => 'Medium Priority Issue',
            'description' => 'Service experiencing partial issues',
            'status' => IncidentStatus::INVESTIGATING->value,
            'severity' => IncidentSeverity::MEDIUM->value,
            'created_by' => $user->id,
        ]);

        // Attach services to incident
        $incident->services()->attach([$service->id]);
        $incident->load('services');

        // Update service statuses based on incident severity
        $statusService = app(\App\Services\ServiceStatusService::class);
        $statusService->updateServiceStatusFromIncident($incident);

        $service->refresh();
        $this->assertEquals(ServiceStatus::PARTIAL_OUTAGE->value, $service->status);
    }

    public function test_service_status_changes_when_low_incident_created()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $service = Service::factory()->for($organization)->create([
            'status' => ServiceStatus::OPERATIONAL,
        ]);

        $organization->users()->attach($user->id, ['role' => 'admin', 'is_active' => true]);
        
        // Assign default permissions for admin role
        $permissionService = app(\App\Services\PermissionService::class);
        $permissionService->assignDefaultOrganizationPermissions($user, $organization, 'admin');

        // Create incident using the controller logic directly
        $incident = $organization->incidents()->create([
            'service_id' => $service->id,
            'title' => 'Low Priority Issue',
            'description' => 'Minor performance degradation',
            'status' => IncidentStatus::INVESTIGATING->value,
            'severity' => IncidentSeverity::LOW->value,
            'created_by' => $user->id,
        ]);

        // Attach services to incident
        $incident->services()->attach([$service->id]);
        $incident->load('services');

        // Update service statuses based on incident severity
        $statusService = app(ServiceStatusService::class);
        $statusService->updateServiceStatusFromIncident($incident);

        $service->refresh();
        $this->assertEquals(ServiceStatus::DEGRADED->value, $service->status);
    }

    public function test_service_status_does_not_downgrade_when_less_severe_incident_created()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $service = Service::factory()->for($organization)->create([
            'status' => ServiceStatus::MAJOR_OUTAGE,
        ]);

        $organization->users()->attach($user->id, ['role' => 'admin', 'is_active' => true]);
        $permissionService = app(\App\Services\PermissionService::class);
        $permissionService->assignDefaultOrganizationPermissions($user, $organization, 'admin');

        // Create incident using the controller logic directly
        $incident = $organization->incidents()->create([
            'service_id' => $service->id,
            'title' => 'Low Priority Issue',
            'description' => 'Minor issue',
            'status' => IncidentStatus::INVESTIGATING->value,
            'severity' => IncidentSeverity::LOW->value,
            'created_by' => $user->id,
        ]);

        // Attach services to incident
        $incident->services()->attach([$service->id]);
        $incident->load('services');

        // Update service statuses based on incident severity
        $statusService = app(ServiceStatusService::class);
        $statusService->updateServiceStatusFromIncident($incident);

        $service->refresh();
        $this->assertEquals(ServiceStatus::MAJOR_OUTAGE->value, $service->status);
    }

    public function test_service_status_returns_to_operational_when_incident_resolved()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $service = Service::factory()->for($organization)->create([
            'status' => ServiceStatus::MAJOR_OUTAGE,
        ]);

        $incident = Incident::factory()->for($organization)->create([
            'status' => IncidentStatus::INVESTIGATING->value,
            'severity' => IncidentSeverity::CRITICAL->value,
        ]);

        $incident->services()->attach($service->id);

        $organization->users()->attach($user->id, ['role' => 'admin', 'is_active' => true]);
        $permissionService = app(\App\Services\PermissionService::class);
        $permissionService->assignDefaultOrganizationPermissions($user, $organization, 'admin');

        // Update incident to resolved status
        $incident->update([
            'status' => IncidentStatus::RESOLVED->value,
            'resolved_by' => $user->id,
            'resolved_at' => now(),
        ]);

        // Update service statuses when incident is resolved
        $statusService = app(ServiceStatusService::class);
        $statusService->handleIncidentResolved($incident);

        $service->refresh();
        $this->assertEquals(ServiceStatus::OPERATIONAL->value, $service->status);
    }

    public function test_service_status_updates_when_incident_severity_changed()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $service = Service::factory()->for($organization)->create([
            'status' => ServiceStatus::DEGRADED,
        ]);

        $incident = Incident::factory()->for($organization)->create([
            'status' => IncidentStatus::INVESTIGATING->value,
            'severity' => IncidentSeverity::LOW->value,
        ]);

        $incident->services()->attach($service->id);

        $organization->users()->attach($user->id, ['role' => 'admin', 'is_active' => true]);
        $permissionService = app(\App\Services\PermissionService::class);
        $permissionService->assignDefaultOrganizationPermissions($user, $organization, 'admin');

        // Update incident severity
        $incident->update([
            'severity' => IncidentSeverity::CRITICAL->value,
        ]);

        // Update service statuses when incident is updated
        $statusService = app(ServiceStatusService::class);
        $statusService->handleIncidentUpdated($incident);

        $service->refresh();
        $this->assertEquals(ServiceStatus::MAJOR_OUTAGE->value, $service->status);
    }

    public function test_multiple_services_affected_by_single_incident()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $service1 = Service::factory()->for($organization)->create([
            'status' => ServiceStatus::OPERATIONAL,
        ]);
        $service2 = Service::factory()->for($organization)->create([
            'status' => ServiceStatus::OPERATIONAL,
        ]);

        $organization->users()->attach($user->id, ['role' => 'admin', 'is_active' => true]);
        $permissionService = app(\App\Services\PermissionService::class);
        $permissionService->assignDefaultOrganizationPermissions($user, $organization, 'admin');

        // Create incident using the controller logic directly
        $incident = $organization->incidents()->create([
            'service_id' => $service1->id,
            'title' => 'Multi-service Issue',
            'description' => 'Affecting multiple services',
            'status' => IncidentStatus::INVESTIGATING->value,
            'severity' => IncidentSeverity::HIGH->value,
            'created_by' => $user->id,
        ]);

        // Attach services to incident
        $incident->services()->attach([$service1->id, $service2->id]);
        $incident->load('services');

        // Update service statuses based on incident severity
        $statusService = app(ServiceStatusService::class);
        $statusService->updateServiceStatusFromIncident($incident);

        $service1->refresh();
        $service2->refresh();

        $this->assertEquals(ServiceStatus::MAJOR_OUTAGE->value, $service1->status);
        $this->assertEquals(ServiceStatus::MAJOR_OUTAGE->value, $service2->status);
    }
} 