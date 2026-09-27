<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class AuthApiController extends Controller
{
    /**
     * Handle API login with Turnstile and 2FA support.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'                 => ['required', 'string', 'email'],
            'password'              => ['required', 'string'],
            'cf-turnstile-response' => ['nullable', 'string'], // Optional if you decide to exempt native app
        ]);

        // 1. Verify Turnstile with Cloudflare (if configured & token provided)
        $turnstileSecret = config('services.turnstile.secret_key', env('TURNSTILE_SECRET_KEY'));

        if ($turnstileSecret && $request->filled('cf-turnstile-response')) {
            $turnstileResponse = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret'   => $turnstileSecret,
                'response' => $request->input('cf-turnstile-response'),
                'remoteip' => $request->ip(),
            ]);

            if (!$turnstileResponse->json('success')) {
                return response()->json([
                    'message' => 'Security check failed. Please refresh and try again.',
                    'errors'  => [
                        'cf-turnstile-response' => ['CAPTCHA verification failed. Please try again.'],
                    ],
                ], 422);
            }
        }

        // 2. Validate Credentials
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => trans('auth.failed'),
                'errors'  => [
                    'email' => [trans('auth.failed')],
                ],
            ], 422);
        }

        // 3. Check if the user ACTUALLY has Two-Factor Authentication enabled
        $isTwoFactorActive = false;

        // If using Jetstream / Fortify:
        if (!empty($user->two_factor_secret) && !is_null($user->two_factor_confirmed_at)) {
            $isTwoFactorActive = true;
        }

        // Or if using a custom boolean column (e.g., $user->two_factor_enabled):
        if (!empty($user->two_factor_enabled)) {
            $isTwoFactorActive = true;
        }

        if ($isTwoFactorActive) {
            if (method_exists($user, 'generateTwoFactorCode')) {
                $user->generateTwoFactorCode();
            }

            $tempToken = $user->createToken('2fa-pending', ['verify-2fa'])->plainTextToken;

            return response()->json([
                'requires_2fa' => true,
                'message'      => 'Two-factor authentication code required.',
                'temp_token'   => $tempToken,
                'user'         => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role,
                ],
            ]);
        }

        // 4. Standard Direct Login: Issue Full Sanctum Token
        $token = $user->createToken('labguard-native-session')->plainTextToken;

        return response()->json([
            'requires_2fa' => false,
            'message'      => 'Login successful.',
            'token'        => $token,
            'user'         => [
                'id'             => $user->id,
                'name'           => $user->name,
                'email'          => $user->email,
                'role'           => $user->role,
                'student_number' => $user->student_number ?? null,
            ],
        ]);
    }

    /**
     * Revoke tokens on logout.
     */
    public function logout(Request $request): JsonResponse
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * Fetch authenticated user details.
     */
    public function user(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }
}
