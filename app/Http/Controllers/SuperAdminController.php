<?php

namespace App\Http\Controllers;

use App\Models\Lab;
use App\Models\LabSession;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;
use App\Models\Alert;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\Models\Activity;

class SuperAdminController extends Controller
{
    public function index()
    {
        // 1. Fetch Summary Card Metrics
        $totalUsers = User::count();
        $activeSessionsCount = LabSession::whereNull('time_out')->count();
        $pendingAlertsCount = Alert::where('status', 'pending')->count();

        // 2. Fetch all Laboratories to ensure we list rooms even if they have 0 active users
        $labs = Lab::all();
        $totalLabs = $labs->count();

        // 3. Count active sessions explicitly grouped by their laboratory connection point
        $activeSessionsPerLab = LabSession::whereNull('time_out')
            ->select('lab_id', \DB::raw('count(*) as active_count'))
            ->groupBy('lab_id')
            ->pluck('active_count', 'lab_id')
            ->all(); // Returns an array like: [lab_id => active_count]

        // 4. Map computed metrics out safely to your view variables
        $labUtilization = [];
        foreach ($labs as $lab) {
            // Get current session volume from our map, defaulting to 0 if no match
            $currentActiveCount = $activeSessionsPerLab[$lab->id] ?? 0;

            // Protect against a division by zero fallback context
            $capacity = $lab->capacity > 0 ? $lab->capacity : 1;
            $percentage = ($currentActiveCount / $capacity) * 100;

            $labUtilization[$lab->id] = [
                'name' => $lab->room_name,
                'percent' => min(100, round($percentage))
            ];
        }

        // 5. Pull Recent Alert Feeds
        $recentAlerts = Alert::with(['lab'])
            ->latest()
            ->take(5)
            ->get();

        return view('super-admin.index', [
            'totalUsers' => $totalUsers,
            'activeSessions' => $activeSessionsCount,
            'alerts' => $pendingAlertsCount,
            'totalLabs' => $totalLabs,
            'labUtilization' => $labUtilization,
            'recentAlerts' => $recentAlerts
        ]);
    }

