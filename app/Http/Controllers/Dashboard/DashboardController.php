<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Computer;
use App\Models\LabSession;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $totalComputers = Computer::count();
        $activeStations = Computer::where('status', 'active')->count();
        $computers = Computer::orderBy('pc_number')->get();

        $alertsToday = 0;

        $labs = Computer::select('lab_name')->distinct()->pluck('lab_name');

        return view('dashboard.index', compact(
            'totalComputers',
            'activeStations',
            'computers',
            'alertsToday',
            'labs'
        ));
    }

    public function storeTeacher(Request $request)
    {
        // 1. Ensure only an Admin can do this
        if (auth()->user()->role !== 'admin') {
            abort(403);
        }

        // 2. Create the Teacher account
        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'teacher', // Manually set as teacher
        ]);

        return back()->with('success', 'Teacher account created successfully!');
    }
}
