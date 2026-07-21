<?php

use App\Models\Setting;
use App\Models\User;

// Cilësimet — shiko/ndrysho: vetëm admin

test('an admin can manage settings', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->can('manage', Setting::class))->toBeTrue();
});

test('an agent cannot manage settings', function () {
    $agent = User::factory()->agent()->create();

    expect($agent->can('manage', Setting::class))->toBeFalse();
});
