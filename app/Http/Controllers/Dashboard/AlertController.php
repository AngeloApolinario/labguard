<?php


namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Computer;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    private function flashToast(string $type, string $title, string $message): void
    {
        session()->flash('toast', [
            'type' => $type,
            'title' => $title,
            'message' => $message,
        ]);
    }

    /**
     * Display a listing of all alerts (History).
     */
    public function index(Request $request)
    {
        // Eager load relationships so Laboratory and Reporter data are accessible
        $query = Alert::with(['computer.lab', 'reporter'])->latest();

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

        // Paginate results while preserving filter query parameters in links
        $alerts = $query->paginate(15)->appends($request->query());

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
            'reporter_id' => auth()->id(),
            'issue_type' => $request->issue_type,
            'remarks' => $request->remarks,
            'status' => 'pending',
        ]);

        $this->flashToast('success', 'Alert Received', 'Alert received and logged successfully.');

        return response()->json([
            'message' => 'Alert received',
            'id' => $alert->id,
            'toast' => [
                'type' => 'success',
                'title' => 'Alert Received',
                'message' => 'Alert received and logged successfully.',
            ],
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

        $this->flashToast('success', 'Issue Resolved', 'Issue marked as resolved.');

        return back()->with('success', 'Issue marked as resolved.');
    }

    public function undoResolution(Alert $alert)
    {
        // Safety check: ensure we only undo alerts that are actually resolved
        if ($alert->status !== 'resolved') {
            $this->flashToast('danger', 'Undo Failed', 'Only resolved alerts can be restored to pending status.');

            return redirect()->back()->with('error', 'Only resolved alerts can be restored to pending status.');
        }

        // Update status back to pending
        $alert->update([
            'status' => 'pending',
            'resolved_at' => null,
            'resolved_by' => null,
        ]);

        $this->flashToast('warning', 'Resolution Undone', "Alert for {$alert->computer->pc_number} restored to pending status.");

        return redirect()->back()->with('success', "Alert for {$alert->computer->pc_number} restored to pending status.");
    }
}
