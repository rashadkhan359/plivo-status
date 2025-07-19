<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    /**
     * Display a listing of teams.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Team::class);
        
        $organization = $this->getCurrentOrganization();
        $user = Auth::user();
        
        // All users (including system admins) see only their organization's teams
        if (!$organization) {
            $teams = collect();
        } else {
            $teams = $organization->teams()->with(['members', 'services']);
        }
        
        // Apply pagination
        $teams = $teams->paginate(12)->withQueryString();
        
        return Inertia::render('teams/index', [
            'teams' => $teams,
        ]);
    }

    /**
     * Show the form for creating a new team.
     */
    public function create(): Response
    {
        $this->authorize('create', Team::class);
        
        $user = Auth::user();
        
        return Inertia::render('teams/create', [
            'organizations' => null, // No need to show all organizations
        ]);
    }

    /**
     * Store a newly created team.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Team::class);
        
        $organization = $this->getOrganizationForResource($request);
        $user = Auth::user();
        
        // For system admins, require organization_id
        if ($user->isSystemAdmin() && !$organization) {
            abort(422, 'Organization ID is required for system admins.');
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
        ]);
        
        $team = $organization->teams()->create([
            ...$validated,
            'created_by' => $user->id,
        ]);
        
        return redirect()->route('teams.index')->with('success', 'Team created successfully.');
    }

    /**
     * Display the specified team.
     */
    public function show(Team $team): Response
    {
        $this->authorize('view', $team);
        
        $organization = $this->getCurrentOrganization();
        $user = Auth::user();
        $team->load(['members', 'services']);
        
        // Get all users in the organization for member management
        $availableUsers = $organization ? $organization->users()->get() : collect();
        
        // Get team role permissions
        $permissionService = app(PermissionService::class);
        $teamRolePermissions = $permissionService->getTeamRolePermissions($team);
        
        return Inertia::render('teams/show', [
            'team' => $team,
            'canManageMembers' => $user->can('manageMembers', $team),
            'availableUsers' => $availableUsers,
            'teamRolePermissions' => $teamRolePermissions,
        ]);
    }

    /**
     * Show the form for editing the specified team.
     */
    public function edit(Team $team): Response
    {
        $this->authorize('update', $team);
        
        return Inertia::render('teams/edit', [
            'team' => $team,
        ]);
    }

    /**
     * Update the specified team.
     */
    public function update(Request $request, Team $team)
    {
        $this->authorize('update', $team);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
        ]);
        
        $team->update($validated);
        
        return redirect()->route('teams.index')->with('success', 'Team updated successfully.');
    }

    /**
     * Remove the specified team.
     */
    public function destroy(Team $team)
    {
        $this->authorize('delete', $team);
        
        $team->delete();
        
        return redirect()->route('teams.index')->with('success', 'Team deleted successfully.');
    }

    /**
     * Join a team.
     */
    public function join(Team $team)
    {
        $this->authorize('join', $team);
        
        $user = Auth::user();
        
        if (!$user->teams()->where('teams.id', $team->id)->exists()) {
            $user->teams()->attach($team->id, ['role' => 'member']);
        }
        
        return redirect()->back()->with('success', 'Joined team successfully.');
    }

    /**
     * Leave a team.
     */
    public function leave(Team $team)
    {
        $this->authorize('leave', $team);
        
        $user = Auth::user();
        $user->teams()->detach($team->id);
        
        return redirect()->back()->with('success', 'Left team successfully.');
    }

    /**
     * Add a member to the team.
     */
    public function addMember(Request $request, Team $team)
    {
        $this->authorize('manageMembers', $team);
        
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:member,lead',
        ]);
        
        $user = User::findOrFail($validated['user_id']);
        $currentUser = Auth::user();
        
        // Check if user belongs to same organization
        $organization = $this->getCurrentOrganization();
        if (!$organization) {
            return back()->withErrors(['user_id' => 'No organization context available.']);
        }
        
        if (!$user->organizations()->where('organizations.id', $organization->id)->exists()) {
            return back()->withErrors(['user_id' => 'User does not belong to this organization.']);
        }
        
        if (!$user->teams()->where('teams.id', $team->id)->exists()) {
            $user->teams()->attach($team->id, [
                'role' => $validated['role']
            ]);
            
            // Assign default permissions for the role
            $permissionService = app(PermissionService::class);
            $permissionService->assignDefaultTeamPermissions($user, $team, $validated['role']);
        }
        
        return redirect()->back()->with('success', 'Member added to team.');
    }

    /**
     * Remove a member from the team.
     */
    public function removeMember(Team $team, User $user)
    {
        $this->authorize('manageMembers', $team);
        
        $user->teams()->detach($team->id);
        
        return redirect()->back()->with('success', 'Member removed from team.');
    }

    /**
     * Update a member's role in the team.
     */
    public function updateMemberRole(Request $request, Team $team, User $user)
    {
        $this->authorize('manageMembers', $team);
        
        $validated = $request->validate([
            'role' => 'required|in:member,lead',
        ]);
        
        $user->teams()->updateExistingPivot($team->id, [
            'role' => $validated['role']
        ]);
        
        // Update permissions for the new role
        $permissionService = app(PermissionService::class);
        $permissionService->assignDefaultTeamPermissions($user, $team, $validated['role']);
        
        return redirect()->back()->with('success', 'Member role updated.');
    }

    /**
     * Get available permissions for teams or organizations
     */
    public function getAvailablePermissions(Request $request)
    {
        $permissionService = app(PermissionService::class);
        $scope = $request->get('scope', 'team');
        $permissions = $permissionService->getAvailablePermissions($scope);
        
        return response()->json([
            'permissions' => $permissions,
        ]);
    }

    /**
     * Update role permissions for the team.
     */
    public function updateRolePermissions(Request $request, Team $team)
    {
        $this->authorize('manageMembers', $team);
        
        $validated = $request->validate([
            'role' => 'required|string|in:lead,member',
            'permissions' => 'required|array',
            'permissions.*' => 'boolean',
        ]);

        $permissionService = app(PermissionService::class);
        $permissionService->updateTeamRolePermissions($team, $validated['role'], $validated['permissions']);

        return back()->with('success', 'Team role permissions updated successfully.');
    }


} 