<?php

namespace App\Services;

use App\Models\User;
use App\Models\Organization;
use App\Models\Team;
use Illuminate\Support\Facades\Cache;

class PermissionService
{
    /**
     * Available permissions across the system
     */
    public const PERMISSIONS = [
        'manage_organization' => [
            'label' => 'Manage Organization',
            'description' => 'Can update organization settings, branding, and general configuration',
            'scope' => 'organization',
        ],
        'manage_users' => [
            'label' => 'Manage Users',
            'description' => 'Can invite, remove, and manage user roles and permissions',
            'scope' => 'organization',
        ],
        'manage_teams' => [
            'label' => 'Manage Teams',
            'description' => 'Can create, edit, and delete teams and assign team members',
            'scope' => 'both',
        ],
        'manage_services' => [
            'label' => 'Manage Services',
            'description' => 'Can create, edit, and delete services and update their status',
            'scope' => 'both',
        ],
        'manage_incidents' => [
            'label' => 'Manage Incidents',
            'description' => 'Can create, update, and resolve incidents',
            'scope' => 'both',
        ],
        'manage_maintenance' => [
            'label' => 'Manage Maintenance',
            'description' => 'Can schedule and manage maintenance windows',
            'scope' => 'both',
        ],
        'view_analytics' => [
            'label' => 'View Analytics',
            'description' => 'Can access analytics and reporting features',
            'scope' => 'both',
        ],
    ];

    /**
     * Default permissions for organization roles
     */
    public const ORGANIZATION_ROLE_PERMISSIONS = [
        'owner' => [
            'manage_organization' => true,
            'manage_users' => true,
            'manage_teams' => true,
            'manage_services' => true,
            'manage_incidents' => true,
            'manage_maintenance' => true,
            'view_analytics' => true,
        ],
        'admin' => [
            'manage_organization' => true,
            'manage_users' => true,
            'manage_teams' => true,
            'manage_services' => true,
            'manage_incidents' => true,
            'manage_maintenance' => true,
            'view_analytics' => true,
        ],
        'team_lead' => [
            'manage_organization' => false,
            'manage_users' => false,
            'manage_teams' => true,
            'manage_services' => true,
            'manage_incidents' => true,
            'manage_maintenance' => true,
            'view_analytics' => true,
        ],
        'member' => [
            'manage_organization' => false,
            'manage_users' => false,
            'manage_teams' => false,
            'manage_services' => false,
            'manage_incidents' => false,
            'manage_maintenance' => false,
            'view_analytics' => false,
        ],
    ];

    /**
     * Default permissions for team roles
     */
    public const TEAM_ROLE_PERMISSIONS = [
        'lead' => [
            'manage_teams' => true,
            'manage_services' => true,
            'manage_incidents' => true,
            'manage_maintenance' => true,
            'view_analytics' => true,
        ],
        'member' => [
            'manage_teams' => false,
            'manage_services' => false,
            'manage_incidents' => true,
            'manage_maintenance' => true,
            'view_analytics' => false,
        ],
    ];

    /**
     * Get all available permissions for a given scope
     */
    public function getAvailablePermissions(string $scope = 'both'): array
    {
        $filtered = array_filter(self::PERMISSIONS, function ($permission) use ($scope) {
            if ($scope === 'organization') {
                return $permission['scope'] === 'organization' || $permission['scope'] === 'both';
            }
            if ($scope === 'team') {
                return $permission['scope'] === 'team' || $permission['scope'] === 'both';
            }
            return $permission['scope'] === $scope || $permission['scope'] === 'both';
        });
        
        // Ensure we return an associative array, not a numerically indexed array
        return $filtered;
    }

    /**
     * Get permissions for organization roles (static version for backward compatibility)
     */
    public function getOrganizationRolePermissions(): array
    {
        $roles = [];
        foreach (self::ORGANIZATION_ROLE_PERMISSIONS as $role => $permissions) {
            $roles[] = [
                'role' => $role,
                'permissions' => $permissions,
                'usersCount' => 0, // Will be populated by caller
            ];
        }
        return $roles;
    }

    /**
     * Get permissions for team roles
     */
    public function getTeamRolePermissions(Team $team): array
    {
        $roles = [];
        foreach (self::TEAM_ROLE_PERMISSIONS as $role => $defaultPermissions) {
            // Check if there are custom permissions stored for this team and role
            $customPermissions = $this->getCustomTeamRolePermissions($team, $role);
            $permissions = $customPermissions ?: $defaultPermissions;
            
            $roles[] = [
                'role' => $role,
                'permissions' => $permissions,
                'usersCount' => $team->members()->wherePivot('role', $role)->count(),
            ];
        }
        return $roles;
    }

