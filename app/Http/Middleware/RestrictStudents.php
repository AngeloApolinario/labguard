<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictStudents
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. FAST EXIT: Unauthenticated users
        if (!auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();

        // 2. FAST EXIT: Non-student roles (Admin, Faculty, etc.)
        if (strcasecmp($user->role, 'student') !== 0) {
            return $next($request);
        }

        // 3. FAST EXIT: Livewire, AJAX/JSON, Logout, and Verification routes
        if (
            $request->expectsJson() ||
            $request->is('livewire/*') ||
            $request->routeIs('livewire.*', 'logout', 'verification.*', 'registration.verified')
        ) {
            // Write to session only when visiting the notice route for the first time
            if ($request->routeIs('verification.notice') && !session('has_seen_verification_notice')) {
                session(['has_seen_verification_notice' => true]);
            }

            return $next($request);
        }

        // 4. UNVERIFIED STUDENTS: Enforce Verification Notice
        if (!$user->hasVerifiedEmail()) {
            if (!session('has_seen_verification_notice')) {
                return redirect()->route('verification.notice');
            }

            if ($request->routeIs('profile.show')) {
                return $next($request);
            }

            return redirect()->route('verification.notice');
        }

        // 5. VERIFIED STUDENTS: Prevent access to admin & terminal endpoints
        if ($request->is('terminal*', 'dashboard*', 'super-admin*')) {
            return redirect()->route('profile.show');
        }

        return $next($request);
    }
}
