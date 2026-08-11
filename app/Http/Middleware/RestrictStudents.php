<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictStudents
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Unauthenticated users pass through
        if (!auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();

        // 2. Admins & non-student roles pass through
        if (strcasecmp($user->role, 'student') !== 0) {
            return $next($request);
        }

        // 3. Prevent access to super-admin & terminal management routes
        if ($request->is('terminal*', 'super-admin*')) {
            return redirect()->route('profile.show');
        }

        // 4. UNVERIFIED STUDENTS: Show notice first
        if (!$user->hasVerifiedEmail()) {

            // Allow these routes to load without looping
            if (
                $request->routeIs('verification.notice', 'verification.*', 'profile.show', 'logout') ||
                $request->is('livewire/*') ||
                $request->expectsJson()
            ) {
                return $next($request);
            }

            // Redirect any other route (like /dashboard) to the notice page
            return redirect()->route('verification.notice');
        }

        // 5. VERIFIED STUDENTS: Free access to normal routes
        return $next($request);
    }
}
