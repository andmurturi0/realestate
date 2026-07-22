<?php

use App\Enums\PropertyCategory;
use App\Models\ContactMessage;
use App\Models\Location;
use App\Models\Property;
use App\Models\PropertyPriceHistory;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

// Dukshmëria — 404 vetëm sipas PropertyPolicy::viewPublic

test('a guest sees a published property', function () {
    $property = Property::factory()->published()->create();

    $this->get(route('properties.show', $property))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/Show')
            ->where('property.id', $property->id));
});

test('a guest gets a 404 for a draft property', function () {
    $property = Property::factory()->draft()->create();

    $this->get(route('properties.show', $property))->assertNotFound();
});

test('a guest sees a published property under a locale prefix', function (string $locale) {
    $property = Property::factory()->published()->create();

    $this->get("/{$locale}/properties/{$property->slug}")
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/Show')
            ->where('property.id', $property->id));
})->with(['en', 'de']);

test('a guest gets a 404 for a draft property under a locale prefix', function (string $locale) {
    $property = Property::factory()->draft()->create();

    $this->get("/{$locale}/properties/{$property->slug}")->assertNotFound();
})->with(['en', 'de']);

test('the owning agent sees their own draft property', function () {
    $agent = User::factory()->agent()->create();
    $property = Property::factory()->draft()->for($agent, 'agent')->create();

    $this->actingAs($agent)
        ->get(route('properties.show', $property))
        ->assertSuccessful();
});

test('a different agent gets a 404 for another agent\'s draft', function () {
    $agent = User::factory()->agent()->create();
    $property = Property::factory()->draft()->create();

    $this->actingAs($agent)
        ->get(route('properties.show', $property))
        ->assertNotFound();
});

test('an admin sees any draft property', function () {
    $admin = User::factory()->admin()->create();
    $property = Property::factory()->draft()->create();

    $this->actingAs($admin)
        ->get(route('properties.show', $property))
        ->assertSuccessful();
});

// Mesazhi i kontaktit — caktimi automatik te agjenti i pronës

test('a contact message for a property auto-assigns to that property\'s agent', function () {
    $agent = User::factory()->agent()->create();
    $property = Property::factory()->published()->for($agent, 'agent')->create();

    $this->post(route('properties.contact', $property), [
        'full_name' => 'Agim Krasniqi',
        'phone' => '+38344123456',
        'message' => 'Më intereson kjo pronë, a mund të caktojmë një vizitë?',
    ])->assertSuccessful();

    $message = ContactMessage::query()->where('property_id', $property->id)->firstOrFail();

    expect($message->assigned_to)->toBe($agent->id)
        ->and($message->full_name)->toBe('Agim Krasniqi')
        ->and($message->status->value)->toBe('new');
});

test('the honeypot silently drops the submission without storing anything', function () {
    $property = Property::factory()->published()->create();

    $this->post(route('properties.contact', $property), [
        'full_name' => 'Bot',
        'phone' => '+38344123456',
        'message' => 'spam',
        'website' => 'https://spam.example',
    ])->assertSuccessful();

    expect(ContactMessage::query()->count())->toBe(0);
});

test('a fourth contact message within an hour is rate limited', function () {
    $property = Property::factory()->published()->create();

    $payload = [
        'full_name' => 'Agim Krasniqi',
        'phone' => '+38344123456',
        'message' => 'Më intereson kjo pronë.',
    ];

    $this->post(route('properties.contact', $property), $payload)->assertSuccessful();
    $this->post(route('properties.contact', $property), $payload)->assertSuccessful();
    $this->post(route('properties.contact', $property), $payload)->assertSuccessful();
    $this->post(route('properties.contact', $property), $payload)->assertStatus(429);

    expect(ContactMessage::query()->count())->toBe(3);
});

test('an invalid phone number is rejected', function () {
    $property = Property::factory()->published()->create();

    $this->postJson(route('properties.contact', $property), [
        'full_name' => 'Agim Krasniqi',
        'phone' => '044123',
        'message' => 'Më intereson kjo pronë.',
    ])->assertJsonValidationErrors('phone');
});

