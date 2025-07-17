<?php

namespace App\Policies;

use App\Models\Incident;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class IncidentPolicy
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
    public function view(User $user, Incident $incident): bool
    {
        return $this->belongsToSameOrganization($user, $incident);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'manage_incidents') || 
               $this->hasRole($user, ['owner', 'admin', 'team_lead']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Incident $incident): bool
    {
        if (!$this->belongsToSameOrganization($user, $incident)) {
            return false;
        }

        // Owners and admins can update any incident
        if ($this->hasRole($user, ['owner', 'admin'])) {
            return true;
        }

        // Team leads can update incidents in their team's services
        if ($this->hasRole($user, ['team_lead']) && $this->canManageIncidentServices($user, $incident)) {
            return true;
        }

        // Incident creator can update their own incident
        return $incident->created_by === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Incident $incident): bool
    {
        if (!$this->belongsToSameOrganization($user, $incident)) {
            return false;
        }

        // Only owners, admins, and incident creators can delete
        return $this->hasRole($user, ['owner', 'admin']) || 
               $incident->created_by === $user->id;
    }

    /**
     * Determine whether the user can resolve the incident.
     */
    public function resolve(User $user, Incident $incident): bool
    {
        if (!$this->belongsToSameOrganization($user, $incident)) {
            return false;
        }

        // Anyone who can update can also resolve
        return $this->update($user, $incident);
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
     * Check if user belongs to same organization as incident
     */
    protected function belongsToSameOrganization(User $user, Incident $incident): bool
    {
        // Check new pivot table
        if ($user->organizations()->where('organizations.id', $incident->organization_id)->exists()) {
            return true;
        }

        // Fallback to legacy organization_id
        return $user->organization_id === $incident->organization_id;
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
     * Check if user can manage incident's services
     */
    protected function canManageIncidentServices(User $user, Incident $incident): bool
    {
        // If no services attached, allow team leads
        if ($incident->services->isEmpty()) {
            return true;
        }

        // Check if user's teams manage any of the incident's services
        $userTeamIds = $user->teams->pluck('id');
        $incidentServiceTeamIds = $incident->services->pluck('team_id')->filter();

        return $userTeamIds->intersect($incidentServiceTeamIds)->isNotEmpty();
    }
} 