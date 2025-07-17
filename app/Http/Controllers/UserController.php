<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;
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
        
        $organization = App::get('current_organization');
        
        // Get users in the organization
        $users = $organization->users()
            ->with(['teams'])
            ->get();
        
        return Inertia::render('users/index', [
            'users' => $users,
            'canCreate' => Auth::user()->can('create', User::class),
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
        
        $organization = App::get('current_organization');
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|in:member,team_lead,admin',
            'permissions' => 'nullable|array',
        ]);
        
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'organization_id' => $organization->id, // Backward compatibility
            'role' => $validated['role'], // Backward compatibility
        ]);
        
        // Add user to organization with role and permissions
        $permissions = $this->getDefaultPermissions($validated['role']);
        if (isset($validated['permissions'])) {
            $permissions = array_merge($permissions, $validated['permissions']);
        }
        
        $user->organizations()->attach($organization->id, [
            'role' => $validated['role'],
            'permissions' => $permissions,
            'joined_at' => now(),
            'is_active' => true,
        ]);
        
        return redirect()->route('users.index')->with('success', 'User created.');
    }

    /**
     * Update the specified user's role.
     */
    public function updateRole(Request $request, User $user)
    {
        $this->authorize('changeRole', $user);
        
        $organization = App::get('current_organization');
        
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
        
        $organization = App::get('current_organization');
        
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