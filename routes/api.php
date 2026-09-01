<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;

// API Controllers
use App\Http\Controllers\Api\Dashboard\DashboardController;
use App\Http\Controllers\Api\Dashboard\LabController;
use App\Http\Controllers\Api\Dashboard\AlertController;
use App\Http\Controllers\Api\PersonnelController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\SuperAdminController;
use App\Http\Controllers\Api\TerminalController;
use App\Http\Controllers\Api\TwoFactorController;
use App\Models\User;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| All routes in this file are automatically prefixed with /api
| Example: Route::get('/dashboard') becomes https://your-domain.com/api/dashboard
*/


/*
|--------------------------------------------------------------------------
| 1. AUTHENTICATION & USER PROFILE ENDPOINTS
|--------------------------------------------------------------------------
| Mobile authentication endpoints for logging in, getting profile, 2FA, and logout.
*/


/*
|--------------------------------------------------------------------------
| USER PROFILE, 2FA, PHOTO, & SESSIONS API ENDPOINTS
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->prefix('user')->group(function () {

    // 1. Get Current Profile Data
    Route::get('/profile', function (Request $request) {
        return response()->json([
            'success' => true,
            'user'    => $request->user(),
        ]);
    });

    // 2. Update Profile Information (Name, Email, Student Number, Phone)
    Route::put('/profile-information', function (Request $request) {
        $user = $request->user();

        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|max:255|unique:users,email,' . $user->id,
            'student_number' => 'nullable|string|unique:users,student_number,' . $user->id,
            'phone'          => 'nullable|string|max:20',
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profile information updated successfully.',
            'user'    => $user->fresh(),
        ]);
    });

    // 3. Upload Profile Photo
    Route::post('/profile-photo', function (Request $request) {
        $request->validate([
            'photo' => 'required|image|max:2048', // 2MB Max
        ]);

        $user = $request->user();

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('profile-photos', 'public');
            $user->update(['profile_photo_path' => $path]);
        }

        return response()->json([
            'success'           => true,
            'message'           => 'Profile photo uploaded successfully.',
            'profile_photo_url' => $user->profile_photo_url,
            'user'              => $user->fresh(),
        ]);
    });

    // 4. Update Password
    Route::put('/password', function (Request $request) {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', \Illuminate\Validation\Rules\Password::defaults(), 'confirmed'],
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'The provided current password does not match our records.',
                'errors'  => ['current_password' => ['The provided current password does not match.']]
            ], 422);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully.',
        ]);
    });

    // 5. Two-Factor Authentication (Enable / Disable)
    Route::post('/two-factor-authentication', function (Request $request) {
        $user = $request->user();

        if (method_exists($user, 'enableTwoFactorAuthentication')) {
            app(\Laravel\Fortify\Actions\EnableTwoFactorAuthentication::class)($user);
        }

        return response()->json([
            'success'                => true,
            'message'                => 'Two-factor authentication enabled.',
            'two_factor_qr_code_svg' => method_exists($user, 'twoFactorQrCodeSvg') ? $user->twoFactorQrCodeSvg() : null,
            'two_factor_recovery_codes' => method_exists($user, 'recoveryCodes') ? $user->recoveryCodes() : [],
        ]);
    });

    Route::delete('/two-factor-authentication', function (Request $request) {
        $user = $request->user();

        if (method_exists($user, 'disableTwoFactorAuthentication')) {
            app(\Laravel\Fortify\Actions\DisableTwoFactorAuthentication::class)($user);
        }

        return response()->json([
            'success' => true,
            'message' => 'Two-factor authentication disabled.',
        ]);
    });

    // 6. View Active Browser Sessions
    Route::get('/browser-sessions', function (Request $request) {
        $user = $request->user();

        $sessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderBy('last_activity', 'desc')
            ->get()
            ->map(function ($s) {
                return [
                    'id'          => $s->id,
                    'ip_address'  => $s->ip_address,
                    'user_agent'  => $s->user_agent,
                    'last_active' => \Carbon\Carbon::createFromTimestamp($s->last_activity)->diffForHumans(),
                ];
            });

        return response()->json([
            'success'  => true,
            'sessions' => $sessions,
        ]);
    });

    // 7. Logout Other Browser Sessions
    Route::delete('/other-browser-sessions', function (Request $request) {
        $request->validate(['password' => 'required|string']);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password mismatch.',
                'errors'  => ['password' => ['The provided password does not match.']]
            ], 422);
        }

        DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', session()->getId())
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out of other browser sessions.',
        ]);
    });
});
/*
|--------------------------------------------------------------------------
| 1. AUTHENTICATION & USER PROFILE ENDPOINTS
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {

    // MOBILE / POSTMAN LOGIN ENDPOINT (Returns Sanctum Token)
    Route::post('/login', function (Request $request) {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.',
            ], 401);
        }

        // Generate Sanctum API token for mobile app / Postman
        $token = $user->createToken('mobile-app-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'token'   => $token,
            'user'    => $user,
        ], 200);
    });

    // Return current authenticated user profile
    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return response()->json([
            'success' => true,
            'user' => $request->user(),
        ]);
    });

    // Mobile 2FA Verification Routes
    Route::middleware('auth:sanctum')->prefix('two-factor')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\TwoFactorController::class, 'index']);
        Route::post('/verify', [\App\Http\Controllers\Api\TwoFactorController::class, 'store']);
        Route::post('/resend', [\App\Http\Controllers\Api\TwoFactorController::class, 'resend']);
    });
});


Route::prefix('auth')->group(function () {
    // Return current authenticated user profile
    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return response()->json([
            'success' => true,
            'user' => $request->user(),
        ]);
    });

    // Mobile 2FA Verification Routes
    Route::middleware('auth:sanctum')->prefix('two-factor')->group(function () {
        Route::get('/', [TwoFactorController::class, 'index']);
        Route::post('/verify', [TwoFactorController::class, 'store']);
        Route::post('/resend', [TwoFactorController::class, 'resend']);
    });
});


/*
|--------------------------------------------------------------------------
| 2. EMAIL VERIFICATION & STATUS CHECKS
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Real-time Email Verification Status Check
    Route::get('/check-verification-status', function (Request $request) {
        return response()->json([
            'verified' => $request->user()->hasVerifiedEmail(),
        ]);
    });

    // Resend Email Verification Notification
    Route::post('/email/verification-notification', function (Request $request) {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json([
                'success' => true,
                'message' => 'Email is already verified.',
            ]);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json([
            'success' => true,
            'message' => 'Verification link sent to your email.',
        ]);
    })->middleware('throttle:6,1');
});


/*
|--------------------------------------------------------------------------
| 3. PYTHON TERMINAL KIOSK ENDPOINTS
|--------------------------------------------------------------------------
| Used by the Python Client App running on laboratory PCs.
*/
Route::prefix('pc')->group(function () {
    Route::post('/login', [TerminalController::class, 'login']);
    Route::get('/status/{lab_id}/{pc_number}', [TerminalController::class, 'checkStatus']);
    Route::post('/logout', [TerminalController::class, 'handleLogout']);
    Route::post('/alerts', [TerminalController::class, 'reportIssue']);
});


