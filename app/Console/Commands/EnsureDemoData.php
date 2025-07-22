<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Organization;
use App\Models\User;
use App\Models\Service;
use App\Models\Incident;
use App\Models\IncidentUpdate;
use App\Models\Maintenance;
use App\Enums\ServiceStatus;
use App\Enums\IncidentStatus;
use App\Enums\IncidentSeverity;
use App\Enums\MaintenanceStatus;
use Illuminate\Support\Facades\DB;

class EnsureDemoData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'demo:ensure {--force : Force recreate demo data}';

    /**
     * The console command description.
     */
    protected $description = 'Ensure demo organization and data exists for public demo page';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $force = $this->option('force');
        
        if ($force) {
            $this->info('Force mode: Recreating demo data...');
            $this->recreateDemoData();
        } else {
            $this->info('Checking demo data availability...');
            $this->ensureDemoData();
        }
        
        $this->info('Demo data check completed successfully!');
        return 0;
    }
    
    /**
     * Ensure demo data exists without forcing recreation
     */
    private function ensureDemoData()
    {
        // Check if XKCD-Robotics organization exists
        $demoOrg = Organization::where('slug', 'xkcd-robotics')->first();
        
        if (!$demoOrg) {
            $this->info('XKCD-Robotics organization not found. Creating...');
            $this->createDemoData();
        } else {
            $this->info('XKCD-Robotics organization exists: ' . $demoOrg->name);
            
            // Check if services exist
            $serviceCount = $demoOrg->services()->count();
            if ($serviceCount < 5) {
                $this->info("Only {$serviceCount} services found. Creating missing services...");
                $this->createDemoServices($demoOrg);
            } else {
                $this->info("XKCD-Robotics organization has {$serviceCount} services");
            }
            
            // Check if users exist
            $owner = User::where('email', 'owner@gmail.com')->first();
            $member = User::where('email', 'member@gmail.com')->first();
            
            if (!$owner || !$member) {
                $this->info('Demo users not found. Creating...');
                $this->createDemoUsers($demoOrg);
            } else {
                $this->info('Demo users exist: ' . $owner->email . ' and ' . $member->email);
            }
        }
    }
    
    /**
     * Force recreate all demo data
     */
    private function recreateDemoData()
    {
        // Delete existing XKCD-Robotics organization and all related data
        $demoOrg = Organization::where('slug', 'xkcd-robotics')->first();
        if ($demoOrg) {
            $this->info('Deleting existing XKCD-Robotics organization...');
            $demoOrg->delete();
        }
        
        $this->createDemoData();
    }
    
    /**
     * Create demo organization and all related data
     */
    private function createDemoData()
    {
        DB::transaction(function () {
            // Create XKCD-Robotics organization
            $demoOrg = Organization::create([
                'name' => 'XKCD-Robotics',
                'slug' => 'xkcd-robotics',
                'domain' => null,
                'settings' => [
                    'allow_registrations' => false,
                    'default_role' => 'member'
                ],
                'timezone' => 'UTC',
            ]);
            
            $this->info('Created XKCD-Robotics organization: ' . $demoOrg->name);
            
            // Create demo users
            $owner = $this->createDemoUsers($demoOrg);
            
            // Create services
            $this->createDemoServices($demoOrg, $owner);
            
            // Create some incidents
            $this->createDemoIncidents($demoOrg, $owner);
            
            // Create some maintenance
            $this->createDemoMaintenance($demoOrg, $owner);
        });
    }
    
    /**
     * Create demo users
     */
    private function createDemoUsers($demoOrg)
    {
        // Create owner user
        $owner = User::firstOrCreate(
            ['email' => 'owner@gmail.com'],
            [
                'name' => 'Organization Owner',
                'password' => bcrypt('password'),
                'organization_id' => $demoOrg->id,
                'role' => 'admin', // Use 'admin' for users table, 'owner' for pivot table
                'email_verified_at' => now(),
            ]
        );
        
        // Create member user
        $member = User::firstOrCreate(
            ['email' => 'member@gmail.com'],
            [
                'name' => 'Team Member',
                'password' => bcrypt('password'),
                'organization_id' => $demoOrg->id,
                'role' => 'member',
                'email_verified_at' => now(),
            ]
        );
        
        // Add users to organization (if not already attached)
        if (!$demoOrg->users()->where('user_id', $owner->id)->exists()) {
            $demoOrg->users()->attach($owner->id, [
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
        
        if (!$demoOrg->users()->where('user_id', $member->id)->exists()) {
            $demoOrg->users()->attach($member->id, [
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
        
        $this->info('Created owner user: ' . $owner->email);
        $this->info('Created member user: ' . $member->email);
        
        return $owner;
    }
    
    /**
     * Create demo services
     */
    private function createDemoServices($demoOrg, $demoUser = null)
    {
        if (!$demoUser) {
            $demoUser = $demoOrg->users()->first();
        }
        
        $services = [
            [
                'name' => 'Robot Control System',
                'description' => 'Central control system for all robotic operations and automation',
                'status' => ServiceStatus::OPERATIONAL->value,
                'visibility' => 'public',
                'order' => 1,
            ],
            [
                'name' => 'AI Processing Engine',
                'description' => 'Machine learning and AI processing for robot decision making',
                'status' => ServiceStatus::OPERATIONAL->value,
                'visibility' => 'public',
                'order' => 2,
            ],
            [
                'name' => 'Sensor Network',
                'description' => 'Distributed sensor network for environmental monitoring',
                'status' => ServiceStatus::DEGRADED->value,
                'visibility' => 'public',
                'order' => 3,
            ],
            [
                'name' => 'Communication Hub',
                'description' => 'Inter-robot communication and coordination system',
                'status' => ServiceStatus::OPERATIONAL->value,
                'visibility' => 'public',
                'order' => 4,
            ],
            [
                'name' => 'Power Management',
                'description' => 'Battery monitoring and power distribution system',
                'status' => ServiceStatus::PARTIAL_OUTAGE->value,
                'visibility' => 'public',
                'order' => 5,
            ],
        ];
        
        foreach ($services as $serviceData) {
            $existingService = Service::where('organization_id', $demoOrg->id)
                ->where('name', $serviceData['name'])
                ->first();
                
            if (!$existingService) {
                Service::create([
                    'organization_id' => $demoOrg->id,
                    'created_by' => $demoUser->id,
                    ...$serviceData
                ]);
                $this->info('Created service: ' . $serviceData['name']);
            }
        }
    }
    
    /**
     * Create demo incidents
     */
    private function createDemoIncidents($demoOrg, $demoUser)
    {
        $sensorService = Service::where('organization_id', $demoOrg->id)
            ->where('name', 'Sensor Network')
            ->first();
            
        if ($sensorService) {
            $incident = Incident::create([
                'organization_id' => $demoOrg->id,
                'service_id' => $sensorService->id,
                'title' => 'Sensor Network Performance Degradation',
                'description' => 'Multiple sensors are reporting intermittent connectivity issues, affecting data collection accuracy.',
                'status' => IncidentStatus::INVESTIGATING->value,
                'severity' => IncidentSeverity::MEDIUM->value,
                'created_by' => $demoUser->id,
            ]);
            
            $incident->services()->attach($sensorService->id);
            
            // Add some updates
            IncidentUpdate::create([
                'incident_id' => $incident->id,
                'description' => 'Initial investigation shows network congestion in the sensor cluster. Engineers are analyzing the root cause.',
                'status' => IncidentStatus::INVESTIGATING->value,
                'created_by' => $demoUser->id,
            ]);
            
            IncidentUpdate::create([
                'incident_id' => $incident->id,
                'description' => 'Root cause identified: firmware issue in sensor nodes. Patch deployment in progress.',
                'status' => IncidentStatus::IDENTIFIED->value,
                'created_by' => $demoUser->id,
            ]);
            
            $this->info('Created demo incident: ' . $incident->title);
        }
    }
    
    /**
     * Create demo maintenance
     */
    private function createDemoMaintenance($demoOrg, $demoUser)
    {
        $aiService = Service::where('organization_id', $demoOrg->id)
            ->where('name', 'AI Processing Engine')
            ->first();
            
        if ($aiService) {
            Maintenance::create([
                'organization_id' => $demoOrg->id,
                'service_id' => $aiService->id,
                'title' => 'AI Model Update and Optimization',
                'description' => 'Scheduled update of machine learning models and performance optimization of the AI processing engine.',
                'status' => MaintenanceStatus::SCHEDULED->value,
                'scheduled_start' => now()->addDays(3)->setTime(2, 0, 0),
                'scheduled_end' => now()->addDays(3)->setTime(6, 0, 0),
                'created_by' => $demoUser->id,
            ]);
            
            $this->info('Created demo maintenance: AI Model Update');
        }
    }
} 