<?php

namespace App\Console\Commands;

use App\Models\Service;
use App\Models\ServiceStatusLog;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PopulateServiceStatusLogs extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'status:populate-logs {--days=90 : Number of days to populate}';

    /**
     * The console command description.
     */
    protected $description = 'Populate sample service status logs for uptime metrics testing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $services = Service::all();
        
        if ($services->isEmpty()) {
            $this->error('No services found. Please create some services first.');
            return 1;
        }

        $this->info("Populating status logs for {$services->count()} services over {$days} days...");

        foreach ($services as $service) {
            $this->populateServiceLogs($service, $days);
        }

        $this->info('Status logs populated successfully!');
        return 0;
    }

    private function populateServiceLogs(Service $service, int $days): void
    {
        $startDate = now()->subDays($days);
        $currentDate = $startDate->copy();
        $currentStatus = 'operational';

        // Create initial log entry
        ServiceStatusLog::create([
            'service_id' => $service->id,
            'status_from' => null,
            'status_to' => $currentStatus,
            'changed_at' => $currentDate,
            'reason' => 'Initial status',
        ]);

        $this->info("  Populating logs for service: {$service->name}");

        // Simulate realistic uptime patterns
        while ($currentDate->lte(now())) {
            // 98% chance of staying operational, 2% chance of incident
            if ($currentStatus === 'operational') {
                if (random_int(1, 100) <= 2) {
                    // Service goes down
                    $newStatus = $this->getRandomDownStatus();
                    $this->createStatusChange($service, $currentDate, $currentStatus, $newStatus);
                    $currentStatus = $newStatus;
                }
            } else {
                // Service is down, 30% chance of recovery each check
                if (random_int(1, 100) <= 30) {
                    $this->createStatusChange($service, $currentDate, $currentStatus, 'operational');
                    $currentStatus = 'operational';
                }
            }

            // Advance time by 1-6 hours randomly
            $currentDate->addHours(random_int(1, 6));
        }

        // Ensure service ends in operational state
        if ($currentStatus !== 'operational') {
            $this->createStatusChange($service, now(), $currentStatus, 'operational');
        }

        $this->info("    Created logs for {$service->name}");
    }

    private function createStatusChange(Service $service, Carbon $timestamp, string $from, string $to): void
    {
        ServiceStatusLog::create([
            'service_id' => $service->id,
            'status_from' => $from,
            'status_to' => $to,
            'changed_at' => $timestamp,
            'reason' => $this->getReasonForStatusChange($to),
        ]);
    }

    private function getRandomDownStatus(): string
    {
        $statuses = ['degraded', 'partial_outage', 'major_outage'];
        $weights = [60, 30, 10]; // Higher chance for less severe issues
        
        $random = random_int(1, 100);
        $cumulative = 0;
        
        foreach ($weights as $index => $weight) {
            $cumulative += $weight;
            if ($random <= $cumulative) {
                return $statuses[$index];
            }
        }
        
        return 'degraded';
    }

    private function getReasonForStatusChange(string $status): string
    {
        $reasons = [
            'operational' => [
                'Issue resolved',
                'Maintenance completed',
                'Systems restored',
                'Service recovery confirmed',
            ],
            'degraded' => [
                'Performance issues detected',
                'High latency reported',
                'Database slowdown',
                'Network congestion',
            ],
            'partial_outage' => [
                'Service partially unavailable',
                'Regional connectivity issues',
                'Database connection problems',
                'API rate limiting',
            ],
            'major_outage' => [
                'Complete service outage',
                'Critical system failure',
                'Infrastructure down',
                'Emergency maintenance required',
            ],
        ];

        $statusReasons = $reasons[$status] ?? ['Status changed'];
        return $statusReasons[array_rand($statusReasons)];
    }
}
