<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Computer;
use App\Models\LabSession;

class PersonnelController extends Controller
{
    public function index()
    {
        // Group computers by lab to show a summary on the selection page
        $labs = Computer::select('lab_name')
            ->selectRaw('count(*) as total')
            ->selectRaw("sum(case when status = 'active' then 1 else 0 end) as occupied")
            ->groupBy('lab_name')
            ->get();

        return view('personnel.index', compact('labs'));
    }

    public function showLab($name)
    {
        $computers = Computer::where('lab_name', $name)->get();
        return view('personnel.lab-view', compact('computers', 'name'));
    }

    public function assign(Request $request, Computer $computer)
    {
        $request->validate([
            'student_name' => 'required|string|max:255',
            'student_id' => 'required|string|max:50',
        ]);

        // Create the liability record
        LabSession::create([
            'computer_id' => $computer->id,
            'student_name' => $request->student_name,
            'student_id_number' => $request->student_id,
            'time_in' => now(),
            'teacher_id' => auth()->id(),
        ]);

        // Mark PC as occupied
        $computer->update(['status' => 'active']);

        return back()->with('success', "{$computer->pc_number} is now assigned.");
    }

    public function release(Computer $computer)
    {
        // Find the active session and close it
        $session = $computer->activeSession;
        if ($session) {
            $session->update(['time_out' => now()]);
        }

        // Mark PC as available
        $computer->update(['status' => 'available']);
        return back()->with('success', "{$computer->pc_number} has been cleared.");
    }
    public function labs()
    {
        // Fetch labs with real-time occupancy and the last person who timed in
        $labs = Computer::select('lab_name')
            ->selectRaw('count(*) as total')
            ->selectRaw('SUM(case when status = "active" then 1 else 0 end) as occupied')
            ->groupBy('lab_name')
            ->get();

        return view('personnel.labs-overview', compact('labs'));
    }
}
