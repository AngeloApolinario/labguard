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
         */
        $currentTime = now()->format('H:i:s');
        $currentDay = now()->format('l');

        $activeSchedule = \App\Models\Schedule::where('lab_id', $pc->lab_id)
            ->where('day', $currentDay)
            ->whereTime('start_time', '<=', $currentTime)
            ->whereTime('end_time', '>=', $currentTime)
            ->first();

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
     */
    public function checkStatus($lab_id, $pc_number)
    {
        // 1. Resolve PC by Lab (Name or ID) and PC Number
        $pc = Computer::whereHas('lab', function ($query) use ($lab_id) {
            $query->where('id', $lab_id)
                ->orWhere('name', $lab_id); // Removed 'code' to prevent SQL 1054 error
        })
            ->where('pc_number', $pc_number)
            ->first();

        // 2. Fallback: Look up directly by pc_number if lab match wasn't found
        if (!$pc) {
            $pc = Computer::where('pc_number', $pc_number)->first();
        }

        // 3. If PC still doesn't exist, tell terminal to stay locked ('available')
        if (!$pc) {
            return response()->json(['status' => 'available'], 200);
        }

        // 4. Return actual PC status (normalized to lowercase)
        return response()->json([
            'status' => strtolower($pc->status)
        ], 200);
    }

    /**
     * Handle PC Reporting/Alerts with Mandatory Accountability Verification
     */
    public function reportIssue(Request $request)
    {
        $request->validate([
            'pc_number'  => 'required|exists:computers,pc_number',
            'student_id' => 'required',
            'password'   => 'required',
            'issue_type' => 'required|string',
            'remarks'    => 'required|string',
        ]);

        // 1. Authenticate user identity for the security audit log
        $user = User::where('student_number', trim($request->student_id))->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Account verification failed. Invalid credentials.'], 401);
        }

        $pc = Computer::where('pc_number', $request->pc_number)->first();

        // 2. Create the maintenance record securely bound to reported_by and lab_id
        Alert::create([
            'computer_id' => $pc->id,
            'lab_id'      => $pc->lab_id, // <--- ADDED: Fixes the missing lab_id default value error
            'reported_by' => $user->id,
            'issue_type'  => $request->issue_type,
            'remarks'     => $request->remarks,
            'status'      => 'pending',
        ]);

        return response()->json(['message' => 'Technical support notified.'], 201);
    }

    /**
     * Handle PC Logout (Triggered by atexit or manual escape)
     */
    public function handleLogout(Request $request)
    {
        $computer = Computer::where('pc_number', $request->pc_number)->first();

        if ($computer) {
            $session = LabSession::where('computer_id', $computer->id)
                ->whereNull('time_out')
                ->orderBy('id', 'desc')
                ->first();

            if ($session) {
                $session->update([
                    'time_out' => now()
                ]);

                Log::info("Session ID {$session->id} closed for PC {$request->pc_number}");
            }

            $computer->update(['status' => 'available']);

            return response()->json(['status' => 'available']);
        }

        return response()->json(['message' => 'PC not found'], 404);
    }
}
