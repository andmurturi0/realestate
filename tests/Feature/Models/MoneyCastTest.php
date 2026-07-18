<?php

use App\Models\Property;

test('prices are stored and retrieved as integer cents', function () {
    $property = Property::factory()->create(['price' => 11400000]);

    expect($property->price)->toBeInt()->toBe(11400000)
        ->and($property->refresh()->price)->toBeInt()->toBe(11400000)
        ->and($property->getRawOriginal('price'))->toEqual(11400000);
});

test('the money cast rejects non-integer values', function (mixed $value) {
    expect(fn () => Property::factory()->create(['price' => $value]))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'float' => 114000.50,
    'float string' => '114000.50',
    'non-numeric string' => 'expensive',
]);

test('the money cast accepts integer strings', function () {
    $property = Property::factory()->create(['price' => '11400000']);

    expect($property->price)->toBeInt()->toBe(11400000);
});
