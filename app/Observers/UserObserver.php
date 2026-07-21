<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * Fields to ignore when tracking changes.
     */
    protected array $ignoredFields = [
        'updated_at',
        'created_at',
        'remember_token',
        'password', // Prevents saving hashes in the logs
    ];

    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        activity()
            ->useLog('user_management')
            ->performedOn($user)
            ->causedBy(auth()->user() ?? $user)
            ->withProperties([
                'target_id'  => $user->id,
                'email'      => $user->email,
                'role'       => $user->role ?? 'user',
                'ip_address' => request()->ip(),
            ])
            ->log("Created new user account: {$user->name}");
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // Get the fields that changed during this save
        $changes = $user->getChanges();

        // Remove ignored fields
        foreach ($this->ignoredFields as $field) {
            unset($changes[$field]);
        }

        // Stop if no meaningful fields changed
        if (empty($changes)) {
            return;
        }

        // Get original values before the update
        $original = [];
        foreach (array_keys($changes) as $key) {
            $original[$key] = $user->getOriginal($key);
        }

        activity()
            ->useLog('user_management')
            ->performedOn($user)
            ->causedBy(auth()->user() ?? $user)
            ->withProperties([
                'target_id'  => $user->id,
                'before'     => $original,
                'after'      => $changes,
                'ip_address' => request()->ip(),
            ])
            ->log("Edited profile details for {$user->name}");
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        activity()
            ->useLog('user_management')
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->withProperties([
                'target_id'  => $user->id,
                'email'      => $user->email,
                'ip_address' => request()->ip(),
            ])
            ->log("Deleted user account: {$user->name}");
    }

    /**
     * Handle the User "restored" event (for soft deletes).
     */
    public function restored(User $user): void
    {
        activity()
            ->useLog('user_management')
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->withProperties([
                'target_id'  => $user->id,
                'email'      => $user->email,
                'ip_address' => request()->ip(),
            ])
            ->log("Restored user account: {$user->name}");
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        activity()
            ->useLog('user_management')
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->withProperties([
                'target_id'  => $user->id,
                'email'      => $user->email,
                'ip_address' => request()->ip(),
            ])
            ->log("Permanently deleted user account: {$user->name}");
    }
}
