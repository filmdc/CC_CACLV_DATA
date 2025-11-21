<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        if (!$request->user()) {
            abort(403, 'Unauthorized access.');
        }

        // Handle comma-separated permissions (e.g., "perm1,perm2")
        $permissionList = [];
        foreach ($permissions as $permission) {
            if (str_contains($permission, ',')) {
                // Split comma-separated permissions
                $permissionList = array_merge($permissionList, array_map('trim', explode(',', $permission)));
            } else {
                $permissionList[] = $permission;
            }
        }

        if (!$request->user()->hasAnyPermission($permissionList)) {
            abort(403, 'You do not have the required permission to access this resource.');
        }

        return $next($request);
    }
}