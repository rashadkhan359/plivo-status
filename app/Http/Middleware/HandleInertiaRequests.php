<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;
use Illuminate\Support\Facades\App;
use App\Services\PermissionService;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }



    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $user = $request->user();
        
        // Try to get current organization from request (set by OrganizationContext middleware)
        $currentOrganization = $request->get('current_organization');
        
        // Fallback: Try to get from container if not in request
        if (!$currentOrganization) {
            try {
                $currentOrganization = App::make('current_organization');
            } catch (\Illuminate\Contracts\Container\BindingResolutionException $e) {
                // Organization context not set (guest user or middleware not run)
                $currentOrganization = null;
            }
        }


        
        // Get user's role and permissions in current organization
        $currentRole = null;
        $currentPermissions = [];
        $permissionService = app(PermissionService::class);
        
        // Check if user is system admin first
        if ($user && $user->isSystemAdmin()) {
            $currentRole = 'system_admin';
            $currentPermissions = [
                'organization' => [
                    'manage_organization' => true,
                    'manage_users' => true,
                    'manage_teams' => true,
                    'manage_services' => true,
                    'manage_incidents' => true,
                    'manage_maintenance' => true,
                    'view_analytics' => true,
                    'system_admin' => true,
                ],
                'teams' => [],
                'resources' => [
                    'teams' => ['accessible' => [], 'can_create' => true],
                    'services' => ['accessible' => [], 'can_create' => true],
                    'incidents' => ['can_create' => true],
                    'maintenance' => ['can_create' => true],
                ],
            ];
        } elseif ($user && $currentOrganization) {
            $userOrganization = $user->organizations()
                ->where('organizations.id', $currentOrganization->id)
                ->first();
                
            if ($userOrganization) {
                $currentRole = $userOrganization->pivot->role ?? 'member';
                
                // Get detailed user permissions with resource access
                $currentPermissions = $permissionService->getDetailedUserPermissions($user, $currentOrganization);
            }
        }
        
        // Fallback: If no organization context, try to get user's first organization
        if ($user && !$currentRole && !$user->isSystemAdmin()) {
            $userOrganization = $user->organizations()->first();
            if ($userOrganization) {
                $currentRole = $userOrganization->pivot->role ?? 'member';
                $currentPermissions = $permissionService->getDetailedUserPermissions($user, $userOrganization);
                $currentOrganization = $currentOrganization ?? $userOrganization;
            }
        }

        // Debug: Check what we're getting
        // if ($user) {
        //     dd([
        //         'user_id' => $user->id,
        //         'user_email' => $user->email,
        //         'user_organization_id' => $user->organization_id, // Legacy field
        //         'currentOrganization' => $currentOrganization ? $currentOrganization->toArray() : null,
        //         'userOrganizations' => $user->organizations()->get()->toArray(),
        //         'userOrganization' => $user && $currentOrganization ? $user->organizations()->where('organizations.id', $currentOrganization->id)->first() : null,
        //         'currentRole' => $currentRole,
        //         'currentPermissions' => $currentPermissions,
        //     ]);
        // }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $user,
                'currentOrganization' => $currentOrganization,
                'currentRole' => $currentRole,
                'currentPermissions' => $currentPermissions,
            ],
            'ziggy' => function () use ($request): array {
                return [
                    ...(new Ziggy)->toArray(),
                    'location' => $request->url(),
                ];
            },
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
