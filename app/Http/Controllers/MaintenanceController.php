<?php

namespace App\Http\Controllers;

use App\Models\Maintenance;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;
use App\Enums\MaintenanceStatus;
use App\Http\Resources\MaintenanceResource;
use App\Http\Resources\ServiceResource;
use App\Events\MaintenanceScheduled;
use App\Events\MaintenanceUpdated;
use App\Events\MaintenanceStarted;
use App\Events\MaintenanceCompleted;
use Illuminate\Validation\Rules\Enum;

class MaintenanceController extends Controller
{
    /**
     * Display a listing of the maintenances.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Maintenance::class);
        
        $organization = $this->getCurrentOrganization();
        $user = Auth::user();
        
        // Get maintenances accessible to the user
        $maintenances = $this->getAccessibleMaintenances($user, $organization);
        
        // Apply pagination
        $maintenances = $maintenances->paginate(15)->withQueryString();

        return Inertia::render('maintenances/index', [
            'maintenances' => MaintenanceResource::collection($maintenances),
        ]);
    }

    /**
     * Show the form for creating a new maintenance.
     */
    public function create(Request $request): Response
    {
        $this->authorize('create', Maintenance::class);
        
        $organization = $this->getCurrentOrganization();
        $user = Auth::user();
        
        // Get services accessible to the user
        $services = $this->getAccessibleServices($user, $organization);
        
        return Inertia::render('maintenances/create', [
            'services' => ServiceResource::collection($services->get()),
            'organizations' => null, // No need to show all organizations
        ]);
    }

    /**
     * Store a newly created maintenance.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Maintenance::class);
        
        $organization = $this->getOrganizationForResource($request);
        $user = Auth::user();
        
        $validated = $request->validate([
            'service_id' => 'nullable|string',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => ['required', new Enum(MaintenanceStatus::class)],
            'scheduled_start' => 'required|date|after:now',
            'scheduled_end' => 'required|date|after:scheduled_start',
        ]);
        
        // For system admins, require organization_id
        if ($user->isSystemAdmin() && !$organization) {
            abort(422, 'Organization ID is required for system admins.');
        }
        
        // Handle "none" value for service_id
        if ($validated['service_id'] === 'none' || $validated['service_id'] === '') {
            $validated['service_id'] = null;
        } else {
            // Validate service exists and belongs to organization
            $service = $organization->services()
                ->where('id', $validated['service_id'])
                ->firstOrFail();
        }
        
        $maintenance = $organization->maintenances()->create([
            ...$validated,
            'created_by' => $user->id,
        ]);
        
        event(new MaintenanceScheduled($maintenance));
        
        return redirect()->route('maintenances.index')->with('success', 'Maintenance scheduled successfully.');
    }

    /**
     * Show the form for editing the specified maintenance.
     */
    public function edit(Maintenance $maintenance): Response
    {
        $this->authorize('update', $maintenance);
        
        $organization = $this->getCurrentOrganization();
        $user = Auth::user();
        
        // Get services accessible to the user
        $services = $this->getAccessibleServices($user, $organization);
        
        return Inertia::render('maintenances/edit', [
            'maintenance' => new MaintenanceResource($maintenance->load('service')),
            'services' => ServiceResource::collection($services->get()),
        ]);
    }

    /**
     * Update the specified maintenance.
     */
    public function update(Request $request, Maintenance $maintenance)
    {
        $this->authorize('update', $maintenance);
        
        $organization = $this->getCurrentOrganization();
        
        $validated = $request->validate([
            'service_id' => 'nullable|string',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => ['required', new Enum(MaintenanceStatus::class)],
            'scheduled_start' => 'required|date',
            'scheduled_end' => 'required|date|after:scheduled_start',
            'actual_start' => 'nullable|date',
            'actual_end' => 'nullable|date|after:actual_start',
        ]);
        
        // Handle "none" value for service_id
        if ($validated['service_id'] === 'none' || $validated['service_id'] === '') {
            $validated['service_id'] = null;
        } else {
            // Validate service exists and belongs to organization
            $service = $organization->services()
                ->where('id', $validated['service_id'])
                ->firstOrFail();
        }
        
        $oldStatus = $maintenance->status;
        $maintenance->update($validated);
        $maintenance->refresh(); // Refresh to get updated values
        
        // Dispatch appropriate events based on status changes
        if ($oldStatus !== $maintenance->status) {
            switch ($maintenance->status) {
                case MaintenanceStatus::IN_PROGRESS:
                    event(new MaintenanceStarted($maintenance));
                    break;
                case MaintenanceStatus::COMPLETED:
                    event(new MaintenanceCompleted($maintenance));
                    break;
            }
        }
        
        event(new MaintenanceUpdated($maintenance));
        
        return redirect()->route('maintenances.index')->with('success', 'Maintenance updated successfully.');
    }

    /**
     * Remove the specified maintenance.
     */
    public function destroy(Maintenance $maintenance)
    {
        $this->authorize('delete', $maintenance);
        $maintenance->delete();
        return redirect()->route('maintenances.index')->with('success', 'Maintenance deleted successfully.');
    }

    /**
     * Start the maintenance.
     */
    public function start(Maintenance $maintenance)
    {
        $this->authorize('update', $maintenance);
        
        $maintenance->update([
            'status' => MaintenanceStatus::IN_PROGRESS,
            'actual_start' => now(),
        ]);
        
        event(new MaintenanceStarted($maintenance));
        event(new MaintenanceUpdated($maintenance));
        
        return redirect()->route('maintenances.index')->with('success', 'Maintenance started successfully.');
    }

    /**
     * Complete the maintenance.
     */
    public function complete(Maintenance $maintenance)
    {
        $this->authorize('update', $maintenance);
        
        $maintenance->update([
            'status' => MaintenanceStatus::COMPLETED,
            'actual_end' => now(),
        ]);
        
        event(new MaintenanceCompleted($maintenance));
        event(new MaintenanceUpdated($maintenance));
        
        return redirect()->route('maintenances.index')->with('success', 'Maintenance completed successfully.');
    }

    /**
     * Get maintenances accessible to the user
     */
    protected function getAccessibleMaintenances($user, $organization)
    {
        // If no organization, return empty query
        if (!$organization) {
            return Maintenance::whereRaw('1 = 0'); // Return empty query builder
        }
        
        $query = $organization->maintenances()->with(['service', 'creator']);
        
        // If user is not admin/owner/system_admin, filter by their team's services
        if (!in_array($user->current_role ?? $user->role, ['owner', 'admin', 'system_admin'])) {
            $userTeamIds = $user->teams()->pluck('teams.id');
            
            $query->where(function ($q) use ($userTeamIds) {
                // Show maintenances for services in user's teams
                $q->whereHas('service', function ($subQ) use ($userTeamIds) {
                    $subQ->whereIn('team_id', $userTeamIds);
                })
                // Or maintenances without specific service
                ->orWhereNull('service_id');
            });
        }
        
        return $query->latest('scheduled_start');
    }

    /**
     * Get services accessible to the user
     */
    protected function getAccessibleServices($user, $organization)
    {
        // If no organization, return empty query
        if (!$organization) {
            return Service::whereRaw('1 = 0'); // Return empty query builder
        }
        
        $query = $organization->services()->with(['team']);
        
        // If user is not admin/owner/system_admin, filter by visibility and team membership
        if (!in_array($user->current_role ?? $user->role, ['owner', 'admin', 'system_admin'])) {
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