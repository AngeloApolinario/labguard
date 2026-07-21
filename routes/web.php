<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\LabController;
use App\Http\Controllers\Dashboard\AlertController;
use App\Http\Controllers\Dashboard\SettingsController;
use App\Http\Controllers\PersonnelController;
use App\Http\Controllers\Session;
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
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');

    // --- User Management ---
    Route::get('/users', [DashboardController::class, 'userManagement'])->name('users');
    Route::post('/users', [DashboardController::class, 'storeUser'])->name('users.store');
    Route::patch('/users/{user}', [DashboardController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}', [DashboardController::class, 'destroyUser'])->name('users.destroy');

    // --- Session Control ---
    Route::patch('/sessions/{session}/terminate', [DashboardController::class, 'terminateSession'])->name('sessions.terminate');

    // --- Laboratory & Hardware Management ---
    Route::get('/labs', [LabController::class, 'index'])->name('labs');
    Route::put('/labs/{lab}', [LabController::class, 'update'])->name('labs.update');

    // --- Laboratory Scheduling ---
    Route::get('/labs/{lab}/schedule', [LabController::class, 'viewSchedule'])->name('labs.schedule');
    Route::post('/labs/{lab}/schedule', [LabController::class, 'storeSchedule'])->name('labs.schedule.store');
    Route::delete('/schedule/{schedule}', [LabController::class, 'destroySchedule'])->name('labs.schedule.destroy');

    // --- Terminal Incident Alerts (Fixed Duplicates) ---
    Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');
    Route::patch('/alerts/{alert}/resolve', [AlertController::class, 'resolve'])->name('alerts.resolve');

    // SESSION HISTORY 
    Route::get('/sessions', [Session::class, 'index'])->name('sessions.index');
    Route::get('/sessions/student/{student}', [Session::class, 'show'])->name('sessions.student');

    // NEW LABORATORY CREATION ROUTES
    Route::post('/labs/store', [DashboardController::class, 'storeNewLaboratory'])->name('labs.store');
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


    Route::get('/sessions', [PersonnelController::class, 'sessionHistory'])->name('sessions');
    Route::get('/alerts', [PersonnelController::class, 'alertHistory'])->name('alerts');
    Route::patch('/alerts/{alert}/resolve', [AlertController::class, 'resolve'])->name('alerts.resolve');

    //EXPORT ROUTES
    Route::get('/export/{schedule}', [PersonnelController::class, 'exportScheduleAttendance'])
        ->name('export');
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

    // Labs Inventory
    Route::get('/labs', [SuperAdminController::class, 'labs'])->name('labs');

    // Scheduling (Full Management)
    Route::get('/labs/{lab}/schedule', [SuperAdminController::class, 'viewSchedule'])->name('labs.schedule');
    Route::post('/labs/{lab}/schedule', [SuperAdminController::class, 'storeSchedule'])->name('labs.schedule.store');
    Route::delete('/schedule/{schedule}', [SuperAdminController::class, 'destroySchedule'])->name('labs.schedule.destroy');
    Route::get('/labs/{lab}', [SuperAdminController::class, 'show'])->name('labs.show');

    // Other Global Monitoring
    Route::get('/sessions', [SuperAdminController::class, 'sessions'])->name('sessions');
    Route::get('/alerts', [SuperAdminController::class, 'alerts'])->name('alerts');

    //ALERTS FOR SUPER ADMIN
    Route::get('/alerts', [AlertController::class, 'index'])->name('alerts');
    Route::patch('/alerts/{alert}/resolve', [AlertController::class, 'resolve'])->name('alerts.resolve');
    Route::get('/analytics/export', [SuperAdminController::class, 'exportReport'])->name('analytics.export');

    //ROUTES FOR THE SUPER ADMIN OVERVIEW 
    Route::get('/logs', [SuperAdminController::class, 'logs'])->name('logs');

    // Super Admin System Utilities
    Route::post('/reports/generate', [SuperAdminController::class, 'generateReport'])->name('reports.generate');
    Route::post('/system/backup', [SuperAdminController::class, 'triggerBackup'])->name('system.backup');
    Route::post('/system/lockout', [SuperAdminController::class, 'emergencyLockout'])->name('system.lockout');
});
