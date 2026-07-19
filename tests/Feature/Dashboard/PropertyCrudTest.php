<?php

use App\Enums\PropertyCategory;
use App\Models\Feature;
use App\Models\Location;
use App\Models\Property;
use App\Models\PropertyPriceHistory;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validPropertyPayload(array $overrides = []): array
{
    return array_merge([
        'listing_type' => 'sale',
        'is_exclusive' => false,
        'category' => 'apartment',
        'status' => 'draft',
        'title' => ['sq' => 'Banesë testuese 75 m²', 'en' => '', 'de' => ''],
        'description' => ['sq' => 'Përshkrim testues i pronës.', 'en' => '', 'de' => ''],
        'price' => 114000,
        'price_negotiable' => false,
        'surface_m2' => 75,
        'bedrooms' => 2,
        'bathrooms' => 1,
        'floor' => 3,
        'total_floors' => 8,
        'year_built' => 2015,
        'parking_spaces' => 1,
        'location_id' => Location::factory()->create()->id,
        'address_line' => 'Rr. e Testit',
        'lat' => 42.6629,
        'lng' => 21.1655,
        'has_possession_sheet' => true,
        'document_type' => 'notary',
        'features' => [],
    ], $overrides);
}

// Lista

test('guests are redirected to login from the property list', function () {
    $this->get('/dashboard/properties')->assertRedirect('/login');
});

test('an agent sees every property but edit rights only on their own', function () {
    $agent = User::factory()->agent()->create();
    $mine = Property::factory()->for($agent, 'agent')->create();
    $theirs = Property::factory()->create();

    $this->actingAs($agent)
        ->get('/dashboard/properties')
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard/properties/Index')
            ->has('properties.data', 2)
            ->where('agents', null)
            ->where(
                'properties.data',
                fn ($rows) => collect($rows)->every(fn ($row) => match ($row['id']) {
                    $mine->id => $row['can']['update'] === true && $row['can']['delete'] === true,
                    $theirs->id => $row['can']['update'] === false && $row['can']['delete'] === false,
                })
            ));
});

test('the list can be searched by reference code', function () {
    $agent = User::factory()->agent()->create();
    $property = Property::factory()->create();
    Property::factory()->count(2)->create();

    $this->actingAs($agent)
        ->get('/dashboard/properties?search='.$property->reference_code)
        ->assertInertia(fn (Assert $page) => $page
            ->has('properties.data', 1)
            ->where('properties.data.0.id', $property->id));
});

test('the list can be filtered by status and category', function () {
    $agent = User::factory()->agent()->create();
    $draft = Property::factory()->draft()->create(['category' => PropertyCategory::House]);
    Property::factory()->published()->create(['category' => PropertyCategory::House]);
    Property::factory()->draft()->create(['category' => PropertyCategory::Land]);

    $this->actingAs($agent)
        ->get('/dashboard/properties?status=draft&category=house')
        ->assertInertia(fn (Assert $page) => $page
            ->has('properties.data', 1)
            ->where('properties.data.0.id', $draft->id));
});

test('the agent filter works for admins and is ignored for agents', function () {
    $admin = User::factory()->admin()->create();
    $agent = User::factory()->agent()->create();
    $mine = Property::factory()->for($agent, 'agent')->create();
    Property::factory()->create();

    $this->actingAs($admin)
        ->get('/dashboard/properties?agent='.$agent->id)
        ->assertInertia(fn (Assert $page) => $page
            ->has('properties.data', 1)
            ->where('properties.data.0.id', $mine->id));

    $this->actingAs($agent)
        ->get('/dashboard/properties?agent='.$admin->id)
        ->assertInertia(fn (Assert $page) => $page->has('properties.data', 2));
});

// Krijimi

test('an agent can create a property and the price is stored in cents', function () {
    $agent = User::factory()->agent()->create();
    $features = Feature::factory()->count(2)->create();

    $this->actingAs($agent)
        ->post('/dashboard/properties', validPropertyPayload([
            'price' => 114000,
            'features' => $features->pluck('id')->all(),
        ]))
        ->assertRedirect('/dashboard/properties');

    $property = Property::latest('id')->first();

    expect($property->price)->toBe(11400000)
        ->and($property->agent_id)->toBe($agent->id)
        ->and($property->reference_code)->toMatch('/^PRO-\d{4,}$/')
        ->and($property->published_at)->toBeNull()
        ->and($property->features()->pluck('features.id')->all())
        ->toEqualCanonicalizing($features->pluck('id')->all());
});

test('decimal euro prices convert to exact cents', function () {
    $agent = User::factory()->agent()->create();

    $this->actingAs($agent)->post('/dashboard/properties', validPropertyPayload(['price' => 1234.56]));

    expect(Property::latest('id')->first()->price)->toBe(123456);
});

