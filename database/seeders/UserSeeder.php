<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Admin
        User::create([
            'name' => 'System Admin',
            'email' => 'admin@labguard.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // Create Personnel
        User::create([
            'name' => 'John Teacher',
            'email' => 'teacher@labguard.com',
            'password' => Hash::make('password'),
            'role' => 'personnel',
        ]);
    }
}
