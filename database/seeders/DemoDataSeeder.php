<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Organization;
use App\Models\Service;
use App\Models\Incident;
use App\Models\IncidentUpdate;
use App\Models\Maintenance;
use App\Models\ServiceStatusLog;
use App\Models\StatusUpdate;
use App\Enums\ServiceStatus;
use App\Enums\IncidentStatus;
use App\Enums\IncidentSeverity;
use App\Enums\MaintenanceStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create XKCD-Robotics organization
        $organization = Organization::firstOrCreate(
            ['slug' => 'xkcd-robotics'],
            [
                'name' => 'XKCD-Robotics',
                'domain' => null,
                'settings' => [
                    'allow_registrations' => false,
                    'default_role' => 'member'
                ],
                'timezone' => 'UTC',
            ]
        );

        // Create owner user
        $owner = User::firstOrCreate(
            ['email' => 'owner@gmail.com'],
            [
                'name' => 'Organization Owner',
                'password' => bcrypt('password'),
                'organization_id' => $organization->id,
                'role' => 'admin', // Use 'admin' for users table, 'owner' for pivot table
                'email_verified_at' => now(),
            ]
        );

        // Create member user
        $member = User::firstOrCreate(
            ['email' => 'rashadkhan359@gmail.com'],
            [
                'name' => 'Rashad Khan',
                'password' => bcrypt('password'),
                'organization_id' => $organization->id,
                'role' => 'member',
                'email_verified_at' => now(),
            ]
        );

        // Create additional users for teams
        $admin = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Organization Admin',
                'password' => bcrypt('password'),
                'organization_id' => $organization->id,
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        $teamLead1 = User::firstOrCreate(
            ['email' => 'teamlead1@gmail.com'],
            [
                'name' => 'Infrastructure Team Lead',
                'password' => bcrypt('password'),
                'organization_id' => $organization->id,
                'role' => 'member',
                'email_verified_at' => now(),
            ]
        );

        $teamLead2 = User::firstOrCreate(
            ['email' => 'teamlead2@gmail.com'],
            [
                'name' => 'AI Team Lead',
                'password' => bcrypt('password'),
                'organization_id' => $organization->id,
                'role' => 'member',
                'email_verified_at' => now(),
            ]
        );

        $member1 = User::firstOrCreate(
            ['email' => 'member1@gmail.com'],
            [
                'name' => 'Infrastructure Engineer',
                'password' => bcrypt('password'),
                'organization_id' => $organization->id,
                'role' => 'member',
                'email_verified_at' => now(),
            ]
        );

        $member2 = User::firstOrCreate(
            ['email' => 'member2@gmail.com'],
            [
                'name' => 'AI Engineer',
                'password' => bcrypt('password'),
                'organization_id' => $organization->id,
                'role' => 'member',
                'email_verified_at' => now(),
            ]
        );

        $member3 = User::firstOrCreate(
            ['email' => 'member3@gmail.com'],
            [
                'name' => 'Systems Engineer',
                'password' => bcrypt('password'),
                'organization_id' => $organization->id,
                'role' => 'member',
                'email_verified_at' => now(),
            ]
        );

        // Add users to organization
        if (!$organization->users()->where('user_id', $owner->id)->exists()) {
            $organization->users()->attach($owner->id, [
                'role' => 'owner',
                'permissions' => [
                    'manage_organization' => true,
                    'manage_users' => true,
                    'manage_teams' => true,
                    'manage_services' => true,
                    'manage_incidents' => true,
                    'manage_maintenance' => true,
                    'view_analytics' => true,
                ],
                'is_active' => true,
                'invited_by' => $owner->id,
                'joined_at' => now(),
            ]);
        }

        if (!$organization->users()->where('user_id', $member->id)->exists()) {
            $organization->users()->attach($member->id, [
                'role' => 'member',
                'permissions' => [
                    'view_services' => true,
                    'view_incidents' => true,
                    'view_maintenance' => true,
                ],
                'is_active' => true,
                'invited_by' => $owner->id,
                'joined_at' => now(),
            ]);
        }

        // Add additional users to organization
        $additionalUsers = [
            [$admin, 'admin', [
                'manage_organization' => true,
                'manage_users' => true,
                'manage_teams' => true,
                'manage_services' => true,
                'manage_incidents' => true,
                'manage_maintenance' => true,
                'view_analytics' => true,
            ]],
            [$teamLead1, 'team_lead', [
                'manage_teams' => true,
                'manage_services' => true,
                'manage_incidents' => true,
                'manage_maintenance' => true,
                'view_analytics' => true,
            ]],
            [$teamLead2, 'team_lead', [
                'manage_teams' => true,
                'manage_services' => true,
                'manage_incidents' => true,
                'manage_maintenance' => true,
                'view_analytics' => true,
            ]],
            [$member1, 'member', [
                'view_services' => true,
                'view_incidents' => true,
                'view_maintenance' => true,
            ]],
            [$member2, 'member', [
                'view_services' => true,
                'view_incidents' => true,
                'view_maintenance' => true,
            ]],
            [$member3, 'member', [
                'view_services' => true,
                'view_incidents' => true,
                'view_maintenance' => true,
            ]],
        ];

        foreach ($additionalUsers as [$user, $role, $permissions]) {
            if (!$organization->users()->where('user_id', $user->id)->exists()) {
                $organization->users()->attach($user->id, [
                    'role' => $role,
                    'permissions' => $permissions,
                    'is_active' => true,
                    'invited_by' => $owner->id,
                    'joined_at' => now(),
                ]);
            }
        }

        // Create 5 relevant services for XKCD-Robotics
        $services = [
            [
                'name' => 'Robot Control System',
                'description' => 'Central control system for all robotic operations and automation',
                'status' => ServiceStatus::OPERATIONAL,
                'visibility' => 'public',
                'order' => 1,
            ],
            [
                'name' => 'AI Processing Engine',
                'description' => 'Machine learning and AI processing for robot decision making',
                'status' => ServiceStatus::OPERATIONAL,
                'visibility' => 'public',
                'order' => 2,
            ],
            [
                'name' => 'Sensor Network',
                'description' => 'Distributed sensor network for environmental monitoring',
                'status' => ServiceStatus::DEGRADED,
                'visibility' => 'public',
                'order' => 3,
            ],
            [
                'name' => 'Communication Hub',
                'description' => 'Inter-robot communication and coordination system',
                'status' => ServiceStatus::OPERATIONAL,
                'visibility' => 'public',
                'order' => 4,
            ],
            [
                'name' => 'Power Management',
                'description' => 'Battery monitoring and power distribution system',
                'status' => ServiceStatus::PARTIAL_OUTAGE,
                'visibility' => 'public',
                'order' => 5,
            ],
        ];

        $createdServices = [];
        foreach ($services as $serviceData) {
            $service = Service::firstOrCreate(
                [
                    'organization_id' => $organization->id,
                    'name' => $serviceData['name'],
                ],
                [
                    'description' => $serviceData['description'],
                    'status' => $serviceData['status'],
                    'visibility' => $serviceData['visibility'],
                    'order' => $serviceData['order'],
                    'created_by' => $owner->id,
                ]
            );
            $createdServices[] = $service;
        }

        // Create teams and distribute services
        $this->createTeamsAndDistributeServices($organization, $createdServices, $owner, $admin, $teamLead1, $teamLead2, $member1, $member2, $member3);

        // Create incidents with proper updates
        $this->createIncidents($organization, $createdServices, $owner, $member);

        // Create maintenance records
        $this->createMaintenance($organization, $createdServices, $owner);

        // Create service status logs for uptime metrics
        $this->createServiceStatusLogs($organization, $createdServices, $owner);

        // Create status updates
        $this->createStatusUpdates($organization, $createdServices, $owner);

        $this->command->info('✅ Demo data created successfully!');
        $this->command->info('🏢 Organization: XKCD-Robotics');
        $this->command->info('👑 Owner: owner@gmail.com / password');
        $this->command->info('👨‍💼 Admin: admin@gmail.com / password');
        $this->command->info('👤 Member: rashadkhan359@gmail.com / password');
        $this->command->info('👥 Teams:');
        $this->command->info('   🏗️ Infrastructure Team (teamlead1@gmail.com, member1@gmail.com, member3@gmail.com)');
        $this->command->info('   🤖 AI & Robotics Team (teamlead2@gmail.com, member2@gmail.com)');
        $this->command->info('🤖 Services: 5 robotics-related services distributed across teams');
        $this->command->info('🚨 Incidents: Multiple incidents with proper updates');
        $this->command->info('🔧 Maintenance: Scheduled and completed maintenance records');
        $this->command->info('📊 Status Logs: 30 days of status history for uptime metrics');
    }

    private function createIncidents($organization, $services, $owner, $member)
    {
        // Incident 1: Active incident on Sensor Network
        $sensorService = $services[2]; // Sensor Network (degraded)
        $incident1 = Incident::create([
            'organization_id' => $organization->id,
            'service_id' => $sensorService->id,
            'title' => 'Sensor Network Performance Degradation',
            'description' => 'Multiple sensors are reporting intermittent connectivity issues, affecting data collection accuracy.',
            'status' => IncidentStatus::INVESTIGATING,
            'severity' => IncidentSeverity::MEDIUM,
            'created_by' => $owner->id,
        ]);

        // Add service relationship
        $incident1->services()->attach($sensorService->id);

        // Incident updates for incident 1
        IncidentUpdate::create([
            'incident_id' => $incident1->id,
            'description' => 'Initial investigation shows network congestion in the sensor cluster. Engineers are analyzing the root cause.',
            'status' => IncidentStatus::INVESTIGATING,
            'created_by' => $owner->id,
        ]);

        IncidentUpdate::create([
            'incident_id' => $incident1->id,
            'description' => 'Root cause identified: firmware issue in sensor nodes. Patch deployment in progress.',
            'status' => IncidentStatus::IDENTIFIED,
            'created_by' => $member->id,
        ]);

        // Incident 2: Resolved incident on Power Management
        $powerService = $services[4]; // Power Management (partial outage)
        $incident2 = Incident::create([
            'organization_id' => $organization->id,
            'service_id' => $powerService->id,
            'title' => 'Battery Backup System Failure',
            'description' => 'Primary battery backup system failed during routine testing, causing temporary power instability.',
            'status' => IncidentStatus::RESOLVED,
            'severity' => IncidentSeverity::HIGH,
            'created_by' => $owner->id,
            'resolved_by' => $owner->id,
            'resolved_at' => now()->subHours(6),
        ]);

        $incident2->services()->attach($powerService->id);

        IncidentUpdate::create([
            'incident_id' => $incident2->id,
            'description' => 'Emergency response team activated. Secondary backup systems are operational.',
            'status' => IncidentStatus::INVESTIGATING,
            'created_by' => $owner->id,
        ]);

        IncidentUpdate::create([
            'incident_id' => $incident2->id,
            'description' => 'Primary backup system repaired and tested. All systems restored to normal operation.',
            'status' => IncidentStatus::RESOLVED,
            'created_by' => $owner->id,
        ]);

        // Incident 3: Critical incident affecting multiple services
        $incident3 = Incident::create([
            'organization_id' => $organization->id,
            'service_id' => $services[0]->id, // Robot Control System
            'title' => 'Critical Security Vulnerability Detected',
            'description' => 'Security audit revealed a critical vulnerability in the robot control system that could allow unauthorized access.',
            'status' => IncidentStatus::INVESTIGATING,
            'severity' => IncidentSeverity::CRITICAL,
            'created_by' => $owner->id,
        ]);

        // Affect multiple services
        $incident3->services()->attach([
            $services[0]->id, // Robot Control System
            $services[1]->id, // AI Processing Engine
            $services[3]->id, // Communication Hub
        ]);

        IncidentUpdate::create([
            'incident_id' => $incident3->id,
            'description' => 'Security team investigating the vulnerability. All affected systems are being patched.',
            'status' => IncidentStatus::INVESTIGATING,
            'created_by' => $owner->id,
        ]);

        IncidentUpdate::create([
            'incident_id' => $incident3->id,
            'description' => 'Vulnerability patched. Security audit completed. All systems verified secure.',
            'status' => IncidentStatus::MONITORING,
            'created_by' => $member->id,
        ]);
    }

    private function createMaintenance($organization, $services, $owner)
    {
        // Scheduled maintenance
        $maintenance1 = Maintenance::create([
            'organization_id' => $organization->id,
            'service_id' => $services[1]->id, // AI Processing Engine
            'title' => 'AI Model Update and Optimization',
            'description' => 'Scheduled update of machine learning models and performance optimization of the AI processing engine.',
            'scheduled_start' => now()->addDays(3)->setTime(2, 0, 0),
            'scheduled_end' => now()->addDays(3)->setTime(6, 0, 0),
            'status' => MaintenanceStatus::SCHEDULED,
            'created_by' => $owner->id,
        ]);

        // In-progress maintenance
        $maintenance2 = Maintenance::create([
            'organization_id' => $organization->id,
            'service_id' => $services[3]->id, // Communication Hub
            'title' => 'Communication Protocol Upgrade',
            'description' => 'Upgrading inter-robot communication protocols to improve efficiency and reduce latency.',
            'scheduled_start' => now()->subHours(2),
            'scheduled_end' => now()->addHours(2),
            'actual_start' => now()->subHours(2),
            'status' => MaintenanceStatus::IN_PROGRESS,
            'created_by' => $owner->id,
        ]);

        // Completed maintenance
        $maintenance3 = Maintenance::create([
            'organization_id' => $organization->id,
            'service_id' => $services[0]->id, // Robot Control System
            'title' => 'Control System Database Migration',
            'description' => 'Successfully migrated the robot control system database to improve performance and reliability.',
            'scheduled_start' => now()->subDays(1)->setTime(1, 0, 0),
            'scheduled_end' => now()->subDays(1)->setTime(5, 0, 0),
            'actual_start' => now()->subDays(1)->setTime(1, 15, 0),
            'actual_end' => now()->subDays(1)->setTime(4, 30, 0),
            'status' => MaintenanceStatus::COMPLETED,
            'created_by' => $owner->id,
        ]);
    }

    private function createServiceStatusLogs($organization, $services, $owner)
    {
        foreach ($services as $service) {
            $currentStatus = $service->status;
            $lastStatus = ServiceStatus::OPERATIONAL;
            
            // Create status logs for the past 30 days
            for ($i = 30; $i >= 0; $i--) {
                $date = now()->subDays($i);
                
                // Simulate realistic status changes
                $status = $this->getSimulatedStatus($service, $i, $currentStatus);
                
                // Create status log entry
                ServiceStatusLog::create([
                    'service_id' => $service->id,
                    'status_from' => $lastStatus,
                    'status_to' => $status,
                    'changed_at' => $date,
                    'changed_by' => $owner->id,
                    'reason' => $this->getStatusChangeReason($status, $lastStatus),
                ]);
                
                $lastStatus = $status;
            }
        }
    }

    private function getSimulatedStatus($service, $daysAgo, $currentStatus)
    {
        // Base status on current service status
        $baseStatus = $currentStatus;
        
        // Add some realistic variations
        if ($service->name === 'Sensor Network' && $daysAgo <= 7) {
            return ServiceStatus::DEGRADED;
        }
        
        if ($service->name === 'Power Management' && $daysAgo <= 3) {
            return ServiceStatus::PARTIAL_OUTAGE;
        }
        
        // Random minor outages (1% chance)
        if (rand(1, 100) <= 1) {
            return ServiceStatus::PARTIAL_OUTAGE;
        }
        
        // Random degraded performance (3% chance)
        if (rand(1, 100) <= 3) {
            return ServiceStatus::DEGRADED;
        }
        
        return $baseStatus;
    }

    private function getStatusChangeReason($newStatus, $oldStatus)
    {
        if ($newStatus === $oldStatus) {
            return null;
        }
        
        $reasons = [
            ServiceStatus::OPERATIONAL->value => 'Service restored to normal operation',
            ServiceStatus::DEGRADED->value => 'Performance degradation detected',
            ServiceStatus::PARTIAL_OUTAGE->value => 'Partial service outage',
            ServiceStatus::MAJOR_OUTAGE->value => 'Major service outage',
        ];
        
        return $reasons[$newStatus->value] ?? 'Status change';
    }

    private function createStatusUpdates($organization, $services, $owner)
    {
        // Create status updates for services
        foreach ($services as $service) {
            if ($service->status !== ServiceStatus::OPERATIONAL) {
                StatusUpdate::create([
                    'organization_id' => $organization->id,
                    'service_id' => $service->id,
                    'type' => 'service_status',
                    'title' => "{$service->name} Status Update",
                    'description' => $this->getStatusUpdateDescription($service),
                    'old_status' => ServiceStatus::OPERATIONAL->value,
                    'new_status' => $service->status->value,
                    'created_by' => $owner->id,
                ]);
            }
        }
    }

    private function createTeamsAndDistributeServices($organization, $services, $owner, $admin, $teamLead1, $teamLead2, $member1, $member2, $member3)
    {
        // Create Infrastructure Team
        $infrastructureTeam = \App\Models\Team::create([
            'organization_id' => $organization->id,
            'name' => 'Infrastructure Team',
            'description' => 'Manages core infrastructure services including power management and communication systems',
            'color' => '#3B82F6', // Blue
            'created_by' => $owner->id,
        ]);

        // Create AI Team
        $aiTeam = \App\Models\Team::create([
            'organization_id' => $organization->id,
            'name' => 'AI & Robotics Team',
            'description' => 'Manages AI processing and robot control systems',
            'color' => '#10B981', // Green
            'created_by' => $owner->id,
        ]);

        // Add team members
        $infrastructureTeam->users()->attach([
            $teamLead1->id => [
                'role' => 'lead',
                'permissions' => [
                    'manage_teams' => true,
                    'manage_services' => true,
                    'manage_incidents' => true,
                    'manage_maintenance' => true,
                    'view_analytics' => true,
                ],
            ],
            $member1->id => [
                'role' => 'member',
                'permissions' => [
                    'manage_teams' => false,
                    'manage_services' => false,
                    'manage_incidents' => true,
                    'manage_maintenance' => true,
                    'view_analytics' => false,
                ],
            ],
            $member3->id => [
                'role' => 'member',
                'permissions' => [
                    'manage_teams' => false,
                    'manage_services' => false,
                    'manage_incidents' => true,
                    'manage_maintenance' => true,
                    'view_analytics' => false,
                ],
            ],
        ]);

        $aiTeam->users()->attach([
            $teamLead2->id => [
                'role' => 'lead',
                'permissions' => [
                    'manage_teams' => true,
                    'manage_services' => true,
                    'manage_incidents' => true,
                    'manage_maintenance' => true,
                    'view_analytics' => true,
                ],
            ],
            $member2->id => [
                'role' => 'member',
                'permissions' => [
                    'manage_teams' => false,
                    'manage_services' => false,
                    'manage_incidents' => true,
                    'manage_maintenance' => true,
                    'view_analytics' => false,
                ],
            ],
        ]);

        // Distribute services to teams
        $serviceDistribution = [
            'Robot Control System' => $aiTeam,
            'AI Processing Engine' => $aiTeam,
            'Sensor Network' => $infrastructureTeam,
            'Communication Hub' => $infrastructureTeam,
            'Power Management' => $infrastructureTeam,
        ];

        foreach ($services as $service) {
            if (isset($serviceDistribution[$service->name])) {
                $team = $serviceDistribution[$service->name];
                $service->update([
                    'team_id' => $team->id,
                    'created_by' => $team->leads()->first()->id,
                ]);
            }
        }
    }

    private function getStatusUpdateDescription($service)
    {
        $descriptions = [
            'Sensor Network' => 'We are experiencing intermittent connectivity issues with our sensor network. Engineers are working to resolve the problem.',
            'Power Management' => 'Battery backup system is experiencing issues. Secondary systems are operational while we address the problem.',
        ];
        
        return $descriptions[$service->name] ?? "{$service->name} is currently experiencing issues. Our team is investigating.";
    }
} 