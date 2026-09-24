<?php

use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Hostinger terminates TLS in front of PHP. Without this the request
        // looks plain-http, url() emits http:// links, and a secure session
        // cookie is never sent back — an infinite redirect loop on /admin/login.
        $middleware->trustProxies(at: '*');

        // There is no route named "login"; the admin panel owns its own.
        // Without this an unauthenticated hit on /admin/* throws
        // RouteNotFoundException and 500s instead of redirecting.
        $middleware->redirectGuestsTo('/admin/login');

        $middleware->alias([
            'active' => EnsureUserIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // The admin panel's inline category manager talks JSON. Without
        // expectsJson() here an expired session would answer an AJAX call with
        // an HTML redirect, which the fetch() sees as a network-ish failure and
        // reports as the wrong thing entirely.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
