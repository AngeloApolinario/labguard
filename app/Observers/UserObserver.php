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
        'last_login_at',
        'two_factor_recovery_codes',
        'two_factor_secret',
        'two_factor_confirmed_at',
        'email_verified_at',
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
        // Get changed attributes
        $changes = $user->getChanges();

        // Filter out ignored fields using array_diff_key
        $filteredChanges = array_diff_key($changes, array_flip($this->ignoredFields));

        // Stop if no meaningful fields changed
        if (empty($filteredChanges)) {
            return;
        }

        // Get original values for only the filtered changes
        $original = [];
        foreach (array_keys($filteredChanges) as $key) {
            $original[$key] = $user->getOriginal($key);
        }

        activity()
            ->useLog('user_management')
            ->performedOn($user)
            ->causedBy(auth()->user() ?? $user)
            ->withProperties([
                'target_id'  => $user->id,
                'before'     => $original,
                'after'      => $filteredChanges,
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
