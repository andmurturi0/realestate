<?php

use App\Enums\FeatureGroup;
use App\Enums\PropertyCategory;
use App\Enums\PropertyStatus;
use App\Models\Feature;
use App\Models\Location;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @return list<int>
 */
function listedPropertyIds(TestResponse $response): array
{
    return array_column($response->viewData('page')['props']['properties']['data'], 'id');
}

// Dukshmëria publike

test('the listing shows published properties with card data', function () {
    $property = Property::factory()->published()->create(['title' => ['sq' => 'Banesë testuese']]);
    PropertyImage::factory()->primary()->for($property)->create();

    $this->get('/properties')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/Index')
            ->has('properties.data', 1)
            ->where('properties.data.0.id', $property->id)
            ->where('properties.data.0.title', 'Banesë testuese')
            ->where('properties.data.0.reference_code', $property->reference_code)
            ->where('properties.data.0.price', $property->price)
            ->has('properties.data.0.thumbnail_url')
            ->has('properties.total')
            ->has('locations')
            ->has('furnishingOptions'));
});

test('non-published properties never appear publicly', function (PropertyStatus $status) {
    Property::factory()->create(['status' => $status, 'published_at' => now()]);

    $this->get('/properties')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->has('properties.data', 0));
})->with([
    'draft' => PropertyStatus::Draft,
    'reserved' => PropertyStatus::Reserved,
    'sold' => PropertyStatus::Sold,
    'rented' => PropertyStatus::Rented,
    'archived' => PropertyStatus::Archived,
]);

// Filtrat një nga një

test('filters by listing type', function () {
    $sale = Property::factory()->published()->forSale()->create();
    $rent = Property::factory()->published()->forRent()->create();

    expect(listedPropertyIds($this->get('/properties?type=sale')))->toBe([$sale->id])
        ->and(listedPropertyIds($this->get('/properties?type=rent')))->toBe([$rent->id]);
});

test('filters exclusive properties', function () {
    $exclusive = Property::factory()->published()->exclusive()->create();
    Property::factory()->published()->create(['is_exclusive' => false]);

    expect(listedPropertyIds($this->get('/properties?exclusive=1')))->toBe([$exclusive->id]);
});

test('filters by a single category', function () {
    $apartment = Property::factory()->published()->create(['category' => PropertyCategory::Apartment]);
    Property::factory()->published()->create(['category' => PropertyCategory::House]);

    expect(listedPropertyIds($this->get('/properties?category[]=apartment')))->toBe([$apartment->id]);
});

test('filters by multiple categories at once', function () {
    $apartment = Property::factory()->published()->create(['category' => PropertyCategory::Apartment]);
    $land = Property::factory()->published()->create(['category' => PropertyCategory::Land]);
    Property::factory()->published()->create(['category' => PropertyCategory::House]);

    $ids = listedPropertyIds($this->get('/properties?category[]=apartment&category[]=land'));

    expect($ids)->toHaveCount(2)->toContain($apartment->id, $land->id);
});

test('filters by price range given in euros against prices stored in cents', function () {
    Property::factory()->published()->create(['price' => 5_000_000]);            // €50,000
    $match = Property::factory()->published()->create(['price' => 10_000_000]); // €100,000
    Property::factory()->published()->create(['price' => 20_000_000]);          // €200,000

    expect(listedPropertyIds($this->get('/properties?price_min=60000&price_max=150000')))->toBe([$match->id]);
});

test('filters by surface range', function () {
    Property::factory()->published()->create(['surface_m2' => 40]);
    $match = Property::factory()->published()->create(['surface_m2' => 75]);
    Property::factory()->published()->create(['surface_m2' => 200]);

    expect(listedPropertyIds($this->get('/properties?surface_min=50&surface_max=100')))->toBe([$match->id]);
});

test('filters by an exact neighborhood', function () {
    $neighborhood = Location::factory()->neighborhood()->create();
    $match = Property::factory()->published()->for($neighborhood, 'location')->create();
    Property::factory()->published()->create();

    expect(listedPropertyIds($this->get("/properties?location={$neighborhood->id}")))->toBe([$match->id]);
});

test('filtering by a municipality includes its neighborhoods', function () {
    $municipality = Location::factory()->create();
    $neighborhood = Location::factory()->neighborhood($municipality)->create();

    $inMunicipality = Property::factory()->published()->for($municipality, 'location')->create();
    $inNeighborhood = Property::factory()->published()->for($neighborhood, 'location')->create();
    Property::factory()->published()->create();

    $ids = listedPropertyIds($this->get("/properties?location={$municipality->id}"));

    expect($ids)->toHaveCount(2)->toContain($inMunicipality->id, $inNeighborhood->id);
});

