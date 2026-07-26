<?php

use App\Models\Property;

// Faza 10 §10/§13: sitemap covers every public page, in every locale,
// including /about, /offer-property, /create-request alongside listing and
// detail pages.

test('the sitemap is valid XML with the correct content type', function () {
    $response = $this->get('/sitemap.xml');

    $response->assertSuccessful();
    $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

    expect(simplexml_load_string($response->content()))->not->toBeFalse();
});

test('the sitemap includes every static public page in all three locales', function () {
    $response = $this->get('/sitemap.xml');
    $xml = $response->content();

    foreach (['/', '/properties', '/about', '/terms', '/privacy', '/offer-property', '/create-request'] as $path) {
        expect($xml)->toContain('<loc>'.url($path).'</loc>');
    }

    foreach (['/en', '/de'] as $prefix) {
        foreach (['/properties', '/about', '/terms', '/privacy', '/offer-property', '/create-request'] as $path) {
            expect($xml)->toContain('href="'.url($prefix.$path).'"');
        }
    }
});

test('the sitemap includes published properties but not drafts', function () {
    $published = Property::factory()->published()->create(['slug' => 'published-property']);
    $draft = Property::factory()->create(['status' => 'draft', 'slug' => 'draft-property']);

    $xml = $this->get('/sitemap.xml')->content();

    expect($xml)->toContain(route('properties.show', $published))
        ->and($xml)->not->toContain(route('properties.show', $draft));
});

test('the sitemap response is cached', function () {
    Property::factory()->published()->create(['slug' => 'first']);
    $this->get('/sitemap.xml');

    Property::factory()->published()->create(['slug' => 'second']);
    $xml = $this->get('/sitemap.xml')->content();

    expect($xml)->not->toContain('second');
});

test('robots.txt points to the sitemap', function () {
    $response = $this->get('/robots.txt');

    $response->assertSuccessful();
    $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    $response->assertSee('Sitemap: '.url('/sitemap.xml'), false);
});

test('robots.txt disallows the dashboard and login', function () {
    $response = $this->get('/robots.txt');

    $response->assertSee('Disallow: /dashboard', false);
    $response->assertSee('Disallow: /login', false);
});

test('public pages stay indexable', function () {
    foreach (['/', '/properties', '/about', '/terms', '/privacy'] as $path) {
        $this->get($path)->assertDontSee('<meta name="robots"', false);
    }
});
