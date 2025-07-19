<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\ServiceStatusService;
use Illuminate\Console\Command;

class RecalculateServiceStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'services:recalculate-statuses {--organization= : Organization ID to recalculate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate service statuses based on active incidents';

    /**
     * Execute the console command.
     */
    public function handle(ServiceStatusService $statusService)
    {
        $organizationId = $this->option('organization');
        
        if ($organizationId) {
            $organization = Organization::find($organizationId);
            
            if (!$organization) {
                $this->error("Organization with ID {$organizationId} not found.");
                return 1;
            }
            
            $this->info("Recalculating service statuses for organization: {$organization->name}");
            $statusService->recalculateAllServicesStatus($organization->id);
            $this->info('Service statuses recalculated successfully.');
        } else {
            $this->info('Recalculating service statuses for all organizations...');
            
            $organizations = Organization::all();
            $bar = $this->output->createProgressBar($organizations->count());
            
            foreach ($organizations as $organization) {
                $statusService->recalculateAllServicesStatus($organization->id);
                $bar->advance();
            }
            
            $bar->finish();
            $this->newLine();
            $this->info('All service statuses recalculated successfully.');
        }
        
        return 0;
    }
} 