<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Supported public-site locales, `sq` first as the default (unprefixed) one.
     *
     * @var list<string>
     */
    public const LOCALES = ['sq', 'en', 'de'];

    /**
     * Handle an incoming request.
     *
     * Resolution order: the `{locale}` route segment (only present on the
     * `/en/...` and `/de/...` mirror routes), then the `locale` cookie from a
     * previous visit, then `sq`.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->route('locale') ?? $request->cookie('locale') ?? 'sq';

        if (! in_array($locale, self::LOCALES, true)) {
            $locale = 'sq';
        }

        App::setLocale($locale);

        Cookie::queue('locale', $locale, 60 * 24 * 365);

        return $next($request);
    }
}
