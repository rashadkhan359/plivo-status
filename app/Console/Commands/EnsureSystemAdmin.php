<?php

namespace App\Console\Commands;

use App\Services\SystemAdminService;
use Illuminate\Console\Command;

class EnsureSystemAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:ensure 
                            {--name=System Admin : The name of the system admin}
                            {--email=admin@plivo-status.com : The email of the system admin}
                            {--password=password : The password for the system admin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ensure a system admin exists, create one if needed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->option('name');
        $email = $this->option('email');
        $password = $this->option('password');

        $adminService = app(SystemAdminService::class);

        // Check if system admin already exists
        if ($adminService->exists()) {
            $admin = $adminService->get();
            $this->info("System admin already exists:");
            $this->info("Name: {$admin->name}");
            $this->info("Email: {$admin->email}");
            return 0;
        }

        try {
            // Create system admin
            $admin = $adminService->create($name, $email, $password);

            $this->info("System admin created successfully!");
            $this->info("Name: {$admin->name}");
            $this->info("Email: {$admin->email}");
            $this->info("Password: {$password}");

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to create system admin: " . $e->getMessage());
            return 1;
        }
    }
} 