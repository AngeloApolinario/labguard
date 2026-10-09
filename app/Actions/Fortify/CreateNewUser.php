<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;
use Illuminate\Validation\Rules\Password;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'ends_with:@phinmaed.com',
                Rule::unique('users')->whereNull('deleted_at'),
            ],

            'password' => [
                'required',
                'string',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
                'confirmed',
            ],

            'phone' => [
                'required',
                'string',
                'regex:/^09[0-9]{9}$/',
            ],

            'student_number' => [
                'required',
                'string',
                Rule::unique('users')->whereNull('deleted_at'),
                // XX (any 2 digits) - XXXX (4 digits) - any length of digits
                'regex:/^[0-9]{2}-[0-9]{4}-[0-9]+$/',
            ],

            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature()
                ? ['accepted', 'required']
                : '',

        ], [
            'email.ends_with' =>
            'Please use your official PHINMA organization email address.',

            'student_number.regex' =>
            'The ID must follow the format: XX-XXXX-XXXX... (e.g., 01-2024-123456).',

            'phone.regex' =>
            'Please provide a valid 11-digit mobile number.',

        ])->validate();

        // 1. Check if a soft-deleted user account already exists
        //    with this email or student number.
        $trashedUser = User::onlyTrashed()
            ->where('email', $input['email'])
            ->orWhere('student_number', $input['student_number'])
            ->first();

        // 2. If a soft-deleted account exists, restore it, update details,
        //    and RESET email_verified_at to null so they must re-verify.
        if ($trashedUser) {
            $trashedUser->restore();

            $trashedUser->forceFill([
                'name'              => $input['name'],
                'email'             => $input['email'],
                'password'          => Hash::make($input['password']),
                'student_number'    => $input['student_number'],
                'phone'             => $input['phone'],
                'role'              => 'student',
                'email_verified_at' => null, // <-- Forces the account to be unverified
            ])->save();

            return $trashedUser;
        }

        // 3. Otherwise, create a fresh student account.
        return User::create([
            'name'           => $input['name'],
            'email'          => $input['email'],
            'password'       => Hash::make($input['password']),
            'student_number' => $input['student_number'],
            'phone'          => $input['phone'],
            'role'           => 'student',
        ]);
    }
}
