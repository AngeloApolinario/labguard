<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Computer;
use App\Models\LabSession;
use App\Models\Schedule;
use App\Models\User;
use App\Models\Lab; // Ensure this is imported

class LabController extends Controller
{
    /**
     * Display all labs with their real-time PC metrics.
     */
    public function index()
    {
        // Using withCount is much cleaner than selectRaw for relational data
        $labs = Lab::withCount([
            'computers as total_pcs',
            'computers as active_pcs' => function ($query) {
                $query->where('status', 'active');
            }
        ])->get();

        return view('dashboard.labs.index', compact('labs'));
    }

    /**
     * View the schedule for a specific lab.
     */
    public function viewSchedule(Lab $lab)
    {
        // Fetch and sort by day of the week and start time
        $schedules = Schedule::where('lab_id', $lab->id)
            ->with('user')
            ->orderByRaw("FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")
            ->orderBy('start_time', 'asc')
            ->get();

        $teachers = User::whereIn('role', ['personnel', 'admin'])->get();

        return view('dashboard.labs.schedule', compact('lab', 'schedules', 'teachers'));
    }

    /**
     * Store a new schedule entry.
     */
    public function storeSchedule(Request $request, Lab $lab)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'subject_code' => 'required|string|max:50',
            'day' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
        ]);

        Schedule::create([
            'lab_id' => $lab->id,
            'user_id' => $validated['user_id'],
            'subject_code' => $validated['subject_code'],
            'day' => $validated['day'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
        ]);

        return back()->with('success', 'Schedule entry created successfully.');
    }

    /**
     * Optional: Update an existing schedule entry.
     */
    public function updateSchedule(Request $request, Schedule $schedule)
    {
        $validated = $request->validate([
            'subject_code' => 'required|string',
            'day' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
        ]);

        $schedule->update($validated);

        return back()->with('success', 'Schedule slot updated.');
    }

    /**
     * Remove a schedule entry.
     */
    public function destroySchedule(Schedule $schedule)
    {
        $schedule->delete();
        return back()->with('success', 'Schedule entry removed.');
    }
}
