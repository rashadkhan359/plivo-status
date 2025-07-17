<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\IncidentUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use App\Http\Resources\IncidentUpdateResource;
use App\Events\IncidentUpdateCreated;

class IncidentUpdateController extends Controller
{
    /**
     * Show updates for an incident (Inertia).
     */
    public function index(Incident $incident): Response
    {
        $this->authorize('view', $incident);
        $updates = $incident->updates()->latest()->get();
        return Inertia::render('incidents/updates', [
            'incident' => $incident,
            'updates' => IncidentUpdateResource::collection($updates),
        ]);
    }

    /**
     * API: List updates for an incident
     */
    public function apiIndex(Incident $incident)
    {
        $this->authorize('view', $incident);
        $updates = $incident->updates()->latest()->get();
        return IncidentUpdateResource::collection($updates);
    }

    /**
     * Store a new update for an incident.
     */
    public function store(Request $request, Incident $incident)
    {
        $this->authorize('update', $incident);
        $validated = $request->validate([
            'message' => 'required|string',
            'status' => 'required|in:investigating,identified,monitoring,resolved',
        ]);
        $update = $incident->updates()->create($validated);
        event(new IncidentUpdateCreated($update));
        return redirect()->route('incidents.updates.index', $incident)->with('success', 'Update added.');
    }

    /**
     * API: Store update for incident
     */
    public function apiStore(Request $request, Incident $incident)
    {
        $this->authorize('update', $incident);
        $validated = $request->validate([
            'message' => 'required|string',
            'status' => 'required|in:investigating,identified,monitoring,resolved',
        ]);
        $update = $incident->updates()->create($validated);
        event(new IncidentUpdateCreated($update));
        return new IncidentUpdateResource($update);
    }
} 