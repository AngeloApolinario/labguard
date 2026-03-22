<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TerminalController;

// The Python script calls these via https://labguard.test/api/pc/...
Route::prefix('pc')->group(function () {
    // We use the 'login' method we built earlier for ID + Password + Verification check
    Route::post('/login', [TerminalController::class, 'login']);

    // Status check for the heartbeat loop
    Route::get('/status/{pc_number}', [TerminalController::class, 'checkStatus']);

    // Logout for when the script closes
    Route::post('/logout', [TerminalController::class, 'handleLogout']);
});