    /**
     * Get custom permissions for an organization role
     */
    public function getCustomOrganizationRolePermissions(Organization $organization, string $role): ?array
    {
        // First try to get from a user's pivot data (if any user has custom permissions)
        $userWithCustomPermissions = $organization->users()
            ->wherePivot('role', $role)
            ->whereNotNull('organization_user.permissions')
            ->first();

        if ($userWithCustomPermissions) {
            return $userWithCustomPermissions->pivot->permissions;
        }

        // Fallback to cache (for backward compatibility during migration)
        $cacheKey = "organization_{$organization->id}_custom_permissions";
        $cachedPermissions = Cache::get($cacheKey, []);
        
        return $cachedPermissions[$role] ?? null;
    }

    /**
     * Get permissions for organization roles with custom permissions support
     */
    public function getOrganizationRolePermissionsWithCustom(Organization $organization): array
    {
        $roles = [];
        foreach (self::ORGANIZATION_ROLE_PERMISSIONS as $role => $defaultPermissions) {
            // Check if there are custom permissions stored for this organization and role
            $customPermissions = $this->getCustomOrganizationRolePermissions($organization, $role);
            $permissions = $customPermissions ?: $defaultPermissions;
            
            $roles[] = [
                'role' => $role,
                'permissions' => $permissions,
                'usersCount' => $organization->users()->wherePivot('role', $role)->count(),
            ];
        }
        return $roles;
    }

    /**
     * Get custom permissions for a team role
     */
    public function getCustomTeamRolePermissions(Team $team, string $role): ?array
    {
        // First try to get from a user's pivot data (if any user has custom permissions)
        $userWithCustomPermissions = $team->members()
            ->wherePivot('role', $role)
            ->whereNotNull('team_user.permissions')
            ->first();

        if ($userWithCustomPermissions) {
            return $userWithCustomPermissions->pivot->permissions;
        }

        // Fallback to cache (for backward compatibility during migration)
        $cacheKey = "team_{$team->id}_custom_permissions";
        $cachedPermissions = Cache::get($cacheKey, []);
        
        return $cachedPermissions[$role] ?? null;
    }

    /**
     * Set user's team permissions
     */
    public function setUserTeamPermissions(User $user, Team $team, array $permissions): void
    {
        $user->teams()->updateExistingPivot($team->id, [
            'permissions' => $permissions
        ]);
    }

    /**
     * Assign default permissions to a user based on their organization role
     */
    public function assignDefaultOrganizationPermissions(User $user, Organization $organization, string $role): void
    {
        $defaultPermissions = self::ORGANIZATION_ROLE_PERMISSIONS[$role] ?? [];
        
        $user->organizations()->updateExistingPivot($organization->id, [
            'permissions' => $defaultPermissions
        ]);
    }

    /**
     * Assign default permissions to a user based on their team role
     */
    public function assignDefaultTeamPermissions(User $user, Team $team, string $role): void
    {
        $defaultPermissions = self::TEAM_ROLE_PERMISSIONS[$role] ?? [];
        
        $user->teams()->updateExistingPivot($team->id, [
            'permissions' => $defaultPermissions
        ]);
    }

    /**
     * Update team role permissions
     */
    public function updateTeamRolePermissions(Team $team, string $role, array $permissions): void
    {
        // If permissions are empty, merge with defaults to ensure all permissions are defined
        if (empty($permissions)) {
            $defaultPermissions = self::TEAM_ROLE_PERMISSIONS[$role] ?? [];
            $permissions = $defaultPermissions;
        }
        
        // Update all users with this role in the team
        $team->members()->wherePivot('role', $role)->each(function ($user) use ($team, $permissions) {
            $user->teams()->updateExistingPivot($team->id, [
                'permissions' => $permissions
            ]);
        });

        // Clear cache
        $cacheKey = "team_{$team->id}_custom_permissions";
        Cache::forget($cacheKey);
    }

    /**
     * Update organization role permissions
     */
    public function updateOrganizationRolePermissions(Organization $organization, string $role, array $permissions): void
    {
        // If permissions are empty, merge with defaults to ensure all permissions are defined
        if (empty($permissions)) {
            $defaultPermissions = self::ORGANIZATION_ROLE_PERMISSIONS[$role] ?? [];
            $permissions = $defaultPermissions;
        }
        
        // Update all users with this role in the organization
        $organization->users()->wherePivot('role', $role)->each(function ($user) use ($organization, $permissions) {
            $user->organizations()->updateExistingPivot($organization->id, [
                'permissions' => $permissions
            ]);
        });

        // Clear cache
        $cacheKey = "organization_{$organization->id}_custom_permissions";
        Cache::forget($cacheKey);
    }

