<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RestrictStudents
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            $user = auth()->user();

            // Only apply these rules to the Student role
            if (strcasecmp($user->role, 'student') === 0) {

                // 1. ALLOWED ROUTES: Always allow these regardless of verification status
                // This breaks the "Too Many Redirects" loop
                if ($request->routeIs('profile.show', 'registration.verified', 'verification.*', 'logout')) {
                    return $next($request);
                }

                // 2. VERIFICATION CHECK: 
                // If they aren't verified, they can ONLY see the profile or the "Please Verify" notice
                if (!$user->hasVerifiedEmail()) {
                    return redirect()->route('verification.notice');
                }

                // 3. ROLE ACCESS:
                // If they ARE verified but try to enter Admin/Personnel areas, bounce them to profile
                if ($request->is('terminal*') || $request->is('dashboard*') || $request->is('super-admin*')) {
                    return redirect()->route('profile.show');
                }
            }
        }

        return $next($request);
    }
}
