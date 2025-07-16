<?php

namespace App\Http\Controllers;

use App\Models\Maintenance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use App\Http\Resources\MaintenanceResource;
use Illuminate\Support\Facades\Broadcast;

class MaintenanceController extends Controller
{
    /**
     * Display a listing of the maintenances.
     */
    public function index(Request $request): Response
    {
        $organization = Auth::user()->organization;
        $maintenances = Maintenance::forOrganization($organization->id)->get();
        return Inertia::render('maintenances/index', [
            'maintenances' => MaintenanceResource::collection($maintenances),
        ]);
    }

    /**
     * API: List maintenances
     */
    public function apiIndex(Request $request)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);
        $organization = $user->organization;
        $maintenances = Maintenance::forOrganization($organization->id)->get();
        return MaintenanceResource::collection($maintenances);
    }

    /**
     * Store a newly created maintenance.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $organization = $user->organization;
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scheduled_start' => 'required|date',
            'scheduled_end' => 'required|date|after:scheduled_start',
            'status' => 'required|in:scheduled,in_progress,completed',
        ]);
        $maintenance = $organization->maintenances()->create($validated);
        Broadcast::event('maintenance.status.updated', $maintenance);
        return redirect()->route('maintenances.index')->with('success', 'Maintenance created.');
    }

    /**
     * API: Store maintenance
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
            'scheduled_start' => 'required|date',
            'scheduled_end' => 'required|date|after:scheduled_start',
            'status' => 'required|in:scheduled,in_progress,completed',
        ]);
        $maintenance = $organization->maintenances()->create($validated);
        Broadcast::event('maintenance.status.updated', $maintenance);
        return new MaintenanceResource($maintenance);
    }

    /**
     * Show the form for editing the specified maintenance.
     */
    public function edit(Maintenance $maintenance): Response
    {
        $this->authorize('update', $maintenance);
        return Inertia::render('maintenances/edit', [
            'maintenance' => new MaintenanceResource($maintenance),
        ]);
    }

    /**
     * Update the specified maintenance.
     */
    public function update(Request $request, Maintenance $maintenance)
    {
        $this->authorize('update', $maintenance);
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scheduled_start' => 'required|date',
            'scheduled_end' => 'required|date|after:scheduled_start',
            'status' => 'required|in:scheduled,in_progress,completed',
        ]);
        $maintenance->update($validated);
        Broadcast::event('maintenance.status.updated', $maintenance);
        return redirect()->route('maintenances.index')->with('success', 'Maintenance updated.');
    }

    /**
     * API: Update maintenance
     */
    public function apiUpdate(Request $request, Maintenance $maintenance)
    {
        $this->authorize('update', $maintenance);
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scheduled_start' => 'required|date',
            'scheduled_end' => 'required|date|after:scheduled_start',
            'status' => 'required|in:scheduled,in_progress,completed',
        ]);
        $maintenance->update($validated);
        Broadcast::event('maintenance.status.updated', $maintenance);
        return new MaintenanceResource($maintenance);
    }

    /**
     * Remove the specified maintenance.
     */
    public function destroy(Maintenance $maintenance)
    {
        $this->authorize('delete', $maintenance);
        $maintenance->delete();
        return redirect()->route('maintenances.index')->with('success', 'Maintenance deleted.');
    }

    /**
     * API: Delete maintenance
     */
    public function apiDestroy(Maintenance $maintenance)
    {
        $this->authorize('delete', $maintenance);
        $maintenance->delete();
        return response()->json(['message' => 'Maintenance deleted.']);
    }
} 