<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use App\Http\Resources\IncidentResource;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use App\Http\Resources\ServiceResource;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Validation\Rules\Enum;

class IncidentController extends Controller
{
    /**
     * Display a listing of the incidents.
     */
    public function index(Request $request): Response
    {
        $organization = Auth::user()->organization;
        $incidents = Incident::forOrganization($organization->id)->get();
        return Inertia::render('incidents/index', [
            'incidents' => IncidentResource::collection($incidents),
        ]);
    }

    /**
     * Show the form for creating a new incident.
     */
    public function create(): Response
    {
        $organization = Auth::user()->organization;
        $services = Service::forOrganization($organization->id)->get();
        return Inertia::render('incidents/create', [
            'services' => ServiceResource::collection($services),
        ]);
    }

    /**
     * API: List incidents
     */
    public function apiIndex(Request $request)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);
        $organization = $user->organization;
        $incidents = Incident::forOrganization($organization->id)->get();
        return IncidentResource::collection($incidents);
    }

    /**
     * Store a newly created incident.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $organization = $user->organization;
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => ['required', new Enum(IncidentStatus::class)],
            'severity' => ['required', new Enum(IncidentSeverity::class)],
        ]);
        $incident = $organization->incidents()->create($validated);
        Broadcast::event('incident.status.updated', $incident);
        return redirect()->route('incidents.index')->with('success', 'Incident created.');
    }

    /**
     * API: Store incident
     */
    public function apiStore(Request $request)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);
        $organization = $user->organization;
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => ['required', new Enum(IncidentStatus::class)],
            'severity' => ['required', new Enum(IncidentSeverity::class)],
        ]);
        $incident = $organization->incidents()->create($validated);
        Broadcast::event('incident.status.updated', $incident);
        return new IncidentResource($incident);
    }

    /**
     * Show the form for editing the specified incident.
     */
    public function edit(Incident $incident): Response
    {
        $this->authorize('update', $incident);
        return Inertia::render('incidents/edit', [
            'incident' => new IncidentResource($incident),
        ]);
    }

    /**
     * Update the specified incident.
     */
    public function update(Request $request, Incident $incident)
    {
        $this->authorize('update', $incident);
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => ['required', new Enum(IncidentStatus::class)],
            'severity' => ['required', new Enum(IncidentSeverity::class)],
        ]);
        $incident->update($validated);
        Broadcast::event('incident.status.updated', $incident);
        return redirect()->route('incidents.index')->with('success', 'Incident updated.');
    }

    /**
     * API: Update incident
     */
    public function apiUpdate(Request $request, Incident $incident)
    {
        $this->authorize('update', $incident);
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => ['required', new Enum(IncidentStatus::class)],
            'severity' => ['required', new Enum(IncidentSeverity::class)],
        ]);
        $incident->update($validated);
        Broadcast::event('incident.status.updated', $incident);
        return new IncidentResource($incident);
    }

    /**
     * Remove the specified incident.
     */
    public function destroy(Incident $incident)
    {
        $this->authorize('delete', $incident);
        $incident->delete();
        return redirect()->route('incidents.index')->with('success', 'Incident deleted.');
    }

    /**
     * API: Delete incident
     */
    public function apiDestroy(Incident $incident)
    {
        $this->authorize('delete', $incident);
        $incident->delete();
        return response()->json(['message' => 'Incident deleted.']);
    }
} 