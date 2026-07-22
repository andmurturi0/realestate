<?php

use App\Models\ContactMessage;
use App\Models\LeadNote;
use App\Models\Property;
use App\Models\PropertyOffer;
use App\Models\PropertyRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

// Qasja

test('guests are redirected to login from the agent list', function () {
    $this->get('/dashboard/agents')->assertRedirect('/login');
});

test('an agent cannot view the agent list', function () {
    $agent = User::factory()->agent()->create();

    $this->actingAs($agent)->get('/dashboard/agents')->assertForbidden();
});

test('an agent cannot create an agent', function () {
    $agent = User::factory()->agent()->create();

    $this->actingAs($agent)
        ->post('/dashboard/agents', [
            'name' => 'Agjent Test',
            'email' => 'test@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'agent',
        ])
        ->assertForbidden();

    expect(User::where('email', 'test@example.test')->exists())->toBeFalse();
});

// CRUD

test('an admin can view the agent list with property and lead counts', function () {
    $admin = User::factory()->admin()->create();
    $agent = User::factory()->agent()->create(['name' => 'Agent Parë']);
    Property::factory()->count(2)->for($agent, 'agent')->create();
    ContactMessage::factory()->create(['assigned_to' => $agent->id]);

    $this->actingAs($admin)
        ->get('/dashboard/agents')
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard/agents/Index')
            ->where('users', fn ($users) => collect($users)
                ->firstWhere('id', $agent->id)['properties_count'] === 2
                && collect($users)->firstWhere('id', $agent->id)['leads_count'] === 1));
});

test('an admin can create an agent with an avatar and bio', function () {
    Storage::fake('supabase');
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/dashboard/agents', [
            'name' => 'Agjent Test',
            'email' => 'agjent.test@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '+38344123456',
            'whatsapp' => '+38344123456',
            'role' => 'agent',
            'bio' => ['sq' => 'Përshkrim shqip', 'en' => '', 'de' => ''],
            'avatar' => UploadedFile::fake()->image('agjent.jpg', 800, 800),
            'is_active' => true,
        ])
        ->assertRedirect('/dashboard/agents')
        ->assertSessionHasNoErrors();

    $agent = User::query()->where('email', 'agjent.test@example.test')->sole();

    expect($agent->name)->toBe('Agjent Test')
        ->and($agent->role->value)->toBe('agent')
        ->and($agent->getTranslation('bio', 'sq'))->toBe('Përshkrim shqip')
        ->and($agent->getTranslations('bio'))->toBe(['sq' => 'Përshkrim shqip'])
        ->and(Hash::check('password123', $agent->password))->toBeTrue()
        ->and($agent->avatar_path)->toStartWith('agents/')
        ->and($agent->avatar_path)->toEndWith('.webp');

    Storage::disk('supabase')->assertExists($agent->avatar_path);
});

test('an svg avatar upload is rejected', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/dashboard/agents', [
            'name' => 'Agjent Test',
            'email' => 'agjent.test@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'agent',
            'avatar' => UploadedFile::fake()->create('foto.svg', 10, 'image/svg+xml'),
        ])
        ->assertSessionHasErrors('avatar');

    expect(User::where('email', 'agjent.test@example.test')->exists())->toBeFalse();
});

test('creating an agent requires a confirmed password', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/dashboard/agents', [
            'name' => 'Agjent Test',
            'email' => 'agjent.test@example.test',
            'password' => 'password123',
            'password_confirmation' => 'nuk-perputhet',
            'role' => 'agent',
        ])
        ->assertSessionHasErrors('password');

    expect(User::where('email', 'agjent.test@example.test')->exists())->toBeFalse();
});

test('an admin can update an agent without changing the password', function () {
    $admin = User::factory()->admin()->create();
    $agent = User::factory()->agent()->create(['name' => 'Emri i vjetër']);
    $originalHash = $agent->password;

    $this->actingAs($admin)
        ->put("/dashboard/agents/{$agent->id}", [
            'name' => 'Emri i ri',
            'email' => $agent->email,
            'phone' => $agent->phone,
            'whatsapp' => $agent->whatsapp,
            'role' => 'agent',
            'is_active' => true,
        ])
        ->assertRedirect('/dashboard/agents')
        ->assertSessionHasNoErrors();

    $agent->refresh();

    expect($agent->name)->toBe('Emri i ri')
        ->and($agent->password)->toBe($originalHash);
});

