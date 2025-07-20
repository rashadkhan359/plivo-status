<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TeamPolicy
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
    public function view(User $user, Team $team): bool
    {
        return $this->belongsToSameOrganization($user, $team);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'manage_teams') || 
               $this->hasRole($user, ['owner', 'admin']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Team $team): bool
    {
        if (!$this->belongsToSameOrganization($user, $team)) {
            return false;
        }

        // Owners and admins can update any team
        if ($this->hasRole($user, ['owner', 'admin'])) {
            return true;
        }

        // Team leads can update their own team
        return $this->isTeamLead($user, $team);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Team $team): bool
    {
        if (!$this->belongsToSameOrganization($user, $team)) {
            return false;
        }

        // Only owners and admins can delete teams
        return $this->hasRole($user, ['owner', 'admin']);
    }

    /**
     * Determine whether the user can manage team members.
     */
    public function manageMembers(User $user, Team $team): bool
    {
        if (!$this->belongsToSameOrganization($user, $team)) {
            return false;
        }

        // Owners, admins, and team leads can manage members
        return $this->hasRole($user, ['owner', 'admin']) || 
               $this->isTeamLead($user, $team);
    }

    /**
     * Determine whether the user can manage team services.
     */
    public function manageServices(User $user, Team $team): bool
    {
        if (!$this->belongsToSameOrganization($user, $team)) {
            return false;
        }

        // Owners, admins, and team leads can manage services
        return $this->hasRole($user, ['owner', 'admin']) || 
               $this->isTeamLead($user, $team);
    }

    /**
     * Determine whether the user can join the team.
     */
    public function join(User $user, Team $team): bool
    {
        return $this->belongsToSameOrganization($user, $team);
    }

    /**
     * Determine whether the user can leave the team.
     */
    public function leave(User $user, Team $team): bool
    {
        if (!$this->belongsToSameOrganization($user, $team)) {
            return false;
        }

        // Users can leave teams they're members of (except team leads)
        return $user->teams()->where('teams.id', $team->id)->exists() && 
               !$this->isTeamLead($user, $team);
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
     * Check if user belongs to same organization as team
     */
    protected function belongsToSameOrganization(User $user, Team $team): bool
    {
        // Check new pivot table
        if ($user->organizations()->where('organizations.id', $team->organization_id)->exists()) {
            return true;
        }

        // Fallback to legacy organization_id
        return $user->organization_id === $team->organization_id;
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
     * Check if user is team lead of the team
     */
    protected function isTeamLead(User $user, Team $team): bool
    {
        return $user->teams()
            ->where('teams.id', $team->id)
            ->wherePivot('role', 'lead')
            ->exists();
    }
} 