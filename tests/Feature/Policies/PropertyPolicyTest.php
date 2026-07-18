<?php

use App\Models\Property;
use App\Models\User;

// Prona — shiko: të gjitha për të dy rolet

test('an admin can view the property list', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->can('viewAny', Property::class))->toBeTrue();
});

test('an agent can view the property list', function () {
    $agent = User::factory()->agent()->create();

    expect($agent->can('viewAny', Property::class))->toBeTrue();
});

test('an admin can view any property', function () {
    $admin = User::factory()->admin()->create();
    $property = Property::factory()->create();

    expect($admin->can('view', $property))->toBeTrue();
});

test('an agent can view another agent\'s property', function () {
    $agent = User::factory()->agent()->create();
    $property = Property::factory()->create();

    expect($agent->can('view', $property))->toBeTrue();
});

// Prona — krijo: të dy rolet

test('an admin can create a property', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->can('create', Property::class))->toBeTrue();
});

test('an agent can create a property', function () {
    $agent = User::factory()->agent()->create();

    expect($agent->can('create', Property::class))->toBeTrue();
});

// Prona — edito: admin të gjitha, agent vetëm të vetat

test('an admin can update any property', function () {
    $admin = User::factory()->admin()->create();
    $property = Property::factory()->create();

    expect($admin->can('update', $property))->toBeTrue();
});

test('an agent can update their own property', function () {
    $agent = User::factory()->agent()->create();
    $property = Property::factory()->for($agent, 'agent')->create();

    expect($agent->can('update', $property))->toBeTrue();
});

test('an agent cannot update another agent\'s property', function () {
    $agent = User::factory()->agent()->create();
    $property = Property::factory()->create();

    expect($agent->can('update', $property))->toBeFalse();
});

// Prona — fshi: admin të gjitha, agent vetëm të vetat

test('an admin can delete any property', function () {
    $admin = User::factory()->admin()->create();
    $property = Property::factory()->create();

    expect($admin->can('delete', $property))->toBeTrue();
});

test('an agent can delete their own property', function () {
    $agent = User::factory()->agent()->create();
    $property = Property::factory()->for($agent, 'agent')->create();

    expect($agent->can('delete', $property))->toBeTrue();
});

test('an agent cannot delete another agent\'s property', function () {
    $agent = User::factory()->agent()->create();
    $property = Property::factory()->create();

    expect($agent->can('delete', $property))->toBeFalse();
});

// Prona — publiko: admin të gjitha, agent vetëm të vetat

test('an admin can publish any property', function () {
    $admin = User::factory()->admin()->create();
    $property = Property::factory()->create();

    expect($admin->can('publish', $property))->toBeTrue();
});

test('an agent can publish their own property', function () {
    $agent = User::factory()->agent()->create();
    $property = Property::factory()->for($agent, 'agent')->create();

    expect($agent->can('publish', $property))->toBeTrue();
});

test('an agent cannot publish another agent\'s property', function () {
    $agent = User::factory()->agent()->create();
    $property = Property::factory()->create();

    expect($agent->can('publish', $property))->toBeFalse();
});
