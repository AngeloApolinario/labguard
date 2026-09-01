<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;

use App\Models\Alert;
use App\Models\Computer;
use App\Models\Lab;
use App\Models\LabSession;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SuperAdminController extends Controller
{
    /**
     * Helper for standardized API JSON responses
     */
    private function jsonResponse(bool $success, string $message, $data = null, int $statusCode = 200): JsonResponse
    {
        $payload = [
            'success' => $success,
            'message' => $message,
        ];

        if ($data !== null) {
            $payload['data'] = $data;
        }

        return response()->json($payload, $statusCode);
    }

    /**
     * Dashboard Overview Metrics
     */
    public function index(): JsonResponse
    {
        // 1. Fetch Summary Card Metrics
        $totalUsers = User::count();
        $activeSessionsCount = LabSession::whereNull('time_out')->count();
        $pendingAlertsCount = Alert::where('status', 'pending')->count();

        // 2. Fetch all Laboratories
        $labs = Lab::with('computers')->get();
        $totalLabs = $labs->count();

        // 3. Count active sessions grouped by lab
        $activeSessionsPerLab = LabSession::whereNull('time_out')
            ->select('lab_id', DB::raw('count(*) as active_count'))
            ->groupBy('lab_id')
            ->pluck('active_count', 'lab_id')
            ->all();

        // 4. Map lab utilization
        $labUtilization = [];
        foreach ($labs as $lab) {
            $currentActiveCount = $activeSessionsPerLab[$lab->id] ?? 0;
            $capacity = $lab->capacity > 0 ? $lab->capacity : 1;
            $percentage = ($currentActiveCount / $capacity) * 100;

            $labUtilization[] = [
                'lab_id' => $lab->id,
                'name' => $lab->name ?? $lab->room_name,
                'capacity' => $lab->capacity,
                'active_sessions' => $currentActiveCount,
                'total_computers' => $lab->computers->count(),
                'utilization_percent' => min(100, round($percentage)),
            ];
        }

        // 5. Recent Alerts
        $recentAlerts = Alert::with(['lab', 'computer'])
            ->latest()
            ->take(5)
            ->get();

        return $this->jsonResponse(true, 'Dashboard metrics retrieved successfully.', [
            'total_users' => $totalUsers,
            'active_sessions' => $activeSessionsCount,
            'pending_alerts' => $pendingAlertsCount,
            'total_labs' => $totalLabs,
            'lab_utilization' => $labUtilization,
            'recent_alerts' => $recentAlerts,
        ]);
    }

    /**
     * Security Threat Overview
     */
    public function security(): JsonResponse
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
            ],
            [
                'type' => 'warning',
                'title' => 'Unusual Access Pattern',
                'badge' => 'Warning',
                'desc' => 'User account accessed from a new location',
                'time' => '1 hour ago',
                'action' => 'Review',
            ],
            [
                'type' => 'info',
                'title' => 'System Update Available',
                'badge' => 'Info',
                'desc' => 'Security patch v2.5.3 available for system kernel',
                'time' => '1 hour ago',
                'action' => 'Update',
            ],
        ];

        return $this->jsonResponse(true, 'Security stats retrieved.', [
            'security_stats' => $securityStats,
            'security_alerts' => $alerts,
        ]);
    }

    /**
     * System Settings Object
     */
    public function settings(): JsonResponse
    {
        $settings = [
            'system_name' => 'LabGuard - Computer Lab Management',
            'institution' => 'Au University',
            'backup_time' => '02:00',
            'session_timeout' => '30',
            'system_email' => 'admin@labguard.edu',
        ];

        return $this->jsonResponse(true, 'System settings retrieved.', $settings);
    }

    /**
     * User Management - List All Users
     */
    public function userManagement(Request $request): JsonResponse
    {
        $users = User::orderBy('role', 'asc')
            ->orderBy('name', 'asc')
            ->paginate($request->query('per_page', 15));

        return $this->jsonResponse(true, 'Users list retrieved.', $users);
    }

    /**
     * Store New User
     */
    public function storeUser(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', Rules\Password::defaults()],
            'role' => ['required', 'in:student,personnel,admin,super-admin'],
            'student_number' => ['required', 'string', 'unique:users', 'regex:/^01-[0-9]{4}-[0-9]{6}$/'],
            'phone' => ['required', 'string', 'regex:/^09[0-9]{9}$/'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'student_number' => $request->student_number,
            'phone' => $request->phone,
            'email_verified_at' => now(),
        ]);

        return $this->jsonResponse(true, "{$user->role} account created successfully.", $user, 201);
    }

    /**
     * Update User Info
     */
    public function updateUser(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'student_number' => ['required', 'string', 'unique:users,student_number,' . $user->id],
            'phone' => ['required', 'string', 'regex:/^09[0-9]{9}$/'],
            'role' => ['required', 'in:student,personnel,admin,super-admin'],
        ]);

        $user->update($request->all());

        return $this->jsonResponse(true, 'Account updated successfully.', $user);
    }

    /**
     * Delete User
     */
    public function destroyUser(User $user): JsonResponse
    {
        if ($user->id === auth()->id()) {
            return $this->jsonResponse(false, 'You cannot delete your own account.', null, 400);
        }

        $user->delete();

        return $this->jsonResponse(true, 'User removed from system.');
    }

    /**
     * List All Laboratories with PC counts
     */
    public function labs(): JsonResponse
    {
        $labs = Lab::withCount([
            'computers as total_pcs',
            'computers as active_pcs' => function ($query) {
                $query->where('status', 'active');
            },
        ])->get();

        return $this->jsonResponse(true, 'Labs list retrieved.', $labs);
    }

    /**
     * View Lab Schedules & Teachers
     */
    public function viewSchedule(Lab $lab): JsonResponse
    {
        $schedules = Schedule::where('lab_id', $lab->id)
            ->with('user')
            ->orderByRaw("FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")
            ->orderBy('start_time', 'asc')
            ->get();

        $teachers = User::whereIn('role', ['personnel', 'admin', 'super-admin'])->get();

        return $this->jsonResponse(true, 'Schedule retrieved.', [
            'lab' => $lab,
            'schedules' => $schedules,
            'teachers' => $teachers,
        ]);
    }

    /**
     * Store New Schedule
     */
    public function storeSchedule(Request $request, Lab $lab): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'subject_code' => 'required|string|max:50',
            'day' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
        ]);

        // Check for overlaps
        $overlap = Schedule::where('lab_id', $lab->id)
            ->where('day', $validated['day'])
            ->where(function ($query) use ($validated) {
                $query->where('start_time', '<', $validated['end_time'])
                    ->where('end_time', '>', $validated['start_time']);
            })->exists();

        if ($overlap) {
            return $this->jsonResponse(false, 'Schedule Conflict: This time slot is already taken.', null, 409);
        }

        $schedule = Schedule::create([
            'lab_id' => $lab->id,
            'user_id' => $validated['user_id'],
            'subject_code' => $validated['subject_code'],
            'day' => $validated['day'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
        ]);

        return $this->jsonResponse(true, 'Master Schedule updated successfully.', $schedule, 201);
    }

    /**
     * Delete Schedule
     */
    public function destroySchedule(Schedule $schedule): JsonResponse
    {
        $schedule->delete();

        return $this->jsonResponse(true, 'Schedule entry removed from Master Control.');
    }

    /**
     * Session History List
     */
    public function sessions(Request $request): JsonResponse
    {
        $query = LabSession::with(['computer', 'lab', 'user'])
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

        $sessions = $query->paginate($request->query('per_page', 15))->withQueryString();

        return $this->jsonResponse(true, 'Sessions history retrieved.', $sessions);
    }

    /**
     * Generate & Download CSV Report (Streams CSV File)
     */
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

        Log::info("Super Admin generated a {$request->type} report for range: {$request->range}");

        $fileName = "labguard_{$request->type}_report_" . now()->format('Y-m-d') . ".csv";

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($data, $request) {
            $file = fopen('php://output', 'w');

            if ($request->type === 'utilization') {
                fputcsv($file, ['Session ID', 'Student', 'Laboratory Room', 'Logged In At', 'Logged Out At']);
                foreach ($data as $row) {
                    fputcsv($file, [
                        $row->id,
                        $row->user->name ?? $row->student_name ?? 'N/A',
                        $row->lab->room_name ?? 'N/A',
                        $row->time_in,
                        $row->time_out ?? 'Active',
                    ]);
                }
            } else {
                fputcsv($file, ['Alert ID', 'Room', 'Station PC', 'Issue Category', 'Status', 'Logged At']);
                foreach ($data as $row) {
                    fputcsv($file, [
                        $row->id,
                        $row->lab->room_name ?? 'N/A',
                        $row->computer->pc_number ?? 'N/A',
                        $row->issue_type ?? 'Technical',
                        $row->status,
                        $row->created_at,
                    ]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Trigger Database Backup
     */
    public function triggerBackup(): JsonResponse
    {
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host     = config('database.connections.mysql.host', '127.0.0.1');
        $port     = config('database.connections.mysql.port', '3306');

        $filename = "backup_" . $database . "_" . now()->format('Y_m_d_H_i_s') . ".sql";
        $storagePath = storage_path('app/backups');

        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        $fullPath = $storagePath . '/' . $filename;
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
        exec($command, $output, $returnVar);

        if ($returnVar === 0) {
            Log::info("Super Admin successfully initialized database snapshot dump file: {$filename}");
            return $this->jsonResponse(true, 'Database snapshot generated and archived successfully.', [
                'filename' => $filename,
                'path' => $fullPath
            ]);
        }

        Log::error('Database dump routine failed execution.', [
            'exit_code' => $returnVar,
            'output'    => implode("\n", $output),
            'command'   => $command
        ]);

        return $this->jsonResponse(false, 'Database backup generation failed. Check server logs.', null, 500);
    }

    /**
     * Emergency Broadcast Lockout
     */
    public function lockout(Request $request): JsonResponse
    {
        $labId = $request->input('lab_id');

        if ($labId === 'all') {
            Lab::query()->update(['status' => 'maintenance']);
            Computer::query()->update(['status' => 'maintenance']);
            $message = 'Global lab maintenance lockdown initiated.';
        } else {
            $lab = Lab::findOrFail($labId);
            $lab->update(['status' => 'maintenance']);
            $lab->computers()->update(['status' => 'maintenance']);
            $message = "Maintenance lockdown initiated for lab ID {$labId}.";
        }

        return $this->jsonResponse(true, $message);
    }

    /**
     * Release Emergency Lockout
     */
    public function releaseLockout(Request $request): JsonResponse
    {
        $labId = $request->input('lab_id');

        if ($labId === 'all') {
            Lab::query()->update(['status' => 'active']);
            Computer::where('status', 'maintenance')->update(['status' => 'available']);
            $message = 'Global lab maintenance lock released.';
        } else {
            $lab = Lab::findOrFail($labId);
            $lab->update(['status' => 'active']);
            $lab->computers()->where('status', 'maintenance')->update(['status' => 'available']);
            $message = "Maintenance lock released for lab ID {$labId}.";
        }

        return $this->jsonResponse(true, $message);
    }

    /**
     * Activity Logs List
     */
    public function logs(Request $request): JsonResponse
    {
        $query = Activity::with(['causer', 'subject'])->latest();

        if ($request->has('category') && $request->category !== 'all') {
            $query->where('log_name', $request->category);
        }

        $logs = $query->paginate($request->query('per_page', 12))->withQueryString();

        $stats = [
            'total' => Activity::count(),
            'incidents' => Activity::where('log_name', 'incident_response')->count(),
            'users' => Activity::where('log_name', 'user_management')->count(),
            'labs' => Activity::where('log_name', 'lab_management')->count(),
        ];

        return $this->jsonResponse(true, 'System logs retrieved.', [
            'stats' => $stats,
            'logs' => $logs,
        ]);
    }

    /**
     * System Analytics Data
     */
    public function analytics(Request $request): JsonResponse
    {
        $range = $request->query('range', 'all');
        $startDate = match ($range) {
            'today' => Carbon::today(),
            'week'  => Carbon::now()->subDays(7),
            'month' => Carbon::now()->startOfMonth(),
            default => null,
        };

        $rangeLabel = match ($range) {
            'today' => 'Today',
            'week'  => 'Past 7 Days',
            'month' => 'This Month',
            default => 'All Time',
        };

        // 1. Statistics
        $totalUsers = User::count();

        $alertsQuery = DB::table('alerts')->where('status', 'pending');
        if ($startDate) {
            $alertsQuery->where('created_at', '>=', $startDate);
        }
        $activeAlertsCount = $alertsQuery->count();

        $activityLogQuery = DB::table('activity_log');
        if ($startDate) {
            $activityLogQuery->where('created_at', '>=', $startDate);
        }
        $totalLogEntries = $activityLogQuery->count();

        $stats = [
            [
                'label' => 'TOTAL ACCOUNTS',
                'value' => number_format($totalUsers),
                'change' => 'Registered users',
            ],
            [
                'label' => 'ACTIVE ALERTS (' . strtoupper($rangeLabel) . ')',
                'value' => number_format($activeAlertsCount),
                'change' => 'Requires attention',
            ],
            [
                'label' => 'SYSTEM LOG ENTRIES (' . strtoupper($rangeLabel) . ')',
                'value' => number_format($totalLogEntries),
                'change' => 'Captured DB rows',
            ],
        ];

        // 2. Lab Usage Distribution
        $labRecordsQuery = DB::table('lab_sessions')
            ->join('labs', 'lab_sessions.lab_id', '=', 'labs.id');

        if ($startDate) {
            $labRecordsQuery->where('lab_sessions.created_at', '>=', $startDate);
        }

        $labRecords = $labRecordsQuery
            ->select('labs.name as lab_name', DB::raw('count(*) as total'))
            ->groupBy('labs.id', 'labs.name')
            ->get();

        $totalSessions = $labRecords->sum('total') ?: 1;

        $colors = ['bg-blue-500', 'bg-emerald-500', 'bg-amber-500', 'bg-purple-500', 'bg-rose-500'];
        $labUsage = [];

        foreach ($labRecords as $index => $record) {
            $labUsage[] = [
                'name' => $record->lab_name,
                'percent' => round(($record->total / $totalSessions) * 100),
                'color' => $colors[$index % count($colors)],
            ];
        }

        // 3. Top Issues
        $topIssuesQuery = DB::table('alerts')
            ->select('issue_type', DB::raw('count(*) as count'));

        if ($startDate) {
            $topIssuesQuery->where('created_at', '>=', $startDate);
        } else {
            $topIssuesQuery->where('created_at', '>=', now()->startOfMonth());
        }

        $topIssues = $topIssuesQuery->groupBy('issue_type')
            ->orderByDesc('count')
            ->take(5)
            ->get();

        return $this->jsonResponse(true, 'Analytics data retrieved.', [
            'range' => $range,
            'range_label' => $rangeLabel,
            'stats' => $stats,
            'lab_usage' => $labUsage,
            'top_issues' => $topIssues,
        ]);
    }

    /**
     * Export Alerts CSV Stream
     */
    public function exportReport(Request $request): StreamedResponse
    {
        $range = $request->query('range', 'all');
        $startDate = match ($range) {
            'today' => Carbon::today(),
            'week'  => Carbon::now()->subDays(7),
            'month' => Carbon::now()->startOfMonth(),
            default => null,
        };

        $fileName = 'labguard_alerts_' . $range . '_report_' . date('Y-m-d') . '.csv';

        $alertsQuery = DB::table('alerts')
            ->leftJoin('labs', 'alerts.lab_id', '=', 'labs.id')
            ->select([
                'alerts.id',
                'labs.name as laboratory_name',
                'alerts.computer_id',
                'alerts.issue_type',
                'alerts.remarks',
                'alerts.status',
                'alerts.reported_by',
                'alerts.created_at',
            ]);

        if ($startDate) {
            $alertsQuery->where('alerts.created_at', '>=', $startDate);
        }

        $alerts = $alertsQuery->orderBy('alerts.created_at', 'desc')->get();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($alerts) {
            $file = fopen('php://output', 'w');

            fputcsv($file, ['Alert ID', 'Laboratory Name', 'Computer ID', 'Issue Type', 'Remarks/Details', 'Status', 'Reported By', 'Timestamp']);

            foreach ($alerts as $alert) {
                fputcsv($file, [
                    $alert->id,
                    $alert->laboratory_name ?? 'N/A',
                    $alert->computer_id ?? 'N/A',
                    $alert->issue_type,
                    $alert->remarks ?? 'No remarks provided',
                    ucfirst($alert->status),
                    $alert->reported_by ?? 'System',
                    $alert->created_at,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import Users from CSV File
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('file');

        if (!$file || !($handle = fopen($file->getRealPath(), 'r'))) {
            return $this->jsonResponse(false, 'Could not open uploaded CSV file.', null, 400);
        }

        $rawHeader = fgetcsv($handle, 1000, ',');

        if (!$rawHeader) {
            fclose($handle);
            return $this->jsonResponse(false, 'The uploaded CSV file is empty.', null, 400);
        }

        $header = array_map(function ($col) {
            $col = preg_replace('/[\x{EF}\x{BB}\x{BF}]/u', '', $col);
            return strtolower(trim($col));
        }, $rawHeader);

        $requiredColumns = ['name', 'email', 'student_number', 'phone', 'role', 'password'];
        $missingColumns = array_diff($requiredColumns, $header);

        if (!empty($missingColumns)) {
            fclose($handle);
            $missingStr = implode(', ', $missingColumns);
            return $this->jsonResponse(false, "Missing required columns: {$missingStr}", null, 422);
        }

        $importedCount = 0;
        $skippedCount = 0;

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

                $data = array_combine($header, $row);

                $email = trim($data['email'] ?? '');
                $studentNumber = trim($data['student_number'] ?? '');

                if (empty($email) || User::where('email', $email)->orWhere('student_number', $studentNumber)->exists()) {
                    $skippedCount++;
                    continue;
                }

                User::create([
                    'name' => trim($data['name']),
                    'email' => $email,
                    'student_number' => $studentNumber,
                    'phone' => trim($data['phone']),
                    'role' => in_array(strtolower(trim($data['role'])), ['student', 'personnel', 'admin']) ? strtolower(trim($data['role'])) : 'student',
                    'password' => Hash::make(trim($data['password'])),
                ]);

                $importedCount++;
            }

            DB::commit();
            fclose($handle);

            return $this->jsonResponse(true, "Mass enrollment complete. Imported: {$importedCount}, Skipped: {$skippedCount}", [
                'imported_count' => $importedCount,
                'skipped_count'  => $skippedCount,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);

            return $this->jsonResponse(false, 'Import failed: ' . $e->getMessage(), null, 500);
        }
    }
}
