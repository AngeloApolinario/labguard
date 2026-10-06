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

        // 1. Create Super Admin
        User::create([
            'name' => 'Master Super Admin',
            'email' => 'superadmin@phinmaed.com',
            'password' => Hash::make('Password1!'),
            'role' => 'super-admin',
            'student_number' => '01-2324-047090',
            'phone' => '09000000001',
            'email_verified_at' => $now,
        ]);

        // 2. Create Admin
        User::create([
            'name' => 'System Admin',
            'email' => 'admin@phinmaed.com',
            'password' => Hash::make('Password1!'),
            'role' => 'admin',
            'student_number' => '01-2324-047091',
            'phone' => '09000000002',
            'email_verified_at' => $now,
        ]);

        // 3. Create Personnel
        User::create([
            'name' => 'John Teacher',
            'email' => 'teacher@phinmaed.com',
            'password' => Hash::make('Password1!'),
            'role' => 'personnel',
            'student_number' => '01-2324-047092',
            'phone' => '09000000003',
            'email_verified_at' => $now,
        ]);

        // 4. Create Personnel
        User::create([
            'name' => 'Maria Santos',
            'email' => 'msantos@phinmaed.com',
            'password' => Hash::make('Password1!'),
            'role' => 'personnel',
            'student_number' => '01-2324-047093',
            'phone' => '09000000004',
            'email_verified_at' => $now,
        ]);

        // 5. Create Personnel
        User::create([
            'name' => 'Ricardo Dalisay',
            'email' => 'rdalisay@phinmaed.com',
            'password' => Hash::make('Password1!'),
            'role' => 'personnel',
            'student_number' => '01-2324-047094',
            'phone' => '09000000005',
            'email_verified_at' => $now,
        ]);

        // 6. Create Student
        User::create([
            'name' => 'Juan Dela Cruz',
            'email' => 'juan.student@phinmaed.com',
            'password' => Hash::make('Password1!'),
            'role' => 'student',
            'student_number' => '01-2324-047095',
            'phone' => '09123456789',
            'email_verified_at' => $now,
        ]);
    }
}
