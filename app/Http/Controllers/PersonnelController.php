<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Computer;
use App\Models\LabSession;
use App\Models\Lab;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;

class PersonnelController extends Controller
{
    /**
     * Staff Dashboard: Overview of all Labs
     */
    public function index()
    {
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
            'student_id_number' => $request->student_number,
            'time_in' => now(),
            'teacher_id' => auth()->id(),
            'lab_id' => $computer->lab_id, // Explicitly linking the lab footprint context here
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
        $session = LabSession::where('computer_id', $computer->id)
            ->whereNull('time_out')
            ->latest()
            ->first();

        if ($session) {
            $session->update([
                'time_out' => now()
            ]);

            $computer->update(['status' => 'available']);

            return response()->json([
                'status' => 'success',
                'message' => "PC {$computer->pc_number} released successfully."
            ]);
        }

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
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        return view('personnel.full-schedule', compact('labs', 'days'));
    }

    /**
     * View Session History
     */
    public function sessionHistory(Request $request)
    {
        $query = LabSession::with(['computer.lab', 'teacher'])
            ->latest()
            ->whereNotNull('time_out');

        if (auth()->user()->role !== 'admin') {
            $query->where('teacher_id', auth()->id());
        }

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
        $alerts = \App\Models\Alert::with(['computer.lab'])->latest()->paginate(15);
        return view('personnel.alerts', compact('alerts'));
    }

    /**
     * EXPORTING THE ATTENDANCE REPORT AS CSV
     * Fixed table schema logic mapping to look at 'teacher_id' column
     */
    /**
     * EXPORTING THE ATTENDANCE REPORT AS CSV
     * Reads a custom date if passed by the Master Schedule grid view layout
     */
    public function exportScheduleAttendance(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);

        if (auth()->id() !== $schedule->user_id && auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        // FIXED: Fallback to today only if the request doesn't pass a specialized contextual date parameter
        $targetDate = $request->query('date', now()->toDateString());

        $sessions = LabSession::where('lab_id', $schedule->lab_id)
            ->where('teacher_id', $schedule->user_id)
            ->whereDate('time_in', $targetDate)
            ->whereTime('time_in', '>=', $schedule->start_time)
            ->whereTime('time_in', '<=', $schedule->end_time)
            ->get();

        if ($sessions->isEmpty()) {
            return back()->with('error', 'No attendance records found for this session date.');
        }

        $fileName = "Attendance_{$schedule->subject_code}_{$targetDate}.csv";

        return response()->streamDownload(function () use ($sessions, $schedule, $targetDate) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['LABGUARD SYSTEM - ATTENDANCE REPORT']);
            fputcsv($file, ['Subject', $schedule->subject_code]);
            fputcsv($file, ['Instructor', $schedule->user->name]);
            fputcsv($file, ['Session Date', Carbon::parse($targetDate)->format('M d, Y')]);
            fputcsv($file, []);
            fputcsv($file, ['STUDENT NAME', 'STUDENT NUMBER', 'TIME IN', 'TIME OUT', 'DURATION (MINS)']);

            foreach ($sessions as $s) {
                $timeIn = $s->time_in instanceof Carbon ? $s->time_in : Carbon::parse($s->time_in);
                $timeOut = null;
                $duration = 'Still Logged In';

                if ($s->time_out) {
                    $timeOut = $s->time_out instanceof Carbon ? $s->time_out : Carbon::parse($s->time_out);
                    $duration = $timeIn->diffInMinutes($timeOut) . ' mins';
                    $timeOutFormat = $timeOut->format('h:i A');
                } else {
                    $timeOutFormat = 'N/A';
                }

                fputcsv($file, [
                    strtoupper($s->student_name),
                    $s->student_id_number,
                    $timeIn->format('h:i A'),
                    $timeOutFormat,
                    $duration
                ]);
            }
            fclose($file);
        }, $fileName);
    }
}
