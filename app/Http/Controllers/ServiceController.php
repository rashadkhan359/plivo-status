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
use App\Events\ServiceCreated;
use App\Events\ServiceUpdated;
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
        
        $organization = $this->getCurrentOrganization();
        $user = Auth::user();
        
        // Get services based on user's role and team memberships
        $services = $this->getAccessibleServices($user, $organization);
        
        // Apply pagination
        $services = $services->paginate(12)->withQueryString();
        
        $teams = $organization->teams()->get();
        
        return Inertia::render('services/index', [
            'services' => ServiceResource::collection($services),
            'teams' => $teams,
        ]);
    }

    /**
     * Show the form for creating a new service.
     */
    public function create(): Response
    {
        $this->authorize('create', Service::class);
        
        $organization = $this->getCurrentOrganization();
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
        
        $organization = $this->getCurrentOrganization();
        $user = Auth::user();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => ['required', new Enum(ServiceStatus::class)],
            'team_id' => 'nullable|string',
            'visibility' => 'required|in:public,private',
            'order' => 'nullable|integer|min:0',
        ]);
        
        // Convert "none" to null for team_id
        if (isset($validated['team_id']) && $validated['team_id'] === 'none') {
            $validated['team_id'] = null;
        }
        
        // Validate team belongs to organization if team_id is not null
        if ($validated['team_id']) {
            $this->validateTeamBelongsToOrganization($validated['team_id']);
        }
        
        $service = $organization->services()->create([
            ...$validated,
            'created_by' => $user->id,
            'order' => $validated['order'] ?? $organization->services()->max('order') + 1,
        ]);
        
        event(new ServiceCreated($service));
        event(new ServiceStatusChanged($service));
        
        return redirect()->route('services.index')->with('success', 'Service created successfully.');
    }

    /**
     * Show the form for editing the specified service.
     */
    public function edit(Service $service): Response
    {
        $this->authorize('update', $service);
        
        $organization = $this->getCurrentOrganization();
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
        
        $organization = $this->getCurrentOrganization();
        
        // Check if this is a status-only update
        $statusOnlyFields = ['status', 'create_incident', 'incident_message', 'affected_services'];
        $requestFields = array_keys($request->all());
        $isStatusOnlyUpdate = $request->has('status') && 
            count(array_intersect($requestFields, $statusOnlyFields)) === count($requestFields);
        
        if ($isStatusOnlyUpdate) {
            // For status-only updates, only validate status-related fields
            $validated = $request->validate([
                'status' => ['required', new Enum(ServiceStatus::class)],
                'create_incident' => 'boolean',
                'incident_message' => 'nullable|string|required_if:create_incident,true',
                'affected_services' => 'nullable|array',
                'affected_services.*' => 'exists:services,id',
            ]);
            
            // Only update the status field
            $oldStatus = $service->status;
            $service->updateStatus($validated['status']);
        } else {
            // For full updates, validate all fields
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'status' => ['required', new Enum(ServiceStatus::class)],
                'team_id' => 'nullable|string',
                'visibility' => 'required|in:public,private',
                'order' => 'nullable|integer|min:0',
                'create_incident' => 'boolean',
                'incident_message' => 'nullable|string|required_if:create_incident,true',
                'affected_services' => 'nullable|array',
                'affected_services.*' => 'exists:services,id',
            ]);

            // Convert "none" to null for team_id
            if (isset($validated['team_id']) && $validated['team_id'] === 'none') {
                $validated['team_id'] = null;
            }

            // Validate team belongs to organization if team_id is not null
            if ($validated['team_id']) {
                $this->validateTeamBelongsToOrganization($validated['team_id']);
            }

            $oldStatus = $service->status;
            $service->update($validated);
        }

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
                'service_id' => $service->id, // Use the current service as primary
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

        event(new ServiceUpdated($service));
        event(new ServiceStatusChanged($service));
        return redirect()->route('services.index')->with('success', 'Service updated successfully.');
    }

    /**
     * Update only the status of a service.
     */
    public function updateStatus(Request $request, Service $service)
    {
        $this->authorize('update', $service);
        
        $organization = $this->getCurrentOrganization();
        
        $validated = $request->validate([
            'status' => ['required', new Enum(ServiceStatus::class)],
            'create_incident' => 'boolean',
            'incident_message' => 'nullable|string|required_if:create_incident,true',
            'affected_services' => 'nullable|array',
            'affected_services.*' => 'exists:services,id',
        ]);

        $oldStatus = $service->status;
        $service->updateStatus($validated['status']);

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
                'service_id' => $service->id, // Use the current service as primary
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

        return redirect()->back()->with('success', 'Service status updated successfully.');
    }

    /**
     * Remove the specified service.
     */
    public function destroy(Service $service)
    {
        $this->authorize('delete', $service);
        $service->delete();
        return redirect()->route('services.index')->with('success', 'Service deleted successfully.');
    }
} 