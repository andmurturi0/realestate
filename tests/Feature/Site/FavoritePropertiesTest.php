<?php

use App\Enums\PropertyStatus;
use App\Models\Property;

test('returns only the requested published properties', function () {
    $match = Property::factory()->published()->create();
    Property::factory()->published()->create(); // not requested

    $response = $this->getJson('/favorites/properties?'.http_build_query(['ids' => [$match->id]]))
        ->assertSuccessful();

    $ids = array_column($response->json('data'), 'id');

    expect($ids)->toBe([$match->id]);
});

test('ignores unpublished properties even when their id is requested', function (PropertyStatus $status) {
    $property = Property::factory()->create(['status' => $status, 'published_at' => now()]);

    $response = $this->getJson('/favorites/properties?'.http_build_query(['ids' => [$property->id]]))
        ->assertSuccessful();

    expect($response->json('data'))->toBe([]);
})->with([
    'draft' => PropertyStatus::Draft,
    'reserved' => PropertyStatus::Reserved,
    'sold' => PropertyStatus::Sold,
    'rented' => PropertyStatus::Rented,
    'archived' => PropertyStatus::Archived,
]);

test('ignores ids that do not exist', function () {
    $match = Property::factory()->published()->create();
    $missingId = $match->id + 999;

    $response = $this->getJson('/favorites/properties?'.http_build_query(['ids' => [$match->id, $missingId]]))
        ->assertSuccessful();

    $ids = array_column($response->json('data'), 'id');

    expect($ids)->toBe([$match->id]);
});

test('returns an empty list when no ids are given', function () {
    Property::factory()->published()->create();

    $this->getJson('/favorites/properties')
        ->assertSuccessful()
        ->assertJson(['data' => []]);
});

test('the favorites page renders', function () {
    $this->get('/favorites')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('favorites/Index'));
});
