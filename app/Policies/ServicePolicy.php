<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServicePolicy
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
    public function view(User $user, Service $service): bool
    {
        // Use PermissionService to check access
        return $this->permissionService->userCanAccessService($user, $service);
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
        if ($this->permissionService->userHasOrganizationPermission($user, $organization, 'manage_services')) {
            return true;
        }

        // Check if user has team-level permission in any team
        $accessibleTeams = $this->permissionService->getUserAccessibleTeams($user, $organization);
        return $accessibleTeams->some(function ($team) use ($user) {
            return $this->permissionService->userHasTeamPermission($user, $team, 'manage_services');
        });
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Service $service): bool
    {
        // System admin can update all
        if ($user->isSystemAdmin()) {
            return true;
        }

        // Must have access to the service first
        if (!$this->permissionService->userCanAccessService($user, $service)) {
            return false;
        }

        $organization = $service->organization ?? $this->getCurrentOrganization($user);
        if (!$organization) {
            return false;
        }

        // Check organization-level permission
        if ($this->permissionService->userHasOrganizationPermission($user, $organization, 'manage_services')) {
            return true;
        }

        // Check team-level permission
        if ($service->team_id) {
            $team = $service->team;
            if ($team && $this->permissionService->userHasTeamPermission($user, $team, 'manage_services')) {
                return true;
            }
        }

        // Service creator can update their own service
        return $service->created_by === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Service $service): bool
    {
        // System admin can delete all
        if ($user->isSystemAdmin()) {
            return true;
        }

        // Must have access to the service first
        if (!$this->permissionService->userCanAccessService($user, $service)) {
            return false;
        }

        $organization = $service->organization ?? $this->getCurrentOrganization($user);
        if (!$organization) {
            return false;
        }

        // Check organization-level permission
        if ($this->permissionService->userHasOrganizationPermission($user, $organization, 'manage_services')) {
            return true;
        }

        // Only service creators can delete their own services (team leads cannot delete)
        return $service->created_by === $user->id;
    }

    /**
     * Determine whether the user can update the service status.
     */
    public function updateStatus(User $user, Service $service): bool
    {
        // Same as update permission
        return $this->update($user, $service);
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
