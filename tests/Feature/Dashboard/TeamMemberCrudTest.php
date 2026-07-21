<?php

use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

// Qasja

test('guests are redirected to login from the team member list', function () {
    $this->get('/dashboard/team-members')->assertRedirect('/login');
});

test('an agent cannot view the team member list', function () {
    $agent = User::factory()->agent()->create();

    $this->actingAs($agent)->get('/dashboard/team-members')->assertForbidden();
});

test('an agent cannot create a team member', function () {
    $agent = User::factory()->agent()->create();

    $this->actingAs($agent)
        ->post('/dashboard/team-members', [
            'name' => 'Anëtar Test',
            'position' => ['sq' => 'Agjent'],
        ])
        ->assertForbidden();

    expect(TeamMember::count())->toBe(0);
});

// CRUD

test('an admin can view the team member list', function () {
    TeamMember::factory()->create(['name' => 'Anëtari Parë']);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/dashboard/team-members')
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard/team-members/Index')
            ->has('teamMembers', 1)
            ->where('teamMembers.0.name', 'Anëtari Parë'));
});

test('an admin can create a team member with a photo', function () {
    Storage::fake('supabase');
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/dashboard/team-members', [
            'name' => 'Anëtar Test',
            'position' => ['sq' => 'Agjent i Shitjeve', 'en' => '', 'de' => ''],
            'photo' => UploadedFile::fake()->image('anetari.jpg', 1000, 1000),
            'is_active' => true,
        ])
        ->assertRedirect('/dashboard/team-members')
        ->assertSessionHasNoErrors();

    $teamMember = TeamMember::query()->sole();

    expect($teamMember->name)->toBe('Anëtar Test')
        ->and($teamMember->getTranslation('position', 'sq'))->toBe('Agjent i Shitjeve')
        ->and($teamMember->getTranslations('position'))->toBe(['sq' => 'Agjent i Shitjeve'])
        ->and($teamMember->photo_path)->toStartWith('team/')
        ->and($teamMember->photo_path)->toEndWith('.webp');

    Storage::disk('supabase')->assertExists($teamMember->photo_path);
});

test('an svg photo upload is rejected', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/dashboard/team-members', [
            'name' => 'Anëtar Test',
            'position' => ['sq' => 'Agjent'],
            'photo' => UploadedFile::fake()->create('foto.svg', 10, 'image/svg+xml'),
        ])
        ->assertSessionHasErrors('photo');

    expect(TeamMember::count())->toBe(0);
});

test('creating a team member requires the sq position', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/dashboard/team-members', ['name' => 'Pa pozicion'])
        ->assertSessionHasErrors('position.sq');

    expect(TeamMember::count())->toBe(0);
});

test('an admin can update a team member and replace its photo', function () {
    Storage::fake('supabase');
    $admin = User::factory()->admin()->create();
    $teamMember = TeamMember::factory()->create(['name' => 'Emri i vjetër']);
    $oldPhoto = 'team/old.webp';
    Storage::disk('supabase')->put($oldPhoto, 'x');
    $teamMember->update(['photo_path' => $oldPhoto]);

    $this->actingAs($admin)
        ->put("/dashboard/team-members/{$teamMember->id}", [
            'name' => 'Emri i ri',
            'position' => ['sq' => 'Pozicion i ri', 'en' => '', 'de' => ''],
            'photo' => UploadedFile::fake()->image('e-re.jpg'),
            'is_active' => true,
        ])
        ->assertRedirect('/dashboard/team-members')
        ->assertSessionHasNoErrors();

    $teamMember->refresh();

    expect($teamMember->name)->toBe('Emri i ri')
        ->and($teamMember->photo_path)->not->toBe($oldPhoto);

    Storage::disk('supabase')->assertMissing($oldPhoto);
    Storage::disk('supabase')->assertExists($teamMember->photo_path);
});

test('an admin can delete a team member and its photo', function () {
    Storage::fake('supabase');
    $admin = User::factory()->admin()->create();
    $photo = 'team/to-delete.webp';
    Storage::disk('supabase')->put($photo, 'x');
    $teamMember = TeamMember::factory()->create(['photo_path' => $photo]);

    $this->actingAs($admin)
        ->delete("/dashboard/team-members/{$teamMember->id}")
        ->assertRedirect('/dashboard/team-members');

    expect(TeamMember::find($teamMember->id))->toBeNull();
    Storage::disk('supabase')->assertMissing($photo);
});

test('an admin can reorder team members', function () {
    $admin = User::factory()->admin()->create();
    [$first, $second, $third] = TeamMember::factory()->count(3)->sequence(
        ['sort_order' => 0],
        ['sort_order' => 1],
        ['sort_order' => 2],
    )->create();

    $this->actingAs($admin)
        ->put('/dashboard/team-members/order', [
            'order' => [$third->id, $first->id, $second->id],
        ])
        ->assertRedirect();

    expect($third->refresh()->sort_order)->toBe(0)
        ->and($first->refresh()->sort_order)->toBe(1)
        ->and($second->refresh()->sort_order)->toBe(2);
});

test('reordering team members rejects ids that do not exist', function () {
    $admin = User::factory()->admin()->create();
    $teamMember = TeamMember::factory()->create();

    $this->actingAs($admin)
        ->put('/dashboard/team-members/order', [
            'order' => [$teamMember->id, 999999],
        ])
        ->assertSessionHasErrors('order.1');
});
