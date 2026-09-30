<?php

namespace App\Http\Controllers;

use App\Models\Computer;
use App\Models\Lab;
use App\Models\LabSession;
use App\Models\Schedule;
use App\Models\User;
use App\Models\Alert;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\SubjectEnrollment;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PersonnelController extends Controller
{
    private function flashToast(string $type, string $title, string $message): void
    {
        session()->flash('toast', [
            'type' => $type,
            'title' => $title,
            'message' => $message,
        ]);
    }

    /**
     * Central Activity Log helper
     */
    private function logActivity(
        string $category,
        string $description,
        string $severity = 'info',
        $subject = null,
        array $properties = []
    ): void {
        $activity = activity()
            ->useLog($category)
            ->withProperties(array_merge([
                'severity' => $severity,
                'ip_address' => request()->ip(),
            ], $properties));

        if ($subject) {
            $activity->performedOn($subject);
        }

        if (auth()->check()) {
            $activity->causedBy(auth()->user());
        }

        $activity->log($description);
    }

    /**
     * Staff Dashboard: Overview of all Labs
     */
    public function index()
    {
        // Clean up any PCs that lost Wi-Fi before rendering the page
        Computer::cleanupStaleSessions();

        $labs = Lab::withCount([
            'computers as total',
            'computers as occupied' => function ($query) {
                $query->where('status', 'active');
            },
        ])->get();

        return view('personnel.index', compact('labs'));
    }

    /**
     * Show a specific lab grid for assigning students to PCs
     */
    public function showLab(Lab $lab)
    {
        $currentTime = now()->format('H:i:s');
        $currentDay = now()->format('l');

        $currentSchedule = $lab->schedules()
            ->where('day', $currentDay)
            ->where('start_time', '<=', $currentTime)
            ->where('end_time', '>=', $currentTime)
            ->first();

        if ($currentSchedule) {

            if (
                auth()->id() !== $currentSchedule->user_id &&
                auth()->user()->role !== 'admin'
            ) {
                $this->logActivity(
                    'auth',
                    "Unauthorized attempt to access {$lab->name}, which is reserved for {$currentSchedule->user->name}.",
                    'danger',
                    $lab,
                    [
                        'lab_id' => $lab->id,
                        'scheduled_user_id' => $currentSchedule->user_id,
                        'attempted_by' => auth()->id(),
                    ]
                );

                $this->flashToast(
                    'danger',
                    'Access Denied',
                    "This lab is currently reserved for {$currentSchedule->user->name}."
                );

                return redirect()->route('personnel.index')
                    ->with(
                        'error',
                        "Access Denied: This lab is currently reserved for {$currentSchedule->user->name}."
                    );
            }
        }

        $computers = $lab->computers()
            ->with(['activeSession'])
            ->orderBy('pc_number')
            ->get();

        $schedules = $lab->schedules()
            ->with('user')
            ->where('day', $currentDay)
            ->orderBy('start_time')
            ->get();

        return view(
            'personnel.lab-view',
            compact(
                'lab',
                'computers',
                'schedules',
                'currentSchedule'
            )
        );
    }

    /**
     * Assign a student to a specific computer
     */
    public function assign(Request $request, Computer $computer)
    {
        $request->validate([
            'student_name' => 'required|string|max:255',
            'student_number' => 'required|string|max:50',
        ]);

        LabSession::create([
            'computer_id' => $computer->id,
            'student_name' => $request->student_name,
            'student_id_number' => $request->student_number,
            'time_in' => now(),
            'teacher_id' => auth()->id(),
            'lab_id' => $computer->lab_id,
        ]);

        $computer->update([
            'status' => 'active',
        ]);

        $this->logActivity(
            'lab_management',
            "Assigned {$request->student_name} to {$computer->pc_number}.",
            'info',
            $computer,
            [
                'computer_id' => $computer->id,
                'lab_id' => $computer->lab_id,
                'pc_number' => $computer->pc_number,
                'student_name' => $request->student_name,
                'student_number' => $request->student_number,
            ]
        );

        $this->flashToast(
            'success',
            'PC Assigned',
            "{$computer->pc_number} is now assigned to {$request->student_name}."
        );

        return back()->with(
            'success',
            "{$computer->pc_number} is now assigned to {$request->student_name}."
        );
    }

    /**
     * Release the PC and end the session
     */
    public function release(Computer $computer)
    {
        $session = LabSession::where('computer_id', $computer->id)
            ->whereNull('time_out')
            ->latest()
            ->first();

        if ($session) {

            $session->update([
                'time_out' => now(),
            ]);

            // Set to 'released' so the Python script triggers self.lock_ui_again()
            $computer->update([
                'status' => Computer::STATUS_RELEASED,
                'last_ping_at' => now(),
            ]);

            $this->logActivity(
                'lab_management',
                "Released {$computer->pc_number} from {$session->student_name}.",
                'info',
                $computer,
                [
                    'computer_id' => $computer->id,
                    'lab_id' => $computer->lab_id,
                    'pc_number' => $computer->pc_number,
                    'student_name' => $session->student_name,
                    'student_number' => $session->student_id_number,
                    'session_id' => $session->id,
                ]
            );

            $this->flashToast(
                'success',
                'PC Released',
                "PC {$computer->pc_number} released successfully."
            );

            return response()->json([
                'status' => 'success',
                'message' => "PC {$computer->pc_number} released successfully.",
                'toast' => [
                    'type' => 'success',
                    'title' => 'PC Released',
                    'message' => "PC {$computer->pc_number} released successfully.",
                ],
            ]);
        }

        // Even if no session was found, set to 'released' to force-lock the terminal
        $computer->update([
            'status' => Computer::STATUS_RELEASED,
            'last_ping_at' => now(),
        ]);

        $this->logActivity(
            'lab_management',
            "Attempted to release {$computer->pc_number}, but no active session was found.",
            'warning',
            $computer,
            [
                'computer_id' => $computer->id,
                'lab_id' => $computer->lab_id,
                'pc_number' => $computer->pc_number,
            ]
        );

        $this->flashToast(
            'warning',
            'No Active Session',
            'PC status reset to released, but no active session record was found.'
        );

        return response()->json([
            'status' => 'warning',
            'message' => 'PC status reset to released, but no active session record was found.',
            'toast' => [
                'type' => 'warning',
                'title' => 'No Active Session',
                'message' => 'PC status reset to released, but no active session record was found.',
            ],
        ]);
    }

    /**
     * Terminate ALL active PCs in a laboratory
     */
    public function terminateAll($labId)
    {
        $activePcs = Computer::where('lab_id', $labId)
            ->where('status', 'active')
            ->get();

        if ($activePcs->isEmpty()) {

            $this->logActivity(
                'incident_response',
                "Attempted to terminate active workstations in laboratory {$labId}, but none were active.",
                'info',
                null,
                [
                    'lab_id' => $labId,
                ]
            );

            return response()->json([
                'status' => 'info',
                'message' => 'No active workstations to terminate.'
            ]);
        }

        // 1. Set all active computers to 'released'
        Computer::where('lab_id', $labId)
            ->where('status', 'active')
            ->update([
                'status' => Computer::STATUS_RELEASED,
                'last_ping_at' => now(),
            ]);

        // 2. Close all active sessions in this lab
        LabSession::where('lab_id', $labId)
            ->whereNull('time_out')
            ->update([
                'time_out' => now(),
            ]);

        $message = "Terminated {$activePcs->count()} active workstation(s).";

        $this->logActivity(
            'incident_response',
            "Terminated {$activePcs->count()} active workstation(s) in laboratory {$labId}.",
            'warning',
            null,
            [
                'lab_id' => $labId,
                'terminated_count' => $activePcs->count(),
                'computer_ids' => $activePcs->pluck('id')->values()->all(),
            ]
        );

        $this->flashToast(
            'success',
            'All Terminals Released',
            $message
        );

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'toast' => [
                'type' => 'success',
                'title' => 'All Terminals Released',
                'message' => $message,
            ]
        ]);
    }

    /**
     * Duplicate of index for "Labs Overview" page
     */
    public function labs()
    {
        // Clean up any PCs that lost Wi-Fi before rendering the page
        Computer::cleanupStaleSessions();

        $labs = Lab::withCount([
            'computers as total',
            'computers as occupied' => function ($query) {
                $query->where('status', 'active');
            },
        ])->get();

        return view('personnel.labs-overview', compact('labs'));
    }

    public function fullSchedule()
    {
        $labs = Lab::with(['schedules.user'])->get();

        $days = [
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday',
            'Sunday'
        ];

        return view(
            'personnel.full-schedule',
            compact('labs', 'days')
        );
    }

    /**
     * View Session History
     */
    public function sessionHistory(Request $request)
    {
        $query = LabSession::with([
            'computer.lab',
            'teacher',
            'checklist'
        ])
            ->latest('id')
            ->whereNotNull('time_out');

        // Non-admins only see sessions for their classes
        if (auth()->user()->role !== 'admin') {
            $query->where(
                'teacher_id',
                auth()->id()
            );
        }

        // 1. Filter by Student Name or Student ID Number
        if ($request->filled('student_name')) {

            $search = trim(
                $request->student_name
            );

            $query->where(
                function ($q) use ($search) {

                    $q->where(
                        'student_name',
                        'like',
                        "%{$search}%"
                    )
                        ->orWhere(
                            'student_id_number',
                            'like',
                            "%{$search}%"
                        );
                }
            );
        }

        // 2. Filter by Terminal ID / PC Number
        if ($request->filled('pc_number')) {

            $pcInput =
                trim($request->pc_number);

            $query->whereHas(
                'computer',
                function ($q) use ($pcInput) {

                    $cleanNum =
                        preg_replace(
                            '/\D/',
                            '',
                            $pcInput
                        );

                    $q->where(
                        'pc_number',
                        'like',
                        "%{$pcInput}%"
                    );

                    if (!empty($cleanNum)) {

                        $num =
                            (int) $cleanNum;

                        $q->orWhere(
                            'pc_number',
                            'like',
                            "%PC-{$num}%"
                        )
                            ->orWhere(
                                'pc_number',
                                'like',
                                "%PC-0{$num}%"
                            )
                            ->orWhere(
                                'pc_number',
                                'like',
                                "%PC {$num}%"
                            )
                            ->orWhere(
                                'pc_number',
                                'like',
                                "%PC 0{$num}%"
                            );
                    }
                }
            );
        }

        // 3. Filter by Date
        if ($request->filled('date')) {
            $query->whereDate(
                'time_in',
                $request->date
            );
        }

        // 4. Retain filters across pagination pages
        $sessions =
            $query->paginate(15)
            ->withQueryString();

        return view(
            'personnel.sessions',
            compact('sessions')
        );
    }

    /**
     * View Alerts/Maintenance History
     */
    public function alertHistory(Request $request)
    {
        $query =
            Alert::with([
                'computer.lab',
                'reporter'
            ])->latest();

        // 1. Filter by PC Number
        if ($request->filled('pc_number')) {

            $pcInput =
                trim($request->pc_number);

            $query->whereHas(
                'computer',
                function ($q) use ($pcInput) {

                    $cleanNum =
                        preg_replace(
                            '/\D/',
                            '',
                            $pcInput
                        );

                    $q->where(
                        'pc_number',
                        'like',
                        "%{$pcInput}%"
                    );

                    if (!empty($cleanNum)) {

                        $num =
                            (int) $cleanNum;

                        $q->orWhere(
                            'pc_number',
                            'like',
                            "%PC-{$num}%"
                        )
                            ->orWhere(
                                'pc_number',
                                'like',
                                "%PC-0{$num}%"
                            )
                            ->orWhere(
                                'pc_number',
                                'like',
                                "%PC {$num}%"
                            )
                            ->orWhere(
                                'pc_number',
                                'like',
                                "%PC 0{$num}%"
                            );
                    }
                }
            );
        }

        // 2. Filter by Date Reported
        if ($request->filled('date')) {
            $query->whereDate(
                'created_at',
                $request->date
            );
        }

        // 3. Filter by Status
        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->status
            );
        }

        // Accurate counts for header stats
        $totalReports =
            Alert::count();

        $unresolvedCount =
            Alert::where(
                'status',
                'pending'
            )->count();

        // Paginate and retain active filter query strings
        $alerts =
            $query->paginate(15)
            ->withQueryString();

        return view(
            'personnel.alerts',
            compact(
                'alerts',
                'totalReports',
                'unresolvedCount'
            )
        );
    }

    /**
     * Mark an alert as dismissed/false alarm.
     */
    public function discardAlert(
        Request $request,
        $id
    ) {
        $alert = Alert::find($id);

        if (!$alert) {

            if ($request->expectsJson()) {

                $this->logActivity(
                    'incident_response',
                    "Attempted to dismiss alert #{$id}, but the alert record was not found.",
                    'warning',
                    null,
                    [
                        'alert_id' => $id,
                    ]
                );

                return response()->json([
                    'status' => 'error',
                    'message' => 'Alert not found.'
                ], 404);
            }

            $this->logActivity(
                'incident_response',
                "Attempted to dismiss alert #{$id}, but the alert record was not found.",
                'warning',
                null,
                [
                    'alert_id' => $id,
                ]
            );

            $this->flashToast(
                'danger',
                'Not Found',
                'Alert record could not be found.'
            );

            return back()->with(
                'error',
                'Alert not found.'
            );
        }

        $alert->update([
            'status' => 'discarded',
            'resolved_at' => now(),
        ]);

        $this->logActivity(
            'incident_response',
            "Dismissed security alert #{$alert->id} as a false alarm.",
            'warning',
            $alert,
            [
                'alert_id' => $alert->id,
                'computer_id' => $alert->computer_id,
                'lab_id' => $alert->lab_id,
                'issue_type' => $alert->issue_type,
            ]
        );

        $this->flashToast(
            'success',
            'Alert Discarded',
            'The alert has been successfully dismissed as a false alarm.'
        );

        return back()->with(
            'success',
            'Alert successfully discarded as a false alarm.'
        );
    }

    /**
     * EXPORTING THE ATTENDANCE REPORT AS CSV
     * Reads a custom date if passed by the Master Schedule grid view layout
     */
    public function exportScheduleAttendance(
        Request $request,
        $id
    ) {
        $schedule =
            Schedule::findOrFail($id);

        if (
            auth()->id() !== $schedule->user_id &&
            auth()->user()->role !== 'admin'
        ) {

            $this->logActivity(
                'auth',
                "Unauthorized attendance export attempt for {$schedule->subject_code}.",
                'danger',
                $schedule,
                [
                    'schedule_id' => $schedule->id,
                    'lab_id' => $schedule->lab_id,
                    'attempted_by' => auth()->id(),
                ]
            );

            abort(
                403,
                'Unauthorized action.'
            );
        }

        $targetDate =
            $request->query(
                'date',
                now()->toDateString()
            );

        $sessions =
            LabSession::where(
                'lab_id',
                $schedule->lab_id
            )
            ->where(
                'teacher_id',
                $schedule->user_id
            )
            ->whereDate(
                'time_in',
                $targetDate
            )
            ->whereTime(
                'time_in',
                '>=',
                $schedule->start_time
            )
            ->whereTime(
                'time_in',
                '<=',
                $schedule->end_time
            )
            ->get();

        if ($sessions->isEmpty()) {

            $this->logActivity(
                'system',
                "Attendance export requested for {$schedule->subject_code}, but no attendance records were found.",
                'warning',
                $schedule,
                [
                    'schedule_id' => $schedule->id,
                    'lab_id' => $schedule->lab_id,
                    'date' => $targetDate,
                ]
            );

            $this->flashToast(
                'danger',
                'No Attendance Found',
                'No attendance records found for this session date.'
            );

            return back()->with(
                'error',
                'No attendance records found for this session date.'
            );
        }

        $fileName =
            "Attendance_{$schedule->subject_code}_{$targetDate}.csv";

        $this->logActivity(
            'system',
            "Exported attendance report for {$schedule->subject_code}.",
            'info',
            $schedule,
            [
                'schedule_id' => $schedule->id,
                'lab_id' => $schedule->lab_id,
                'date' => $targetDate,
                'record_count' => $sessions->count(),
            ]
        );

        return response()->streamDownload(
            function () use (
                $sessions,
                $schedule,
                $targetDate
            ) {

                $file =
                    fopen(
                        'php://output',
                        'w'
                    );

                fputcsv(
                    $file,
                    [
                        'LABGUARD SYSTEM - ATTENDANCE REPORT'
                    ]
                );

                fputcsv(
                    $file,
                    [
                        'Subject',
                        $schedule->subject_code
                    ]
                );

                fputcsv(
                    $file,
                    [
                        'Instructor',
                        $schedule->user->name
                    ]
                );

                fputcsv(
                    $file,
                    [
                        'Session Date',
                        Carbon::parse(
                            $targetDate
                        )->format('M d, Y')
                    ]
                );

                fputcsv(
                    $file,
                    []
                );

                fputcsv(
                    $file,
                    [
                        'STUDENT NAME',
                        'STUDENT NUMBER',
                        'TIME IN',
                        'TIME OUT',
                        'DURATION (MINS)'
                    ]
                );

                foreach ($sessions as $s) {

                    $timeIn =
                        $s->time_in instanceof Carbon
                        ? $s->time_in
                        : Carbon::parse(
                            $s->time_in
                        );

                    $timeOut = null;

                    $duration =
                        'Still Logged In';

                    if ($s->time_out) {

                        $timeOut =
                            $s->time_out instanceof Carbon
                            ? $s->time_out
                            : Carbon::parse(
                                $s->time_out
                            );

                        $duration =
                            $timeIn->diffInMinutes(
                                $timeOut
                            ) . ' mins';

                        $timeOutFormat =
                            $timeOut->format(
                                'h:i A'
                            );
                    } else {

                        $timeOutFormat =
                            'N/A';
                    }

                    fputcsv(
                        $file,
                        [
                            strtoupper(
                                $s->student_name
                            ),
                            $s->student_id_number,
                            $timeIn->format(
                                'h:i A'
                            ),
                            $timeOutFormat,
                            $duration,
                        ]
                    );
                }

                fclose($file);
            },
            $fileName
        );
    }

    // ==========================================================
    // ENROLLMENT OF STUDENTS TO SUBJECTS
    // ==========================================================

    public function enrollStudent(
        Request $request
    ) {
        $request->validate([
            'subject_code' => 'required|string',
            'emails' => 'nullable|string',
            'file' => 'nullable|file|mimes:csv,txt|max:5120',
        ]);

        $subjectCode =
            trim(
                $request->subject_code
            );

        $emailsToEnroll = [];

        // Get emails from textarea
        if ($request->filled('emails')) {

            preg_match_all(
                '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/',
                $request->emails,
                $matches
            );

            $emailsToEnroll =
                array_merge(
                    $emailsToEnroll,
                    $matches[0] ?? []
                );
        }

        // Get emails from uploaded file
        if ($request->hasFile('file')) {

            $file =
                $request->file('file');

            $content =
                file_get_contents(
                    $file->getRealPath()
                );

            preg_match_all(
                '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/',
                $content,
                $matches
            );

            $emailsToEnroll =
                array_merge(
                    $emailsToEnroll,
                    $matches[0] ?? []
                );
        }

        // Clean and remove duplicates
        $emailsToEnroll =
            array_unique(
                array_map(
                    'strtolower',
                    array_map(
                        'trim',
                        $emailsToEnroll
                    )
                )
            );

        // No valid emails found
        if (empty($emailsToEnroll)) {

            $this->logActivity(
                'user_management',
                "Attempted to enroll students into {$subjectCode}, but no valid email addresses were found.",
                'warning',
                null,
                [
                    'subject_code' => $subjectCode,
                ]
            );

            return back()->with(
                'toast',
                [
                    'type' => 'warning',
                    'title' => 'No Students Found',
                    'message' => 'No valid email addresses were found in your input.',
                ]
            );
        }

        $enrolledCount = 0;

        foreach ($emailsToEnroll as $email) {

            SubjectEnrollment::updateOrCreate(
                [
                    'subject_code' => $subjectCode,
                    'email' => $email,
                ]
            );

            $enrolledCount++;
        }

        $this->logActivity(
            'user_management',
            "Enrolled {$enrolledCount} student(s) into {$subjectCode}.",
            'info',
            null,
            [
                'subject_code' => $subjectCode,
                'enrolled_count' => $enrolledCount,
            ]
        );

        return back()->with(
            'toast',
            [
                'type' => 'success',
                'title' => 'Roster Authorized',
                'message' => "Successfully enrolled {$enrolledCount} student(s) into {$subjectCode} for the entire week!",
            ]
        );
    }

    public function unenrollStudent(
        SubjectEnrollment $enrollment
    ) {
        $subjectCode =
            $enrollment->subject_code;

        $email =
            $enrollment->email;

        $this->logActivity(
            'user_management',
            "Removed {$email} from {$subjectCode}.",
            'warning',
            $enrollment,
            [
                'enrollment_id' => $enrollment->id,
                'subject_code' => $subjectCode,
                'email' => $email,
            ]
        );

        $enrollment->delete();

        return back()->with(
            'toast',
            [
                'type' => 'success',
                'title' => 'Student Removed',
                'message' => "Removed {$email} from {$subjectCode}.",
            ]
        );
    }

    public function clearRoster(
        Request $request
    ) {
        $request->validate([
            'subject_code' => 'required|string',
        ]);

        $subjectCode =
            trim(
                $request->subject_code
            );

        $count =
            SubjectEnrollment::where(
                'subject_code',
                $subjectCode
            )->delete();

        $this->logActivity(
            'user_management',
            "Cleared {$count} student enrollment(s) from {$subjectCode}.",
            'warning',
            null,
            [
                'subject_code' => $subjectCode,
                'cleared_count' => $count,
            ]
        );

        return back()->with(
            'toast',
            [
                'type' => 'success',
                'title' => 'Roster Cleared',
                'message' => "Cleared all {$count} enrolled student(s) from {$subjectCode}.",
            ]
        );
    }
}
