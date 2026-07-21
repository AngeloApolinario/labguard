<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;
use App\Models\User;
use App\Models\Lab;
use App\Observers\UserObserver;
use App\Observers\LabObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        User::observe(UserObserver::class);
        Lab::observe(LabObserver::class);

        Event::listen(Registered::class, function ($event) {
            // This sends the standard Laravel verification email with the button
            $event->user->sendEmailVerificationNotification();
        });
    }
}
