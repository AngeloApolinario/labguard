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

                // 1. Unverified Check
                if (!$user->hasVerifiedEmail()) {

                    // Always allow verification routes & logout
                    if ($request->routeIs('verification.*', 'logout', 'registration.verified')) {
                        // Mark that they have visited/seen the verification page
                        session(['has_seen_verification_notice' => true]);
                        return $next($request);
                    }

                    // If they haven't seen the verification screen yet this session, FORCE THEM THERE FIRST
                    if (!session('has_seen_verification_notice')) {
                        return redirect()->route('verification.notice');
                    }

                    // Once they've seen it, allow them to view profile.show
                    if ($request->routeIs('profile.show')) {
                        return $next($request);
                    }

                    // Any other page attempt (dashboard, etc.) redirects back to verification notice
                    return redirect()->route('verification.notice');
                }

                // 2. Verified Students attempting Admin/Personnel areas -> send to profile
                if ($request->is('terminal*') || $request->is('dashboard*') || $request->is('super-admin*')) {
                    return redirect()->route('profile.show');
                }
            }
        }

        return $next($request);
    }
}
