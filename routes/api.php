<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

// API Controllers
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\TwoFactorApiController;
use App\Http\Controllers\Api\Dashboard\DashboardApiController;
use App\Http\Controllers\Api\Dashboard\LabApiController;
use App\Http\Controllers\Api\Dashboard\AlertApiController;
use App\Http\Controllers\Api\PersonnelApiController;
use App\Http\Controllers\Api\SessionApiController;
use App\Http\Controllers\Api\SuperAdminApiController;
use App\Http\Controllers\Api\TerminalController;

/*
|--------------------------------------------------------------------------
| 1. AUTHENTICATION & PROFILE ENDPOINTS
|--------------------------------------------------------------------------
*/

// Public Auth Endpoints
Route::post('/login', [AuthApiController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthApiController::class, 'logout']);
    Route::get('/me', [AuthApiController::class, 'user']);
});

// Mobile & 2FA Routes
Route::middleware('auth:sanctum')->prefix('two-factor')->group(function () {
    Route::post('/verify', [TwoFactorApiController::class, 'verify']);
    Route::post('/resend', [TwoFactorApiController::class, 'resend']);
});

// User Profile Management
Route::middleware('auth:sanctum')->prefix('user')->group(function () {
    Route::get('/profile', function (Request $request) {
        return response()->json(['success' => true, 'user' => $request->user()]);
    });

    Route::put('/profile-information', function (Request $request) {
        $user = $request->user();
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|max:255|unique:users,email,' . $user->id,
            'student_number' => 'nullable|string|unique:users,student_number,' . $user->id,
            'phone'          => 'nullable|string|max:20',
        ]);
        $user->update($validated);
        return response()->json(['success' => true, 'message' => 'Profile updated.', 'user' => $user->fresh()]);
    });

    Route::post('/profile-photo', function (Request $request) {
        $request->validate(['photo' => 'required|image|max:2048']);
        $user = $request->user();
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('profile-photos', 'public');
            $user->update(['profile_photo_path' => $path]);
        }
        return response()->json(['success' => true, 'profile_photo_url' => $user->profile_photo_url, 'user' => $user->fresh()]);
    });

    Route::put('/password', function (Request $request) {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', \Illuminate\Validation\Rules\Password::defaults(), 'confirmed'],
        ]);
        $user = $request->user();
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'The provided current password does not match.',
                'errors'  => ['current_password' => ['The provided current password does not match.']]
            ], 422);
        }
        $user->update(['password' => Hash::make($request->password)]);
        return response()->json(['success' => true, 'message' => 'Password updated successfully.']);
    });
});

/*
|--------------------------------------------------------------------------
| 2. HARDWARE TERMINAL KIOSK ENDPOINTS (Python Client)
|--------------------------------------------------------------------------
*/
Route::prefix('pc')->group(function () {
    Route::post('/login', [TerminalController::class, 'login']);
    Route::get('/status/{lab_id}/{pc_number}', [TerminalController::class, 'checkStatus']);
    Route::post('/logout', [TerminalController::class, 'handleLogout']);
    Route::post('/alerts', [TerminalController::class, 'reportIssue']);
    Route::post('/checklist', [TerminalController::class, 'storeChecklist']);
});

