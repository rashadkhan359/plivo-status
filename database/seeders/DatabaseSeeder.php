<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Organization;
use App\Models\Service;
use App\Models\Incident;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create 3 organizations, each with 1 admin and 2 members, 3 services, and 2 incidents per service
        Organization::factory(3)->create()->each(function ($org) {
            // Admin user
            $admin = User::factory()->withOrganization($org)->create();
            // Member users
            User::factory(2)->member()->withOrganization($org)->create();
            // Services
            $services = Service::factory(3)->withOrganization($org)->create();
            // Incidents for each service
            $services->each(function ($service) use ($org) {
                Incident::factory(2)->withService($service)->create();
            });
        });
    }
}
