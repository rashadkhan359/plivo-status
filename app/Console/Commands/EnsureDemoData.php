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
        // Check if demo organization exists
        $demoOrg = Organization::where('slug', 'demo-org')->first();
        
        if (!$demoOrg) {
            $this->info('Demo organization not found. Creating...');
            $this->createDemoData();
        } else {
            $this->info('Demo organization exists: ' . $demoOrg->name);
            
            // Check if services exist
            $serviceCount = $demoOrg->services()->count();
            if ($serviceCount < 3) {
                $this->info("Only {$serviceCount} services found. Creating missing services...");
                $this->createDemoServices($demoOrg);
            } else {
                $this->info("Demo organization has {$serviceCount} services");
            }
        }
    }
    
    /**
     * Force recreate all demo data
     */
    private function recreateDemoData()
    {
        // Delete existing demo organization and all related data
        $demoOrg = Organization::where('slug', 'demo-org')->first();
        if ($demoOrg) {
            $this->info('Deleting existing demo organization...');
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
            // Create demo organization
            $demoOrg = Organization::create([
                'name' => 'Demo Organization',
                'slug' => 'demo-org',
                'domain' => null,
                'settings' => [
                    'allow_registrations' => false,
                    'default_role' => 'member'
                ],
                'timezone' => 'UTC',
            ]);
            
            $this->info('Created demo organization: ' . $demoOrg->name);
            
            // Create demo user
            $demoUser = User::create([
                'name' => 'Demo Admin',
                'email' => 'demo@example.com',
                'password' => bcrypt('password'),
                'organization_id' => $demoOrg->id,
                'role' => 'admin',
                'email_verified_at' => now(),
            ]);
            
            // Add user to organization
            $demoOrg->users()->attach($demoUser->id, [
                'role' => 'admin',
                'permissions' => ['*'],
                'is_active' => true,
                'invited_by' => $demoUser->id,
                'joined_at' => now(),
            ]);
            
            $this->info('Created demo user: ' . $demoUser->email);
            
            // Create services
            $this->createDemoServices($demoOrg, $demoUser);
            
            // Create some incidents
            $this->createDemoIncidents($demoOrg, $demoUser);
            
            // Create some maintenance
            $this->createDemoMaintenance($demoOrg, $demoUser);
        });
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
                'name' => 'API Gateway',
                'description' => 'Main API gateway service handling all external requests',
                'status' => ServiceStatus::OPERATIONAL->value,
                'visibility' => 'public',
                'order' => 1,
            ],
            [
                'name' => 'Database',
                'description' => 'Primary database cluster',
                'status' => ServiceStatus::OPERATIONAL->value,
                'visibility' => 'public',
                'order' => 2,
            ],
            [
                'name' => 'CDN',
                'description' => 'Content delivery network for static assets',
                'status' => ServiceStatus::OPERATIONAL->value,
                'visibility' => 'public',
                'order' => 3,
            ],
            [
                'name' => 'Email Service',
                'description' => 'Email delivery and processing service',
                'status' => ServiceStatus::DEGRADED->value,
                'visibility' => 'public',
                'order' => 4,
            ],
            [
                'name' => 'Payment Processing',
                'description' => 'Payment gateway and transaction processing',
                'status' => ServiceStatus::OPERATIONAL->value,
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
        $emailService = Service::where('organization_id', $demoOrg->id)
            ->where('name', 'Email Service')
            ->first();
            
        if ($emailService) {
            $incident = Incident::create([
                'organization_id' => $demoOrg->id,
                'service_id' => $emailService->id,
                'title' => 'Email delivery delays',
                'description' => 'We are experiencing delays in email delivery due to increased load',
                'status' => IncidentStatus::MONITORING->value,
                'severity' => IncidentSeverity::MEDIUM->value,
                'created_by' => $demoUser->id,
            ]);
            
            $incident->services()->attach($emailService->id);
            
            // Add some updates
            IncidentUpdate::create([
                'incident_id' => $incident->id,
                'description' => 'We have identified the issue and are working on a fix',
                'status' => IncidentStatus::IDENTIFIED->value,
                'created_by' => $demoUser->id,
            ]);
            
            IncidentUpdate::create([
                'incident_id' => $incident->id,
                'description' => 'A fix has been implemented. We are monitoring the situation',
                'status' => IncidentStatus::MONITORING->value,
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
        $database = Service::where('organization_id', $demoOrg->id)
            ->where('name', 'Database')
            ->first();
            
        if ($database) {
            Maintenance::create([
                'organization_id' => $demoOrg->id,
                'service_id' => $database->id,
                'title' => 'Database maintenance',
                'description' => 'Scheduled database maintenance for performance optimization',
                'status' => MaintenanceStatus::SCHEDULED->value,
                'scheduled_start' => now()->addDays(7),
                'scheduled_end' => now()->addDays(7)->addHours(2),
                'created_by' => $demoUser->id,
            ]);
            
            $this->info('Created demo maintenance');
        }
    }
} 