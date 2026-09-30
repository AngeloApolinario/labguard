<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckClearance
{
    public function handle(
        Request $request,
        Closure $next,
        string $role
    ): Response {

        /*
         * If the user is not authenticated,
         * let the auth middleware handle the login redirect.
         */
        if (!auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();

        /*
         * User has the required clearance.
         */
        if ($user->role === $role) {
            return $next($request);
        }

        /*
         * SUPER ADMIN
         *
         * Super admins should only access routes
         * explicitly protected by clearance:super-admin.
         */
        if ($user->role === 'super-admin') {
            return redirect()->route('super-admin.index');
        }

        /*
         * ADMIN
         */
        if ($user->role === 'admin') {
            return redirect()->route('dashboard.index');
        }

        /*
         * PERSONNEL
         */
        if ($user->role === 'personnel') {
            return redirect()->route('personnel.index');
        }

        /*
         * STUDENT
         *
         * Students should be returned to their own
         * authenticated user area instead of the admin dashboard.
         */
        if ($user->role === 'student') {
            return redirect()->route('profile.show');
        }

        /*
         * Unknown / unsupported role.
         */
        return abort(403, 'Unauthorized access.');
    }
}
