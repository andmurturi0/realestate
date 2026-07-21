<?php

use App\Models\User;

// Faza 10 §3: guests must never see dashboard.* route names/URIs in the
// page source — only policies should stand between a route and its data,
// but the route map itself shouldn't be handed out for free either.

test('a guest does not receive any dashboard route in the page source', function () {
    $response = $this->get('/properties');

    $response->assertSuccessful();
    $response->assertDontSee('dashboard.properties.index', false);
    $response->assertDontSee('dashboard.settings.edit', false);
    $response->assertDontSee('"dashboard"', false);
});

test('a guest still receives the public routes it needs', function () {
    $response = $this->get('/properties');

    $response->assertSuccessful();
    $response->assertSee('"properties.index"', false);
    $response->assertSee('"login"', false);
});

test('an authenticated agent receives dashboard routes', function () {
    $agent = User::factory()->agent()->create();

    $response = $this->actingAs($agent)->get('/dashboard');

    $response->assertSuccessful();
    $response->assertSee('"dashboard.properties.index"', false);
});
