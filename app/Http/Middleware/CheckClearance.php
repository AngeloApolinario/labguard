<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckClearance
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // 1. If the user is NOT logged in, don't redirect to /terminal!
        // Just let them pass through so the 'auth' middleware can send them to /login.
        if (!auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();

        // 2. If they have the right role, let them in.
        if ($user->role === $role) {
            return $next($request);
        }

        // 3. ONLY redirect if they are logged in but in the WRONG place.
        if ($user->role === 'admin') {
            return redirect()->route('admin.index');
        }

        if ($user->role === 'personnel') {
            return redirect()->route('personnel.index');
        }

        return $next($request);
    }
}
