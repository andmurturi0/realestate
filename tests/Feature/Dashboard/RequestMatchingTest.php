<?php

use App\Enums\PropertyCategory;
use App\Models\Location;
use App\Models\Property;
use App\Models\PropertyRequest;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('matching properties are same category, same municipality, within budget and surface range', function () {
    $admin = User::factory()->admin()->create();
    $municipality = Location::factory()->create();
    $neighborhood = Location::factory()->neighborhood($municipality)->create();

    $request = PropertyRequest::factory()->create([
        'category' => PropertyCategory::Apartment,
        'location_id' => $neighborhood->id,
        'budget_max' => 9_000_000,
        'surface_min_m2' => 50,
        'surface_max_m2' => 70,
    ]);

    // Matches: same category, within the municipality (same neighbourhood or not), in budget and surface range.
    $matchSameNeighborhood = Property::factory()->published()->create([
        'category' => PropertyCategory::Apartment,
        'location_id' => $neighborhood->id,
        'price' => 8_500_000,
        'surface_m2' => 60,
    ]);
    $matchSameMunicipality = Property::factory()->published()->create([
        'category' => PropertyCategory::Apartment,
        'location_id' => $municipality->id,
        'price' => 8_000_000,
        'surface_m2' => 55,
    ]);

    // Excluded: wrong category, over budget, surface out of range, different municipality, unpublished.
    Property::factory()->published()->create([
        'category' => PropertyCategory::House,
        'location_id' => $neighborhood->id,
        'price' => 8_500_000,
        'surface_m2' => 60,
    ]);
    Property::factory()->published()->create([
        'category' => PropertyCategory::Apartment,
        'location_id' => $neighborhood->id,
        'price' => 9_500_000,
        'surface_m2' => 60,
    ]);
    Property::factory()->published()->create([
        'category' => PropertyCategory::Apartment,
        'location_id' => $neighborhood->id,
        'price' => 8_500_000,
        'surface_m2' => 20,
    ]);
    $otherMunicipality = Location::factory()->create();
    Property::factory()->published()->create([
        'category' => PropertyCategory::Apartment,
        'location_id' => $otherMunicipality->id,
        'price' => 8_500_000,
        'surface_m2' => 60,
    ]);
    Property::factory()->draft()->create([
        'category' => PropertyCategory::Apartment,
        'location_id' => $neighborhood->id,
        'price' => 8_500_000,
        'surface_m2' => 60,
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard.inbox.requests', ['selected' => $request->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('selected.matches', 2)
            ->where(
                'selected.matches',
                fn ($matches) => collect($matches)->pluck('id')->all() === [$matchSameNeighborhood->id, $matchSameMunicipality->id]
            ));
});

test('a request with no matches reports zero matches', function () {
    $admin = User::factory()->admin()->create();
    $request = PropertyRequest::factory()->create([
        'category' => PropertyCategory::Land,
        'budget_max' => 1_000_000,
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard.inbox.requests', ['selected' => $request->id]))
        ->assertInertia(fn (Assert $page) => $page->has('selected.matches', 0));
});

test('matches are limited to six, ordered by price closest to budget', function () {
    $admin = User::factory()->admin()->create();
    $municipality = Location::factory()->create();

    $request = PropertyRequest::factory()->create([
        'category' => PropertyCategory::Apartment,
        'location_id' => $municipality->id,
        'budget_max' => 10_000_000,
        'surface_min_m2' => null,
        'surface_max_m2' => null,
    ]);

    $prices = [9_900_000, 9_800_000, 9_700_000, 9_600_000, 9_500_000, 9_400_000, 9_300_000];
    foreach ($prices as $price) {
        Property::factory()->published()->create([
            'category' => PropertyCategory::Apartment,
            'location_id' => $municipality->id,
            'price' => $price,
        ]);
    }

    $this->actingAs($admin)
        ->get(route('dashboard.inbox.requests', ['selected' => $request->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('selected.matches', 6)
            ->where('selected.matches.0.price', 9_900_000));
});
