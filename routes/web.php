<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\LabController;
use App\Http\Controllers\Dashboard\AlertController;
use App\Http\Controllers\Dashboard\SettingsController;
use App\Http\Controllers\PersonnelController;
use App\Http\Controllers\Session;
use App\Http\Controllers\SuperAdminController;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\File;
/*
|--------------------------------------------------------------------------
| 1. PUBLIC & AUTHENTICATION ROUTES
|--------------------------------------------------------------------------
| Accessible by any visitor (unauthenticated guests).
*/

Route::get('/', function () {
    return view('auth.login');
});


/*
|--------------------------------------------------------------------------
| 2. SHARED AUTHENTICATED ROUTES
|--------------------------------------------------------------------------
| Requires user to be logged in, but DOES NOT require email verification yet.
| Handles profile updates, email verification notices, and resend requests.
*/
Route::middleware(['auth'])->group(function () {

    // PROFILE LANDING PAGE (ALOWS STUDENTS TO UPDATE SETTINGS OR FIX EMAIL)
    Route::get('/user/profile', function () {
        return view('profile.show');
    })->name('profile.show');

    // SUCCESS PAGE DISPLAYED AFTER CLICKING THE EMAIL VERIFICATION LINK
    Route::get('/registration-confirmed', function () {
        return view('auth.confirmed');
    })->name('registration.verified');

    // EMAIL VERIFICATION NOTICE VIEW
    Route::get('/email/verify', function () {
        return view('auth.verify-email');
    })->name('verification.notice');

    // RESEND VERIFICATION EMAIL NOTIFICATION (THROTTLED TO PREVENT SPAM)
    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('status', 'verification-link-sent');
    })->middleware(['throttle:6,1'])->name('verification.send');
});


/*
|--------------------------------------------------------------------------
| 3. EMAIL VERIFICATION & REAL-TIME POLLING
|--------------------------------------------------------------------------
| Handles incoming email verification link callbacks and live status checks.
*/

// DIRECT LINK HANDLER FROM VERIFICATION EMAIL
Route::get('/email/verify/{id}/{hash}', function ($id, $hash, Request $request) {
    $user = User::findOrFail($id);

    // VALIDATE HASH SECURITY
    if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
        abort(403, 'Invalid verification link.');
    }

    // MARK EMAIL AS VERIFIED IF NOT YET MARKED
    if (! $user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
        event(new Verified($user));
    }

    auth()->login($user);

    return redirect()->route('registration.verified');
})->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

// AUTO-POLLING ENDPOINT FOR CROSS-DEVICE REAL-TIME VERIFICATION REFRESH
Route::get('/api/check-verification-status', function () {
    if (auth()->check() && auth()->user()->hasVerifiedEmail()) {
        session()->flash('verified_toast', 'Your email is now verified and can be used to log in to the computer.');
        return response()->json(['verified' => true]);
    }

    return response()->json(['verified' => false]);
})->middleware(['auth']);


