<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);
        
        $organization = $this->getCurrentOrganization();
        $user = Auth::user();
        
        // System admins can see all users across all organizations
        if ($user->isSystemAdmin()) {
            $users = User::with(['teams', 'organizations'])->get();
            $teams = \App\Models\Team::all();
        } else {
            // Regular users see only their organization's users
            if (!$organization) {
                $users = collect();
                $teams = collect();
            } else {
                $users = $organization->users()->with(['teams'])->get();
                $teams = $organization->teams()->get();
            }
        }
        
        return Inertia::render('users/index', [
            'users' => $users,
            'teams' => $teams,
            'canCreate' => $user->can('create', User::class),
        ]);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): Response
    {
        $this->authorize('view', $user);
        
        $user->load(['teams', 'organizations']);
        
        return Inertia::render('users/show', [
            'user' => $user,
            'canUpdate' => Auth::user()->can('update', $user),
            'canChangeRole' => Auth::user()->can('changeRole', $user),
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $this->authorize('create', User::class);
        
        $organization = $this->getOrganizationForResource($request);
        $user = Auth::user();
        
        // For system admins, require organization_id
        if ($user->isSystemAdmin() && !$organization) {
            abort(422, 'Organization ID is required for system admins.');
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users',
            'role' => 'required|in:member,team_lead,admin',
            'permissions' => 'nullable|array',
        ]);
        
        // Check if user already exists
        $existingUser = User::where('email', $validated['email'])->first();
        
        if ($existingUser) {
            // If user exists, just add them to the organization
            $permissions = $this->getDefaultPermissions($validated['role']);
            if (isset($validated['permissions'])) {
                $permissions = array_merge($permissions, $validated['permissions']);
            }
            
            $existingUser->organizations()->attach($organization->id, [
                'role' => $validated['role'],
                'permissions' => $permissions,
                'joined_at' => now(),
                'is_active' => true,
                'invited_by' => Auth::id(),
            ]);
            
            return redirect()->route('users.index')->with('success', 'User added to organization.');
        }
        
        // Create new user
        $newUser = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make('password'), // Default password, user will reset
            'organization_id' => $organization->id, // Backward compatibility
            'role' => $validated['role'], // Backward compatibility
        ]);
        
        // Add user to organization with role and permissions
        $permissions = $this->getDefaultPermissions($validated['role']);
        if (isset($validated['permissions'])) {
            $permissions = array_merge($permissions, $validated['permissions']);
        }
        
        $newUser->organizations()->attach($organization->id, [
            'role' => $validated['role'],
            'permissions' => $permissions,
            'joined_at' => now(),
            'is_active' => true,
            'invited_by' => Auth::id(),
        ]);
        
        // TODO: Send invitation email to user
        
        return redirect()->route('users.index')->with('success', 'User invited successfully.');
    }

    /**
     * Update the specified user's role.
     */
    public function updateRole(Request $request, User $user)
    {
        $this->authorize('changeRole', $user);
        
        $organization = $this->getCurrentOrganization();
        $currentUser = Auth::user();
        
        // For system admins, allow updating any user's role
        if ($currentUser->isSystemAdmin()) {
            // System admins need to specify which organization to update
            $organizationId = $request->input('organization_id');
            if (!$organizationId) {
                abort(422, 'Organization ID is required for system admins.');
            }
            $organization = \App\Models\Organization::findOrFail($organizationId);
        } else {
            // Regular users can only update within their organization
            if (!$organization) {
                abort(403, 'No organization context available.');
            }
        }
        
        $validated = $request->validate([
            'role' => 'required|in:member,team_lead,admin',
            'permissions' => 'nullable|array',
        ]);
        
        // Get default permissions for the role
        $permissions = $this->getDefaultPermissions($validated['role']);
        if (isset($validated['permissions'])) {
            $permissions = array_merge($permissions, $validated['permissions']);
        }
        
        // Update organization relationship
        $user->organizations()->updateExistingPivot($organization->id, [
            'role' => $validated['role'],
            'permissions' => $permissions,
        ]);
        
        // Update legacy fields for backward compatibility
        $user->update(['role' => $validated['role']]);
        
        return redirect()->back()->with('success', 'User role updated.');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        $this->authorize('delete', $user);
        
        $organization = $this->getCurrentOrganization();
        $currentUser = Auth::user();
        
        // For system admins, allow removing from any organization
        if ($currentUser->isSystemAdmin()) {
            // System admins need to specify which organization to remove from
            $organizationId = request()->input('organization_id');
            if (!$organizationId) {
                abort(422, 'Organization ID is required for system admins.');
            }
            $organization = \App\Models\Organization::findOrFail($organizationId);
        } else {
            // Regular users can only remove from their organization
            if (!$organization) {
                abort(403, 'No organization context available.');
            }
        }
        
        // Remove from organization
        $user->organizations()->detach($organization->id);
        
        // If user has no other organizations, delete the user
        if ($user->organizations()->count() === 0) {
            $user->delete();
        }
        
        return redirect()->route('users.index')->with('success', 'User removed.');
    }

    /**
     * Get default permissions for a role.
     */
    protected function getDefaultPermissions(string $role): array
    {
        return match ($role) {
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
                'manage_teams' => false,
                'manage_services' => true,
                'manage_incidents' => true,
                'manage_maintenance' => true,
                'view_analytics' => true,
            ],
            'member' => [
                'view_analytics' => false,
            ],
            default => []
        };
    }
} 