<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TwoFactorApiController extends Controller
{
    /**
     * Verify the 2FA verification code.
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'two_factor_code' => ['required'],
        ]);

        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Validate code match and expiration (if implemented in your User model)
        if ($request->two_factor_code == $user->two_factor_code) {
            if (method_exists($user, 'resetTwoFactorCode')) {
                $user->resetTwoFactorCode();
            } else {
                $user->update([
                    'two_factor_code' => null,
                    'two_factor_expires_at' => null,
                ]);
            }

            // Generate an elevated or full-access token if Sanctum is used
            $token = $user->createToken('labguard-native-session')->plainTextToken;

            return response()->json([
                'message' => 'Two-factor authentication verified successfully.',
                'token'   => $token,
                'user'    => $user,
            ]);
        }

        return response()->json([
            'message' => 'The code is incorrect.',
            'errors'  => [
                'two_factor_code' => ['The code is incorrect.'],
            ],
        ], 422);
    }

    /**
     * Optional: Resend 2FA code if requested by client.
     */
    public function resend(): JsonResponse
    {
        $user = auth()->user();

        if (method_exists($user, 'generateTwoFactorCode')) {
            $user->generateTwoFactorCode();
        }

        return response()->json([
            'message' => 'A new two-factor code has been dispatched.',
        ]);
    }
}
