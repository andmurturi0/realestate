<?php

use App\Models\User;

test('an admin user reports isAdmin and not isAgent', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->isAdmin())->toBeTrue()
        ->and($admin->isAgent())->toBeFalse();
});

test('an agent user reports isAgent and not isAdmin', function () {
    $agent = User::factory()->agent()->create();

    expect($agent->isAgent())->toBeTrue()
        ->and($agent->isAdmin())->toBeFalse();
});
