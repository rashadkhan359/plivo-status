<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrganizationResource;
use App\Models\User;
use App\Models\Invitation;
use App\Notifications\TeamInvitation;
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

        // Get role permissions from pivot table
        $rolePermissions = [];
        $roles = ['owner', 'admin', 'member'];
        
        foreach ($roles as $role) {
            $roleUsers = $organization->users()->wherePivot('role', $role)->get();
            if ($roleUsers->isNotEmpty()) {
                $permissionsData = $roleUsers->first()->pivot->permissions ?? '[]';
                $permissions = is_string($permissionsData) ? json_decode($permissionsData, true) : $permissionsData;
                $permissions = is_array($permissions) ? $permissions : [];
                
                $rolePermissions[$role] = [
                    'manage_organization' => in_array('manage_organization', $permissions),
                    'manage_users' => in_array('manage_users', $permissions),
                    'manage_teams' => in_array('manage_teams', $permissions),
                    'manage_services' => in_array('manage_services', $permissions),
                    'manage_incidents' => in_array('manage_incidents', $permissions),
                    'manage_maintenance' => in_array('manage_maintenance', $permissions),
                    'view_analytics' => in_array('view_analytics', $permissions),
                ];
            }
        }

        return Inertia::render('settings/organization-team', [
            'organization' => new OrganizationResource($organization),
            'members' => \App\Http\Resources\UserResource::collection($members),
            'currentUser' => new \App\Http\Resources\UserResource($user),
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
            'role' => 'required|string|in:owner,admin,member',
            'permissions' => 'required|array',
            'permissions.*' => 'boolean',
        ]);

        // Only owners can update role permissions
        if (!in_array($user->role, ['owner'])) {
            return back()->withErrors(['role' => 'Only organization owners can update role permissions.']);
        }

        // Update permissions for all users with this role in the organization
        $permissionKeys = array_keys(array_filter($validated['permissions']));
        
        $organization->users()
            ->wherePivot('role', $validated['role'])
            ->updateExistingPivot($organization->users()->pluck('users.id')->toArray(), [
                'permissions' => json_encode($permissionKeys)
            ]);

        return back()->with('success', 'Role permissions updated successfully.');
    }
} 