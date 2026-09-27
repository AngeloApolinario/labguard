<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Computer;
use App\Models\Lab;
use App\Models\LabSession;
use App\Models\Schedule;
use App\Models\SubjectEnrollment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PersonnelApiController extends Controller
{
    /**
     * Overview of all labs with occupied station counts.
     */
    public function index(): JsonResponse
    {
        Computer::cleanupStaleSessions();

        $labs = Lab::withCount([
            'computers as total',
            'computers as occupied' => function ($query) {
                $query->where('status', 'active');
            },
        ])->get();

        return response()->json($labs);
    }

    /**
     * Show a specific lab grid for assigning students to PCs.
     */
    public function showLab(Lab $lab): JsonResponse
    {
        $currentTime = now()->format('H:i:s');
        $currentDay  = now()->format('l');

        $currentSchedule = $lab->schedules()
            ->with('user')
            ->where('day', $currentDay)
            ->where('start_time', '<=', $currentTime)
            ->where('end_time', '>=', $currentTime)
            ->first();

        if ($currentSchedule) {
            if (auth()->id() !== $currentSchedule->user_id && auth()->user()->role !== 'admin') {
                return response()->json([
                    'status'           => 'denied',
                    'message'          => "Access Denied: This lab is currently reserved for {$currentSchedule->user->name}.",
                    'currentSchedule'  => $currentSchedule,
                ], 403);
            }
        }

        $computers = $lab->computers()->with(['activeSession'])->orderBy('pc_number')->get();
        $schedules = $lab->schedules()->with('user')->where('day', $currentDay)->orderBy('start_time')->get();

        return response()->json([
            'lab'             => $lab,
            'computers'       => $computers,
            'schedules'       => $schedules,
            'currentSchedule' => $currentSchedule,
        ]);
    }

    /**
     * Assign a student to a specific computer.
     */
    public function assign(Request $request, Computer $computer): JsonResponse
    {
        $validated = $request->validate([
            'student_name'   => 'required|string|max:255',
            'student_number' => 'required|string|max:50',
        ]);

        $session = LabSession::create([
            'computer_id'       => $computer->id,
            'student_name'      => $validated['student_name'],
            'student_id_number' => $validated['student_number'],
            'time_in'           => now(),
            'teacher_id'        => auth()->id(),
            'lab_id'            => $computer->lab_id,
        ]);

        $computer->update(['status' => 'active']);

        return response()->json([
            'message'  => "{$computer->pc_number} is now assigned to {$validated['student_name']}.",
            'session'  => $session,
            'computer' => $computer,
        ], 201);
    }

    /**
     * Release the PC and conclude session.
     */
    public function release(Computer $computer): JsonResponse
    {
        $session = LabSession::where('computer_id', $computer->id)
            ->whereNull('time_out')
            ->latest()
            ->first();

        if ($session) {
            $session->update([
                'time_out' => now(),
            ]);

            $computer->update(['status' => 'available']);

            return response()->json([
                'status'  => 'success',
                'message' => "PC {$computer->pc_number} released successfully.",
            ]);
        }

        $computer->update(['status' => 'available']);

        return response()->json([
            'status'  => 'warning',
            'message' => 'PC status reset, but no active session record was found.',
        ]);
    }

    /**
     * Weekly master schedule of all laboratories.
     */
    public function fullSchedule(): JsonResponse
    {
        $labs = Lab::with(['schedules.user'])->get();
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        return response()->json([
            'labs' => $labs,
            'days' => $days,
        ]);
    }

    /**
     * Historical log of completed student sessions.
     */
    public function sessionHistory(Request $request): JsonResponse
    {
        $query = LabSession::with(['computer.lab', 'teacher', 'checklist'])
            ->latest('id')
            ->whereNotNull('time_out');

        if (auth()->user()->role !== 'admin') {
            $query->where('teacher_id', auth()->id());
        }

        if ($request->filled('student_name')) {
            $search = trim($request->student_name);
            $query->where(function ($q) use ($search) {
                $q->where('student_name', 'like', "%{$search}%")
                    ->orWhere('student_id_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('pc_number')) {
            $pcInput = trim($request->pc_number);
            $query->whereHas('computer', function ($q) use ($pcInput) {
                $cleanNum = preg_replace('/\D/', '', $pcInput);
                $q->where('pc_number', 'like', "%{$pcInput}%");

                if (!empty($cleanNum)) {
                    $num = (int)$cleanNum;
                    $q->orWhere('pc_number', 'like', "%PC-{$num}%")
                        ->orWhere('pc_number', 'like', "%PC-0{$num}%")
                        ->orWhere('pc_number', 'like', "%PC {$num}%")
                        ->orWhere('pc_number', 'like', "%PC 0{$num}%");
                }
            });
        }

        if ($request->filled('date')) {
            $query->whereDate('time_in', $request->date);
        }

        $sessions = $query->paginate(15);

        return response()->json($sessions);
    }

    /**
     * Maintenance and security incident history.
     */
    public function alertHistory(Request $request): JsonResponse
    {
        $query = Alert::with(['computer.lab', 'reporter'])->latest();

        if ($request->filled('pc_number')) {
            $pcInput = trim($request->pc_number);
            $query->whereHas('computer', function ($q) use ($pcInput) {
                $cleanNum = preg_replace('/\D/', '', $pcInput);
                $q->where('pc_number', 'like', "%{$pcInput}%");

                if (!empty($cleanNum)) {
                    $num = (int)$cleanNum;
                    $q->orWhere('pc_number', 'like', "%PC-{$num}%")
                        ->orWhere('pc_number', 'like', "%PC-0{$num}%")
                        ->orWhere('pc_number', 'like', "%PC {$num}%")
                        ->orWhere('pc_number', 'like', "%PC 0{$num}%");
                }
            });
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $totalReports    = Alert::count();
        $unresolvedCount = Alert::where('status', 'pending')->count();
        $alerts          = $query->paginate(15);

        return response()->json([
            'alerts'          => $alerts,
            'totalReports'    => $totalReports,
            'unresolvedCount' => $unresolvedCount,
        ]);
    }

    /**
     * Discard an alert as a false alarm.
     */
    public function discardAlert($id): JsonResponse
    {
        $alert = Alert::find($id);

        if (!$alert) {
            return response()->json(['message' => 'Alert record could not be found.'], 404);
        }

        $alert->update([
            'status'      => 'discarded',
            'resolved_at' => now(),
        ]);

        return response()->json([
            'message' => 'Alert successfully discarded as a false alarm.',
            'alert'   => $alert,
        ]);
    }

    /**
     * Stream CSV attendance report for a scheduled class session.
     */
    public function exportScheduleAttendance(Request $request, $id)
    {
        $schedule = Schedule::with('user')->findOrFail($id);

        if (auth()->id() !== $schedule->user_id && auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $targetDate = $request->query('date', now()->toDateString());

        $sessions = LabSession::where('lab_id', $schedule->lab_id)
            ->where('teacher_id', $schedule->user_id)
            ->whereDate('time_in', $targetDate)
            ->whereTime('time_in', '>=', $schedule->start_time)
            ->whereTime('time_in', '<=', $schedule->end_time)
            ->get();

        if ($sessions->isEmpty()) {
            return response()->json(['message' => 'No attendance records found for this session date.'], 404);
        }

        $fileName = "Attendance_{$schedule->subject_code}_{$targetDate}.csv";

        return response()->streamDownload(function () use ($sessions, $schedule, $targetDate) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // Excel UTF-8 BOM
            fputcsv($file, ['LABGUARD SYSTEM - ATTENDANCE REPORT']);
            fputcsv($file, ['Subject', $schedule->subject_code]);
            fputcsv($file, ['Instructor', $schedule->user->name ?? 'N/A']);
            fputcsv($file, ['Session Date', Carbon::parse($targetDate)->format('M d, Y')]);
            fputcsv($file, []);
            fputcsv($file, ['STUDENT NAME', 'STUDENT NUMBER', 'TIME IN', 'TIME OUT', 'DURATION (MINS)']);

            foreach ($sessions as $s) {
                $timeIn   = Carbon::parse($s->time_in);
                $duration = 'Still Logged In';

                if ($s->time_out) {
                    $timeOut       = Carbon::parse($s->time_out);
                    $duration      = $timeIn->diffInMinutes($timeOut) . ' mins';
                    $timeOutFormat = $timeOut->format('h:i A');
                } else {
                    $timeOutFormat = 'N/A';
                }

                fputcsv($file, [
                    strtoupper($s->student_name),
                    $s->student_id_number,
                    $timeIn->format('h:i A'),
                    $timeOutFormat,
                    $duration,
                ]);
            }

            fclose($file);
        }, $fileName);
    }

    /**
     * Batch enroll students into a subject from text box or CSV.
     */
    public function enrollStudent(Request $request): JsonResponse
    {
        $request->validate([
            'subject_code' => 'required|string',
            'emails'       => 'nullable|string',
            'file'         => 'nullable|file|mimes:csv,txt|max:5120',
        ]);

        $subjectCode    = trim($request->subject_code);
        $emailsToEnroll = [];

        if ($request->filled('emails')) {
            preg_match_all('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $request->emails, $matches);
            $emailsToEnroll = array_merge($emailsToEnroll, $matches[0] ?? []);
        }

        if ($request->hasFile('file')) {
            $file    = $request->file('file');
            $content = file_get_contents($file->getRealPath());
            preg_match_all('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $content, $matches);
            $emailsToEnroll = array_merge($emailsToEnroll, $matches[0] ?? []);
        }

        $emailsToEnroll = array_unique(array_map('strtolower', array_map('trim', $emailsToEnroll)));

        if (empty($emailsToEnroll)) {
            return response()->json(['message' => 'No valid email addresses were found in your input.'], 422);
        }

        $enrolledCount = 0;
        foreach ($emailsToEnroll as $email) {
            SubjectEnrollment::updateOrCreate([
                'subject_code' => $subjectCode,
                'email'        => $email,
            ]);
            $enrolledCount++;
        }

        return response()->json([
            'message'        => "Successfully enrolled {$enrolledCount} student(s) into {$subjectCode} for the entire week!",
            'enrolled_count' => $enrolledCount,
        ]);
    }

    /**
     * Remove a student from subject roster.
     */
    public function unenrollStudent(SubjectEnrollment $enrollment): JsonResponse
    {
        $subjectCode = $enrollment->subject_code;
        $email       = $enrollment->email;
        $enrollment->delete();

        return response()->json([
            'message' => "Removed {$email} from {$subjectCode}.",
        ]);
    }

    /**
     * Clear all enrolled students for a subject.
     */
    public function clearRoster(Request $request): JsonResponse
    {
        $request->validate(['subject_code' => 'required|string']);

        $subjectCode = $request->subject_code;
        $count       = SubjectEnrollment::where('subject_code', $subjectCode)->delete();

        return response()->json([
            'message' => "Cleared all {$count} enrolled student(s) from {$subjectCode}.",
            'count'   => $count,
        ]);
    }
}
