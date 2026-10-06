<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Lab;
use App\Models\Computer;
use Carbon\Carbon;

class LabSessionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // 1. Retrieve seeded Users
        $teacher1 = User::where('email', 'teacher@labguard.com')->first();
        $teacher2 = User::where('email', 'msantos@labguard.com')->first();
        $student  = User::where('email', 'juan.student@phinmaed.com')->first();

        // 2. Resolve Labs & Computers (with fallbacks if not seeded yet)
        $lab1 = Lab::find(1);
        $lab2 = Lab::find(2);
        $labName1 = $lab1->name ?? 'LAB 1';
        $labName2 = $lab2->name ?? 'LAB 2';

        $computer1 = Computer::find(1);
        $computer2 = Computer::find(2);
        $pcNumber1 = $computer1->pc_number ?? 'PC-01';
        $pcNumber2 = $computer2->pc_number ?? 'PC-02';

        $studentNumber1 = $student->student_number ?? '01-2324-047095';
        $studentName1   = $student->name ?? 'Juan Dela Cruz';

        $studentNumber2 = '01-2324-099999';
        $studentName2   = 'Pedro Penduko';

        // =========================================================================
        // STEP 1: SEED LAB SESSIONS
        // =========================================================================

        // Session 1: Completed Earlier Today
        $sessionId1 = DB::table('lab_sessions')->insertGetId([
            'computer_id'       => $computer1->id ?? 1,
            'student_name'      => $studentName1,
            'student_id_number' => $studentNumber1,
            'time_in'           => $now->copy()->subHours(4),
            'time_out'          => $now->copy()->subHours(2),
            'teacher_id'        => $teacher1->id ?? 3,
            'lab_id'            => $lab1->id ?? 1,
            'created_at'        => $now->copy()->subHours(4),
            'updated_at'        => $now->copy()->subHours(2),
        ]);

        // Session 2: Currently Active Session (Ongoing - no time_out)
        $sessionId2 = DB::table('lab_sessions')->insertGetId([
            'computer_id'       => $computer2->id ?? 2,
            'student_name'      => $studentName1,
            'student_id_number' => $studentNumber1,
            'time_in'           => $now->copy()->subMinutes(45),
            'time_out'          => null,
            'teacher_id'        => $teacher2->id ?? 4,
            'lab_id'            => $lab1->id ?? 1,
            'created_at'        => $now->copy()->subMinutes(45),
            'updated_at'        => $now,
        ]);

        // Session 3: Yesterday's Completed Session
        $sessionId3 = DB::table('lab_sessions')->insertGetId([
            'computer_id'       => $computer1->id ?? 1,
            'student_name'      => $studentName2,
            'student_id_number' => $studentNumber2,
            'time_in'           => $now->copy()->subDay()->setHour(9)->setMinute(0),
            'time_out'          => $now->copy()->subDay()->setHour(11)->setMinute(30),
            'teacher_id'        => $teacher1->id ?? 3,
            'lab_id'            => $lab2->id ?? 2,
            'created_at'        => $now->copy()->subDay()->setHour(9)->setMinute(0),
            'updated_at'        => $now->copy()->subDay()->setHour(11)->setMinute(30),
        ]);

        // =========================================================================
        // STEP 2: SEED CONNECTED SESSION CHECKLISTS
        // =========================================================================
        DB::table('session_checklists')->insert([
            // Checklist 1: 100% Operational (All items OK)
            [
                'lab_session_id'    => $sessionId1,
                'student_id_number' => $studentNumber1,
                'pc_number'         => $pcNumber1,
                'lab_name'          => $labName1,
                'system_unit_ok'    => 1,
                'monitor_ok'        => 1,
                'avr_ok'            => 1,
                'mouse_ok'          => 1,
                'keyboard_ok'       => 1,
                'cables_ok'         => 1,
                'all_operational'   => 1,
                'items_payload'     => json_encode([
                    'system_unit' => true,
                    'monitor'     => true,
                    'avr'         => true,
                    'mouse'       => true,
                    'keyboard'    => true,
                    'cables'      => true,
                ]),
                'verified_at'       => $now->copy()->subHours(4),
                'created_at'        => $now->copy()->subHours(4),
                'updated_at'        => $now->copy()->subHours(4),
            ],

            // Checklist 2: Active Session with Unchecked Optical Mouse (Defective/Missing)
            [
                'lab_session_id'    => $sessionId2,
                'student_id_number' => $studentNumber1,
                'pc_number'         => $pcNumber2,
                'lab_name'          => $labName1,
                'system_unit_ok'    => 1,
                'monitor_ok'        => 1,
                'avr_ok'            => 1,
                'mouse_ok'          => 0, // Defective/unchecked
                'keyboard_ok'       => 1,
                'cables_ok'         => 1,
                'all_operational'   => 0,
                'items_payload'     => json_encode([
                    'system_unit' => true,
                    'monitor'     => true,
                    'avr'         => true,
                    'mouse'       => false,
                    'keyboard'    => true,
                    'cables'      => true,
                ]),
                'verified_at'       => $now->copy()->subMinutes(45),
                'created_at'        => $now->copy()->subMinutes(45),
                'updated_at'        => $now->copy()->subMinutes(45),
            ],

            // Checklist 3: Yesterday's Session with Loose Cables Reported
            [
                'lab_session_id'    => $sessionId3,
                'student_id_number' => $studentNumber2,
                'pc_number'         => $pcNumber1,
                'lab_name'          => $labName2,
                'system_unit_ok'    => 1,
                'monitor_ok'        => 1,
                'avr_ok'            => 1,
                'mouse_ok'          => 1,
                'keyboard_ok'       => 1,
                'cables_ok'         => 0, // Loose/unplugged cables
                'all_operational'   => 0,
                'items_payload'     => json_encode([
                    'system_unit' => true,
                    'monitor'     => true,
                    'avr'         => true,
                    'mouse'       => true,
                    'keyboard'    => true,
                    'cables'      => false,
                ]),
                'verified_at'       => $now->copy()->subDay()->setHour(9)->setMinute(0),
                'created_at'        => $now->copy()->subDay()->setHour(9)->setMinute(0),
                'updated_at'        => $now->copy()->subDay()->setHour(9)->setMinute(0),
            ],
        ]);
    }
}
