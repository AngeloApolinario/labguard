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

        return match ($role) {
            'super-admin' => redirect()->route('super-admin.index'),
            'admin'       => redirect()->route('dashboard.index'),
            'personnel'   => redirect()->route('personnel.index'),
            default       => redirect()->route('profile.show'),
        };
    }
}
