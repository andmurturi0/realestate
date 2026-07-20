<?php

use App\Models\ContactMessage;
use App\Models\Property;
use App\Models\PropertyOffer;
use App\Models\PropertyRequest;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

// Smoke test — a message attached to a property must not blow up the list
// query. property_id being non-null is what triggers the eager-load of the
// `property` relation at all (an empty foreign-key set gets optimized away
// by Eloquent, which is why a message with no property never caught this).

test('the message list loads successfully when a message is attached to a property', function () {
    $admin = User::factory()->admin()->create();
    $property = Property::factory()->published()->create();
    ContactMessage::factory()->create(['property_id' => $property->id]);

    $this->actingAs($admin)
        ->get(route('dashboard.inbox.messages'))
        ->assertSuccessful();
});

// Scoping — the query itself must never even enumerate another agent's leads.

test('an agent only sees messages assigned to them in the list', function () {
    $agent = User::factory()->agent()->create();
    $mine = ContactMessage::factory()->create(['assigned_to' => $agent->id]);
    ContactMessage::factory()->create();

    $this->actingAs($agent)
        ->get(route('dashboard.inbox.messages'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('messages.data', 1)
            ->where('messages.data.0.id', $mine->id));
});

test('an agent only sees offers assigned to them in the list', function () {
    $agent = User::factory()->agent()->create();
    $mine = PropertyOffer::factory()->create(['assigned_to' => $agent->id]);
    PropertyOffer::factory()->create();

    $this->actingAs($agent)
        ->get(route('dashboard.inbox.offers'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('offers.data', 1)
            ->where('offers.data.0.id', $mine->id));
});

test('an agent only sees requests assigned to them in the list', function () {
    $agent = User::factory()->agent()->create();
    $mine = PropertyRequest::factory()->create(['assigned_to' => $agent->id]);
    PropertyRequest::factory()->create();

    $this->actingAs($agent)
        ->get(route('dashboard.inbox.requests'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('requests.data', 1)
            ->where('requests.data.0.id', $mine->id));
});

test('an admin sees every lead in every tab', function () {
    $admin = User::factory()->admin()->create();
    ContactMessage::factory()->count(2)->create();
    PropertyOffer::factory()->count(2)->create();
    PropertyRequest::factory()->count(2)->create();

    $this->actingAs($admin)->get(route('dashboard.inbox.messages'))
        ->assertInertia(fn (Assert $page) => $page->has('messages.data', 2));
    $this->actingAs($admin)->get(route('dashboard.inbox.offers'))
        ->assertInertia(fn (Assert $page) => $page->has('offers.data', 2));
    $this->actingAs($admin)->get(route('dashboard.inbox.requests'))
        ->assertInertia(fn (Assert $page) => $page->has('requests.data', 2));
});

// Direct-URL access — ?selected=<id> for a lead outside the agent's scope must 403.

test('an agent hitting another agent’s message by direct URL gets a 403', function () {
    $agent = User::factory()->agent()->create();
    $other = User::factory()->agent()->create();
    $theirs = ContactMessage::factory()->create(['assigned_to' => $other->id]);

    $this->actingAs($agent)
        ->get(route('dashboard.inbox.messages', ['selected' => $theirs->id]))
        ->assertForbidden();
});

test('an agent hitting another agent’s offer by direct URL gets a 403', function () {
    $agent = User::factory()->agent()->create();
    $other = User::factory()->agent()->create();
    $theirs = PropertyOffer::factory()->create(['assigned_to' => $other->id]);

    $this->actingAs($agent)
        ->get(route('dashboard.inbox.offers', ['selected' => $theirs->id]))
        ->assertForbidden();
});

test('an agent hitting another agent’s request by direct URL gets a 403', function () {
    $agent = User::factory()->agent()->create();
    $other = User::factory()->agent()->create();
    $theirs = PropertyRequest::factory()->create(['assigned_to' => $other->id]);

    $this->actingAs($agent)
        ->get(route('dashboard.inbox.requests', ['selected' => $theirs->id]))
        ->assertForbidden();
});

test('an agent can select their own lead and sees it in the detail panel', function () {
    $agent = User::factory()->agent()->create();
    $mine = ContactMessage::factory()->create(['assigned_to' => $agent->id]);

    $this->actingAs($agent)
        ->get(route('dashboard.inbox.messages', ['selected' => $mine->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('selected.id', $mine->id)
            ->where('selected.can_assign', false));
});

// Assignment — admin only, enforced server-side regardless of what the UI shows.

test('an agent cannot assign a lead even one assigned to them', function () {
    $agent = User::factory()->agent()->create();
    $another = User::factory()->agent()->create();
    $lead = ContactMessage::factory()->create(['assigned_to' => $agent->id]);

    $this->actingAs($agent)
        ->patch(route('dashboard.inbox.messages.assign', $lead), ['assigned_to' => $another->id])
        ->assertForbidden();

    expect($lead->fresh()->assigned_to)->toBe($agent->id);
});

test('an admin can assign a lead to an agent and it is visible to them immediately', function () {
    $admin = User::factory()->admin()->create();
    $agent = User::factory()->agent()->create();
    $offer = PropertyOffer::factory()->create(['assigned_to' => null]);

    $this->actingAs($admin)
        ->patch(route('dashboard.inbox.offers.assign', $offer), ['assigned_to' => $agent->id])
        ->assertRedirect();

    expect($offer->fresh()->assigned_to)->toBe($agent->id);

    $this->actingAs($agent)
        ->get(route('dashboard.inbox.offers'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('offers.data', 1)
            ->where('offers.data.0.id', $offer->id));
});

// Status updates — an agent may update their own lead's status.

test('an agent can update the status of their own lead', function () {
    $agent = User::factory()->agent()->create();
    $lead = ContactMessage::factory()->create(['assigned_to' => $agent->id, 'status' => 'new']);

    $this->actingAs($agent)
        ->patch(route('dashboard.inbox.messages.status', $lead), ['status' => 'contacted'])
        ->assertRedirect();

    expect($lead->fresh()->status->value)->toBe('contacted');
});

test('an agent cannot update the status of a lead assigned to someone else', function () {
    $agent = User::factory()->agent()->create();
    $other = User::factory()->agent()->create();
    $lead = ContactMessage::factory()->create(['assigned_to' => $other->id, 'status' => 'new']);

    $this->actingAs($agent)
        ->patch(route('dashboard.inbox.messages.status', $lead), ['status' => 'contacted'])
        ->assertForbidden();

    expect($lead->fresh()->status->value)->toBe('new');
});

// Sidebar badges — new-lead count drops once status changes off "new".

test('the sidebar badge count drops after a lead status change', function () {
    $agent = User::factory()->agent()->create();
    $lead = ContactMessage::factory()->create(['assigned_to' => $agent->id, 'status' => 'new']);

    $this->actingAs($agent)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('badges.messages', 1));

    $this->actingAs($agent)
        ->patch(route('dashboard.inbox.messages.status', $lead), ['status' => 'contacted'])
        ->assertRedirect();

    $this->actingAs($agent)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('badges.messages', 0));
});

// Notes — saved with the correct author, newest first.

test('a note is saved with the correct author', function () {
    $agent = User::factory()->agent()->create();
    $lead = ContactMessage::factory()->create(['assigned_to' => $agent->id]);

    $this->actingAs($agent)
        ->post(route('dashboard.inbox.messages.notes.store', $lead), ['body' => 'E thirra, do ta vizitojë të shtunën.'])
        ->assertRedirect();

    $note = $lead->notes()->firstOrFail();

    expect($note->body)->toBe('E thirra, do ta vizitojë të shtunën.')
        ->and($note->user_id)->toBe($agent->id);
});

test('an agent cannot add a note to a lead assigned to someone else', function () {
    $agent = User::factory()->agent()->create();
    $other = User::factory()->agent()->create();
    $lead = ContactMessage::factory()->create(['assigned_to' => $other->id]);

    $this->actingAs($agent)
        ->post(route('dashboard.inbox.messages.notes.store', $lead), ['body' => 'spam'])
        ->assertForbidden();

    expect($lead->notes()->count())->toBe(0);
});
