<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            SecurityHeaders::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'setlocale' => SetLocale::class,
        ]);

        // Both cookies are written directly by client-side JS in plain text
        // (appearance by useAppearance.ts, locale by LanguageSwitcher.vue) so
        // app.blade.php / SetLocale can read them server-side without a round
        // trip. EncryptCookies can't decrypt a value JS wrote in plain text —
        // it would silently null it out on the next request — so both must
        // be excluded. Faza 10 §11: this was a real bug — `locale` was
        // missing from this list, so a JS-driven language switch could
        // silently fail to persist server-side on the following request.
        $middleware->encryptCookies(except: ['appearance', 'locale']);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Faza 10 §10: a designed page instead of Laravel's bare default,
        // for the statuses visitors actually hit. Skipped for JSON/XHR
        // requests (they keep their normal error payload) and whenever
        // debug mode is on, so local development still sees the real trace.
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            if (! $request->expectsJson()
                && ! app()->hasDebugModeEnabled()
                && in_array($response->getStatusCode(), [403, 404, 500, 503], true)) {
                return Inertia::render('errors/Error', ['status' => $response->getStatusCode()])
                    ->toResponse($request)
                    ->setStatusCode($response->getStatusCode());
            }

            return $response;
        });
    })->create();
