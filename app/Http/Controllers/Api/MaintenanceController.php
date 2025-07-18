<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Maintenance;
use App\Enums\MaintenanceStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\MaintenanceResource;
use App\Events\MaintenanceScheduled;
use Illuminate\Validation\Rules\Enum;

class MaintenanceController extends Controller
{
    public function index(Request $request)
    {
        $organization = $this->getCurrentOrganization();
        $user = Auth::user();
        
        $maintenances = $this->getAccessibleMaintenances($user, $organization);
        return MaintenanceResource::collection($maintenances);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Maintenance::class);
        
        $organization = $this->getCurrentOrganization();
        $user = Auth::user();
        
        $validated = $request->validate([
            'service_id' => 'nullable|exists:services,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scheduled_start' => 'required|date|after:now',
            'scheduled_end' => 'required|date|after:scheduled_start',
            'status' => ['required', new Enum(MaintenanceStatus::class)],
        ]);
        
        // Validate service belongs to organization if provided
        if ($validated['service_id']) {
            $service = $organization->services()
                ->where('id', $validated['service_id'])
                ->firstOrFail();
        }
        
        $maintenance = $organization->maintenances()->create([
            ...$validated,
            'created_by' => $user->id,
        ]);
        
        event(new MaintenanceScheduled($maintenance));
        
        return new MaintenanceResource($maintenance->load('service'));
    }

    public function show(Maintenance $maintenance)
    {
        $this->authorize('view', $maintenance);
        $this->validateResourceBelongsToOrganization($maintenance);
        
        return new MaintenanceResource($maintenance->load('service'));
    }

    public function update(Request $request, Maintenance $maintenance)
    {
        $this->authorize('update', $maintenance);
        $this->validateResourceBelongsToOrganization($maintenance);
        
        $validated = $request->validate([
            'service_id' => 'nullable|exists:services,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scheduled_start' => 'required|date',
            'scheduled_end' => 'required|date|after:scheduled_start',
            'status' => ['required', new Enum(MaintenanceStatus::class)],
        ]);
        
        // Validate service belongs to organization if provided
        if ($validated['service_id']) {
            $service = $this->getCurrentOrganization()->services()
                ->where('id', $validated['service_id'])
                ->firstOrFail();
        }
        
        $maintenance->update($validated);
        
        return new MaintenanceResource($maintenance->load('service'));
    }

    public function destroy(Maintenance $maintenance)
    {
        $this->authorize('delete', $maintenance);
        $this->validateResourceBelongsToOrganization($maintenance);
        
        $maintenance->delete();
        return response()->json(['message' => 'Maintenance deleted.']);
    }
} 