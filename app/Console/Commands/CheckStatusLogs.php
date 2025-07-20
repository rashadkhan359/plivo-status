<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ServiceStatusLog;
use App\Models\Service;

class CheckStatusLogs extends Command
{
    protected $signature = 'check:status-logs';
    protected $description = 'Check service status logs';

    public function handle()
    {
        $logs = ServiceStatusLog::with('service')->get();
        
        $this->info("Total Status Logs: " . $logs->count());
        
        if ($logs->count() > 0) {
            $this->info("\nStatus Log Details:");
            foreach ($logs as $log) {
                $this->info("Service: {$log->service->name} | {$log->status_from} -> {$log->status_to} | {$log->changed_at}");
            }
        }
        
        return 0;
    }
} 