/*
|--------------------------------------------------------------------------
| 3. ADMIN DASHBOARD API ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->prefix('dashboard')->group(function () {
    // Overview
    Route::get('/', [DashboardApiController::class, 'index']);

    // User Management
    Route::get('/users', [DashboardApiController::class, 'userManagement']);
    Route::post('/users', [DashboardApiController::class, 'storeUser']);
    Route::put('/users/{user}', [DashboardApiController::class, 'updateUser']);
    Route::patch('/users/{user}', [DashboardApiController::class, 'updateUser']);
    Route::delete('/users/{user}', [DashboardApiController::class, 'destroyUser']);
    Route::post('/users/import', [DashboardApiController::class, 'import']);

    // Workstation Session Control
    Route::patch('/sessions/{session}/terminate', [DashboardApiController::class, 'terminateSession']);
    Route::post('/sessions/{session}/terminate', [DashboardApiController::class, 'terminateSession']);

    // Labs & Capacity Management
    Route::get('/labs', [LabApiController::class, 'index']);
    Route::post('/labs/store', [DashboardApiController::class, 'storeNewLaboratory']);
    Route::put('/labs/{lab}', [LabApiController::class, 'update']);

    // Lab Scheduling & Conflicts
    Route::get('/labs/{lab}/schedule', [LabApiController::class, 'viewSchedule']);
    Route::post('/labs/{lab}/schedule', [LabApiController::class, 'storeSchedule']);
    Route::post('/labs/{lab}/schedules', [LabApiController::class, 'storeSchedule']);
    Route::delete('/schedule/{schedule}', [LabApiController::class, 'destroySchedule']);
    Route::delete('/labs/{lab}/schedule/day', [LabApiController::class, 'destroyByDay']);
    Route::delete('/labs/{lab}/schedules-by-day', [LabApiController::class, 'destroyByDay']);
    Route::post('/labs/{lab}/schedule/check-conflict', [LabApiController::class, 'checkConflict']);
    Route::get('/labs/schedule/event/{schedule}/attendance', [LabApiController::class, 'exportEventAttendance']);

    // Incident Alerts
    Route::get('/alerts', [AlertApiController::class, 'index']);
    Route::patch('/alerts/{alert}/resolve', [AlertApiController::class, 'resolve']);
    Route::post('/alerts/{alert}/resolve', [AlertApiController::class, 'resolve']);
    Route::patch('/alerts/{alert}/undo', [AlertApiController::class, 'undoResolution']);
    Route::post('/alerts/{alert}/undo', [AlertApiController::class, 'undoResolution']);
    Route::patch('/alerts/{alert}/discard', [AlertApiController::class, 'discardAlert']);
    Route::post('/alerts/{alert}/discard', [AlertApiController::class, 'discardAlert']);

    // Archived Sessions
    Route::get('/sessions', [SessionApiController::class, 'index']);
});

/*
|--------------------------------------------------------------------------
| 4. PERSONNEL TERMINAL API ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->prefix('terminal')->group(function () {
    Route::get('/', [PersonnelApiController::class, 'index']);
    Route::get('/labs', [PersonnelApiController::class, 'labs']);
    Route::get('/lab/{lab}', [PersonnelApiController::class, 'showLab']);

    // Workstation Controls
    Route::post('/assign/{computer}', [PersonnelApiController::class, 'assign']);
    Route::post('/release/{computer}', [PersonnelApiController::class, 'release']);

    // Schedules & Enrollments
    Route::get('/schedule-overview', [PersonnelApiController::class, 'fullSchedule']);
    Route::get('/export/{schedule}', [PersonnelApiController::class, 'exportScheduleAttendance']);
    Route::post('/enroll-student', [PersonnelApiController::class, 'enrollStudent']);
    Route::delete('/unenroll/{enrollment}', [PersonnelApiController::class, 'unenrollStudent']);
    Route::delete('/clear-roster', [PersonnelApiController::class, 'clearRoster']);

    // Logs & Alerts
    Route::get('/sessions', [PersonnelApiController::class, 'sessionHistory']);
    Route::get('/alerts', [PersonnelApiController::class, 'alertHistory']);
    Route::patch('/alerts/{alert}/resolve', [AlertApiController::class, 'resolve']);
    Route::patch('/alerts/{alert}/undo', [AlertApiController::class, 'undoResolution']);
    Route::patch('/alerts/{id}/discard', [PersonnelApiController::class, 'discardAlert']);
    Route::post('/alerts/{id}/discard', [PersonnelApiController::class, 'discardAlert']);
});

/*
|--------------------------------------------------------------------------
| 5. SUPER ADMIN API ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->prefix('super-admin')->group(function () {
    Route::get('/overview', [SuperAdminApiController::class, 'index']);
    Route::get('/security', [SuperAdminApiController::class, 'security']);
    Route::get('/analytics', [SuperAdminApiController::class, 'analytics']);
    Route::get('/analytics/export', [SuperAdminApiController::class, 'exportReport']);
    Route::get('/export', [SuperAdminApiController::class, 'exportReport']);
    Route::get('/settings', [SuperAdminApiController::class, 'settings']);
    Route::get('/logs', [SuperAdminApiController::class, 'logs']);

    // User Management
    Route::get('/users', [SuperAdminApiController::class, 'userManagement']);
    Route::post('/users', [SuperAdminApiController::class, 'storeUser']);
    Route::put('/users/{user}', [SuperAdminApiController::class, 'updateUser']);
    Route::patch('/users/{user}', [SuperAdminApiController::class, 'updateUser']);
    Route::delete('/users/{user}', [SuperAdminApiController::class, 'destroyUser']);
    Route::post('/users/import', [SuperAdminApiController::class, 'import']);

    // Labs & Scheduling
    Route::get('/labs', [SuperAdminApiController::class, 'labs']);
    Route::get('/labs/{lab}/schedule', [SuperAdminApiController::class, 'viewSchedule']);
    Route::post('/labs/{lab}/schedule', [SuperAdminApiController::class, 'storeSchedule']);
    Route::delete('/schedule/{schedule}', [SuperAdminApiController::class, 'destroySchedule']);

    // Sessions & Alerts
    Route::get('/sessions', [SuperAdminApiController::class, 'sessions']);
    Route::get('/alerts', [AlertApiController::class, 'index']);
    Route::patch('/alerts/{alert}/resolve', [AlertApiController::class, 'resolve']);

    // System Maintenance
    Route::post('/reports/generate', [SuperAdminApiController::class, 'generateReport']);
    Route::post('/system/backup', [SuperAdminApiController::class, 'triggerBackup']);
    Route::post('/backup', [SuperAdminApiController::class, 'triggerBackup']);
    Route::post('/system/lockout', [SuperAdminApiController::class, 'lockout']);
    Route::post('/lockout', [SuperAdminApiController::class, 'lockout']);
    Route::post('/system/release-lockout', [SuperAdminApiController::class, 'releaseLockout']);
    Route::post('/release-lockout', [SuperAdminApiController::class, 'releaseLockout']);
});
