<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictStudents
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. FAST EXIT: Guest users
        if (!auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();

        // 2. FAST EXIT: Non-student roles (Admin, Faculty, etc.)
        if (strcasecmp($user->role, 'student') !== 0) {
            return $next($request);
        }

        // 3. FAST EXIT: Allow Livewire, AJAX/JSON, Logout, and Verification actions
        if (
            $request->expectsJson() ||
            $request->is('livewire/*') ||
            $request->routeIs('livewire.*', 'logout', 'verification.*', 'registration.verified')
        ) {
            // Only update session if on the notice page and not previously set
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

        // 5. VERIFIED STUDENTS: Restrict access to restricted administrative prefixes
        if ($request->is('terminal*', 'dashboard*', 'super-admin*')) {
            return redirect()->route('profile.show');
        }

        return $next($request);
    }
}
