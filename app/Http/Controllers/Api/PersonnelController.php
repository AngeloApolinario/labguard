<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Computer;
use App\Models\Lab;
use App\Models\LabSession;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PersonnelController extends Controller
{
    /**
     * Staff Dashboard: Overview of all Labs
     */
    public function index()
    {
        Computer::cleanupStaleSessions();

        $labs = Lab::with(['computers'])->withCount([
            'computers as total',
            'computers as occupied' => function ($query) {
                $query->where('status', 'active');
            },
        ])->get();

        return response()->json(['success' => true, 'data' => $labs]);
    }

    public function showLab(Lab $lab)
    {
        $currentTime = now()->format('H:i:s');
        $currentDay = now()->format('l');

        $currentSchedule = $lab->schedules()
            ->where('day', $currentDay)
            ->where('start_time', '<=', $currentTime)
            ->where('end_time', '>=', $currentTime)
            ->first();

        $computers = $lab->computers()->with(['activeSession'])->orderBy('pc_number')->get();
        $schedules = $lab->schedules()->with('user')->where('day', $currentDay)->orderBy('start_time')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'lab'              => $lab,
                'computers'        => $computers,
                'schedules'        => $schedules,
                'current_schedule' => $currentSchedule,
            ]
        ]);
    }

    /**
     * Assign a student to a specific computer
     */
    public function assign(Request $request, Computer $computer)
    {
        $validated = $request->validate([
            'student_name' => 'required|string|max:255',
            'student_number' => 'required|string|max:50',
        ]);

        $session = LabSession::create([
            'computer_id' => $computer->id,
            'student_name' => $validated['student_name'],
            'student_id_number' => $validated['student_number'],
            'time_in' => now(),
            'teacher_id' => auth()->id(),
            'lab_id' => $computer->lab_id,
        ]);

        $computer->update(['status' => 'active']);

        return response()->json([
            'message' => "{$computer->pc_number} is now assigned to {$validated['student_name']}.",
            'session' => $session,
            'computer' => $computer->load('activeSession'),
        ], 201);
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
                'time_out' => now(),
            ]);

            $computer->update(['status' => 'available']);

            return response()->json([
                'status' => 'success',
                'message' => "PC {$computer->pc_number} released successfully.",
            ]);
        }

        $computer->update(['status' => 'available']);

        return response()->json([
            'status' => 'warning',
            'message' => 'PC status reset, but no active session record was found.',
        ]);
    }

    /**
     * Labs Overview API endpoint
     */
    public function labs()
    {
        Computer::cleanupStaleSessions();

        $labs = Lab::withCount([
            'computers as total',
            'computers as occupied' => function ($query) {
                $query->where('status', 'active');
            },
        ])->get();

        return response()->json([
            'labs' => $labs,
        ]);
    }

    public function fullSchedule()
    {
        $labs = Lab::with(['schedules.user'])->get();
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        return response()->json([
            'labs' => $labs,
            'days' => $days,
        ]);
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

        return response()->json($sessions);
    }

    /**
     * View Alerts/Maintenance History
     */
    public function alertHistory()
    {
        $alerts = \App\Models\Alert::with(['computer.lab'])->latest()->paginate(15);

        return response()->json($alerts);
    }

    /**
     * EXPORTING THE ATTENDANCE REPORT AS CSV
     */
    public function exportScheduleAttendance(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);

        if (auth()->id() !== $schedule->user_id && auth()->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized action.',
            ], 403);
        }

        $targetDate = $request->query('date', now()->toDateString());

        $sessions = LabSession::where('lab_id', $schedule->lab_id)
            ->where('teacher_id', $schedule->user_id)
            ->whereDate('time_in', $targetDate)
            ->whereTime('time_in', '>=', $schedule->start_time)
            ->whereTime('time_in', '<=', $schedule->end_time)
            ->get();

        if ($sessions->isEmpty()) {
            return response()->json([
                'message' => 'No attendance records found for this session date.',
            ], 404);
        }

        $fileName = "Attendance_{$schedule->subject_code}_{$targetDate}.csv";

        $response = new StreamedResponse(function () use ($sessions, $schedule, $targetDate) {
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
                    $duration,
                ]);
            }

            fclose($file);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        return $response;
    }
}
