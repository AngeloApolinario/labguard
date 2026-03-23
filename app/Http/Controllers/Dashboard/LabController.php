<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Computer;
use App\Models\LabSession;
use App\Models\Schedule;
use App\Models\User;
use App\Models\Lab;

class LabController extends Controller
{
    public function index()
    {
        $labs = Lab::withCount([
            'computers as total_pcs',
            'computers as active_pcs' => function ($query) {
                $query->where('status', 'active');
            }
        ])->get();

        return view('dashboard.labs.index', compact('labs'));
    }

    public function viewSchedule(Lab $lab)
    {
        $schedules = Schedule::where('lab_id', $lab->id)
            ->with('user')
            ->orderByRaw("FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")
            ->orderBy('start_time', 'asc')
            ->get();

        $teachers = User::whereIn('role', ['personnel', 'admin'])->get();

        return view('dashboard.labs.schedule', compact('lab', 'schedules', 'teachers'));
    }

    public function storeSchedule(Request $request, Lab $lab)
    {
        // 1. Validate
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'subject_code' => 'required|string|max:50',
            'day' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
        ]);

        // 2. Check for Overlaps
        // Logic: A conflict exists if (StartA < EndB) AND (EndA > StartB)
        $overlap = Schedule::where('lab_id', $lab->id)
            ->where('day', $validated['day'])
            ->where(function ($query) use ($validated) {
                $query->where('start_time', '<', $validated['end_time'])
                    ->where('end_time', '>', $validated['start_time']);
            })->exists();

        if ($overlap) {
            return back()
                ->withInput()
                ->with('error', 'Schedule Conflict: The selected time overlaps with an existing slot.');
        }

        // 3. Create
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

    public function destroySchedule(Schedule $schedule)
    {
        $schedule->delete();
        return back()->with('success', 'Schedule entry removed.');
    }
}
