<?php

namespace App\Policies;

use App\Models\Maintenance;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MaintenancePolicy
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
    public function view(User $user, Maintenance $maintenance): bool
    {
        return $this->belongsToSameOrganization($user, $maintenance);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'manage_maintenance') || 
               $this->hasRole($user, ['owner', 'admin', 'team_lead']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Maintenance $maintenance): bool
    {
        if (!$this->belongsToSameOrganization($user, $maintenance)) {
            return false;
        }

        // Owners and admins can update any maintenance
        if ($this->hasRole($user, ['owner', 'admin'])) {
            return true;
        }

        // Team leads can update maintenance for their team's services
        if ($this->hasRole($user, ['team_lead']) && $this->canManageMaintenanceService($user, $maintenance)) {
            return true;
        }

        // Maintenance creator can update their own maintenance
        return $maintenance->created_by === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Maintenance $maintenance): bool
    {
        if (!$this->belongsToSameOrganization($user, $maintenance)) {
            return false;
        }

        // Only owners, admins, and maintenance creators can delete
        return $this->hasRole($user, ['owner', 'admin']) || 
               $maintenance->created_by === $user->id;
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
     * Check if user belongs to same organization as maintenance
     */
    protected function belongsToSameOrganization(User $user, Maintenance $maintenance): bool
    {
        // Check new pivot table
        if ($user->organizations()->where('organizations.id', $maintenance->organization_id)->exists()) {
            return true;
        }

        // Fallback to legacy organization_id
        return $user->organization_id === $maintenance->organization_id;
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
     * Check if user can manage maintenance's service
     */
    protected function canManageMaintenanceService(User $user, Maintenance $maintenance): bool
    {
        // If no service specified, allow team leads
        if (!$maintenance->service_id) {
            return true;
        }

        // Check if user's teams manage the maintenance's service
        $userTeamIds = $user->teams->pluck('id');
        $serviceTeamId = $maintenance->service->team_id ?? null;

        return $serviceTeamId && $userTeamIds->contains($serviceTeamId);
    }
} 