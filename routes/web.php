<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\LabController;
use App\Http\Controllers\Dashboard\AlertController;
use App\Http\Controllers\Dashboard\SettingsController;
use App\Http\Controllers\PersonnelController;
use App\Http\Controllers\SuperAdminController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

// 1. PUBLIC ROUTES
Route::get('/', function () {
    return view('auth.login');
});

// 2. SHARED AUTH ROUTES (Requires Login, but NOT necessarily Verified)
Route::middleware(['auth'])->group(function () {

    // Profile Landing Page (Allows students to fix settings/email)
    Route::get('/user/profile', function () {
        return view('profile.show');
    })->name('profile.show');

    // Success page after clicking the email link
    Route::get('/registration-confirmed', function () {
        return view('auth.confirmed');
    })->name('registration.verified');

    // Email verification notice & resend logic
    Route::get('/email/verify', function () {
        return view('auth.verify-email');
    })->name('verification.notice');

    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('status', 'verification-link-sent');
    })->middleware(['throttle:6,1'])->name('verification.send');
});

// 3. THE EMAIL LINK HANDLER
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect()->route('registration.verified');
})->middleware(['auth', 'signed', 'throttle:6,1'])->name('verification.verify');


// 4. ADMIN DASHBOARD (Requires Verified Status)
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'clearance:admin',
    'student.lock',
])->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('index');
    Route::get('/labs', [LabController::class, 'index'])->name('labs');
    Route::get('/alerts', [AlertController::class, 'index'])->name('alerts');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');

    // --- New User Management Routes ---
    // URL: /dashboard/users | Route Name: dashboard.users
    Route::get('/users', [DashboardController::class, 'userManagement'])->name('users');

    // URL: /dashboard/users/store | Route Name: dashboard.users.store
    Route::post('/users/store', [DashboardController::class, 'storeUser'])->name('users.store');

    // New Edit/Update/Delete Routes
    Route::patch('/users/{user}', [DashboardController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}', [DashboardController::class, 'destroyUser'])->name('users.destroy');
    //TERMINATE SESSION
    Route::patch('/sessions/{session}/terminate', [DashboardController::class, 'terminateSession'])->name('sessions.terminate');
});

// 5. PERSONNEL TERMINAL (Requires Verified Status)
Route::middleware([
    'auth',
    'verified',
    'clearance:personnel',
    'student.lock',
])->prefix('terminal')->name('personnel.')->group(function () {
    Route::get('/', [PersonnelController::class, 'index'])->name('index');
    Route::get('/labs', [PersonnelController::class, 'labs'])->name('labs');
    Route::get('/lab/{name}', [PersonnelController::class, 'showLab'])->name('lab.show');
    Route::post('/assign/{computer}', [PersonnelController::class, 'assign'])->name('assign');
    Route::post('/release/{computer}', [PersonnelController::class, 'release'])->name('release');
});

// 6. SUPER ADMIN (Requires Verified Status)
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'clearance:super-admin',
    'student.lock',
])->prefix('super-admin')->name('super-admin.')->group(function () {
    Route::get('/overview', [SuperAdminController::class, 'index'])->name('index');
    Route::get('/manage-users', [SuperAdminController::class, 'manageUsers'])->name('users');
    Route::get('/security', [SuperAdminController::class, 'security'])->name('security');
    Route::get('/analytics', [SuperAdminController::class, 'analytics'])->name('analytics');
    Route::get('/settings', [SuperAdminController::class, 'settings'])->name('settings');
    Route::get('/system-logs', [SuperAdminController::class, 'logs'])->name('logs');
});
