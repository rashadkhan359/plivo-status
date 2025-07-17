<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;
use Illuminate\Support\Facades\App;

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
        
        // Try to get current organization from container (set by OrganizationContext middleware)
        $currentOrganization = null;
        try {
            $currentOrganization = App::make('current_organization');
        } catch (\Illuminate\Contracts\Container\BindingResolutionException $e) {
            // Organization context not set (guest user or middleware not run)
            $currentOrganization = null;
        }
        
        // Get user's role and permissions in current organization
        $currentRole = null;
        $currentPermissions = [];
        
        if ($user && $currentOrganization) {
            $userOrganization = $user->organizations()
                ->where('organizations.id', $currentOrganization->id)
                ->first();
                
            if ($userOrganization) {
                $currentRole = $userOrganization->pivot->role;
                $currentPermissions = $userOrganization->pivot->permissions ?? [];
            }
        }

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
            'ziggy' => fn (): array => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
