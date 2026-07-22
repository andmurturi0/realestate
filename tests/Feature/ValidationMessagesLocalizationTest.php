<?php

use App\Models\Location;
use App\Models\Property;

// Faza 10 §15: the three public forms used to show English framework-default
// validation errors regardless of locale (no lang/{locale}/validation.php
// existed at all). The locale for these POST-only routes comes from the
// `locale` cookie (SetLocale), not a URL prefix — they aren't mirrored under
// /en, /de like the GET pages are. postJson() drops cookies unless
// withCredentials() is chained (it simulates a cross-origin XHR by default).
// withUnencryptedCookie(), not withCookie(): `locale` is excluded from
// EncryptCookies (Faza 10 §11, it's written as plain JS text by the
// browser) — withCookie() would encrypt it and EncryptCookies would then
// leave that ciphertext alone since the cookie is on the except list.

test('a contact message validation error is in Albanian by default', function () {
    $property = Property::factory()->published()->create();

    $response = $this->postJson(route('properties.contact', $property), []);

    expect($response->json('errors.full_name.0'))->toBe('Fusha emri i plotë është e detyrueshme.')
        ->and($response->json('errors.message.0'))->toBe('Fusha mesazhi është e detyrueshme.');
});

test('an offer submission validation error is in sq, en and de depending on the locale cookie', function () {
    $invalidPayload = ['phone' => '044123'];

    $sq = $this->withCredentials()->withUnencryptedCookie('locale', 'sq')->postJson(route('offer-property.store'), $invalidPayload);
    $en = $this->withCredentials()->withUnencryptedCookie('locale', 'en')->postJson(route('offer-property.store'), $invalidPayload);
    $de = $this->withCredentials()->withUnencryptedCookie('locale', 'de')->postJson(route('offer-property.store'), $invalidPayload);

    expect($sq->json('errors.first_name.0'))->toBe('Fusha emri është e detyrueshme.')
        ->and($en->json('errors.first_name.0'))->toBe('The first name field is required.')
        ->and($de->json('errors.first_name.0'))->toBe('Das Feld Vorname ist erforderlich.');

    expect($sq->json('errors.phone.0'))->toBe('Fusha telefoni duhet të jetë një numër telefoni i vlefshëm.')
        ->and($en->json('errors.phone.0'))->toBe('The phone field must be a valid phone number.')
        ->and($de->json('errors.phone.0'))->toBe('Das Feld Telefon muss eine gültige Telefonnummer sein.');
});

test('a create-request validation error uses the translated attribute name', function () {
    $location = Location::factory()->create();

    $response = $this->withCredentials()->withUnencryptedCookie('locale', 'de')->postJson(route('create-request.store'), [
        'first_name' => 'Blerta',
        'last_name' => 'Gashi',
        'phone' => '+38344123456',
        'request_type' => 'buying',
        'category' => 'apartment',
        'location_id' => $location->id,
        'surface_unit' => 'm2',
        // budget omitted on purpose
    ]);

    expect($response->json('errors.budget.0'))->toBe('Das Feld Budget ist erforderlich.');
});
