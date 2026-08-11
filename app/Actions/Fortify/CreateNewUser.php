<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->whereNull('deleted_at'),
            ],
            'password' => $this->passwordRules(),
            'phone' => ['required', 'string', 'regex:/^09[0-9]{9}$/'],
            'student_number' => [
                'required',
                'string',
                Rule::unique('users')->whereNull('deleted_at'),
                'regex:/^01-[0-9]{4}-[0-9]{6}$/',
            ],
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ], [
            'student_number.regex' => 'The ID must follow the AU format: 01-XXXX-XXXXXX.',
            'phone.regex' => 'Please provide a valid 11-digit mobile number.',
        ])->validate();

        // 1. Check if a soft-deleted user account already exists with this email or student number
        $trashedUser = User::onlyTrashed()
            ->where('email', $input['email'])
            ->orWhere('student_number', $input['student_number'])
            ->first();

        // 2. If soft-deleted, restore the record and sync the newly registered data
        if ($trashedUser) {
            $trashedUser->restore();
            $trashedUser->update([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
                'student_number' => $input['student_number'],
                'phone' => $input['phone'],
                'role' => 'student',
            ]);

            return $trashedUser;
        }

        // 3. Otherwise, create a brand new user row
        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'student_number' => $input['student_number'],
            'phone' => $input['phone'],
            'role' => 'student',
        ]);
    }
}
