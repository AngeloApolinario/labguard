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

    // Core Dashboard & Analytics
    Route::get('/', [DashboardController::class, 'index'])->name('index');
    Route::get('/alerts', [AlertController::class, 'index'])->name('alerts');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');

    // --- User Management ---
    Route::get('/users', [DashboardController::class, 'userManagement'])->name('users');
    Route::post('/users', [DashboardController::class, 'storeUser'])->name('users.store');
    Route::patch('/users/{user}', [DashboardController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}', [DashboardController::class, 'destroyUser'])->name('users.destroy');

    // --- Session Control ---
    Route::patch('/sessions/{session}/terminate', [DashboardController::class, 'terminateSession'])->name('sessions.terminate');

    // --- Laboratory & Hardware Management ---
    // URL: /dashboard/labs
    Route::get('/labs', [LabController::class, 'index'])->name('labs');

    // --- Laboratory Scheduling ---
    // These now use the 'lab' ID to identify which room is being scheduled
    Route::get('/labs/{lab}/schedule', [LabController::class, 'viewSchedule'])->name('labs.schedule');
    Route::post('/labs/{lab}/schedule', [LabController::class, 'storeSchedule'])->name('labs.schedule.store');

    // Using {schedule} allows Route Model Binding in LabController@destroySchedule
    Route::delete('/schedule/{schedule}', [LabController::class, 'destroySchedule'])->name('labs.schedule.destroy');
});



// 5. PERSONNEL TERMINAL (Requires Verified Status)
Route::middleware([
    'auth',
    'verified',
    'clearance:personnel',
    'student.lock',
])->prefix('terminal')->name('personnel.')->group(function () {

    // Main Dashboard / Overview
    Route::get('/', [PersonnelController::class, 'index'])->name('index');
    Route::get('/labs', [PersonnelController::class, 'labs'])->name('labs');

    /**
     * Lab Monitoring & PC Grid
     * URL: /terminal/lab/{lab} | Route: personnel.lab.show
     */
    Route::get('/lab/{lab}', [PersonnelController::class, 'showLab'])->name('lab.show');

    /**
     * Session Management
     * Using POST for Assign and Release actions
     */
    Route::post('/assign/{computer}', [PersonnelController::class, 'assign'])->name('assign');
    Route::post('/release/{computer}', [PersonnelController::class, 'release'])->name('release');

    Route::get('/schedule-overview', [PersonnelController::class, 'fullSchedule'])->name('full-schedule');
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
    Route::get('/security', [SuperAdminController::class, 'security'])->name('security');
    Route::get('/analytics', [SuperAdminController::class, 'analytics'])->name('analytics');
    Route::get('/settings', [SuperAdminController::class, 'settings'])->name('settings');
    Route::get('/system-logs', [SuperAdminController::class, 'logs'])->name('logs');

    //USER MANAGEMENT ROUTES FOR SUPER ADMIN
    Route::get('/users', [SuperAdminController::class, 'userManagement'])->name('users');
    Route::post('/users', [SuperAdminController::class, 'storeUser'])->name('users.store');
    Route::patch('/users/{user}', [SuperAdminController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}', [SuperAdminController::class, 'destroyUser'])->name('users.destroy');
});
