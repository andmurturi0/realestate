<?php

use App\Enums\PropertyCategory;
use App\Enums\RequestType;
use App\Models\Location;
use App\Models\PropertyRequest;
use App\Models\User;
use App\Notifications\NewLeadReceived;
use Illuminate\Support\Facades\Notification;

function validRequestPayload(Location $location): array
{
    return [
        'first_name' => 'Blerta',
        'last_name' => 'Gashi',
        'phone' => '+38344123456',
        'request_type' => RequestType::Buying->value,
        'category' => PropertyCategory::Apartment->value,
        'location_id' => $location->id,
        'budget' => 90000,
        'surface_unit' => 'm2',
        'surface_min' => 50,
        'surface_max' => 70,
        'details' => 'Preferoj katin e parë.',
        // Far enough in the past to clear the minimum time-on-form check.
        'form_rendered_at' => (int) (now()->valueOf() - 5000),
    ];
}

test('a valid request submission is stored correctly', function () {
    $location = Location::factory()->create();

    $this->post(route('create-request.store'), validRequestPayload($location))->assertSuccessful();

    $request = PropertyRequest::query()->firstOrFail();

    expect($request->first_name)->toBe('Blerta')
        ->and($request->last_name)->toBe('Gashi')
        ->and($request->status->value)->toBe('new')
        ->and($request->assigned_to)->toBeNull()
        ->and($request->budget_max)->toBe(9000000)
        ->and($request->surface_min_m2)->toEqual(50.0)
        ->and($request->surface_max_m2)->toEqual(70.0);
});

test('surface min/max are normalized to square metres from the chosen unit', function () {
    $location = Location::factory()->create();

    $this->post(route('create-request.store'), [
        ...validRequestPayload($location),
        'surface_unit' => 'ari',
        'surface_min' => 1,
        'surface_max' => 2,
    ])->assertSuccessful();

    $request = PropertyRequest::query()->firstOrFail();

    expect($request->surface_min_m2)->toEqual(100.0)
        ->and($request->surface_max_m2)->toEqual(200.0)
        ->and($request->surface_unit->value)->toBe('ari');
});

test('an invalid phone number is rejected', function () {
    $location = Location::factory()->create();

    $this->postJson(route('create-request.store'), [
        ...validRequestPayload($location),
        'phone' => '044123',
    ])->assertJsonValidationErrors('phone');

    expect(PropertyRequest::query()->count())->toBe(0);
});

test('valid international phone numbers are accepted and stored in E.164', function (string $phone) {
    $location = Location::factory()->create();

    $this->post(route('create-request.store'), [
        ...validRequestPayload($location),
        'phone' => $phone,
    ])->assertSuccessful();

    expect(PropertyRequest::query()->firstOrFail()->phone)->toBe($phone);
})->with([
    'Kosovo' => '+38344123456',
    'Germany' => '+491701234567',
    'Albania' => '+355692045678',
]);

test('a fourth request within an hour is rate limited', function () {
    $location = Location::factory()->create();
    $payload = validRequestPayload($location);

    $this->post(route('create-request.store'), $payload)->assertSuccessful();
    $this->post(route('create-request.store'), $payload)->assertSuccessful();
    $this->post(route('create-request.store'), $payload)->assertSuccessful();
    $this->post(route('create-request.store'), $payload)->assertStatus(429);

    expect(PropertyRequest::query()->count())->toBe(3);
});

test('the honeypot silently drops the submission without storing anything', function () {
    $location = Location::factory()->create();

    $this->post(route('create-request.store'), [
        ...validRequestPayload($location),
        'website' => 'https://spam.example',
    ])->assertSuccessful();

    expect(PropertyRequest::query()->count())->toBe(0);
});

test('a submission faster than the minimum time on form is silently dropped', function () {
    $location = Location::factory()->create();

    $this->post(route('create-request.store'), [
        ...validRequestPayload($location),
        'form_rendered_at' => (int) now()->valueOf(),
    ])->assertSuccessful();

    expect(PropertyRequest::query()->count())->toBe(0);
});

test('admins are notified of a new request', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    User::factory()->agent()->create();
    $location = Location::factory()->create();

    $this->post(route('create-request.store'), validRequestPayload($location))->assertSuccessful();

    Notification::assertSentTo($admin, NewLeadReceived::class);
    Notification::assertCount(1);
});
