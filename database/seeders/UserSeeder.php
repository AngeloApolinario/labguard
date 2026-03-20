<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // 1. Create Super Admin (The Boss)
        User::create([
            'name' => 'Master Super Admin',
            'email' => 'superadmin@labguard.com',
            'password' => Hash::make('password'),
            'role' => 'super-admin',
            'email_verified_at' => $now, // Verified instantly
        ]);

        // 2. Create Admin
        User::create([
            'name' => 'System Admin',
            'email' => 'admin@labguard.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => $now,
        ]);

        // 3. Create Personnel
        User::create([
            'name' => 'John Teacher',
            'email' => 'teacher@labguard.com',
            'password' => Hash::make('password'),
            'role' => 'personnel',
            'email_verified_at' => $now,
        ]);

        // 4. Create Student
        User::create([
            'name' => 'Juan Dela Cruz',
            'email' => 'student@labguard.com',
            'password' => Hash::make('password'),
            'role' => 'student',
            'email_verified_at' => $now, // Verified instantly
        ]);
    }
}
