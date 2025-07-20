<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Service;
use App\Models\Incident;
use App\Models\Maintenance;
use App\Services\UptimeMetricsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Http\Resources\ServiceResource;
use App\Http\Resources\IncidentResource;
use App\Http\Resources\MaintenanceResource;

class PublicStatusController extends Controller
{
    /**
     * Show the public status page for an organization.
     */
    public function show(Request $request, $slug): Response
    {
        $organization = Organization::where('slug', $slug)->firstOrFail();
        
        // Only show public services
        $services = Service::forOrganization($organization->id)
            ->where('visibility', 'public')
            ->get();
            
        // Only show public incidents
        $incidents = Incident::forOrganization($organization->id)
            ->latest()
            ->take(10)
            ->get();

        // Only show public maintenances
        $maintenances = Maintenance::forOrganization($organization->id)
            ->latest()
            ->take(5)
            ->get();

        // Get the IDs of the incidents for the organization
        $incidentIds = $incidents->pluck('id');

        // Fetch the latest 15 updates for those incidents
        $updates = \App\Models\IncidentUpdate::whereIn('incident_id', $incidentIds)
            ->latest()
            ->take(15)
            ->get();

        // Get basic uptime metrics for public display (30-day average for consistency)
        $uptimeService = app(UptimeMetricsService::class);
        $publicUptimeMetrics = [];
        $chartData = [];
        
        foreach ($services as $service) {
            // Get uptime percentage for 30 days (consistent with dashboard)
            $uptime = $uptimeService->calculateUptimeForPeriod($service, now()->subDays(30), now());
            $publicUptimeMetrics[] = [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'uptime_percentage' => round($uptime, 1),
                'period' => '30d',
            ];
            
            // Get 30-day chart data for visualization (daily data points)
            $serviceChartData = $uptimeService->getUptimeChartData($service, '30d');
            $chartData[$service->id] = array_map(function($point) {
                return [
                    'date' => \Carbon\Carbon::parse($point['timestamp'])->format('M-d'),
                    'uptime' => $point['uptime'],
                    'timestamp' => $point['timestamp'],
                ];
            }, $serviceChartData);
        }

        return Inertia::render('public-status-page', [
            'organization' => $organization,
            'services' => ServiceResource::collection($services),
            'incidents' => IncidentResource::collection($incidents),
            'maintenances' => MaintenanceResource::collection($maintenances),
            'updates' => \App\Http\Resources\IncidentUpdateResource::collection($updates),
            'uptimeMetrics' => $publicUptimeMetrics,
            'chartData' => $chartData,
        ]);
    }

    /**
     * API: Get public status data
     */
    public function apiShow(Request $request, $slug)
    {
        $organization = Organization::where('slug', $slug)->firstOrFail();
        
        // Only show public services
        $services = Service::forOrganization($organization->id)
            ->where('visibility', 'public')
            ->get();
            
        // Only show public incidents
        $incidents = Incident::forOrganization($organization->id)
            ->latest()
            ->take(10)
            ->get();

        // Only show public maintenances
        $maintenances = Maintenance::forOrganization($organization->id)
            ->latest()
            ->take(5)
            ->get();

        // Get the IDs of the incidents for the organization
        $incidentIds = $incidents->pluck('id');

        // Fetch the latest 15 updates for those incidents
        $updates = \App\Models\IncidentUpdate::whereIn('incident_id', $incidentIds)
            ->latest()
            ->take(15)
            ->get();

        return response()->json([
            'organization' => $organization,
            'services' => ServiceResource::collection($services),
            'incidents' => IncidentResource::collection($incidents),
            'maintenances' => MaintenanceResource::collection($maintenances),
            'updates' => \App\Http\Resources\IncidentUpdateResource::collection($updates),
        ]);
    }
}
