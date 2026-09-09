<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class RestrictStudents
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Guests pass through
        if (!auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();
        $userRole = trim(strtolower((string) ($user->role ?? '')));

        // 2. Non-students (Admins, Teachers, Staff) pass through
        if ($userRole !== 'student') {
            return $next($request);
        }

        // 3. Block students from staff management consoles
        if ($request->is('terminal*', 'super-admin*')) {
            return redirect()->to('/dashboard');
        }

        // 4. UNVERIFIED STUDENTS HANDLING
        if (is_null($user->email_verified_at)) {

            // A. Allow verification actions, logout, Livewire, and API requests
            if (
                $request->is('email*', 'logout', 'livewire*', 'registration*') ||
                $request->routeIs('verification.*', 'registration.*', 'logout') ||
                $request->expectsJson()
            ) {
                return $next($request);
            }

            // B. Allow full Profile & Account Management at all times
            // (user*, profile* covers /user/profile, /user/profile-information, /user/password, /user/confirm-password, etc.)
            if (
                $request->is('user*', 'profile*') ||
                $request->routeIs(
                    'profile.*',
                    'user.*',
                    'user-profile-information.*',
                    'user-password.*',
                    'password.confirm*',
                    'two-factor.*',
                    'current-user.*',
                    'current-user-photo.*',
                    'other-browser-sessions.*'
                )
            ) {
                return $next($request);
            }

            // C. Block everything else (including /dashboard on login) and force verification notice
            return redirect()->route('verification.notice');
        }

        // 5. VERIFIED STUDENTS: Full access granted
        return $next($request);
    }
}
