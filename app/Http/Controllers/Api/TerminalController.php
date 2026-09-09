<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Computer;
use App\Models\User;
use App\Models\LabSession;
use App\Models\Alert;
use App\Models\Schedule;
use App\Models\SessionChecklist;
use App\Models\SubjectEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class TerminalController extends Controller
{
    /**
     * Handle Student Login from Python Terminal with Strict Enrollment, Schedule & Email Verification
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

        // 1. Validate User Credentials (formatted or raw student ID)
        $user = User::where('student_number', $inputStudentId)
            ->orWhere('student_number', $cleanStudentId)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        // 1b. Student Account Verification Check
        if (strtolower($user->role) === 'student' && is_null($user->email_verified_at)) {
            return response()->json([
                'message' => 'Access Denied: Your student account is not verified. Please verify your email before accessing the terminal.'
            ], 403);
        }

        // 2. Case-Insensitive PC and Lab Lookup
        $labId = strtolower(trim($request->lab_id ?? $request->lab ?? ''));
        $pcNumber = strtolower(trim($request->pc_number));

        $pc = Computer::whereHas('lab', function ($query) use ($labId) {
            if ($labId) {
                $query->where('id', $labId)
                    ->orWhereRaw('LOWER(name) = ?', [$labId]);
            }
        })->whereRaw('LOWER(pc_number) = ?', [$pcNumber])->first();

        // Fallback: Lookup by pc_number alone
        if (!$pc) {
            $pc = Computer::whereRaw('LOWER(pc_number) = ?', [$pcNumber])->first();
        }

        if (!$pc) {
            return response()->json(['message' => 'Terminal station not found.'], 404);
        }

        // 3. Block login if PC is under maintenance
        if (strtolower($pc->status) === 'maintenance') {
            return response()->json(['message' => 'This terminal station is currently under maintenance.'], 403);
        }

        /**
         * DYNAMIC TEACHER ASSIGNMENT & ENROLLMENT VERIFICATION
         */
        $currentTime = now()->format('H:i:s');
        $currentDay = now()->format('l');

        $activeSchedule = Schedule::where('lab_id', $pc->lab_id)
            ->where('day', $currentDay)
            ->whereTime('start_time', '<=', $currentTime)
            ->whereTime('end_time', '>=', $currentTime)
            ->first();

        $assignedTeacherId = $activeSchedule ? $activeSchedule->user_id : null;

        // 4. STRICT SUBJECT ENROLLMENT & SCHEDULE RESTRICTION (Students Only)
        if (strtolower($user->role) === 'student') {
            // Check 4A: Block if there is no class or schedule active in this lab
            if (!$activeSchedule) {
                return response()->json([
                    'message' => 'Access Denied: No active class or open lab scheduled for this laboratory.'
                ], 403);
            }

            $subjectCode = trim($activeSchedule->subject_code);

            // Check 4B: Allow if designated as Open Lab
            $isOpenLab = str_contains(strtoupper($subjectCode), 'OPEN')
                || str_contains(strtoupper($subjectCode), 'FREE')
                || ($activeSchedule->is_open_lab ?? false);

            if (!$isOpenLab) {
                $studentEmail = strtolower(trim($user->email));

                // Check 4C: Direct check against enrollment table
                $isEnrolled = SubjectEnrollment::whereRaw('LOWER(TRIM(subject_code)) = ?', [strtolower($subjectCode)])
                    ->whereRaw('LOWER(TRIM(email)) = ?', [$studentEmail])
                    ->exists();

                if (!$isEnrolled) {
                    return response()->json([
                        'message' => "Access Denied: You are not enrolled in {$subjectCode}."
                    ], 403);
                }
            }
        }

        // 5. Race Condition Fix: Close active sessions on this PC or for this student
        LabSession::where(function ($query) use ($pc, $user) {
            $query->where('computer_id', $pc->id)
                ->orWhere('student_id_number', $user->student_number);
        })
            ->whereNull('time_out')
            ->update(['time_out' => now()]);

        // 6. Update PC Status
        $pc->update([
            'status'       => 'active',
            'last_ping_at' => now()
        ]);

        // 7. Create Lab Session
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
            'role'    => $user->role,
            'teacher' => $activeSchedule?->user?->name ?? 'No active class'
        ], 200);
    }

    /**
     * Heartbeat check for Python background thread
     */
    public function checkStatus($lab_id, $pc_number)
    {
        $cleanLabId = strtolower(trim($lab_id));
        $cleanPcNumber = strtolower(trim($pc_number));

        // 1. Resolve PC by Lab and PC Number
        $pc = Computer::whereHas('lab', function ($query) use ($cleanLabId) {
            $query->where('id', $cleanLabId)
                ->orWhereRaw('LOWER(name) = ?', [$cleanLabId]);
        })
            ->whereRaw('LOWER(pc_number) = ?', [$cleanPcNumber])
            ->first();

        // 2. Fallback lookup directly by pc_number
        if (!$pc) {
            $pc = Computer::whereRaw('LOWER(pc_number) = ?', [$cleanPcNumber])->first();
        }

        // 3. If PC doesn't exist, tell terminal to stay locked
        if (!$pc) {
            return response()->json(['status' => 'available'], 200);
        }

        // 4. Record Heartbeat Ping Timestamp
        $pc->update(['last_ping_at' => now()]);

        // 5. Cleanup stale active PCs whose pings stopped > 30s ago
        Computer::cleanupStaleSessions();

        // 6. Return actual PC status
        return response()->json([
            'status' => strtolower($pc->status)
        ], 200);
    }

    /**
     * Handle PC Reporting/Alerts
     */
    public function reportIssue(Request $request)
    {
        $request->validate([
            'pc_number'  => 'required',
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

        $cleanPcNumber = strtolower(trim($request->pc_number));
        $pc = Computer::whereRaw('LOWER(pc_number) = ?', [$cleanPcNumber])->first();

        if (!$pc) {
            return response()->json(['message' => 'Terminal station not found.'], 404);
        }

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
     * Handle PC Logout
     */
    public function handleLogout(Request $request)
    {
        $cleanPcNumber = strtolower(trim($request->pc_number));
        $computer = Computer::whereRaw('LOWER(pc_number) = ?', [$cleanPcNumber])->first();

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

            if (strtolower($computer->status) !== 'maintenance') {
                $computer->update(['status' => 'available']);
            }

            return response()->json(['status' => strtolower($computer->status)]);
        }

        return response()->json(['message' => 'PC not found'], 404);
    }

    /**
     * Store Workstation Peripheral Inspection Checklist
     */
    public function storeChecklist(Request $request)
    {
        try {
            $validated = $request->validate([
                'pc_number'         => 'required|string',
                'student_id'        => 'nullable|string',
                'student_id_number' => 'nullable|string',
                'lab'               => 'nullable|string',
                'session_id'        => 'nullable|integer',
                'checklist'         => 'required|array',
            ]);

            $pcNumber = $validated['pc_number'];
            $studentIdNumber = $request->input('student_id_number') ?? $request->input('student_id');
            $sessionId = $validated['session_id'] ?? null;

            // 1. Find Computer
            $computer = Computer::where('pc_number', $pcNumber)->first();

            // 2. Find Active LabSession if session_id wasn't provided
            if (!$sessionId) {
                $sessionQuery = LabSession::whereNull('time_out');

                if ($studentIdNumber) {
                    $sessionQuery->where('student_id_number', $studentIdNumber);
                } elseif ($computer) {
                    $sessionQuery->where('computer_id', $computer->id);
                }

                $activeSession = $sessionQuery->latest('id')->first();
                $sessionId = $activeSession?->id;

                if (!$studentIdNumber && $activeSession) {
                    $studentIdNumber = $activeSession->student_id_number;
                }
            }

            if (!$studentIdNumber) {
                $studentIdNumber = 'UNKNOWN';
            }

            // 3. Mark computer as active
            if ($computer) {
                $computer->update([
                    'status'          => 'active',
                    'current_student' => $studentIdNumber,
                    'last_ping_at'    => now(),
                ]);
            }

            $items = $validated['checklist'];

            // 4. Create hardware inspection record directly
            $record = new SessionChecklist();
            $record->lab_session_id    = $sessionId;
            $record->student_id_number = $studentIdNumber;
            $record->pc_number         = $pcNumber;
            $record->lab_name          = $validated['lab'] ?? null;
            $record->monitor_ok        = (bool)($items['monitor'] ?? true);
            $record->keyboard_ok       = (bool)($items['keyboard'] ?? true);
            $record->mouse_ok          = (bool)($items['mouse'] ?? true);
            $record->avr_ok            = (bool)($items['avr'] ?? true);
            $record->pc_case_ok        = (bool)($items['case'] ?? true);
            $record->headset_ok        = (bool)($items['headset'] ?? true);
            $record->all_operational   = true;
            $record->items_payload     = $items;
            $record->verified_at       = now();
            $record->save();

            return response()->json([
                'status'  => 'success',
                'message' => 'Inspection logged and linked to session successfully.',
                'data'    => $record,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Checklist Store Error: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
