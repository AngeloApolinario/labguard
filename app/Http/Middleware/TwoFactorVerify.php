<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorVerify
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        // If the user is logged in and has a 2FA code pending...
        if (auth()->check() && $user->two_factor_code) {

            // If they try to go anywhere else besides the verification page, send them back
            if (!$request->is('verify*')) {
                return redirect()->route('verify.index');
            }
        }

        return $next($request);
    }
}
