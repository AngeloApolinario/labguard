<?php

namespace App\Http\Controllers;

use App\Models\Lab;
use App\Models\LabSession;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Hash;

class SuperAdminController extends Controller
{
    public function index()
    {
        // Actual Database Counts
        $totalUsers = User::count();
        $activeSessions = rand(300, 450); // Mocking active sessions
        $alerts = 23;
        $uptime = '99.8%';

        // System Health Stats (Percentages)
        $health = [
            'cpu' => 45,
            'memory' => 62,
            'database' => 98,
            'latency' => 23,
        ];

        // Recent Activities Mock
        $activities = [
            [
                'type' => 'warning',
                'title' => 'Login Attempt',
                'desc' => 'User admin@au.edu failed 3 login attempts',
                'time' => '2 minutes ago'
            ],
            [
                'type' => 'info',
                'title' => 'System Update',
                'desc' => 'Security patch applied to all terminals',
                'time' => '1 hour ago'
            ]
        ];

        return view('super-admin.index', compact('totalUsers', 'activeSessions', 'alerts', 'uptime', 'health', 'activities'));
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

    public function analytics()
    {
        $stats = [
            ['label' => 'Total Logins', 'value' => '12,456', 'change' => '+23% vs last month', 'color' => 'text-green-500'],
            ['label' => 'Avg Session Time', 'value' => '2h 34m', 'change' => '+15% vs last month', 'color' => 'text-green-500'],
            ['label' => 'Equipment Issues', 'value' => '18', 'change' => '-12% vs last month', 'color' => 'text-green-500'],
            ['label' => 'User Satisfaction', 'value' => '4.7/5', 'change' => '+0.3 vs last month', 'color' => 'text-green-500'],
        ];

        $labUsage = [
            ['name' => 'Lab 1', 'percent' => 85, 'color' => 'bg-blue-600'],
            ['name' => 'Lab 2', 'percent' => 72, 'color' => 'bg-amber-400'],
            ['name' => 'Lab 3', 'percent' => 68, 'color' => 'bg-emerald-500'],
            ['name' => 'Lab 4', 'percent' => 55, 'color' => 'bg-orange-600'],
            ['name' => 'Lab 5', 'percent' => 78, 'color' => 'bg-purple-500'],
            ['name' => 'Lab 6', 'percent' => 62, 'color' => 'bg-rose-500'],
        ];

        return view('super-admin.analytics', compact('stats', 'labUsage'));
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
}