test('valid international phone numbers are accepted and stored in E.164', function (string $phone) {
    $property = Property::factory()->published()->create();

    $this->post(route('properties.contact', $property), [
        'full_name' => 'Agim Krasniqi',
        'phone' => $phone,
        'message' => 'Më intereson kjo pronë.',
    ])->assertSuccessful();

    expect(ContactMessage::query()->where('property_id', $property->id)->firstOrFail()->phone)->toBe($phone);
})->with([
    'Kosovo' => '+38344123456',
    'Germany' => '+491701234567',
    'Albania' => '+355692045678',
]);

// Prona të ngjashme

test('similar properties match same category, municipality and price band', function () {
    $municipality = Location::factory()->create();
    $neighborhood = Location::factory()->neighborhood($municipality)->create();

    $property = Property::factory()->published()->create([
        'category' => PropertyCategory::Apartment,
        'location_id' => $neighborhood->id,
        'price' => 10_000_000,
    ]);

    // Exactly 3 strict matches, so no category-only relaxation kicks in.
    $matchA = Property::factory()->published()->create([
        'category' => PropertyCategory::Apartment,
        'location_id' => $neighborhood->id,
        'price' => 11_000_000,
    ]);
    $matchB = Property::factory()->published()->create([
        'category' => PropertyCategory::Apartment,
        'location_id' => $municipality->id,
        'price' => 9_000_000,
    ]);
    $matchC = Property::factory()->published()->create([
        'category' => PropertyCategory::Apartment,
        'location_id' => $neighborhood->id,
        'price' => 12_000_000,
    ]);

    // Excluded: wrong category, wrong municipality, price out of band.
    Property::factory()->published()->create([
        'category' => PropertyCategory::House,
        'location_id' => $neighborhood->id,
        'price' => 10_000_000,
    ]);
    $otherMunicipality = Location::factory()->create();
    Property::factory()->published()->create([
        'category' => PropertyCategory::Apartment,
        'location_id' => $otherMunicipality->id,
        'price' => 10_000_000,
    ]);
    Property::factory()->published()->create([
        'category' => PropertyCategory::Apartment,
        'location_id' => $neighborhood->id,
        'price' => 20_000_000,
    ]);

    $response = $this->get(route('properties.show', $property))->assertSuccessful();

    $similarIds = array_column($response->viewData('page')['props']['similarProperties'], 'id');

    expect($similarIds)->toHaveCount(3)
        ->and($similarIds)->toEqualCanonicalizing([$matchA->id, $matchB->id, $matchC->id]);
});

test('similar properties relax to category-only when fewer than 3 strict matches exist', function () {
    $municipality = Location::factory()->create();

    $property = Property::factory()->published()->create([
        'category' => PropertyCategory::Land,
        'location_id' => $municipality->id,
        'price' => 10_000_000,
    ]);

    // Same category, different municipality and price — would fail the strict match.
    $fallbackMatches = Property::factory()->published()->count(3)->create([
        'category' => PropertyCategory::Land,
        'price' => 90_000_000,
    ]);

    $this->get(route('properties.show', $property))
        ->assertInertia(fn (Assert $page) => $page->has('similarProperties', 3));
});

// Historiku i çmimit

test('the price history is hidden with fewer than two rows', function () {
    $property = Property::factory()->published()->create();
    PropertyPriceHistory::factory()->for($property)->create();

    $this->get(route('properties.show', $property))
        ->assertInertia(fn (Assert $page) => $page->where('property.price_history', []));
});

test('the price history is included with two or more rows', function () {
    $property = Property::factory()->published()->create();
    PropertyPriceHistory::factory()->for($property)->count(2)->create();

    $this->get(route('properties.show', $property))
        ->assertInertia(fn (Assert $page) => $page->has('property.price_history', 2));
});

// views_count — një herë për seancë

test('views_count increments once per session, not on every request', function () {
    $property = Property::factory()->published()->create(['views_count' => 0]);

    $this->get(route('properties.show', $property))->assertSuccessful();
    $this->get(route('properties.show', $property))->assertSuccessful();

    expect($property->fresh()->views_count)->toBe(1);
});
