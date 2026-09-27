<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Computer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertApiController extends Controller
{
    /**
     * Display a listing of all alerts with filtering and pagination.
     */
    public function index(Request $request): JsonResponse
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

        $alerts = $query->paginate(15);

        return response()->json($alerts);
    }

    /**
     * Store a new alert.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pc_number'  => 'required|exists:computers,pc_number',
            'issue_type' => 'required|string',
            'remarks'    => 'required|string',
        ]);

        $computer = Computer::where('pc_number', $validated['pc_number'])->first();

        $alert = Alert::create([
            'computer_id' => $computer->id,
            'lab_id'      => $computer->lab_id,
            'reported_by' => auth()->id(),
            'issue_type'  => $validated['issue_type'],
            'remarks'     => $validated['remarks'],
            'status'      => 'pending',
        ]);

        $alert->load(['computer.lab', 'reporter']);

        return response()->json([
            'message' => 'Alert received and logged successfully.',
            'alert'   => $alert,
        ], 201);
    }

    /**
     * Mark an alert as resolved.
     */
    public function resolve(Request $request, $id): JsonResponse
    {
        $alert = Alert::with(['computer.lab'])->findOrFail($id);

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

        return response()->json([
            'message' => 'Issue marked as resolved.',
            'alert'   => $alert,
        ]);
    }

    /**
     * Discard an alert as a false alarm.
     */
    public function discardAlert($id): JsonResponse
    {
        $alert = Alert::with(['computer.lab'])->find($id);

        if (!$alert) {
            return response()->json(['message' => 'Alert record could not be found.'], 404);
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

        return response()->json([
            'message' => 'Alert successfully discarded as a false alarm.',
            'alert'   => $alert,
        ]);
    }

    /**
     * Restore a resolved or discarded alert back to pending.
     */
    public function undoResolution($id): JsonResponse
    {
        $alert = Alert::with(['computer.lab'])->findOrFail($id);

        if (!in_array($alert->status, ['resolved', 'discarded'])) {
            return response()->json([
                'message' => 'Only resolved or discarded alerts can be restored.',
            ], 422);
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

        return response()->json([
            'message' => "Alert for {$alert->computer->pc_number} restored to pending status.",
            'alert'   => $alert,
        ]);
    }
}
