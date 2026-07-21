<?php

use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

// Qasja

test('guests are redirected to login from the testimonial list', function () {
    $this->get('/dashboard/testimonials')->assertRedirect('/login');
});

test('an agent cannot view the testimonial list', function () {
    $agent = User::factory()->agent()->create();

    $this->actingAs($agent)->get('/dashboard/testimonials')->assertForbidden();
});

test('an agent cannot create a testimonial', function () {
    $agent = User::factory()->agent()->create();

    $this->actingAs($agent)
        ->post('/dashboard/testimonials', [
            'author_name' => 'Klient Test',
            'quote' => ['sq' => 'Shërbim shumë i mirë.'],
        ])
        ->assertForbidden();

    expect(Testimonial::count())->toBe(0);
});

// CRUD

test('an admin can view the testimonial list', function () {
    Testimonial::factory()->create(['author_name' => 'Klienti Parë']);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/dashboard/testimonials')
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard/testimonials/Index')
            ->has('testimonials', 1)
            ->where('testimonials.0.author_name', 'Klienti Parë'));
});

test('an admin can create a testimonial with a photo', function () {
    Storage::fake('supabase');
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/dashboard/testimonials', [
            'author_name' => 'Klient Test',
            'company' => 'Kompania Test',
            'quote' => ['sq' => 'Shërbim shumë i mirë.', 'en' => '', 'de' => ''],
            'photo' => UploadedFile::fake()->image('klienti.jpg', 1000, 1000),
            'is_active' => true,
        ])
        ->assertRedirect('/dashboard/testimonials')
        ->assertSessionHasNoErrors();

    $testimonial = Testimonial::query()->sole();

    expect($testimonial->author_name)->toBe('Klient Test')
        ->and($testimonial->company)->toBe('Kompania Test')
        ->and($testimonial->getTranslation('quote', 'sq'))->toBe('Shërbim shumë i mirë.')
        ->and($testimonial->getTranslations('quote'))->toBe(['sq' => 'Shërbim shumë i mirë.'])
        ->and($testimonial->photo_path)->toStartWith('testimonials/')
        ->and($testimonial->photo_path)->toEndWith('.webp');

    Storage::disk('supabase')->assertExists($testimonial->photo_path);
});

test('an svg photo upload is rejected', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/dashboard/testimonials', [
            'author_name' => 'Klient Test',
            'quote' => ['sq' => 'Shërbim shumë i mirë.'],
            'photo' => UploadedFile::fake()->create('foto.svg', 10, 'image/svg+xml'),
        ])
        ->assertSessionHasErrors('photo');

    expect(Testimonial::count())->toBe(0);
});

test('creating a testimonial requires the sq quote', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/dashboard/testimonials', ['author_name' => 'Pa citim'])
        ->assertSessionHasErrors('quote.sq');

    expect(Testimonial::count())->toBe(0);
});

test('an admin can update a testimonial and replace its photo', function () {
    Storage::fake('supabase');
    $admin = User::factory()->admin()->create();
    $testimonial = Testimonial::factory()->create(['author_name' => 'Emri i vjetër']);
    $oldPhoto = 'testimonials/old.webp';
    Storage::disk('supabase')->put($oldPhoto, 'x');
    $testimonial->update(['photo_path' => $oldPhoto]);

    $this->actingAs($admin)
        ->put("/dashboard/testimonials/{$testimonial->id}", [
            'author_name' => 'Emri i ri',
            'quote' => ['sq' => 'Citim i përditësuar.', 'en' => '', 'de' => ''],
            'photo' => UploadedFile::fake()->image('e-re.jpg'),
            'is_active' => true,
        ])
        ->assertRedirect('/dashboard/testimonials')
        ->assertSessionHasNoErrors();

    $testimonial->refresh();

    expect($testimonial->author_name)->toBe('Emri i ri')
        ->and($testimonial->photo_path)->not->toBe($oldPhoto);

    Storage::disk('supabase')->assertMissing($oldPhoto);
    Storage::disk('supabase')->assertExists($testimonial->photo_path);
});

test('an admin can delete a testimonial and its photo', function () {
    Storage::fake('supabase');
    $admin = User::factory()->admin()->create();
    $photo = 'testimonials/to-delete.webp';
    Storage::disk('supabase')->put($photo, 'x');
    $testimonial = Testimonial::factory()->create(['photo_path' => $photo]);

    $this->actingAs($admin)
        ->delete("/dashboard/testimonials/{$testimonial->id}")
        ->assertRedirect('/dashboard/testimonials');

    expect(Testimonial::find($testimonial->id))->toBeNull();
    Storage::disk('supabase')->assertMissing($photo);
});

test('an admin can reorder testimonials', function () {
    $admin = User::factory()->admin()->create();
    [$first, $second, $third] = Testimonial::factory()->count(3)->sequence(
        ['sort_order' => 0],
        ['sort_order' => 1],
        ['sort_order' => 2],
    )->create();

    $this->actingAs($admin)
        ->put('/dashboard/testimonials/order', [
            'order' => [$third->id, $first->id, $second->id],
        ])
        ->assertRedirect();

    expect($third->refresh()->sort_order)->toBe(0)
        ->and($first->refresh()->sort_order)->toBe(1)
        ->and($second->refresh()->sort_order)->toBe(2);
});

test('reordering testimonials rejects ids that do not exist', function () {
    $admin = User::factory()->admin()->create();
    $testimonial = Testimonial::factory()->create();

    $this->actingAs($admin)
        ->put('/dashboard/testimonials/order', [
            'order' => [$testimonial->id, 999999],
        ])
        ->assertSessionHasErrors('order.1');
});
