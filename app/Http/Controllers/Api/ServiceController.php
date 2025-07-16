<?php

namespace App\Http\Controllers\Api;

use App\Models\Service;
use Illuminate\Http\Request;
use App\Http\Resources\ServiceResource;

class ServiceController
{
    public function index(Request $request)
    {
        $services = Service::forOrganization($request->user()->organization_id)->get();
        return ServiceResource::collection($services);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:operational,degraded,partial_outage,major_outage',
        ]);
        $service = Service::create(array_merge($validated, [
            'organization_id' => $request->user()->organization_id,
        ]));
        return new ServiceResource($service);
    }

    public function show(Service $service)
    {
        return new ServiceResource($service);
    }

    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:operational,degraded,partial_outage,major_outage',
        ]);
        $service->update($validated);
        return new ServiceResource($service);
    }

    public function destroy(Service $service)
    {
        $service->delete();
        return response()->json(['message' => 'Service deleted.']);
    }

    public function updateStatus(Request $request, Service $service)
    {
        $validated = $request->validate([
            'status' => 'required|in:operational,degraded,partial_outage,major_outage',
        ]);
        $service->update(['status' => $validated['status']]);
        return new ServiceResource($service);
    }
} 