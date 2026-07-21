<?php

namespace App\Observers;

use App\Models\Lab;

class LabObserver
{
    /**
     * Fields to ignore when checking for changes.
     */
    protected array $ignoredFields = [
        'updated_at',
        'created_at',
    ];

    /**
     * Handle the Lab "updated" event.
     */
    public function updated(Lab $lab): void
    {
        // Get fields that actually changed during save
        $changes = $lab->getChanges();

        // Remove ignored timestamp fields
        foreach ($this->ignoredFields as $field) {
            unset($changes[$field]);
        }

        // Exit early if no tracked fields changed
        if (empty($changes)) {
            return;
        }

        // Get original values prior to update
        $original = [];
        foreach (array_keys($changes) as $key) {
            $original[$key] = $lab->getOriginal($key);
        }

        activity()
            ->useLog('hardware')
            ->performedOn($lab)
            ->causedBy(auth()->user())
            ->withProperties([
                'lab_id'     => $lab->id,
                'before'     => $original,
                'after'      => $changes,
                'ip_address' => request()->ip(),
            ])
            ->log("Updated lab details for: {$lab->name}");
    }

    /**
     * Handle the Lab "created" event.
     */
    public function created(Lab $lab): void
    {
        activity()
            ->useLog('hardware')
            ->performedOn($lab)
            ->causedBy(auth()->user())
            ->withProperties([
                'lab_id'     => $lab->id,
                'name'       => $lab->name,
                'location'   => $lab->location,
                'ip_address' => request()->ip(),
            ])
            ->log("Created new lab room: {$lab->name}");
    }

    /**
     * Handle the Lab "deleted" event.
     */
    public function deleted(Lab $lab): void
    {
        activity()
            ->useLog('hardware')
            ->performedOn($lab)
            ->causedBy(auth()->user())
            ->withProperties([
                'lab_id'     => $lab->id,
                'name'       => $lab->name,
                'ip_address' => request()->ip(),
            ])
            ->log("Deleted lab room: {$lab->name}");
    }
}
