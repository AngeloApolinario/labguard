<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Computer;
use App\Models\LabSession;

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
}
