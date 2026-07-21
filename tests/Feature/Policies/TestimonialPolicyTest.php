<?php

use App\Models\Testimonial;
use App\Models\User;

// Dëshmitë — CRUD: vetëm admin

test('an admin can view the testimonial list', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->can('viewAny', Testimonial::class))->toBeTrue();
});

test('an agent cannot view the testimonial list', function () {
    $agent = User::factory()->agent()->create();

    expect($agent->can('viewAny', Testimonial::class))->toBeFalse();
});

test('an admin can create a testimonial', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->can('create', Testimonial::class))->toBeTrue();
});

test('an agent cannot create a testimonial', function () {
    $agent = User::factory()->agent()->create();

    expect($agent->can('create', Testimonial::class))->toBeFalse();
});

test('an admin can update a testimonial', function () {
    $admin = User::factory()->admin()->create();
    $testimonial = Testimonial::factory()->create();

    expect($admin->can('update', $testimonial))->toBeTrue();
});

test('an agent cannot update a testimonial', function () {
    $agent = User::factory()->agent()->create();
    $testimonial = Testimonial::factory()->create();

    expect($agent->can('update', $testimonial))->toBeFalse();
});

test('an admin can delete a testimonial', function () {
    $admin = User::factory()->admin()->create();
    $testimonial = Testimonial::factory()->create();

    expect($admin->can('delete', $testimonial))->toBeTrue();
});

test('an agent cannot delete a testimonial', function () {
    $agent = User::factory()->agent()->create();
    $testimonial = Testimonial::factory()->create();

    expect($agent->can('delete', $testimonial))->toBeFalse();
});
