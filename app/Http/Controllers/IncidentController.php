<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;
use App\Http\Resources\IncidentResource;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use App\Http\Resources\ServiceResource;
use App\Events\IncidentCreated;
use App\Events\IncidentUpdated;
use App\Events\IncidentResolved;
use Illuminate\Validation\Rules\Enum;

class IncidentController extends Controller
{
    /**
     * Display a listing of the incidents.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Incident::class);
        
        $organization = $this->getCurrentOrganization();
        $user = Auth::user();
        
        // Get incidents accessible to the user
        $incidents = $this->getAccessibleIncidents($user, $organization);
        
        // Apply pagination
        $incidents = $incidents->paginate(15)->withQueryString();
        
        return Inertia::render('incidents/index', [
            'incidents' => IncidentResource::collection($incidents),
        ]);
    }

    /**
     * Show the form for creating a new incident.
     */
    public function create(): Response
    {
        $this->authorize('create', Incident::class);
        
        $organization = $this->getCurrentOrganization();
        $user = Auth::user();
        
        // Get services accessible to the user
        $services = $this->getAccessibleServices($user, $organization);
        
        return Inertia::render('incidents/create', [
            'services' => ServiceResource::collection($services->get()),
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
        
        // Load the services relationship for the status service
        $incident->load('services');
        
        // Update service statuses based on incident severity
        $statusService = app(\App\Services\ServiceStatusService::class);
        $statusService->updateServiceStatusFromIncident($incident);
        
        event(new IncidentCreated($incident));
        
        return redirect()->route('incidents.index')->with('success', 'Incident created successfully.');
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
        $incident = $organization->incidents()->create([
            ...$validated,
            'created_by' => $user->id,
        ]);
        event(new IncidentCreated($incident));
        return new IncidentResource($incident);
    }

    /**
     * Show the form for editing the specified incident.
     */
    public function edit(Incident $incident): Response
    {
        $this->authorize('update', $incident);
        
        $organization = $this->getCurrentOrganization();
        $user = Auth::user();
        
        // Get services accessible to the user
        $services = $this->getAccessibleServices($user, $organization);
        
        return Inertia::render('incidents/edit', [
            'incident' => new IncidentResource($incident->load('services')),
            'services' => ServiceResource::collection($services->get()),
        ]);
    }

    /**
     * Update the specified incident.
     */
    public function update(Request $request, Incident $incident)
    {
        $this->authorize('update', $incident);
        
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
        
        // Check if user has permission to change services
        $canChangeServices = in_array($user->role, ['owner', 'admin']);
        
        $wasResolved = $incident->status === IncidentStatus::RESOLVED;
        
        $incident->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'status' => $validated['status'],
            'severity' => $validated['severity'],
        ]);
        
        // Only update service relationships if user has permission
        if ($canChangeServices) {
            $incident->services()->sync($validated['service_ids']);
            // Update primary service_id for backward compatibility
            $incident->update(['service_id' => $validated['service_ids'][0]]);
        }
        
        // Check if incident was resolved
        if (!$wasResolved && $validated['status'] === IncidentStatus::RESOLVED) {
            $incident->update(['resolved_by' => Auth::id(), 'resolved_at' => now()]);
            event(new IncidentResolved($incident));
        } else {
            event(new IncidentUpdated($incident));
        }
        
        // Update service statuses based on incident changes
        $statusService = app(\App\Services\ServiceStatusService::class);
        if (!$wasResolved && $validated['status'] === IncidentStatus::RESOLVED) {
            $statusService->handleIncidentResolved($incident);
        } else {
            $statusService->handleIncidentUpdated($incident);
        }
        
        return redirect()->route('incidents.index')->with('success', 'Incident updated successfully.');
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
        event(new IncidentUpdated($incident));
        return new IncidentResource($incident);
    }

    /**
     * Remove the specified incident.
     */
    public function destroy(Incident $incident)
    {
        $this->authorize('delete', $incident);
        $incident->delete();
        return redirect()->route('incidents.index')->with('success', 'Incident deleted successfully.');
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

    /**
     * Resolve an incident.
     */
    public function resolve(Incident $incident)
    {
        $this->authorize('update', $incident);
        
        $incident->update([
            'status' => IncidentStatus::RESOLVED,
            'resolved_by' => Auth::id(),
            'resolved_at' => now(),
        ]);
        
        // Update service statuses when incident is resolved
        $statusService = app(\App\Services\ServiceStatusService::class);
        $statusService->handleIncidentResolved($incident);
        
        event(new IncidentResolved($incident));
        
        return back()->with('success', 'Incident resolved successfully.');
    }

    /**
     * Get incidents accessible to the user
     */
    protected function getAccessibleIncidents($user, $organization)
    {
        // If no organization, return empty query
        if (!$organization) {
            return \App\Models\Incident::whereRaw('1 = 0'); // Return empty query builder
        }
        
        $query = $organization->incidents()->with(['services', 'creator', 'resolver']);
        
        // If user is not admin/owner, filter by their team's services
        if (!in_array($user->role, ['owner', 'admin'])) {
            $userTeamIds = $user->teams()->pluck('teams.id');
            
            // Get service IDs for user's teams
            $userServiceIds = $organization->services()
                ->whereIn('team_id', $userTeamIds)
                ->pluck('id');
                
            // Only show incidents that affect user's team services
            $query->whereHas('services', function ($q) use ($userServiceIds) {
                $q->whereIn('services.id', $userServiceIds);
            });
        }
        
        return $query->latest();
    }

    /**
     * Get services accessible to the user
     */
    protected function getAccessibleServices($user, $organization)
    {
        // If no organization, return empty query
        if (!$organization) {
            return \App\Models\Service::whereRaw('1 = 0'); // Return empty query builder
        }
        
        $query = $organization->services()->with(['team']);
        
        // If user is not admin/owner, filter by visibility and team membership
        if (!in_array($user->role, ['owner', 'admin'])) {
            $userTeamIds = $user->teams()->pluck('teams.id');
            
            $query->where(function ($q) use ($userTeamIds) {
                // Public services are always visible
                $q->where('visibility', 'public')
                  // Or private services where user is in the team
                  ->orWhere(function ($subQ) use ($userTeamIds) {
                      $subQ->where('visibility', 'private')
                           ->whereIn('team_id', $userTeamIds);
                  });
            });
        }
        
        return $query->orderBy('order');
    }
} 