<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\Organization;
use App\Models\Service;
use App\Models\Incident;
use App\Models\Maintenance;

class DashboardController extends Controller
{
    /**
     * Show the dashboard overview.
     */
    public function index(Request $request): Response
    {
        $organization = Auth::user()->organization;
        $services = Service::forOrganization($organization->id)->count();
        $incidents = Incident::forOrganization($organization->id)->count();
        $maintenances = Maintenance::forOrganization($organization->id)->count();

        return Inertia::render('dashboard', [
            'servicesCount' => $services,
            'incidentsCount' => $incidents,
            'maintenancesCount' => $maintenances,
        ]);
    }

    /**
     * API: Get dashboard overview data.
     */
    public function apiOverview(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $organization = $user->organization;
        if (!$organization) {
            return response()->json(['message' => 'No organization found'], 404);
        }
        return response()->json([
            'services' => Service::forOrganization($organization->id)->count(),
            'incidents' => Incident::forOrganization($organization->id)->count(),
            'maintenances' => Maintenance::forOrganization($organization->id)->count(),
        ]);
    }
} 