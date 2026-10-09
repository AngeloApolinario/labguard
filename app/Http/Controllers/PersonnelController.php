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
                    ->with('error', "Access Denied: This lab is currently reserved for {$currentSchedule->user->name}.");
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

        return view('personnel.lab-view', compact('lab', 'computers', 'schedules', 'currentSchedule'));
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

        return back()->with('success', "{$computer->pc_number} is now assigned to {$request->student_name}.");
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
                ['lab_id' => $labId]
            );

            return response()->json([
                'status' => 'info',
                'message' => 'No active workstations to terminate.'
            ]);
        }

        Computer::where('lab_id', $labId)
            ->where('status', 'active')
            ->update([
                'status' => Computer::STATUS_RELEASED,
                'last_ping_at' => now(),
            ]);

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

        $this->flashToast('success', 'All Computers Released', $message);

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'toast' => [
                'type' => 'success',
                'title' => 'All Computers Released',
                'message' => $message,
            ]
        ]);
    }

    /**
     * Duplicate of index for "Labs Overview" page
     */
    public function labs()
    {
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

        return view('personnel.full-schedule', compact('labs', 'days'));
    }

    /**
     * View Session History
     */
    public function sessionHistory(Request $request)
    {
        $currentUser = auth()->user();
        $userRole    = strtolower(trim($currentUser->role ?? ''));

        $query = LabSession::with(['computer.lab', 'lab', 'teacher', 'checklist'])
            ->latest('time_in');

        // STRICT RBAC SCOPING:
        // Only 'admin' and 'super-admin' have global visibility.
        // Personnel (teachers) will ONLY see their own sessions!
        $isGlobalAdmin = in_array($userRole, ['admin', 'super-admin', 'super_admin']);

        if (! $isGlobalAdmin) {
            $query->where('teacher_id', $currentUser->id);
        }

        if ($request->filled('student_name')) {
            $query->where('student_name', 'like', '%' . trim($request->student_name) . '%');
        }

        if ($request->filled('date')) {
            $query->whereDate('time_in', $request->date);
        }

        if ($request->filled('lab_id') || $request->filled('pc_number')) {
            $query->where(function ($q) use ($request) {
                if ($request->filled('lab_id')) {
                    $q->where('lab_id', $request->lab_id)
                        ->orWhereHas('computer', fn($c) => $c->where('lab_id', $request->lab_id));
                }

                if ($request->filled('pc_number')) {
                    $cleanPc = trim($request->pc_number);
                    $q->whereHas('computer', fn($c) => $c->where('pc_number', 'like', "%{$cleanPc}%"));
                }
            });
        }

        $metricsScope = LabSession::query();
        if (! $isGlobalAdmin) {
            $metricsScope->where('teacher_id', $currentUser->id);
        }

        $totalSessions       = (clone $metricsScope)->count();
        $activeSessionsCount = (clone $metricsScope)->whereNull('time_out')->count();

        $sessions = $query->paginate(15)->withQueryString();
        $allLabs  = Lab::orderBy('name')->get();

        return view('dashboard.sessions.index', compact(
            'sessions',
            'allLabs',
            'totalSessions',
            'activeSessionsCount'
        ));
    }

    /**
     * View Alerts/Maintenance History
     */
    public function alerthistory(Request $request)
    {
        $query = Alert::with(['computer.lab', 'reporter']);

        if ($request->filled('lab_id') || $request->filled('pc_number')) {
            $query->whereHas('computer', function ($q) use ($request) {
                if ($request->filled('lab_id')) {
                    $q->where('lab_id', $request->lab_id);
                }

                if ($request->filled('pc_number')) {
                    $cleanPc = trim($request->pc_number);
                    $q->where('pc_number', 'LIKE', "%{$cleanPc}%");
                }
            });
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $totalReports = Alert::count();
        $unresolvedCount = Alert::where('status', 'pending')->count();

        $alerts = $query->latest()->paginate(10)->withQueryString();
        $allLabs = Lab::orderBy('name')->get();

        return view('personnel.alerts', compact('alerts', 'allLabs', 'totalReports', 'unresolvedCount'));
    }

    /**
     * Mark an alert as dismissed/false alarm.
     */
    public function discardAlert(Request $request, $id)
    {
        $alert = Alert::find($id);

        if (!$alert) {
            if ($request->expectsJson()) {
                $this->logActivity('incident_response', "Attempted to dismiss alert #{$id}, but the alert record was not found.", 'warning', null, ['alert_id' => $id]);
                return response()->json(['status' => 'error', 'message' => 'Alert not found.'], 404);
            }

            $this->logActivity('incident_response', "Attempted to dismiss alert #{$id}, but the alert record was not found.", 'warning', null, ['alert_id' => $id]);
            $this->flashToast('danger', 'Not Found', 'Alert record could not be found.');
            return back()->with('error', 'Alert not found.');
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

        $this->flashToast('success', 'Alert Discarded', 'The alert has been successfully dismissed as a false alarm.');
        return back()->with('success', 'Alert successfully discarded as a false alarm.');
    }
    public function manualStore(Request $request)
    {
        $validated = $request->validate([
            'lab_id'      => 'required|exists:labs,id',
            'computer_id' => 'required|exists:computers,id',
            'reported_by' => 'required|exists:users,id',
            'issue_type'  => 'required|string',
            'remarks'     => 'required|string|max:1000',
        ]);

        Alert::create([
            'computer_id' => $validated['computer_id'],
            'lab_id'      => $validated['lab_id'],
            'reported_by' => $validated['reported_by'],
            'issue_type'  => $validated['issue_type'],
            'remarks'     => $validated['remarks'],
            'status'      => 'pending',
        ]);

        // Trigger your toast helper
        $this->flashToast('success', 'Incident Logged', 'Manual incident ticket has been filed successfully.');

        return back();
    }

    // ==========================================================
    // STYLED SPREADSHEET ENGINE HELPER (.xls)
    // ==========================================================

    /**
     * Streams a styled HTML-based Excel spreadsheet (.xls) complete
     * with dark-slate headers, gold branding, and colored status cells.
     */
    private function streamStyledSpreadsheet(
        string $filename,
        string $reportTitle,
        string $subtitle,
        array $metadata,
        array $headers,
        array $rows
    ) {
        $headersList = [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () use ($reportTitle, $subtitle, $metadata, $headers, $rows) {
            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            echo '<head>';
            echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
            echo '<style>';
            echo 'body { font-family: "Segoe UI", Arial, sans-serif; background-color: #f8fafc; }';
            echo '.banner-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }';
            echo '.banner-cell { background-color: #0f172a; padding: 18px; color: #ffffff; border-radius: 8px; }';
            echo '.main-title { font-size: 16pt; font-weight: bold; color: #D4AF37; letter-spacing: 1px; }';
            echo '.sub-title { font-size: 10pt; color: #94a3b8; margin-top: 4px; }';
            echo '.meta-box { background-color: #1e293b; padding: 10px 14px; margin-top: 10px; font-size: 9pt; color: #cbd5e1; border-left: 3px solid #D4AF37; }';
            echo '.data-table { width: 100%; border-collapse: collapse; margin-top: 12px; }';
            echo '.data-table th { background-color: #0f172a; color: #D4AF37; font-size: 10pt; font-weight: bold; padding: 10px 12px; border: 1px solid #334155; text-align: left; }';
            echo '.data-table td { padding: 9px 12px; font-size: 9pt; border: 1px solid #cbd5e1; color: #1e293b; vertical-align: middle; }';
            echo '.row-even { background-color: #ffffff; }';
            echo '.row-odd  { background-color: #f8fafc; }';
            echo '.badge-pass    { background-color: #dcfce7; color: #15803d; font-weight: bold; text-align: center; border-radius: 4px; }';
            echo '.badge-fail    { background-color: #fee2e2; color: #b91c1c; font-weight: bold; text-align: center; border-radius: 4px; }';
            echo '.badge-pending { background-color: #fef3c7; color: #b45309; font-weight: bold; text-align: center; border-radius: 4px; }';
            echo '.badge-neutral { background-color: #f1f5f9; color: #475569; font-weight: bold; text-align: center; border-radius: 4px; }';
            echo '</style>';
            echo '</head>';
            echo '<body>';

            // Top Header Banner
            echo '<table class="banner-table">';
            echo '<tr><td class="banner-cell" colspan="' . count($headers) . '">';
            echo '<div class="main-title">' . htmlspecialchars(strtoupper($reportTitle)) . '</div>';
            echo '<div class="sub-title">' . htmlspecialchars($subtitle) . '</div>';
            echo '<div class="meta-box">';
            foreach ($metadata as $label => $val) {
                echo '<strong>' . htmlspecialchars($label) . ':</strong> ' . htmlspecialchars($val) . ' &nbsp;|&nbsp; ';
            }
            echo '<strong>Generated By:</strong> ' . htmlspecialchars(auth()->user()->name ?? 'Personnel') . ' (' . now()->format('M d, Y h:i A') . ')';
            echo '</div>';
            echo '</td></tr>';
            echo '</table>';

            // Main Data Table
            echo '<table class="data-table">';
            echo '<thead><tr>';
            foreach ($headers as $col) {
                echo '<th>' . htmlspecialchars($col) . '</th>';
            }
            echo '</tr></thead>';
            echo '<tbody>';

            foreach ($rows as $index => $row) {
                $rowClass = ($index % 2 === 0) ? 'row-even' : 'row-odd';
                echo '<tr class="' . $rowClass . '">';
                foreach ($row as $cell) {
                    $cellClass = '';
                    $upper = strtoupper(trim((string)$cell));

                    if (in_array($upper, ['PASS', 'YES', 'RESOLVED', 'ACTIVE', '100%'])) {
                        $cellClass = 'class="badge-pass"';
                    } elseif (in_array($upper, ['FAIL', 'NO', 'DISCARDED', 'MAINTENANCE', 'CORRUPT'])) {
                        $cellClass = 'class="badge-fail"';
                    } elseif (in_array($upper, ['PENDING', 'FLAGGED', 'STILL LOGGED IN'])) {
                        $cellClass = 'class="badge-pending"';
                    } elseif (in_array($upper, ['N/A', '--', 'UNLOGGED', 'NONE'])) {
                        $cellClass = 'class="badge-neutral"';
                    }

                    echo '<td ' . $cellClass . '>' . htmlspecialchars((string)$cell) . '</td>';
                }
                echo '</tr>';
            }

            echo '</tbody></table>';
            echo '</body></html>';
        }, 200, $headersList);
    }

    /**
     * EXPORTING THE ATTENDANCE REPORT AS A STYLED SPREADSHEET (.xls)
     * Formatted with gold/slate branding and colored status cells.
     */
    public function exportScheduleAttendance(Request $request, $id)
    {
        $schedule = Schedule::with(['lab', 'user'])->findOrFail($id);

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
                    'lab_id'      => $schedule->lab_id,
                    'attempted_by' => auth()->id(),
                ]
            );

            abort(403, 'Unauthorized action.');
        }

        $targetDate = $request->query('date', now()->toDateString());

        $sessions = LabSession::with('computer')
            ->where('lab_id', $schedule->lab_id)
            ->where('teacher_id', $schedule->user_id)
            ->whereDate('time_in', $targetDate)
            ->whereTime('time_in', '>=', $schedule->start_time)
            ->whereTime('time_in', '<=', $schedule->end_time)
            ->orderBy('time_in')
            ->get();

        if ($sessions->isEmpty()) {
            $this->logActivity(
                'system',
                "Attendance export requested for {$schedule->subject_code}, but no attendance records were found.",
                'warning',
                $schedule,
                [
                    'schedule_id' => $schedule->id,
                    'lab_id'      => $schedule->lab_id,
                    'date'        => $targetDate,
                ]
            );

            $this->flashToast('danger', 'No Attendance Found', 'No attendance records found for this session date.');
            return back()->with('error', 'No attendance records found for this session date.');
        }

        $fileName = "Attendance_{$schedule->subject_code}_{$targetDate}.xls";

        $metadata = [
            'Subject / Course'   => $schedule->subject_code,
            'Supervising Faculty' => $schedule->user->name ?? 'Instructor',
            'Laboratory Room'    => $schedule->lab->name ?? 'N/A',
            'Session Date'       => Carbon::parse($targetDate)->format('M d, Y') . " (" . $schedule->day . ")",
            'Allocated Window'   => Carbon::parse($schedule->start_time)->format('h:i A') . ' — ' . Carbon::parse($schedule->end_time)->format('h:i A'),
            'Total Attendees'    => $sessions->count(),
        ];

        $headers = [
            '#',
            'Student Name',
            'Student ID / Number',
            'Workstation PC',
            'Time In',
            'Time Out',
            'Session Duration',
        ];

        $rows = [];
        foreach ($sessions as $i => $s) {
            $timeIn = $s->time_in instanceof Carbon ? $s->time_in : Carbon::parse($s->time_in);
            $timeOutFormat = 'STILL LOGGED IN';
            $duration = 'Still Logged In';

            if ($s->time_out) {
                $timeOut = $s->time_out instanceof Carbon ? $s->time_out : Carbon::parse($s->time_out);
                $timeOutFormat = $timeOut->format('h:i A');
                $duration = $timeIn->diffInMinutes($timeOut) . ' mins';
            }

            $rows[] = [
                $i + 1,
                strtoupper($s->student_name),
                $s->student_id_number,
                $s->computer->pc_number ?? 'PC-??',
                $timeIn->format('h:i A'),
                $timeOutFormat,
                $duration,
            ];
        }

        $this->logActivity(
            'system',
            "Exported attendance spreadsheet for {$schedule->subject_code}.",
            'info',
            $schedule,
            [
                'schedule_id'   => $schedule->id,
                'lab_id'        => $schedule->lab_id,
                'date'          => $targetDate,
                'attendees'     => $sessions->count(),
            ]
        );

        return $this->streamStyledSpreadsheet(
            $fileName,
            'Academic Class Attendance Ledger',
            'PHINMA Araullo University &bull; Computer Laboratory Management System',
            $metadata,
            $headers,
            $rows
        );
    }

    // ==========================================================
    // ENROLLMENT OF STUDENTS TO SUBJECTS
    // ==========================================================

    public function enrollStudent(Request $request)
    {
        $request->validate([
            'subject_code' => 'required|string',
            'emails'       => 'nullable|string',
            'file'         => 'nullable|file|mimes:csv,txt|max:5120',
        ]);

        $subjectCode = trim($request->subject_code);
        $emailsToEnroll = [];

        if ($request->filled('emails')) {
            preg_match_all('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $request->emails, $matches);
            $emailsToEnroll = array_merge($emailsToEnroll, $matches[0] ?? []);
        }

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $content = file_get_contents($file->getRealPath());
            preg_match_all('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $content, $matches);
            $emailsToEnroll = array_merge($emailsToEnroll, $matches[0] ?? []);
        }

        $emailsToEnroll = array_unique(array_map('strtolower', array_map('trim', $emailsToEnroll)));

        if (empty($emailsToEnroll)) {
            $this->logActivity(
                'user_management',
                "Attempted to enroll students into {$subjectCode}, but no valid email addresses were found.",
                'warning',
                null,
                ['subject_code' => $subjectCode]
            );

            return back()->with('toast', [
                'type'    => 'warning',
                'title'   => 'No Students Found',
                'message' => 'No valid email addresses were found in your input.',
            ]);
        }

        $enrolledCount = 0;
        foreach ($emailsToEnroll as $email) {
            SubjectEnrollment::updateOrCreate([
                'subject_code' => $subjectCode,
                'email'        => $email,
            ]);
            $enrolledCount++;
        }

        $this->logActivity(
            'user_management',
            "Enrolled {$enrolledCount} student(s) into {$subjectCode}.",
            'info',
            null,
            [
                'subject_code'   => $subjectCode,
                'enrolled_count' => $enrolledCount,
            ]
        );

        return back()->with('toast', [
            'type'    => 'success',
            'title'   => 'Roster Authorized',
            'message' => "Successfully enrolled {$enrolledCount} student(s) into {$subjectCode} for the entire week!",
        ]);
    }

    public function unenrollStudent(SubjectEnrollment $enrollment)
    {
        $subjectCode = $enrollment->subject_code;
        $email       = $enrollment->email;

        $this->logActivity(
            'user_management',
            "Removed {$email} from {$subjectCode}.",
            'warning',
            $enrollment,
            [
                'enrollment_id' => $enrollment->id,
                'subject_code'  => $subjectCode,
                'email'         => $email,
            ]
        );

        $enrollment->delete();

        return back()->with('toast', [
            'type'    => 'success',
            'title'   => 'Student Removed',
            'message' => "Removed {$email} from {$subjectCode}.",
        ]);
    }

    public function clearRoster(Request $request)
    {
        $request->validate([
            'subject_code' => 'required|string',
        ]);

        $subjectCode = trim($request->subject_code);

        $count = SubjectEnrollment::where('subject_code', $subjectCode)->delete();

        $this->logActivity(
            'user_management',
            "Cleared {$count} student enrollment(s) from {$subjectCode}.",
            'warning',
            null,
            [
                'subject_code'  => $subjectCode,
                'cleared_count' => $count,
            ]
        );

        return back()->with('toast', [
            'type'    => 'success',
            'title'   => 'Roster Cleared',
            'message' => "Cleared all {$count} enrolled student(s) from {$subjectCode}.",
        ]);
    }
}
