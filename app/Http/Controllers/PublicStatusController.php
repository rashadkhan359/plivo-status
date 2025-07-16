<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Service;
use App\Models\Incident;
use App\Models\Maintenance;
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
        $services = Service::forOrganization($organization->id)->get();
        $incidents = Incident::forOrganization($organization->id)->latest()->take(10)->get();
        $maintenances = Maintenance::forOrganization($organization->id)->latest()->take(5)->get();
        return Inertia::render('public/status', [
            'organization' => $organization,
            'services' => ServiceResource::collection($services),
            'incidents' => IncidentResource::collection($incidents),
            'maintenances' => MaintenanceResource::collection($maintenances),
        ]);
    }

    /**
     * API: Get public status data
     */
    public function apiShow(Request $request, $slug)
    {
        $organization = Organization::where('slug', $slug)->firstOrFail();
        $services = Service::forOrganization($organization->id)->get();
        $incidents = Incident::forOrganization($organization->id)->latest()->take(10)->get();
        $maintenances = Maintenance::forOrganization($organization->id)->latest()->take(5)->get();
        return response()->json([
            'organization' => $organization,
            'services' => ServiceResource::collection($services),
            'incidents' => IncidentResource::collection($incidents),
            'maintenances' => MaintenanceResource::collection($maintenances),
        ]);
    }
} 