<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TerminalController;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::post('/pc/login', [TerminalController::class, 'handleLogin']);
//STATUS ROUTES
Route::get('/pc/status/{pc_number}', [TerminalController::class, 'checkStatus']);
Route::post('/pc/logout', [TerminalController::class, 'handleLogout']);

Route::post('/pc-unlock', function (Request $request) {
    $user = User::where('email', $request->email)->first();

    if ($user && Hash::check($request->password, $user->password)) {

        // CHECK: Has the student clicked the verification button?
        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please verify your email on the website first!'
            ], 403);
        }

        // Logic to create session history and unlock
        return response()->json(['status' => 'success', 'user' => $user->name]);
    }

    return response()->json(['status' => 'error', 'message' => 'Invalid Credentials'], 401);
});
