<?php

namespace App\Policies;

use App\Models\Maintenance;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Auth\Access\HandlesAuthorization;

class MaintenancePolicy
{
    use HandlesAuthorization;

    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // System admin can view all
        if ($user->isSystemAdmin()) {
            return true;
        }

        // User must belong to an organization
        return $user->organizations()->exists() || !is_null($user->organization_id);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Maintenance $maintenance): bool
    {
        // Use PermissionService to check access
        return $this->permissionService->userCanAccessMaintenance($user, $maintenance);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // System admin can create
        if ($user->isSystemAdmin()) {
            return true;
        }

        $organization = $this->getCurrentOrganization($user);
        if (!$organization) {
            return false;
        }

        // Check organization-level permission
        if ($this->permissionService->userHasOrganizationPermission($user, $organization, 'manage_maintenance')) {
            return true;
        }

        // Check if user has team-level permission in any team
        $accessibleTeams = $this->permissionService->getUserAccessibleTeams($user, $organization);
        return $accessibleTeams->some(function ($team) use ($user) {
            return $this->permissionService->userHasTeamPermission($user, $team, 'manage_maintenance');
        });
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Maintenance $maintenance): bool
    {
        // System admin can update all
        if ($user->isSystemAdmin()) {
            return true;
        }

        // Must have access to the maintenance first
        if (!$this->permissionService->userCanAccessMaintenance($user, $maintenance)) {
            return false;
        }

        $organization = $maintenance->organization ?? $this->getCurrentOrganization($user);
        if (!$organization) {
            return false;
        }

        // Check organization-level permission
        if ($this->permissionService->userHasOrganizationPermission($user, $organization, 'manage_maintenance')) {
            return true;
        }

        // Check team-level permission for any of the maintenance's services
        foreach ($maintenance->services as $service) {
            if ($service->team_id) {
                $team = $service->team;
                if ($team && $this->permissionService->userHasTeamPermission($user, $team, 'manage_maintenance')) {
                    return true;
                }
            }
        }

        // Maintenance creator can update their own maintenance
        return $maintenance->created_by === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Maintenance $maintenance): bool
    {
        // System admin can delete all
        if ($user->isSystemAdmin()) {
            return true;
        }

        // Must have access to the maintenance first
        if (!$this->permissionService->userCanAccessMaintenance($user, $maintenance)) {
            return false;
        }

        $organization = $maintenance->organization ?? $this->getCurrentOrganization($user);
        if (!$organization) {
            return false;
        }

        // Check organization-level permission
        if ($this->permissionService->userHasOrganizationPermission($user, $organization, 'manage_maintenance')) {
            return true;
        }

        // Only maintenance creators can delete their own maintenance (team members cannot delete)
        return $maintenance->created_by === $user->id;
    }

    /**
     * Determine whether the user can update the maintenance status.
     */
    public function updateStatus(User $user, Maintenance $maintenance): bool
    {
        // Same as update permission
        return $this->update($user, $maintenance);
    }

    /**
     * Get current organization from user context
     */
    protected function getCurrentOrganization(User $user)
    {
        // Try to get from app container (set by middleware)
        try {
            return app('current_organization');
        } catch (\Illuminate\Contracts\Container\BindingResolutionException $e) {
            // Fallback to user's first organization
            return $user->organizations()->first() ?? 
                   ($user->organization_id ? \App\Models\Organization::find($user->organization_id) : null);
        }
    }
} 