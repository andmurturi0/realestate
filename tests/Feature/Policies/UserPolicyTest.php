<?php

use App\Models\User;

// Agjentët — CRUD: vetëm admin

test('an admin can view the agent list', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->can('viewAny', User::class))->toBeTrue();
});

test('an agent cannot view the agent list', function () {
    $agent = User::factory()->agent()->create();

    expect($agent->can('viewAny', User::class))->toBeFalse();
});

test('an admin can view an agent', function () {
    $admin = User::factory()->admin()->create();
    $agent = User::factory()->agent()->create();

    expect($admin->can('view', $agent))->toBeTrue();
});

test('an agent cannot view another user', function () {
    $agent = User::factory()->agent()->create();
    $otherAgent = User::factory()->agent()->create();

    expect($agent->can('view', $otherAgent))->toBeFalse();
});

test('an admin can create an agent', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->can('create', User::class))->toBeTrue();
});

test('an agent cannot create an agent', function () {
    $agent = User::factory()->agent()->create();

    expect($agent->can('create', User::class))->toBeFalse();
});

test('an admin can update an agent', function () {
    $admin = User::factory()->admin()->create();
    $agent = User::factory()->agent()->create();

    expect($admin->can('update', $agent))->toBeTrue();
});

test('an agent cannot update another user', function () {
    $agent = User::factory()->agent()->create();
    $otherAgent = User::factory()->agent()->create();

    expect($agent->can('update', $otherAgent))->toBeFalse();
});

test('an admin can delete an agent', function () {
    $admin = User::factory()->admin()->create();
    $agent = User::factory()->agent()->create();

    expect($admin->can('delete', $agent))->toBeTrue();
});

test('an agent cannot delete a user', function () {
    $agent = User::factory()->agent()->create();
    $otherAgent = User::factory()->agent()->create();

    expect($agent->can('delete', $otherAgent))->toBeFalse();
});

test('an admin cannot delete themselves', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->can('delete', $admin))->toBeFalse();
});
