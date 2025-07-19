<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'organization_name' => 'required|string|max:255|unique:organizations,name',
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($request) {
            // Create organization
            $orgName = $request->organization_name;
            $slug = Str::slug($orgName);
            $slugBase = $slug;
            $i = 1;
            while (Organization::where('slug', $slug)->exists()) {
                $slug = $slugBase . '-' . $i++;
            }
            
            $organization = Organization::create([
                'name' => $orgName,
                'slug' => $slug,
                'domain' => null,
                'settings' => [
                    'allow_registrations' => false,
                    'default_role' => 'member'
                ],
                'timezone' => 'UTC',
            ]);
            
            // Create user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'organization_id' => $organization->id, // Keep for backward compatibility
                'role' => 'admin', // Keep for backward compatibility
            ]);
            
            // Set user as organization owner and created_by
            $organization->update(['created_by' => $user->id]);
            
            // Attach user to organization with owner role
            $user->organizations()->attach($organization->id, [
                'role' => 'owner',
                'permissions' => [
                    'manage_organization' => true,
                    'manage_users' => true,
                    'manage_teams' => true,
                    'manage_services' => true,
                    'manage_incidents' => true,
                    'manage_maintenance' => true,
                    'view_analytics' => true,
                ],
                'joined_at' => now(),
                'is_active' => true,
            ]);
            
            return $user;
        });

        event(new Registered($user));
        Auth::login($user);
        
        return redirect()->intended(route('dashboard', absolute: false))
            ->with('flash', [
                'success' => 'Account created successfully! Welcome to ' . $request->organization_name . '.'
            ]);
    }
}
