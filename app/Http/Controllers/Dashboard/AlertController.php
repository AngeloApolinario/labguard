<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Computer;
use Illuminate\Http\Request;
use App\Models\Lab;

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
     * Display a listing of all alerts (History) with filtering and pagination.
     */
    public function index(Request $request)
    {
        $query = Alert::with(['computer.lab', 'reporter'])->latest();

        // 1. COMBINED FILTER: Restricts PC Number strictly to the selected Laboratory
        if ($request->filled('lab_id') || $request->filled('pc_number')) {
            $query->whereHas('computer', function ($q) use ($request) {
                if ($request->filled('lab_id')) {
                    $q->where('lab_id', $request->lab_id);
                }

                if ($request->filled('pc_number')) {
                    $cleanPc = trim($request->pc_number);
                    $q->where('pc_number', 'like', '%' . $cleanPc . '%');
                }
            });
        }

        // 2. Filter by Date Reported
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        // 3. Filter by Status (pending, resolved, discarded)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 4. Metric Counts for Header Cards
        $totalReports    = Alert::count();
        $unresolvedCount = Alert::where('status', 'pending')->count();

        // 5. Paginate and preserve all active filter query parameters
        $alerts = $query->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($alerts);
        }

        // 6. Fetch all laboratories for the dropdown filter
        $allLabs = Lab::orderBy('name')->get();

        return view('dashboard.alerts.index', compact(
            'alerts',
            'allLabs',
            'totalReports',
            'unresolvedCount'
        ));
    }

    /**
     * Store a new alert sent from terminal devices or clients.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'pc_number'  => 'required|exists:computers,pc_number',
            'issue_type' => 'required|string',
            'remarks'    => 'required|string',
        ]);

        $computer = Computer::where('pc_number', $request->pc_number)->first();

        $alert = Alert::create([
            'computer_id' => $computer->id,
            'lab_id'      => $computer->lab_id,
            'reported_by' => auth()->id(),
            'issue_type'  => $request->issue_type,
            'remarks'     => $request->remarks,
            'status'      => 'pending',
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Alert received and logged successfully.',
                'alert'   => $alert,
            ], 201);
        }

        $this->flashToast('success', 'Alert Created', 'Incident report successfully submitted.');
        return back()->with('success', 'Alert reported successfully.');
    }

    /**
     * Mark an alert as resolved.
     */
    public function resolve(Request $request, $alert)
    {
        $alert = $alert instanceof Alert ? $alert : Alert::findOrFail($alert);

        $alert->update([
            'status'      => 'resolved',
            'resolved_at' => now(),
        ]);

        if (function_exists('activity')) {
            activity()
                ->useLog('incident_response')
                ->performedOn($alert)
                ->causedBy(auth()->user())
                ->withProperties([
                    'alert_title' => $alert->title ?? $alert->issue_type,
                    'lab_room'    => $alert->computer->lab->name ?? 'N/A',
                    'notes'       => $request->resolution_notes,
                ])
                ->log("Resolved security alert: '{$alert->issue_type}'");
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Issue marked as resolved.',
                'alert'   => $alert,
            ]);
        }

        $this->flashToast('success', 'Alert Resolved', 'The alert has been marked as resolved.');
        return back()->with('success', 'Issue marked as resolved.');
    }

    /**
     * Discard an alert as a false alarm or trolling.
     */
    public function discardAlert(Request $request, $alert)
    {
        $alert = $alert instanceof Alert ? $alert : Alert::find($alert);

        if (!$alert) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Alert not found.'], 404);
            }
            $this->flashToast('danger', 'Not Found', 'Alert record could not be found.');
            return back()->with('error', 'Alert not found.');
        }

        $alert->update([
            'status'      => 'discarded',
            'resolved_at' => now(),
        ]);

        if (function_exists('activity')) {
            activity()
                ->useLog('incident_response')
                ->performedOn($alert)
                ->causedBy(auth()->user())
                ->log("Dismissed alert as false alarm / discarded: '{$alert->issue_type}'");
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Alert successfully discarded as a false alarm.',
                'alert'   => $alert,
            ]);
        }

        $this->flashToast('success', 'Alert Discarded', 'The alert has been successfully dismissed as a false alarm.');
        return back()->with('success', 'Alert successfully discarded as a false alarm.');
    }

    /**
     * Restore a resolved OR discarded alert back to pending status.
     */
    public function undoResolution(Request $request, $alert)
    {
        $alert = $alert instanceof Alert ? $alert : Alert::findOrFail($alert);

        // Allowed to undo BOTH resolved and discarded alerts
        if (!in_array($alert->status, ['resolved', 'discarded'])) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Only resolved or discarded alerts can be restored to pending status.',
                ], 422);
            }
            $this->flashToast('warning', 'Invalid Action', 'Only resolved or discarded alerts can be restored.');
            return back()->with('error', 'Only resolved or discarded alerts can be restored.');
        }

        $previousStatus = $alert->status;

        $alert->update([
            'status'      => 'pending',
            'resolved_at' => null,
        ]);

        if (function_exists('activity')) {
            activity()
                ->useLog('incident_response')
                ->performedOn($alert)
                ->causedBy(auth()->user())
                ->log("Restored {$previousStatus} alert for {$alert->computer->pc_number} back to pending");
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Alert for {$alert->computer->pc_number} restored to pending status.",
                'alert'   => $alert,
            ]);
        }

        $this->flashToast('info', 'Alert Restored', "Alert for {$alert->computer->pc_number} restored to pending status.");
        return back()->with('success', "Alert for {$alert->computer->pc_number} restored to pending status.");
    }
    public function manualStore(Request $request)
    {
        $validated = $request->validate([
            'lab_id'      => 'required|exists:labs,id',
            'computer_id' => 'required|exists:computers,id',
            'reported_by' => 'required|exists:users,id',
            'issue_type'  => 'required|string',
            'remarks'     => 'required|string|max:1000',
        ]);

        Alert::create([
            'computer_id' => $validated['computer_id'],
            'lab_id'      => $validated['lab_id'],
            'reported_by' => $validated['reported_by'],
            'issue_type'  => $validated['issue_type'],
            'remarks'     => $validated['remarks'],
            'status'      => 'pending',
        ]);

        return redirect()->route('personnel.alerts')->with('success', 'Manual incident ticket logged successfully.');
    }
}
