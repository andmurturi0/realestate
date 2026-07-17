<?php

namespace Database\Factories;

use App\Enums\DocumentType;
use App\Enums\ListingType;
use App\Enums\PropertyCategory;
use App\Enums\PropertyStatus;
use App\Models\Location;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Dependent attributes (price, surface, rooms, title) are closures so that
 * overriding category or listing_type keeps every derived value consistent.
 *
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agent_id' => User::factory(),
            'location_id' => Location::factory(),
            'category' => fake()->randomElement(PropertyCategory::cases()),
            'listing_type' => fn (array $attributes) => $this->listingTypeFor($attributes['category']),
            'surface_m2' => fn (array $attributes) => $this->surfaceFor($attributes['category']),
            'title' => fn (array $attributes) => $this->titleFor(
                $attributes['category'], $attributes['listing_type'], $attributes['surface_m2']
            ),
            'description' => fn (array $attributes) => $this->descriptionFor($attributes['category']),
            'is_exclusive' => fake()->boolean(15),
            'price' => fn (array $attributes) => $this->priceCentsFor(
                $attributes['category'], $attributes['listing_type']
            ),
            'price_negotiable' => fake()->boolean(30),
            'land_surface_m2' => fn (array $attributes) => match ($attributes['category']) {
                PropertyCategory::House => fake()->numberBetween(100, 800),
                PropertyCategory::Land => $attributes['surface_m2'],
                default => null,
            },
            'bedrooms' => fn (array $attributes) => $this->isResidential($attributes['category'])
                ? fake()->numberBetween(1, 5)
                : null,
            'bathrooms' => fn (array $attributes) => $this->isResidential($attributes['category'])
                ? fake()->numberBetween(1, 3)
                : null,
            'floor' => fn (array $attributes) => $attributes['category'] === PropertyCategory::Apartment
                ? fake()->numberBetween(0, 12)
                : null,
            'total_floors' => fn (array $attributes) => match ($attributes['category']) {
                PropertyCategory::Apartment => fake()->numberBetween(4, 14),
                PropertyCategory::House => fake()->numberBetween(1, 3),
                default => null,
            },
            'year_built' => fn (array $attributes) => $attributes['category'] === PropertyCategory::Land
                ? null
                : fake()->numberBetween(1975, 2025),
            'parking_spaces' => fn (array $attributes) => $attributes['category'] === PropertyCategory::Land
                ? null
                : fake()->optional(0.6)->numberBetween(1, 3),
            'address_line' => fake()->optional(0.5)->streetName(),
            'lat' => 42.6629 + fake()->randomFloat(7, -0.05, 0.05),
            'lng' => 21.1655 + fake()->randomFloat(7, -0.05, 0.05),
            'has_possession_sheet' => fake()->optional(0.8)->boolean(85),
            'document_type' => fake()->optional(0.7)->randomElement(DocumentType::cases()),
            'status' => PropertyStatus::Published,
            'published_at' => fake()->dateTimeBetween('-8 months'),
            'views_count' => fake()->numberBetween(0, 2500),
            'is_featured' => fake()->boolean(10),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PropertyStatus::Published,
            'published_at' => fake()->dateTimeBetween('-8 months'),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PropertyStatus::Draft,
            'published_at' => null,
        ]);
    }

    public function sold(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PropertyStatus::Sold,
            'listing_type' => ListingType::Sale,
        ]);
    }

    public function rented(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PropertyStatus::Rented,
            'listing_type' => ListingType::Rent,
        ]);
    }

    public function exclusive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_exclusive' => true,
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    public function forSale(): static
    {
        return $this->state(fn (array $attributes) => [
            'listing_type' => ListingType::Sale,
        ]);
    }

    public function forRent(): static
    {
        return $this->state(fn (array $attributes) => [
            'listing_type' => ListingType::Rent,
        ]);
    }

    protected function isResidential(PropertyCategory $category): bool
    {
        return in_array($category, [PropertyCategory::House, PropertyCategory::Apartment], true);
    }

    protected function listingTypeFor(PropertyCategory $category): ListingType
    {
        if ($category === PropertyCategory::Land) {
            return ListingType::Sale;
        }

        return fake()->boolean(70) ? ListingType::Sale : ListingType::Rent;
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
     * Realistic prices in integer cents. Sale prices round to €500,
     * rents to €10, so listings look believable.
     */
    protected function priceCentsFor(PropertyCategory $category, ListingType $listingType): int
    {
        if ($listingType === ListingType::Rent) {
            [$min, $max] = match ($category) {
                PropertyCategory::House => [400, 900],
                PropertyCategory::Apartment => [250, 900],
                PropertyCategory::Office => [150, 1200],
                PropertyCategory::Store => [200, 1500],
                PropertyCategory::Warehouse => [300, 2000],
                PropertyCategory::Object => [500, 3000],
                PropertyCategory::Land => [100, 500],
            };

            return fake()->numberBetween(intdiv($min, 10), intdiv($max, 10)) * 10 * 100;
        }

        [$min, $max] = match ($category) {
            PropertyCategory::Apartment => [70_000, 180_000],
            PropertyCategory::House => [150_000, 400_000],
            PropertyCategory::Land => [15_000, 90_000],
            PropertyCategory::Office => [50_000, 250_000],
            PropertyCategory::Store => [30_000, 200_000],
            PropertyCategory::Warehouse => [60_000, 300_000],
            PropertyCategory::Object => [100_000, 500_000],
        };

        return fake()->numberBetween(intdiv($min, 500), intdiv($max, 500)) * 500 * 100;
    }

    /**
     * @return array<string, string>
     */
    protected function titleFor(PropertyCategory $category, ListingType $listingType, float $surface): array
    {
        $surface = number_format($surface, 0);
        $nouns = self::categoryNouns($category);

        $suffix = $listingType === ListingType::Sale
            ? ['sq' => 'në shitje', 'en' => 'for sale', 'de' => 'zu verkaufen']
            : ['sq' => 'me qira', 'en' => 'for rent', 'de' => 'zu vermieten'];

        return [
            'sq' => "{$nouns['sq']} {$surface} m² {$suffix['sq']}",
            'en' => "{$nouns['en']} {$surface} m² {$suffix['en']}",
            'de' => "{$nouns['de']} {$surface} m² {$suffix['de']}",
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function descriptionFor(PropertyCategory $category): array
    {
        $nouns = self::categoryNouns($category);

        return [
            'sq' => "{$nouns['sq']} në gjendje shumë të mirë, në pozitë të shkëlqyer me qasje të lehtë. Dokumentacioni i rregullt. Kontaktoni për më shumë informata dhe për të caktuar një vizitë.",
            'en' => "{$nouns['en']} in very good condition, in an excellent location with easy access. Documentation in order. Contact us for more information and to arrange a viewing.",
            'de' => "{$nouns['de']} in sehr gutem Zustand, in ausgezeichneter Lage mit guter Anbindung. Unterlagen vollständig. Kontaktieren Sie uns für weitere Informationen und einen Besichtigungstermin.",
        ];
    }

    /**
     * Shared with PropertySeeder so seeded titles stay consistent.
     *
     * @return array<string, string>
     */
    public static function categoryNouns(PropertyCategory $category): array
    {
        return match ($category) {
            PropertyCategory::House => ['sq' => 'Shtëpi', 'en' => 'House', 'de' => 'Haus'],
            PropertyCategory::Apartment => ['sq' => 'Banesë', 'en' => 'Apartment', 'de' => 'Wohnung'],
            PropertyCategory::Office => ['sq' => 'Zyrë', 'en' => 'Office', 'de' => 'Büro'],
            PropertyCategory::Store => ['sq' => 'Lokal', 'en' => 'Store', 'de' => 'Ladenlokal'],
            PropertyCategory::Land => ['sq' => 'Tokë', 'en' => 'Land', 'de' => 'Grundstück'],
            PropertyCategory::Warehouse => ['sq' => 'Depo', 'en' => 'Warehouse', 'de' => 'Lagerhalle'],
            PropertyCategory::Object => ['sq' => 'Objekt', 'en' => 'Building', 'de' => 'Objekt'],
        };
    }
}
