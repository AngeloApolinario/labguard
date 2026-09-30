<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Lab;
use App\Models\LabSession;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class LabController extends Controller
{
    private function flashToast(
        string $type,
        string $title,
        string $message
    ): void {
        session()->flash('toast', [
            'type' => $type,
            'title' => $title,
            'message' => $message,
        ]);
    }

    public function index()
    {
        $labs = Lab::withCount([
            'computers as total_pcs',
            'computers as active_pcs' => function ($query) {
                $query->where('status', 'active');
            },
        ])->get();

        return view(
            'dashboard.labs.index',
            compact('labs')
        );
    }

    public function viewSchedule(Lab $lab)
    {
        $todayDate = now()->toDateString();
        $currentTime = now()->format('H:i:s');

        // 1. Fetch upcoming & ongoing events
        $activeEvents = Schedule::where(
            'lab_id',
            $lab->id
        )
            ->where(
                'is_event',
                true
            )
            ->where(function ($q) use (
                $todayDate,
                $currentTime
            ) {
                $q->whereDate(
                    'event_date',
                    '>',
                    $todayDate
                )
                    ->orWhere(function (
                        $todayQ
                    ) use (
                        $todayDate,
                        $currentTime
                    ) {
                        $todayQ->whereDate(
                            'event_date',
                            $todayDate
                        )
                            ->whereTime(
                                'end_time',
                                '>=',
                                $currentTime
                            );
                    });
            })
            ->get();

        // 2. Fetch all recurring classes
        $allClasses = Schedule::where(
            'lab_id',
            $lab->id
        )
            ->where(
                'is_event',
                false
            )
            ->with('user')
            ->get();

        // 3. Hide regular classes that collide with an active event
        $visibleClasses = $allClasses->filter(
            function ($class) use ($activeEvents) {
                $isMasked = $activeEvents->contains(
                    function ($event) use ($class) {
                        return $event->day === $class->day
                            && Carbon::parse(
                                $event->event_date
                            )->isSameWeek(now())
                            && $event->start_time < $class->end_time
                            && $event->end_time > $class->start_time;
                    }
                );

                return !$isMasked;
            }
        );

        // 4. Attach live attendee counts to events
        foreach ($activeEvents as $event) {
            $event->attendees_count = LabSession::where(
                'lab_id',
                $lab->id
            )
                ->whereDate(
                    'time_in',
                    $event->event_date
                )
                ->whereTime(
                    'time_in',
                    '>=',
                    $event->start_time
                )
                ->whereTime(
                    'time_in',
                    '<=',
                    $event->end_time
                )
                ->count();
        }

        // 5. Combine active events + non-masked classes
        $schedules = $visibleClasses
            ->concat($activeEvents)
            ->sortBy('start_time');

        // 6. Fetch past completed events
        $pastEvents = Schedule::where(
            'lab_id',
            $lab->id
        )
            ->where(
                'is_event',
                true
            )
            ->where(function ($q) use (
                $todayDate,
                $currentTime
            ) {
                $q->whereDate(
                    'event_date',
                    '<',
                    $todayDate
                )
                    ->orWhere(function (
                        $todayQ
                    ) use (
                        $todayDate,
                        $currentTime
                    ) {
                        $todayQ->whereDate(
                            'event_date',
                            $todayDate
                        )
                            ->whereTime(
                                'end_time',
                                '<',
                                $currentTime
                            );
                    });
            })
            ->latest('event_date')
            ->get();

        foreach ($pastEvents as $pe) {
            $pe->attendees_count = LabSession::where(
                'lab_id',
                $lab->id
            )
                ->whereDate(
                    'time_in',
                    $pe->event_date
                )
                ->whereTime(
                    'time_in',
                    '>=',
                    $pe->start_time
                )
                ->whereTime(
                    'time_in',
                    '<=',
                    $pe->end_time
                )
                ->count();
        }

        $teachers = User::whereIn(
            'role',
            [
                'personnel',
                'teacher',
                'admin'
            ]
        )
            ->orderBy('name')
            ->get();

        return view(
            'dashboard.labs.schedule',
            compact(
                'lab',
                'schedules',
                'pastEvents',
                'teachers'
            )
        );
    }

    /**
     * Check for Overlaps Before Submission
     */
    public function checkConflict(
        Request $request,
        $labId
    ) {
        $isEvent = $request->input(
            'schedule_type'
        ) === 'event';

        $startTime = $request->input(
            'start_time'
        );

        $endTime = $request->input(
            'end_time'
        );

        $eventDate = $request->input(
            'event_date'
        );

        $day = $isEvent
            ? (
                $eventDate
                ? Carbon::parse(
                    $eventDate
                )->format('l')
                : null
            )
            : $request->input(
                'day'
            );

        if (
            !$startTime
            || !$endTime
            || (!$day && !$eventDate)
        ) {
            return response()->json([
                'has_conflict' => false
            ]);
        }

        $conflict = Schedule::where(
            'lab_id',
            $labId
        )
            ->where(function ($q) use (
                $isEvent,
                $eventDate,
                $day
            ) {
                if ($isEvent) {
                    $q->where(function (
                        $sub
                    ) use (
                        $eventDate
                    ) {
                        $sub->where(
                            'is_event',
                            true
                        )
                            ->whereDate(
                                'event_date',
                                $eventDate
                            );
                    })
                        ->orWhere(function (
                            $sub
                        ) use (
                            $day
                        ) {
                            $sub->where(
                                'is_event',
                                false
                            )
                                ->where(
                                    'day',
                                    $day
                                );
                        });
                } else {
                    $q->where(
                        'day',
                        $day
                    );
                }
            })
            ->where(function ($q) use (
                $startTime,
                $endTime
            ) {
                $q->where(
                    'start_time',
                    '<',
                    $endTime
                )
                    ->where(
                        'end_time',
                        '>',
                        $startTime
                    );
            })
            ->with('user')
            ->first();

        if ($conflict) {
            return response()->json([
                'has_conflict' => true,
                'conflict' => [
                    'subject_code' =>
                    $conflict->subject_code
                        ?? 'Reservation',

                    'host_name' =>
                    $conflict->is_event
                        ? (
                            $conflict->speaker_name
                            ?? 'Guest Speaker'
                        )
                        : (
                            $conflict->user->name
                            ?? 'Instructor'
                        ),

                    'is_event' =>
                    (bool) $conflict->is_event,

                    'day' =>
                    $conflict->day,

                    'time_window' =>
                    Carbon::parse(
                        $conflict->start_time
                    )->format('h:i A')
                        . ' — ' .
                        Carbon::parse(
                            $conflict->end_time
                        )->format('h:i A'),
                ]
            ]);
        }

        return response()->json([
            'has_conflict' => false
        ]);
    }

    /**
     * Store Schedule or Event
     */
    public function storeSchedule(
        Request $request,
        $labId
    ) {
        $isEvent = $request->input(
            'schedule_type'
        ) === 'event';

        $request->validate([
            'schedule_type' =>
            'required|in:class,event',

            'user_id' =>
            $isEvent
                ? 'nullable'
                : 'required|exists:users,id',

            'speaker_name' =>
            $isEvent
                ? 'required|string|max:255'
                : 'nullable',

            'event_date' =>
            $isEvent
                ? 'required|date|after_or_equal:today'
                : 'nullable',

            'day' =>
            $isEvent
                ? 'nullable'
                : 'required|string',

            'subject_code' =>
            'required|string|max:100',

            'start_time' =>
            'required',

            'end_time' =>
            'required|after:start_time',
        ]);

        $day = $isEvent
            ? Carbon::parse(
                $request->event_date
            )->format('l')
            : $request->day;

        Schedule::create([
            'lab_id' =>
            $labId,

            'is_event' =>
            $isEvent,

            'event_date' =>
            $isEvent
                ? $request->event_date
                : null,

            'user_id' =>
            $isEvent
                ? null
                : $request->user_id,

            'speaker_name' =>
            $isEvent
                ? $request->speaker_name
                : null,

            'day' =>
            $day,

            'subject_code' =>
            strtoupper(
                trim(
                    $request->subject_code
                )
            ),

            'start_time' =>
            $request->start_time,

            'end_time' =>
            $request->end_time,
        ]);

        if ($isEvent) {
            $this->flashToast(
                'success',
                'Event Scheduled',
                'Event scheduled successfully. Overlapping classes are masked for the event date.'
            );
        } else {
            $this->flashToast(
                'success',
                'Class Scheduled',
                'Class schedule established successfully.'
            );
        }

        return back();
    }

    /**
     * Stream CSV of Event Attendees
     */
    public function exportEventAttendance(
        Schedule $schedule
    ) {
        $filename =
            "Attendance_"
            . str_replace(
                ' ',
                '_',
                $schedule->subject_code
            )
            . "_"
            . (
                $schedule->event_date
                ?? now()->toDateString()
            )
            . ".csv";

        $sessions = LabSession::with(
            'computer'
        )
            ->where(
                'lab_id',
                $schedule->lab_id
            )
            ->whereDate(
                'time_in',
                $schedule->event_date
            )
            ->whereTime(
                'time_in',
                '>=',
                $schedule->start_time
            )
            ->whereTime(
                'time_in',
                '<=',
                $schedule->end_time
            )
            ->orderBy('time_in')
            ->get();

        $headers = [
            "Content-type" =>
            "text/csv; charset=UTF-8",

            "Content-Disposition" =>
            "attachment; filename={$filename}",

            "Pragma" =>
            "no-cache",

            "Cache-Control" =>
            "must-revalidate, post-check=0, pre-check=0",

            "Expires" =>
            "0"
        ];

        return response()->stream(
            function () use (
                $sessions,
                $schedule
            ) {
                $handle = fopen(
                    'php://output',
                    'w'
                );

                fprintf(
                    $handle,
                    chr(0xEF)
                        . chr(0xBB)
                        . chr(0xBF)
                );

                fputcsv(
                    $handle,
                    [
                        'EVENT / TOPIC',
                        $schedule->subject_code
                    ]
                );

                fputcsv(
                    $handle,
                    [
                        'SPEAKER / HOST',
                        $schedule->speaker_name
                            ?? 'Guest Speaker'
                    ]
                );

                fputcsv(
                    $handle,
                    [
                        'EVENT DATE',
                        $schedule->event_date
                    ]
                );

                fputcsv(
                    $handle,
                    [
                        'TIME WINDOW',
                        Carbon::parse(
                            $schedule->start_time
                        )->format('h:i A')
                            . ' - ' .
                            Carbon::parse(
                                $schedule->end_time
                            )->format('h:i A')
                    ]
                );

                fputcsv(
                    $handle,
                    [
                        'TOTAL ATTENDEES',
                        $sessions->count()
                    ]
                );

                fputcsv(
                    $handle,
                    []
                );

                fputcsv(
                    $handle,
                    [
                        '#',
                        'Student Name',
                        'Student ID Number',
                        'Workstation',
                        'Time In',
                        'Time Out',
                        'Session Duration'
                    ]
                );

                foreach (
                    $sessions as $i => $s
                ) {
                    fputcsv(
                        $handle,
                        [
                            $i + 1,

                            $s->student_name,

                            $s->student_id_number,

                            $s->computer->pc_number
                                ?? 'PC-??',

                            $s->time_in
                                ? Carbon::parse(
                                    $s->time_in
                                )->format('h:i A')
                                : 'N/A',

                            $s->time_out
                                ? Carbon::parse(
                                    $s->time_out
                                )->format('h:i A')
                                : 'Active',

                            $s->time_out
                                ? Carbon::parse(
                                    $s->time_in
                                )->diffForHumans(
                                    Carbon::parse(
                                        $s->time_out
                                    ),
                                    true
                                )
                                : 'Ongoing',
                        ]
                    );
                }

                fclose($handle);
            },
            200,
            $headers
        );
    }

    public function destroySchedule(
        Request $request,
        $id
    ) {
        $schedule = Schedule::findOrFail(
            $id
        );

        $labId = $schedule->lab_id;

        $day = $request->input(
            'day',
            'All'
        );

        $scheduleType = $schedule->is_event
            ? 'Event'
            : 'Class';

        $schedule->delete();

        $this->flashToast(
            'success',
            "{$scheduleType} Removed",
            "{$scheduleType} slot revoked successfully."
        );

        return redirect()->route(
            'dashboard.labs.schedule',
            [
                'lab' => $labId,
                'day' => $day
            ]
        );
    }

    public function destroyByDay(
        Request $request,
        $labId
    ) {
        $day = $request->input(
            'day',
            'All'
        );

        if (
            $day
            && $day !== 'All'
        ) {
            $deletedCount = Schedule::where(
                'lab_id',
                $labId
            )
                ->where(
                    'day',
                    $day
                )
                ->delete();

            $message =
                "All slots for {$day} "
                . "revoked successfully.";

            $title = "Day Schedule Cleared";
        } else {
            $deletedCount = Schedule::where(
                'lab_id',
                $labId
            )->delete();

            $message =
                "All laboratory schedule slots "
                . "revoked successfully.";

            $title = "All Schedules Cleared";
        }

        if ($deletedCount > 0) {
            $this->flashToast(
                'success',
                $title,
                $message
            );
        } else {
            $this->flashToast(
                'info',
                'Nothing to Remove',
                'There were no schedule slots to revoke.'
            );
        }

        return redirect()->route(
            'dashboard.labs.schedule',
            [
                'lab' => $labId,
                'day' => $day
            ]
        );
    }

    public function update(
        Request $request,
        Lab $lab
    ) {
        $validated = $request->validate([
            'name' =>
            'required|string|max:255',

            'location' =>
            'required|string|max:255',

            'pc_count' =>
            'required|integer|min:1|max:60',
        ]);

        $lab->update([
            'name' =>
            $validated['name'],

            'location' =>
            $validated['location'],
        ]);

        $currentCount = $lab->computers()->count();

        $targetCount = (int)
        $validated['pc_count'];

        $unitsAdded = 0;
        $unitsRemoved = 0;

        if (
            $targetCount
            > $currentCount
        ) {
            $unitsAdded =
                $targetCount
                - $currentCount;

            for (
                $i = 1;
                $i <= $unitsAdded;
                $i++
            ) {
                $nextPcNumber =
                    $currentCount + $i;

                $lab->computers()->create([
                    'pc_number' =>
                    'PC-'
                        . str_pad(
                            $nextPcNumber,
                            2,
                            '0',
                            STR_PAD_LEFT
                        ),

                    'status' =>
                    'available',
                ]);
            }
        } elseif (
            $targetCount
            < $currentCount
        ) {
            $unitsRemoved =
                $currentCount
                - $targetCount;

            $lab->computers()
                ->latest('id')
                ->take($unitsRemoved)
                ->delete();
        }

        if ($unitsAdded > 0) {
            $this->flashToast(
                'success',
                'Lab Updated',
                "Laboratory details updated and {$unitsAdded} computer unit(s) added successfully."
            );
        } elseif ($unitsRemoved > 0) {
            $this->flashToast(
                'success',
                'Lab Updated',
                "Laboratory details updated and {$unitsRemoved} computer unit(s) removed successfully."
            );
        } else {
            $this->flashToast(
                'success',
                'Lab Updated',
                'Laboratory details and unit capacity updated successfully.'
            );
        }

        return redirect()->back();
    }
}
