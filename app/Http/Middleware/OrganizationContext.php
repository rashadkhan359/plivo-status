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
        // Debug: Check if middleware is being called
        // dd('OrganizationContext middleware called', $request->url());
        
        $user = Auth::user();
        
        // Allow guest access for public routes
        if (!$user) {
            return $next($request);
        }

        // System admins still need organization context for security
        // They should work within their own organization, not see all organizations

        // Get the user's current organization
        $organization = $this->getCurrentOrganization($user, $request);
        
        // Debug: Check what organization we found (uncomment to debug)
        // dd([
        //     'user_id' => $user->id,
        //     'organization' => $organization ? $organization->toArray() : null,
        //     'user_organizations' => $user->organizations()->get()->toArray(),
        //     'user_organizations_active' => $user->organizations()->wherePivot('is_active', true)->get()->toArray(),
        // ]);
        
        if (!$organization) {
            return $this->handleNoOrganizationAccess($request, $user);
        }

        // Set organization context globally (for backward compatibility)
        $this->setOrganizationContext($organization, $user);
        
        // Add organization data to request (primary method)
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
                ->wherePivot('is_active', true)
                ->first();
                
            if (!$organization) {
                return null;
            }
            
            return $organization;
        }

        // For backward compatibility, check if user has organization_id
        if ($user->organization_id) {
            $organization = Organization::find($user->organization_id);
            if ($organization) {
                return $organization;
            }
        }

        // Get user's primary active organization (simplified query)
        $organization = $user->organizations()
            ->wherePivot('is_active', true)
            ->first();
            
        // If no active organization found, try without the is_active filter
        if (!$organization) {
            $organization = $user->organizations()->first();
        }
        
        return $organization;
    }

    /**
     * Handle cases where user has no organization access
     */
    protected function handleNoOrganizationAccess(Request $request, $user)
    {
        // Check if user has any organizations (even inactive ones)
        $hasAnyOrganizations = $user->organizations()->exists();
        
        if ($hasAnyOrganizations) {
            // User has organizations but none are active
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your account has been deactivated. Please contact your organization administrator.',
                    'code' => 'ACCOUNT_DEACTIVATED'
                ], 403);
            }
            
            // For web requests, redirect to a helpful page
            Auth::logout();
            return redirect()->route('login')->withErrors([
                'email' => 'Your account has been deactivated. Please contact your organization administrator for access.',
            ]);
        } else {
            // User has no organizations at all
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'You are not associated with any organization. Please contact support.',
                    'code' => 'NO_ORGANIZATION'
                ], 403);
            }
            
            // For web requests, redirect to a helpful page
            Auth::logout();
            return redirect()->route('login')->withErrors([
                'email' => 'Your account is not associated with any organization. Please contact support.',
            ]);
        }
    }



    /**
     * Set organization context globally
     */
    protected function setOrganizationContext(Organization $organization, $user)
    {
        // Debug: Check if organization is being set
        // dd('Setting organization context', $organization->toArray());
        
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
            
            // For system admins, override role but keep organization context
            if ($user->isSystemAdmin()) {
                $role = 'system_admin';
                $permissions = [
                    'manage_organization' => true,
                    'manage_users' => true,
                    'manage_teams' => true,
                    'manage_services' => true,
                    'manage_incidents' => true,
                    'manage_maintenance' => true,
                    'view_analytics' => true,
                    'system_admin' => true,
                ];
            }
            
            // Share user's role and permissions
            View::share('currentRole', $role);
            View::share('currentPermissions', $permissions);
            
            // Set user's current role for the request
            $user->current_role = $role;
            $user->current_permissions = $permissions;
        }
    }
} 