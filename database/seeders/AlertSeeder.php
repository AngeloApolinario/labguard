<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Carbon\Carbon;

class AlertSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // Retrieve seeded users for 'reported_by'
        $teacher = User::where('email', 'teacher@labguard.com')->first();
        $student = User::where('email', 'juan.student@phinmaed.com')->first();

        // Fallback user ID if specific emails aren't found
        $defaultReporterId = $student->id ?? $teacher->id ?? 1;

        // Dummy/Fallback IDs for foreign tables
        $computerId1 = 1;
        $computerId2 = 2;
        $labId1      = 1;
        $labId2      = 2;

        DB::table('alerts')->insert([
            // 1. Pending Hardware Issue
            [
                'computer_id' => $computerId1,
                'lab_id'      => $labId1,
                'reported_by' => $defaultReporterId,
                'issue_type'  => 'Hardware Fault',
                'remarks'     => 'Monitor display blinks intermittently and shows static lines.',
                'status'      => 'pending',
                'resolved_at' => null,
                'created_at'  => $now->copy()->subHours(2),
                'updated_at'  => $now->copy()->subHours(2),
            ],

            // 2. Network Issue (Status set to 'fixing')
            [
                'computer_id' => $computerId2,
                'lab_id'      => $labId1,
                'reported_by' => $teacher->id ?? $defaultReporterId,
                'issue_type'  => 'Network Disconnection',
                'remarks'     => 'No internet connectivity. LAN cable appears intact but port light is off.',
                'status'      => 'fixing',
                'resolved_at' => null,
                'created_at'  => $now->copy()->subHours(5),
                'updated_at'  => $now->copy()->subHour(),
            ],

            // 3. Resolved Software Issue
            [
                'computer_id' => $computerId1,
                'lab_id'      => $labId2,
                'reported_by' => $defaultReporterId,
                'issue_type'  => 'Software Corruption',
                'remarks'     => 'Visual Studio Code keeps crashing on startup.',
                'status'      => 'resolved',
                'resolved_at' => $now->copy()->subDay()->addHours(3),
                'created_at'  => $now->copy()->subDay(),
                'updated_at'  => $now->copy()->subDay()->addHours(3),
            ],

            // 4. Pending Peripherals Issue
            [
                'computer_id' => $computerId2,
                'lab_id'      => $labId2,
                'reported_by' => $defaultReporterId,
                'issue_type'  => 'Peripheral Damage',
                'remarks'     => 'Keyboard spacebar and letter E key are unreadable and unresponsive.',
                'status'      => 'pending',
                'resolved_at' => null,
                'created_at'  => $now->copy()->subMinutes(30),
                'updated_at'  => $now->copy()->subMinutes(30),
            ],
        ]);
    }
}
