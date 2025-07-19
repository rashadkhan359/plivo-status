<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Enums\ServiceStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\ServiceResource;
use App\Events\ServiceStatusChanged;
use App\Events\ServiceCreated;
use App\Events\ServiceUpdated;
use Illuminate\Validation\Rules\Enum;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $organization = $this->getCurrentOrganization();
        $user = Auth::user();
        
        $services = $this->getAccessibleServices($user, $organization);
        return ServiceResource::collection($services);
    }

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
        event(new ServiceUpdated($service));
        event(new ServiceStatusChanged($service));
        
        return new ServiceResource($service);
    }

    public function show(Service $service)
    {
        $this->authorize('view', $service);
        $this->validateResourceBelongsToOrganization($service);
        
        return new ServiceResource($service);
    }

    public function update(Request $request, Service $service)
    {
        $this->authorize('update', $service);
        $this->validateResourceBelongsToOrganization($service);
        
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
        
        $oldStatus = $service->status;
        $service->update($validated);
        
        // Fire event if status changed
        if ($oldStatus !== $validated['status']) {
            event(new ServiceStatusChanged($service));
        }
        
        return new ServiceResource($service);
    }

    public function destroy(Service $service)
    {
        $this->authorize('delete', $service);
        $this->validateResourceBelongsToOrganization($service);
        
        $service->delete();
        return response()->json(['message' => 'Service deleted.']);
    }

    public function updateStatus(Request $request, Service $service)
    {
        $this->authorize('update', $service);
        $this->validateResourceBelongsToOrganization($service);
        
        $validated = $request->validate([
            'status' => ['required', new Enum(ServiceStatus::class)],
        ]);
        
        $oldStatus = $service->status;
        $service->update(['status' => $validated['status']]);
        
        // Fire event if status changed
        if ($oldStatus !== $validated['status']) {
            event(new ServiceStatusChanged($service));
        }
        
        return new ServiceResource($service);
    }
} 