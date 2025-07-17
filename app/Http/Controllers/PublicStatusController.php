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

        // Get the IDs of the incidents for the organization
        $incidentIds = $incidents->pluck('id');

        // Fetch the latest 15 updates for those incidents
        $updates = \App\Models\IncidentUpdate::whereIn('incident_id', $incidentIds)
            ->latest()
            ->take(15)
            ->get();

        return Inertia::render('public-status-page', [
            'organization' => $organization,
            'services' => ServiceResource::collection($services),
            'incidents' => IncidentResource::collection($incidents),
            'maintenances' => MaintenanceResource::collection($maintenances),
            'updates' => \App\Http\Resources\IncidentUpdateResource::collection($updates),
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
