<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Organization;
use App\Models\Service;
use App\Models\Incident;
use App\Models\IncidentUpdate;
use App\Models\Maintenance;
use App\Enums\ServiceStatus;
use App\Enums\IncidentStatus;
use App\Enums\IncidentSeverity;
use App\Enums\MaintenanceStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create demo organization (or get existing)
        $demoOrg = Organization::firstOrCreate(
            ['slug' => 'demo-org'],
            [
                'name' => 'Demo Organization',
                'domain' => null,
                'settings' => [
                    'allow_registrations' => false,
                    'default_role' => 'member'
                ],
                'timezone' => 'UTC',
            ]
        );

        // Create demo user (or get existing)
        $demoUser = User::firstOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name' => 'Demo Admin',
                'password' => bcrypt('password'),
                'organization_id' => $demoOrg->id,
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // Add user to organization (if not already attached)
        if (!$demoOrg->users()->where('user_id', $demoUser->id)->exists()) {
            $demoOrg->users()->attach($demoUser->id, [
                'role' => 'admin',
                'permissions' => json_encode(['*']),
                'is_active' => true,
                'invited_by' => $demoUser->id,
                'joined_at' => now(),
            ]);
        }

        // Create demo services (only if they don't exist)
        $services = [
            [
                'name' => 'API Gateway',
                'description' => 'Main API gateway service handling all external requests',
                'status' => ServiceStatus::OPERATIONAL,
                'visibility' => 'public',
                'order' => 1,
            ],
            [
                'name' => 'Database',
                'description' => 'Primary database cluster',
                'status' => ServiceStatus::OPERATIONAL,
                'visibility' => 'public',
                'order' => 2,
            ],
            [
                'name' => 'CDN',
                'description' => 'Content delivery network for static assets',
                'status' => ServiceStatus::OPERATIONAL,
                'visibility' => 'public',
                'order' => 3,
            ],
            [
                'name' => 'Email Service',
                'description' => 'Email delivery and processing service',
                'status' => ServiceStatus::DEGRADED,
                'visibility' => 'public',
                'order' => 4,
            ],
            [
                'name' => 'Payment Processing',
                'description' => 'Payment gateway and transaction processing',
                'status' => ServiceStatus::OPERATIONAL,
                'visibility' => 'public',
                'order' => 5,
            ],
        ];

        foreach ($services as $serviceData) {
            Service::firstOrCreate(
                [
                    'organization_id' => $demoOrg->id,
                    'name' => $serviceData['name'],
                ],
                [
                    'description' => $serviceData['description'],
                    'status' => $serviceData['status'],
                    'visibility' => $serviceData['visibility'],
                    'order' => $serviceData['order'],
                    'created_by' => $demoUser->id,
                ]
            );
        }

        // Get the email service for incidents
        $emailService = Service::where('name', 'Email Service')->first();

        // Create demo incidents
        $incident1 = Incident::create([
            'organization_id' => $demoOrg->id,
            'service_id' => $emailService->id,
            'title' => 'Email delivery delays',
            'description' => 'We are experiencing delays in email delivery. Some emails may take up to 15 minutes to be delivered.',
            'status' => IncidentStatus::INVESTIGATING,
            'severity' => IncidentSeverity::LOW,
            'created_by' => $demoUser->id,
        ]);

        // Create incident updates
        IncidentUpdate::create([
            'incident_id' => $incident1->id,
            'description' => 'We have identified the issue and are working on a resolution.',
            'status' => IncidentStatus::INVESTIGATING,
            'created_by' => $demoUser->id,
        ]);

        IncidentUpdate::create([
            'incident_id' => $incident1->id,
            'description' => 'We have implemented a fix and are monitoring the situation.',
            'status' => IncidentStatus::MONITORING,
            'created_by' => $demoUser->id,
        ]);

        // Create a resolved incident
        $incident2 = Incident::create([
            'organization_id' => $demoOrg->id,
            'service_id' => $emailService->id,
            'title' => 'Scheduled maintenance completed',
            'description' => 'Routine maintenance has been completed successfully.',
            'status' => IncidentStatus::RESOLVED,
            'severity' => IncidentSeverity::LOW,
            'created_by' => $demoUser->id,
            'resolved_at' => now()->subHours(2),
        ]);

        IncidentUpdate::create([
            'incident_id' => $incident2->id,
            'description' => 'Maintenance completed successfully. All systems are operational.',
            'status' => IncidentStatus::RESOLVED,
            'created_by' => $demoUser->id,
        ]);

        // Create demo maintenance
        $apiService = Service::where('name', 'API Gateway')->first();
        
        Maintenance::create([
            'organization_id' => $demoOrg->id,
            'service_id' => $apiService->id,
            'title' => 'API Gateway Upgrade',
            'description' => 'We will be upgrading our API Gateway to improve performance and security.',
            'scheduled_start' => now()->addDays(2)->setTime(2, 0, 0),
            'scheduled_end' => now()->addDays(2)->setTime(4, 0, 0),
            'status' => MaintenanceStatus::SCHEDULED,
            'created_by' => $demoUser->id,
        ]);

        // Create some service status logs for uptime metrics
        $this->createServiceStatusLogs($demoOrg->id);

        $this->command->info('Demo data created successfully!');
        $this->command->info('Demo organization: demo-org');
        $this->command->info('Demo user: demo@example.com / password');
    }

    private function createServiceStatusLogs($organizationId)
    {
        $services = Service::where('organization_id', $organizationId)->get();
        
        foreach ($services as $service) {
            // Create status logs for the past 30 days
            for ($i = 30; $i >= 0; $i--) {
                $date = now()->subDays($i);
                
                // Simulate some downtime
                $status = ServiceStatus::OPERATIONAL;
                if ($service->name === 'Email Service' && $i <= 5) {
                    $status = ServiceStatus::DEGRADED;
                }
                
                // Add some random minor outages
                if (rand(1, 100) <= 2) { // 2% chance of outage
                    $status = ServiceStatus::PARTIAL_OUTAGE;
                }
                
                DB::table('service_status_logs')->insert([
                    'service_id' => $service->id,
                    'status_to' => $status,
                    'changed_at' => $date,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
            }
        }
    }
} 