<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Schedule;
use App\Models\Lab;
use App\Models\User;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $labs = Lab::all();
        $teachers = User::where('role', 'personnel')->get();
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        // Define standard 1.5-hour time blocks
        $timeSlots = [
            ['07:30:00', '09:00:00'],
            ['09:00:00', '10:30:00'],
            ['10:30:00', '12:00:00'],
            ['13:00:00', '14:30:00'],
            ['14:30:00', '16:00:00'],
            ['16:00:00', '17:30:00'],
        ];

        $subjectPrefixes = ['ITE', 'CS', 'ACT', 'GEC'];

        foreach ($labs as $lab) {
            foreach ($days as $day) {
                foreach ($timeSlots as $index => $slot) {
                    // Rotate teachers so they aren't all in the same lab
                    $teacher = $teachers[($lab->id + $index) % $teachers->count()];

                    Schedule::create([
                        'lab_id' => $lab->id,
                        'user_id' => $teacher->id,
                        'day' => $day,
                        'subject_code' => $subjectPrefixes[array_rand($subjectPrefixes)] . "-" . rand(100, 500),
                        'start_time' => $slot[0],
                        'end_time' => $slot[1],
                    ]);
                }
            }
        }
    }
}
