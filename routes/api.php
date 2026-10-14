<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;

// API Controllers
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\TwoFactorApiController;
use App\Http\Controllers\Api\TerminalController;

// Fortify Actions for 2FA
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\TwoFactorAuthenticationProvider;
/*
|--------------------------------------------------------------------------
| 1. PUBLIC AUTHENTICATION ENDPOINTS
|--------------------------------------------------------------------------
*/

// Public 2FA Login Challenge (No token needed yet)
Route::post('/two-factor/challenge', function (Request $request) {
    $request->validate([
        'user_id'       => 'required',
        'code'          => 'nullable|string',
        'recovery_code' => 'nullable|string',
    ]);

    $user = User::findOrFail($request->user_id);

    // 1. Verify via Recovery Code
    if ($request->filled('recovery_code')) {
        $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);

        if (! in_array($request->recovery_code, $recoveryCodes)) {
            return response()->json(['message' => 'The provided recovery code was invalid.'], 422);
        }

        // Burn the used recovery code
        $user->forceFill([
            'two_factor_recovery_codes' => encrypt(json_encode(array_values(array_diff($recoveryCodes, [$request->recovery_code])))),
        ])->save();
    }
    // 2. Verify via 6-digit Authenticator Code (Google Authenticator)
    elseif ($request->filled('code')) {
        $provider = app(TwoFactorAuthenticationProvider::class);

        if (! $provider->verify(decrypt($user->two_factor_secret), $request->code)) {
            return response()->json(['message' => 'The provided two-factor authentication code was invalid.'], 422);
        }
    } else {
        return response()->json(['message' => 'Please provide an authentication code.'], 422);
    }

    // Code is valid! Issue the official Sanctum Token now:
    return response()->json([
        'success' => true,
        'message' => 'Two-factor authentication successful.',
        'token'   => $user->createToken('mobile-app')->plainTextToken,
        'user'    => $user,
    ]);
});

// Login
Route::post('/login', [AuthApiController::class, 'login']);

// Student Registration
Route::post('/register', function (Request $request) {
    $validated = $request->validate([
        'name'           => 'required|string|max:255',
        'email'          => 'required|string|email|max:255|unique:users',
        'student_number' => 'nullable|string|unique:users,student_number',
        'password'       => ['required', 'string', \Illuminate\Validation\Rules\Password::defaults()],
        'terms'          => 'accepted',
    ]);

    $user = User::create([
        'name'           => $validated['name'],
        'email'          => $validated['email'],
        'student_number' => $validated['student_number'] ?? null,
        'password'       => Hash::make($validated['password']),
        'role'           => 'student',
    ]);

    // Send email verification notification
    if (method_exists($user, 'sendEmailVerificationNotification')) {
        $user->sendEmailVerificationNotification();
    }

    return response()->json([
        'success' => true,
        'message' => 'Registration successful. Please verify your email.',
        'token'   => $user->createToken('mobile-app')->plainTextToken,
        'user'    => $user,
        'verified' => false,
    ], 201);
});

