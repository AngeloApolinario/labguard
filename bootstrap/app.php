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
        // [Put ALL your aliases in this one single list]
        $middleware->alias([
            'clearance'    => CheckClearance::class,
            'student.lock' => RestrictStudents::class,
            'super-admin'  => EnsureSuperAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create(); // This MUST be at the very end