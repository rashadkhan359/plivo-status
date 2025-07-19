<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrganizationResource;
use App\Models\User;
use App\Models\Invitation;
use App\Notifications\TeamInvitation;
use App\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    /**
     * Show the organization settings page.
     */
    public function edit(): Response
    {
        $user = Auth::user();
        $organization = $user->organization;

        return Inertia::render('settings/organization', [
            'organization' => new OrganizationResource($organization),
            'canUpdate' => $user->can('update', $organization),
            'canDelete' => $user->can('delete', $organization),
            'statusPageUrl' => route('status.public', $organization->slug),
        ]);
    }

    /**
     * Update the organization settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $organization = $user->organization;

        $this->authorize('update', $organization);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:organizations,name,' . $organization->id,
            'slug' => 'required|string|max:255|regex:/^[a-z0-9\-]+$/|unique:organizations,slug,' . $organization->id,
            'domain' => 'nullable|string|max:255|unique:organizations,domain,' . $organization->id,
        ]);

        // Ensure slug is properly formatted
        $validated['slug'] = Str::slug($validated['slug']);

        $organization->update($validated);

        return back()->with('success', 'Organization settings updated successfully.');
    }

    /**
     * Show the organization team management page.
     */
    public function team(): Response
    {
        $user = Auth::user();
        $organization = $this->getCurrentOrganization();

        $this->authorize('manageUsers', $organization);

        // Get users with pivot data from organization_user table
        $members = $organization->users()
            ->withPivot('role', 'joined_at')
            ->orderBy('organization_user.joined_at', 'desc')
            ->get()
            ->map(function ($member) {
                // Add role from pivot to member object for frontend
                $member->role = $member->pivot->role;
                $member->joined_at = $member->pivot->joined_at;
                return $member;
            });

        // Get role permissions with custom permissions support
        $permissionService = app(PermissionService::class);
        $rolePermissionsData = $permissionService->getOrganizationRolePermissionsWithCustom($organization);
        
        $rolePermissions = [];
        foreach ($rolePermissionsData as $roleData) {
            $rolePermissions[$roleData['role']] = $roleData['permissions'];
        }

        // Get current user with role from pivot table
        $currentUserWithRole = $organization->users()
            ->where('users.id', $user->id)
            ->withPivot('role')
            ->first();
        
        if ($currentUserWithRole) {
            $currentUserWithRole->role = $currentUserWithRole->pivot->role;
        }

        return Inertia::render('settings/organization-team', [
            'organization' => new OrganizationResource($organization),
            'members' => \App\Http\Resources\UserResource::collection($members),
            'currentUser' => new \App\Http\Resources\UserResource($currentUserWithRole ?: $user),
            'rolePermissions' => $rolePermissions,
        ]);
    }

    /**
     * Show the invite team member page.
     */
    public function inviteForm(): Response
    {
        $user = Auth::user();
        $organization = $user->organization;

        $this->authorize('manageUsers', $organization);

        return Inertia::render('settings/invite-member', [
            'organization' => new OrganizationResource($organization),
        ]);
    }

    /**
     * Send invitation to a new team member.
     */
    public function invite(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $organization = $user->organization;

        $this->authorize('manageUsers', $organization);

        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'name' => 'required|string|max:255',
            'role' => 'required|in:admin,member',
            'message' => 'nullable|string|max:500',
        ]);

        // Check if user already exists and is already in this organization
        $existingUser = User::where('email', $validated['email'])->first();

        if ($existingUser && $organization->hasUser($existingUser)) {
            return back()->withErrors(['email' => 'This user is already a member of your organization.']);
        }

        // Check if there's already a pending invitation for this email
        $existingInvitation = Invitation::where('email', $validated['email'])
            ->where('organization_id', $organization->id)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($existingInvitation) {
            return back()->withErrors(['email' => 'An invitation has already been sent to this email address.']);
        }

        // Create the invitation
        $invitation = Invitation::createInvitation([
            'organization_id' => $organization->id,
            'invited_by' => $user->id,
            'email' => $validated['email'],
            'name' => $validated['name'],
            'role' => $validated['role'],
            'message' => $validated['message'],
        ]);

        // Send the invitation email
        \Illuminate\Support\Facades\Notification::route('mail', $validated['email'])
            ->notify(new TeamInvitation($invitation));

        return back()->with('success', 'Invitation sent successfully! The user will receive an email with instructions to join your organization.');
    }

    /**
     * Update a team member's role.
     */
    public function updateMemberRole(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $organization = $this->getCurrentOrganization();

        $this->authorize('manageUsers', $organization);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:admin,member,owner',
        ]);

        // Check if user is in the organization
        $member = $organization->users()->where('users.id', $validated['user_id'])->first();
        if (!$member) {
            return back()->withErrors(['user_id' => 'User is not a member of this organization.']);
        }

        // Prevent users from demoting themselves if they're the only admin/owner
        if ($member->id === $user->id && $validated['role'] === 'member') {
            $adminCount = $organization->users()
                ->wherePivotIn('role', ['admin', 'owner'])
                ->count();
            if ($adminCount <= 1) {
                return back()->withErrors(['role' => 'Cannot demote the only admin of the organization.']);
            }
        }

        // Update role in pivot table
        $organization->users()->updateExistingPivot($validated['user_id'], [
            'role' => $validated['role']
        ]);

        // Update permissions for the new role
        $permissionService = app(PermissionService::class);
        $permissionService->assignDefaultOrganizationPermissions($member, $organization, $validated['role']);

        return back()->with('success', 'Member role updated successfully.');
    }

    /**
     * Remove a team member from the organization.
     */
    public function removeMember(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $organization = $this->getCurrentOrganization();

        $this->authorize('manageUsers', $organization);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        // Check if user is in the organization
        $member = $organization->users()->where('users.id', $validated['user_id'])->first();
        if (!$member) {
            return back()->withErrors(['user_id' => 'User is not a member of this organization.']);
        }

        // Prevent users from removing themselves if they're the only admin/owner
        if ($member->id === $user->id) {
            $adminCount = $organization->users()
                ->wherePivotIn('role', ['admin', 'owner'])
                ->count();
            if ($adminCount <= 1) {
                return back()->withErrors(['user_id' => 'Cannot remove the only admin of the organization.']);
            }
        }

        // Remove user from organization (detach from pivot table)
        $organization->users()->detach($validated['user_id']);

        return back()->with('success', 'Member removed from organization successfully.');
    }

    /**
     * Update role permissions for the organization.
     */
    public function updateRolePermissions(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $organization = $this->getCurrentOrganization();

        $this->authorize('manageUsers', $organization);

        $validated = $request->validate([
            'role' => 'required|string|in:owner,admin,team_lead,member',
            'permissions' => 'required|array',
            'permissions.*' => 'boolean',
        ]);

        // Get user's role from the pivot table
        $userOrganization = $organization->users()
            ->where('users.id', $user->id)
            ->withPivot('role')
            ->first();

        if (!$userOrganization) {
            return back()->withErrors(['role' => 'User is not a member of this organization.']);
        }

        // Only owners can update role permissions
        if ($userOrganization->pivot->role !== 'owner') {
            return back()->withErrors(['role' => 'Only organization owners can update role permissions.']);
        }

        // Update organization role permissions
        $permissionService = app(PermissionService::class);
        $permissionService->updateOrganizationRolePermissions($organization, $validated['role'], $validated['permissions']);
        
        return back()->with('success', 'Role permissions updated successfully.');
    }

    /**
     * Get permissions for a given role (same as HandleInertiaRequests)
     */
    private function getPermissionsForRole(string $role): array
    {
        return \App\Services\PermissionService::ORGANIZATION_ROLE_PERMISSIONS[$role] ?? [
            'manage_organization' => false,
            'manage_users' => false,
            'manage_teams' => false,
            'manage_services' => false,
            'manage_incidents' => false,
            'manage_maintenance' => false,
            'view_analytics' => false,
        ];
    }
} 