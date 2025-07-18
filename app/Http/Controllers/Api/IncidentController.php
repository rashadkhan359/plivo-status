<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Enums\IncidentStatus;
use App\Enums\IncidentSeverity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\IncidentResource;
use App\Events\IncidentCreated;
use App\Events\IncidentUpdated;
use App\Events\IncidentResolved;
use Illuminate\Validation\Rules\Enum;

class IncidentController extends Controller
{
    public function index(Request $request)
    {
        $organization = $this->getCurrentOrganization();
        $user = Auth::user();
        
        $incidents = $this->getAccessibleIncidents($user, $organization);
        return IncidentResource::collection($incidents);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Incident::class);
        
        $organization = $this->getCurrentOrganization();
        $user = Auth::user();
        
        $validated = $request->validate([
            'service_ids' => 'required|array|min:1',
            'service_ids.*' => 'exists:services,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => ['required', new Enum(IncidentStatus::class)],
            'severity' => ['required', new Enum(IncidentSeverity::class)],
        ]);
        
        // Validate services belong to organization
        $this->validateServicesBelongToOrganization($validated['service_ids']);
        
        $incident = $organization->incidents()->create([
            'service_id' => $validated['service_ids'][0], // Use first service as primary
            'title' => $validated['title'],
            'description' => $validated['description'],
            'status' => $validated['status'],
            'severity' => $validated['severity'],
            'created_by' => $user->id,
        ]);
        
        // Attach services to incident
        $incident->services()->attach($validated['service_ids']);
        
        event(new IncidentCreated($incident));
        
        return new IncidentResource($incident->load('services'));
    }

    public function show(Incident $incident)
    {
        $this->authorize('view', $incident);
        $this->validateResourceBelongsToOrganization($incident);
        
        return new IncidentResource($incident->load('services'));
    }

    public function update(Request $request, Incident $incident)
    {
        $this->authorize('update', $incident);
        $this->validateResourceBelongsToOrganization($incident);
        
        $validated = $request->validate([
            'service_ids' => 'required|array|min:1',
            'service_ids.*' => 'exists:services,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => ['required', new Enum(IncidentStatus::class)],
            'severity' => ['required', new Enum(IncidentSeverity::class)],
        ]);
        
        // Validate services belong to organization
        $this->validateServicesBelongToOrganization($validated['service_ids']);
        
        $wasResolved = $incident->status === IncidentStatus::RESOLVED;
        
        $incident->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'status' => $validated['status'],
            'severity' => $validated['severity'],
        ]);
        
        // Update service relationships
        $incident->services()->sync($validated['service_ids']);
        
        // Check if incident was resolved
        if (!$wasResolved && $validated['status'] === IncidentStatus::RESOLVED) {
            $incident->update(['resolved_by' => Auth::id(), 'resolved_at' => now()]);
            event(new IncidentResolved($incident));
        } else {
            event(new IncidentUpdated($incident));
        }
        
        return new IncidentResource($incident->load('services'));
    }

    public function destroy(Incident $incident)
    {
        $this->authorize('delete', $incident);
        $this->validateResourceBelongsToOrganization($incident);
        
        $incident->delete();
        return response()->json(['message' => 'Incident deleted.']);
    }

    public function resolve(Request $request, Incident $incident)
    {
        $this->authorize('update', $incident);
        $this->validateResourceBelongsToOrganization($incident);
        
        $incident->update([
            'status' => IncidentStatus::RESOLVED->value,
            'resolved_by' => Auth::id(),
            'resolved_at' => now()
        ]);
        
        event(new IncidentResolved($incident));
        
        return new IncidentResource($incident->load('services'));
    }
} 