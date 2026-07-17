<?php

namespace Database\Seeders;

use App\Enums\FeatureGroup;
use App\Models\Feature;
use Illuminate\Database\Seeder;

class FeatureSeeder extends Seeder
{
    public function run(): void
    {
        $infrastructure = [
            ['street', 'route', ['sq' => 'Rruga', 'en' => 'Street', 'de' => 'Straße']],
            ['water', 'droplets', ['sq' => 'Uji', 'en' => 'Water', 'de' => 'Wasser']],
            ['wastewater', 'waves', ['sq' => 'Kanalizimi', 'en' => 'Sewage', 'de' => 'Kanalisation']],
            ['keds', 'zap', ['sq' => 'Rryma (KEDS)', 'en' => 'Electricity (KEDS)', 'de' => 'Strom (KEDS)']],
            ['elevator', 'arrow-up-down', ['sq' => 'Ashensori', 'en' => 'Elevator', 'de' => 'Aufzug']],
            ['central_heating', 'flame', ['sq' => 'Ngrohja qendrore', 'en' => 'Central heating', 'de' => 'Zentralheizung']],
            ['balcony', 'sun', ['sq' => 'Ballkoni', 'en' => 'Balcony', 'de' => 'Balkon']],
            ['garage', 'car', ['sq' => 'Garazha', 'en' => 'Garage', 'de' => 'Garage']],
            ['basement', 'layers', ['sq' => 'Bodrumi', 'en' => 'Basement', 'de' => 'Keller']],
        ];

        $furnishing = [
            ['salon', 'sofa', ['sq' => 'Salloni', 'en' => 'Living room', 'de' => 'Wohnzimmer']],
            ['bedroom', 'bed', ['sq' => 'Dhoma e gjumit', 'en' => 'Bedroom', 'de' => 'Schlafzimmer']],
            ['kitchen', 'utensils', ['sq' => 'Kuzhina', 'en' => 'Kitchen', 'de' => 'Küche']],
            ['bathroom', 'bath', ['sq' => 'Banjo', 'en' => 'Bathroom', 'de' => 'Badezimmer']],
            ['unfurnished', 'package-open', ['sq' => 'Pa mobilje', 'en' => 'Unfurnished', 'de' => 'Unmöbliert']],
        ];

        $this->seedGroup($infrastructure, FeatureGroup::Infrastructure);
        $this->seedGroup($furnishing, FeatureGroup::Furnishing);
    }

    /**
     * @param  list<array{string, string, array<string, string>}>  $features
     */
    protected function seedGroup(array $features, FeatureGroup $group): void
    {
        foreach ($features as $sortOrder => [$key, $icon, $name]) {
            Feature::updateOrCreate(
                ['key' => $key],
                [
                    'name' => $name,
                    'icon' => $icon,
                    'group' => $group,
                    'sort_order' => $sortOrder,
                ]
            );
        }
    }
}
