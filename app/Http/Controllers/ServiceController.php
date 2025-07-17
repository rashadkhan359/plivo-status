<?php

namespace App\Http\Controllers;

use App\Enums\ServiceStatus;
use App\Models\Service;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use App\Http\Resources\ServiceResource;
use Illuminate\Support\Facades\Broadcast;

class ServiceController extends Controller
{
    /**
     * Display a listing of the services.
     */
    public function index(Request $request): Response
    {
        $organization = Auth::user()->organization;
        $services = Service::forOrganization($organization->id)->get();
        return Inertia::render('services/index', [
            'services' => ServiceResource::collection($services),
        ]);
    }

    /**
     * API: List services
     */
    public function apiIndex(Request $request)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);
        $organization = $user->organization;
        $services = Service::forOrganization($organization->id)->get();
        return ServiceResource::collection($services);
    }

    /**
     * Store a newly created service.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $organization = $user->organization;
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => ['required', new Enum(ServiceStatus::class)],
        ]);
        $service = $organization->services()->create($validated);
        Broadcast::event('service.status.updated', $service);
        return redirect()->route('services.index')->with('success', 'Service created.');
    }

    /**
     * API: Store service
     */
    public function apiStore(Request $request)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);
        $organization = $user->organization;
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => ['required', new Enum(ServiceStatus::class)],
        ]);
        $service = $organization->services()->create($validated);
        Broadcast::event('service.status.updated', $service);
        return new ServiceResource($service);
    }

    /**
     * Show the form for editing the specified service.
     */
    public function edit(Service $service): Response
    {
        $this->authorize('update', $service);
        return Inertia::render('services/edit', [
            'service' => new ServiceResource($service),
        ]);
    }

    /**
     * Update the specified service.
     */
    public function update(Request $request, Service $service)
    {
        $this->authorize('update', $service);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => ['required', new Enum(ServiceStatus::class)],
        ]);
        $service->update($validated);
        Broadcast::event('service.status.updated', $service);
        return redirect()->route('services.index')->with('success', 'Service updated.');
    }

    /**
     * API: Update service
     */
    public function apiUpdate(Request $request, Service $service)
    {
        $this->authorize('update', $service);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => ['required', new Enum(ServiceStatus::class)],
        ]);
        $service->update($validated);
        Broadcast::event('service.status.updated', $service);
        return new ServiceResource($service);
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
     * API: Delete service
     */
    public function apiDestroy(Service $service)
    {
        $this->authorize('delete', $service);
        $service->delete();
        return response()->json(['message' => 'Service deleted.']);
    }
} 