<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;
use App\Models\Organization;
use App\Models\Service;
use App\Models\Incident;
use App\Models\IncidentUpdate;
use App\Models\Maintenance;
use App\Services\UptimeMetricsService;
use App\Services\PermissionService;

class DashboardController extends Controller
{
    /**
     * Show the dashboard overview.
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $organization = $this->getCurrentOrganization();
        $permissionService = app(PermissionService::class);

        // All users (including system admins) see data from their organization only
        $services = Service::forOrganization($organization->id)->with('incidents')->get();
        $incidents = Incident::forOrganization($organization->id)->latest()->take(10)->get();
        $incidentIds = $incidents->pluck('id');
        $incidentUpdates = IncidentUpdate::whereIn('incident_id', $incidentIds)
            ->latest()
            ->take(15)
            ->get();
        $maintenances = Maintenance::forOrganization($organization->id)->with('service')->latest()->take(5)->get();

        // Get uptime metrics for admin/owner users
        $uptimeMetrics = null;
        $organizationUptime = null;
        $serviceUptimes = null;
        
        // Check if user can view analytics (admin/owner level access)
        if ($user->isSystemAdmin() || 
            $permissionService->userHasOrganizationPermission($user, $organization, 'view_analytics') ||
            $permissionService->userHasOrganizationPermission($user, $organization, 'manage_organization')) {
            
            $uptimeService = app(UptimeMetricsService::class);
            
            // Get organization-wide uptime average
            $organizationUptime = $uptimeService->getOrganizationUptimeAverage($services, '30d');
            
            // Get service-level uptime metrics
            $serviceUptimes = $uptimeService->getBulkUptimeMetrics($services, '30d');
        }

        return Inertia::render('dashboard', [
            'services' => \App\Http\Resources\ServiceResource::collection($services),
            'incidents' => \App\Http\Resources\IncidentResource::collection($incidents),
            'incidentUpdates' => \App\Http\Resources\IncidentUpdateResource::collection($incidentUpdates),
            'maintenances' => \App\Http\Resources\MaintenanceResource::collection($maintenances),
            'stats' => [
                'servicesCount' => $services->count(),
                'incidentsCount' => Incident::forOrganization($organization->id)->count(),
                'maintenancesCount' => Maintenance::forOrganization($organization->id)->count(),
            ],
            'uptimeMetrics' => [
                'organizationUptime' => $organizationUptime,
                'serviceUptimes' => $serviceUptimes,
                'canViewMetrics' => !is_null($organizationUptime),
            ],
        ]);
    }

    /**
     * API: Get dashboard overview data.
     */
    public function apiOverview(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $organization = $this->getCurrentOrganization();

        return response()->json([
            'services' => Service::forOrganization($organization->id)->count(),
            'incidents' => Incident::forOrganization($organization->id)->count(),
            'maintenances' => Maintenance::forOrganization($organization->id)->count(),
        ]);
    }
}
