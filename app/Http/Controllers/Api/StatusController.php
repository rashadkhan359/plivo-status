<?php

namespace App\Http\Controllers\Api;

use App\Models\Organization;
use App\Models\Service;
use App\Models\Incident;
use Illuminate\Http\Request;
use App\Http\Resources\ServiceResource;
use App\Http\Resources\IncidentResource;

class StatusController
{
    public function show(Organization $organization)
    {
        return response()->json([
            'organization' => $organization,
            'services' => ServiceResource::collection($organization->services),
            'incidents' => IncidentResource::collection($organization->incidents()->latest()->take(10)->get()),
        ]);
    }

    public function services(Organization $organization)
    {
        return ServiceResource::collection($organization->services);
    }

    public function incidents(Organization $organization)
    {
        return IncidentResource::collection($organization->incidents()->latest()->take(10)->get());
    }
} 