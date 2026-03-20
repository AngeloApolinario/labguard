<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TwoFactorController extends Controller
{
    public function index()
    {
        return view('auth.two-factor'); // Create this view next
    }

    public function store(Request $request)
    {
        $request->validate(['two_factor_code' => 'required']);
        $user = auth()->user();

        if ($request->two_factor_code == $user->two_factor_code) {
            $user->resetTwoFactorCode(); // We'll add this to the Model next
            return redirect()->route('dashboard');
        }

        return back()->withErrors(['two_factor_code' => 'The code is incorrect.']);
    }
}
