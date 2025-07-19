<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use App\Models\Service;
use App\Models\Incident;
use App\Models\Maintenance;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;

class OrganizationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Organization::class);
        
        $organizations = Organization::withCount(['users', 'services', 'incidents', 'maintenances'])
            ->with(['users' => function ($query) {
                $query->select('users.id', 'name', 'email', 'users.created_at')
                      ->withPivot('role', 'joined_at');
            }])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('slug', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();
            
        // Get overall stats
        $stats = [
            'total_organizations' => Organization::count(),
            'total_users' => User::count(),
            'total_services' => Service::count(),
            'total_incidents' => Incident::count(),
            'total_maintenances' => Maintenance::count(),
            'active_incidents' => Incident::whereNotIn('status', ['resolved'])->count(),
            'scheduled_maintenances' => Maintenance::where('status', 'scheduled')->count(),
        ];
        
        // Get chart data for last 30 days
        $chartData = $this->getChartData();
            
        return Inertia::render('admin/organizations/index', [
            'organizations' => OrganizationResource::collection($organizations),
            'stats' => $stats,
            'chartData' => $chartData,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Organization $organization): Response
    {
        $this->authorize('view', $organization);
        
        $organization->loadCount(['users', 'services', 'incidents', 'maintenances']);
        $organization->load([
            'users' => function ($query) {
                $query->withPivot('role', 'joined_at')
                      ->orderBy('organization_user.joined_at', 'desc');
            },
            'services' => function ($query) {
                $query->withCount('incidents')
                      ->orderBy('order');
            },
            'incidents' => function ($query) {
                $query->with(['services', 'creator'])
                      ->latest()
                      ->limit(10);
            },
            'maintenances' => function ($query) {
                $query->with(['service', 'creator'])
                      ->latest()
                      ->limit(10);
            }
        ]);
        
        // Get organization-specific stats
        $orgStats = [
            'services_by_status' => $organization->services()
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray(),
            'incidents_by_status' => $organization->incidents()
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray(),
            'incidents_by_severity' => $organization->incidents()
                ->select('severity', DB::raw('count(*) as count'))
                ->groupBy('severity')
                ->pluck('count', 'severity')
                ->toArray(),
            'users_by_role' => $organization->users()
                ->select('organization_user.role', DB::raw('count(*) as count'))
                ->groupBy('organization_user.role')
                ->pluck('count', 'role')
                ->toArray(),
        ];

        // dd($organization->users, $orgStats);
        
        return Inertia::render('admin/organizations/show', [
            'organization' => new OrganizationResource($organization),
            'stats' => $orgStats,
        ]);
    }
    
    /**
     * Get chart data for the admin dashboard
     */
    private function getChartData(): array
    {
        $thirtyDaysAgo = now()->subDays(30);
        
        // Organizations created in last 30 days
        $organizationsCreated = Organization::where('created_at', '>=', $thirtyDaysAgo)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();
            
        // Users joined in last 30 days
        $usersJoined = DB::table('organization_user')
            ->where('joined_at', '>=', $thirtyDaysAgo)
            ->selectRaw('DATE(joined_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();
            
        // Incidents created in last 30 days
        $incidentsCreated = Incident::where('created_at', '>=', $thirtyDaysAgo)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();
            
        return [
            'organizations' => $organizationsCreated,
            'users' => $usersJoined,
            'incidents' => $incidentsCreated,
        ];
    }
}
