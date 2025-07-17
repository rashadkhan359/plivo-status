<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;
use App\Models\Organization;
use Illuminate\Http\Request;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Get the current organization from the middleware context.
     * 
     * @return Organization
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function getCurrentOrganization(): Organization
    {
        try {
            return App::make('current_organization');
        } catch (\Illuminate\Contracts\Container\BindingResolutionException $e) {
            // Fallback to legacy organization for backward compatibility
            $user = Auth::user();
            if ($user && $user->organization) {
                return $user->organization;
            }
            
            abort(403, 'You do not have access to any organization.');
        }
    }

    /**
     * Handle organization access denied with user-friendly response
     */
    protected function handleOrganizationAccessDenied()
    {
        $user = Auth::user();
        
        if (request()->expectsJson()) {
            abort(403, 'You do not have access to this organization.');
        }
        
        // Check if user has any organizations
        if ($user && $user->organizations()->exists()) {
            // User has organizations but not this one
            return redirect()->route('dashboard')
                ->with('error', 'You do not have access to the requested organization.');
        } else {
            // User has no organizations
            Auth::logout();
            return redirect()->route('login')
                ->withErrors(['email' => 'Your account is not associated with any organization.']);
        }
    }

    /**
     * Handle authorization exceptions with better UX
     */
    protected function handleAuthorizationException($exception, $fallbackRoute = 'dashboard')
    {
        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'You do not have permission to perform this action.',
                'code' => 'INSUFFICIENT_PERMISSIONS'
            ], 403);
        }
        
        return redirect()->route($fallbackRoute)
            ->with('error', 'You do not have permission to perform this action.');
    }

    /**
     * Check if user has specific permission in current organization
     */
    protected function hasPermission(string $permission): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        try {
            $organization = $this->getCurrentOrganization();
            $userOrg = $user->organizations()
                ->where('organizations.id', $organization->id)
                ->first();
                
            if (!$userOrg) return false;
            
            $permissions = json_decode($userOrg->pivot->permissions ?? '[]', true);
            return in_array($permission, $permissions) || 
                   in_array('*', $permissions) || 
                   $userOrg->pivot->role === 'owner';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get user's role in current organization
     */
    protected function getCurrentRole(): ?string
    {
        $user = Auth::user();
        if (!$user) return null;
        
        try {
            $organization = $this->getCurrentOrganization();
            $userOrg = $user->organizations()
                ->where('organizations.id', $organization->id)
                ->first();
                
            return $userOrg ? $userOrg->pivot->role : null;
        } catch (\Exception $e) {
            return $user->role ?? null; // Fallback to legacy role
        }
    }
}