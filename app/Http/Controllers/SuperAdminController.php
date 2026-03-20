<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

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

    public function manageUsers(Request $request)
    {
        // Fetch real users from DB
        $users = User::latest()->paginate(10);

        // Hardcode statuses for the view logic since the column doesn't exist yet
        $users->getCollection()->transform(function ($user, $key) {
            // We'll just rotate statuses for the demo so the table looks varied
            if ($key === 0) $user->status = 'locked';
            elseif ($key % 3 === 0) $user->status = 'inactive';
            else $user->status = 'active';

            return $user;
        });

        // Hardcode the Stats for the top cards
        $stats = [
            'total'    => User::count(),
            'active'   => User::count() - 3, // Just a guess for the demo
            'inactive' => 2,
            'locked'   => 1,
        ];

        return view('super-admin.users', compact('users', 'stats'));
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
}
