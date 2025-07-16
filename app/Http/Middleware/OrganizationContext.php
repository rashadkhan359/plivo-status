<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrganizationContext
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if (!$user || !$user->organization_id) {
            abort(403, 'No organization context.');
        }
        // Optionally, set organization context globally (e.g., app()->instance('organization', $user->organization))
        return $next($request);
    }
} 