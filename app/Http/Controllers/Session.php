<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lab;
use App\Models\LabSession;

class Session extends Controller
{


    public function index(Request $request)
    {
        $currentUser = auth()->user();

        // 1. Initialize Base Query
        $query = LabSession::with(['computer.lab', 'lab', 'teacher', 'checklist'])
            ->latest('time_in');

        // 2. ROLE-BASED ACCESS CONTROL (RBAC) SCOPING
        // Super-Admin, Admin, and Personnel have global lab surveillance privileges
        $hasGlobalClearance = in_array(strtolower($currentUser->role), [
            'super-admin',
            'super_admin',
            'admin',
            'personnel'
        ]);

        if (! $hasGlobalClearance) {
            if (strtolower($currentUser->role) === 'teacher') {
                // STRICT TEACHER SCOPE: Only retrieve sessions supervised by this teacher
                $query->where('teacher_id', $currentUser->id);
            } elseif (strtolower($currentUser->role) === 'student') {
                // Defensive: If a student ever accesses this page, show only their own logs
                $query->where('student_id_number', $currentUser->student_number);
            }
        }

        // 3. User Filter: Student Name Search
        if ($request->filled('student_name')) {
            $query->where('student_name', 'like', '%' . trim($request->student_name) . '%');
        }

        // 4. User Filter: Activity Date
        if ($request->filled('date')) {
            $query->whereDate('time_in', $request->date);
        }

        // 5. User Filter: Laboratory and PC Number
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

        // 6. Compute Scoped Metrics for Header Cards
        // (So teachers see their own active/total counts, not the entire school's)
        $metricsScope = LabSession::query();
        if (! $hasGlobalClearance && strtolower($currentUser->role) === 'teacher') {
            $metricsScope->where('teacher_id', $currentUser->id);
        }

        $totalSessions       = (clone $metricsScope)->count();
        $activeSessionsCount = (clone $metricsScope)->whereNull('time_out')->count();

        // 7. Paginate and preserve all active filter query parameters
        $sessions = $query->paginate(15)->withQueryString();

        // 8. Laboratories for Dropdown Filter
        $allLabs = Lab::orderBy('name')->get();

        return view('dashboard.sessions.index', compact(
            'sessions',
            'allLabs',
            'totalSessions',
            'activeSessionsCount'
        ));
    }
}
