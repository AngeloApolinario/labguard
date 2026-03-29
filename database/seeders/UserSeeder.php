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
            'student_number' => 'SA-01',
            'phone' => '09000000001',
            'email_verified_at' => $now,
        ]);

        // 2. Create Admin
        User::create([
            'name' => 'System Admin',
            'email' => 'admin@labguard.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'student_number' => 'AD-01',
            'phone' => '09000000002',
            'email_verified_at' => $now,
        ]);

        // 3. Create Personnel (Original Teacher)
        User::create([
            'name' => 'John Teacher',
            'email' => 'teacher@labguard.com',
            'password' => Hash::make('password'),
            'role' => 'personnel',
            'student_number' => 'PR-01',
            'phone' => '09000000003',
            'email_verified_at' => $now,
        ]);

        // 4. Create Personnel (Teacher 2)
        User::create([
            'name' => 'Maria Santos',
            'email' => 'msantos@labguard.com',
            'password' => Hash::make('password'),
            'role' => 'personnel',
            'student_number' => 'PR-02',
            'phone' => '09000000004',
            'email_verified_at' => $now,
        ]);

        // 5. Create Personnel (Teacher 3)
        User::create([
            'name' => 'Ricardo Dalisay',
            'email' => 'rdalisay@labguard.com',
            'password' => Hash::make('password'),
            'role' => 'personnel',
            'student_number' => 'PR-03',
            'phone' => '09000000005',
            'email_verified_at' => $now,
        ]);

        // 6. Create Student
        User::create([
            'name' => 'Juan Dela Cruz',
            'email' => 'juan.student@phinmaed.com',
            'password' => Hash::make('password'),
            'role' => 'student',
            'student_number' => '01-2324-048389',
            'phone' => '09123456789',
            'email_verified_at' => $now,
        ]);
    }
}
