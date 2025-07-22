<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Organization;
use App\Models\Team;
use App\Models\Service;
use App\Models\Incident;
use App\Models\Maintenance;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {

        // Create system admin user
        $systemAdmin = User::factory()->systemAdmin()->create([
            'name' => 'System Admin',
            'email' => 'admin@plivo-status.com',
            'password' => bcrypt('password'),
        ]);

        // Create 3 additional organizations with complete multi-tenant structure
        $organizations = Organization::factory(3)->create();

        // Attach system admin to the first organization
        $firstOrganization = $organizations->first();
        $firstOrganization->users()->attach($systemAdmin->id, [
            'role' => 'system_admin',
            'permissions' => [
                'manage_organization' => true,
                'manage_users' => true,
                'manage_teams' => true,
                'manage_services' => true,
                'manage_incidents' => true,
                'manage_maintenance' => true,
                'view_analytics' => true,
                'system_admin' => true,
            ],
            'joined_at' => now(),
        ]);

        $organizations->each(function ($organization) {

            // Create organization owner
            $owner = User::factory()->withOrganization($organization)->owner()->create();

            // Update organization with creator
            $organization->update(['created_by' => $owner->id]);

            // Attach owner to organization with proper role and permissions
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
                'joined_at' => now(),
            ]);

            // Create 1 admin
            $admin = User::factory()->withOrganization($organization)->admin()->create();
            $organization->users()->attach($admin->id, [
                'role' => 'admin',
                'permissions' => [
                    'manage_users' => true,
                    'manage_teams' => true,
                    'manage_services' => true,
                    'manage_incidents' => true,
                    'manage_maintenance' => true,
                    'view_analytics' => true,
                ],
                'invited_by' => $owner->id,
                'joined_at' => now(),
            ]);

            // Create 2 team leads
            $teamLeads = User::factory(2)->withOrganization($organization)->teamLead()->create();
            foreach ($teamLeads as $teamLead) {
                $organization->users()->attach($teamLead->id, [
                    'role' => 'team_lead',
                    'permissions' => [
                        'manage_teams' => true,
                        'manage_services' => true,
                        'manage_incidents' => true,
                        'manage_maintenance' => true,
                    ],
                    'invited_by' => $admin->id,
                    'joined_at' => now(),
                ]);
            }

            // Create 4 members
            $members = User::factory(4)->withOrganization($organization)->member()->create();
            foreach ($members as $member) {
                $organization->users()->attach($member->id, [
                    'role' => 'member',
                    'permissions' => [
                        'manage_services' => false,
                        'manage_incidents' => true,
                        'manage_maintenance' => false,
                    ],
                    'invited_by' => $admin->id,
                    'joined_at' => now(),
                ]);
            }

            // Create 3 teams for the organization
            $teams = Team::factory(3)->forOrganization($organization)->create()->each(function ($team) use ($teamLeads, $members) {
                // Assign a team lead to each team
                $teamLead = $teamLeads->random();
                $team->users()->attach($teamLead->id, ['role' => 'lead']);

                // Add 2-3 members to each team
                $teamMembers = $members->random(rand(2, 3));
                foreach ($teamMembers as $member) {
                    $team->users()->attach($member->id, ['role' => 'member']);
                }
            });

            // Create 2 organization-wide services (public)
            $orgServices = Service::factory(2)
                ->forOrganization($organization)
                ->public()
                ->create(['created_by' => $owner->id]);

            // Create 2-3 services per team (mix of public and private)
            $teams->each(function ($team) {
                $teamServices = Service::factory(rand(2, 3))
                    ->forTeam($team)
                    ->create(['created_by' => $team->users()->wherePivot('role', 'lead')->first()->id]);
            });

            // Create incidents for some services
            $allServices = $organization->services;
            $allServices->random(rand(3, 5))->each(function ($service) use ($organization) {
                $orgUsers = $organization->users;

                Incident::factory(rand(1, 3))
                    ->forService($service)
                    ->create([
                        'created_by' => $orgUsers->random()->id,
                        'resolved_by' => rand(0, 1) ? $orgUsers->random()->id : null,
                    ]);
            });

            // Create some maintenance windows
            $allServices->random(rand(2, 4))->each(function ($service) use ($organization) {
                $orgUsers = $organization->users;

                Maintenance::factory(rand(1, 2))
                    ->forService($service)
                    ->create(['created_by' => $orgUsers->random()->id]);
            });

            // Create some organization-wide maintenance
            Maintenance::factory(1)
                ->organizationWide()
                ->create([
                    'organization_id' => $organization->id,
                    'created_by' => $owner->id,
                ]);
        });

        // Call the demo data seeder for XKCD-Robotics organization
        $this->call(DemoDataSeeder::class);
    }
}
