<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\LabController;
use App\Http\Controllers\Dashboard\AlertController;
use App\Http\Controllers\Dashboard\SettingsController;
use App\Http\Controllers\PersonnelController;
use App\Http\Controllers\PersonnelLabController;

// Redirect root to Login (Your existing logic)
Route::get('/', function () {
    return view('auth.login');
});

// Authenticated LabGuard Routes
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'clearance:admin',
])->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('index');
    Route::get('/labs', [LabController::class, 'index'])->name('labs');
    Route::get('/alerts', [AlertController::class, 'index'])->name('alerts');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
});

Route::middleware(['auth', 'verified', 'clearance:personnel'])
    ->prefix('terminal')
    ->name('personnel.')
    ->group(function () {
        Route::get('/', [PersonnelController::class, 'index'])->name('index');
        Route::get('/labs', [PersonnelLabController::class, 'index'])->name('labs');
    });
