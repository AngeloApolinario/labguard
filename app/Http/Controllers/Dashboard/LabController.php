<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Computer;
use App\Models\LabSession;

class LabController extends Controller
{
    public function index()
    {
        // Group computers by lab_name and count them
        $labs = Computer::select('lab_name')
            ->selectRaw('count(*) as total_pcs')
            ->selectRaw('SUM(case when status = "active" then 1 else 0 end) as active_pcs')
            ->groupBy('lab_name')
            ->get();

        return view('dashboard.labs.index', compact('labs'));
    }
}
