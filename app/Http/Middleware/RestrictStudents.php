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

        // 4. UNVERIFIED STUDENTS: Show verification notice first for /dashboard and general routes
        if (is_null($user->email_verified_at)) {

            // Allow verification notice, user profile (user.show/profile.show), logout, and Livewire
            if (
                $request->routeIs('verification.notice', 'verification.verify', 'verification.send', 'user.show', 'profile.show', 'logout') ||
                $request->is('email/verify*', 'user/*', 'profile/*', 'livewire/*') ||
                $request->expectsJson()
            ) {
                return $next($request);
            }

            // Redirect unverified students attempting to visit /dashboard or other pages to verification notice first
            return redirect()->route('verification.notice');
        }

        // 5. VERIFIED STUDENTS: Allow full access
        return $next($request);
    }
}
