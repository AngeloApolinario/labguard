<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Computer;
use App\Models\Lab;
use App\Models\LabSession;
use App\Models\Schedule;
use App\Models\SessionChecklist;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Spatie\Activitylog\Models\Activity;

class SuperAdminApiController extends Controller
{
    /**
     * Super Admin overview metrics, lab utilization, and recent alerts.
     */
    public function index(): JsonResponse
    {
        $totalUsers          = User::count();
        $activeSessionsCount = LabSession::whereNull('time_out')->count();
        $pendingAlertsCount  = Alert::where('status', 'pending')->count();

        $labs      = Lab::with('computers')->get();
        $totalLabs = $labs->count();

        $recentAlerts = Alert::with(['lab', 'computer'])
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'totalUsers'     => $totalUsers,
            'activeSessions' => $activeSessionsCount,
            'alerts'         => $pendingAlertsCount,
            'totalLabs'      => $totalLabs,
            'labUtilization' => $labs,
            'recentAlerts'   => $recentAlerts,
        ]);
    }

    /**
     * Security health checks and telemetry feed.
     */
    public function security(): JsonResponse
    {
        $securityStats = [
            'score'           => '92/100',
            'threats'         => 3,
            'vulnerabilities' => 5,
        ];

        $alerts = [
            [
                'type'      => 'critical',
                'title'     => 'Multiple Failed Login Attempts',
                'badge'     => 'Critical',
                'desc'      => 'IP 192.168.1.45 detected 5 failed login attempts in the last 10 minutes',
                'time'      => '2 minutes ago',
                'action'    => 'Block IP',
                'icon'      => 'heroicon-o-exclamation-triangle',
                'iconColor' => 'text-rose-500',
                'bgColor'   => 'bg-rose-50',
            ],
            [
                'type'      => 'warning',
                'title'     => 'Unusual Access Pattern',
                'badge'     => 'Warning',
                'desc'      => 'User account accessed from a new location: Tokyo, Japan',
                'time'      => '1 hour ago',
                'action'    => 'Review',
                'icon'      => 'heroicon-o-exclamation-circle',
                'iconColor' => 'text-amber-500',
                'bgColor'   => 'bg-amber-50',
            ],
            [
                'type'      => 'info',
                'title'     => 'System Update Available',
                'badge'     => 'Info',
                'desc'      => 'Security patch v2.5.3 available for system kernel',
                'time'      => '1 hour ago',
                'action'    => 'Update',
                'icon'      => 'heroicon-o-information-circle',
                'iconColor' => 'text-blue-500',
                'bgColor'   => 'bg-blue-50',
            ],
        ];

        return response()->json([
            'securityStats' => $securityStats,
            'alerts'        => $alerts,
        ]);
    }

    /**
     * Global system preferences.
     */
    public function settings(): JsonResponse
    {
        return response()->json([
            'settings' => [
                'system_name'     => 'LabGuard - Computer Lab Management',
                'institution'     => 'Au University',
                'backup_time'     => '02:00',
                'session_timeout' => '30',
                'system_email'    => 'admin@labguard.edu',
            ]
        ]);
    }

    /**
     * Paginated system users list.
     */
    public function userManagement(): JsonResponse
    {
        $users = User::orderBy('role', 'asc')
            ->orderBy('name', 'asc')
            ->paginate(15);

        return response()->json($users);
    }

    /**
     * Enroll new administrative or student account.
     */
    public function storeUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'       => [
                'required',
                'string',
                Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
            'role'           => ['required', 'in:student,personnel,admin,super-admin'],
            'student_number' => ['required', 'string', 'unique:users', 'regex:/^01-[0-9]{4}-[0-9]{6}$/'],
            'phone'          => ['required', 'string', 'regex:/^09[0-9]{9}$/'],
        ]);

        $user = User::create([
            'name'              => $validated['name'],
            'email'             => $validated['email'],
            'password'          => Hash::make($validated['password']),
            'role'              => $validated['role'],
            'student_number'    => $validated['student_number'],
            'phone'             => $validated['phone'],
            'email_verified_at' => now(),
        ]);

        return response()->json([
            'message' => "{$user->role} account created successfully.",
            'user'    => $user,
        ], 201);
    }

    /**
     * Update account details.
     */
    public function updateUser(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'student_number' => ['required', 'string', 'unique:users,student_number,' . $user->id],
            'phone'          => ['required', 'string', 'regex:/^09[0-9]{9}$/'],
            'role'           => ['required', 'in:student,personnel,admin,super-admin'],
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Account updated successfully.',
            'user'    => $user,
        ]);
    }

    /**
     * Delete an account (blocks self-deletion).
     */
    public function destroyUser(User $user): JsonResponse
    {
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'You cannot delete your own account.'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'User removed from system.']);
    }

    /**
     * All facilities with active/total counts.
     */
    public function labs(): JsonResponse
    {
        $labs = Lab::withCount([
            'computers as total_pcs',
            'computers as active_pcs' => function ($query) {
                $query->where('status', 'active');
            },
        ])->get();

        return response()->json($labs);
    }

    /**
     * View Lab schedule and assignable instructors.
     */
    public function viewSchedule(Lab $lab): JsonResponse
    {
        $schedules = Schedule::where('lab_id', $lab->id)
            ->with('user')
            ->orderByRaw("FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")
            ->orderBy('start_time', 'asc')
            ->get();

        $teachers = User::whereIn('role', ['personnel', 'admin', 'super-admin'])->get();

        return response()->json([
            'lab'       => $lab,
            'schedules' => $schedules,
            'teachers'  => $teachers,
        ]);
    }

    /**
     * Store new master schedule entry.
     */
    public function storeSchedule(Request $request, Lab $lab): JsonResponse
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
            })->exists();

        if ($overlap) {
            return response()->json([
                'message' => 'Schedule Conflict: This time slot is already taken.',
            ], 422);
        }

        $schedule = Schedule::create([
            'lab_id'       => $lab->id,
            'user_id'      => $validated['user_id'],
            'subject_code' => $validated['subject_code'],
            'day'          => $validated['day'],
            'start_time'   => $validated['start_time'],
            'end_time'     => $validated['end_time'],
        ]);

        return response()->json([
            'message'  => 'Master Schedule updated successfully.',
            'schedule' => $schedule,
        ], 201);
    }

    /**
     * Delete schedule slot.
     */
    public function destroySchedule(Schedule $schedule): JsonResponse
    {
        $schedule->delete();

        return response()->json([
            'message' => 'Schedule entry removed from Master Control.',
        ]);
    }

    /**
     * Paginated completed sessions archive.
     */
    public function sessions(Request $request): JsonResponse
    {
        $query = LabSession::with(['computer'])
            ->whereNotNull('time_out')
            ->latest('time_out');

        if ($request->filled('student_name')) {
            $query->where('student_name', 'like', '%' . $request->student_name . '%');
        }

        if ($request->filled('pc_number')) {
            $query->whereHas('computer', function ($q) use ($request) {
                $q->where('pc_number', 'like', '%' . $request->pc_number . '%');
            });
        }

        if ($request->filled('date')) {
            $query->whereDate('time_in', $request->date);
        }

        $sessions = $query->paginate(15);

        return response()->json($sessions);
    }

    /**
     * Stream administrative summary reports (utilization or security).
     */
    public function generateReport(Request $request)
    {
        $request->validate([
            'type'  => 'required|in:utilization,security',
            'range' => 'required|in:today,week,month',
        ]);

        $timeframe = match ($request->range) {
            'today' => now()->startOfDay(),
            'week'  => now()->subDays(7),
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

        Log::info("Super Admin generated a {$request->type} report for range: {$request->range}");

        $fileName = "labguard_{$request->type}_report_" . now()->format('Y-m-d') . ".csv";

        $headers = [
            'Content-type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () use ($data, $request) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            if ($request->type === 'utilization') {
                fputcsv($file, ['Session ID', 'Student', 'Laboratory Room', 'Logged In At', 'Logged Out At']);
                foreach ($data as $row) {
                    fputcsv($file, [
                        $row->id,
                        $row->user->name ?? $row->student_name ?? 'N/A',
                        $row->lab->room_name ?? $row->lab->name ?? 'N/A',
                        $row->time_in,
                        $row->time_out ?? 'Active',
                    ]);
                }
            } else {
                fputcsv($file, ['Alert ID', 'Room', 'Station PC', 'Issue Category', 'Status', 'Logged At']);
                foreach ($data as $row) {
                    fputcsv($file, [
                        $row->id,
                        $row->lab->room_name ?? $row->lab->name ?? 'N/A',
                        $row->computer->pc_number ?? 'N/A',
                        $row->issue_type ?? 'Technical',
                        $row->status,
                        $row->created_at,
                    ]);
                }
            }

            fclose($file);
        }, 200, $headers);
    }

    /**
     * Execute MySQL database snapshot on host server.
     */
    public function triggerBackup(): JsonResponse
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

        $output    = [];
        $returnVar = null;
        exec($command, $output, $returnVar);

        if ($returnVar === 0) {
            Log::info("Super Admin successfully initialized database snapshot dump file: {$filename}");
            return response()->json(['message' => 'Database snapshot generated and archived successfully.']);
        }

        Log::error('Database dump routine failed execution.', [
            'exit_code' => $returnVar,
            'output'    => implode("\n", $output),
            'command'   => $command,
        ]);

        return response()->json([
            'message' => 'Database utility structural engine execution failed. Check server logs.',
        ], 500);
    }

    /**
     * Emergency Broadcast Protocol - Immediate Global Lockout.
     */
    public function lockout(Request $request): JsonResponse
    {
        $labId = $request->input('lab_id');

        if ($labId === 'all') {
            Lab::query()->update(['status' => 'maintenance']);
            Computer::query()->update(['status' => 'maintenance']);
        } else {
            $lab = Lab::findOrFail($labId);
            $lab->update(['status' => 'maintenance']);
            $lab->computers()->update(['status' => 'maintenance']);
        }

        return response()->json(['message' => 'Lab maintenance lockdown initiated.']);
    }

    /**
     * Release maintenance lockout.
     */
    public function releaseLockout(Request $request): JsonResponse
    {
        $labId = $request->input('lab_id');

        if ($labId === 'all') {
            Lab::query()->update(['status' => 'active']);
            Computer::where('status', 'maintenance')->update(['status' => 'available']);
        } else {
            $lab = Lab::findOrFail($labId);
            $lab->update(['status' => 'active']);
            $lab->computers()->where('status', 'maintenance')->update(['status' => 'available']);
        }

        return response()->json(['message' => 'Lab maintenance lock released.']);
    }

    /**
     * Activity and audit log telemetry.
     */
    public function logs(Request $request): JsonResponse
    {
        $query = Activity::with(['causer', 'subject'])->latest();

        if ($request->has('category') && $request->category !== 'all') {
            $query->where('log_name', $request->category);
        }

        $logs = $query->paginate(12);

        $stats = [
            'total'     => Activity::count(),
            'incidents' => Activity::where('log_name', 'incident_response')->count(),
            'users'     => Activity::where('log_name', 'user_management')->count(),
            'labs'      => Activity::where('log_name', 'lab_management')->count(),
        ];

        return response()->json([
            'logs'  => $logs,
            'stats' => $stats,
        ]);
    }

    /**
     * Deep analytics, hardware inspection, and velocity metrics.
     */
    public function analytics(Request $request): JsonResponse
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

        $totalChecklists    = (clone $checklistQuery)->count();
        $flawlessChecklists = (clone $checklistQuery)->where('all_operational', true)->count();
        $flaggedChecklists  = $totalChecklists - $flawlessChecklists;

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

        $computerFleet  = Computer::when($selectedLabId, fn($q) => $q->where('lab_id', $selectedLabId));
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

        return response()->json(compact(
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

    /**
     * Export Handler for Check-ins and Alerts CSV.
     */
    public function export(Request $request)
    {
        $type      = $request->query('type', 'checklists');
        $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : now()->subDays(7)->startOfDay();
        $endDate   = $request->filled('end_date') ? Carbon::parse($request->end_date)->endOfDay() : now()->endOfDay();
        $labId     = $request->query('lab_id');

        if ($type === 'alerts') {
            return $this->exportAlertsCsv($startDate, $endDate, $labId);
        }

        return $this->exportChecklistsCsv($startDate, $endDate, $labId);
    }

    protected function exportChecklistsCsv($startDate, $endDate, $labId = null)
    {
        $filename = "Hardware_Checkins_" . now()->format('Ymd_His') . ".csv";

        $query = SessionChecklist::whereBetween('created_at', [$startDate, $endDate]);

        if ($labId) {
            $lab = Lab::find($labId);
            if ($lab) {
                $query->where(function ($q) use ($lab) {
                    $q->where('lab_name', $lab->name)
                        ->orWhereHas('labSession', fn($sq) => $sq->where('lab_id', $lab->id));
                });
            }
        }

        $records = $query->latest('id')->get();

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename={$filename}",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0",
        ];

        return response()->stream(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
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
            ]);

            foreach ($records as $r) {
                fputcsv($handle, [
                    $r->id,
                    $r->pc_number,
                    $r->lab_name ?? 'N/A',
                    $r->student_id_number,
                    $r->monitor_ok ? 'PASS' : 'FAIL',
                    $r->keyboard_ok ? 'PASS' : 'FAIL',
                    $r->mouse_ok ? 'PASS' : 'FAIL',
                    $r->avr_ok ? 'PASS' : 'FAIL',
                    $r->pc_case_ok ? 'PASS' : 'FAIL',
                    $r->headset_ok ? 'PASS' : 'FAIL',
                    $r->all_operational ? 'YES' : 'NO',
                    optional($r->verified_at)->format('Y-m-d H:i:s') ?? optional($r->created_at)->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    protected function exportAlertsCsv($startDate, $endDate, $labId = null)
    {
        $filename = "Security_Alerts_" . now()->format('Ymd_His') . ".csv";

        $query = Alert::with(['computer.lab', 'reporter'])->whereBetween('created_at', [$startDate, $endDate]);

        if ($labId) {
            $query->where('lab_id', $labId);
        }

        $records = $query->latest('id')->get();

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename={$filename}",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0",
        ];

        return response()->stream(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
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
            ]);

            foreach ($records as $r) {
                fputcsv($handle, [
                    $r->id,
                    $r->computer->pc_number ?? 'N/A',
                    $r->lab->name ?? $r->computer->lab->name ?? 'N/A',
                    $r->reporter->name ?? 'Student',
                    $r->reporter->student_number ?? 'N/A',
                    $r->issue_type,
                    strtoupper($r->status),
                    $r->remarks ?? 'N/A',
                    optional($r->created_at)->format('Y-m-d H:i:s'),
                    optional($r->resolved_at)->format('Y-m-d H:i:s') ?? 'N/A',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Batch import users from CSV.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('file');

        if (!$file || !($handle = fopen($file->getRealPath(), 'r'))) {
            return response()->json(['message' => 'Could not open uploaded file.'], 422);
        }

        $rawHeader = fgetcsv($handle, 1000, ',');

        if (!$rawHeader) {
            fclose($handle);
            return response()->json(['message' => 'The uploaded file is empty.'], 422);
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
            return response()->json(['message' => "Missing required columns: {$missingStr}"], 422);
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
                    'role'           => in_array(strtolower(trim($data['role'])), ['student', 'personnel', 'admin', 'super-admin']) ? strtolower(trim($data['role'])) : 'student',
                    'password'       => Hash::make(trim($data['password'])),
                ]);

                $importedCount++;
            }

            DB::commit();
            fclose($handle);

            if ($importedCount === 0) {
                return response()->json([
                    'type'    => 'warning',
                    'message' => "No new users were imported. ({$skippedCount} duplicate or invalid records skipped).",
                ]);
            }

            $message = "Successfully enrolled {$importedCount} new user(s).";
            if ($skippedCount > 0) {
                $message .= " ({$skippedCount} duplicate/invalid records were skipped).";
            }

            return response()->json([
                'type'    => 'success',
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);

            return response()->json(['message' => 'Critical System Error: ' . $e->getMessage()], 500);
        }
    }
}
