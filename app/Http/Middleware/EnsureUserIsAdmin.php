<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            abort(403, 'Authentication required.');
        }

        $user = Auth::user();
        
        // Check if user has admin privileges
        if ($this->hasAdminAccess($user, $request)) {
            return $next($request);
        }

        abort(403, 'You do not have permission to access this area.');
    }

    /**
     * Check if user has admin access
     */
    protected function hasAdminAccess($user, Request $request): bool
    {
        // System admins have access to everything
        if ($user->isSystemAdmin()) {
            return true;
        }
        
        // Check new organization-based roles
        $organization = null;
        try {
            $organization = app('current_organization');
        } catch (\Exception $e) {
            // Organization not set in container, which is fine for system admins
        }
        
        if ($organization) {
            $userOrganization = $user->organizations()
                ->where('organizations.id', $organization->id)
                ->first();
                
            if ($userOrganization) {
                $role = $userOrganization->pivot->role;
                $permissions = $userOrganization->pivot->permissions ?? [];
                
                // Owner, Admin, and Team Lead have admin access
                if (in_array($role, ['owner', 'admin', 'team_lead'])) {
                    return true;
                }
                
                // Or check for specific management permissions
                if (
                    isset($permissions['manage_organization']) && $permissions['manage_organization'] ||
                    isset($permissions['manage_users']) && $permissions['manage_users']
                ) {
                    return true;
                }
            }
        }
        
        // Fallback to legacy role check for backward compatibility
        if ($user->role === 'admin') {
            return true;
        }
        
        return false;
    }
}
