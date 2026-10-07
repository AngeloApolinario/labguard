<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Computer;
use App\Models\Lab;
use App\Models\LabSession;
use App\Models\Schedule;
use App\Models\User;
use App\Models\SessionChecklist;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Validation\Rules\Password;

class SuperAdminController extends Controller
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
     * Central Activity Log helper.
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

    // ==========================================================
    // DASHBOARD OVERVIEW
    // ==========================================================

    public function index()
    {
        Computer::cleanupStaleSessions();

        $totalUsers = User::count();
        $activeSessionsCount = LabSession::whereNull('time_out')->count();
        $pendingAlertsCount = Alert::where('status', 'pending')->count();

        $labs = Lab::with([
            'computers' => function ($query) {
                $query->select('id', 'lab_id', 'pc_number', 'status');
            },
        ])
            ->withCount([
                'computers as total_pcs',
                'computers as active_pcs' => function ($query) {
                    $query->where('status', 'active');
                },
                'computers as maintenance_pcs' => function ($query) {
                    $query->where('status', 'maintenance');
                },
            ])
            ->orderBy('name')
            ->get();

        $totalLabs = $labs->count();

        $activeSessionsPerLab = LabSession::whereNull('time_out')
            ->select('lab_id', DB::raw('count(*) as active_count'))
            ->groupBy('lab_id')
            ->pluck('active_count', 'lab_id')
            ->all();

        $labUtilization = [];

        foreach ($labs as $lab) {
            $currentActiveCount = $activeSessionsPerLab[$lab->id] ?? 0;
            $capacity = $lab->capacity > 0 ? $lab->capacity : max(1, $lab->total_pcs);
            $percentage = $capacity > 0 ? ($currentActiveCount / $capacity) * 100 : 0;

            $labUtilization[$lab->id] = [
                'id'              => $lab->id,
                'name'            => $lab->name,
                'status'          => $lab->status,
                'percent'         => min(100, round($percentage)),
                'active'          => $currentActiveCount,
                'capacity'        => $capacity,
                'total_pcs'       => $lab->total_pcs,
                'active_pcs'      => $lab->active_pcs,
                'maintenance_pcs' => $lab->maintenance_pcs,
                'computers'       => $lab->computers,
            ];
        }

        $recentAlerts = Alert::with(['lab', 'computer.lab', 'reporter'])
            ->latest()
            ->take(5)
            ->get();

        return view('super-admin.index', [
            'totalUsers'     => $totalUsers,
            'activeSessions' => $activeSessionsCount,
            'alerts'         => $pendingAlertsCount,
            'totalLabs'      => $totalLabs,
            'labUtilization' => $labUtilization,
            'recentAlerts'   => $recentAlerts,
        ]);
    }

    public function security()
    {
        $securityStats = [
            'score'           => '92/100',
            'threats'         => 3,
            'vulnerabilities' => 5,
        ];

        $alerts = [
            [
                'type'       => 'critical',
                'title'      => 'Multiple Failed Login Attempts',
                'badge'      => 'Critical',
                'desc'       => 'IP 192.168.1.45 detected 5 failed login attempts in the last 10 minutes',
                'time'       => '2 minutes ago',
                'action'     => 'Block IP',
                'icon'       => 'heroicon-o-exclamation-triangle',
                'iconColor'  => 'text-rose-500',
                'bgColor'    => 'bg-rose-50',
            ],
            [
                'type'       => 'warning',
                'title'      => 'Unusual Access Pattern',
                'badge'      => 'Warning',
                'desc'       => 'User account accessed from a new location: Tokyo, Japan',
                'time'       => '1 hour ago',
                'action'     => 'Review',
                'icon'       => 'heroicon-o-exclamation-circle',
                'iconColor'  => 'text-amber-500',
                'bgColor'    => 'bg-amber-50',
            ],
            [
                'type'       => 'info',
                'title'      => 'System Update Available',
                'badge'      => 'Info',
                'desc'       => 'Security patch v2.5.3 available for system kernel',
                'time'       => '1 hour ago',
                'action'     => 'Update',
                'icon'       => 'heroicon-o-information-circle',
                'iconColor'  => 'text-blue-500',
                'bgColor'    => 'bg-blue-50',
            ],
        ];

        return view('super-admin.security', compact('securityStats', 'alerts'));
    }

    public function settings()
    {
        $settings = [
            'system_name'     => 'LabGuard - Computer Lab Management',
            'institution'     => 'Au University',
            'backup_time'     => '02:00',
            'session_timeout' => '30',
            'system_email'    => 'admin@labguard.edu',
        ];

        return view('super-admin.settings', compact('settings'));
    }

    // ==========================================================
    // USER MANAGEMENT
    // ==========================================================

    public function userManagement()
    {
        $users = User::orderBy('role', 'asc')
            ->orderBy('name', 'asc')
            ->paginate(15);

        return view('super-admin.user-management', compact('users'));
    }

    public function storeUser(Request $request)
    {
        $request->validate(
            [
                'name'           => ['required', 'string', 'max:255'],
                'email'          => ['required', 'string', 'email', 'max:255', 'ends_with:@phinmaed.com', 'unique:users'],
                'password'       => ['required', 'string', Password::min(8)->mixedCase()->numbers()->symbols()],
                'role'           => ['required', 'in:student,personnel,admin,super-admin'],
                'student_number' => ['required', 'string', 'unique:users', 'regex:/^01-[0-9]{4}-[0-9]{6}$/'],
                'phone'          => ['required', 'string', 'regex:/^09[0-9]{9}$/'],
            ],
            [
                'email.ends_with'      => 'Please use an official PHINMA organization email address.',
                'student_number.regex' => 'The ID must follow the AU format: 01-XXXX-XXXXXX.',
                'phone.regex'          => 'Please provide a valid 11-digit mobile number.',
            ]
        );

        $user = User::create([
            'name'              => $request->name,
            'email'             => $request->email,
            'password'          => Hash::make($request->password),
            'role'              => $request->role,
            'student_number'    => $request->student_number,
            'phone'             => $request->phone,
            'email_verified_at' => now(),
        ]);

        $this->logActivity('user_management', "Created {$user->role} account for {$user->name}.", 'info', $user);
        $this->flashToast('success', 'User Created', "{$user->role} account created successfully.");

        return redirect()->back();
    }

    public function updateUser(Request $request, User $user)
    {
        $request->validate(
            [
                'name'           => ['required', 'string', 'max:255'],
                'email'          => ['required', 'string', 'email', 'max:255', 'ends_with:@phinmaed.com', 'unique:users,email,' . $user->id],
                'student_number' => ['required', 'string', 'unique:users,student_number,' . $user->id, 'regex:/^01-[0-9]{4}-[0-9]{6}$/'],
                'phone'          => ['required', 'string', 'regex:/^09[0-9]{9}$/'],
                'role'           => ['required', 'in:student,personnel,admin,super-admin'],
            ],
            [
                'email.ends_with'      => 'Please use an official PHINMA organization email address.',
                'student_number.regex' => 'The ID must follow the AU format: 01-XXXX-XXXXXX.',
                'phone.regex'          => 'Please provide a valid 11-digit mobile number.',
            ]
        );

        $user->update([
            'name'           => $request->name,
            'email'          => $request->email,
            'student_number' => $request->student_number,
            'phone'          => $request->phone,
            'role'           => $request->role,
        ]);

        $this->logActivity('user_management', "Updated details for account: {$user->name}.", 'info', $user);
        $this->flashToast('success', 'Account Updated', 'Account updated successfully.');

        return redirect()->back();
    }

    public function destroyUser(User $user)
    {
        if ($user->id === auth()->id()) {
            $this->flashToast('danger', 'Action Blocked', 'You cannot delete your own account.');
            return redirect()->back()->with('error', 'You cannot delete your own account.');
        }

        $userName = $user->name;
        $userRole = $user->role;
        $user->delete();

        $this->logActivity('user_management', "Deleted {$userRole} account: {$userName}.", 'warning');
        $this->flashToast('success', 'User Removed', 'User removed from system.');

        return redirect()->back()->with('status', 'User removed from system.');
    }

    // ==========================================================
    // LAB MANAGEMENT
    // ==========================================================

    public function labs()
    {
        $labs = Lab::withCount([
            'computers as total_pcs',
            'computers as active_pcs' => function ($query) {
                $query->where('status', 'active');
            },
        ])->get();

        return view('super-admin.labs', compact('labs'));
    }

    public function viewSchedule(Lab $lab)
    {
        $schedules = Schedule::where('lab_id', $lab->id)
            ->with('user')
            ->orderByRaw("FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")
            ->orderBy('start_time', 'asc')
            ->get();

        $teachers = User::whereIn('role', ['personnel', 'admin', 'super-admin'])->get();

        return view('super-admin.schedule', compact('lab', 'schedules', 'teachers'));
    }

    public function storeSchedule(Request $request, Lab $lab)
    {
        $validated = $request->validate([
            'user_id'      => 'required|exists:users,id',
            'subject_code' => 'required|string|max:50',
            'day'          => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
            'start_time'   => 'required',
            'end_time'     => 'required|after:start_time',
        ]);

        $overlap = Schedule::where('lab_id', $lab->id)
            ->where('day', $validated['day'])
            ->where(function ($query) use ($validated) {
                $query->where('start_time', '<', $validated['end_time'])
                    ->where('end_time', '>', $validated['start_time']);
            })
            ->exists();

        if ($overlap) {
            $this->logActivity(
                'lab_management',
                "Schedule conflict detected in laboratory {$lab->name}.",
                'warning',
                $lab,
                [
                    'lab_id'       => $lab->id,
                    'subject_code' => $validated['subject_code'],
                    'day'          => $validated['day'],
                    'start_time'   => $validated['start_time'],
                    'end_time'     => $validated['end_time'],
                ]
            );

            $this->flashToast('danger', 'Schedule Conflict', 'This time slot is already taken.');
            return back()->withInput()->with('error', 'Schedule Conflict: This time slot is already taken.');
        }

        $schedule = Schedule::create([
            'lab_id'       => $lab->id,
            'user_id'      => $validated['user_id'],
            'subject_code' => $validated['subject_code'],
            'day'          => $validated['day'],
            'start_time'   => $validated['start_time'],
            'end_time'     => $validated['end_time'],
        ]);

        $this->logActivity('lab_management', "Created schedule {$schedule->subject_code} in laboratory {$lab->name}.", 'info', $schedule);
        $this->flashToast('success', 'Schedule Updated', 'Master schedule updated successfully.');

        return back()->with('success', 'Master Schedule updated successfully.');
    }

    public function destroySchedule(Schedule $schedule)
    {
        $subjectCode = $schedule->subject_code;
        $this->logActivity('lab_management', "Deleted schedule {$subjectCode}.", 'warning', $schedule);
        $schedule->delete();

        $this->flashToast('success', 'Schedule Removed', 'Schedule entry removed from master control.');
        return back()->with('success', 'Schedule entry removed from Master Control.');
    }

    // ==========================================================
    // LAB SESSION MANAGEMENT
    // ==========================================================

    public function sessions(Request $request)
    {
        $query = LabSession::with(['computer.lab', 'lab', 'teacher', 'checklist'])
            ->latest('time_in');

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

        $totalSessions       = LabSession::count();
        $activeSessionsCount = LabSession::whereNull('time_out')->count();

        $sessions = $query->paginate(15)->withQueryString();
        $allLabs  = Lab::orderBy('name')->get();

        return view('super-admin.sessions', compact(
            'sessions',
            'allLabs',
            'totalSessions',
            'activeSessionsCount'
        ));
    }

    // ==========================================================
    // INCIDENT ALERTS
    // ==========================================================

    public function alerts(Request $request)
    {
        $query = Alert::with(['computer.lab', 'reporter'])->latest();

        if ($request->filled('lab_id') || $request->filled('pc_number')) {
            $query->whereHas('computer', function ($q) use ($request) {
                if ($request->filled('lab_id')) {
                    $q->where('lab_id', $request->lab_id);
                }

                if ($request->filled('pc_number')) {
                    $cleanPc = trim($request->pc_number);
                    $q->where('pc_number', 'like', '%' . $cleanPc . '%');
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

        $alerts  = $query->paginate(15)->withQueryString();
        $allLabs = Lab::orderBy('name')->get();

        return view('super-admin.alerts', compact(
            'alerts',
            'allLabs',
            'totalReports',
            'unresolvedCount'
        ));
    }

    /**
     * Mark an alert as resolved.
     */
    public function resolveAlert(Alert $alert)
    {
        $pcNumber = $alert->computer->pc_number ?? 'Terminal';
        $labName  = $alert->computer->lab->name ?? 'Facility';

        $alert->update([
            'status'      => 'resolved',
            'resolved_at' => now(),
        ]);

        $this->logActivity(
            'incident_response',
            "Super Admin resolved security incident on {$pcNumber} ({$alert->issue_type}).",
            'info',
            $alert,
            [
                'alert_id'   => $alert->id,
                'pc_number'  => $pcNumber,
                'lab'        => $labName,
                'issue_type' => $alert->issue_type,
                'status'     => 'resolved',
            ]
        );

        $this->flashToast('success', 'Alert Resolved', "Incident report for {$pcNumber} has been resolved.");

        return back()->with('success', "Alert for {$pcNumber} has been resolved.");
    }

    /**
     * Discard an alert as a false alarm.
     */
    public function discardAlert(Alert $alert)
    {
        $pcNumber = $alert->computer->pc_number ?? 'Terminal';
        $labName  = $alert->computer->lab->name ?? 'Facility';

        $alert->update([
            'status'      => 'discarded',
            'resolved_at' => now(),
        ]);

        $this->logActivity(
            'incident_response',
            "Super Admin dismissed alert on {$pcNumber} as a false alarm.",
            'warning',
            $alert,
            [
                'alert_id'   => $alert->id,
                'pc_number'  => $pcNumber,
                'lab'        => $labName,
                'issue_type' => $alert->issue_type,
                'status'     => 'discarded',
            ]
        );

        $this->flashToast('success', 'Alert Dismissed', "Incident report for {$pcNumber} dismissed as a false alarm.");

        return back()->with('success', "Alert for {$pcNumber} dismissed as a false alarm.");
    }

    /**
     * Restore alert back to pending.
     */
    public function undoAlert(Alert $alert)
    {
        $pcNumber       = $alert->computer->pc_number ?? 'Terminal';
        $previousStatus = $alert->status;

        $alert->update([
            'status'      => 'pending',
            'resolved_at' => null,
        ]);

        $this->logActivity(
            'incident_response',
            "Super Admin reverted {$previousStatus} alert on {$pcNumber} back to pending.",
            'info',
            $alert,
            [
                'alert_id'        => $alert->id,
                'pc_number'       => $pcNumber,
                'previous_status' => $previousStatus,
                'status'          => 'pending',
            ]
        );

        $this->flashToast('info', 'Alert Reverted', "Alert for {$pcNumber} restored to pending status.");

        return back()->with('success', "Alert for {$pcNumber} restored to pending.");
    }

    // ==========================================================
    // STYLED EXECUTIVE SPREADSHEET ENGINE (.xls)
    // ==========================================================

    /**
     * Core renderer: Streams an HTML-based Excel spreadsheet (.xls)
     * complete with dark-slate headers, gold branding, and colored status cells.
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
            echo '<strong>Generated By:</strong> ' . htmlspecialchars(auth()->user()->name ?? 'Super Admin') . ' (' . now()->format('M d, Y h:i A') . ')';
            echo '</div>';
            echo '</td></tr>';
            echo '</table>';

            // Main Table
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
                    } elseif (in_array($upper, ['PENDING', 'FLAGGED', 'ONGOING'])) {
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

    // ==========================================================
    // EXPORTS: ANALYTICS & HUB REPORTS
    // ==========================================================

    public function exportReport(Request $request)
    {
        $type = $request->query('type', 'checklists');

        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : now()->subDays(7)->startOfDay();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : now()->endOfDay();

        $labId = $request->query('lab_id');

        $this->logActivity('system', "Exported {$type} analytics report.", 'info', null, [
            'report_type' => $type,
            'lab_id'      => $labId,
            'start_date'  => $startDate->format('Y-m-d'),
            'end_date'    => $endDate->format('Y-m-d'),
        ]);

        if ($type === 'alerts') {
            return $this->exportAlertsCsv($startDate, $endDate, $labId);
        }

        return $this->exportChecklistsCsv($startDate, $endDate, $labId);
    }

    protected function exportChecklistsCsv($startDate, $endDate, $labId = null)
    {
        $filename = "LabGuard_Hardware_Audits_" . now()->format('Ymd_His') . ".xls";

        $query = SessionChecklist::whereBetween('created_at', [$startDate, $endDate]);

        $labName = 'All Facilities';
        if ($labId) {
            $lab = Lab::find($labId);
            if ($lab) {
                $labName = $lab->name;
                $query->where(function ($q) use ($lab) {
                    $q->where('lab_name', $lab->name)
                        ->orWhereHas('labSession', fn($sq) => $sq->where('lab_id', $lab->id));
                });
            }
        }

        $records = $query->latest('id')->get();

        $metadata = [
            'Facility'       => $labName,
            'Coverage Range' => $startDate->format('M d, Y') . ' — ' . $endDate->format('M d, Y'),
            'Total Audits'   => $records->count(),
            'Passing Rate'   => $records->count() > 0 ? round(($records->where('all_operational', true)->count() / $records->count()) * 100, 1) . '%' : '100%',
        ];

        $headers = [
            'Audit ID',
            'Station / PC',
            'Laboratory Room',
            'Student ID',
            'Display Monitor',
            'Keyboard Unit',
            'Optical Mouse',
            'Power Unit (AVR)',
            'System Unit',
            'I/O Cables',
            'Operational?',
            'Timestamp',
        ];

        $rows = [];
        foreach ($records as $r) {
            $rows[] = [
                '#' . str_pad($r->id, 5, '0', STR_PAD_LEFT),
                $r->pc_number ?? 'PC-??',
                $r->lab_name ?? 'N/A',
                $r->student_id_number,
                $r->monitor_ok ? 'PASS' : 'FAIL',
                $r->keyboard_ok ? 'PASS' : 'FAIL',
                $r->mouse_ok ? 'PASS' : 'FAIL',
                $r->avr_ok ? 'PASS' : 'FAIL',
                ($r->system_unit_ok ?? $r->pc_case_ok ?? true) ? 'PASS' : 'FAIL',
                ($r->cables_ok ?? $r->headset_ok ?? true) ? 'PASS' : 'FAIL',
                $r->all_operational ? 'YES' : 'NO',
                optional($r->verified_at)->format('Y-m-d h:i A') ?? optional($r->created_at)->format('Y-m-d h:i A'),
            ];
        }

        return $this->streamStyledSpreadsheet(
            $filename,
            'Hardware Integrity & Inspection Audit Log',
            'PHINMA Araullo University &bull; Computer Laboratory Management System',
            $metadata,
            $headers,
            $rows
        );
    }

    protected function exportAlertsCsv($startDate, $endDate, $labId = null)
    {
        $filename = "LabGuard_Security_Alerts_" . now()->format('Ymd_His') . ".xls";

        $query = Alert::with(['computer.lab', 'lab', 'reporter'])
            ->whereBetween('created_at', [$startDate, $endDate]);

        $labName = 'All Facilities';
        if ($labId) {
            $lab = Lab::find($labId);
            if ($lab) {
                $labName = $lab->name;
                $query->where(function ($q) use ($labId) {
                    $q->where('lab_id', $labId)
                        ->orWhereHas('computer', fn($sq) => $sq->where('lab_id', $labId));
                });
            }
        }

        $records = $query->latest('id')->get();

        $metadata = [
            'Facility'       => $labName,
            'Coverage Range' => $startDate->format('M d, Y') . ' — ' . $endDate->format('M d, Y'),
            'Total Tickets'  => $records->count(),
            'Pending Alert'  => $records->where('status', 'pending')->count(),
            'Resolved Count' => $records->where('status', 'resolved')->count(),
        ];

        $headers = [
            'Ticket ID',
            'Terminal / PC',
            'Laboratory Room',
            'Student Reporter',
            'Student ID',
            'Category',
            'Triage Status',
            'Problem Remarks',
            'Date Reported',
            'Resolved At',
        ];

        $rows = [];
        foreach ($records as $r) {
            $rows[] = [
                '#' . str_pad($r->id, 5, '0', STR_PAD_LEFT),
                $r->computer->pc_number ?? 'N/A',
                $r->lab->name ?? $r->computer->lab->name ?? 'N/A',
                $r->reporter->name ?? $r->reportedBy->name ?? 'Student',
                $r->reporter->student_number ?? $r->reportedBy->student_number ?? 'N/A',
                $r->issue_type,
                strtoupper($r->status),
                $r->remarks ?? $r->description ?? 'N/A',
                $r->created_at->format('Y-m-d h:i A'),
                $r->resolved_at ? Carbon::parse($r->resolved_at)->format('Y-m-d h:i A') : 'UNRESOLVED',
            ];
        }

        return $this->streamStyledSpreadsheet(
            $filename,
            'Workstation Defect & Incident Response Ledger',
            'PHINMA Araullo University &bull; Computer Laboratory Management System',
            $metadata,
            $headers,
            $rows
        );
    }

    public function generateReport(Request $request)
    {
        $request->validate([
            'type'  => 'required|in:utilization,security',
            'range' => 'required|in:today,week,month',
        ]);

        $timeframe = match ($request->range) {
            'today' => now()->startOfDay(),
            'week'  => now()->subDays(7)->startOfDay(),
            'month' => now()->startOfMonth(),
        };

        $filename = "LabGuard_{$request->type}_Report_" . now()->format('Ymd_His') . ".xls";

        if ($request->type === 'utilization') {
            $data = LabSession::with(['user', 'lab', 'computer'])
                ->where('time_in', '>=', $timeframe)
                ->latest('time_in')
                ->get();

            $metadata = [
                'Report Focus'  => 'Laboratory Utilization & Student Roster',
                'Period'        => ucfirst($request->range),
                'Total Entries' => $data->count(),
            ];

            $headers = ['Session ID', 'Student Name', 'ID Number', 'Laboratory Room', 'Station PC', 'Logged In At', 'Logged Out At', 'Session Length'];

            $rows = [];
            foreach ($data as $row) {
                $rows[] = [
                    '#' . str_pad($row->id, 5, '0', STR_PAD_LEFT),
                    $row->user->name ?? $row->student_name ?? 'N/A',
                    $row->student_id_number ?? $row->user->student_number ?? 'N/A',
                    $row->lab->room_name ?? $row->lab->name ?? 'N/A',
                    $row->computer->pc_number ?? 'PC-??',
                    $row->time_in ? Carbon::parse($row->time_in)->format('Y-m-d h:i A') : 'N/A',
                    $row->time_out ? Carbon::parse($row->time_out)->format('Y-m-d h:i A') : 'ACTIVE',
                    $row->time_out ? Carbon::parse($row->time_in)->diffForHumans(Carbon::parse($row->time_out), true) : 'Ongoing',
                ];
            }

            return $this->streamStyledSpreadsheet($filename, 'Laboratory Space Utilization Report', 'Academic Fleet Usage & Student Session Logbook', $metadata, $headers, $rows);
        } else {
            $data = Alert::with(['lab', 'computer.lab', 'reporter'])
                ->where('created_at', '>=', $timeframe)
                ->latest()
                ->get();

            $metadata = [
                'Report Focus'  => 'Incident Triage & Security Alerts',
                'Period'        => ucfirst($request->range),
                'Total Tickets' => $data->count(),
            ];

            $headers = ['Alert ID', 'Station PC', 'Laboratory Room', 'Reported By', 'Category', 'Triage Status', 'Remarks', 'Logged At', 'Resolved At'];

            $rows = [];
            foreach ($data as $row) {
                $rows[] = [
                    '#' . str_pad($row->id, 5, '0', STR_PAD_LEFT),
                    $row->computer->pc_number ?? 'N/A',
                    $row->lab->name ?? $row->computer->lab->name ?? 'N/A',
                    $row->reporter->name ?? $row->reportedBy->name ?? 'Student',
                    $row->issue_type ?? 'Technical',
                    strtoupper($row->status),
                    $row->remarks ?? 'N/A',
                    $row->created_at->format('Y-m-d h:i A'),
                    $row->resolved_at ? Carbon::parse($row->resolved_at)->format('Y-m-d h:i A') : 'UNRESOLVED',
                ];
            }

            return $this->streamStyledSpreadsheet($filename, 'Laboratory Incident & Safety Ledger', 'Workstation Health & Technical Alert Records', $metadata, $headers, $rows);
        }
    }

    // ==========================================================
    // BACKUP
    // ==========================================================

    public function triggerBackup()
    {
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host     = config('database.connections.mysql.host', '127.0.0.1');
        $port     = config('database.connections.mysql.port', '3306');

        $filename    = "backup_" . $database . "_" . now()->format('Y_m_d_H_i_s') . ".sql";
        $storagePath = storage_path('app/backups');

        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        $fullPath = $storagePath . '/' . $filename;
        $binary   = 'mysqldump';

        $command = sprintf(
            'MYSQL_PWD=%s %s --user=%s --host=%s --port=%s %s > %s 2>&1',
            escapeshellarg($password),
            $binary,
            escapeshellarg($username),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($database),
            escapeshellarg($fullPath)
        );

        $output = [];
        $returnVar = null;

        exec($command, $output, $returnVar);

        if ($returnVar === 0) {
            $this->logActivity('system', "Database backup created successfully: {$filename}", 'info', null, [
                'filename' => $filename,
                'database' => $database,
            ]);

            Log::info("Super Admin successfully initialized database snapshot dump file: {$filename}");
            $this->flashToast('success', 'Backup Created', 'Database snapshot generated and archived successfully.');

            return redirect()->back()->with('status', 'Database snapshot generated and archived successfully.');
        }

        $this->logActivity('system', 'Database backup failed.', 'danger', null, [
            'filename'  => $filename,
            'exit_code' => $returnVar,
            'output'    => implode("\n", $output),
        ]);

        Log::error('Database dump routine failed execution.', [
            'exit_code' => $returnVar,
            'output'    => implode("\n", $output),
            'command'   => $command,
        ]);

        $this->flashToast('danger', 'Backup Failed', 'Database backup generation failed. Check server permissions.');

        return redirect()->back()->with('error', 'Database utility structural engine execution failed. Check server logs for details.');
    }

    // ==========================================================
    // EMERGENCY LOCKDOWN
    // ==========================================================

    public function lockout(Request $request)
    {
        $labId = $request->input('lab_id');

        if ($labId === 'all') {
            Lab::query()->update(['status' => 'maintenance']);
            Computer::query()->update(['status' => 'maintenance']);

            $this->logActivity('incident_response', 'System-wide laboratory lockdown activated.', 'danger', null, [
                'scope' => 'all_labs',
            ]);

            $this->flashToast('danger', 'System Lockdown Active', 'All laboratories and computer stations have been placed under maintenance lockdown.');

            return redirect()->back()->with('success', 'All laboratories have been placed under maintenance lockdown.');
        }

        $lab = Lab::findOrFail($labId);
        $lab->update(['status' => 'maintenance']);
        $lab->computers()->update(['status' => 'maintenance']);

        $this->logActivity('incident_response', "Laboratory {$lab->name} was placed under lockdown.", 'danger', $lab, [
            'lab_id' => $lab->id,
            'scope'  => 'single_lab',
        ]);

        $this->flashToast('danger', 'Lab Lockdown Active', "{$lab->name} has been placed under maintenance lockdown.");

        return redirect()->back()->with('success', "{$lab->name} has been placed under maintenance lockdown.");
    }

    public function releaseLockout(Request $request)
    {
        $labId = $request->input('lab_id');

        if ($labId === 'all') {
            Lab::query()->update(['status' => 'active']);
            Computer::where('status', 'maintenance')->update(['status' => 'available']);

            $this->logActivity('incident_response', 'System-wide laboratory lockdown released.', 'info', null, [
                'scope' => 'all_labs',
            ]);

            $this->flashToast('success', 'System Lockdown Released', 'All laboratories and computer stations have been restored to normal operation.');

            return redirect()->back()->with('success', 'All laboratory lockdowns have been released.');
        }

        $lab = Lab::findOrFail($labId);
        $lab->update(['status' => 'active']);
        $lab->computers()->where('status', 'maintenance')->update(['status' => 'available']);

        $this->logActivity('incident_response', "Laboratory {$lab->name} lockdown was released.", 'info', $lab, [
            'lab_id' => $lab->id,
            'scope'  => 'single_lab',
        ]);

        $this->flashToast('success', 'Lab Lockdown Released', "{$lab->name} has been restored to normal operation.");

        return redirect()->back()->with('success', "{$lab->name} lockdown has been released.");
    }

    // ==========================================================
    // ACTIVITY LOGS
    // ==========================================================

    public function logs(Request $request)
    {
        $query = Activity::with(['causer', 'subject'])->latest();

        if ($request->has('category') && $request->category !== 'all') {
            $query->where('log_name', $request->category);
        }

        $logs = $query->paginate(12)->withQueryString();

        $stats = [
            'total'     => Activity::count(),
            'incidents' => Activity::where('log_name', 'incident_response')->count(),
            'users'     => Activity::where('log_name', 'user_management')->count(),
            'labs'      => Activity::where('log_name', 'lab_management')->count(),
        ];

        return view('super-admin.logs', compact('logs', 'stats'));
    }

    // ==========================================================
    // ANALYTICS VIEW
    // ==========================================================

    public function analytics(Request $request)
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : now()->subDays(7)->startOfDay();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : now()->endOfDay();

        $selectedLabId = $request->query('lab_id');

        $startDateInput = $startDate->format('Y-m-d');
        $endDateInput   = $endDate->format('Y-m-d');
        $rangeLabel     = $startDate->format('M d, Y') . ' — ' . $endDate->format('M d, Y');

        $checklistQuery = SessionChecklist::whereBetween('created_at', [$startDate, $endDate]);
        $sessionQuery   = LabSession::whereBetween('created_at', [$startDate, $endDate]);
        $alertQuery     = Alert::whereBetween('created_at', [$startDate, $endDate]);

        if ($selectedLabId) {
            $sessionQuery->where('lab_id', $selectedLabId);
            $alertQuery->where('lab_id', $selectedLabId);

            $lab = Lab::find($selectedLabId);
            if ($lab) {
                $checklistQuery->where(function ($q) use ($lab) {
                    $q->where('lab_name', $lab->name)
                        ->orWhereHas('labSession', fn($sq) => $sq->where('lab_id', $lab->id));
                });
            }
        }

        $totalChecklists = (clone $checklistQuery)->count();
        $flawlessChecklists = (clone $checklistQuery)->where('all_operational', true)->count();
        $flaggedChecklists = $totalChecklists - $flawlessChecklists;

        $hardwareIntegrityRate = $totalChecklists > 0
            ? round(($flawlessChecklists / $totalChecklists) * 100, 1)
            : 100.0;

        $peripheralFailures = [
            'system_unit' => (clone $checklistQuery)->where('system_unit_ok', false)->count(),
            'monitor'     => (clone $checklistQuery)->where('monitor_ok', false)->count(),
            'avr'         => (clone $checklistQuery)->where('avr_ok', false)->count(),
            'mouse'       => (clone $checklistQuery)->where('mouse_ok', false)->count(),
            'keyboard'    => (clone $checklistQuery)->where('keyboard_ok', false)->count(),
            'cables'      => (clone $checklistQuery)->where('cables_ok', false)->count(),
        ];

        $recentIssues = (clone $checklistQuery)
            ->where(function ($q) {
                $q->where('all_operational', false)
                    ->orWhere('system_unit_ok', false)
                    ->orWhere('monitor_ok', false)
                    ->orWhere('avr_ok', false)
                    ->orWhere('mouse_ok', false)
                    ->orWhere('keyboard_ok', false)
                    ->orWhere('cables_ok', false);
            })
            ->latest('id')
            ->take(6)
            ->get();

        $computerFleet = Computer::when($selectedLabId, fn($q) => $q->where('lab_id', $selectedLabId));

        $totalComputers = (clone $computerFleet)->count();
        $fleetActive    = (clone $computerFleet)->where('status', 'active')->count();
        $fleetAvailable = (clone $computerFleet)->where('status', 'available')->count();
        $fleetMaint     = (clone $computerFleet)->where('status', 'maintenance')->count();

        $pendingAlerts   = (clone $alertQuery)->where('status', 'pending')->count();
        $resolvedAlerts  = (clone $alertQuery)->where('status', 'resolved')->count();
        $discardedAlerts = (clone $alertQuery)->where('status', 'discarded')->count();

        $hourlyDistribution = (clone $sessionQuery)
            ->selectRaw('HOUR(time_in) as hour, count(*) as count')
            ->whereNotNull('time_in')
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        $hourlyData = [];
        for ($h = 7; $h <= 20; $h++) {
            $hourlyData[] = [
                'hour'  => Carbon::createFromTime($h)->format('g A'),
                'count' => $hourlyDistribution[$h] ?? 0,
            ];
        }

        $allLabs = Lab::orderBy('name')->get();

        return view('super-admin.analytics', compact(
            'startDateInput',
            'endDateInput',
            'rangeLabel',
            'selectedLabId',
            'allLabs',
            'totalChecklists',
            'flawlessChecklists',
            'flaggedChecklists',
            'hardwareIntegrityRate',
            'peripheralFailures',
            'totalComputers',
            'fleetActive',
            'fleetAvailable',
            'fleetMaint',
            'pendingAlerts',
            'resolvedAlerts',
            'discardedAlerts',
            'recentIssues',
            'hourlyData'
        ));
    }

    // ==========================================================
    // USER CSV IMPORT
    // ==========================================================

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('file');

        if (!$file || !($handle = fopen($file->getRealPath(), 'r'))) {
            $this->flashToast('danger', 'Import Failed', 'Could not open uploaded file.');
            return back()->withErrors(['file' => 'Could not open uploaded file.']);
        }

        $rawHeader = fgetcsv($handle, 1000, ',');
        if (!$rawHeader) {
            fclose($handle);
            $this->flashToast('danger', 'Import Failed', 'The uploaded file is empty.');
            return back()->withErrors(['file' => 'The uploaded file is empty.']);
        }

        $header = array_map(function ($col) {
            $col = preg_replace('/[\x{EF}\x{BB}\x{BF}]/u', '', $col);
            return strtolower(trim($col));
        }, $rawHeader);

        $requiredColumns = ['name', 'email', 'student_number', 'phone', 'role', 'password'];
        $missingColumns  = array_diff($requiredColumns, $header);

        if (!empty($missingColumns)) {
            fclose($handle);
            $missingStr = implode(', ', $missingColumns);
            $this->logActivity('user_management', 'User CSV import was rejected because required columns were missing.', 'warning', null, [
                'missing_columns' => $missingColumns,
            ]);
            $this->flashToast('danger', 'Invalid CSV Format', "Missing required columns: {$missingStr}");
            return back()->withErrors(['file' => "Missing required columns: {$missingStr}"]);
        }

        $importedCount = 0;
        $skippedCount  = 0;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                if (empty(array_filter($row))) {
                    continue;
                }
                if (count($row) < count($header)) {
                    $skippedCount++;
                    continue;
                }

                $data          = array_combine($header, $row);
                $email         = trim($data['email'] ?? '');
                $studentNumber = trim($data['student_number'] ?? '');

                if (empty($email) || User::where('email', $email)->orWhere('student_number', $studentNumber)->exists()) {
                    $skippedCount++;
                    continue;
                }

                User::create([
                    'name'           => trim($data['name']),
                    'email'          => $email,
                    'student_number' => $studentNumber,
                    'phone'          => trim($data['phone']),
                    'role'           => in_array(strtolower(trim($data['role'])), ['student', 'personnel', 'admin']) ? strtolower(trim($data['role'])) : 'student',
                    'password'       => Hash::make(trim($data['password'])),
                ]);

                $importedCount++;
            }

            DB::commit();
            fclose($handle);

            if ($importedCount === 0) {
                $message = "No new users were imported. ({$skippedCount} duplicate or invalid records skipped).";
                $this->logActivity('user_management', 'User CSV import completed with no new users added.', 'warning', null, [
                    'imported_count' => 0,
                    'skipped_count'  => $skippedCount,
                ]);
                $this->flashToast('warning', 'Import Completed', $message);
                return back()->with('status', $message);
            }

            $message = "Successfully enrolled {$importedCount} new user(s).";
            if ($skippedCount > 0) {
                $message .= " ({$skippedCount} duplicate/invalid records were skipped).";
            }

            $this->logActivity('user_management', "Imported {$importedCount} new user account(s).", $skippedCount > 0 ? 'warning' : 'info', null, [
                'imported_count' => $importedCount,
                'skipped_count'  => $skippedCount,
            ]);
            $this->flashToast('success', 'Mass Enrollment Complete', $message);

            return back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);

            $this->logActivity('user_management', 'User CSV import failed.', 'danger', null, ['error' => $e->getMessage()]);
            $this->flashToast('danger', 'Import Failed', 'Critical System Error: ' . $e->getMessage());

            return back()->withErrors(['file' => 'Import failed: ' . $e->getMessage()]);
        }
    }
}
