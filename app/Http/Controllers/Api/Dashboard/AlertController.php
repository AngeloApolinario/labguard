<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Computer;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    /**
     * Display a listing of all alerts (History) with filtering and pagination.
     */
    public function index(Request $request)
    {
        $query = Alert::with(['computer.lab', 'reporter'])->latest();

        if ($request->filled('pc_number')) {
            $query->whereHas('computer', function ($q) use ($request) {
                $q->where('pc_number', 'like', '%' . $request->pc_number . '%');
            });
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $alerts = $query->paginate(15)->appends($request->query());

        return response()->json($alerts);
    }

    /**
     * Store a new alert sent from terminal devices or clients.
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
            'reporter_id' => auth()->id(),
            'issue_type' => $request->issue_type,
            'remarks' => $request->remarks,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Alert received and logged successfully.',
            'alert' => $alert,
        ], 201);
    }

    /**
     * Mark an alert as resolved.
     */
    public function resolve(Request $request, Alert $alert)
    {
        $alert->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);

        activity()
            ->useLog('incident_response')
            ->performedOn($alert)
            ->causedBy(auth()->user())
            ->withProperties([
                'alert_title' => $alert->title ?? $alert->issue_type,
                'lab_room' => $alert->computer->laboratory->name ?? 'N/A',
                'notes' => $request->resolution_notes,
            ])
            ->log("Resolved security alert: '{$alert->issue_type}'");

        return response()->json([
            'message' => 'Issue marked as resolved.',
            'alert' => $alert,
        ]);
    }

    /**
     * Restore a resolved alert back to pending status.
     */
    public function undoResolution(Alert $alert)
    {
        if ($alert->status !== 'resolved') {
            return response()->json([
                'message' => 'Only resolved alerts can be restored to pending status.',
            ], 422);
        }

        $alert->update([
            'status' => 'pending',
            'resolved_at' => null,
            'resolved_by' => null,
        ]);

        return response()->json([
            'message' => "Alert for {$alert->computer->pc_number} restored to pending status.",
            'alert' => $alert,
        ]);
    }
}