test('filters by an exact number of bedrooms', function () {
    $match = Property::factory()->published()->create(['bedrooms' => 2]);
    Property::factory()->published()->create(['bedrooms' => 3]);

    expect(listedPropertyIds($this->get('/properties?bedrooms=2')))->toBe([$match->id]);
});

test('bedrooms=5 means five or more', function () {
    Property::factory()->published()->create(['bedrooms' => 4]);
    $five = Property::factory()->published()->create(['bedrooms' => 5]);
    $seven = Property::factory()->published()->create(['bedrooms' => 7]);

    $ids = listedPropertyIds($this->get('/properties?bedrooms=5'));

    expect($ids)->toHaveCount(2)->toContain($five->id, $seven->id);
});

test('filters by possession sheet', function () {
    $with = Property::factory()->published()->create(['has_possession_sheet' => true]);
    $without = Property::factory()->published()->create(['has_possession_sheet' => false]);
    Property::factory()->published()->create(['has_possession_sheet' => null]);

    expect(listedPropertyIds($this->get('/properties?possession=with')))->toBe([$with->id])
        ->and(listedPropertyIds($this->get('/properties?possession=without')))->toBe([$without->id]);
});

test('filters by document type', function () {
    $notary = Property::factory()->published()->create(['document_type' => 'notary']);
    $lawyer = Property::factory()->published()->create(['document_type' => 'lawyer']);
    Property::factory()->published()->create(['document_type' => null]);

    expect(listedPropertyIds($this->get('/properties?documents=notary')))->toBe([$notary->id])
        ->and(listedPropertyIds($this->get('/properties?documents=lawyer')))->toBe([$lawyer->id]);
});

test('furnishing matches properties having ANY of the selected keys', function () {
    $salon = Feature::factory()->create(['key' => 'salon', 'group' => FeatureGroup::Furnishing]);
    $kitchen = Feature::factory()->create(['key' => 'kitchen', 'group' => FeatureGroup::Furnishing]);

    $withSalon = Property::factory()->published()->hasAttached($salon, [], 'features')->create();
    $withKitchen = Property::factory()->published()->hasAttached($kitchen, [], 'features')->create();
    Property::factory()->published()->create();

    $ids = listedPropertyIds($this->get('/properties?furnishing[]=salon&furnishing[]=kitchen'));

    expect($ids)->toHaveCount(2)->toContain($withSalon->id, $withKitchen->id);
});

test('the furnishing filter cannot match infrastructure features', function () {
    $garage = Feature::factory()->create(['key' => 'garage', 'group' => FeatureGroup::Infrastructure]);
    Property::factory()->published()->hasAttached($garage, [], 'features')->create();

    $this->get('/properties?furnishing[]=garage')
        ->assertInertia(fn (Assert $page) => $page->has('properties.data', 0));
});

// Sortimi

test('sorts by price, surface and recency', function () {
    $cheapOld = Property::factory()->published()->create([
        'price' => 5_000_000, 'surface_m2' => 200, 'published_at' => now()->subMonths(3),
    ]);
    $expensiveNew = Property::factory()->published()->create([
        'price' => 20_000_000, 'surface_m2' => 50, 'published_at' => now(),
    ]);

    expect(listedPropertyIds($this->get('/properties?sort=price_asc')))->toBe([$cheapOld->id, $expensiveNew->id])
        ->and(listedPropertyIds($this->get('/properties?sort=price_desc')))->toBe([$expensiveNew->id, $cheapOld->id])
        ->and(listedPropertyIds($this->get('/properties?sort=surface_desc')))->toBe([$cheapOld->id, $expensiveNew->id])
        ->and(listedPropertyIds($this->get('/properties')))->toBe([$expensiveNew->id, $cheapOld->id]);
});

// Kombinimet