/*
|--------------------------------------------------------------------------
| 2. PROTECTED USER & PROFILE ENDPOINTS (Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Auth & Identity Checks
    Route::post('/logout', [AuthApiController::class, 'logout']);
    Route::get('/me', [AuthApiController::class, 'user']);

    // Direct /user alias (to prevent 404s from any client)
    Route::get('/user', function (Request $request) {
        return response()->json([
            'success' => true,
            'user'    => $request->user(),
            'has_verified_email' => $request->user()->hasVerifiedEmail(),
        ]);
    });

    // Email Verification Resend
    Route::post('/email/verification-notification', function (Request $request) {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.']);
        }

        $request->user()->sendEmailVerificationNotification();
        return response()->json(['success' => true, 'message' => 'Verification link sent!']);
    });

    /*
    |----------------------------------------------------------------------
    | User Profile Management (/api/user/...)
    |----------------------------------------------------------------------
    */
    Route::prefix('user')->group(function () {

        // Get Profile
        Route::get('/profile', function (Request $request) {
            return response()->json([
                'success' => true,
                'user'    => $request->user(),
                'has_verified_email' => $request->user()->hasVerifiedEmail(),
            ]);
        });

        // Update Name, Email, Student Number, Phone
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
                'message' => 'Profile updated successfully.',
                'user'    => $user->fresh()
            ]);
        });

        // Update Profile Picture
        Route::post('/profile-photo', function (Request $request) {
            $request->validate(['photo' => 'required|image|max:2048']);
            $user = $request->user();

            if ($request->hasFile('photo')) {
                $path = $request->file('photo')->store('profile-photos', 'public');
                $user->update(['profile_photo_path' => $path]);
            }

            return response()->json([
                'success'           => true,
                'profile_photo_url' => $user->profile_photo_url,
                'user'              => $user->fresh()
            ]);
        });

        // Update Password
        Route::put('/password', function (Request $request) {
            $request->validate([
                'current_password' => ['required', 'string'],
                'password'         => ['required', 'string', \Illuminate\Validation\Rules\Password::defaults(), 'confirmed'],
            ]);

            $user = $request->user();

            if (! Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The provided current password does not match.',
                    'errors'  => ['current_password' => ['The provided current password does not match.']]
                ], 422);
            }

            $user->update(['password' => Hash::make($request->password)]);

            return response()->json([
                'success' => true,
                'message' => 'Password updated successfully.'
            ]);
        });

        /*
        |------------------------------------------------------------------
        | Browser Sessions
        |------------------------------------------------------------------
        */
        Route::get('/browser-sessions', function (Request $request) {
            $sessions = DB::table('sessions')
                ->where('user_id', $request->user()->id)
                ->orderBy('last_activity', 'desc')
                ->get(['id', 'ip_address', 'user_agent', 'last_activity']);

            return response()->json($sessions);
        });

        Route::post('/other-browser-sessions', function (Request $request) {
            $request->validate(['password' => 'required|string']);

            if (! Hash::check($request->password, $request->user()->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The provided password does not match our records.'
                ], 422);
            }

            // Invalidate other browser sessions in the database
            DB::table('sessions')
                ->where('user_id', $request->user()->id)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Other browser sessions logged out.'
            ]);
        });

        /*
        |------------------------------------------------------------------
        | Two-Factor Authentication (Fortify Engine)
        |------------------------------------------------------------------
        */
        Route::post('/two-factor-authentication', function (Request $request) {
            $enable2fa = app(EnableTwoFactorAuthentication::class);
            $enable2fa($request->user());

            return response()->json(['success' => true]);
        });

        Route::get('/two-factor-qr-code', function (Request $request) {
            return response()->json([
                'svg'      => $request->user()->twoFactorQrCodeSvg(),
                'setupKey' => decrypt($request->user()->two_factor_secret),
            ]);
        });

        Route::post('/confirmed-two-factor-authentication', function (Request $request) {
            $confirm2fa = app(ConfirmTwoFactorAuthentication::class);
            $confirm2fa($request->user(), $request->code);

            return response()->json(['success' => true]);
        });

        Route::get('/two-factor-recovery-codes', function (Request $request) {
            return response()->json(json_decode(decrypt($request->user()->two_factor_recovery_codes), true));
        });

        Route::post('/two-factor-recovery-codes', function (Request $request) {
            $generate = app(GenerateNewRecoveryCodes::class);
            $generate($request->user());

            return response()->json(json_decode(decrypt($request->user()->two_factor_recovery_codes), true));
        });

        Route::delete('/two-factor-authentication', function (Request $request) {
            $disable2fa = app(DisableTwoFactorAuthentication::class);
            $disable2fa($request->user());

            return response()->json([
                'success' => true,
                'message' => 'Two-factor authentication disabled.'
            ]);
        });

        /*
        |------------------------------------------------------------------
        | Account Decommission (Self-Deletion)
        |------------------------------------------------------------------
        */
        Route::delete('/account', function (Request $request) {
            $request->validate(['password' => 'required|string']);

            if (! Hash::check($request->password, $request->user()->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The provided password does not match our records.'
                ], 422);
            }

            $user = $request->user();
            $user->tokens()->delete(); // Revoke all mobile tokens
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Account successfully decommissioned.'
            ]);
        });
    });

    /*
    |----------------------------------------------------------------------
    | Mobile & 2FA Custom Verification Routes
    |----------------------------------------------------------------------
    */
    Route::prefix('two-factor')->group(function () {
        Route::post('/verify', [TwoFactorApiController::class, 'verify']);
        Route::post('/resend', [TwoFactorApiController::class, 'resend']);
    });
});

/*
|--------------------------------------------------------------------------
| 3. HARDWARE TERMINAL KIOSK ENDPOINTS (Python Client)
|--------------------------------------------------------------------------
*/
Route::prefix('pc')->group(function () {
    Route::post('/login', [TerminalController::class, 'login']);
    Route::get('/status/{lab_id}/{pc_number}', [TerminalController::class, 'checkStatus']);
    Route::post('/logout', [TerminalController::class, 'handleLogout']);
    Route::post('/alerts', [TerminalController::class, 'reportIssue']);
    Route::post('/checklist', [TerminalController::class, 'storeChecklist']);
});
