<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\CheckClearance;
use App\Http\Middleware\RestrictStudents;
use App\Http\Middleware\EnsureSuperAdmin;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Automatically run RestrictStudents on every web request
        $middleware->web(append: [
            RestrictStudents::class,
        ]);

        // Route aliases for specific manual protection
        $middleware->alias([
            'clearance'   => CheckClearance::class,
            'super-admin' => EnsureSuperAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