/*
|--------------------------------------------------------------------------
| 4. ADMIN DASHBOARD API ROUTES
|--------------------------------------------------------------------------
| Requires Sanctum Auth, Verified Email, and Admin Clearance.
*/
Route::middleware([
    'auth:sanctum',
    'verified',
    'clearance:admin',
])->prefix('dashboard')->group(function () {

    // Dashboard Overview & Settings
    Route::get('/', [DashboardController::class, 'index']);


    // User Management
    Route::get('/users', [DashboardController::class, 'userManagement']);
    Route::post('/users', [DashboardController::class, 'storeUser']);
    Route::put('/users/{user}', [DashboardController::class, 'updateUser']);
    Route::patch('/users/{user}', [DashboardController::class, 'updateUser']);
    Route::delete('/users/{user}', [DashboardController::class, 'destroyUser']);
    Route::post('/users/import', [DashboardController::class, 'import']);

    // Workstation Session Control
    Route::patch('/sessions/{session}/terminate', [DashboardController::class, 'terminateSession']);

    // Laboratory Management
    Route::get('/labs', [LabController::class, 'index']);
    Route::post('/labs/store', [DashboardController::class, 'storeNewLaboratory']);
    Route::put('/labs/{lab}', [LabController::class, 'update']);

    // Laboratory Scheduling
    Route::get('/labs/{lab}/schedule', [LabController::class, 'viewSchedule']);
    Route::post('/labs/{lab}/schedule', [LabController::class, 'storeSchedule']);
    Route::delete('/schedule/{schedule}', [LabController::class, 'destroySchedule']);
    Route::delete('/labs/{lab}/schedule/day', [LabController::class, 'destroyByDay']);

    // Incident Alerts
    Route::get('/alerts', [AlertController::class, 'index']);
    Route::patch('/alerts/{alert}/resolve', [AlertController::class, 'resolve']);
    Route::patch('/alerts/{alert}/undo', [AlertController::class, 'undoResolution']);

    // Historical Sessions & Auditing
    Route::get('/sessions', [SessionController::class, 'index']);
    Route::get('/sessions/student/{student}', [SessionController::class, 'show']);
});


