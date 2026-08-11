<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Lab;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Http\Request;

class LabController extends Controller
{
    private function flashToast(string $type, string $title, string $message): void
    {
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

        return view('dashboard.labs.index', compact('labs'));
    }

    public function viewSchedule(Lab $lab)
    {
        $schedules = Schedule::where('lab_id', $lab->id)
            ->with('user')
            ->orderByRaw("FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")
            ->orderBy('start_time', 'asc')
            ->get();

        $teachers = User::whereIn('role', ['personnel', 'admin'])->get();

        return view('dashboard.labs.schedule', compact('lab', 'schedules', 'teachers'));
    }

    public function storeSchedule(Request $request, Lab $lab)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'subject_code' => 'required|string|max:50',
            'day' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
        ]);

        $overlap = Schedule::where('lab_id', $lab->id)
            ->where('day', $validated['day'])
            ->where(function ($query) use ($validated) {
                $query->where('start_time', '<', $validated['end_time'])
                    ->where('end_time', '>', $validated['start_time']);
            })->exists();

        if ($overlap) {
            $this->flashToast('danger', 'Schedule Conflict', 'The selected time overlaps with an existing slot.');

            return back()
                ->withInput()
                ->with('error', 'Schedule Conflict: The selected time overlaps with an existing slot.');
        }

        Schedule::create([
            'lab_id' => $lab->id,
            'user_id' => $validated['user_id'],
            'subject_code' => $validated['subject_code'],
            'day' => $validated['day'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
        ]);

        $this->flashToast('success', 'Schedule Created', 'Schedule entry created successfully.');

        return back()->with('success', 'Schedule entry created successfully.');
    }

    public function destroySchedule(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);
        $labId = $schedule->lab_id;
        $day = $request->input('day', 'All');

        $schedule->delete();

        $this->flashToast('success', 'Slot Revoked', 'Slot revoked successfully.');

        return redirect()->route('dashboard.labs.schedule', ['lab' => $labId, 'day' => $day])
            ->with('success', 'Slot revoked successfully.');
    }

    public function destroyByDay(Request $request, $labId)
    {
        $day = $request->input('day', 'All');

        if ($day && $day !== 'All') {
            Schedule::where('lab_id', $labId)->where('day', $day)->delete();
            $message = "All slots for {$day} revoked successfully.";
        } else {
            Schedule::where('lab_id', $labId)->delete();
            $message = 'All slots revoked successfully.';
        }

        $this->flashToast('success', 'Slots Revoked', $message);

        return redirect()->route('dashboard.labs.schedule', ['lab' => $labId, 'day' => $day])
            ->with('success', $message);
    }

    public function update(Request $request, Lab $lab)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'pc_count' => 'required|integer|min:1|max:60',
        ]);

        $lab->update([
            'name' => $validated['name'],
            'location' => $validated['location'],
        ]);

        $currentCount = $lab->computers()->count();
        $targetCount = (int) $validated['pc_count'];

        if ($targetCount > $currentCount) {
            $unitsToAdd = $targetCount - $currentCount;

            for ($i = 1; $i <= $unitsToAdd; $i++) {
                $nextPcNumber = $currentCount + $i;

                $lab->computers()->create([
                    'pc_number' => 'PC-' . str_pad($nextPcNumber, 2, '0', STR_PAD_LEFT),
                    'status' => 'available',
                ]);
            }
        } elseif ($targetCount < $currentCount) {
            $unitsToRemove = $currentCount - $targetCount;

            $lab->computers()
                ->latest('id')
                ->take($unitsToRemove)
                ->delete();
        }

        $this->flashToast('success', 'Lab Updated', 'Laboratory details and unit capacity updated successfully.');

        return redirect()->back()->with('success', 'Laboratory details and unit capacity updated successfully.');
    }
}
