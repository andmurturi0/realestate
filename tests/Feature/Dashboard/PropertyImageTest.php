<?php

use App\Models\Location;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\Setting;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * A real, deterministic PNG upload — two calls with the same arguments
 * produce byte-identical files, which the watermark test relies on.
 */
function imageUpload(string $name = 'foto.png', int $width = 1200, int $height = 800): UploadedFile
{
    $canvas = imagecreatetruecolor($width, $height);
    imagefill($canvas, 0, 0, imagecolorallocate($canvas, 90, 140, 200));

    $path = tempnam(sys_get_temp_dir(), 'upl');
    imagepng($canvas, $path);

    return new UploadedFile($path, $name, 'image/png', null, true);
}

/**
 * A real UploadedFile whose mime type is guessed from the CONTENT via
 * fileinfo — unlike UploadedFile::fake()->createWithContent(), whose
 * Testing\File guesses from the extension and would bypass the very
 * check these tests exist to prove.
 */
function uploadWithContent(string $name, string $content): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'upl');
    file_put_contents($path, $content);

    return new UploadedFile($path, $name, null, null, true);
}

/**
 * A file whose content is PHP code but whose name claims to be a photo.
 */
function renamedPhpUpload(): UploadedFile
{
    return uploadWithContent('shell.jpg', "<?php\necho 'jo foto';\n");
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function minimalPropertyPayload(array $overrides = []): array
{
    return array_merge([
        'listing_type' => 'sale',
        'category' => 'apartment',
        'status' => 'draft',
        'title' => ['sq' => 'Banesë me foto', 'en' => '', 'de' => ''],
        'description' => ['sq' => 'Përshkrim testues.', 'en' => '', 'de' => ''],
        'price' => 99000,
        'surface_m2' => 80,
        'location_id' => Location::factory()->create()->id,
        'lat' => 42.66,
        'lng' => 21.16,
        'features' => [],
    ], $overrides);
}

// Ngarkimi

test('an agent can upload an image to their own property', function () {
    Storage::fake('supabase');
    $agent = User::factory()->agent()->create();
    $property = Property::factory()->for($agent, 'agent')->create();

    $this->actingAs($agent)
        ->post("/dashboard/properties/{$property->id}/images", [
            'image' => imageUpload('foto.png', 2500, 1500),
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $image = $property->images()->sole();

    expect($image->is_primary)->toBeTrue()
        ->and($image->path)->toStartWith("properties/{$property->id}/")
        ->and($image->path)->toEndWith('.webp')
        ->and($image->thumbnail_path)->toEndWith('_thumb.webp');

    Storage::disk('supabase')->assertExists($image->path);
    Storage::disk('supabase')->assertExists($image->thumbnail_path);

    $large = imagecreatefromstring(Storage::disk('supabase')->get($image->path));
    $thumbnail = imagecreatefromstring(Storage::disk('supabase')->get($image->thumbnail_path));

    expect(imagesx($large))->toBe(1920)
        ->and(imagesx($thumbnail))->toBe(400);
});

test('a php script renamed to .jpg is rejected by the real mime check', function () {
    Storage::fake('supabase');
    $agent = User::factory()->agent()->create();
    $property = Property::factory()->for($agent, 'agent')->create();

    $this->actingAs($agent)
        ->from("/dashboard/properties/{$property->id}/edit")
        ->post("/dashboard/properties/{$property->id}/images", [
            'image' => renamedPhpUpload(),
        ])
        ->assertSessionHasErrors('image');

    expect($property->images()->count())->toBe(0);
});

test('svg uploads are rejected with a clear message', function (string $filename) {
    Storage::fake('supabase');
    $agent = User::factory()->agent()->create();
    $property = Property::factory()->for($agent, 'agent')->create();

    $svg = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"><rect width="10" height="10"/></svg>';

    $this->actingAs($agent)
        ->post("/dashboard/properties/{$property->id}/images", [
            'image' => uploadWithContent($filename, $svg),
        ])
        ->assertSessionHasErrors(['image' => 'Fotot SVG nuk lejohen.']);

    expect($property->images()->count())->toBe(0);
})->with([
    'honest extension' => 'logo.svg',
    'renamed to jpg' => 'logo.jpg',
]);

test('images over 8MB are rejected', function () {
    Storage::fake('supabase');
    $agent = User::factory()->agent()->create();
    $property = Property::factory()->for($agent, 'agent')->create();

    $this->actingAs($agent)
        ->post("/dashboard/properties/{$property->id}/images", [
            'image' => UploadedFile::fake()->image('e-madhe.jpg')->size(9000),
        ])
        ->assertSessionHasErrors(['image' => 'Fotoja nuk mund të jetë më e madhe se 8MB.']);
});

test('a property cannot have more than 30 images', function () {
    Storage::fake('supabase');
    $agent = User::factory()->agent()->create();
    $property = Property::factory()->for($agent, 'agent')->create();
    PropertyImage::factory()->count(30)->for($property)->create();

    $this->actingAs($agent)
        ->post("/dashboard/properties/{$property->id}/images", [
            'image' => imageUpload(),
        ])
        ->assertSessionHasErrors('image');

    expect($property->images()->count())->toBe(30);
});

test('an agent cannot upload images to another agents property', function () {
    Storage::fake('supabase');
    $agent = User::factory()->agent()->create();
    $property = Property::factory()->create();

    $this->actingAs($agent)
        ->post("/dashboard/properties/{$property->id}/images", ['image' => imageUpload()])
        ->assertForbidden();
});

test('an admin can upload images to any property', function () {
    Storage::fake('supabase');
    $admin = User::factory()->admin()->create();
    $property = Property::factory()->create();

    $this->actingAs($admin)
        ->post("/dashboard/properties/{$property->id}/images", ['image' => imageUpload()])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($property->images()->count())->toBe(1);
});

// Rirenditja

test('drag reordering persists sort_order', function () {
    $agent = User::factory()->agent()->create();
    $property = Property::factory()->for($agent, 'agent')->create();
    [$first, $second, $third] = PropertyImage::factory()
        ->count(3)
        ->for($property)
        ->sequence(['sort_order' => 0], ['sort_order' => 1], ['sort_order' => 2])
        ->create();

    $this->actingAs($agent)
        ->put("/dashboard/properties/{$property->id}/images/order", [
            'order' => [$third->id, $first->id, $second->id],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($third->refresh()->sort_order)->toBe(0)
        ->and($first->refresh()->sort_order)->toBe(1)
        ->and($second->refresh()->sort_order)->toBe(2);
});

test('reordering rejects image ids that belong to another property', function () {
    $agent = User::factory()->agent()->create();
    $property = Property::factory()->for($agent, 'agent')->create();
    $mine = PropertyImage::factory()->for($property)->create();
    $foreign = PropertyImage::factory()->create();

    $this->actingAs($agent)
        ->put("/dashboard/properties/{$property->id}/images/order", [
            'order' => [$mine->id, $foreign->id],
        ])
        ->assertSessionHasErrors('order.1');
});

// Fotoja primare

test('setting a primary image demotes every other image of the property', function () {
    $agent = User::factory()->agent()->create();
    $property = Property::factory()->for($agent, 'agent')->create();
    $old = PropertyImage::factory()->for($property)->primary()->create();
    $new = PropertyImage::factory()->for($property)->create(['is_primary' => false]);

    $this->actingAs($agent)
        ->put("/dashboard/properties/{$property->id}/images/{$new->id}/primary")
        ->assertRedirect();

    expect($new->refresh()->is_primary)->toBeTrue()
        ->and($old->refresh()->is_primary)->toBeFalse()
        ->and($property->images()->where('is_primary', true)->count())->toBe(1);
});

test('the model enforces a single primary even when set directly', function () {
    $property = Property::factory()->create();
    $first = PropertyImage::factory()->for($property)->create();

    expect($first->refresh()->is_primary)->toBeTrue();

    $second = PropertyImage::factory()->for($property)->primary()->create();

    expect($second->is_primary)->toBeTrue()
        ->and($first->refresh()->is_primary)->toBeFalse()
        ->and($property->images()->where('is_primary', true)->count())->toBe(1);
});

// Fshirja

test('deleting an image removes its files and reassigns the primary flag', function () {
    Storage::fake('supabase');
    $agent = User::factory()->agent()->create();
    $property = Property::factory()->for($agent, 'agent')->create();

    $this->actingAs($agent)
        ->post("/dashboard/properties/{$property->id}/images", ['image' => imageUpload('a.png')]);
    $this->post("/dashboard/properties/{$property->id}/images", ['image' => imageUpload('b.png')]);

    [$primary, $other] = $property->images()->orderBy('sort_order')->get();
    expect($primary->is_primary)->toBeTrue();

    $this->delete("/dashboard/properties/{$property->id}/images/{$primary->id}")
        ->assertRedirect();

    Storage::disk('supabase')->assertMissing($primary->path);
    Storage::disk('supabase')->assertMissing($primary->thumbnail_path);

    expect(PropertyImage::find($primary->id))->toBeNull()
        ->and($other->refresh()->is_primary)->toBeTrue();
});

test('an agent cannot delete images of another agents property', function () {
    $agent = User::factory()->agent()->create();
    $image = PropertyImage::factory()->create();

    $this->actingAs($agent)
        ->delete("/dashboard/properties/{$image->property_id}/images/{$image->id}")
        ->assertForbidden();

    expect(PropertyImage::find($image->id))->not->toBeNull();
});

// Ngarkimet në formularin e krijimit (pending)

test('images uploaded before the property exists attach on save', function () {
    Storage::fake('supabase');
    $agent = User::factory()->agent()->create();

    $this->actingAs($agent)
        ->post('/dashboard/pending-images', ['image' => imageUpload('a.png')])
        ->assertRedirect()
        ->assertSessionHasNoErrors();
    $this->post('/dashboard/pending-images', ['image' => imageUpload('b.png')]);

    $pending = session(ImageService::PENDING_SESSION_KEY);
    expect($pending)->toHaveCount(2);
    Storage::disk('supabase')->assertExists($pending[0]['path']);

    $this->post('/dashboard/properties', minimalPropertyPayload())
        ->assertRedirect('/dashboard/properties');

    $property = Property::query()->latest('id')->firstOrFail();
    $images = $property->images()->orderBy('sort_order')->get();

    expect($images)->toHaveCount(2)
        ->and($images[0]->is_primary)->toBeTrue()
        ->and($images[1]->is_primary)->toBeFalse()
        ->and(session(ImageService::PENDING_SESSION_KEY))->toBeNull();

    foreach ($images as $image) {
        expect($image->path)->toStartWith("properties/{$property->id}/");
        Storage::disk('supabase')->assertExists($image->path);
        Storage::disk('supabase')->assertExists($image->thumbnail_path);
    }

    Storage::disk('supabase')->assertMissing($pending[0]['path']);
    Storage::disk('supabase')->assertMissing($pending[1]['path']);
});

test('a pending upload can be removed before the property is saved', function () {
    Storage::fake('supabase');
    $agent = User::factory()->agent()->create();

    $this->actingAs($agent)->post('/dashboard/pending-images', ['image' => imageUpload()]);

    $entry = session(ImageService::PENDING_SESSION_KEY)[0];

    $this->delete("/dashboard/pending-images/{$entry['id']}")->assertRedirect();

    expect(session(ImageService::PENDING_SESSION_KEY))->toBeEmpty();
    Storage::disk('supabase')->assertMissing($entry['path']);
    Storage::disk('supabase')->assertMissing($entry['thumbnail_path']);
});

test('pending uploads validate the real mime type too', function () {
    Storage::fake('supabase');
    $agent = User::factory()->agent()->create();

    $this->actingAs($agent)
        ->post('/dashboard/pending-images', ['image' => renamedPhpUpload()])
        ->assertSessionHasErrors('image');

    expect(session(ImageService::PENDING_SESSION_KEY, []))->toBeEmpty();
});

// Watermark

test('the watermark lands on the large image but never on the thumbnail', function () {
    Storage::fake('supabase');
    $agent = User::factory()->agent()->create();
    $property = Property::factory()->for($agent, 'agent')->create();

    $logo = imagecreatetruecolor(200, 100);
    imagefill($logo, 0, 0, imagecolorallocate($logo, 220, 40, 40));
    ob_start();
    imagepng($logo);
    Storage::disk('supabase')->put('branding/logo.png', ob_get_clean());

    Setting::updateOrCreate(['key' => 'logo_path'], ['value' => 'branding/logo.png']);
    Setting::updateOrCreate(['key' => 'watermark_enabled'], ['value' => true]);

    $this->actingAs($agent)
        ->post("/dashboard/properties/{$property->id}/images", ['image' => imageUpload()])
        ->assertSessionHasNoErrors();

    Setting::updateOrCreate(['key' => 'watermark_enabled'], ['value' => false]);

    $this->post("/dashboard/properties/{$property->id}/images", ['image' => imageUpload()])
        ->assertSessionHasNoErrors();

    [$watermarked, $plain] = $property->images()->orderBy('sort_order')->get();
    $disk = Storage::disk('supabase');

    expect($disk->get($watermarked->path))->not->toBe($disk->get($plain->path))
        ->and($disk->get($watermarked->thumbnail_path))->toBe($disk->get($plain->thumbnail_path));
});
