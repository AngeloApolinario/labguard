<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Alert;
use App\Models\Computer;

class AlertController extends Controller
{
    /**
     * Display a listing of all alerts (History).
     */
    public function index(Request $request)
    {
        $query = Alert::with('computer')->latest();

        // Filter by PC Number
        if ($request->filled('pc_number')) {
            $query->whereHas('computer', function ($q) use ($request) {
                $q->where('pc_number', 'like', '%' . $request->pc_number . '%');
            });
        }

        // Filter by Specific Date
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        // Filter by Status (pending/resolved)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $alerts = $query->get();

        return view('dashboard.alerts.index', compact('alerts'));
    }

    /**
     * Store a new alert sent from the Python Terminal.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'pc_number' => 'required|exists:computers,pc_number',
            'issue_type' => 'required|string',
            'remarks' => 'required|string',
        ]);

        $computer = Computer::where('pc_number', $request->pc_number)->first();

        $alert = Alert::create([
            'computer_id' => $computer->id,
            'issue_type' => $request->issue_type,
            'remarks' => $request->remarks,
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'Alert received', 'id' => $alert->id], 201);
    }

    /**
     * Mark an alert as resolved.
     */
    public function resolve(Alert $alert)
    {
        $alert->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);

        return back()->with('success', 'Issue marked as resolved.');
    }
}
