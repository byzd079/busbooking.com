<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Render terminates HTTPS at its proxy before forwarding to Apache.
        $middleware->trustProxies(at: '*');

        $middleware->validateCsrfTokens(except: [
            '/pay-via-ajax',
            '/success',
            '/cancel',
            '/fail',
            '/ipn'
        ]);
        $middleware->alias([
            'onlyguest' => \App\Http\Middleware\OnlyGuest::class,
            'notguest' => \App\Http\Middleware\CheckNotGuest::class,
            'onlyuser' => \App\Http\Middleware\Onlyuser::class,
            'dashoboard' => \App\Http\Middleware\AdminDasboard::class,
            'admin' => \App\Http\Middleware\OnlyAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
