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
        $userRole = trim(strtolower((string) ($user->role ?? '')));

        // 2. Non-student roles (Admins, Teachers, Staff) pass through
        if ($userRole !== 'student') {
            return $next($request);
        }

        // 3. Prevent students from accessing admin/terminal management routes
        if ($request->is('terminal*', 'super-admin*')) {
            return redirect()->to('/dashboard');
        }

        // 4. UNVERIFIED STUDENTS HANDLING
        if (is_null($user->email_verified_at)) {

            // Always allow essential background actions (resending verification email, verification links, logout, Livewire)
            if (
                $request->routeIs('verification.verify', 'verification.send', 'logout') ||
                $request->is('logout', 'livewire/*') ||
                $request->expectsJson()
            ) {
                return $next($request);
            }

            // Force notice FIRST: When viewing the notice page, set the session flag and allow access
            if ($request->routeIs('verification.notice') || $request->is('email/verify*')) {
                session(['has_seen_verification_notice' => true]);
                return $next($request);
            }

            // ONLY AFTER seeing the notice: Allow access to user profile routes
            if (session('has_seen_verification_notice') === true) {
                if (
                    $request->routeIs('user.show', 'profile.show') ||
                    $request->is('user/*', 'profile/*')
                ) {
                    return $next($request);
                }
            }

            // Otherwise (first login/registration redirect or trying to visit /dashboard), force redirect to notice page first
            return redirect()->route('verification.notice');
        }

        // 5. VERIFIED STUDENTS: Allow full access
        return $next($request);
    }
}
