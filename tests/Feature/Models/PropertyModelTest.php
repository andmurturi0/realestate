<?php

use App\Models\Property;
use App\Models\PropertyPriceHistory;
use App\Models\User;

test('reference_code generates as PRO-{id} padded to four digits after create', function () {
    $property = Property::factory()->create();

    $expected = 'PRO-'.str_pad((string) $property->id, 4, '0', STR_PAD_LEFT);

    expect($property->reference_code)->toBe($expected)
        ->and($property->refresh()->reference_code)->toBe($expected);

    $second = Property::factory()->create();

    expect($second->reference_code)->toBe('PRO-'.str_pad((string) $second->id, 4, '0', STR_PAD_LEFT));
});

test('the slug generates from the sq title on create', function () {
    $property = Property::factory()->create([
        'title' => [
            'sq' => 'Banesë e re në Dardani',
            'en' => 'New apartment in Dardania',
            'de' => 'Neue Wohnung in Dardania',
        ],
    ]);

    expect($property->slug)->toBe('banese-e-re-ne-dardani');
});

test('duplicate titles get a suffixed slug', function () {
    $title = ['sq' => 'Shtëpi në Ulpianë', 'en' => 'House', 'de' => 'Haus'];

    $first = Property::factory()->create(['title' => $title]);
    $second = Property::factory()->create(['title' => $title]);

    expect($first->slug)->toBe('shtepi-ne-ulpiane')
        ->and($second->slug)->toBe('shtepi-ne-ulpiane-2');
});

test('the slug does not change when the title is edited later', function () {
    $property = Property::factory()->create([
        'title' => ['sq' => 'Banesë në Qafa', 'en' => 'Apartment', 'de' => 'Wohnung'],
    ]);
    $originalSlug = $property->slug;

    $property->update([
        'title' => ['sq' => 'Titull krejt tjetër', 'en' => 'Other', 'de' => 'Anders'],
    ]);

    expect($property->refresh()->slug)->toBe($originalSlug);
});

test('updating the price writes a price history row with correct old and new values', function () {
    $property = Property::factory()->create(['price' => 10000000]);

    $property->update(['price' => 9500000]);

    $history = $property->priceHistories()->get();

    expect($history)->toHaveCount(1)
        ->and($history->first()->old_price)->toBe(10000000)
        ->and($history->first()->new_price)->toBe(9500000)
        ->and($history->first()->changed_by)->toBeNull();
});

test('the price history records the authenticated user as changed_by', function () {
    $user = User::factory()->create();
    $property = Property::factory()->create(['price' => 10000000]);

    $this->actingAs($user);
    $property->update(['price' => 12000000]);

    expect($property->priceHistories()->first()->changed_by)->toBe($user->id);
});

test('updating other fields does not write a price history row', function () {
    $property = Property::factory()->create(['price' => 10000000]);

    $property->update(['views_count' => 99, 'is_featured' => true]);

    expect(PropertyPriceHistory::count())->toBe(0);
});

test('saving an unchanged price does not write a price history row', function () {
    $property = Property::factory()->create(['price' => 10000000]);

    $property->update(['price' => 10000000]);

    expect(PropertyPriceHistory::count())->toBe(0);
});
