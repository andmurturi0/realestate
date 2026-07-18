<?php

use App\Models\Location;
use App\Models\Property;

test('translatable fields return the requested locale', function () {
    $property = Property::factory()->create([
        'title' => ['sq' => 'Shtëpi', 'en' => 'House', 'de' => 'Haus'],
    ]);

    expect($property->getTranslation('title', 'sq'))->toBe('Shtëpi')
        ->and($property->getTranslation('title', 'en'))->toBe('House')
        ->and($property->getTranslation('title', 'de'))->toBe('Haus');
});

test('the model locale can be switched with setLocale', function () {
    $property = Property::factory()->create([
        'title' => ['sq' => 'Shtëpi', 'en' => 'House', 'de' => 'Haus'],
    ]);

    expect($property->setLocale('de')->title)->toBe('Haus')
        ->and($property->setLocale('en')->title)->toBe('House');
});

test('an empty locale falls back to sq', function () {
    $property = Property::factory()->create([
        'title' => ['sq' => 'Banesë në Ulpianë'],
    ]);

    expect($property->getTranslation('title', 'de'))->toBe('Banesë në Ulpianë')
        ->and($property->getTranslation('title', 'en'))->toBe('Banesë në Ulpianë');
});

test('location names are translatable with sq fallback', function () {
    $location = Location::factory()->create([
        'name' => ['sq' => 'Prishtinë', 'en' => 'Pristina'],
    ]);

    expect($location->getTranslation('name', 'en'))->toBe('Pristina')
        ->and($location->getTranslation('name', 'de'))->toBe('Prishtinë');
});
