<?php

use App\Enums\OfferStatus;
use App\Models\Location;
use App\Models\Property;
use App\Models\PropertyOffer;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function conversionPayload(array $overrides = []): array
{
    return array_merge([
        'listing_type' => 'sale',
        'is_exclusive' => false,
        'category' => 'apartment',
        'status' => 'draft',
        'title' => ['sq' => 'Banesë në Prishtinë', 'en' => '', 'de' => ''],
        'description' => ['sq' => 'Përshkrim testues i pronës.', 'en' => '', 'de' => ''],
        'price' => 95000,
        'price_negotiable' => false,
        'surface_m2' => 75,
        'location_id' => Location::factory()->create()->id,
        'address_line' => null,
        'lat' => 42.6629,
        'lng' => 21.1655,
        'features' => [],
    ], $overrides);
}

test('the prefill form shows offer data without writing anything', function () {
    $agent = User::factory()->agent()->create();
    $location = Location::factory()->create();
    $offer = PropertyOffer::factory()->create([
        'assigned_to' => $agent->id,
        'category' => 'apartment',
        'listing_type' => 'sale',
        'location_id' => $location->id,
        'surface_m2' => 80,
        'asking_price' => 9000000,
    ]);

    $this->actingAs($agent)
        ->get(route('dashboard.inbox.offers.convert.create', $offer))
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard/inbox/ConvertOffer')
            ->where('property.listing_type', 'sale')
            ->where('property.category', 'apartment')
            ->where('property.surface_m2', fn ($value) => (float) $value === 80.0)
            ->where('property.price_euros', fn ($value) => (float) $value === 90000.0)
            ->where('property.location_id', $location->id));

    $offer->refresh();

    expect($offer->status)->toBe(OfferStatus::New)
        ->and($offer->converted_property_id)->toBeNull()
        ->and(Property::query()->count())->toBe(0);
});

test('abandoning the conversion form leaves the offer untouched', function () {
    $agent = User::factory()->agent()->create();
    $offer = PropertyOffer::factory()->create(['assigned_to' => $agent->id]);

    // Visiting the GET page (and never posting) must never write anything.
    $this->actingAs($agent)->get(route('dashboard.inbox.offers.convert.create', $offer))->assertSuccessful();
    $this->actingAs($agent)->get(route('dashboard.inbox.offers.convert.create', $offer))->assertSuccessful();

    $offer->refresh();

    expect($offer->status)->toBe(OfferStatus::New)
        ->and($offer->converted_property_id)->toBeNull()
        ->and(Property::query()->count())->toBe(0);
});

test('converting an offer creates the property and links both records atomically', function () {
    $agent = User::factory()->agent()->create();
    $offer = PropertyOffer::factory()->create(['assigned_to' => $agent->id, 'asking_price' => 9000000]);

    $this->actingAs($agent)
        ->post(route('dashboard.inbox.offers.convert.store', $offer), conversionPayload(['price' => 98000]))
        ->assertRedirect();

    $property = Property::query()->latest('id')->firstOrFail();
    $offer->refresh();

    expect($property->price)->toBe(9800000)
        ->and($property->agent_id)->toBe($agent->id)
        ->and($offer->status)->toBe(OfferStatus::Converted)
        ->and($offer->converted_property_id)->toBe($property->id);
});

test('the redirect after conversion goes to the new property’s edit page', function () {
    $agent = User::factory()->agent()->create();
    $offer = PropertyOffer::factory()->create(['assigned_to' => $agent->id]);

    $response = $this->actingAs($agent)->post(route('dashboard.inbox.offers.convert.store', $offer), conversionPayload());

    $property = Property::query()->latest('id')->firstOrFail();

    $response->assertRedirect(route('dashboard.properties.edit', $property));
});

test('the converting agent owns the property even if a tampered agent_id is submitted', function () {
    $admin = User::factory()->admin()->create();
    $otherAgent = User::factory()->agent()->create();
    $offer = PropertyOffer::factory()->create(['assigned_to' => null]);

    $this->actingAs($admin)->post(
        route('dashboard.inbox.offers.convert.store', $offer),
        conversionPayload(['agent_id' => $otherAgent->id])
    )->assertRedirect();

    $property = Property::query()->latest('id')->firstOrFail();

    expect($property->agent_id)->toBe($admin->id);
});

test('an agent cannot convert an offer assigned to someone else', function () {
    $agent = User::factory()->agent()->create();
    $other = User::factory()->agent()->create();
    $offer = PropertyOffer::factory()->create(['assigned_to' => $other->id]);

    $this->actingAs($agent)
        ->get(route('dashboard.inbox.offers.convert.create', $offer))
        ->assertForbidden();

    $this->actingAs($agent)
        ->post(route('dashboard.inbox.offers.convert.store', $offer), conversionPayload())
        ->assertForbidden();

    expect(Property::query()->count())->toBe(0);
});
