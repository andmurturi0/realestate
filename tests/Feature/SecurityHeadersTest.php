<?php

use App\Models\Property;

// Faza 10 §2: SecurityHeaders sets the baseline hardening headers and a CSP
// that allows exactly what the app uses — nothing broader. script-src must
// never carry 'unsafe-inline'; the two inline scripts in app.blade.php are
// nonced instead (checked against the same nonce the CSP header advertises).

test('the baseline security headers are present', function () {
    $response = $this->get('/properties');

    $response->assertHeader('X-Frame-Options', 'DENY');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Permissions-Policy');
});

test('the CSP allows exactly the external sources the app uses', function () {
    $response = $this->get('/properties');

    $response->assertHeader('Content-Security-Policy');
    $csp = $response->headers->get('Content-Security-Policy');

    expect($csp)->toContain("style-src 'self' 'unsafe-inline' https://fonts.bunny.net")
        ->and($csp)->toContain('font-src')
        ->and($csp)->toContain('https://fonts.bunny.net')
        ->and($csp)->toContain('https://tile.openstreetmap.org')
        ->and($csp)->toContain(parse_url((string) config('filesystems.disks.supabase.url'), PHP_URL_HOST))
        ->and($csp)->toContain("frame-ancestors 'none'")
        ->and($csp)->toContain("object-src 'none'");
});

test('script-src never carries unsafe-inline, only a nonce', function () {
    $csp = $this->get('/properties')->headers->get('Content-Security-Policy');

    $scriptSrc = collect(explode(';', $csp))
        ->map(fn (string $directive) => trim($directive))
        ->first(fn (string $directive) => str_starts_with($directive, 'script-src'));

    expect($scriptSrc)->not->toContain('unsafe-inline')
        ->and($scriptSrc)->toMatch("/'nonce-[A-Za-z0-9]+'/");
});

test('the inline scripts in the page carry the same nonce the CSP header advertises', function () {
    // The dark-mode script only renders for the 'system' appearance.
    $response = $this->withUnencryptedCookie('appearance', 'system')->get('/properties');

    $csp = $response->headers->get('Content-Security-Policy');
    preg_match("/'nonce-([A-Za-z0-9]+)'/", $csp, $matches);
    $nonce = $matches[1] ?? null;

    expect($nonce)->not->toBeNull();
    $response->assertSee('<script nonce="'.$nonce.'">', false);
});

test('the JSON-LD block on a property page carries the CSP nonce', function () {
    $property = Property::factory()->published()->create();

    $response = $this->get(route('properties.show', $property));

    $csp = $response->headers->get('Content-Security-Policy');
    preg_match("/'nonce-([A-Za-z0-9]+)'/", $csp, $matches);
    $nonce = $matches[1] ?? null;

    expect($nonce)->not->toBeNull();
    $response->assertSee('<script type="application/ld+json" nonce="'.$nonce.'">', false);
});

test('the Vite dev-server websocket scheme is allowed in connect-src outside production', function () {
    $csp = $this->get('/properties')->headers->get('Content-Security-Policy');

    // Scheme-only (ws:), not enumerated hosts: bracketed IPv6 host-literals
    // like [::1] — exactly what Vite's dev server binds to on some boxes —
    // aren't reliably valid under CSP's host-source grammar and get
    // silently dropped, breaking HMR. The test environment is "testing",
    // not "production" — matches a local/staging box.
    expect($csp)->toContain('connect-src')
        ->and($csp)->toContain('ws:');
});

test('the Vite dev-server image scheme is allowed in img-src outside production', function () {
    // intl-tel-input's flag sprite loads from Vite's dev server in dev
    // (e.g. http://[::1]:5173/...), which img-src previously didn't cover —
    // only connect-src had the dev-only widening. Scheme-only (http:), same
    // reasoning as the connect-src ws:/wss: case above.
    $csp = $this->get('/properties')->headers->get('Content-Security-Policy');

    expect($csp)->toContain('img-src')
        ->and($csp)->toContain('http:');
});

test('img-src stays strict in production, excluding the Vite dev origin', function () {
    app()->instance('env', 'production');

    $csp = $this->get('/properties')->headers->get('Content-Security-Policy');

    $imgSrc = collect(explode(';', $csp))
        ->map(fn (string $directive) => trim($directive))
        ->first(fn (string $directive) => str_starts_with($directive, 'img-src'));

    expect($imgSrc)->not->toContain('http:');
});

// Regression: Ziggy's own script tag (the @routes Blade directive) was
// missed in the first pass — only the two hand-written app.blade.php
// scripts were nonced. A missing nonce on ANY script tag means that tag
// doesn't execute under an enforced CSP; for Ziggy specifically that meant
// route() was undefined and the whole app crashed. This scans every
// <script> tag in the response, not just known ones, so a future addition
// that forgets the nonce fails here too, not just in someone's browser.
test('every script tag in the page carries the CSP nonce', function () {
    $property = Property::factory()->published()->create();

    $response = $this->withUnencryptedCookie('appearance', 'system')->get(route('properties.show', $property));

    $csp = $response->headers->get('Content-Security-Policy');
    preg_match("/'nonce-([A-Za-z0-9]+)'/", $csp, $matches);
    $nonce = $matches[1] ?? null;
    expect($nonce)->not->toBeNull();

    preg_match_all('/<script\b[^>]*>/', $response->content(), $scriptTags);

    expect($scriptTags[0])->not->toBeEmpty();

    foreach ($scriptTags[0] as $tag) {
        expect($tag)->toContain('nonce="'.$nonce.'"');
    }
});
