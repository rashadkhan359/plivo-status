<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\App;
use App\Models\Organization;

class OrganizationContext
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        // Allow guest access for public routes
        if (!$user) {
            return $next($request);
        }

        // Get the user's current organization
        $organization = $this->getCurrentOrganization($user, $request);
        
        if (!$organization) {
            // User has no organization access
            abort(403, 'You do not have access to any organization.');
        }

        // Set organization context globally
        $this->setOrganizationContext($organization, $user);
        
        // Add organization data to request
        $request->merge(['current_organization' => $organization]);
        
        return $next($request);
    }

    /**
     * Get the current organization for the user
     */
    protected function getCurrentOrganization($user, Request $request)
    {
        // Check if organization is specified in route parameter
        $organizationId = $request->route('organization');
        
        if ($organizationId) {
            // Verify user has access to this specific organization
            $organization = $user->organizations()
                ->where('organizations.id', $organizationId)
                ->first();
                
            if (!$organization) {
                abort(403, 'You do not have access to this organization.');
            }
            
            return $organization;
        }

        // For backward compatibility, check if user has organization_id
        if ($user->organization_id) {
            return Organization::find($user->organization_id);
        }

        // Get user's primary organization (first one they're a member of)
        return $user->organizations()->first();
    }

    /**
     * Set organization context globally
     */
    protected function setOrganizationContext(Organization $organization, $user)
    {
        // Make organization available throughout the application
        App::instance('current_organization', $organization);
        
        // Share with all views
        View::share('currentOrganization', $organization);
        
        // Get user's role in this organization
        $userOrganization = $user->organizations()
            ->where('organizations.id', $organization->id)
            ->first();
            
        if ($userOrganization) {
            $role = $userOrganization->pivot->role;
            $permissions = $userOrganization->pivot->permissions ?? [];
            
            // Share user's role and permissions
            View::share('currentRole', $role);
            View::share('currentPermissions', $permissions);
            
            // Set user's current role for the request
            $user->current_role = $role;
            $user->current_permissions = $permissions;
        }
    }
} 