test('an admin can set a new password when updating an agent', function () {
    $admin = User::factory()->admin()->create();
    $agent = User::factory()->agent()->create();

    $this->actingAs($admin)
        ->put("/dashboard/agents/{$agent->id}", [
            'name' => $agent->name,
            'email' => $agent->email,
            'role' => 'agent',
            'password' => 'fjalekalim-i-ri',
            'password_confirmation' => 'fjalekalim-i-ri',
            'is_active' => true,
        ])
        ->assertRedirect('/dashboard/agents')
        ->assertSessionHasNoErrors();

    expect(Hash::check('fjalekalim-i-ri', $agent->refresh()->password))->toBeTrue();
});

// Vetë-çaktivizimi

test('an admin cannot deactivate themselves through the edit form', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put("/dashboard/agents/{$admin->id}", [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'admin',
            'is_active' => false,
        ])
        ->assertSessionHasErrors('is_active');

    expect($admin->refresh()->is_active)->toBeTrue();
});

test('an admin cannot toggle their own active status', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/dashboard/agents/{$admin->id}/toggle-active")
        ->assertForbidden();

    expect($admin->refresh()->is_active)->toBeTrue();
});

test('an admin can deactivate and reactivate another agent', function () {
    $admin = User::factory()->admin()->create();
    $agent = User::factory()->agent()->create(['is_active' => true]);

    $this->actingAs($admin)->patch("/dashboard/agents/{$agent->id}/toggle-active")->assertRedirect();
    expect($agent->refresh()->is_active)->toBeFalse();

    $this->actingAs($admin)->patch("/dashboard/agents/{$agent->id}/toggle-active")->assertRedirect();
    expect($agent->refresh()->is_active)->toBeTrue();
});

test('a deactivated agent is logged out and blocked from the dashboard', function () {
    $agent = User::factory()->agent()->inactive()->create();

    $this->actingAs($agent)
        ->get('/dashboard')
        ->assertRedirect('/login');

    $this->assertGuest();
});

// Fshirja — pronat dhe lidhjet kalojnë te admini, jo null

test('deleting an agent reassigns their properties and leads to the acting admin and deletes their lead notes', function () {
    $admin = User::factory()->admin()->create();
    $agent = User::factory()->agent()->create();

    $properties = Property::factory()->count(2)->published()->for($agent, 'agent')->create();
    $message = ContactMessage::factory()->create(['assigned_to' => $agent->id]);
    $offer = PropertyOffer::factory()->create(['assigned_to' => $agent->id]);
    $request = PropertyRequest::factory()->create(['assigned_to' => $agent->id]);
    $note = LeadNote::factory()->create(['user_id' => $agent->id, 'notable_id' => $message->id, 'notable_type' => ContactMessage::class]);

    $this->actingAs($admin)
        ->delete("/dashboard/agents/{$agent->id}")
        ->assertRedirect('/dashboard/agents');

    expect(User::find($agent->id))->toBeNull()
        ->and(LeadNote::find($note->id))->toBeNull();

    foreach ($properties as $property) {
        expect($property->refresh()->agent_id)->toBe($admin->id);
    }

    expect($message->refresh()->assigned_to)->toBe($admin->id)
        ->and($offer->refresh()->assigned_to)->toBe($admin->id)
        ->and($request->refresh()->assigned_to)->toBe($admin->id);
});

test('a property reassigned to the admin after agent deletion still loads publicly and auto-assigns new contact messages to the admin', function () {
    $admin = User::factory()->admin()->create();
    $agent = User::factory()->agent()->create();
    $property = Property::factory()->published()->for($agent, 'agent')->create();

    $this->actingAs($admin)->delete("/dashboard/agents/{$agent->id}")->assertRedirect();

    $property->refresh();
    expect($property->agent_id)->toBe($admin->id);

    $this->get(route('properties.show', $property))->assertOk();

    $this->post(route('properties.contact', $property), [
        'full_name' => 'Agim Krasniqi',
        'phone' => '+38344123456',
        'message' => 'Më intereson kjo pronë.',
    ])->assertSuccessful();

    $newMessage = ContactMessage::query()->where('property_id', $property->id)->latest('id')->first();
    expect($newMessage->assigned_to)->toBe($admin->id);
});

test('an admin cannot delete themselves', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->delete("/dashboard/agents/{$admin->id}")
        ->assertForbidden();

    expect(User::find($admin->id))->not->toBeNull();
});
