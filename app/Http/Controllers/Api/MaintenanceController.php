<?php

namespace App\Http\Controllers\Api;

use App\Models\Maintenance;
use Illuminate\Http\Request;
use App\Http\Resources\MaintenanceResource;

class MaintenanceController
{
    public function index(Request $request)
    {
        $maintenances = Maintenance::forOrganization($request->user()->organization_id)->get();
        return MaintenanceResource::collection($maintenances);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scheduled_start' => 'required|date',
            'scheduled_end' => 'required|date|after:scheduled_start',
            'status' => 'required|in:scheduled,in_progress,completed',
        ]);
        $maintenance = Maintenance::create(array_merge($validated, [
            'organization_id' => $request->user()->organization_id,
        ]));
        return new MaintenanceResource($maintenance);
    }

    public function show(Maintenance $maintenance)
    {
        return new MaintenanceResource($maintenance);
    }

    public function update(Request $request, Maintenance $maintenance)
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scheduled_start' => 'required|date',
            'scheduled_end' => 'required|date|after:scheduled_start',
            'status' => 'required|in:scheduled,in_progress,completed',
        ]);
        $maintenance->update($validated);
        return new MaintenanceResource($maintenance);
    }

    public function destroy(Maintenance $maintenance)
    {
        $maintenance->delete();
        return response()->json(['message' => 'Maintenance deleted.']);
    }
} 