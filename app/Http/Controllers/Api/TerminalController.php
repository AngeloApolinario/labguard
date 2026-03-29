<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Computer;
use App\Models\User;
use App\Models\LabSession;
use App\Models\Alert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class TerminalController extends Controller
{
    /**
     * Handle Student Login from Python Terminal
     */
    public function login(Request $request)
    {
        $request->validate([
            'student_id' => 'required',
            'password'   => 'required',
            'pc_number'  => 'required'
        ]);

        $user = User::where('student_number', trim($request->student_id))->first();

        // 1. Validate User & Password
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        // 2. Find the PC and its Lab
        $pc = Computer::with('lab')->where('pc_number', $request->pc_number)->first();
        if (!$pc) {
            return response()->json(['message' => 'Terminal station not found.'], 404);
        }

        /**
         * DYNAMIC TEACHER ASSIGNMENT
         * Find the schedule for THIS lab, TODAY, at THIS current time.
         */
        $currentTime = now()->format('H:i:s');
        $currentDay = now()->format('l'); // e.g., "Monday"

        $activeSchedule = \App\Models\Schedule::where('lab_id', $pc->lab_id)
            ->where('day', $currentDay)
            ->whereTime('start_time', '<=', $currentTime)
            ->whereTime('end_time', '>=', $currentTime)
            ->first();

        // Assign teacher_id from schedule, or default to NULL (or a specific "No Teacher" ID)
        $assignedTeacherId = $activeSchedule ? $activeSchedule->user_id : null;

        // 3. Race Condition Fix (Close stuck sessions)
        LabSession::where('computer_id', $pc->id)
            ->whereNull('time_out')
            ->update(['time_out' => now()]);

        // 4. Update PC Status
        $pc->update(['status' => 'active']);


        // 5. Create Lab Session
        LabSession::create([
            'user_id'           => $user->id,
            'computer_id'       => $pc->id,
            'lab_id'            => $pc->lab_id,
            'student_name'      => $user->name,
            'student_id_number' => $user->student_number,
            'time_in'           => now(),
            'time_out'          => null,
            'teacher_id'        => $assignedTeacherId,
        ]);

        return response()->json([
            'message' => 'Access Granted',
            'name' => $user->name,
            'teacher' => $activeSchedule?->user?->name ?? 'No active class'
        ], 200);
    }

    /**
     * Heartbeat check for Python background thread
     * Used to detect if the Admin has remotely locked the PC.
     */
    public function checkStatus($pc_number)
    {
        $pc = Computer::where('pc_number', $pc_number)->first();

        if (!$pc) {
            return response()->json(['status' => 'available']);
        }

        /**
         * If the status is 'available' (set by Admin in Dashboard), 
         * the Python script will see this and trigger its lock screen.
         */
        return response()->json([
            'status' => $pc->status
        ]);
    }

    /**
     * Handle PC Reporting/Alerts from the Cinematic UI
     */
    public function reportIssue(Request $request)
    {
        $request->validate([
            'pc_number'  => 'required|exists:computers,pc_number',
            'issue_type' => 'required|string',
            'remarks'    => 'required|string',
        ]);

        $pc = Computer::where('pc_number', $request->pc_number)->first();

        // Create the maintenance/alert record
        Alert::create([
            'computer_id' => $pc->id,
            'issue_type'  => $request->issue_type,
            'remarks'     => $request->remarks,
            'status'      => 'pending',
        ]);

        return response()->json(['message' => 'Technical support notified.'], 201);
    }

    /**
     * Handle PC Logout (Triggered by atexit in Python)
     */
    public function handleLogout(Request $request)
    {
        // 1. Find the Computer
        $computer = Computer::where('pc_number', $request->pc_number)->first();

        if ($computer) {
            // 2. Find ONLY the active session for this specific computer
            // We order by ID descending to ensure we get the very last one created
            $session = LabSession::where('computer_id', $computer->id)
                ->whereNull('time_out')
                ->orderBy('id', 'desc')
                ->first();

            if ($session) {
                // 3. Perform the update
                $session->update([
                    'time_out' => now()
                ]);

                Log::info("Session ID {$session->id} closed for PC {$request->pc_number}");
            }

            // 4. Reset PC Status
            $computer->update(['status' => 'available']);

            return response()->json(['status' => 'available']);
        }

        return response()->json(['message' => 'PC not found'], 404);
    }
}
