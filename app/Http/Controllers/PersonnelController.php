<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Computer;
use App\Models\LabSession;
use App\Models\Lab; // Added Lab Model
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PersonnelController extends Controller
{
    /**
     * Staff Dashboard: Overview of all Labs
     */
    public function index()
    {
        // Using withCount on the Lab model is much more efficient than grouping strings
        $labs = Lab::withCount([
            'computers as total',
            'computers as occupied' => function ($query) {
                $query->where('status', 'active');
            }
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

        // Find if there is a schedule RIGHT NOW
        $currentSchedule = $lab->schedules()
            ->where('day', $currentDay)
            ->where('start_time', '<=', $currentTime)
            ->where('end_time', '>=', $currentTime)
            ->first();

        /**
         * ACCESS CONTROL LOGIC:
         * 1. If user is Admin, allow access.
         * 2. If there is NO schedule, allow Personnel/Teachers to access (General use).
         * 3. If there IS a schedule, ONLY the scheduled teacher can enter.
         */
        if ($currentSchedule) {
            if (auth()->id() !== $currentSchedule->user_id && auth()->user()->role !== 'admin') {
                return redirect()->route('personnel.index')
                    ->with('error', "Access Denied: This lab is currently reserved for {$currentSchedule->user->name}.");
            }
        }

        $computers = $lab->computers()->with(['activeSession'])->orderBy('pc_number')->get();

        // Fetch today's schedules for the sidebar
        $schedules = $lab->schedules()->with('user')->where('day', $currentDay)->orderBy('start_time')->get();

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

        // Create the LabSession record
        LabSession::create([
            'computer_id' => $computer->id,
            'student_name' => $request->student_name,
            // CHANGE THIS: Use the exact column name in your DB (student_id_number)
            'student_id_number' => $request->student_number,
            'time_in' => now(),
            'teacher_id' => auth()->id(),
        ]);

        // Mark PC as occupied
        $computer->update(['status' => 'active']);

        return back()->with('success', "{$computer->pc_number} is now assigned to {$request->student_name}.");
    }

    /**
     * Release the PC and end the session
     */
    public function release(Computer $computer)
    {
        // 1. Find the active session for this specific computer ID where time_out is still null
        $session = LabSession::where('computer_id', $computer->id)
            ->whereNull('time_out')
            ->latest() // Get the most recent one just in case
            ->first();

        if ($session) {
            // 2. Close the session
            $session->update([
                'time_out' => now()
            ]);

            // 3. Set the PC status back to available
            $computer->update(['status' => 'available']);

            return response()->json([
                'status' => 'success',
                'message' => "PC {$computer->pc_number} released successfully."
            ]);
        }

        // Fallback: If no session was found but PC is 'active', force it to 'available'
        $computer->update(['status' => 'available']);

        return response()->json([
            'status' => 'warning',
            'message' => "PC status reset, but no active session record was found."
        ]);
    }

    /**
     * Duplicate of index for "Labs Overview" page
     */
    public function labs()
    {
        $labs = Lab::withCount([
            'computers as total',
            'computers as occupied' => function ($query) {
                $query->where('status', 'active');
            }
        ])->get();

        return view('personnel.labs-overview', compact('labs'));
    }
    public function fullSchedule()
    {
        $labs = Lab::with(['schedules.user'])->get();

        // Define the days of the week for the table headers
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday',];

        return view('personnel.full-schedule', compact('labs', 'days'));
    }

    /**
     * View Session History
     * Filters: Admins see all, Teachers see their own assigned sessions.
     */
    public function sessionHistory(Request $request)
    {
        $query = LabSession::with(['computer.lab', 'teacher'])
            ->latest()->whereNotNull('time_out');;

        // If not admin, only show sessions where this teacher was the one in charge
        if (auth()->user()->role !== 'admin') {
            $query->where('teacher_id', auth()->id());
        }

        // Optional: Filter by Date if provided in request
        if ($request->has('date')) {
            $query->whereDate('time_in', $request->date);
        }

        $sessions = $query->paginate(15);

        return view('personnel.sessions', compact('sessions'));
    }

    /**
     * View Alerts/Maintenance History
     */
    public function alertHistory()
    {
        // Fetch alerts with their related computer and lab info
        $query = \App\Models\Alert::with(['computer.lab'])
            ->latest();

        // Admins see all alerts. 
        // Teachers see alerts for all PCs (so they know if a station is broken before assigning it).
        $alerts = $query->paginate(15);

        return view('personnel.alerts', compact('alerts'));
    }

    //EXPORTING THE ATTENDACE REPORT AS CSV
    public function exportScheduleAttendance($id)
    {
        $schedule = Schedule::findOrFail($id);

        // Ensure the teacher is only exporting their own classes
        if (auth()->id() !== $schedule->user_id && auth()->user()->role !== 'super-admin') {
            abort(403, 'Unauthorized action.');
        }

        $sessions = LabSession::where('lab_id', $schedule->lab_id)
            ->where('teacher_id', $schedule->user_id)
            ->whereDate('time_in', now()->toDateString())
            ->whereTime('time_in', '>=', $schedule->start_time)
            ->whereTime('time_in', '<=', $schedule->end_time)
            ->whereNotNull('time_out')
            ->get();

        if ($sessions->isEmpty()) {
            return back()->with('error', 'No attendance records found for today.');
        }

        $fileName = "Attendance_{$schedule->subject_code}_" . now()->format('Y-m-d') . ".csv";

        return response()->streamDownload(function () use ($sessions, $schedule) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['LABGUARD SYSTEM - ATTENDANCE REPORT']);
            fputcsv($file, ['Subject', $schedule->subject_code]);
            fputcsv($file, ['Instructor', $schedule->user->name]);
            fputcsv($file, ['Date', now()->format('M d, Y')]);
            fputcsv($file, []);
            fputcsv($file, ['STUDENT NAME', 'STUDENT NUMBER', 'TIME IN', 'TIME OUT', 'DURATION (MINS)']);

            foreach ($sessions as $s) {
                fputcsv($file, [
                    strtoupper($s->student_name),
                    $s->student_id_number,
                    $s->time_in->format('h:i A'),
                    $s->time_out->format('h:i A'),
                    $s->time_in->diffInMinutes($s->time_out)
                ]);
            }
            fclose($file);
        }, $fileName);
    }
}