    public function security()
    {
        // Hardcoded stats for the top cards
        $securityStats = [
            'score' => '92/100',
            'threats' => 3,
            'vulnerabilities' => 5
        ];

        // Hardcoded alerts to match your UI image
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
                'bgColor' => 'bg-rose-50'
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
                'bgColor' => 'bg-amber-50'
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
                'bgColor' => 'bg-blue-50'
            ]
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
            'system_email' => 'admin@labguard.edu'
        ];

        return view('super-admin.settings', compact('settings'));
    }

    //USER MANAGEMENT CONTROLLER CODE 
    public function userManagement()
    {
        // Super Admin sees EVERYONE
        $users = User::orderBy('role', 'asc')
            ->orderBy('name', 'asc')
            ->paginate(15);

        return view('super-admin.user-management', compact('users'));
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', Rules\Password::defaults()],
            'role' => ['required', 'in:student,personnel,admin,super-admin'], // Expanded roles
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
            'email_verified_at' => now(), // Auto-verify
        ]);

        return redirect()->back()->with('success', "{$user->role} account created successfully.");
    }

    public function updateUser(Request $request, User $user)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'student_number' => ['required', 'string', 'unique:users,student_number,' . $user->id],
            'phone' => ['required', 'string', 'regex:/^09[0-9]{9}$/'],
            'role' => ['required', 'in:student,personnel,admin,super-admin'],
        ]);

        $user->update($request->all());

        return redirect()->back()->with('status', 'Account updated.');
    }

    public function destroyUser(User $user)
    {
        // Prevent accidental self-deletion
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();
        return redirect()->back()->with('status', 'User removed from system.');
    }


    //LAB AND SCHEDULE ROUTES 
    public function labs()
    {
        $labs = Lab::withCount([
            'computers as total_pcs',
            'computers as active_pcs' => function ($query) {
                $query->where('status', 'active');
            }
        ])->get();

        return view('super-admin.labs', compact('labs'));
    }

    // View the Schedule for a specific Lab
    public function viewSchedule(Lab $lab)
    {
        $schedules = Schedule::where('lab_id', $lab->id)
            ->with('user')
            ->orderByRaw("FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")
            ->orderBy('start_time', 'asc')
            ->get();

        // Get all personnel/admins to assign to schedules
        $teachers = User::whereIn('role', ['personnel', 'admin', 'super-admin'])->get();

        return view('super-admin.schedule', compact('lab', 'schedules', 'teachers'));
    }

    // Store a New Schedule
    public function storeSchedule(Request $request, Lab $lab)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'subject_code' => 'required|string|max:50',
            'day' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
        ]);

        // Check for Overlaps
        $overlap = Schedule::where('lab_id', $lab->id)
            ->where('day', $validated['day'])
            ->where(function ($query) use ($validated) {
                $query->where('start_time', '<', $validated['end_time'])
                    ->where('end_time', '>', $validated['start_time']);
            })->exists();

        if ($overlap) {
            return back()->withInput()->with('error', 'Schedule Conflict: This time slot is already taken.');
        }

        Schedule::create([
            'lab_id' => $lab->id,
            'user_id' => $validated['user_id'],
            'subject_code' => $validated['subject_code'],
            'day' => $validated['day'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
        ]);

        return back()->with('success', 'Master Schedule updated successfully.');
    }

    // Delete a Schedule
    public function destroySchedule(Schedule $schedule)
    {
        $schedule->delete();
        return back()->with('success', 'Schedule entry removed from Master Control.');
    }

    //SESSION HISTORY FOR THE SUPER ADMIN DASHBOARD
    public function sessions(Request $request)
    {
        // 1. Same logic: Only finished sessions
        $query = LabSession::with(['computer'])
            ->whereNotNull('time_out')
            ->latest('time_out');

        // 2. Same RAD Filters
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

        $sessions = $query->paginate(15)->withQueryString();

        // Change this path to match your actual view folder
        return view('super-admin.sessions', compact('sessions'));
    }


    // ==========================================
    // SUPERADMIN OVERVIEW FUNCTIONS (FIXED)
    // ==========================================

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

        // Query the data depending on selected report type
        if ($request->type === 'utilization') {
            // MATCHED: 'lab' instead of 'laboratory', 'time_in' instead of 'created_at'
            $data = LabSession::with(['user', 'lab'])
                ->where('time_in', '>=', $timeframe)
                ->get();
        } else {
            // MATCHED: 'lab' instead of 'laboratory'
            $data = Alert::with(['lab', 'computer'])
                ->where('created_at', '>=', $timeframe)
                ->get();
        }

        // Log the administrative audit trail action
        Log::info("Super Admin generated a {$request->type} report for range: {$request->range}");

        // Build a clean streamable CSV payload text block inline
        $fileName = "labguard_{$request->type}_report_" . now()->format('Y-m-d') . ".csv";

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function () use ($data, $request) {
            $file = fopen('php://output', 'w');

            if ($request->type === 'utilization') {
                fputcsv($file, ['Session ID', 'Student', 'Laboratory Room', 'Logged In At', 'Logged Out At']);
                foreach ($data as $row) {
                    // MATCHED: utilizing 'lab' and your 'time_in' / 'time_out' schema columns
                    fputcsv($file, [
                        $row->id,
                        $row->user->name ?? $row->student_name ?? 'N/A',
                        $row->lab->room_name ?? 'N/A',
                        $row->time_in,
                        $row->time_out ?? 'Active'
                    ]);
                }
            } else {
                fputcsv($file, ['Alert ID', 'Room', 'Station PC', 'Issue Category', 'Status', 'Logged At']);
                foreach ($data as $row) {
                    // MATCHED: utilizing 'lab' relation structure
                    fputcsv($file, [
                        $row->id,
                        $row->lab->room_name ?? 'N/A',
                        $row->computer->pc_number ?? 'N/A',
                        $row->issue_type ?? 'Technical',
                        $row->status,
                        $row->created_at
                    ]);
                }
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Run a secure production MySQL backup dump
     */
    public function triggerBackup()
    {
        $filename = "backup_" . env('DB_DATABASE') . "_" . now()->format('Y_m_dH_i_s') . ".sql";

        // Build path context for secure internal app directory
        $storagePath = storage_path("app/backups/");
        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        $fullPath = $storagePath . $filename;

        // Construct standard mysqldump command layout safely
        $command = sprintf(
            "mysqldump --user=%s --password=%s --host=%s %s > %s 2>&1",
            escapeshellarg(env('DB_USERNAME')),
            escapeshellarg(env('DB_PASSWORD')),
            escapeshellarg(env('DB_HOST', '127.0.0.1')),
            escapeshellarg(env('DB_DATABASE')),
            escapeshellarg($fullPath)
        );

        $output = [];
        $returnVar = null;
        exec($command, $output, $returnVar);

        if ($returnVar === 0) {
            Log::info("Super Admin successfully initialized database snapshot dump file: {$filename}");
            return redirect()->back()->with('status', 'Database architectural snapshot generated and archived to secure local path successfully.');
        }

        Log::error("Database dump routine failed execution. Output details: " . implode("\n", $output));
        return redirect()->back()->with('error', 'Database utility structural engine execution failed. Check server permissions.');
    }

    /**
     * Emergency Broadcast Protocol - Immediate Global System Lockout
     */
    public function emergencyLockout()
    {
        DB::beginTransaction();
        try {
            // MATCHED: Update 'time_out' instead of 'ended_at' where 'time_out' is null
            LabSession::whereNull('time_out')->update([
                'time_out' => now()
            ]);

            // Set all computers to maintenance status to block new student log-ins immediately
            DB::table('computers')->update([
                'status' => 'maintenance'
            ]);

            DB::commit();

            Log::emergency("CRITICAL CRITERIA: Super Admin invoked physical Emergency Lockout protocol. All campus terminal stations forced offline.");

            return redirect()->back()->with('alert', 'EMERGENCY PROTOCOL ACTIVATED: All user sessions disconnected. Physical workstations locked.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Emergency Lockout chain sequence aborted unexpectedly: " . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to safely broadcast system lockdown command network-wide.');
        }
    }
    public function logs(Request $request)
    {
        $query = Activity::with(['causer', 'subject'])->latest();

        // Optional filter support
        if ($request->has('category') && $request->category !== 'all') {
            $query->where('log_name', $request->category);
        }

        $logs = $query->paginate(12)->withQueryString();

        // High-level counters for the hero stats bar
        $stats = [
            'total'     => Activity::count(),
            'incidents' => Activity::where('log_name', 'incident_response')->count(),
            'users'     => Activity::where('log_name', 'user_management')->count(),
            'labs'      => Activity::where('log_name', 'lab_management')->count(),
        ];

        return view('super-admin.logs', compact('logs', 'stats'));
    }


    //ANALYTICS CONTROLLER
    public function analytics(Request $request)
    {
        // Determine the active date boundary based on the selected range parameter
        $range = $request->query('range', 'all'); // Default to all-time if nothing selected
        $startDate = null;

        switch ($range) {
            case 'today':
                $startDate = Carbon::today();
                $rangeLabel = 'Today';
                break;
            case 'week':
                $startDate = Carbon::now()->subDays(7);
                $rangeLabel = 'Past 7 Days';
                break;
            case 'month':
                $startDate = Carbon::now()->startOfMonth();
                $rangeLabel = 'This Month';
                break;
            default:
                $startDate = null;
                $rangeLabel = 'All Time';
                break;
        }

        // 1. Calculate Actual System Statistics
        $totalUsers = User::count();

        // Total Alerts query with active date range filter
        $alertsQuery = DB::table('alerts')->where('status', 'pending');
        if ($startDate) {
            $alertsQuery->where('created_at', '>=', $startDate);
        }
        $activeAlertsCount = $alertsQuery->count();

        // Read actual log line count for diagnostic metrics
        $logFile = storage_path('logs/laravel.log');
        $totalLogLines = file_exists($logFile) ? count(file($logFile)) : 0;

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
                'label' => 'SYSTEM LOG ENTRIES',
                'value' => number_format($totalLogLines),
                'change' => 'Captured rows',
            ],
        ];

        // 2. Fetch Actual Lab Usage Distribution joining with 'labs' (Filtered by Date Range)
        $labRecordsQuery = DB::table('lab_sessions')
            ->join('labs', 'lab_sessions.lab_id', '=', 'labs.id');

        if ($startDate) {
            // Safe check assuming lab_sessions has a standard timestamp column
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

        // 3. Fetch Top Reported Issue Types from 'alerts' table within selected period
        $topIssuesQuery = DB::table('alerts')
            ->select('issue_type', DB::raw('count(*) as count'));

        if ($startDate) {
            $topIssuesQuery->where('created_at', '>=', $startDate);
        } else {
            // Fall back to current month if 'All Time' is selected to keep the card descriptive
            $topIssuesQuery->where('created_at', '>=', now()->startOfMonth());
        }

        $topIssues = $topIssuesQuery->groupBy('issue_type')
            ->orderByDesc('count')
            ->take(5)
            ->get();

        return view('super-admin.analytics', compact('stats', 'labUsage', 'topIssues', 'range', 'rangeLabel'));
    }

    public function exportReport(Request $request): StreamedResponse
    {
        $range = $request->query('range', 'all');
        $startDate = null;

        if ($range === 'today') {
            $startDate = Carbon::today();
        } elseif ($range === 'week') {
            $startDate = Carbon::now()->subDays(7);
        } elseif ($range === 'month') {
            $startDate = Carbon::now()->startOfMonth();
        }

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
                'alerts.created_at'
            ]);

        if ($startDate) {
            $alertsQuery->where('alerts.created_at', '>=', $startDate);
        }

        $alerts = $alertsQuery->orderBy('alerts.created_at', 'desc')->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function () use ($alerts) {
            $file = fopen('php://output', 'w');

            // CSV Structural Headers
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

    //REVERSE THE LOCKOUT
    public function releaseLockout(Request $request)
    {
        // Restore all maintenance workstations back to available
        DB::table('computers')->update([
            'status' => 'available'
        ]);
        return redirect()->back()->with('status', 'Global system lockdown released. All stations restored to available state.');
    }
}
