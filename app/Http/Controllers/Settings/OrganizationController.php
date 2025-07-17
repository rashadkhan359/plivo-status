<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrganizationResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
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
        $organization = $user->organization;

        $this->authorize('manageMembers', $organization);

        $members = $organization->users()->orderBy('created_at')->get();

        return Inertia::render('settings/organization-team', [
            'organization' => new OrganizationResource($organization),
            'members' => \App\Http\Resources\UserResource::collection($members),
            'currentUser' => new \App\Http\Resources\UserResource($user),
        ]);
    }

    /**
     * Update a team member's role.
     */
    public function updateMemberRole(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $organization = $user->organization;

        $this->authorize('manageMembers', $organization);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:admin,member',
        ]);

        $member = $organization->users()->findOrFail($validated['user_id']);

        // Prevent users from demoting themselves if they're the only admin
        if ($member->id === $user->id && $validated['role'] === 'member') {
            $adminCount = $organization->users()->where('role', 'admin')->count();
            if ($adminCount <= 1) {
                return back()->withErrors(['role' => 'Cannot demote the only admin of the organization.']);
            }
        }

        $member->update(['role' => $validated['role']]);

        return back()->with('success', 'Member role updated successfully.');
    }

    /**
     * Remove a team member from the organization.
     */
    public function removeMember(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $organization = $user->organization;

        $this->authorize('manageMembers', $organization);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $member = $organization->users()->findOrFail($validated['user_id']);

        // Prevent users from removing themselves if they're the only admin
        if ($member->id === $user->id) {
            $adminCount = $organization->users()->where('role', 'admin')->count();
            if ($adminCount <= 1) {
                return back()->withErrors(['user_id' => 'Cannot remove the only admin of the organization.']);
            }
        }

        $member->delete();

        return back()->with('success', 'Member removed from organization.');
    }
} 