<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TerminalController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::post('/pc/login', [TerminalController::class, 'handleLogin']);
//STATUS ROUTES
Route::get('/pc/status/{pc_number}', [TerminalController::class, 'checkStatus']);
Route::post('/pc/logout', [TerminalController::class, 'handleLogout']);