test('combines rent, category, price range, location and bedrooms', function () {
    $neighborhood = Location::factory()->neighborhood()->create();

    $match = Property::factory()->published()->forRent()->for($neighborhood, 'location')->create([
        'category' => PropertyCategory::Apartment, 'price' => 45_000, 'bedrooms' => 2, // €450/muaj
    ]);
    // Near misses: one attribute off each.
    Property::factory()->published()->forSale()->for($neighborhood, 'location')->create([
        'category' => PropertyCategory::Apartment, 'price' => 45_000, 'bedrooms' => 2,
    ]);
    Property::factory()->published()->forRent()->for($neighborhood, 'location')->create([
        'category' => PropertyCategory::House, 'price' => 45_000, 'bedrooms' => 2,
    ]);
    Property::factory()->published()->forRent()->for($neighborhood, 'location')->create([
        'category' => PropertyCategory::Apartment, 'price' => 90_000, 'bedrooms' => 2,
    ]);
    Property::factory()->published()->forRent()->create([
        'category' => PropertyCategory::Apartment, 'price' => 45_000, 'bedrooms' => 2,
    ]);
    Property::factory()->published()->forRent()->for($neighborhood, 'location')->create([
        'category' => PropertyCategory::Apartment, 'price' => 45_000, 'bedrooms' => 3,
    ]);

    $ids = listedPropertyIds($this->get(
        "/properties?type=rent&category[]=apartment&price_min=300&price_max=600&location={$neighborhood->id}&bedrooms=2"
    ));

    expect($ids)->toBe([$match->id]);
});

test('combines exclusive, type and surface with sorting', function () {
    $small = Property::factory()->published()->forSale()->exclusive()->create(['surface_m2' => 80, 'price' => 8_000_000]);
    $large = Property::factory()->published()->forSale()->exclusive()->create(['surface_m2' => 150, 'price' => 12_000_000]);
    Property::factory()->published()->forSale()->create(['surface_m2' => 120, 'is_exclusive' => false]);
    Property::factory()->published()->forRent()->exclusive()->create(['surface_m2' => 120]);

    expect(listedPropertyIds($this->get('/properties?type=sale&exclusive=1&surface_min=50&sort=surface_desc')))
        ->toBe([$large->id, $small->id]);
});

test('combines possession, documents and furnishing', function () {
    $salon = Feature::factory()->create(['key' => 'salon', 'group' => FeatureGroup::Furnishing]);

    $match = Property::factory()->published()->hasAttached($salon, [], 'features')->create([
        'has_possession_sheet' => true, 'document_type' => 'notary',
    ]);
    Property::factory()->published()->hasAttached($salon, [], 'features')->create([
        'has_possession_sheet' => false, 'document_type' => 'notary',
    ]);
    Property::factory()->published()->create([
        'has_possession_sheet' => true, 'document_type' => 'notary',
    ]);

    expect(listedPropertyIds($this->get('/properties?possession=with&documents=notary&furnishing[]=salon')))
        ->toBe([$match->id]);
});

// Paginimi dhe gjendja në URL

test('pagination preserves the active filters in the URL', function () {
    Property::factory()->published()->forSale()->count(15)->create();
    Property::factory()->published()->forRent()->count(5)->create();

    $response = $this->get('/properties?type=sale&sort=price_asc&page=2');

    $response->assertInertia(fn (Assert $page) => $page
        ->component('properties/Index')
        ->where('properties.current_page', 2)
        ->has('properties.data', 3)
        ->where('properties.total', 15)
        ->where('filters.type', 'sale')
        ->where('filters.sort', 'price_asc'));

    $links = collect($response->viewData('page')['props']['properties']['links'])
        ->pluck('url')
        ->filter();

    expect($links)->not->toBeEmpty()
        ->and($links->every(fn (string $url) => str_contains($url, 'type=sale') && str_contains($url, 'sort=price_asc')))
        ->toBeTrue();
});

test('invalid filter values are ignored instead of erroring', function () {
    Property::factory()->published()->count(2)->create();

    $this->get('/properties?type=bogus&category[]=castle&price_min=abc&bedrooms=99&possession=maybe&sort=hack')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('properties.data', 2)
            ->where('filters', []));
});

// Performanca — zero N+1

test('the query count stays constant whether 1 or 50 properties are listed', function () {
    $makeProperty = function (): void {
        $neighborhood = Location::factory()->neighborhood()->create();
        $property = Property::factory()
            ->published()
            ->for(User::factory()->agent(), 'agent')
            ->for($neighborhood, 'location')
            ->create();
        PropertyImage::factory()->primary()->for($property)->create();
    };

    $countQueries = function (): int {
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->get('/properties')->assertSuccessful();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    };

    $makeProperty();
    $this->get('/properties')->assertSuccessful(); // warm the settings cache

    $queriesForOne = $countQueries();

    for ($i = 0; $i < 49; $i++) {
        $makeProperty();
    }

    $queriesForFifty = $countQueries();

    expect($queriesForFifty)->toBe($queriesForOne);
});
