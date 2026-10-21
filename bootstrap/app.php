<?php

use App\Http\Middleware\HandleInertiaRequests;
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
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        // Telegram signs its updates with a secret header instead of a CSRF token.
        $middleware->preventRequestForgery(except: [
            'telegram/webhook',
        ]);

        // A visitor opening the app's address is most likely new, so they land on sign-up.
        // Any other page was most likely opened by a user whose session ended, so they sign in.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('/') ? route('register') : route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
