<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Organization;
use App\Models\Team;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_role_permissions_are_assigned_correctly()
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        
        // First attach user to organization
        $organization->addUser($user, 'owner');
        
        $permissionService = app(PermissionService::class);
        
        // Test owner permissions
        $permissionService->assignDefaultOrganizationPermissions($user, $organization, 'owner');
        
        $userOrganization = $user->organizations()->where('organizations.id', $organization->id)->first();
        $permissions = $userOrganization->pivot->permissions;
        
        $this->assertTrue($permissions['manage_organization']);
        $this->assertTrue($permissions['manage_users']);
        $this->assertTrue($permissions['manage_teams']);
        $this->assertTrue($permissions['manage_services']);
        $this->assertTrue($permissions['manage_incidents']);
        $this->assertTrue($permissions['manage_maintenance']);
        $this->assertTrue($permissions['view_analytics']);
        
        // Test member permissions
        $permissionService->assignDefaultOrganizationPermissions($user, $organization, 'member');
        
        $userOrganization = $user->organizations()->where('organizations.id', $organization->id)->first();
        $permissions = $userOrganization->pivot->permissions;
        
        $this->assertFalse($permissions['manage_organization']);
        $this->assertFalse($permissions['manage_users']);
        $this->assertFalse($permissions['manage_teams']);
        $this->assertFalse($permissions['manage_services']);
        $this->assertFalse($permissions['manage_incidents']);
        $this->assertFalse($permissions['manage_maintenance']);
        $this->assertFalse($permissions['view_analytics']);
    }

    public function test_team_role_permissions_are_assigned_correctly()
    {
        $organization = Organization::factory()->create();
        $team = Team::factory()->create(['organization_id' => $organization->id]);
        $user = User::factory()->create();
        
        // First attach user to team
        $user->teams()->attach($team->id, ['role' => 'lead']);
        
        $permissionService = app(PermissionService::class);
        
        // Test team lead permissions
        $permissionService->assignDefaultTeamPermissions($user, $team, 'lead');
        
        $userTeam = $user->teams()->where('teams.id', $team->id)->first();
        $permissions = $userTeam->pivot->permissions;
        
        $this->assertTrue($permissions['manage_teams']);
        $this->assertTrue($permissions['manage_services']);
        $this->assertTrue($permissions['manage_incidents']);
        $this->assertTrue($permissions['manage_maintenance']);
        $this->assertTrue($permissions['view_analytics']);
        
        // Test team member permissions
        $permissionService->assignDefaultTeamPermissions($user, $team, 'member');
        
        $userTeam = $user->teams()->where('teams.id', $team->id)->first();
        $permissions = $userTeam->pivot->permissions;
        
        $this->assertFalse($permissions['manage_teams']);
        $this->assertFalse($permissions['manage_services']);
        $this->assertTrue($permissions['manage_incidents']);
        $this->assertTrue($permissions['manage_maintenance']);
        $this->assertFalse($permissions['view_analytics']);
    }

    public function test_permission_hierarchy_works_correctly()
    {
        $organization = Organization::factory()->create();
        $team = Team::factory()->create(['organization_id' => $organization->id]);
        $user = User::factory()->create();
        
        // Attach user to both organization and team
        $organization->addUser($user, 'member');
        $user->teams()->attach($team->id, ['role' => 'lead']);
        
        $permissionService = app(PermissionService::class);
        
        // Give user organization-level member role (no permissions)
        $permissionService->assignDefaultOrganizationPermissions($user, $organization, 'member');
        
        // Give user team-level lead role (has team permissions)
        $permissionService->assignDefaultTeamPermissions($user, $team, 'lead');
        
        // User should have team permissions even though organization role is member
        $this->assertTrue($permissionService->userHasTeamPermission($user, $team, 'manage_services'));
        $this->assertFalse($permissionService->userHasOrganizationPermission($user, $organization, 'manage_services'));
        
        // But user should be able to manage services through team permission
        $this->assertTrue($permissionService->userCan($user, 'manage_services', $team));
    }
} 