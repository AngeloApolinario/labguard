<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TerminalController;

/*
|--------------------------------------------------------------------------
| Terminal API Routes
|--------------------------------------------------------------------------
| These routes are accessed via https://labguard.test/api/pc/...
*/

Route::prefix('pc')->group(function () {

    // Auth: Validates Student ID + Password
    Route::post('/login', [TerminalController::class, 'login']);

    // Heartbeat: Checks if the PC should stay unlocked
    Route::get('/status/{pc_number}', [TerminalController::class, 'checkStatus']);

    // Session End: Updates PC status to 'available'
    Route::post('/logout', [TerminalController::class, 'handleLogout']);

    // Maintenance: Receives technical reports from the Python UI
    Route::post('/alerts', [TerminalController::class, 'reportIssue']);
});