test('agent_id is auto-assigned and a forged agent_id in the payload is ignored', function () {
    $agent = User::factory()->agent()->create();
    $victim = User::factory()->agent()->create();

    $this->actingAs($agent)
        ->post('/dashboard/properties', validPropertyPayload(['agent_id' => $victim->id]))
        ->assertRedirect('/dashboard/properties')
        ->assertSessionDoesntHaveErrors();

    expect(Property::latest('id')->first()->agent_id)->toBe($agent->id);
});

test('an admin can assign the property to another agent on create', function () {
    $admin = User::factory()->admin()->create();
    $agent = User::factory()->agent()->create();

    $this->actingAs($admin)->post('/dashboard/properties', validPropertyPayload(['agent_id' => $agent->id]));

    expect(Property::latest('id')->first()->agent_id)->toBe($agent->id);
});

test('creating with status published sets published_at', function () {
    $agent = User::factory()->agent()->create();

    $this->actingAs($agent)->post('/dashboard/properties', validPropertyPayload(['status' => 'published']));

    expect(Property::latest('id')->first()->published_at)->not->toBeNull();
});

test('the albanian title is required', function () {
    $agent = User::factory()->agent()->create();

    $this->actingAs($agent)
        ->from('/dashboard/properties/create')
        ->post('/dashboard/properties', validPropertyPayload([
            'title' => ['sq' => '', 'en' => 'Apartment 75 m²', 'de' => ''],
        ]))
        ->assertSessionHasErrors('title.sq');

    expect(Property::count())->toBe(0);
});

// Fushat e varura nga kategoria

test('land rejects bedrooms, bathrooms and floor instead of silently accepting them', function () {
    $agent = User::factory()->agent()->create();

    $this->actingAs($agent)
        ->post('/dashboard/properties', validPropertyPayload([
            'category' => 'land',
            'bedrooms' => 3,
            'bathrooms' => 2,
            'floor' => 1,
        ]))
        ->assertSessionHasErrors(['bedrooms', 'bathrooms', 'floor']);

    expect(Property::count())->toBe(0);
});

test('an object rejects bedrooms', function () {
    $agent = User::factory()->agent()->create();

    $this->actingAs($agent)
        ->post('/dashboard/properties', validPropertyPayload([
            'category' => 'object',
            'bedrooms' => 2,
            'bathrooms' => null,
            'floor' => null,
        ]))
        ->assertSessionHasErrors('bedrooms');
});

test('land without room fields is accepted', function () {
    $agent = User::factory()->agent()->create();

    $this->actingAs($agent)
        ->post('/dashboard/properties', validPropertyPayload([
            'category' => 'land',
            'bedrooms' => null,
            'bathrooms' => null,
            'floor' => null,
            'total_floors' => null,
            'year_built' => null,
            'parking_spaces' => null,
            'land_surface_m2' => 500,
        ]))
        ->assertRedirect('/dashboard/properties')
        ->assertSessionDoesntHaveErrors();

    expect(Property::latest('id')->first()->land_surface_m2)->toBe(500.0);
});

test('switching the category to land clears the stale room fields', function () {
    $agent = User::factory()->agent()->create();
    $property = Property::factory()->for($agent, 'agent')->create([
        'category' => PropertyCategory::Apartment,
        'bedrooms' => 3,
        'bathrooms' => 2,
        'floor' => 4,
    ]);

    $this->actingAs($agent)
        ->put("/dashboard/properties/{$property->id}", validPropertyPayload([
            'category' => 'land',
            'bedrooms' => null,
            'bathrooms' => null,
            'floor' => null,
            'total_floors' => null,
            'year_built' => null,
            'parking_spaces' => null,
        ]))
        ->assertSessionDoesntHaveErrors();

    $property->refresh();

    expect($property->bedrooms)->toBeNull()
        ->and($property->bathrooms)->toBeNull()
        ->and($property->floor)->toBeNull();
});

// Përditësimi

test('an agent can update their own property and a price change is recorded in the history', function () {
    $agent = User::factory()->agent()->create();
    $property = Property::factory()->for($agent, 'agent')->create(['price' => 11400000]);

    $this->actingAs($agent)
        ->put("/dashboard/properties/{$property->id}", validPropertyPayload(['price' => 120000]))
        ->assertRedirect("/dashboard/properties/{$property->id}/edit");

    expect($property->refresh()->price)->toBe(12000000);

    $history = PropertyPriceHistory::where('property_id', $property->id)->latest('id')->first();

    expect($history)->not->toBeNull()
        ->and($history->old_price)->toBe(11400000)
        ->and($history->new_price)->toBe(12000000)
        ->and($history->changed_by)->toBe($agent->id);
});

