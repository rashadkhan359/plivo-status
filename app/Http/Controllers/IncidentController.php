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
        
        return Inertia::render('incidents/index', [
            'incidents' => IncidentResource::collection($incidents),
            'canCreate' => $user->can('create', Incident::class),
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
        $serviceIds = $validated['service_ids'];
        $validServices = $organization->services()
            ->whereIn('id', $serviceIds)
            ->pluck('id')
            ->toArray();
            
        if (count($validServices) !== count($serviceIds)) {
            return back()->withErrors(['service_ids' => 'Some selected services are invalid.']);
        }
        
        $incident = $organization->incidents()->create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'status' => $validated['status'],
            'severity' => $validated['severity'],
            'created_by' => $user->id,
        ]);
        
        // Attach services to incident
        $incident->services()->attach($serviceIds);
        
        event(new IncidentCreated($incident));
        
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
            'services' => ServiceResource::collection($services),
        ]);
    }

    /**
     * Update the specified incident.
     */
    public function update(Request $request, Incident $incident)
    {
        $this->authorize('update', $incident);
        
        $organization = $this->getCurrentOrganization();
        
        $validated = $request->validate([
            'service_ids' => 'required|array|min:1',
            'service_ids.*' => 'exists:services,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => ['required', new Enum(IncidentStatus::class)],
            'severity' => ['required', new Enum(IncidentSeverity::class)],
        ]);
        
        // Validate services belong to organization
        $serviceIds = $validated['service_ids'];
        $validServices = $organization->services()
            ->whereIn('id', $serviceIds)
            ->pluck('id')
            ->toArray();
            
        if (count($validServices) !== count($serviceIds)) {
            return back()->withErrors(['service_ids' => 'Some selected services are invalid.']);
        }
        
        $wasResolved = $incident->status === IncidentStatus::RESOLVED;
        
        $incident->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'status' => $validated['status'],
            'severity' => $validated['severity'],
        ]);
        
        // Update service relationships
        $incident->services()->sync($serviceIds);
        
        // Check if incident was resolved
        if (!$wasResolved && $validated['status'] === IncidentStatus::RESOLVED) {
            $incident->update(['resolved_by' => Auth::id(), 'resolved_at' => now()]);
            event(new IncidentResolved($incident));
        } else {
            event(new IncidentUpdated($incident));
        }
        
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

    /**
     * Resolve the specified incident.
     */
    public function resolve(Incident $incident)
    {
        $this->authorize('resolve', $incident);
        
        $incident->update([
            'status' => IncidentStatus::RESOLVED,
            'resolved_by' => Auth::id(),
            'resolved_at' => now(),
        ]);
        
        event(new IncidentResolved($incident));
        
        return redirect()->route('incidents.index')->with('success', 'Incident resolved.');
    }

    /**
     * Get incidents accessible to the user
     */
    protected function getAccessibleIncidents($user, $organization)
    {
        $query = $organization->incidents()->with(['services', 'creator', 'resolver']);
        
        // If user is not admin/owner, filter by their team's services
        if (!in_array($user->current_role ?? $user->role, ['owner', 'admin'])) {
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
        
        return $query->latest()->get();
    }

    /**
     * Get services accessible to the user
     */
    protected function getAccessibleServices($user, $organization)
    {
        $query = $organization->services()->with(['team']);
        
        // If user is not admin/owner, filter by visibility and team membership
        if (!in_array($user->current_role ?? $user->role, ['owner', 'admin'])) {
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
        
        return $query->orderBy('order')->get();
    }
} 