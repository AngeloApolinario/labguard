<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LabSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionApiController extends Controller
{
    /**
     * Display archived/completed lab sessions with filtering and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        // 1. Only show sessions that have ended (Archive Mode)
        $query = LabSession::with(['computer.lab', 'checklist'])
            ->whereNotNull('time_out')
            ->latest('time_out');

        // 2. Apply Filters
        if ($request->filled('student_name')) {
            $query->where('student_name', 'like', '%' . $request->student_name . '%');
        }

        if ($request->filled('pc_number')) {
            $query->whereHas('computer', function ($q) use ($request) {
                $q->where('pc_number', 'like', '%' . $request->pc_number . '%');
            });
        }

        if ($request->filled('date')) {
            $query->whereDate('time_in', $request->date);
        }

        $sessions = $query->paginate(15);

        return response()->json($sessions);
    }
}
