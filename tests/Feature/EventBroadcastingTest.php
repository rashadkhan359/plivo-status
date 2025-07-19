<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Organization;
use App\Models\Service;
use App\Models\Maintenance;
use App\Models\User;
use App\Enums\ServiceStatus;
use App\Enums\MaintenanceStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Events\ServiceCreated;
use App\Events\ServiceUpdated;
use App\Events\ServiceStatusChanged;
use App\Events\MaintenanceScheduled;
use App\Events\MaintenanceUpdated;
use App\Events\MaintenanceStarted;
use App\Events\MaintenanceCompleted;

class EventBroadcastingTest extends TestCase
{
    use RefreshDatabase;

    public function test_events_broadcast_to_correct_channels()
    {
        $organization = Organization::factory()->create(['slug' => 'test-org']);
        $user = User::factory()->create();
        $service = Service::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $serviceCreatedEvent = new ServiceCreated($service);
        $channels = $serviceCreatedEvent->broadcastOn();

        $this->assertCount(2, $channels);
        $this->assertEquals('status.test-org', $channels[0]->name);
        $this->assertEquals('private-organization.' . $organization->id, $channels[1]->name);
    }

    public function test_maintenance_events_broadcast_to_correct_channels()
    {
        $organization = Organization::factory()->create(['slug' => 'test-org']);
        $user = User::factory()->create();
        $service = Service::factory()->create([
            'organization_id' => $organization->id,
        ]);
        $maintenance = Maintenance::factory()->create([
            'organization_id' => $organization->id,
            'service_id' => $service->id,
            'created_by' => $user->id,
        ]);

        $maintenanceScheduledEvent = new MaintenanceScheduled($maintenance);
        $channels = $maintenanceScheduledEvent->broadcastOn();

        $this->assertCount(2, $channels);
        $this->assertEquals('status.test-org', $channels[0]->name);
        $this->assertEquals('private-organization.' . $organization->id, $channels[1]->name);
    }

    public function test_events_have_correct_broadcast_names()
    {
        $organization = Organization::factory()->create(['slug' => 'test-org']);
        $user = User::factory()->create();
        $service = Service::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);
        $maintenance = Maintenance::factory()->create([
            'organization_id' => $organization->id,
            'service_id' => $service->id,
            'created_by' => $user->id,
        ]);

        $serviceCreatedEvent = new ServiceCreated($service);
        $maintenanceScheduledEvent = new MaintenanceScheduled($maintenance);

        $this->assertEquals('ServiceCreated', $serviceCreatedEvent->broadcastAs());
        $this->assertEquals('MaintenanceScheduled', $maintenanceScheduledEvent->broadcastAs());
    }

    public function test_events_include_correct_data()
    {
        $organization = Organization::factory()->create(['slug' => 'test-org']);
        $user = User::factory()->create();
        $service = Service::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $serviceCreatedEvent = new ServiceCreated($service);
        $data = $serviceCreatedEvent->broadcastWith();

        $this->assertArrayHasKey('service', $data);
        $this->assertEquals($service->id, $data['service']['id']);
        $this->assertEquals($service->name, $data['service']['name']);
    }
} 