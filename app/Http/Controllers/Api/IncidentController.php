<?php

namespace App\Http\Controllers\Api;

use App\Models\Incident;
use Illuminate\Http\Request;
use App\Http\Resources\IncidentResource;

class IncidentController
{
    public function index(Request $request)
    {
        $incidents = Incident::forOrganization($request->user()->organization_id)->get();
        return IncidentResource::collection($incidents);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:investigating,identified,monitoring,resolved',
            'severity' => 'required|in:low,medium,high,critical',
        ]);
        $incident = Incident::create(array_merge($validated, [
            'organization_id' => $request->user()->organization_id,
        ]));
        return new IncidentResource($incident);
    }

    public function show(Incident $incident)
    {
        return new IncidentResource($incident);
    }

    public function update(Request $request, Incident $incident)
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:investigating,identified,monitoring,resolved',
            'severity' => 'required|in:low,medium,high,critical',
        ]);
        $incident->update($validated);
        return new IncidentResource($incident);
    }

    public function destroy(Incident $incident)
    {
        $incident->delete();
        return response()->json(['message' => 'Incident deleted.']);
    }

    public function resolve(Request $request, Incident $incident)
    {
        $incident->update(['status' => 'resolved', 'resolved_at' => now()]);
        return new IncidentResource($incident);
    }
} 