/*
|--------------------------------------------------------------------------
| 4. ADMIN DASHBOARD ROUTES
|--------------------------------------------------------------------------
| Requires Authentication, Verified Email, and Admin Role Clearance.
*/
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'clearance:admin',
])->prefix('dashboard')->name('dashboard.')->group(function () {

    // DASHBOARD OVERVIEW & SYSTEM SETTINGS
    Route::get('/', [DashboardController::class, 'index'])->name('index');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');

    // USER MANAGEMENT
    Route::get('/users', [DashboardController::class, 'userManagement'])->name('users');
    Route::post('/users', [DashboardController::class, 'storeUser'])->name('users.store');
    Route::patch('/users/{user}', [DashboardController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}', [DashboardController::class, 'destroyUser'])->name('users.destroy');
    Route::post('/users/import', [DashboardController::class, 'import'])->name('users.import');

    // WORKSTATION SESSION CONTROL
    Route::patch('/sessions/{session}/terminate', [DashboardController::class, 'terminateSession'])->name('sessions.terminate');

    // LABORATORY & HARDWARE MANAGEMENT
    Route::get('/labs', [LabController::class, 'index'])->name('labs');
    Route::post('/labs/store', [DashboardController::class, 'storeNewLaboratory'])->name('labs.store');
    Route::put('/labs/{lab}', [LabController::class, 'update'])->name('labs.update');

    // LABORATORY SCHEDULING
    Route::get('/labs/{lab}/schedule', [LabController::class, 'viewSchedule'])->name('labs.schedule');
    Route::post('/labs/{lab}/schedule', [LabController::class, 'storeSchedule'])->name('labs.schedule.store');
    Route::delete('/schedule/{schedule}', [LabController::class, 'destroySchedule'])->name('labs.schedule.destroy');
    Route::delete('/labs/{lab}/schedule/day', [LabController::class, 'destroyByDay'])->name('labs.schedule.destroyByDay');

    // INCIDENT ALERTS & NOTIFICATIONS
    Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');
    Route::patch('/alerts/{alert}/resolve', [AlertController::class, 'resolve'])->name('alerts.resolve');
    Route::patch('/alerts/{alert}/undo', [AlertController::class, 'undoResolution'])->name('alerts.undo');

    // HISTORICAL SESSIONS & AUDITING
    Route::get('/sessions', [Session::class, 'index'])->name('sessions.index');
    Route::get('/sessions/student/{student}', [Session::class, 'show'])->name('sessions.student');
});


/*
|--------------------------------------------------------------------------
| 5. PERSONNEL TERMINAL ROUTES
|--------------------------------------------------------------------------
| Requires Authentication, Verified Email, and Personnel Role Clearance.
*/
Route::middleware([
    'auth',
    'verified',
    'clearance:personnel',
])->prefix('terminal')->name('personnel.')->group(function () {

    // MAIN TERMINAL OVERVIEW & LAB GRID
    Route::get('/', [PersonnelController::class, 'index'])->name('index');
    Route::get('/labs', [PersonnelController::class, 'labs'])->name('labs');
    Route::get('/lab/{lab}', [PersonnelController::class, 'showLab'])->name('lab.show');

    // WORKSTATION ASSIGNMENT & RELEASE CONTROLS
    Route::post('/assign/{computer}', [PersonnelController::class, 'assign'])->name('assign');
    Route::post('/release/{computer}', [PersonnelController::class, 'release'])->name('release');

    // SCHEDULES & REPORT EXPORTS
    Route::get('/schedule-overview', [PersonnelController::class, 'fullSchedule'])->name('full-schedule');
    Route::get('/export/{schedule}', [PersonnelController::class, 'exportScheduleAttendance'])->name('export');

    // LOGS & ALERTS
    Route::get('/sessions', [PersonnelController::class, 'sessionHistory'])->name('sessions');
    Route::get('/alerts', [PersonnelController::class, 'alertHistory'])->name('alerts');
    Route::patch('/alerts/{alert}/resolve', [AlertController::class, 'resolve'])->name('alerts.resolve');
    Route::patch('/alerts/{alert}/undo', [AlertController::class, 'undoResolution'])->name('alerts.undo');
});


/*
|--------------------------------------------------------------------------
| 6. SUPER ADMIN ROUTES
|--------------------------------------------------------------------------
| Requires Authentication, Verified Email, and Super Admin Clearance.
*/
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'clearance:super-admin',
])->prefix('super-admin')->name('super-admin.')->group(function () {

    // HIGH-LEVEL SYSTEM OVERVIEW & ANALYTICS
    Route::get('/overview', [SuperAdminController::class, 'index'])->name('index');
    Route::get('/security', [SuperAdminController::class, 'security'])->name('security');
    Route::get('/analytics', [SuperAdminController::class, 'analytics'])->name('analytics');
    Route::get('/analytics/export', [SuperAdminController::class, 'exportReport'])->name('analytics.export');
    Route::get('/settings', [SuperAdminController::class, 'settings'])->name('settings');
    Route::get('/logs', [SuperAdminController::class, 'logs'])->name('logs');

    // SYSTEM-WIDE USER MANAGEMENT
    Route::get('/users', [SuperAdminController::class, 'userManagement'])->name('users');
    Route::post('/users', [SuperAdminController::class, 'storeUser'])->name('users.store');
    Route::patch('/users/{user}', [SuperAdminController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}', [SuperAdminController::class, 'destroyUser'])->name('users.destroy');
    Route::post('/users/import', [SuperAdminController::class, 'import'])->name('users.import');


    // GLOBAL LAB INVENTORY & SCHEDULING
    Route::get('/labs', [SuperAdminController::class, 'labs'])->name('labs');
    Route::get('/labs/{lab}', [SuperAdminController::class, 'show'])->name('labs.show');
    Route::get('/labs/{lab}/schedule', [SuperAdminController::class, 'viewSchedule'])->name('labs.schedule');
    Route::post('/labs/{lab}/schedule', [SuperAdminController::class, 'storeSchedule'])->name('labs.schedule.store');
    Route::delete('/schedule/{schedule}', [SuperAdminController::class, 'destroySchedule'])->name('labs.schedule.destroy');

    // GLOBAL SESSIONS & SYSTEM ALERTS
    Route::get('/sessions', [SuperAdminController::class, 'sessions'])->name('sessions');
    Route::get('/alerts', [AlertController::class, 'index'])->name('alerts');
    Route::patch('/alerts/{alert}/resolve', [AlertController::class, 'resolve'])->name('alerts.resolve');

    // EMERGENCY CONTROLS & SYSTEM MAINTENANCE
    Route::post('/reports/generate', [SuperAdminController::class, 'generateReport'])->name('reports.generate');
    Route::post('/system/backup', [SuperAdminController::class, 'triggerBackup'])->name('system.backup');
    Route::post('/system/lockout', [SuperAdminController::class, 'lockout'])->name('system.lockout');
    Route::post('/system/release-lockout', [SuperAdminController::class, 'releaseLockout'])->name('system.release-lockout');
});


/*
|--------------------------------------------------------------------------
| 7. INSTALLER DOWNLOAD ROUTES
|--------------------------------------------------------------------------
| 
*/


Route::get('/download/client-setup', function () {
    $filePath = base_path('LabGuard_Installer/Output/LabGuard_Client_Setup.exe');

    if (!file_exists($filePath)) {
        return back()->with('error', 'LabGuard Client Setup file not found on server.');
    }

    return response()->download($filePath, 'LabGuard_Client_Setup.exe', [
        'Content-Type' => 'application/octet-stream',
    ]);
})->middleware(['auth'])->name('downloads.setup');
