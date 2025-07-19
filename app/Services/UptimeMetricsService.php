<?php

namespace App\Services;

use App\Models\Service;
use App\Models\ServiceStatusLog;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class UptimeMetricsService
{
    /**
     * Calculate uptime percentage for a service over different time periods.
     */
    public function calculateUptime(Service $service, array $periods = ['24h', '7d', '30d', '90d']): array
    {
        $metrics = [];
        
        foreach ($periods as $period) {
            $startDate = $this->getStartDateForPeriod($period);
            $endDate = now();
            
            $uptime = $this->calculateUptimeForPeriod($service, $startDate, $endDate);
            
            $metrics[$period] = [
                'uptime_percentage' => round($uptime, 2),
                'period' => $period,
                'start_date' => $startDate->toISOString(),
                'end_date' => $endDate->toISOString(),
            ];
        }
        
        return $metrics;
    }

    /**
     * Calculate uptime percentage for a specific time period.
     */
    public function calculateUptimeForPeriod(Service $service, Carbon $startDate, Carbon $endDate): float
    {
        // Get all status changes within the period
        $statusLogs = ServiceStatusLog::forService($service->id)
            ->withinDateRange($startDate, $endDate)
            ->orderBy('changed_at')
            ->get();

        // If no logs exist, assume the service was operational for the entire period
        if ($statusLogs->isEmpty()) {
            return $service->status === 'operational' ? 100.0 : 0.0;
        }

        $totalDuration = $endDate->diffInMinutes($startDate);
        $uptimeDuration = 0;

        // Get the initial status at the start of the period
        $initialLog = ServiceStatusLog::forService($service->id)
            ->where('changed_at', '<=', $startDate)
            ->orderBy('changed_at', 'desc')
            ->first();

        $currentStatus = $initialLog ? $initialLog->status_to : 'operational';
        $currentTime = $startDate;

        foreach ($statusLogs as $log) {
            $logTime = Carbon::parse($log->changed_at);
            
            // Add uptime duration if current status is operational
            if ($currentStatus === 'operational') {
                $uptimeDuration += $currentTime->diffInMinutes($logTime);
            }
            
            // Update current status and time
            $currentStatus = $log->status_to;
            $currentTime = $logTime;
        }

        // Handle the final period from last log to end date
        if ($currentStatus === 'operational') {
            $uptimeDuration += $currentTime->diffInMinutes($endDate);
        }

        return $totalDuration > 0 ? ($uptimeDuration / $totalDuration) * 100 : 0.0;
    }

    /**
     * Get uptime data for charts (hourly data points for the last period).
     */
    public function getUptimeChartData(Service $service, string $period = '7d'): array
    {
        $startDate = $this->getStartDateForPeriod($period);
        $endDate = now();
        
        $dataPoints = [];
        $interval = $this->getIntervalForPeriod($period);
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            $periodEnd = $current->copy()->add($interval);
            if ($periodEnd->gt($endDate)) {
                $periodEnd = $endDate;
            }

            $uptime = $this->calculateUptimeForPeriod($service, $current, $periodEnd);
            
            $dataPoints[] = [
                'timestamp' => $current->toISOString(),
                'uptime' => round($uptime, 2),
                'label' => $current->format($this->getDateFormatForPeriod($period)),
            ];

            $current->add($interval);
        }

        return $dataPoints;
    }

    /**
     * Get uptime metrics for multiple services.
     */
    public function getBulkUptimeMetrics(Collection $services, string $period = '30d'): array
    {
        $metrics = [];
        
        foreach ($services as $service) {
            $startDate = $this->getStartDateForPeriod($period);
            $endDate = now();
            $uptime = $this->calculateUptimeForPeriod($service, $startDate, $endDate);
            
            $metrics[] = [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'uptime_percentage' => round($uptime, 2),
                'status' => $service->status,
            ];
        }
        
        // Sort by uptime percentage descending
        usort($metrics, fn($a, $b) => $b['uptime_percentage'] <=> $a['uptime_percentage']);
        
        return $metrics;
    }

    /**
     * Get organization-wide uptime average.
     */
    public function getOrganizationUptimeAverage(Collection $services, string $period = '30d'): float
    {
        if ($services->isEmpty()) {
            return 100.0;
        }

        $totalUptime = 0;
        $serviceCount = $services->count();

        foreach ($services as $service) {
            $startDate = $this->getStartDateForPeriod($period);
            $endDate = now();
            $totalUptime += $this->calculateUptimeForPeriod($service, $startDate, $endDate);
        }

        return round($totalUptime / $serviceCount, 2);
    }

    /**
     * Get recent incidents affecting uptime.
     */
    public function getRecentIncidents(Service $service, string $period = '30d'): array
    {
        $startDate = $this->getStartDateForPeriod($period);
        
        $statusLogs = ServiceStatusLog::forService($service->id)
            ->withinDateRange($startDate, now())
            ->where('status_to', '!=', 'operational')
            ->orderBy('changed_at', 'desc')
            ->with('changedBy')
            ->get();

        $incidents = [];
        
        foreach ($statusLogs as $log) {
            // Find when service came back up
            $resolvedLog = ServiceStatusLog::forService($service->id)
                ->where('changed_at', '>', $log->changed_at)
                ->where('status_to', 'operational')
                ->orderBy('changed_at')
                ->first();

            $incidents[] = [
                'started_at' => $log->changed_at->toISOString(),
                'resolved_at' => $resolvedLog ? $resolvedLog->changed_at->toISOString() : null,
                'status' => $log->status_to,
                'duration_minutes' => $resolvedLog 
                    ? $log->changed_at->diffInMinutes($resolvedLog->changed_at)
                    : $log->changed_at->diffInMinutes(now()),
                'is_ongoing' => !$resolvedLog,
            ];
        }

        return $incidents;
    }

    /**
     * Get start date for a given period.
     */
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

    /**
     * Get interval for chart data points based on period.
     */
    private function getIntervalForPeriod(string $period): \DateInterval
    {
        return match ($period) {
            '24h' => new \DateInterval('PT1H'), // 1 hour
            '7d' => new \DateInterval('PT6H'),  // 6 hours
            '30d' => new \DateInterval('P1D'),  // 1 day
            '90d' => new \DateInterval('P3D'),  // 3 days
            default => new \DateInterval('P1D'),
        };
    }

    /**
     * Get date format for chart labels based on period.
     */
    private function getDateFormatForPeriod(string $period): string
    {
        return match ($period) {
            '24h' => 'H:i',
            '7d' => 'M-d H:i',
            '30d' => 'M-d',
            '90d' => 'M-d',
            default => 'M-d',
        };
    }
} 