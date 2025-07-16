<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Example: Ensure user is accessing their own tenant's resources
        $user = Auth::user();
        if ($user && $request->route('organization') && $user->organization_id != $request->route('organization')->id) {
            abort(403, 'Unauthorized tenant access.');
        }
        return $next($request);
    }
} 