<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Computer;
use App\Models\LabSession;
use App\Models\Lab; // Added Lab Model
use App\Models\User;
use Illuminate\Support\Facades\Gate;

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
            'student_number' => 'required|string|max:50', // Updated to match your schema naming
        ]);

        // Create the LabSession record
        LabSession::create([
            'computer_id' => $computer->id,
            'student_name' => $request->student_name,
            'student_number' => $request->student_number,
            'time_in' => now(),
            'teacher_id' => auth()->id(), // Personnel/Admin who authorized the session
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
        // Use the relationship defined in your Computer model
        $session = $computer->activeSession();

        if ($session) {
            $session->update([
                'time_out' => now()
            ]);
        }

        // Mark PC as available
        $computer->update(['status' => 'available']);

        return back()->with('success', "{$computer->pc_number} has been cleared and is now available.");
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
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        return view('personnel.full-schedule', compact('labs', 'days'));
    }
}
