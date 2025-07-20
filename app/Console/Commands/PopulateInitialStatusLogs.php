<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Service;
use App\Models\ServiceStatusLog;
use Illuminate\Support\Facades\DB;

class PopulateInitialStatusLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'status:populate-initial-logs {--force : Force creation even if logs exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create initial status logs for services that don\'t have any status logs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $force = $this->option('force');
        
        $this->info('Populating initial status logs for services...');

        // Get all services
        $services = Service::all();
        
        $created = 0;
        $skipped = 0;

        foreach ($services as $service) {
            // Check if service already has status logs
            $hasLogs = ServiceStatusLog::forService($service->id)->exists();
            
            if ($hasLogs && !$force) {
                $this->line("Skipping {$service->name} (ID: {$service->id}) - already has status logs");
                $skipped++;
                continue;
            }

            if ($hasLogs && $force) {
                $this->line("Force mode: deleting existing logs for {$service->name} (ID: {$service->id})");
                ServiceStatusLog::forService($service->id)->delete();
            }

            // Create initial status log using the service's creation time
            try {
                $initialTime = $service->created_at ?: now();
                
                ServiceStatusLog::create([
                    'service_id' => $service->id,
                    'status_from' => null,
                    'status_to' => $service->status,
                    'changed_at' => $initialTime,
                    'changed_by' => $service->created_by,
                    'reason' => 'Initial status (populated automatically)',
                ]);

                $this->info("Created initial status log for {$service->name} (ID: {$service->id}) - Status: {$service->status}");
                $created++;
            } catch (\Exception $e) {
                $this->error("Failed to create initial status log for {$service->name} (ID: {$service->id}): {$e->getMessage()}");
            }
        }

        $this->info("\nCompleted!");
        $this->info("Created: {$created} initial status logs");
        $this->info("Skipped: {$skipped} services");
        
        return 0;
    }
} 