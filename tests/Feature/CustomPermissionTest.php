<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Organization;
use App\Models\Team;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_organization_permissions_override_defaults()
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        
        // Attach user to organization
        $organization->addUser($user, 'member');
        
        $permissionService = app(PermissionService::class);
        
        // Create custom permissions that override defaults
        $customPermissions = [
            'manage_organization' => false,
            'manage_users' => false,
            'manage_teams' => false,
            'manage_services' => true, // Override: member can manage services
            'manage_incidents' => true, // Override: member can manage incidents
            'manage_maintenance' => false,
            'view_analytics' => true, // Override: member can view analytics
        ];
        
        // Update organization role permissions
        $permissionService->updateOrganizationRolePermissions($organization, 'member', $customPermissions);
        
        // Verify the custom permissions are applied
        $this->assertTrue($permissionService->userHasOrganizationPermission($user, $organization, 'manage_services'));
        $this->assertTrue($permissionService->userHasOrganizationPermission($user, $organization, 'manage_incidents'));
        $this->assertTrue($permissionService->userHasOrganizationPermission($user, $organization, 'view_analytics'));
        $this->assertFalse($permissionService->userHasOrganizationPermission($user, $organization, 'manage_organization'));
        $this->assertFalse($permissionService->userHasOrganizationPermission($user, $organization, 'manage_users'));
    }

    public function test_custom_team_permissions_override_defaults()
    {
        $organization = Organization::factory()->create();
        $team = Team::factory()->create(['organization_id' => $organization->id]);
        $user = User::factory()->create();
        
        // Attach user to team
        $user->teams()->attach($team->id, ['role' => 'member']);
        
        $permissionService = app(PermissionService::class);
        
        // Create custom permissions that override defaults
        $customPermissions = [
            'manage_teams' => false,
            'manage_services' => true, // Override: member can manage services
            'manage_incidents' => true,
            'manage_maintenance' => true,
            'view_analytics' => true, // Override: member can view analytics
        ];
        
        // Update team role permissions
        $permissionService->updateTeamRolePermissions($team, 'member', $customPermissions);
        
        // Verify the custom permissions are applied
        $this->assertTrue($permissionService->userHasTeamPermission($user, $team, 'manage_services'));
        $this->assertTrue($permissionService->userHasTeamPermission($user, $team, 'manage_incidents'));
        $this->assertTrue($permissionService->userHasTeamPermission($user, $team, 'manage_maintenance'));
        $this->assertTrue($permissionService->userHasTeamPermission($user, $team, 'view_analytics'));
        $this->assertFalse($permissionService->userHasTeamPermission($user, $team, 'manage_teams'));
    }

    public function test_organization_role_permissions_with_custom_returns_correct_data()
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        
        // Attach user to organization
        $organization->addUser($user, 'member');
        
        $permissionService = app(PermissionService::class);
        
        // Create custom permissions
        $customPermissions = [
            'manage_services' => true,
            'manage_incidents' => true,
            'view_analytics' => true,
        ];
        
        // Update organization role permissions
        $permissionService->updateOrganizationRolePermissions($organization, 'member', $customPermissions);
        
        // Get role permissions with custom support
        $rolePermissions = $permissionService->getOrganizationRolePermissionsWithCustom($organization);
        
        // Find the member role
        $memberRole = collect($rolePermissions)->firstWhere('role', 'member');
        
        $this->assertNotNull($memberRole);
        $this->assertEquals(1, $memberRole['usersCount']);
        $this->assertTrue($memberRole['permissions']['manage_services']);
        $this->assertTrue($memberRole['permissions']['manage_incidents']);
        $this->assertTrue($memberRole['permissions']['view_analytics']);
    }

    public function test_team_role_permissions_with_custom_returns_correct_data()
    {
        $organization = Organization::factory()->create();
        $team = Team::factory()->create(['organization_id' => $organization->id]);
        $user = User::factory()->create();
        
        // Attach user to team
        $user->teams()->attach($team->id, ['role' => 'member']);
        
        $permissionService = app(PermissionService::class);
        
        // Create custom permissions
        $customPermissions = [
            'manage_services' => true,
            'manage_incidents' => true,
            'manage_maintenance' => true,
            'view_analytics' => true,
        ];
        
        // Update team role permissions
        $permissionService->updateTeamRolePermissions($team, 'member', $customPermissions);
        
        // Get role permissions with custom support
        $rolePermissions = $permissionService->getTeamRolePermissions($team);
        
        // Find the member role
        $memberRole = collect($rolePermissions)->firstWhere('role', 'member');
        
        $this->assertNotNull($memberRole);
        $this->assertEquals(1, $memberRole['usersCount']);
        $this->assertTrue($memberRole['permissions']['manage_services']);
        $this->assertTrue($memberRole['permissions']['manage_incidents']);
        $this->assertTrue($memberRole['permissions']['manage_maintenance']);
        $this->assertTrue($memberRole['permissions']['view_analytics']);
    }
} 