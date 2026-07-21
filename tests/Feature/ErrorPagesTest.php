<?php

use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

// Faza 10 §10: designed 404/500 pages instead of Laravel's bare defaults.
// Gated on `! app()->hasDebugModeEnabled()` (phpunit.xml forces APP_DEBUG=false)
// and `! $request->expectsJson()`, so JSON/XHR callers keep their normal
// error payload and local `php artisan serve` (APP_DEBUG=true) still shows
// the real trace.

test('a nonexistent route renders the designed 404 page', function () {
    $response = $this->get('/this-route-does-not-exist');

    $response->assertNotFound();
    $response->assertInertia(fn (Assert $page) => $page->component('errors/Error')->where('status', 404));
});

test('an unpublished property viewed by a guest renders the designed 404 page', function () {
    $property = Property::factory()->create(['status' => 'draft']);

    $response = $this->get(route('properties.show', $property));

    $response->assertNotFound();
    $response->assertInertia(fn (Assert $page) => $page->component('errors/Error')->where('status', 404));
});

test('a forbidden dashboard action renders the designed 403 page', function () {
    $agent = User::factory()->agent()->create();
    $otherAgentsProperty = Property::factory()->for(User::factory()->agent(), 'agent')->create();

    $response = $this->actingAs($agent)->get(route('dashboard.properties.edit', $otherAgentsProperty));

    $response->assertForbidden();
    $response->assertInertia(fn (Assert $page) => $page->component('errors/Error')->where('status', 403));
});

test('a server error renders the designed 500 page', function () {
    Route::middleware('web')->get('/__test-server-error', fn () => throw new RuntimeException('boom'));

    $response = $this->get('/__test-server-error');

    $response->assertStatus(500);
    $response->assertInertia(fn (Assert $page) => $page->component('errors/Error')->where('status', 500));
});

test('a JSON request does not receive the Inertia error page on 403', function () {
    $agent = User::factory()->agent()->create();
    $otherAgentsProperty = Property::factory()->for(User::factory()->agent(), 'agent')->create();

    $response = $this->actingAs($agent)->getJson(route('dashboard.properties.edit', $otherAgentsProperty));

    $response->assertForbidden();
    $response->assertHeader('Content-Type', 'application/json');
});
