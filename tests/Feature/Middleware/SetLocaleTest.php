<?php

use App\Models\Property;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

// Prefiksi i URL-së për gjuhën: sq pa prefiks (parazgjedhje), en/de me prefiks.
// FAZAT.md Faza 8 — /properties (sq), /en/properties, /de/properties.

test('an unprefixed public route resolves to sq by default', function () {
    $this->get('/properties')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'sq'));
});

test('the /en prefix resolves the locale to en on the same page', function () {
    $this->get('/en/properties')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/Index')
            ->where('locale', 'en'));
});

test('the /de prefix resolves the locale to de on the same page', function () {
    $this->get('/de/properties')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/Index')
            ->where('locale', 'de'));
});

test('an unsupported locale prefix 404s', function () {
    $this->get('/fr/properties')->assertNotFound();
});

test('a locale cookie is honoured on an unprefixed request', function () {
    $this->withUnencryptedCookie('locale', 'en')
        ->get('/properties')
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'en'));
});

// Regression (Faza 10 §11): LanguageSwitcher.vue writes the cookie as plain
// JS text (document.cookie = 'locale=en'), never encrypted. withCookie()
// auto-encrypts on the way out (masking this bug), so this test uses
// withUnencryptedCookie() to send the raw value exactly as a real browser
// would. Before `locale` was added to encryptCookies' except list,
// EncryptCookies failed to decrypt it and silently nulled it out.
test('a plain-text, unencrypted locale cookie (as the browser actually writes it) is honoured', function () {
    $this->withUnencryptedCookie('locale', 'de')
        ->get('/properties')
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'de'));
});

test('an invalid locale cookie falls back to sq', function () {
    $this->withUnencryptedCookie('locale', 'fr')
        ->get('/properties')
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'sq'));
});

test('visiting a locale-prefixed page persists the choice via cookie for the next visit', function () {
    // $encrypted = false: the locale cookie is deliberately excluded from
    // EncryptCookies (Faza 10 §11), so the Set-Cookie value is plain text.
    $this->get('/en/properties')
        ->assertCookie('locale', 'en', false);
});

test('a property title without an en translation falls back to sq under /en', function () {
    $property = Property::factory()->published()->create(['title' => ['sq' => 'Banesë vetëm në shqip']]);

    $this->get('/en/properties')
        ->assertInertia(fn (Assert $page) => $page
            ->where('properties.data.0.title', 'Banesë vetëm në shqip'));
});

test('the dashboard always renders in sq regardless of the locale cookie', function () {
    $agent = User::factory()->agent()->create();

    $this->withCookie('locale', 'en')
        ->actingAs($agent)
        ->get('/dashboard')
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'sq'));
});

test('hreflang alternate tags are present on a public page', function () {
    $response = $this->get('/properties');

    $response->assertSuccessful();
    $response->assertSee('hreflang="sq"', false);
    $response->assertSee('hreflang="en"', false);
    $response->assertSee('hreflang="de"', false);
    $response->assertSee('hreflang="x-default"', false);
});

test('hreflang tags are absent on non-public-listing routes like the dashboard', function () {
    $agent = User::factory()->agent()->create();

    $this->actingAs($agent)
        ->get('/dashboard')
        ->assertDontSee('hreflang=', false);
});

// Regression: a literal "@endphp" inside a Blade comment made storePhpBlocks()
// swallow everything from the first @php to that comment as one unprocessed
// raw block, so the <head> leaked raw Blade source as visible page text.
// hreflang assertions above didn't catch this — they only check the tags
// exist somewhere in the body, and those tags sit after the corrupted span.
test('the compiled blade template does not leak raw directives as visible text', function () {
    $response = $this->get('/properties');

    $response->assertSuccessful();
    $response->assertDontSee('{{ $branding', false);
    $response->assertDontSee('@if (', false);
    $response->assertDontSee('@endif', false);
    $response->assertDontSee('<?php', false);
    $response->assertDontSee('@php', false);
});

// Regression: no lang/xx/pagination.php is published, so Laravel's paginator
// falls back to the raw translation key itself, not literal "Previous"/"Next"
// text (see LengthAwarePaginator::previous/nextPageUrl). The frontend's
// paginationLabel() helper depends on this exact key format to translate it.
test('unpaginated pagination links use the raw translation key, not literal English', function () {
    Property::factory()->count(13)->published()->create();

    $response = $this->get('/properties');

    $response->assertInertia(fn (Assert $page) => $page
        ->where('properties.links.0.label', 'pagination.previous')
        ->where('properties.links.3.label', 'pagination.next'));
});
