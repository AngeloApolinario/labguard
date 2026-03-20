<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = auth()->user();
        $role = strtolower($user->role); // Force lowercase to prevent "Admin" vs "admin" bugs

        return match ($role) {
            'super-admin' => redirect()->route('super-admin.index'),
            'admin'       => redirect()->route('dashboard.index'),
            'personnel'   => redirect()->route('personnel.index'),
            default       => redirect()->route('profile.show'), // Ensure this route exists!
        };
    }
}
