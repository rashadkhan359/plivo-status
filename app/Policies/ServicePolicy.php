<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServicePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasOrganizationAccess($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Service $service): bool
    {
        // Check if user belongs to the same organization
        if (!$this->belongsToSameOrganization($user, $service)) {
            return false;
        }

        // Check service visibility
        if ($service->visibility === 'private') {
            // Private services can only be viewed by team members or admins
            return $this->isTeamMemberOrAdmin($user, $service);
        }

        // Public services can be viewed by any organization member
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'manage_services') || 
               $this->hasRole($user, ['owner', 'admin', 'team_lead']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Service $service): bool
    {
        if (!$this->belongsToSameOrganization($user, $service)) {
            return false;
        }

        // Owners and admins can update any service
        if ($this->hasRole($user, ['owner', 'admin'])) {
            return true;
        }

        // Team leads can update services in their team
        if ($this->hasRole($user, ['team_lead']) && $this->isTeamMemberOrAdmin($user, $service)) {
            return true;
        }

        // Service creator can update their own service
        return $service->created_by === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Service $service): bool
    {
        if (!$this->belongsToSameOrganization($user, $service)) {
            return false;
        }

        // Only owners, admins, and service creators can delete
        return $this->hasRole($user, ['owner', 'admin']) || 
               $service->created_by === $user->id;
    }

    /**
     * Check if user has organization access
     */
    protected function hasOrganizationAccess(User $user): bool
    {
        return $user->organizations()->wherePivot('is_active', true)->exists() || 
               !is_null($user->organization_id);
    }

    /**
     * Check if user belongs to same organization as service
     */
    protected function belongsToSameOrganization(User $user, Service $service): bool
    {
        // Check new pivot table
        if ($user->organizations()->where('organizations.id', $service->organization_id)->exists()) {
            return true;
        }

        // Fallback to legacy organization_id
        return $user->organization_id === $service->organization_id;
    }

    /**
     * Check if user has specific role
     */
    protected function hasRole(User $user, array $roles): bool
    {
        // Check current role from organization context
        if (isset($user->current_role) && in_array($user->current_role, $roles)) {
            return true;
        }

        // Fallback to legacy role
        return in_array($user->role, $roles);
    }

    /**
     * Check if user has specific permission
     */
    protected function hasPermission(User $user, string $permission): bool
    {
        if (isset($user->current_permissions) && 
            isset($user->current_permissions[$permission]) && 
            $user->current_permissions[$permission]) {
            return true;
        }

        return false;
    }

    /**
     * Check if user is team member or admin for service
     */
    protected function isTeamMemberOrAdmin(User $user, Service $service): bool
    {
        // Admins and owners can access all services
        if ($this->hasRole($user, ['owner', 'admin'])) {
            return true;
        }

        // Check if user is in the service's team
        if ($service->team_id) {
            return $user->teams()->where('teams.id', $service->team_id)->exists();
        }

        return false;
    }
}
