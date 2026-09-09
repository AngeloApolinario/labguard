<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        // Clear any intended URL saved in session prior to login
        session()->forget('url.intended');

        $user = auth()->user();
        $role = strtolower($user->role);

        // 1. If unverified student, ALWAYS send to verification notice upon login
        if ($role === 'student' && is_null($user->email_verified_at)) {
            return redirect()->route('verification.notice');
        }

        // 2. Role destinations for verified users & staff
        return match ($role) {
            'super-admin' => redirect()->route('super-admin.index'),
            'admin'       => redirect()->route('dashboard.index'),
            'personnel'   => redirect()->route('personnel.index'),
            default       => redirect()->route('profile.show'),
        };
    }
}
