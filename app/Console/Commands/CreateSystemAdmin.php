<?php

namespace App\Console\Commands;

use App\Services\SystemAdminService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateSystemAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create 
                            {--name=System Admin : The name of the system admin}
                            {--email=admin@plivo-status.com : The email of the system admin}
                            {--password= : The password for the system admin (will prompt if not provided)}
                            {--force : Force creation even if system admin exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a system admin user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->option('name');
        $email = $this->option('email');
        $password = $this->option('password');
        $force = $this->option('force');

        // Validate input
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return 1;
        }

        $adminService = app(SystemAdminService::class);

        // Check if system admin already exists
        if ($adminService->exists() && !$force) {
            $this->warn('A system admin already exists. Use --force to create another one.');
            return 0;
        }

        try {
            // Create the system admin
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