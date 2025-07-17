<?php

namespace App\Http\Controllers;

use App\Enums\ServiceStatus;
use App\Models\Service;
use App\Models\Team;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;
use App\Http\Resources\ServiceResource;
use App\Events\ServiceStatusChanged;
use App\Events\IncidentCreated;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;

class ServiceController extends Controller
{
    /**
     * Display a listing of the services.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Service::class);
        
        $organization = App::get('current_organization');
        $user = Auth::user();
        
        // Get services based on user's role and team memberships
        $services = $this->getAccessibleServices($user, $organization);
        
        $teams = $organization->teams()->get();
        
        return Inertia::render('services/index', [
            'services' => ServiceResource::collection($services),
            'teams' => $teams,
            'canCreate' => $user->can('create', Service::class),
        ]);
    }

    /**
     * Show the form for creating a new service.
     */
    public function create(): Response
    {
        $this->authorize('create', Service::class);
        
        $organization = App::get('current_organization');
        $teams = $organization->teams()->get();
        
        return Inertia::render('services/create', [
            'teams' => $teams,
        ]);
    }

    /**
     * Store a newly created service.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Service::class);
        
        $organization = App::get('current_organization');
        $user = Auth::user();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => ['required', new Enum(ServiceStatus::class)],
            'team_id' => 'nullable|exists:teams,id',
            'visibility' => 'required|in:public,private',
            'order' => 'nullable|integer|min:0',
        ]);
        
        // Validate team belongs to organization
        if ($validated['team_id']) {
            $team = Team::where('id', $validated['team_id'])
                ->where('organization_id', $organization->id)
                ->firstOrFail();
        }
        
        $service = $organization->services()->create([
            ...$validated,
            'created_by' => $user->id,
            'order' => $validated['order'] ?? $organization->services()->max('order') + 1,
        ]);
        
        event(new ServiceStatusChanged($service));
        
        return redirect()->route('services.index')->with('success', 'Service created.');
    }

    /**
     * Show the form for editing the specified service.
     */
    public function edit(Service $service): Response
    {
        $this->authorize('update', $service);
        
        $organization = App::get('current_organization');
        $teams = $organization->teams()->get();
        
        return Inertia::render('services/edit', [
            'service' => new ServiceResource($service->load('team')),
            'teams' => $teams,
        ]);
    }

    /**
     * Update the specified service.
     */
    public function update(Request $request, Service $service)
    {
        $this->authorize('update', $service);
        
        $organization = App::get('current_organization');
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => ['required', new Enum(ServiceStatus::class)],
            'team_id' => 'nullable|exists:teams,id',
            'visibility' => 'required|in:public,private',
            'order' => 'nullable|integer|min:0',
            'create_incident' => 'boolean',
            'incident_message' => 'nullable|string|required_if:create_incident,true',
            'affected_services' => 'nullable|array',
            'affected_services.*' => 'exists:services,id',
        ]);

        // Validate team belongs to organization
        if ($validated['team_id']) {
            $team = Team::where('id', $validated['team_id'])
                ->where('organization_id', $organization->id)
                ->firstOrFail();
        }

        $oldStatus = $service->status;
        $service->update($validated);

        // Create incident if requested and status is downgraded
        if (
            $request->boolean('create_incident') && 
            $validated['status'] !== 'operational' && 
            $request->filled('incident_message')
        ) {
            $severity = match($validated['status']) {
                'degraded' => IncidentSeverity::MEDIUM,
                'partial_outage' => IncidentSeverity::HIGH,
                'major_outage' => IncidentSeverity::CRITICAL,
                default => IncidentSeverity::LOW,
            };

            $incident = $organization->incidents()->create([
                'title' => "Service Status Change: {$service->name}",
                'description' => $request->input('incident_message'),
                'status' => IncidentStatus::INVESTIGATING,
                'severity' => $severity,
                'created_by' => Auth::id(),
            ]);
            
            // Attach affected services (including the updated service)
            $affectedServices = $request->input('affected_services', []);
            $affectedServices[] = $service->id;
            $incident->services()->attach(array_unique($affectedServices));

            event(new IncidentCreated($incident));
        }

        event(new ServiceStatusChanged($service));
        return redirect()->route('services.index')->with('success', 'Service updated.');
    }

    /**
     * Remove the specified service.
     */
    public function destroy(Service $service)
    {
        $this->authorize('delete', $service);
        $service->delete();
        return redirect()->route('services.index')->with('success', 'Service deleted.');
    }

    /**
     * Get services accessible to the user based on role and team membership
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