    /**
     * Update organization role permissions for a user
     */
    public function updateOrganizationUserPermissions(User $user, Organization $organization, array $permissions): void
    {
        $user->organizations()->updateExistingPivot($organization->id, [
            'permissions' => $permissions
        ]);
    }

    /**
     * Get user's permissions in an organization
     */
    public function getUserOrganizationPermissions(User $user, Organization $organization): array
    {
        $organizationUser = $user->organizations()->where('organization_id', $organization->id)->first();
        
        if (!$organizationUser) {
            return [];
        }

        $pivot = $organizationUser->pivot;
        
        // Return custom permissions if they exist, otherwise use default role permissions
        if ($pivot->permissions) {
            return $pivot->permissions;
        }

        return self::ORGANIZATION_ROLE_PERMISSIONS[$pivot->role] ?? [];
    }

    /**
     * Get user's permissions in a team
     */
    public function getUserTeamPermissions(User $user, Team $team): array
    {
        $teamUser = $user->teams()->where('team_id', $team->id)->first();
        
        if (!$teamUser) {
            return [];
        }

        $pivot = $teamUser->pivot;
        
        // Return custom permissions if they exist, otherwise use default role permissions
        if ($pivot->permissions) {
            return $pivot->permissions;
        }

        // Get default permissions for the role
        $defaultPermissions = self::TEAM_ROLE_PERMISSIONS[$pivot->role] ?? [];
        
        // If no custom permissions are set, set the default ones
        if (empty($pivot->permissions) && !empty($defaultPermissions)) {
            $this->setUserTeamPermissions($user, $team, $defaultPermissions);
        }

        return $defaultPermissions;
    }

    /**
     * Check if user has a specific permission in an organization
     */
    public function userHasOrganizationPermission(User $user, Organization $organization, string $permission): bool
    {
        $permissions = $this->getUserOrganizationPermissions($user, $organization);
        return $permissions[$permission] ?? false;
    }

    /**
     * Check if user has a specific permission in a team
     */
    public function userHasTeamPermission(User $user, Team $team, string $permission): bool
    {
        $permissions = $this->getUserTeamPermissions($user, $team);
        return $permissions[$permission] ?? false;
    }

    /**
     * Get all permissions for a user (organization + teams)
     */
    public function getAllUserPermissions(User $user, Organization $organization): array
    {
        $orgPermissions = $this->getUserOrganizationPermissions($user, $organization);
        $teamPermissions = [];
        
        foreach ($user->teams as $team) {
            if ($team->organization_id === $organization->id) {
                $teamPermissions[$team->id] = $this->getUserTeamPermissions($user, $team);
            }
        }

        return [
            'organization' => $orgPermissions,
            'teams' => $teamPermissions,
        ];
    }

    /**
     * Check if user can perform an action based on organization or team permissions
     */
    public function userCan(User $user, string $permission, $context = null): bool
    {
        // System admin can do everything
        if ($user->is_system_admin) {
            return true;
        }

        $organization = $user->organization; // Legacy relationship
        
        if (!$organization) {
            return false;
        }

        // Check organization-level permission
        if ($this->userHasOrganizationPermission($user, $organization, $permission)) {
            return true;
        }

        // If context is a team, check team-level permission
        if ($context instanceof Team) {
            return $this->userHasTeamPermission($user, $context, $permission);
        }

        // If context is a model that belongs to a team, check team permission
        if ($context && method_exists($context, 'team') && $context->team) {
            return $this->userHasTeamPermission($user, $context->team, $permission);
        }

        return false;
    }

    /**
     * Get user's accessible teams within an organization
     */
    public function getUserAccessibleTeams(User $user, Organization $organization): \Illuminate\Support\Collection
    {
        // System admin can access all teams
        if ($user->is_system_admin) {
            return $organization->teams;
        }

        // Organization owners/admins can access all teams
        if ($this->userHasOrganizationPermission($user, $organization, 'manage_teams')) {
            return $organization->teams;
        }

        // Regular users can only access teams they're members of
        return $user->teams()->where('organization_id', $organization->id)->get();
    }

