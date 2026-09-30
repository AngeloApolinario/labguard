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
     *
     * Categories:
     * - user_management
     * - lab_management
     * - incident_response
     * - system
     * - auth
     *
     * Severities:
     * - info
     * - warning
     * - danger
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

    public function index()
    {
        Computer::cleanupStaleSessions();

        $totalUsers = User::count();

        $activeSessionsCount = LabSession::whereNull('time_out')->count();

        $pendingAlertsCount = Alert::where('status', 'pending')->count();

        $labs = Lab::with([
            'computers' => function ($query) {
                $query->select(
                    'id',
                    'lab_id',
                    'pc_number',
                    'status'
                );
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
            ->select(
                'lab_id',
                DB::raw('count(*) as active_count')
            )
            ->groupBy('lab_id')
            ->pluck('active_count', 'lab_id')
            ->all();

        $labUtilization = [];

        foreach ($labs as $lab) {
            $currentActiveCount =
                $activeSessionsPerLab[$lab->id] ?? 0;

            $capacity = $lab->capacity > 0
                ? $lab->capacity
                : max(1, $lab->total_pcs);

            $percentage = $capacity > 0
                ? ($currentActiveCount / $capacity) * 100
                : 0;

            $labUtilization[$lab->id] = [
                'id' => $lab->id,
                'name' => $lab->name,
                'status' => $lab->status,

                'percent' => min(
                    100,
                    round($percentage)
                ),

                'active' => $currentActiveCount,
                'capacity' => $capacity,

                'total_pcs' => $lab->total_pcs,
                'active_pcs' => $lab->active_pcs,
                'maintenance_pcs' => $lab->maintenance_pcs,

                'computers' => $lab->computers,
            ];
        }

        $recentAlerts = Alert::with([
            'lab',
            'computer.lab',
            'reporter',
        ])
            ->latest()
            ->take(5)
            ->get();

        return view('super-admin.index', [
            'totalUsers' => $totalUsers,
            'activeSessions' => $activeSessionsCount,
            'alerts' => $pendingAlertsCount,
            'totalLabs' => $totalLabs,
            'labUtilization' => $labUtilization,
            'recentAlerts' => $recentAlerts,
        ]);
    }

    public function security()
    {
        $securityStats = [
            'score' => '92/100',
            'threats' => 3,
            'vulnerabilities' => 5,
        ];

        $alerts = [
            [
                'type' => 'critical',
                'title' => 'Multiple Failed Login Attempts',
                'badge' => 'Critical',
                'desc' => 'IP 192.168.1.45 detected 5 failed login attempts in the last 10 minutes',
                'time' => '2 minutes ago',
                'action' => 'Block IP',
                'icon' => 'heroicon-o-exclamation-triangle',
                'iconColor' => 'text-rose-500',
                'bgColor' => 'bg-rose-50',
            ],
            [
                'type' => 'warning',
                'title' => 'Unusual Access Pattern',
                'badge' => 'Warning',
                'desc' => 'User account accessed from a new location: Tokyo, Japan',
                'time' => '1 hour ago',
                'action' => 'Review',
                'icon' => 'heroicon-o-exclamation-circle',
                'iconColor' => 'text-amber-500',
                'bgColor' => 'bg-amber-50',
            ],
            [
                'type' => 'info',
                'title' => 'System Update Available',
                'badge' => 'Info',
                'desc' => 'Security patch v2.5.3 available for system kernel',
                'time' => '1 hour ago',
                'action' => 'Update',
                'icon' => 'heroicon-o-information-circle',
                'iconColor' => 'text-blue-500',
                'bgColor' => 'bg-blue-50',
            ],
        ];

        return view('super-admin.security', compact('securityStats', 'alerts'));
    }

    public function settings()
    {
        $settings = [
            'system_name' => 'LabGuard - Computer Lab Management',
            'institution' => 'Au University',
            'backup_time' => '02:00',
            'session_timeout' => '30',
            'system_email' => 'admin@labguard.edu',
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
                'name' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    'ends_with:@phinmaed.com',
                    'unique:users',
                ],

                'password' => [
                    'required',
                    'string',
                    Password::min(8)
                        ->mixedCase()
                        ->numbers()
                        ->symbols(),
                ],

                'role' => [
                    'required',
                    'in:student,personnel,admin,super-admin',
                ],

                'student_number' => [
                    'required',
                    'string',
                    'unique:users',
                    'regex:/^01-[0-9]{4}-[0-9]{6}$/',
                ],

                'phone' => [
                    'required',
                    'string',
                    'regex:/^09[0-9]{9}$/',
                ],
            ],
            [
                'email.ends_with' =>
                'Please use an official PHINMA organization email address.',

                'student_number.regex' =>
                'The ID must follow the AU format: 01-XXXX-XXXXXX.',

                'phone.regex' =>
                'Please provide a valid 11-digit mobile number.',
            ]
        );

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'student_number' => $request->student_number,
            'phone' => $request->phone,
            'email_verified_at' => now(),
        ]);

        /*
         * UserObserver already creates the Spatie Activity Log
         * entry for this action.
         */

        $this->flashToast(
            'success',
            'User Created',
            "{$user->role} account created successfully."
        );

        return redirect()->back();
    }

    public function updateUser(Request $request, User $user)
    {
        $request->validate(
            [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    'ends_with:@phinmaed.com',
                    'unique:users,email,' . $user->id,
                ],

                'student_number' => [
                    'required',
                    'string',
                    'unique:users,student_number,' . $user->id,
                    'regex:/^01-[0-9]{4}-[0-9]{6}$/',
                ],

                'phone' => [
                    'required',
                    'string',
                    'regex:/^09[0-9]{9}$/',
                ],

                'role' => [
                    'required',
                    'in:student,personnel,admin,super-admin',
                ],
            ],
            [
                'email.ends_with' =>
                'Please use an official PHINMA organization email address.',

                'student_number.regex' =>
                'The ID must follow the AU format: 01-XXXX-XXXXXX.',

                'phone.regex' =>
                'Please provide a valid 11-digit mobile number.',
            ]
        );

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'student_number' => $request->student_number,
            'phone' => $request->phone,
            'role' => $request->role,
        ]);

        /*
         * UserObserver already creates the Spatie Activity Log
         * entry for this action.
         */

        $this->flashToast(
            'success',
            'Account Updated',
            'Account updated successfully.'
        );

        return redirect()->back();
    }

    public function destroyUser(User $user)
    {
        if ($user->id === auth()->id()) {
            $this->flashToast(
                'danger',
                'Action Blocked',
                'You cannot delete your own account.'
            );

            return redirect()->back()
                ->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        /*
         * UserObserver already creates the Spatie Activity Log
         * entry for this action.
         */

        $this->flashToast(
            'success',
            'User Removed',
            'User removed from system.'
        );

        return redirect()->back()
            ->with('status', 'User removed from system.');
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
            ->orderByRaw(
                "FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')"
            )
            ->orderBy('start_time', 'asc')
            ->get();

        $teachers = User::whereIn(
            'role',
            ['personnel', 'admin', 'super-admin']
        )->get();

        return view(
            'super-admin.schedule',
            compact('lab', 'schedules', 'teachers')
        );
    }

    public function storeSchedule(Request $request, Lab $lab)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'subject_code' => 'required|string|max:50',
            'day' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
        ]);

        $overlap = Schedule::where('lab_id', $lab->id)
            ->where('day', $validated['day'])
            ->where(function ($query) use ($validated) {
                $query->where(
                    'start_time',
                    '<',
                    $validated['end_time']
                )->where(
                    'end_time',
                    '>',
                    $validated['start_time']
                );
            })
            ->exists();

        if ($overlap) {
            $this->logActivity(
                'lab_management',
                "Schedule conflict detected in laboratory {$lab->name}.",
                'warning',
                $lab,
                [
                    'lab_id' => $lab->id,
                    'subject_code' => $validated['subject_code'],
                    'day' => $validated['day'],
                    'start_time' => $validated['start_time'],
                    'end_time' => $validated['end_time'],
                ]
            );

            $this->flashToast(
                'danger',
                'Schedule Conflict',
                'This time slot is already taken.'
            );

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Schedule Conflict: This time slot is already taken.'
                );
        }

        $schedule = Schedule::create([
            'lab_id' => $lab->id,
            'user_id' => $validated['user_id'],
            'subject_code' => $validated['subject_code'],
            'day' => $validated['day'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
        ]);

        $this->logActivity(
            'lab_management',
            "Created schedule {$schedule->subject_code} in laboratory {$lab->name}.",
            'info',
            $schedule,
            [
                'schedule_id' => $schedule->id,
                'lab_id' => $lab->id,
                'user_id' => $validated['user_id'],
                'day' => $validated['day'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
            ]
        );

        $this->flashToast(
            'success',
            'Schedule Updated',
            'Master schedule updated successfully.'
        );

        return back()
            ->with('success', 'Master Schedule updated successfully.');
    }

    public function destroySchedule(Schedule $schedule)
    {
        $scheduleId = $schedule->id;
        $subjectCode = $schedule->subject_code;
        $labId = $schedule->lab_id;

        $this->logActivity(
            'lab_management',
            "Deleted schedule {$subjectCode}.",
            'warning',
            $schedule,
            [
                'schedule_id' => $scheduleId,
                'lab_id' => $labId,
            ]
        );

        $schedule->delete();

        $this->flashToast(
            'success',
            'Schedule Removed',
            'Schedule entry removed from master control.'
        );

        return back()
            ->with(
                'success',
                'Schedule entry removed from Master Control.'
            );
    }

    // ==========================================================
    // LAB SESSION MANAGEMENT
    // ==========================================================

    public function sessions(Request $request)
    {
        $query = LabSession::with(['computer'])
            ->whereNotNull('time_out')
            ->latest('time_out');

        if ($request->filled('student_name')) {
            $query->where(
                'student_name',
                'like',
                '%' . $request->student_name . '%'
            );
        }

        if ($request->filled('pc_number')) {
            $query->whereHas('computer', function ($q) use ($request) {
                $q->where(
                    'pc_number',
                    'like',
                    '%' . $request->pc_number . '%'
                );
            });
        }

        if ($request->filled('date')) {
            $query->whereDate(
                'time_in',
                $request->date
            );
        }

        $sessions = $query
            ->paginate(15)
            ->withQueryString();

        return view(
            'super-admin.sessions',
            compact('sessions')
        );
    }

    // ==========================================================
    // REPORTS
    // ==========================================================

    public function generateReport(Request $request)
    {
        $request->validate([
            'type' => 'required|in:utilization,security',
            'range' => 'required|in:today,week,month',
        ]);

        $timeframe = match ($request->range) {
            'today' => now()->startOfDay(),
            'week' => now()->subDays(7),
            'month' => now()->startOfMonth(),
        };

        if ($request->type === 'utilization') {
            $data = LabSession::with(['user', 'lab'])
                ->where('time_in', '>=', $timeframe)
                ->get();
        } else {
            $data = Alert::with(['lab', 'computer'])
                ->where('created_at', '>=', $timeframe)
                ->get();
        }

        $this->logActivity(
            'system',
            "Generated {$request->type} report for {$request->range}.",
            'info',
            null,
            [
                'report_type' => $request->type,
                'range' => $request->range,
                'record_count' => $data->count(),
            ]
        );

        Log::info(
            "Super Admin generated a {$request->type} report for range: {$request->range}"
        );

        $fileName =
            "labguard_{$request->type}_report_" .
            now()->format('Y-m-d') .
            ".csv";

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma' => 'no-cache',
            'Cache-Control' =>
            'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($data, $request) {
            $file = fopen('php://output', 'w');

            if ($request->type === 'utilization') {
                fputcsv($file, [
                    'Session ID',
                    'Student',
                    'Laboratory Room',
                    'Logged In At',
                    'Logged Out At',
                ]);

                foreach ($data as $row) {
                    fputcsv($file, [
                        $row->id,
                        $row->user->name
                            ?? $row->student_name
                            ?? 'N/A',
                        $row->lab->room_name
                            ?? $row->lab->name
                            ?? 'N/A',
                        $row->time_in,
                        $row->time_out ?? 'Active',
                    ]);
                }
            } else {
                fputcsv($file, [
                    'Alert ID',
                    'Room',
                    'Station PC',
                    'Issue Category',
                    'Status',
                    'Logged At',
                ]);

                foreach ($data as $row) {
                    fputcsv($file, [
                        $row->id,
                        $row->lab->room_name
                            ?? $row->lab->name
                            ?? 'N/A',
                        $row->computer->pc_number ?? 'N/A',
                        $row->issue_type ?? 'Technical',
                        $row->status,
                        $row->created_at,
                    ]);
                }
            }

            fclose($file);
        };

        return response()->stream(
            $callback,
            200,
            $headers
        );
    }

    // ==========================================================
    // BACKUP
    // ==========================================================

    public function triggerBackup()
    {
        $database =
            config('database.connections.mysql.database');

        $username =
            config('database.connections.mysql.username');

        $password =
            config('database.connections.mysql.password');

        $host =
            config(
                'database.connections.mysql.host',
                '127.0.0.1'
            );

        $port =
            config(
                'database.connections.mysql.port',
                '3306'
            );

        $filename =
            "backup_" .
            $database .
            "_" .
            now()->format('Y_m_d_H_i_s') .
            ".sql";

        $storagePath =
            storage_path('app/backups');

        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        $fullPath =
            $storagePath . '/' . $filename;

        $binary = 'mysqldump';

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

        exec(
            $command,
            $output,
            $returnVar
        );

        if ($returnVar === 0) {

            $this->logActivity(
                'system',
                "Database backup created successfully: {$filename}",
                'info',
                null,
                [
                    'filename' => $filename,
                    'database' => $database,
                ]
            );

            Log::info(
                "Super Admin successfully initialized database snapshot dump file: {$filename}"
            );

            $this->flashToast(
                'success',
                'Backup Created',
                'Database snapshot generated and archived successfully.'
            );

            return redirect()->back()
                ->with(
                    'status',
                    'Database snapshot generated and archived successfully.'
                );
        }

        $this->logActivity(
            'system',
            'Database backup failed.',
            'danger',
            null,
            [
                'filename' => $filename,
                'exit_code' => $returnVar,
                'output' => implode("\n", $output),
            ]
        );

        Log::error(
            'Database dump routine failed execution.',
            [
                'exit_code' => $returnVar,
                'output' => implode("\n", $output),
                'command' => $command,
            ]
        );

        $this->flashToast(
            'danger',
            'Backup Failed',
            'Database backup generation failed. Check server permissions.'
        );

        return redirect()->back()
            ->with(
                'error',
                'Database utility structural engine execution failed. Check server logs for details.'
            );
    }

    // ==========================================================
    // EMERGENCY LOCKDOWN
    // ==========================================================

    public function lockout(Request $request)
    {
        $labId = $request->input('lab_id');

        if ($labId === 'all') {

            Lab::query()->update([
                'status' => 'maintenance',
            ]);

            Computer::query()->update([
                'status' => 'maintenance',
            ]);

            $this->logActivity(
                'incident_response',
                'System-wide laboratory lockdown activated.',
                'danger',
                null,
                [
                    'scope' => 'all_labs',
                ]
            );

            $this->flashToast(
                'danger',
                'System Lockdown Active',
                'All laboratories and computer stations have been placed under maintenance lockdown.'
            );

            return redirect()->back()
                ->with(
                    'success',
                    'All laboratories have been placed under maintenance lockdown.'
                );
        }

        $lab = Lab::findOrFail($labId);

        $lab->update([
            'status' => 'maintenance',
        ]);

        $lab->computers()->update([
            'status' => 'maintenance',
        ]);

        $this->logActivity(
            'incident_response',
            "Laboratory {$lab->name} was placed under lockdown.",
            'danger',
            $lab,
            [
                'lab_id' => $lab->id,
                'scope' => 'single_lab',
            ]
        );

        $this->flashToast(
            'danger',
            'Lab Lockdown Active',
            "{$lab->name} has been placed under maintenance lockdown."
        );

        return redirect()->back()
            ->with(
                'success',
                "{$lab->name} has been placed under maintenance lockdown."
            );
    }

    public function releaseLockout(Request $request)
    {
        $labId = $request->input('lab_id');

        if ($labId === 'all') {

            Lab::query()->update([
                'status' => 'active',
            ]);

            Computer::where('status', 'maintenance')
                ->update([
                    'status' => 'available',
                ]);

            $this->logActivity(
                'incident_response',
                'System-wide laboratory lockdown released.',
                'info',
                null,
                [
                    'scope' => 'all_labs',
                ]
            );

            $this->flashToast(
                'success',
                'System Lockdown Released',
                'All laboratories and computer stations have been restored to normal operation.'
            );

            return redirect()->back()
                ->with(
                    'success',
                    'All laboratory lockdowns have been released.'
                );
        }

        $lab = Lab::findOrFail($labId);

        $lab->update([
            'status' => 'active',
        ]);

        $lab->computers()
            ->where('status', 'maintenance')
            ->update([
                'status' => 'available',
            ]);

        $this->logActivity(
            'incident_response',
            "Laboratory {$lab->name} lockdown was released.",
            'info',
            $lab,
            [
                'lab_id' => $lab->id,
                'scope' => 'single_lab',
            ]
        );

        $this->flashToast(
            'success',
            'Lab Lockdown Released',
            "{$lab->name} has been restored to normal operation."
        );

        return redirect()->back()
            ->with(
                'success',
                "{$lab->name} lockdown has been released."
            );
    }

    // ==========================================================
    // ACTIVITY LOGS
    // ==========================================================

    public function logs(Request $request)
    {
        $query = Activity::with([
            'causer',
            'subject',
        ])->latest();

        if (
            $request->has('category') &&
            $request->category !== 'all'
        ) {
            $query->where(
                'log_name',
                $request->category
            );
        }

        $logs = $query
            ->paginate(12)
            ->withQueryString();

        $stats = [
            'total' => Activity::count(),

            'incidents' => Activity::where(
                'log_name',
                'incident_response'
            )->count(),

            'users' => Activity::where(
                'log_name',
                'user_management'
            )->count(),

            'labs' => Activity::where(
                'log_name',
                'lab_management'
            )->count(),
        ];

        return view(
            'super-admin.logs',
            compact('logs', 'stats')
        );
    }

    // ==========================================================
    // ANALYTICS
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

        $startDateInput =
            $startDate->format('Y-m-d');

        $endDateInput =
            $endDate->format('Y-m-d');

        $rangeLabel =
            $startDate->format('M d, Y') .
            ' — ' .
            $endDate->format('M d, Y');

        $checklistQuery = SessionChecklist::whereBetween(
            'created_at',
            [$startDate, $endDate]
        );

        $sessionQuery = LabSession::whereBetween(
            'created_at',
            [$startDate, $endDate]
        );

        $alertQuery = Alert::whereBetween(
            'created_at',
            [$startDate, $endDate]
        );

        if ($selectedLabId) {

            $sessionQuery->where(
                'lab_id',
                $selectedLabId
            );

            $alertQuery->where(
                'lab_id',
                $selectedLabId
            );

            $lab = Lab::find($selectedLabId);

            if ($lab) {
                $checklistQuery->where(
                    function ($q) use ($lab) {

                        $q->where(
                            'lab_name',
                            $lab->name
                        )->orWhereHas(
                            'labSession',
                            fn($sq) =>
                            $sq->where(
                                'lab_id',
                                $lab->id
                            )
                        );
                    }
                );
            }
        }

        $totalChecklists =
            (clone $checklistQuery)->count();

        $flawlessChecklists =
            (clone $checklistQuery)
            ->where(
                'all_operational',
                true
            )
            ->count();

        $flaggedChecklists =
            $totalChecklists -
            $flawlessChecklists;

        $hardwareIntegrityRate =
            $totalChecklists > 0
            ? round(
                ($flawlessChecklists / $totalChecklists) * 100,
                1
            )
            : 100.0;

        $peripheralFailures = [
            'system_unit' => (clone $checklistQuery)
                ->where(
                    'system_unit_ok',
                    false
                )
                ->count(),

            'monitor' => (clone $checklistQuery)
                ->where(
                    'monitor_ok',
                    false
                )
                ->count(),

            'avr' => (clone $checklistQuery)
                ->where(
                    'avr_ok',
                    false
                )
                ->count(),

            'mouse' => (clone $checklistQuery)
                ->where(
                    'mouse_ok',
                    false
                )
                ->count(),

            'keyboard' => (clone $checklistQuery)
                ->where(
                    'keyboard_ok',
                    false
                )
                ->count(),

            'cables' => (clone $checklistQuery)
                ->where(
                    'cables_ok',
                    false
                )
                ->count(),
        ];

        $recentIssues = (clone $checklistQuery)
            ->where(function ($q) {
                $q->where(
                    'all_operational',
                    false
                )
                    ->orWhere(
                        'system_unit_ok',
                        false
                    )
                    ->orWhere(
                        'monitor_ok',
                        false
                    )
                    ->orWhere(
                        'avr_ok',
                        false
                    )
                    ->orWhere(
                        'mouse_ok',
                        false
                    )
                    ->orWhere(
                        'keyboard_ok',
                        false
                    )
                    ->orWhere(
                        'cables_ok',
                        false
                    );
            })
            ->latest('id')
            ->take(6)
            ->get();

        $computerFleet =
            Computer::when(
                $selectedLabId,
                fn($q) =>
                $q->where(
                    'lab_id',
                    $selectedLabId
                )
            );

        $totalComputers =
            (clone $computerFleet)->count();

        $fleetActive =
            (clone $computerFleet)
            ->where(
                'status',
                'active'
            )
            ->count();

        $fleetAvailable =
            (clone $computerFleet)
            ->where(
                'status',
                'available'
            )
            ->count();

        $fleetMaint =
            (clone $computerFleet)
            ->where(
                'status',
                'maintenance'
            )
            ->count();

        $pendingAlerts =
            (clone $alertQuery)
            ->where(
                'status',
                'pending'
            )
            ->count();

        $resolvedAlerts =
            (clone $alertQuery)
            ->where(
                'status',
                'resolved'
            )
            ->count();

        $discardedAlerts =
            (clone $alertQuery)
            ->where(
                'status',
                'discarded'
            )
            ->count();

        $hourlyDistribution =
            (clone $sessionQuery)
            ->selectRaw(
                'HOUR(time_in) as hour, count(*) as count'
            )
            ->whereNotNull('time_in')
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck(
                'count',
                'hour'
            )
            ->toArray();

        $hourlyData = [];

        for ($h = 7; $h <= 20; $h++) {
            $hourlyData[] = [
                'hour' =>
                Carbon::createFromTime($h)
                    ->format('g A'),

                'count' =>
                $hourlyDistribution[$h] ?? 0,
            ];
        }

        $allLabs =
            Lab::orderBy('name')->get();

        return view(
            'super-admin.analytics',
            compact(
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
            )
        );
    }

    // ==========================================================
    // ANALYTICS EXPORT
    // ==========================================================

    public function export(Request $request)
    {
        $type =
            $request->query(
                'type',
                'checklists'
            );

        $startDate =
            $request->filled('start_date')
            ? Carbon::parse(
                $request->start_date
            )->startOfDay()
            : now()->subDays(7)->startOfDay();

        $endDate =
            $request->filled('end_date')
            ? Carbon::parse(
                $request->end_date
            )->endOfDay()
            : now()->endOfDay();

        $labId =
            $request->query('lab_id');

        if ($type === 'alerts') {
            return $this->exportAlerts(
                $startDate,
                $endDate,
                $labId
            );
        }

        return $this->exportChecklists(
            $startDate,
            $endDate,
            $labId
        );
    }

    private function exportChecklists(
        $start,
        $end,
        $labId
    ) {
        $filename =
            "Hardware_Checkins_" .
            now()->format('Ymd_His') .
            ".csv";

        $query =
            SessionChecklist::whereBetween(
                'created_at',
                [$start, $end]
            );

        if ($labId) {
            $lab = Lab::find($labId);

            if ($lab) {
                $query->where(
                    'lab_name',
                    $lab->name
                );
            }
        }

        $records =
            $query->latest('id')->get();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' =>
            "attachment; filename={$filename}",
            'Pragma' => 'no-cache',
            'Cache-Control' =>
            'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $this->logActivity(
            'system',
            'Exported hardware check-in records.',
            'info',
            null,
            [
                'export_type' => 'checklists',
                'record_count' => $records->count(),
                'lab_id' => $labId,
            ]
        );

        $callback = function () use ($records) {

            $handle =
                fopen(
                    'php://output',
                    'w'
                );

            fputcsv(
                $handle,
                [
                    'ID',
                    'PC Number',
                    'Lab',
                    'Student ID',
                    'Monitor',
                    'Keyboard',
                    'Mouse',
                    'AVR',
                    'Chassis',
                    'Headset',
                    'All Operational',
                    'Verified At',
                ]
            );

            foreach ($records as $r) {

                fputcsv(
                    $handle,
                    [
                        $r->id,
                        $r->pc_number,
                        $r->lab_name,
                        $r->student_id_number,
                        $r->monitor_ok
                            ? 'PASS'
                            : 'FAIL',
                        $r->keyboard_ok
                            ? 'PASS'
                            : 'FAIL',
                        $r->mouse_ok
                            ? 'PASS'
                            : 'FAIL',
                        $r->avr_ok
                            ? 'PASS'
                            : 'FAIL',
                        $r->pc_case_ok
                            ? 'PASS'
                            : 'FAIL',
                        $r->headset_ok
                            ? 'PASS'
                            : 'FAIL',
                        $r->all_operational
                            ? 'YES'
                            : 'NO',
                        $r->verified_at
                            ?->format(
                                'Y-m-d H:i:s'
                            ),
                    ]
                );
            }

            fclose($handle);
        };

        return response()->stream(
            $callback,
            200,
            $headers
        );
    }

    private function exportAlerts(
        $start,
        $end,
        $labId
    ) {
        $filename =
            "Security_Alerts_" .
            now()->format('Ymd_His') .
            ".csv";

        $query =
            Alert::with([
                'computer',
                'lab',
            ])->whereBetween(
                'created_at',
                [$start, $end]
            );

        if ($labId) {
            $query->where(
                'lab_id',
                $labId
            );
        }

        $records =
            $query->latest('id')->get();

        $this->logActivity(
            'incident_response',
            'Exported security alert records.',
            'info',
            null,
            [
                'export_type' => 'alerts',
                'record_count' => $records->count(),
                'lab_id' => $labId,
            ]
        );

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' =>
            "attachment; filename={$filename}",
            'Pragma' => 'no-cache',
            'Cache-Control' =>
            'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($records) {

            $handle =
                fopen(
                    'php://output',
                    'w'
                );

            fputcsv(
                $handle,
                [
                    'ID',
                    'PC Number',
                    'Lab',
                    'Issue Type',
                    'Status',
                    'Remarks',
                    'Date Reported',
                    'Resolved At',
                ]
            );

            foreach ($records as $r) {

                fputcsv(
                    $handle,
                    [
                        $r->id,
                        $r->computer->pc_number
                            ?? 'N/A',
                        $r->lab->name
                            ?? 'N/A',
                        $r->issue_type,
                        strtoupper(
                            $r->status
                        ),
                        $r->remarks,
                        $r->created_at
                            ->format(
                                'Y-m-d H:i:s'
                            ),
                        $r->resolved_at
                            ?->format(
                                'Y-m-d H:i:s'
                            )
                            ?? 'N/A',
                    ]
                );
            }

            fclose($handle);
        };

        return response()->stream(
            $callback,
            200,
            $headers
        );
    }

    public function exportReport(Request $request)
    {
        $type =
            $request->query(
                'type',
                'checklists'
            );

        if (
            $request->filled('start_date') &&
            $request->filled('end_date')
        ) {

            $startDate =
                Carbon::parse(
                    $request->start_date
                )->startOfDay();

            $endDate =
                Carbon::parse(
                    $request->end_date
                )->endOfDay();
        } else {

            $range =
                $request->query(
                    'range',
                    'week'
                );

            $startDate =
                match ($range) {
                    'today' =>
                    now()->startOfDay(),

                    'week' =>
                    now()->subDays(7)->startOfDay(),

                    'month' =>
                    now()->startOfMonth(),

                    default =>
                    Carbon::createFromTimestamp(0),
                };

            $endDate =
                now()->endOfDay();
        }

        $labId =
            $request->query('lab_id');

        $this->logActivity(
            'system',
            "Exported {$type} report.",
            'info',
            null,
            [
                'report_type' => $type,
                'lab_id' => $labId,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ]
        );

        if ($type === 'alerts') {
            return $this->exportAlertsCsv(
                $startDate,
                $endDate,
                $labId
            );
        }

        return $this->exportChecklistsCsv(
            $startDate,
            $endDate,
            $labId
        );
    }

    protected function exportChecklistsCsv(
        $startDate,
        $endDate,
        $labId = null
    ) {
        $filename =
            "Hardware_Checkins_" .
            now()->format('Ymd_His') .
            ".csv";

        $query =
            SessionChecklist::whereBetween(
                'created_at',
                [$startDate, $endDate]
            );

        if ($labId) {

            $lab =
                Lab::find($labId);

            if ($lab) {

                $query->where(
                    function ($q) use ($lab) {

                        $q->where(
                            'lab_name',
                            $lab->name
                        )->orWhereHas(
                            'labSession',
                            fn($sq) =>
                            $sq->where(
                                'lab_id',
                                $lab->id
                            )
                        );
                    }
                );
            }
        }

        $records =
            $query->latest('id')->get();

        $headers = [
            'Content-type' =>
            'text/csv; charset=UTF-8',

            'Content-Disposition' =>
            "attachment; filename={$filename}",

            'Pragma' =>
            'no-cache',

            'Cache-Control' =>
            'must-revalidate, post-check=0, pre-check=0',

            'Expires' =>
            '0',
        ];

        return response()->stream(
            function () use ($records) {

                $handle =
                    fopen(
                        'php://output',
                        'w'
                    );

                fprintf(
                    $handle,
                    chr(0xEF) .
                        chr(0xBB) .
                        chr(0xBF)
                );

                fputcsv(
                    $handle,
                    [
                        'Audit ID',
                        'Station / PC Number',
                        'Laboratory Zone',
                        'Student ID Number',
                        'Display Monitor',
                        'Keyboard Unit',
                        'Optical Mouse',
                        'Power Unit (AVR)',
                        'PC Chassis',
                        'Audio / Headset',
                        'All Operational?',
                        'Verified At',
                    ]
                );

                foreach ($records as $r) {

                    fputcsv(
                        $handle,
                        [
                            $r->id,
                            $r->pc_number,
                            $r->lab_name
                                ?? 'N/A',
                            $r->student_id_number,

                            $r->monitor_ok
                                ? 'PASS'
                                : 'FAIL',

                            $r->keyboard_ok
                                ? 'PASS'
                                : 'FAIL',

                            $r->mouse_ok
                                ? 'PASS'
                                : 'FAIL',

                            $r->avr_ok
                                ? 'PASS'
                                : 'FAIL',

                            $r->pc_case_ok
                                ? 'PASS'
                                : 'FAIL',

                            $r->headset_ok
                                ? 'PASS'
                                : 'FAIL',

                            $r->all_operational
                                ? 'YES'
                                : 'NO',

                            optional(
                                $r->verified_at
                            )->format(
                                'Y-m-d H:i:s'
                            )
                                ??
                                optional(
                                    $r->created_at
                                )->format(
                                    'Y-m-d H:i:s'
                                ),
                        ]
                    );
                }

                fclose($handle);
            },
            200,
            $headers
        );
    }

    protected function exportAlertsCsv(
        $startDate,
        $endDate,
        $labId = null
    ) {
        $filename =
            "Security_Alerts_" .
            now()->format('Ymd_His') .
            ".csv";

        $query =
            Alert::with([
                'computer.lab',
                'reporter',
            ])->whereBetween(
                'created_at',
                [$startDate, $endDate]
            );

        if ($labId) {
            $query->where(
                'lab_id',
                $labId
            );
        }

        $records =
            $query->latest('id')->get();

        $headers = [
            'Content-type' =>
            'text/csv; charset=UTF-8',

            'Content-Disposition' =>
            "attachment; filename={$filename}",

            'Pragma' =>
            'no-cache',

            'Cache-Control' =>
            'must-revalidate, post-check=0, pre-check=0',

            'Expires' =>
            '0',
        ];

        return response()->stream(
            function () use ($records) {

                $handle =
                    fopen(
                        'php://output',
                        'w'
                    );

                fprintf(
                    $handle,
                    chr(0xEF) .
                        chr(0xBB) .
                        chr(0xBF)
                );

                fputcsv(
                    $handle,
                    [
                        'Alert ID',
                        'PC Number',
                        'Laboratory Zone',
                        'Reported By (Name)',
                        'Student ID / Number',
                        'Issue Category',
                        'Status',
                        'Remarks / Problem Description',
                        'Reported Date',
                        'Resolved Date',
                    ]
                );

                foreach ($records as $r) {

                    fputcsv(
                        $handle,
                        [
                            $r->id,

                            $r->computer->pc_number
                                ?? 'N/A',

                            $r->lab->name
                                ?? $r->computer->lab->name
                                ?? 'N/A',

                            $r->reporter->name
                                ?? $r->reportedBy->name
                                ?? 'Student',

                            $r->reporter->student_number
                                ?? $r->reportedBy->student_number
                                ?? 'N/A',

                            $r->issue_type,

                            strtoupper(
                                $r->status
                            ),

                            $r->remarks
                                ?? $r->description
                                ?? 'N/A',

                            optional(
                                $r->created_at
                            )->format(
                                'Y-m-d H:i:s'
                            ),

                            optional(
                                $r->resolved_at
                            )->format(
                                'Y-m-d H:i:s'
                            )
                                ?? 'N/A',
                        ]
                    );
                }

                fclose($handle);
            },
            200,
            $headers
        );
    }

    // ==========================================================
    // USER CSV IMPORT
    // ==========================================================

    public function import(Request $request)
    {
        $request->validate([
            'file' =>
            'required|mimes:csv,txt|max:5120',
        ]);

        $file =
            $request->file('file');

        if (
            !$file ||
            !(
                $handle = fopen(
                    $file->getRealPath(),
                    'r'
                )
            )
        ) {

            $this->flashToast(
                'danger',
                'Import Failed',
                'Could not open uploaded file.'
            );

            return back()
                ->withErrors([
                    'file' =>
                    'Could not open uploaded file.',
                ]);
        }

        $rawHeader =
            fgetcsv(
                $handle,
                1000,
                ','
            );

        if (!$rawHeader) {

            fclose($handle);

            $this->flashToast(
                'danger',
                'Import Failed',
                'The uploaded file is empty.'
            );

            return back()
                ->withErrors([
                    'file' =>
                    'The uploaded file is empty.',
                ]);
        }

        $header =
            array_map(
                function ($col) {

                    $col =
                        preg_replace(
                            '/[\x{EF}\x{BB}\x{BF}]/u',
                            '',
                            $col
                        );

                    return strtolower(
                        trim($col)
                    );
                },
                $rawHeader
            );

        $requiredColumns = [
            'name',
            'email',
            'student_number',
            'phone',
            'role',
            'password',
        ];

        $missingColumns =
            array_diff(
                $requiredColumns,
                $header
            );

        if (!empty($missingColumns)) {

            fclose($handle);

            $missingStr =
                implode(
                    ', ',
                    $missingColumns
                );

            $this->logActivity(
                'user_management',
                'User CSV import was rejected because required columns were missing.',
                'warning',
                null,
                [
                    'missing_columns' =>
                    $missingColumns,
                ]
            );

            $this->flashToast(
                'danger',
                'Invalid CSV Format',
                "Missing required columns: {$missingStr}"
            );

            return back()
                ->withErrors([
                    'file' =>
                    "Missing required columns: {$missingStr}",
                ]);
        }

        $importedCount = 0;
        $skippedCount = 0;

        DB::beginTransaction();

        try {

            while (
                (
                    $row = fgetcsv(
                        $handle,
                        1000,
                        ','
                    )
                ) !== false
            ) {

                if (
                    empty(array_filter($row))
                ) {
                    continue;
                }

                if (
                    count($row) <
                    count($header)
                ) {

                    $skippedCount++;

                    continue;
                }

                $data =
                    array_combine(
                        $header,
                        $row
                    );

                $email =
                    trim(
                        $data['email'] ?? ''
                    );

                $studentNumber =
                    trim(
                        $data['student_number'] ?? ''
                    );

                if (
                    empty($email) ||
                    User::where(
                        'email',
                        $email
                    )->orWhere(
                        'student_number',
                        $studentNumber
                    )->exists()
                ) {

                    $skippedCount++;

                    continue;
                }

                User::create([
                    'name' =>
                    trim(
                        $data['name']
                    ),

                    'email' =>
                    $email,

                    'student_number' =>
                    $studentNumber,

                    'phone' =>
                    trim(
                        $data['phone']
                    ),

                    'role' =>
                    in_array(
                        strtolower(
                            trim(
                                $data['role']
                            )
                        ),
                        [
                            'student',
                            'personnel',
                            'admin',
                        ]
                    )
                        ? strtolower(
                            trim(
                                $data['role']
                            )
                        )
                        : 'student',

                    'password' =>
                    Hash::make(
                        trim(
                            $data['password']
                        )
                    ),
                ]);

                $importedCount++;
            }

            DB::commit();

            fclose($handle);

            if ($importedCount === 0) {

                $message =
                    "No new users were imported. " .
                    "({$skippedCount} duplicate or invalid records skipped).";

                $this->logActivity(
                    'user_management',
                    'User CSV import completed with no new users added.',
                    'warning',
                    null,
                    [
                        'imported_count' =>
                        0,

                        'skipped_count' =>
                        $skippedCount,
                    ]
                );

                $this->flashToast(
                    'warning',
                    'Import Completed',
                    $message
                );

                return back()
                    ->with(
                        'status',
                        $message
                    );
            }

            $message =
                "Successfully enrolled {$importedCount} new user(s).";

            if ($skippedCount > 0) {

                $message .=
                    " ({$skippedCount} duplicate/invalid records were skipped).";
            }

            $this->logActivity(
                'user_management',
                "Imported {$importedCount} new user account(s).",
                $skippedCount > 0
                    ? 'warning'
                    : 'info',
                null,
                [
                    'imported_count' =>
                    $importedCount,

                    'skipped_count' =>
                    $skippedCount,
                ]
            );

            $this->flashToast(
                'success',
                'Mass Enrollment Complete',
                $message
            );

            return back()
                ->with(
                    'success',
                    $message
                );
        } catch (\Exception $e) {

            DB::rollBack();

            fclose($handle);

            $this->logActivity(
                'user_management',
                'User CSV import failed.',
                'danger',
                null,
                [
                    'error' =>
                    $e->getMessage(),
                ]
            );

            $this->flashToast(
                'danger',
                'Import Failed',
                'Critical System Error: ' .
                    $e->getMessage()
            );

            return back()
                ->withErrors([
                    'file' =>
                    'Import failed: ' .
                        $e->getMessage(),
                ]);
        }
    }
}
