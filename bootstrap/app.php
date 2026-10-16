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
        // Webhook Midtrans datang dari server luar, tanpa token CSRF.
        // Keamanannya dijaga lewat verifikasi signature di controller.
        $middleware->validateCsrfTokens(except: ['midtrans/notification']);

        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('admin/*') || $request->is('admin')) {
                return route('admin.login');
            }

            return route('owner.login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
