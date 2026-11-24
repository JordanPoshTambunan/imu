<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Laravel 12 mendaftarkan middleware di sini, bukan di app/Http/Kernel.php.
        // File Kernel.php sudah dihapus dari project ini — jangan dibuat lagi,
        // isinya tidak akan pernah dibaca.
        $middleware->alias([
            'profile_complete' => \App\Http\Middleware\CheckProfileCompletion::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
