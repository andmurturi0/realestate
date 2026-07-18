<?php

use App\Models\ContactMessage;
use App\Models\Property;
use App\Models\User;

test('a message with a property_id auto-assigns to that property\'s agent', function () {
    $agent = User::factory()->create();
    $property = Property::factory()->for($agent, 'agent')->create();

    $message = ContactMessage::factory()->create(['property_id' => $property->id]);

    expect($message->assigned_to)->toBe($agent->id);
});

test('a message without a property stays unassigned', function () {
    $message = ContactMessage::factory()->create();

    expect($message->property_id)->toBeNull()
        ->and($message->assigned_to)->toBeNull();
});

test('an explicit assignment is not overridden by the auto-assign hook', function () {
    $agent = User::factory()->create();
    $otherAgent = User::factory()->create();
    $property = Property::factory()->for($agent, 'agent')->create();

    $message = ContactMessage::factory()->create([
        'property_id' => $property->id,
        'assigned_to' => $otherAgent->id,
    ]);

    expect($message->assigned_to)->toBe($otherAgent->id);
});
