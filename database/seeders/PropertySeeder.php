<?php

namespace Database\Seeders;

use App\Enums\ListingType;
use App\Enums\LocationType;
use App\Enums\PropertyCategory;
use App\Enums\PropertyStatus;
use App\Enums\UserRole;
use App\Models\Feature;
use App\Models\Location;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\User;
use Database\Factories\PropertyFactory;
use Illuminate\Database\Seeder;

class PropertySeeder extends Seeder
{
    public function run(): void
    {
        $agentIds = User::where('role', UserRole::Agent)->pluck('id');
        $municipalities = Location::where('type', LocationType::Municipality)->get();
        $neighborhoods = Location::where('type', LocationType::Neighborhood)->get();
        $featureIds = Feature::pluck('id');

        $imageRows = [];
        $now = now();

        for ($i = 0; $i < 200; $i++) {
            $category = $this->weightedCategory();
            $listingType = $this->listingTypeFor($category);
            $surface = $this->surfaceFor($category);
            $location = fake()->boolean(70) ? $neighborhoods->random() : $municipalities->random();
            $status = $this->weightedStatus($listingType);

            $property = Property::factory()->create([
                'agent_id' => $agentIds->random(),
                'location_id' => $location->id,
                'category' => $category,
                'listing_type' => $listingType,
                'surface_m2' => $surface,
                'title' => $this->title($category, $listingType, $surface, $location),
                'lat' => $location->lat + fake()->randomFloat(7, -0.008, 0.008),
                'lng' => $location->lng + fake()->randomFloat(7, -0.008, 0.008),
                'status' => $status,
                'published_at' => $status === PropertyStatus::Draft
                    ? null
                    : fake()->dateTimeBetween('-8 months'),
            ]);

            $property->features()->attach(
                $featureIds->random(fake()->numberBetween(3, 8))->all()
            );

            $imageCount = fake()->numberBetween(3, 8);
            for ($j = 0; $j < $imageCount; $j++) {
                $seed = "property-{$property->id}-{$j}";
                $imageRows[] = [
                    'property_id' => $property->id,
                    'path' => "https://picsum.photos/seed/{$seed}/1920/1080",
                    'thumbnail_path' => "https://picsum.photos/seed/{$seed}/400/267",
                    'is_primary' => $j === 0,
                    'sort_order' => $j,
                    'alt_text' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($imageRows, 500) as $chunk) {
            PropertyImage::insert($chunk);
        }
    }

    protected function weightedCategory(): PropertyCategory
    {
        $roll = fake()->numberBetween(1, 100);

        return match (true) {
            $roll <= 40 => PropertyCategory::Apartment,
            $roll <= 65 => PropertyCategory::House,
            $roll <= 77 => PropertyCategory::Land,
            $roll <= 85 => PropertyCategory::Office,
            $roll <= 92 => PropertyCategory::Store,
            $roll <= 96 => PropertyCategory::Warehouse,
            default => PropertyCategory::Object,
        };
    }

    protected function listingTypeFor(PropertyCategory $category): ListingType
    {
        if ($category === PropertyCategory::Land) {
            return ListingType::Sale;
        }

        return fake()->boolean(65) ? ListingType::Sale : ListingType::Rent;
    }

    protected function weightedStatus(ListingType $listingType): PropertyStatus
    {
        $roll = fake()->numberBetween(1, 100);

        return match (true) {
            $roll <= 70 => PropertyStatus::Published,
            $roll <= 78 => PropertyStatus::Draft,
            $roll <= 84 => PropertyStatus::Reserved,
            $roll <= 96 => $listingType === ListingType::Sale
                ? PropertyStatus::Sold
                : PropertyStatus::Rented,
            default => PropertyStatus::Archived,
        };
    }

    protected function surfaceFor(PropertyCategory $category): int
    {
        return match ($category) {
            PropertyCategory::Apartment => fake()->numberBetween(35, 140),
            PropertyCategory::House => fake()->numberBetween(80, 400),
            PropertyCategory::Office => fake()->numberBetween(20, 300),
            PropertyCategory::Store => fake()->numberBetween(20, 250),
            PropertyCategory::Land => fake()->numberBetween(200, 5000),
            PropertyCategory::Warehouse => fake()->numberBetween(100, 1500),
            PropertyCategory::Object => fake()->numberBetween(150, 2000),
        };
    }

    /**
     * Titles carry the real neighborhood/municipality name, e.g.
     * "Banesë 75 m² në Dardania".
     *
     * @return array<string, string>
     */
    protected function title(
        PropertyCategory $category,
        ListingType $listingType,
        int $surface,
        Location $location,
    ): array {
        $nouns = PropertyFactory::categoryNouns($category);

        $suffix = $listingType === ListingType::Sale
            ? ['sq' => 'në shitje', 'en' => 'for sale', 'de' => 'zum Verkauf']
            : ['sq' => 'me qira', 'en' => 'for rent', 'de' => 'zur Miete'];

        return [
            'sq' => "{$nouns['sq']} {$surface} m² {$suffix['sq']} në ".$location->getTranslation('name', 'sq'),
            'en' => "{$nouns['en']} {$surface} m² {$suffix['en']} in ".$location->getTranslation('name', 'en'),
            'de' => "{$nouns['de']} {$surface} m² {$suffix['de']} in ".$location->getTranslation('name', 'de'),
        ];
    }
}
