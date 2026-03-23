<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Schedule;

class CheckLabAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $labId = $request->route('lab'); // Assumes your route is /labs/{lab}
        $now = now()->format('H:i:s');
        $today = now()->format('l');

        // Find if there is an active schedule for this lab right now
        $activeSchedule = Schedule::where('lab_id', $labId)
            ->where('day', $today)
            ->where('start_time', '<=', $now)
            ->where('end_time', '>=', $now)
            ->first();

        // If a schedule exists AND it's not assigned to the logged-in teacher
        if ($activeSchedule && $activeSchedule->user_id !== auth()->id()) {
            return redirect()->route('dashboard.index')->with(
                'error',
                "Unauthorized Access: This lab is currently reserved for {$activeSchedule->subject_code} by another instructor."
            );
        }

        return $next($request);
    }
}
