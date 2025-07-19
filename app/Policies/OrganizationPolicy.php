<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrganizationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Only system admins can view all organizations
        return $user->isSystemAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Organization $organization): bool
    {
        // System admins can view any organization
        if ($user->isSystemAdmin()) {
            return true;
        }
        
        return $this->belongsToOrganization($user, $organization);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Anyone can create organizations during registration
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Organization $organization): bool
    {
        if (!$this->belongsToOrganization($user, $organization)) {
            return false;
        }

        return $this->hasPermission($user, 'manage_organization') || 
               $this->hasRole($user, ['owner', 'admin']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Organization $organization): bool
    {
        if (!$this->belongsToOrganization($user, $organization)) {
            return false;
        }

        // Only organization owners can delete
        return $this->hasRole($user, ['owner']);
    }

    /**
     * Determine whether the user can manage organization users.
     */
    public function manageUsers(User $user, Organization $organization): bool
    {
        if (!$this->belongsToOrganization($user, $organization)) {
            return false;
        }

        return $this->hasPermission($user, 'manage_users') || 
               $this->hasRole($user, ['owner', 'admin']);
    }

    /**
     * Determine whether the user can manage organization teams.
     */
    public function manageTeams(User $user, Organization $organization): bool
    {
        if (!$this->belongsToOrganization($user, $organization)) {
            return false;
        }

        return $this->hasPermission($user, 'manage_teams') || 
               $this->hasRole($user, ['owner', 'admin']);
    }

    /**
     * Determine whether the user can view organization analytics.
     */
    public function viewAnalytics(User $user, Organization $organization): bool
    {
        if (!$this->belongsToOrganization($user, $organization)) {
            return false;
        }

        return $this->hasPermission($user, 'view_analytics') || 
               $this->hasRole($user, ['owner', 'admin', 'team_lead']);
    }

    /**
     * Check if user belongs to organization
     */
    protected function belongsToOrganization(User $user, Organization $organization): bool
    {
        // Check new pivot table
        if ($user->organizations()->where('organizations.id', $organization->id)->exists()) {
            return true;
        }

        // Fallback to legacy organization_id
        return $user->organization_id === $organization->id;
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
} 