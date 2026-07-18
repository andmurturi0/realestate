<?php

use App\Models\ContactMessage;
use App\Models\PropertyOffer;
use App\Models\PropertyRequest;
use App\Models\User;

dataset('lead types', [
    'contact message' => [ContactMessage::class],
    'property offer' => [PropertyOffer::class],
    'property request' => [PropertyRequest::class],
]);

// Inbox — shiko listën: çdo përdorues i kyçur

test('an admin can view the inbox', function (string $leadType) {
    $admin = User::factory()->admin()->create();

    expect($admin->can('viewAny', $leadType))->toBeTrue();
})->with('lead types');

test('an agent can view the inbox', function (string $leadType) {
    $agent = User::factory()->agent()->create();

    expect($agent->can('viewAny', $leadType))->toBeTrue();
})->with('lead types');

// Inbox — shiko lead: admin të gjitha, agent vetëm assigned_to = me

test('an admin can view any lead', function (string $leadType) {
    $admin = User::factory()->admin()->create();
    $lead = $leadType::factory()->create();

    expect($admin->can('view', $lead))->toBeTrue();
})->with('lead types');

test('an agent can view a lead assigned to them', function (string $leadType) {
    $agent = User::factory()->agent()->create();
    $lead = $leadType::factory()->create(['assigned_to' => $agent->id]);

    expect($agent->can('view', $lead))->toBeTrue();
})->with('lead types');

test('an agent cannot view a lead assigned to someone else', function (string $leadType) {
    $agent = User::factory()->agent()->create();
    $otherAgent = User::factory()->agent()->create();
    $lead = $leadType::factory()->create(['assigned_to' => $otherAgent->id]);

    expect($agent->can('view', $lead))->toBeFalse();
})->with('lead types');

test('an agent cannot view an unassigned lead', function (string $leadType) {
    $agent = User::factory()->agent()->create();
    $lead = $leadType::factory()->create();

    expect($agent->can('view', $lead))->toBeFalse();
})->with('lead types');

// Inbox — status/shënim (update): admin të gjitha, agent vetëm të vetat

test('an admin can update any lead', function (string $leadType) {
    $admin = User::factory()->admin()->create();
    $lead = $leadType::factory()->create();

    expect($admin->can('update', $lead))->toBeTrue();
})->with('lead types');

test('an agent can update a lead assigned to them', function (string $leadType) {
    $agent = User::factory()->agent()->create();
    $lead = $leadType::factory()->create(['assigned_to' => $agent->id]);

    expect($agent->can('update', $lead))->toBeTrue();
})->with('lead types');

test('an agent cannot update a lead assigned to someone else', function (string $leadType) {
    $agent = User::factory()->agent()->create();
    $otherAgent = User::factory()->agent()->create();
    $lead = $leadType::factory()->create(['assigned_to' => $otherAgent->id]);

    expect($agent->can('update', $lead))->toBeFalse();
})->with('lead types');

// Inbox — cakto te dikush: vetëm admin

test('an admin can assign a lead', function (string $leadType) {
    $admin = User::factory()->admin()->create();
    $lead = $leadType::factory()->create();

    expect($admin->can('assign', $lead))->toBeTrue();
})->with('lead types');

test('an agent cannot assign a lead, even one assigned to them', function (string $leadType) {
    $agent = User::factory()->agent()->create();
    $lead = $leadType::factory()->create(['assigned_to' => $agent->id]);

    expect($agent->can('assign', $lead))->toBeFalse();
})->with('lead types');

// Inbox — fshi: vetëm admin

test('an admin can delete a lead', function (string $leadType) {
    $admin = User::factory()->admin()->create();
    $lead = $leadType::factory()->create();

    expect($admin->can('delete', $lead))->toBeTrue();
})->with('lead types');

test('an agent cannot delete a lead, even one assigned to them', function (string $leadType) {
    $agent = User::factory()->agent()->create();
    $lead = $leadType::factory()->create(['assigned_to' => $agent->id]);

    expect($agent->can('delete', $lead))->toBeFalse();
})->with('lead types');
