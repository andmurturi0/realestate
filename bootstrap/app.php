<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'setlocale' => SetLocale::class,
        ]);

        // The appearance cookie is written directly by useAppearance.ts (plain
        // document.cookie, same convention as the locale switcher) so app.blade.php
        // can read it server-side on first paint and apply the `dark` class before
        // Vue mounts. EncryptCookies can't decrypt a value JS wrote in plain text —
        // it would silently null it out — so this cookie must be excluded.
        $middleware->encryptCookies(except: ['appearance']);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
