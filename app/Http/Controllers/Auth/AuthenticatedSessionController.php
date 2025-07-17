<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();
        
        $user = Auth::user();
        
        // Check if user has access to any organization
        $hasOrganizationAccess = $this->verifyOrganizationAccess($user);
        
        if (!$hasOrganizationAccess) {
            Auth::logout();
            return back()->withErrors([
                'email' => 'Your account is not associated with any organization or has been deactivated.',
            ]);
        }
        
        // Always redirect to dashboard after login
        return redirect()->route('dashboard');
    }

    /**
     * Verify user has access to at least one organization
     */
    protected function verifyOrganizationAccess($user): bool
    {
        // Check new pivot table relationships
        if ($user->organizations()->wherePivot('is_active', true)->exists()) {
            return true;
        }
        
        // Fallback to legacy organization_id for backward compatibility
        if ($user->organization_id && $user->organization) {
            return true;
        }
        
        return false;
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
