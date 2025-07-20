<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Service;
use App\Models\ServiceStatusLog;
use App\Services\UptimeMetricsService;
use Carbon\Carbon;

class TestUptimeCalculation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:uptime {service_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test uptime calculation for services';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $serviceId = $this->argument('service_id');
        $uptimeService = new UptimeMetricsService();

        if ($serviceId) {
            $service = Service::find($serviceId);
            if (!$service) {
                $this->error("Service with ID {$serviceId} not found.");
                return 1;
            }
            $this->testServiceUptime($service, $uptimeService);
        } else {
            $services = Service::all();
            foreach ($services as $service) {
                $this->testServiceUptime($service, $uptimeService);
            }
        }

        return 0;
    }

    private function testServiceUptime(Service $service, UptimeMetricsService $uptimeService)
    {
        $this->info("\n=== Testing Service: {$service->name} (ID: {$service->id}) ===");
        $this->info("Current Status: {$service->status}");

        // Get status logs
        $logs = ServiceStatusLog::forService($service->id)->orderBy('changed_at')->get();
        $this->info("Total Status Logs: " . $logs->count());

        if ($logs->count() > 0) {
            $this->info("Status Log History:");
            foreach ($logs as $log) {
                $this->info("  {$log->changed_at}: {$log->status_from} -> {$log->status_to}");
            }
        }

        // Test 24-hour period specifically
        $startDate = now()->subDay();
        $endDate = now();
        
        // Get the status before the period started
        $initialLog = ServiceStatusLog::forService($service->id)
            ->where('changed_at', '<', $startDate)
            ->orderBy('changed_at', 'desc')
            ->first();
        
        $initialStatus = $initialLog ? $initialLog->status_to : $service->status;
        $this->info("Initial Status for 24h period: {$initialStatus}");
        $this->info("Period: {$startDate->toDateTimeString()} to {$endDate->toDateTimeString()}");
        
        // Get logs within the period
        $periodLogs = ServiceStatusLog::forService($service->id)
            ->whereBetween('changed_at', [$startDate, $endDate])
            ->orderBy('changed_at')
            ->get();
        
        $this->info("Logs within 24h period: " . $periodLogs->count());
        foreach ($periodLogs as $log) {
            $this->info("  {$log->changed_at}: {$log->status_from} -> {$log->status_to}");
        }
        
        $uptime = $uptimeService->calculateUptimeForPeriod($service, $startDate, $endDate);
        $this->info("Calculated Uptime for 24h: {$uptime}%");

        // Test different periods
        $periods = ['24h', '7d', '30d'];
        foreach ($periods as $period) {
            $startDate = $this->getStartDateForPeriod($period);
            $endDate = now();
            
            $uptime = $uptimeService->calculateUptimeForPeriod($service, $startDate, $endDate);
            
            $this->info("Uptime for {$period}: {$uptime}%");
            $this->info("  Period: {$startDate->toDateTimeString()} to {$endDate->toDateTimeString()}");
        }

        // Test chart data
        $chartData = $uptimeService->getUptimeChartData($service, '30d');
        $this->info("Chart data points: " . count($chartData));
        if (count($chartData) > 0) {
            $this->info("First 3 data points:");
            foreach (array_slice($chartData, 0, 3) as $point) {
                $this->info("  {$point['label']}: {$point['uptime']}%");
            }
        }
    }

    private function getStartDateForPeriod(string $period): Carbon
    {
        return match ($period) {
            '24h' => now()->subDay(),
            '7d' => now()->subWeek(),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            default => now()->subDays(30),
        };
    }
} 