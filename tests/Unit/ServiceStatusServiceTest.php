<?php

namespace Tests\Unit;

use App\Models\Service;
use App\Models\Incident;
use App\Models\Organization;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use App\Enums\ServiceStatus;
use App\Services\ServiceStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    private ServiceStatusService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ServiceStatusService();
    }

    public function test_determine_service_status_from_critical_severity()
    {
        $organization = Organization::factory()->create();
        $service = Service::factory()->for($organization)->create([
            'status' => ServiceStatus::OPERATIONAL,
        ]);
        
        $incident = Incident::factory()->for($organization)->create([
            'status' => IncidentStatus::INVESTIGATING->value,
            'severity' => IncidentSeverity::CRITICAL->value,
        ]);
        
        $incident->services()->attach($service->id);
        $incident->load('services');
        
        $this->service->updateServiceStatusFromIncident($incident);
        
        $service->refresh();
        $this->assertEquals(ServiceStatus::MAJOR_OUTAGE->value, $service->status);
    }

    public function test_determine_service_status_from_high_severity()
    {
        $organization = Organization::factory()->create();
        $service = Service::factory()->for($organization)->create([
            'status' => ServiceStatus::OPERATIONAL,
        ]);
        
        $incident = Incident::factory()->for($organization)->create([
            'status' => IncidentStatus::INVESTIGATING->value,
            'severity' => IncidentSeverity::HIGH->value,
        ]);
        
        $incident->services()->attach($service->id);
        $incident->load('services');
        
        $this->service->updateServiceStatusFromIncident($incident);
        
        $service->refresh();
        $this->assertEquals(ServiceStatus::MAJOR_OUTAGE->value, $service->status);
    }

    public function test_determine_service_status_from_medium_severity()
    {
        $organization = Organization::factory()->create();
        $service = Service::factory()->for($organization)->create([
            'status' => ServiceStatus::OPERATIONAL,
        ]);
        
        $incident = Incident::factory()->for($organization)->create([
            'status' => IncidentStatus::INVESTIGATING->value,
            'severity' => IncidentSeverity::MEDIUM->value,
        ]);
        
        $incident->services()->attach($service->id);
        $incident->load('services');
        
        $this->service->updateServiceStatusFromIncident($incident);
        
        $service->refresh();
        $this->assertEquals(ServiceStatus::PARTIAL_OUTAGE->value, $service->status);
    }

    public function test_determine_service_status_from_low_severity()
    {
        $organization = Organization::factory()->create();
        $service = Service::factory()->for($organization)->create([
            'status' => ServiceStatus::OPERATIONAL,
        ]);
        
        $incident = Incident::factory()->for($organization)->create([
            'status' => IncidentStatus::INVESTIGATING->value,
            'severity' => IncidentSeverity::LOW->value,
        ]);
        
        $incident->services()->attach($service->id);
        $incident->load('services');
        
        $this->service->updateServiceStatusFromIncident($incident);
        
        $service->refresh();
        $this->assertEquals(ServiceStatus::DEGRADED->value, $service->status);
    }
} 