<?php

use App\Models\TeamMember;
use App\Models\User;

// Ekipi — CRUD: vetëm admin

test('an admin can view the team member list', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->can('viewAny', TeamMember::class))->toBeTrue();
});

test('an agent cannot view the team member list', function () {
    $agent = User::factory()->agent()->create();

    expect($agent->can('viewAny', TeamMember::class))->toBeFalse();
});

test('an admin can create a team member', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->can('create', TeamMember::class))->toBeTrue();
});

test('an agent cannot create a team member', function () {
    $agent = User::factory()->agent()->create();

    expect($agent->can('create', TeamMember::class))->toBeFalse();
});

test('an admin can update a team member', function () {
    $admin = User::factory()->admin()->create();
    $teamMember = TeamMember::factory()->create();

    expect($admin->can('update', $teamMember))->toBeTrue();
});

test('an agent cannot update a team member', function () {
    $agent = User::factory()->agent()->create();
    $teamMember = TeamMember::factory()->create();

    expect($agent->can('update', $teamMember))->toBeFalse();
});

test('an admin can delete a team member', function () {
    $admin = User::factory()->admin()->create();
    $teamMember = TeamMember::factory()->create();

    expect($admin->can('delete', $teamMember))->toBeTrue();
});

test('an agent cannot delete a team member', function () {
    $agent = User::factory()->agent()->create();
    $teamMember = TeamMember::factory()->create();

    expect($agent->can('delete', $teamMember))->toBeFalse();
});