    /**
     * Get user's accessible services based on team membership
     */
    public function getUserAccessibleServices(User $user, Organization $organization): \Illuminate\Support\Collection
    {
        // System admin can access all services
        if ($user->is_system_admin) {
            return $organization->services;
        }

        // Organization owners/admins can access all services
        if ($this->userHasOrganizationPermission($user, $organization, 'manage_services')) {
            return $organization->services;
        }

        // Team members can only access services assigned to their teams
        $accessibleTeams = $this->getUserAccessibleTeams($user, $organization);
        $teamIds = $accessibleTeams->pluck('id')->toArray();
        
        return $organization->services()->whereIn('team_id', $teamIds)->get();
    }

    /**
     * Check if user can access a specific service
     */
    public function userCanAccessService(User $user, $service): bool
    {
        // System admin can access everything
        if ($user->is_system_admin) {
            return true;
        }

        $organization = $service->organization ?? $user->organization;
        if (!$organization) {
            return false;
        }

        // Organization owners/admins can access all services
        if ($this->userHasOrganizationPermission($user, $organization, 'manage_services')) {
            return true;
        }

        // Check if service belongs to user's team
        if ($service->team_id) {
            return $user->teams()->where('teams.id', $service->team_id)->exists();
        }

        return false;
    }

    /**
     * Check if user can access a specific incident
     */
    public function userCanAccessIncident(User $user, $incident): bool
    {
        // System admin can access everything
        if ($user->is_system_admin) {
            return true;
        }

        $organization = $incident->organization ?? $user->organization;
        if (!$organization) {
            return false;
        }

        // Organization owners/admins can access all incidents
        if ($this->userHasOrganizationPermission($user, $organization, 'manage_incidents')) {
            return true;
        }

        // Check if incident's services belong to user's teams
        $userTeamIds = $user->teams()->where('organization_id', $organization->id)->pluck('teams.id')->toArray();
        
        return $incident->services()->whereIn('team_id', $userTeamIds)->exists();
    }

    /**
     * Check if user can access a specific maintenance
     */
    public function userCanAccessMaintenance(User $user, $maintenance): bool
    {
        // System admin can access everything
        if ($user->is_system_admin) {
            return true;
        }

        $organization = $maintenance->organization ?? $user->organization;
        if (!$organization) {
            return false;
        }

        // Organization owners/admins can access all maintenance
        if ($this->userHasOrganizationPermission($user, $organization, 'manage_maintenance')) {
            return true;
        }

        // Check if maintenance's service belongs to user's teams
        if ($maintenance->service_id) {
            $userTeamIds = $user->teams()->where('organization_id', $organization->id)->pluck('teams.id')->toArray();
            return $maintenance->service()->whereIn('team_id', $userTeamIds)->exists();
        }
        
        // If no specific service, check organization-level permission
        return $this->userHasOrganizationPermission($user, $organization, 'manage_maintenance');
    }

    /**
     * Get detailed permissions for frontend with resource-specific access
     */
    public function getDetailedUserPermissions(User $user, Organization $organization): array
    {
        $permissions = $this->getAllUserPermissions($user, $organization);
        
        // Add resource-specific permissions
        $accessibleTeams = $this->getUserAccessibleTeams($user, $organization);
        $accessibleServices = $this->getUserAccessibleServices($user, $organization);
        
        // Build detailed permission structure
        $detailedPermissions = [
            'organization' => $permissions['organization'],
            'teams' => $permissions['teams'],
            'resources' => [
                'teams' => [
                    'accessible' => $accessibleTeams->pluck('id')->toArray(),
                    'can_create' => $this->userHasOrganizationPermission($user, $organization, 'manage_teams'),
                ],
                'services' => [
                    'accessible' => $accessibleServices->pluck('id')->toArray(),
                    'can_create' => $this->userHasOrganizationPermission($user, $organization, 'manage_services') || 
                                   $accessibleTeams->some(fn($team) => $this->userHasTeamPermission($user, $team, 'manage_services')),
                ],
                'incidents' => [
                    'can_create' => $this->userHasOrganizationPermission($user, $organization, 'manage_incidents') || 
                                   $accessibleTeams->some(fn($team) => $this->userHasTeamPermission($user, $team, 'manage_incidents')),
                ],
                'maintenance' => [
                    'can_create' => $this->userHasOrganizationPermission($user, $organization, 'manage_maintenance') || 
                                   $accessibleTeams->some(fn($team) => $this->userHasTeamPermission($user, $team, 'manage_maintenance')),
                ],
            ],
        ];

        return $detailedPermissions;
    }
} 