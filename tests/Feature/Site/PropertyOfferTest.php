<?php

use App\Enums\ListingType;
use App\Enums\PropertyCategory;
use App\Models\Location;
use App\Models\PropertyOffer;
use App\Models\User;
use App\Notifications\NewLeadReceived;
use Illuminate\Support\Facades\Notification;

function validOfferPayload(Location $location): array
{
    return [
        'first_name' => 'Agim',
        'last_name' => 'Krasniqi',
        'phone' => '+38344123456',
        'listing_type' => ListingType::Sale->value,
        'category' => PropertyCategory::Apartment->value,
        'location_id' => $location->id,
        'surface_m2' => 75,
        'price' => 95000,
        // Far enough in the past to clear the minimum time-on-form check.
        'form_rendered_at' => (int) (now()->valueOf() - 5000),
    ];
}

test('a valid offer submission is stored correctly', function () {
    $location = Location::factory()->create();

    $this->post(route('offer-property.store'), validOfferPayload($location))->assertSuccessful();

    $offer = PropertyOffer::query()->firstOrFail();

    expect($offer->first_name)->toBe('Agim')
        ->and($offer->last_name)->toBe('Krasniqi')
        ->and($offer->status->value)->toBe('new')
        ->and($offer->assigned_to)->toBeNull()
        ->and($offer->asking_price)->toBe(9500000);
});

test('an invalid phone number is rejected', function () {
    $location = Location::factory()->create();

    $this->postJson(route('offer-property.store'), [
        ...validOfferPayload($location),
        'phone' => '044123',
    ])->assertJsonValidationErrors('phone');

    expect(PropertyOffer::query()->count())->toBe(0);
});

test('a fourth offer within an hour is rate limited', function () {
    $location = Location::factory()->create();
    $payload = validOfferPayload($location);

    $this->post(route('offer-property.store'), $payload)->assertSuccessful();
    $this->post(route('offer-property.store'), $payload)->assertSuccessful();
    $this->post(route('offer-property.store'), $payload)->assertSuccessful();
    $this->post(route('offer-property.store'), $payload)->assertStatus(429);

    expect(PropertyOffer::query()->count())->toBe(3);
});

test('the honeypot silently drops the submission without storing anything', function () {
    $location = Location::factory()->create();

    $this->post(route('offer-property.store'), [
        ...validOfferPayload($location),
        'website' => 'https://spam.example',
    ])->assertSuccessful();

    expect(PropertyOffer::query()->count())->toBe(0);
});

test('a submission faster than the minimum time on form is silently dropped', function () {
    $location = Location::factory()->create();

    $this->post(route('offer-property.store'), [
        ...validOfferPayload($location),
        'form_rendered_at' => (int) now()->valueOf(),
    ])->assertSuccessful();

    expect(PropertyOffer::query()->count())->toBe(0);
});

test('admins are notified of a new offer', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    User::factory()->agent()->create();
    $location = Location::factory()->create();

    $this->post(route('offer-property.store'), validOfferPayload($location))->assertSuccessful();

    Notification::assertSentTo($admin, NewLeadReceived::class);
    Notification::assertCount(1);
});
