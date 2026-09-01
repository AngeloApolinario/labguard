<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TwoFactorController extends Controller
{
    /**
     * Check 2FA Status for Mobile Client
     */
    public function index(): JsonResponse
    {
        $user = auth()->user();

        return response()->json([
            'success' => true,
            'message' => 'Two-factor status retrieved.',
            'data'    => [
                'two_factor_required' => !is_null($user->two_factor_code),
                'expires_at'          => $user->two_factor_expires_at ?? null,
            ]
        ], 200);
    }

    /**
     * Verify 2FA Code submitted from Mobile App
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'two_factor_code' => ['required', 'string'],
        ]);

        $user = auth()->user();

        // 1. Check if the code has expired
        if (isset($user->two_factor_expires_at) && $user->two_factor_expires_at && $user->two_factor_expires_at->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'The two-factor code has expired. Please request a new code.',
                'errors'  => [
                    'two_factor_code' => ['The verification code has expired.']
                ]
            ], 422);
        }

        // 2. Validate Code Mismatch
        if (!$user->two_factor_code || $request->two_factor_code != $user->two_factor_code) {
            return response()->json([
                'success' => false,
                'message' => 'The two-factor code is incorrect.',
                'errors'  => [
                    'two_factor_code' => ['The provided two-factor code is incorrect.']
                ]
            ], 422);
        }

        // 3. Reset Code on Success
        if (method_exists($user, 'resetTwoFactorCode')) {
            $user->resetTwoFactorCode();
        } else {
            $user->update([
                'two_factor_code'       => null,
                'two_factor_expires_at' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Two-factor authentication verified successfully.',
            'data'    => [
                'user' => $user->fresh(),
            ]
        ], 200);
    }

    /**
     * Resend 2FA Verification Code (Mobile App Helper)
     */
    public function resend(): JsonResponse
    {
        $user = auth()->user();

        if (method_exists($user, 'generateTwoFactorCode')) {
            $user->generateTwoFactorCode();
        }

        return response()->json([
            'success' => true,
            'message' => 'A new two-factor verification code has been generated and sent.',
        ], 200);
    }
}
