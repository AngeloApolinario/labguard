<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Computer;
use App\Models\User;
use App\Models\LabSession;
use App\Models\Alert;
use App\Models\Schedule;
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

        $inputStudentId = trim($request->student_id);
        $cleanStudentId = preg_replace('/\D/', '', $inputStudentId);

        // 1. Validate User Credentials (Checks formatted or raw student ID)
        $user = User::where('student_number', $inputStudentId)
            ->orWhere('student_number', $cleanStudentId)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        // 2. Verify User Email (Only verified students can access labs)
        if (!$user->email_verified_at) {
            return response()->json(['message' => 'Your account is not verified. Please verify your email address first.'], 403);
        }

        // 3. Find the PC and its Lab
        $pc = Computer::with('lab')->where('pc_number', $request->pc_number)->first();
        if (!$pc) {
            return response()->json(['message' => 'Terminal station not found.'], 404);
        }

        // 4. Block login if PC is under maintenance
        if (strtolower($pc->status) === 'maintenance') {
            return response()->json(['message' => 'This terminal station is currently under maintenance.'], 403);
        }

        /**
         * DYNAMIC TEACHER ASSIGNMENT
         */
        $currentTime = now()->format('H:i:s');
        $currentDay = now()->format('l');

        $activeSchedule = Schedule::where('lab_id', $pc->lab_id)
            ->where('day', $currentDay)
            ->whereTime('start_time', '<=', $currentTime)
            ->whereTime('end_time', '>=', $currentTime)
            ->first();

        $assignedTeacherId = $activeSchedule ? $activeSchedule->user_id : null;

        // 4. Race Condition Fix: Close active sessions on this PC or for this student number
        LabSession::where(function ($query) use ($pc, $user) {
            $query->where('computer_id', $pc->id)
                ->orWhere('student_id_number', $user->student_number);
        })
            ->whereNull('time_out')
            ->update(['time_out' => now()]);

        // 5. Update PC Status
        $pc->update([
            'status' => 'active',
            'last_ping_at' => now()
        ]);

        // 6. Create Lab Session (Matches your exact lab_sessions columns)
        LabSession::create([
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
            'name'    => $user->name,
            'teacher' => $activeSchedule?->user?->name ?? 'No active class'
        ], 200);
    }

    /**
     * Heartbeat check for Python background thread
     */
    public function checkStatus($lab_id, $pc_number)
    {
        // 1. Resolve PC by Lab (ID or Name) and PC Number
        $pc = Computer::whereHas('lab', function ($query) use ($lab_id) {
            $query->where('id', $lab_id)
                ->orWhere('name', $lab_id);
        })
            ->where('pc_number', $pc_number)
            ->first();

        // 2. Fallback: Look up directly by pc_number
        if (!$pc) {
            $pc = Computer::where('pc_number', $pc_number)->first();
        }

        // 3. If PC doesn't exist, tell terminal to stay locked ('available')
        if (!$pc) {
            return response()->json(['status' => 'available'], 200);
        }

        // 4. Record Heartbeat Ping Timestamp
        $pc->update(['last_ping_at' => now()]);

        // 5. SELF-HEALING: Cleanup any active PCs whose pings stopped > 30s ago (e.g. abrupt shutdown)
        $this->cleanupStaleSessions();

        // 6. Return actual PC status (normalized to lowercase)
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

        $inputStudentId = trim($request->student_id);
        $cleanStudentId = preg_replace('/\D/', '', $inputStudentId);

        // 1. Authenticate user identity
        $user = User::where('student_number', $inputStudentId)
            ->orWhere('student_number', $cleanStudentId)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Account verification failed. Invalid credentials.'], 401);
        }

        $pc = Computer::where('pc_number', $request->pc_number)->first();

        // 2. Create the alert record
        Alert::create([
            'computer_id' => $pc->id,
            'lab_id'      => $pc->lab_id,
            'reported_by' => $user->id,
            'issue_type'  => $request->issue_type,
            'remarks'     => $request->remarks,
            'status'      => 'pending',
        ]);

        return response()->json(['message' => 'Technical support notified.'], 201);
    }

    /**
     * Handle PC Logout (Triggered by OS shutdown, escape button, or exit handler)
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

            // Only mark available if NOT under maintenance
            if (strtolower($computer->status) !== 'maintenance') {
                $computer->update(['status' => 'available']);
            }

            return response()->json(['status' => strtolower($computer->status)]);
        }

        return response()->json(['message' => 'PC not found'], 404);
    }

    /**
     * Helper to auto-close sessions for computers that abruptly shut down/disconnected
     */
    private function cleanupStaleSessions()
    {
        $staleThreshold = now()->subSeconds(30);

        // Find active PCs that haven't pinged in > 30 seconds
        $staleComputers = Computer::where('status', 'active')
            ->where(function ($query) use ($staleThreshold) {
                $query->whereNull('last_ping_at')
                    ->orWhere('last_ping_at', '<', $staleThreshold);
            })
            ->get();

        foreach ($staleComputers as $pc) {
            LabSession::where('computer_id', $pc->id)
                ->whereNull('time_out')
                ->update(['time_out' => now()]);

            $pc->update(['status' => 'available']);
            Log::info("Auto-released stale PC: {$pc->pc_number} (no ping received)");
        }
    }
}
