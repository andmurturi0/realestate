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

test('the Vite dev-server origins are allowed in connect-src outside production', function () {
    $csp = $this->get('/properties')->headers->get('Content-Security-Policy');

    // The test environment is "testing", not "production" — matches how a
    // staging/local box (not yet cut over to APP_ENV=production) behaves.
    expect($csp)->toContain('ws://localhost:*')
        ->and($csp)->toContain('ws://127.0.0.1:*');
});
