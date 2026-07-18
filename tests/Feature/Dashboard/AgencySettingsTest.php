<?php

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('supabase');
});

// Qasja: vetëm admini i sheh dhe i ndryshon cilësimet

test('an admin can view the settings page', function () {
    $admin = User::factory()->admin()->create();

    Setting::create(['key' => 'agency_name', 'value' => 'Agjencia Demo']);

    $this->actingAs($admin)
        ->get('/dashboard/settings')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard/Settings')
            ->where('settings.agency_name', 'Agjencia Demo'));
});

test('an agent cannot view the settings page', function () {
    $agent = User::factory()->agent()->create();

    $this->actingAs($agent)->get('/dashboard/settings')->assertForbidden();
});

test('a guest is redirected to login from the settings page', function () {
    $this->get('/dashboard/settings')->assertRedirect(route('login'));
});

test('an agent cannot update settings', function () {
    $agent = User::factory()->agent()->create();

    $this->actingAs($agent)
        ->post('/dashboard/settings/branding', [
            'agency_name' => 'Hacked',
            'primary_color' => '#000000',
            'watermark_enabled' => false,
        ])
        ->assertForbidden();
});

// Brendi

test('updating branding persists and invalidates the settings cache', function () {
    $admin = User::factory()->admin()->create();

    Setting::create(['key' => 'agency_name', 'value' => 'Emri i Vjetër']);

    // Prime the cache so the test proves invalidation, not just persistence.
    expect(Setting::get('agency_name'))->toBe('Emri i Vjetër');

    $this->actingAs($admin)
        ->post('/dashboard/settings/branding', [
            'agency_name' => 'Agjencia e Re',
            'primary_color' => '#FF6600',
            'watermark_enabled' => true,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Setting::get('agency_name'))->toBe('Agjencia e Re')
        ->and(Setting::get('primary_color'))->toBe('#FF6600')
        ->and(Setting::get('watermark_enabled'))->toBeTrue();
});

test('uploading a logo stores it on the supabase disk', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/dashboard/settings/branding', [
            'agency_name' => 'Agjencia',
            'primary_color' => '#1d4ed8',
            'watermark_enabled' => false,
            'logo' => UploadedFile::fake()->image('logo.png', 200, 80),
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $path = Setting::get('logo_path');

    expect($path)->toStartWith('branding/');
    Storage::disk('supabase')->assertExists($path);
});

test('uploading a new logo deletes the previously stored file', function () {
    $admin = User::factory()->admin()->create();

    Storage::disk('supabase')->put('branding/old-logo.png', 'old');
    Setting::create(['key' => 'logo_path', 'value' => 'branding/old-logo.png']);

    $this->actingAs($admin)
        ->post('/dashboard/settings/branding', [
            'agency_name' => 'Agjencia',
            'primary_color' => '#1d4ed8',
            'watermark_enabled' => false,
            'logo' => UploadedFile::fake()->image('logo.webp'),
        ])
        ->assertSessionHasNoErrors();

    Storage::disk('supabase')->assertMissing('branding/old-logo.png');
    Storage::disk('supabase')->assertExists(Setting::get('logo_path'));
});

test('an svg upload is rejected', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->from('/dashboard/settings')
        ->post('/dashboard/settings/branding', [
            'agency_name' => 'Agjencia',
            'primary_color' => '#1d4ed8',
            'watermark_enabled' => false,
            'logo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
        ])
        ->assertSessionHasErrors('logo');
});

test('an invalid primary color is rejected', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->from('/dashboard/settings')
        ->post('/dashboard/settings/branding', [
            'agency_name' => 'Agjencia',
            'primary_color' => 'e kuqe',
            'watermark_enabled' => false,
        ])
        ->assertSessionHasErrors('primary_color');
});

// Kontakti

test('updating contact details persists', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put('/dashboard/settings/contact', [
            'phone' => '+38344555666',
            'whatsapp' => '+38344555666',
            'email' => 'zyra@agjencia.com',
            'address' => 'Rr. Garibaldi 1, Prishtinë',
            'office_lat' => 42.6612,
            'office_lng' => 21.1621,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Setting::get('email'))->toBe('zyra@agjencia.com')
        ->and(Setting::get('office_lat'))->toBe(42.6612)
        ->and(Setting::get('office_lng'))->toBe(21.1621);
});

test('out-of-range office coordinates are rejected', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->from('/dashboard/settings')
        ->put('/dashboard/settings/contact', [
            'office_lat' => 200,
            'office_lng' => -500,
        ])
        ->assertSessionHasErrors(['office_lat', 'office_lng']);
});

// Rrjetet sociale

test('updating social links persists', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put('/dashboard/settings/social', [
            'facebook' => 'https://facebook.com/agjencia',
            'instagram' => 'https://instagram.com/agjencia',
            'tiktok' => null,
            'linkedin' => null,
            'youtube' => null,
            'app_store_url' => null,
            'play_store_url' => null,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Setting::get('facebook'))->toBe('https://facebook.com/agjencia')
        ->and(Setting::get('tiktok'))->toBeNull();
});

test('an invalid social url is rejected', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->from('/dashboard/settings')
        ->put('/dashboard/settings/social', [
            'facebook' => 'jo-url',
        ])
        ->assertSessionHasErrors('facebook');
});

// Përmbajtja

test('updating content persists all three locales', function () {
    $admin = User::factory()->admin()->create();

    $content = fn (string $text) => ['sq' => "<p>{$text} sq</p>", 'en' => "<p>{$text} en</p>", 'de' => "<p>{$text} de</p>"];

    $this->actingAs($admin)
        ->put('/dashboard/settings/content', [
            'about_content' => $content('Rreth nesh'),
            'terms_content' => $content('Kushtet'),
            'privacy_content' => $content('Privatësia'),
            'faq_content' => $content('FAQ'),
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Setting::get('about_content'))->toMatchArray([
        'sq' => '<p>Rreth nesh sq</p>',
        'en' => '<p>Rreth nesh en</p>',
        'de' => '<p>Rreth nesh de</p>',
    ])->and(Setting::get('faq_content')['de'])->toBe('<p>FAQ de</p>');
});
