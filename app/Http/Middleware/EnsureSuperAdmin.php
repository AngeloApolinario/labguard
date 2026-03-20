<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, $next)
    {
        // 1. If the user is trying to verify their email or logout, let them pass!
        if ($request->routeIs('verification.*', 'logout', 'profile.show')) {
            return $next($request);
        }

        // 2. Check for Super Admin
        if (auth()->check() && auth()->user()->role === 'super-admin') {
            return $next($request);
        }

        // 3. If they are just a student, redirect to their allowed area
        return redirect()->route('profile.show');
    }
}