/*
|--------------------------------------------------------------------------
| 5. PERSONNEL TERMINAL API ROUTES
|--------------------------------------------------------------------------
| Requires Sanctum Auth, Verified Email, and Personnel Clearance.
*/
Route::middleware([
    'auth:sanctum',
    'verified',
    'clearance:personnel',
])->prefix('terminal')->group(function () {

    // Terminal Overview & Lab Grid
    Route::get('/', [PersonnelController::class, 'index']);
    Route::get('/labs', [PersonnelController::class, 'labs']);
    Route::get('/lab/{lab}', [PersonnelController::class, 'showLab']);

    // Workstation Assignment & Release
    Route::post('/assign/{computer}', [PersonnelController::class, 'assign']);
    Route::post('/release/{computer}', [PersonnelController::class, 'release']);

    // Schedules & Reports
    Route::get('/schedule-overview', [PersonnelController::class, 'fullSchedule']);
    Route::get('/export/{schedule}', [PersonnelController::class, 'exportScheduleAttendance']);

    // Logs & Alerts
    Route::get('/sessions', [PersonnelController::class, 'sessionHistory']);
    Route::get('/alerts', [PersonnelController::class, 'alertHistory']);
    Route::patch('/alerts/{alert}/resolve', [AlertController::class, 'resolve']);
    Route::patch('/alerts/{alert}/undo', [AlertController::class, 'undoResolution']);
});


/*
|--------------------------------------------------------------------------
| 6. SUPER ADMIN API ROUTES
|--------------------------------------------------------------------------
| Requires Sanctum Auth, Verified Email, and Super Admin Clearance.
*/
Route::middleware([
    'auth:sanctum',
    'verified',
    'clearance:super-admin',
])->prefix('super-admin')->group(function () {

    // High-Level System Overview & Analytics
    Route::get('/overview', [SuperAdminController::class, 'index']);
    Route::get('/security', [SuperAdminController::class, 'security']);
    Route::get('/analytics', [SuperAdminController::class, 'analytics']);
    Route::get('/analytics/export', [SuperAdminController::class, 'exportReport']);
    Route::get('/settings', [SuperAdminController::class, 'settings']);
    Route::get('/logs', [SuperAdminController::class, 'logs']);

    // System-Wide User Management
    Route::get('/users', [SuperAdminController::class, 'userManagement']);
    Route::post('/users', [SuperAdminController::class, 'storeUser']);
    Route::put('/users/{user}', [SuperAdminController::class, 'updateUser']);
    Route::patch('/users/{user}', [SuperAdminController::class, 'updateUser']);
    Route::delete('/users/{user}', [SuperAdminController::class, 'destroyUser']);
    Route::post('/users/import', [SuperAdminController::class, 'import']);

    // Global Lab Inventory & Scheduling
    Route::get('/labs', [SuperAdminController::class, 'labs']);
    Route::get('/labs/{lab}', [SuperAdminController::class, 'show']);
    Route::get('/labs/{lab}/schedule', [SuperAdminController::class, 'viewSchedule']);
    Route::post('/labs/{lab}/schedule', [SuperAdminController::class, 'storeSchedule']);
    Route::delete('/schedule/{schedule}', [SuperAdminController::class, 'destroySchedule']);

    // Global Sessions & System Alerts
    Route::get('/sessions', [SuperAdminController::class, 'sessions']);
    Route::get('/alerts', [AlertController::class, 'index']);
    Route::patch('/alerts/{alert}/resolve', [AlertController::class, 'resolve']);

    // Emergency Controls & System Maintenance
    Route::post('/reports/generate', [SuperAdminController::class, 'generateReport']);
    Route::post('/system/backup', [SuperAdminController::class, 'triggerBackup']);
    Route::post('/system/lockout', [SuperAdminController::class, 'lockout']);
    Route::post('/system/release-lockout', [SuperAdminController::class, 'releaseLockout']);
});
