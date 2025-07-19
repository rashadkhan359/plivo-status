<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;
use App\Models\Organization;
use App\Models\User;
use App\Models\Service;
use App\Models\Incident;
use App\Models\Maintenance;
use Illuminate\Http\Request;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Get the current organization from the middleware context.
     * 
     * @return Organization|null
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function getCurrentOrganization(): ?Organization
    {
        $user = Auth::user();
        
        try {
            return App::make('current_organization');
        } catch (\Illuminate\Contracts\Container\BindingResolutionException $e) {
            // Fallback to legacy organization for backward compatibility
            if ($user && $user->organization) {
                return $user->organization;
            }
            
            // If no organization is bound, user needs organization access
            if (!$user || !($user instanceof User)) {
                abort(403, 'You do not have access to any organization.');
            }
            
            return null;
        }
    }

    /**
     * Handle organization access denied with user-friendly response
     */
    protected function handleOrganizationAccessDenied()
    {
        $user = Auth::user();
        
        if (request()->expectsJson()) {
            abort(403, 'You do not have access to this organization.');
        }
        
        // Check if user has any organizations
        if ($user && $user->organizations()->exists()) {
            // User has organizations but not this one
            return redirect()->route('dashboard')
                ->with('error', 'You do not have access to the requested organization.');
        } else {
            // User has no organizations
            Auth::logout();
            return redirect()->route('login')
                ->withErrors(['email' => 'Your account is not associated with any organization.']);
        }
    }

    /**
     * Handle authorization exceptions with better UX
     */
    protected function handleAuthorizationException($exception, $fallbackRoute = 'dashboard')
    {
        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'You do not have permission to perform this action.',
                'code' => 'INSUFFICIENT_PERMISSIONS'
            ], 403);
        }
        
        return redirect()->route($fallbackRoute)
            ->with('error', 'You do not have permission to perform this action.');
    }

    /**
     * Check if user has specific permission in current organization
     */
    protected function hasPermission(string $permission): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        // System admins have all permissions
        if ($user->isSystemAdmin()) {
            return true;
        }
        
        try {
            $organization = $this->getCurrentOrganization();
            if (!$organization) return false;
            
            $userOrg = $user->organizations()
                ->where('organizations.id', $organization->id)
                ->first();
                
            if (!$userOrg) return false;
            
            $permissions = json_decode($userOrg->pivot->permissions ?? '[]', true);
            return in_array($permission, $permissions) || 
                   in_array('*', $permissions) || 
                   $userOrg->pivot->role === 'owner';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get user's role in current organization
     */
    protected function getCurrentRole(): ?string
    {
        $user = Auth::user();
        if (!$user) return null;
        
        // System admins have system_admin role
        if ($user->isSystemAdmin()) {
            return 'system_admin';
        }
        
        try {
            $organization = $this->getCurrentOrganization();
            if (!$organization) return null;
            
            $userOrg = $user->organizations()
                ->where('organizations.id', $organization->id)
                ->first();
                
            return $userOrg ? $userOrg->pivot->role : null;
        } catch (\Exception $e) {
            return $user->role ?? null; // Fallback to legacy role
        }
    }

    /**
     * Get services accessible to the user in the current organization
     */
    protected function getAccessibleServices($user, $organization)
    {
        // If no organization, return empty query
        if (!$organization) {
            return \App\Models\Service::whereRaw('1 = 0'); // Return empty query builder
        }
        
        $query = $organization->services();
        
        // Get user's role in the organization
        $userRole = $this->getCurrentRole();
        
        if ($userRole === 'owner' || $userRole === 'admin' || $user->isSystemAdmin()) {
            // Owners, admins, and system admins see all services in their organization
            return $query->with(['team', 'incidents']);
        }
        
        // Members see services based on team memberships and visibility
        $userTeamIds = $user->teams()->where('organization_id', $organization->id)->pluck('teams.id');
        
        return $query->where(function ($q) use ($userTeamIds) {
            $q->where('visibility', 'public')
              ->orWhereIn('team_id', $userTeamIds)
              ->orWhereNull('team_id'); // Unassigned services are visible to all
        })->with(['team', 'incidents']);
    }

    /**
     * Get incidents accessible to the user in the current organization
     */
    protected function getAccessibleIncidents($user, $organization)
    {
        // If no organization, return empty query
        if (!$organization) {
            return \App\Models\Incident::whereRaw('1 = 0'); // Return empty query builder
        }
        
        $query = $organization->incidents();
        
        // Get user's role in the organization
        $userRole = $this->getCurrentRole();
        
        if ($userRole === 'owner' || $userRole === 'admin' || $user->isSystemAdmin()) {
            // Owners, admins, and system admins see all incidents in their organization
            return $query->with(['services', 'creator', 'resolver'])->latest();
        }
        
        // Members see incidents for services they have access to
        $accessibleServices = $this->getAccessibleServices($user, $organization);
        $serviceIds = $accessibleServices->pluck('id')->toArray();
        
        if (empty($serviceIds)) {
            // If user has no accessible services, return empty query
            return $query->whereRaw('1 = 0');
        }
        
        return $query->whereHas('services', function ($q) use ($serviceIds) {
            $q->whereIn('services.id', $serviceIds);
        })->with(['services', 'creator', 'resolver'])->latest();
    }

    /**
     * Get maintenances accessible to the user in the current organization
     */
    protected function getAccessibleMaintenances($user, $organization)
    {
        // If no organization, return empty query
        if (!$organization) {
            return \App\Models\Maintenance::whereRaw('1 = 0'); // Return empty query builder
        }
        
        $query = $organization->maintenances();
        
        // Get user's role in the organization
        $userRole = $this->getCurrentRole();
        
        if ($userRole === 'owner' || $userRole === 'admin' || $user->isSystemAdmin()) {
            // Owners, admins, and system admins see all maintenances in their organization
            return $query->with(['service', 'creator'])->latest();
        }
        
        // Members see maintenances for services they have access to
        $accessibleServices = $this->getAccessibleServices($user, $organization);
        $serviceIds = $accessibleServices->pluck('id')->toArray();
        
        if (empty($serviceIds)) {
            // If user has no accessible services, only show general maintenances
            return $query->whereNull('service_id')->with(['service', 'creator'])->latest();
        }
        
        return $query->where(function ($q) use ($serviceIds) {
            $q->whereIn('service_id', $serviceIds)
              ->orWhereNull('service_id'); // General maintenances are visible to all
        })->with(['service', 'creator'])->latest();
    }

    /**
     * Validate that a resource belongs to the current organization
     */
    protected function validateResourceBelongsToOrganization($resource, $organizationId = null)
    {
        $organizationId = $organizationId ?? $this->getCurrentOrganization()->id;
        
        if ($resource->organization_id !== $organizationId) {
            abort(403, 'Resource does not belong to your organization.');
        }
    }



    /**
     * Validate that a team belongs to the current organization
     */
    protected function validateTeamBelongsToOrganization($teamId, $organizationId = null)
    {
        if (!$teamId) return null;
        
        $organizationId = $organizationId ?? $this->getCurrentOrganization()->id;
        
        $team = \App\Models\Team::where('id', $teamId)
            ->where('organization_id', $organizationId)
            ->first();
            
        if (!$team) {
            abort(422, 'Team does not belong to your organization.');
        }
        
        return $team;
    }

    /**
     * Get the organization for creating/updating resources
     * For system admins, this returns the organization from the request or a default
     */
    protected function getOrganizationForResource($request = null)
    {
        $user = Auth::user();
        
        // System admins can work with any organization
        if ($user->isSystemAdmin()) {
            // If organization_id is provided in request, use that
            if ($request && $request->has('organization_id')) {
                return Organization::findOrFail($request->organization_id);
            }
            
            // For system admins without specific organization, return null
            // This will be handled by the specific controller logic
            return null;
        }
        
        // Regular users work with their current organization
        return $this->getCurrentOrganization();
    }

    /**
     * Validate that services belong to the current organization
     */
    protected function validateServicesBelongToOrganization(array $serviceIds, $organizationId = null)
    {
        $organizationId = $organizationId ?? $this->getCurrentOrganization()->id;
        
        $validServices = Service::whereIn('id', $serviceIds)
            ->where('organization_id', $organizationId)
            ->pluck('id')
            ->toArray();
            
        if (count($validServices) !== count($serviceIds)) {
            abort(422, 'Some selected services do not belong to your organization.');
        }
        
        return $validServices;
    }
}