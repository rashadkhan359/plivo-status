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
use App\Models\Maintenance;

class DashboardController extends Controller
{
    /**
     * Show the dashboard overview.
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $organization = $this->getCurrentOrganization();

        $services = Service::forOrganization($organization->id)->with('incidents')->get();
        $incidents = Incident::forOrganization($organization->id)->with('services')->latest()->take(10)->get();
        $maintenances = Maintenance::forOrganization($organization->id)->with('service')->latest()->take(5)->get();

        return Inertia::render('dashboard', [
            'services' => \App\Http\Resources\ServiceResource::collection($services),
            'incidents' => \App\Http\Resources\IncidentResource::collection($incidents),
            'maintenances' => \App\Http\Resources\MaintenanceResource::collection($maintenances),
            'stats' => [
                'servicesCount' => $services->count(),
                'incidentsCount' => Incident::forOrganization($organization->id)->count(),
                'maintenancesCount' => Maintenance::forOrganization($organization->id)->count(),
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