test('a german title is saved and returned correctly', function () {
    $agent = User::factory()->agent()->create();
    $property = Property::factory()->for($agent, 'agent')->create();

    $this->actingAs($agent)->put("/dashboard/properties/{$property->id}", validPropertyPayload([
        'title' => ['sq' => 'Banesë në Dardani', 'en' => '', 'de' => 'Wohnung in Dardania'],
    ]));

    expect($property->refresh()->getTranslation('title', 'de'))->toBe('Wohnung in Dardania');
});

test('publishing via update sets published_at once and keeps the original timestamp', function () {
    $agent = User::factory()->agent()->create();
    $property = Property::factory()->for($agent, 'agent')->draft()->create();

    $this->actingAs($agent)->put("/dashboard/properties/{$property->id}", validPropertyPayload(['status' => 'published']));

    $publishedAt = $property->refresh()->published_at;
    expect($publishedAt)->not->toBeNull();

    $this->travel(3)->days();

    $this->actingAs($agent)->put("/dashboard/properties/{$property->id}", validPropertyPayload(['status' => 'published']));

    expect($property->refresh()->published_at->timestamp)->toBe($publishedAt->timestamp);
});

test('an agent cannot reassign ownership by sending agent_id on update', function () {
    $agent = User::factory()->agent()->create();
    $victim = User::factory()->agent()->create();
    $property = Property::factory()->for($agent, 'agent')->create();

    $this->actingAs($agent)
        ->put("/dashboard/properties/{$property->id}", validPropertyPayload(['agent_id' => $victim->id]))
        ->assertSessionDoesntHaveErrors();

    expect($property->refresh()->agent_id)->toBe($agent->id);
});

test('an admin can reassign a property to another agent on update', function () {
    $admin = User::factory()->admin()->create();
    $agent = User::factory()->agent()->create();
    $property = Property::factory()->create();

    $this->actingAs($admin)->put("/dashboard/properties/{$property->id}", validPropertyPayload(['agent_id' => $agent->id]));

    expect($property->refresh()->agent_id)->toBe($agent->id);
});

// Autorizimi

test('an agent cannot open the edit page of another agent\'s property', function () {
    $agent = User::factory()->agent()->create();
    $property = Property::factory()->create();

    $this->actingAs($agent)->get("/dashboard/properties/{$property->id}/edit")->assertForbidden();
});

test('an agent cannot update another agent\'s property', function () {
    $agent = User::factory()->agent()->create();
    $property = Property::factory()->create(['price' => 11400000]);

    $this->actingAs($agent)
        ->put("/dashboard/properties/{$property->id}", validPropertyPayload(['price' => 1]))
        ->assertForbidden();

    expect($property->refresh()->price)->toBe(11400000);
});

test('an agent cannot delete another agent\'s property', function () {
    $agent = User::factory()->agent()->create();
    $property = Property::factory()->create();

    $this->actingAs($agent)->delete("/dashboard/properties/{$property->id}")->assertForbidden();

    expect($property->refresh()->trashed())->toBeFalse();
});

test('an admin can update any property', function () {
    $admin = User::factory()->admin()->create();
    $property = Property::factory()->create();

    $this->actingAs($admin)
        ->put("/dashboard/properties/{$property->id}", validPropertyPayload())
        ->assertSessionDoesntHaveErrors()
        ->assertRedirect("/dashboard/properties/{$property->id}/edit");
});

// Fshirja

test('an agent can soft delete their own property', function () {
    $agent = User::factory()->agent()->create();
    $property = Property::factory()->for($agent, 'agent')->create();

    $this->actingAs($agent)
        ->delete("/dashboard/properties/{$property->id}")
        ->assertRedirect('/dashboard/properties');

    $this->assertSoftDeleted('properties', ['id' => $property->id]);
});

// Faqet e formës

test('the create page renders with municipalities, features and no agents for an agent', function () {
    $agent = User::factory()->agent()->create();
    Location::factory()->create();
    Feature::factory()->create();

    $this->actingAs($agent)
        ->get('/dashboard/properties/create')
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard/properties/Create')
            ->has('municipalities')
            ->has('features')
            ->where('agents', null));
});

test('the edit page renders the property with translations and the agents list for an admin', function () {
    $admin = User::factory()->admin()->create();
    $property = Property::factory()->create(['price' => 11400000]);

    $this->actingAs($admin)
        ->get("/dashboard/properties/{$property->id}/edit")
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard/properties/Edit')
            ->where('property.id', $property->id)
            ->where('property.price_euros', fn ($value) => (float) $value === 114000.0)
            ->has('property.title.sq')
            ->has('agents'));
});
