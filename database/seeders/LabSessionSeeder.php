<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Carbon\Carbon;

class LabSessionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // Retrieve seeded users
        $teacher1 = User::where('email', 'teacher@labguard.com')->first();
        $teacher2 = User::where('email', 'msantos@labguard.com')->first();
        $student  = User::where('email', 'juan.student@phinmaed.com')->first();

        // Dummy/Fallback IDs if computers or labs tables aren't seeded yet
        $computerId1 = 1;
        $computerId2 = 2;
        $labId1      = 1;
        $labId2      = 2;

        DB::table('lab_sessions')->insert([
            // 1. Completed Session (Earlier Today)
            [
                'computer_id'       => $computerId1,
                'student_name'      => $student->name ?? 'Juan Dela Cruz',
                'student_id_number' => $student->student_number ?? '01-2324-047095',
                'time_in'           => $now->copy()->subHours(4),
                'time_out'          => $now->copy()->subHours(2),
                'teacher_id'        => $teacher1->id ?? 3,
                'lab_id'            => $labId1,
                'created_at'        => $now->copy()->subHours(4),
                'updated_at'        => $now->copy()->subHours(2),
            ],

            // 2. Currently Active Session (Logged In - No time_out)
            [
                'computer_id'       => $computerId2,
                'student_name'      => $student->name ?? 'Juan Dela Cruz',
                'student_id_number' => $student->student_number ?? '01-2324-047095',
                'time_in'           => $now->copy()->subMinutes(45),
                'time_out'          => null,
                'teacher_id'        => $teacher2->id ?? 4,
                'lab_id'            => $labId1,
                'created_at'        => $now->copy()->subMinutes(45),
                'updated_at'        => $now,
            ],

            // 3. Yesterday's Completed Session
            [
                'computer_id'       => $computerId1,
                'student_name'      => 'Pedro Penduko',
                'student_id_number' => '01-2324-099999',
                'time_in'           => $now->copy()->subDay()->setHour(9)->setMinute(0),
                'time_out'          => $now->copy()->subDay()->setHour(11)->setMinute(30),
                'teacher_id'        => $teacher1->id ?? 3,
                'lab_id'            => $labId2,
                'created_at'        => $now->copy()->subDay()->setHour(9)->setMinute(0),
                'updated_at'        => $now->copy()->subDay()->setHour(11)->setMinute(30),
            ],
        ]);
    }
}
