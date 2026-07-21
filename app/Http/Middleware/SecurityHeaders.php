<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Faza 10 §2. The nonce is generated before the response is built (Vite
     * tags — including hot-reload script tags in dev — pick it up
     * automatically once set, see Vite::useCspNonce()) and reused for the
     * app.blade.php inline scripts (dark-mode toggle, JSON-LD) via
     * Vite::cspNonce() in the view.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Vite::useCspNonce();

        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=(), payment=(), usb=()');
        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy($nonce));

        return $response;
    }

    private function contentSecurityPolicy(string $nonce): string
    {
        return implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors 'none'",
            "form-action 'self'",
            // No 'unsafe-inline' here, ever — app.blade.php's two inline
            // scripts (dark-mode toggle, JSON-LD) carry this same nonce.
            "script-src 'self' 'nonce-{$nonce}'",
            // ApexCharts renders its SVG charts with computed inline styles
            // at runtime, which can't be nonced — 'unsafe-inline' is scoped
            // to style-src only, never script-src.
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
            'font-src '.implode(' ', $this->fontSources()),
            'img-src '.implode(' ', $this->imgSources()),
            'connect-src '.implode(' ', $this->connectSources()),
        ]);
    }

    /**
     * @return list<string>
     */
    private function fontSources(): array
    {
        return ["'self'", 'https://fonts.bunny.net'];
    }

    /**
     * data: covers Leaflet's bundled CSS, which references its default
     * marker artwork as data URIs. The Supabase storage host is read from
     * config (never hardcoded — CLAUDE.md's single-tenant rule applies to
     * infra config too, not just branding) so this keeps working unchanged
     * across installations pointed at a different Supabase project.
     *
     * @return list<string>
     */
    private function imgSources(): array
    {
        $sources = ["'self'", 'data:', 'https://tile.openstreetmap.org'];

        $storageHost = parse_url((string) config('filesystems.disks.supabase.url'), PHP_URL_HOST);

        if (is_string($storageHost) && $storageHost !== '') {
            $sources[] = "https://{$storageHost}";
        }

        return $sources;
    }

    /**
     * Vite's dev server (HMR websocket + asset ping) only exists outside
     * production. Scheme-only sources, not enumerated hosts: the port is
     * whatever's free, and the host varies by OS/Node (localhost, 127.0.0.1,
     * or the IPv6 loopback — bracketed IPv6 host-literals like [::1] aren't
     * reliably valid under CSP's host-source grammar and got silently
     * dropped by the browser, which is exactly what this box binds to per
     * public/hot). Dev-only, so being broad here is low-risk.
     *
     * @return list<string>
     */
    private function connectSources(): array
    {
        $sources = ["'self'"];

        if (! app()->isProduction()) {
            array_push($sources, 'ws:', 'wss:', 'http:');
        }

        return $sources;
    }
}
