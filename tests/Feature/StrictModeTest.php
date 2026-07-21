<?php

use App\Models\Property;
use Illuminate\Database\LazyLoadingViolationException;

// Faza 10 §4: Model::preventLazyLoading is on outside production, so a
// missing eager-load surfaces as a loud exception here instead of a silent
// N+1 in production. Laravel only flags the violation when hydrating more
// than one row (Builder::hydrate) — a single first()/find() isn't an N+1 —
// so these tests fetch a collection, matching how a real listing page would.

test('lazily loading a relation across a collection throws outside production', function () {
    Property::factory()->count(2)->create();
    $properties = Property::query()->get();

    expect(fn () => $properties->each(fn (Property $property) => $property->location->name))
        ->toThrow(LazyLoadingViolationException::class);
});

test('an eager-loaded relation across a collection does not throw', function () {
    Property::factory()->count(2)->create();
    $properties = Property::query()->with('location')->get();

    expect(fn () => $properties->each(fn (Property $property) => $property->location->name))
        ->not->toThrow(LazyLoadingViolationException::class);
});
