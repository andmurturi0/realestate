<?php

use App\Enums\FeatureGroup;
use App\Models\Feature;
use App\Models\Location;
use App\Services\FacetOptionsService;
use Illuminate\Support\Facades\Cache;

// Faza 10 §5: locations/features are re-queried on every filter/form page
// load; caching them must not go stale when an admin (or a seeder) changes
// one.

test('municipalities are cached per locale', function () {
    $service = app(FacetOptionsService::class);
    Location::factory()->create(['name' => ['sq' => 'Prishtina', 'en' => 'Pristina', 'de' => 'Pristina']]);

    $service->municipalities('sq');

    expect(Cache::has('facets.municipalities.sq'))->toBeTrue()
        ->and(Cache::has('facets.municipalities.en'))->toBeFalse();

    expect($service->municipalities('sq')[0]['name'])->toBe('Prishtina')
        ->and($service->municipalities('en')[0]['name'])->toBe('Pristina');
});

test('saving a location invalidates every cached facet', function () {
    $service = app(FacetOptionsService::class);
    Location::factory()->create(['name' => ['sq' => 'Prishtina', 'en' => 'Pristina', 'de' => 'Pristina']]);

    $service->municipalities('sq');
    $service->municipalitiesWithCoordinates();

    expect(Cache::has('facets.municipalities.sq'))->toBeTrue()
        ->and(Cache::has('facets.municipalities.dashboard'))->toBeTrue();

    Location::factory()->create(['name' => ['sq' => 'Prizreni', 'en' => 'Prizren', 'de' => 'Prizren']]);

    expect(Cache::has('facets.municipalities.sq'))->toBeFalse()
        ->and(Cache::has('facets.municipalities.dashboard'))->toBeFalse()
        ->and($service->municipalities('sq'))->toHaveCount(2);
});

test('deleting a location invalidates the cache', function () {
    $service = app(FacetOptionsService::class);
    $location = Location::factory()->create();
    $service->municipalities('sq');

    $location->delete();

    expect(Cache::has('facets.municipalities.sq'))->toBeFalse()
        ->and($service->municipalities('sq'))->toBe([]);
});

test('furnishing features are cached per locale and only include the furnishing group', function () {
    $service = app(FacetOptionsService::class);
    Feature::factory()->create(['group' => FeatureGroup::Furnishing, 'key' => 'salon']);
    Feature::factory()->create(['group' => FeatureGroup::Infrastructure, 'key' => 'water']);

    $keys = collect($service->furnishingFeatures('sq'))->pluck('key')->all();

    expect(Cache::has('facets.furnishing.sq'))->toBeTrue()
        ->and($keys)->toBe(['salon']);
});

test('saving a feature invalidates every cached facet', function () {
    $service = app(FacetOptionsService::class);
    $service->furnishingFeatures('sq');
    $service->featuresGroupedForDashboard();

    expect(Cache::has('facets.furnishing.sq'))->toBeTrue()
        ->and(Cache::has('facets.features.dashboard'))->toBeTrue();

    Feature::factory()->create(['group' => FeatureGroup::Furnishing]);

    expect(Cache::has('facets.furnishing.sq'))->toBeFalse()
        ->and(Cache::has('facets.features.dashboard'))->toBeFalse();
});

test('featuresGroupedForDashboard groups every feature by its group, in sq', function () {
    $service = app(FacetOptionsService::class);
    Feature::factory()->create(['group' => FeatureGroup::Furnishing, 'name' => ['sq' => 'Salloni', 'en' => 'Living room', 'de' => 'Wohnzimmer']]);
    Feature::factory()->create(['group' => FeatureGroup::Infrastructure, 'name' => ['sq' => 'Uji', 'en' => 'Water', 'de' => 'Wasser']]);

    $grouped = $service->featuresGroupedForDashboard();

    expect($grouped['furnishing'][0]['name'])->toBe('Salloni')
        ->and($grouped['infrastructure'][0]['name'])->toBe('Uji');
});
