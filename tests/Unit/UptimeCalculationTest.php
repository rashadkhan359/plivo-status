<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Service;
use App\Models\ServiceStatusLog;
use App\Models\Organization;
use App\Models\User;
use App\Services\UptimeMetricsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UptimeCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_uptime_calculation_for_operational_service()
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $service = Service::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'operational',
        ]);

        $uptimeService = new UptimeMetricsService();
        
        // Test 24-hour uptime for a service that's been operational
        $uptime = $uptimeService->calculateUptimeForPeriod(
            $service, 
            now()->subDay(), 
            now()
        );

        // Should be 100% since service is operational and no status changes
        $this->assertEquals(100.0, $uptime);
    }

    public function test_uptime_calculation_with_downtime()
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $service = Service::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'operational',
        ]);

        // Create status logs for a 24-hour period with 2 hours of downtime
        $startTime = Carbon::create(2025, 7, 19, 0, 0, 0); // Fixed start time
        $endTime = Carbon::create(2025, 7, 20, 0, 0, 0);   // Fixed end time (24 hours later)
        
        // Service goes down after 10 hours
        ServiceStatusLog::create([
            'service_id' => $service->id,
            'status_from' => 'operational',
            'status_to' => 'degraded',
            'changed_at' => $startTime->copy()->addHours(10),
            'changed_by' => $user->id,
        ]);

        // Service comes back up after 2 hours (12 hours total)
        ServiceStatusLog::create([
            'service_id' => $service->id,
            'status_from' => 'degraded',
            'status_to' => 'operational',
            'changed_at' => $startTime->copy()->addHours(12),
            'changed_by' => $user->id,
        ]);

        $uptimeService = new UptimeMetricsService();
        
        // Calculate uptime for 24 hours
        $uptime = $uptimeService->calculateUptimeForPeriod(
            $service, 
            $startTime, 
            $endTime
        );

        // Debug: Let's see what's happening
        $this->assertGreaterThan(0, $uptime, "Uptime should be greater than 0");
        
        // Should be 91.67% (22 hours operational out of 24 hours)
        $expectedUptime = (22 / 24) * 100;
        $this->assertEquals(round($expectedUptime, 2), round($uptime, 2));
    }

    public function test_uptime_calculation_with_multiple_incidents()
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $service = Service::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'operational',
        ]);

        $startTime = now()->subDay();
        
        // First incident: 1 hour downtime
        ServiceStatusLog::create([
            'service_id' => $service->id,
            'status_from' => 'operational',
            'status_to' => 'major_outage',
            'changed_at' => $startTime->copy()->addHours(2),
            'changed_by' => $user->id,
        ]);

        ServiceStatusLog::create([
            'service_id' => $service->id,
            'status_from' => 'major_outage',
            'status_to' => 'operational',
            'changed_at' => $startTime->copy()->addHours(3),
            'changed_by' => $user->id,
        ]);

        // Second incident: 2 hours downtime
        ServiceStatusLog::create([
            'service_id' => $service->id,
            'status_from' => 'operational',
            'status_to' => 'partial_outage',
            'changed_at' => $startTime->copy()->addHours(8),
            'changed_by' => $user->id,
        ]);

        ServiceStatusLog::create([
            'service_id' => $service->id,
            'status_from' => 'partial_outage',
            'status_to' => 'operational',
            'changed_at' => $startTime->copy()->addHours(10),
            'changed_by' => $user->id,
        ]);

        $uptimeService = new UptimeMetricsService();
        
        // Calculate uptime for 24 hours
        $uptime = $uptimeService->calculateUptimeForPeriod(
            $service, 
            $startTime, 
            now()
        );

        // Should be 87.5% (21 hours operational out of 24 hours)
        $expectedUptime = (21 / 24) * 100;
        $this->assertEquals($expectedUptime, round($uptime, 2));
    }

    public function test_organization_uptime_average()
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        
        // Create 3 services with different uptimes
        $service1 = Service::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'operational',
        ]);
        
        $service2 = Service::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'operational',
        ]);
        
        $service3 = Service::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'operational',
        ]);

        // Add some downtime to service2
        $startTime = now()->subDay();
        ServiceStatusLog::create([
            'service_id' => $service2->id,
            'status_from' => 'operational',
            'status_to' => 'degraded',
            'changed_at' => $startTime->copy()->addHours(12),
            'changed_by' => $user->id,
        ]);

        ServiceStatusLog::create([
            'service_id' => $service2->id,
            'status_from' => 'degraded',
            'status_to' => 'operational',
            'changed_at' => $startTime->copy()->addHours(14),
            'changed_by' => $user->id,
        ]);

        $uptimeService = new UptimeMetricsService();
        
        $services = collect([$service1, $service2, $service3]);
        $averageUptime = $uptimeService->getOrganizationUptimeAverage($services, '24h');

        // Service1: 100%, Service2: 91.67%, Service3: 100%
        // Average: (100 + 91.67 + 100) / 3 = 97.22%
        $expectedAverage = (100 + 91.67 + 100) / 3;
        $this->assertEquals(round($expectedAverage, 2), round($averageUptime, 2));
    }
} 