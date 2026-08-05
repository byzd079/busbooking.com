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

        // Security headers: X-Frame-Options, CSP, HSTS, X-Content-Type-Options, Referrer-Policy
        $middleware->append(\Bepsvpt\SecureHeaders\SecureHeadersMiddleware::class);

        // SSLCommerz callbacks arrive as gateway-initiated requests without a session
        // CSRF token, so they must stay exempt. Each handler validates the transaction
        // against the gateway / enforces Pending-only state transitions instead.
        $middleware->validateCsrfTokens(except: [
            '/success',
            '/fail',
            '/cancel',
            '/ipn',
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
