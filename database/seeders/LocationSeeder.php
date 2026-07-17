<?php

namespace Database\Seeders;

use App\Enums\LocationType;
use App\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LocationSeeder extends Seeder
{
    /**
     * All Kosovo municipalities, plus the neighborhoods of Prishtina and
     * Çagllavica under Graçanica. Fushë Kosova is a municipality in its own
     * right — it is NOT a Prishtina neighborhood (see PLAN.md §10).
     */
    public function run(): void
    {
        foreach ($this->municipalities() as [$sq, $en, $lat, $lng]) {
            $this->upsertLocation($sq, $en, $lat, $lng, LocationType::Municipality);
        }

        $prishtina = Location::where('slug', 'prishtine')->firstOrFail();
        foreach ($this->prishtinaNeighborhoods() as [$sq, $lat, $lng]) {
            $this->upsertLocation($sq, $sq, $lat, $lng, LocationType::Neighborhood, $prishtina->id);
        }

        $gracanica = Location::where('slug', 'gracanice')->firstOrFail();
        $this->upsertLocation('Çagllavicë', 'Çagllavicë', 42.6206, 21.1561, LocationType::Neighborhood, $gracanica->id);
    }

    protected function upsertLocation(
        string $sq,
        string $en,
        float $lat,
        float $lng,
        LocationType $type,
        ?int $parentId = null,
    ): void {
        Location::updateOrCreate(
            ['slug' => Str::slug($sq)],
            [
                'parent_id' => $parentId,
                'name' => ['sq' => $sq, 'en' => $en, 'de' => $en],
                'lat' => $lat,
                'lng' => $lng,
                'type' => $type,
            ]
        );
    }

    /**
     * @return list<array{string, string, float, float}>
     */
    protected function municipalities(): array
    {
        return [
            ['Prishtinë', 'Pristina', 42.6629, 21.1655],
            ['Prizren', 'Prizren', 42.2139, 20.7397],
            ['Ferizaj', 'Ferizaj', 42.3702, 21.1483],
            ['Pejë', 'Peja', 42.6593, 20.2887],
            ['Gjakovë', 'Gjakova', 42.3803, 20.4308],
            ['Gjilan', 'Gjilan', 42.4635, 21.4694],
            ['Mitrovicë', 'Mitrovica', 42.8826, 20.8666],
            ['Mitrovica e Veriut', 'North Mitrovica', 42.8914, 20.8660],
            ['Podujevë', 'Podujeva', 42.9111, 21.1933],
            ['Vushtrri', 'Vushtrria', 42.8231, 20.9675],
            ['Suharekë', 'Suhareka', 42.3582, 20.8250],
            ['Rahovec', 'Rahovec', 42.3994, 20.6547],
            ['Drenas', 'Drenas', 42.6236, 20.8939],
            ['Lipjan', 'Lipjan', 42.5217, 21.1258],
            ['Malishevë', 'Malisheva', 42.4822, 20.7458],
            ['Kamenicë', 'Kamenica', 42.5781, 21.5803],
            ['Viti', 'Viti', 42.3214, 21.3583],
            ['Deçan', 'Decan', 42.5403, 20.2875],
            ['Istog', 'Istog', 42.7808, 20.4869],
            ['Klinë', 'Klina', 42.6217, 20.5778],
            ['Skenderaj', 'Skenderaj', 42.7469, 20.7886],
            ['Dragash', 'Dragash', 42.0625, 20.6528],
            ['Fushë Kosovë', 'Fushë Kosova', 42.6389, 21.0961],
            ['Kaçanik', 'Kacanik', 42.2319, 21.2594],
            ['Shtime', 'Shtime', 42.4331, 21.0397],
            ['Obiliq', 'Obiliq', 42.6869, 21.0700],
            ['Junik', 'Junik', 42.4761, 20.2775],
            ['Hani i Elezit', 'Hani i Elezit', 42.1531, 21.2961],
            ['Mamushë', 'Mamusha', 42.3253, 20.7256],
            ['Graçanicë', 'Gracanica', 42.6000, 21.1939],
            ['Ranillug', 'Ranilug', 42.4870, 21.5530],
            ['Partesh', 'Partesh', 42.4022, 21.4336],
            ['Kllokot', 'Kllokot', 42.3672, 21.3744],
            ['Novobërdë', 'Novo Brdo', 42.6167, 21.4333],
            ['Shtërpcë', 'Shterpca', 42.2394, 21.0272],
            ['Zubin Potok', 'Zubin Potok', 42.9147, 20.6897],
            ['Zveçan', 'Zvecan', 42.9075, 20.8397],
            ['Leposaviq', 'Leposavic', 43.1039, 20.8022],
        ];
    }

    /**
     * @return list<array{string, float, float}>
     */
    protected function prishtinaNeighborhoods(): array
    {
        return [
            ['Dardania', 42.6521, 21.1520],
            ['Ulpiana', 42.6553, 21.1601],
            ['Mati 1', 42.6499, 21.1355],
            ['Bregu i Diellit', 42.6479, 21.1745],
            ['Kodra e Diellit', 42.6415, 21.1580],
            ['Sunny Hill', 42.6449, 21.1687],
            ['Arbëria', 42.6714, 21.1489],
            ['Lakrishte', 42.6559, 21.1522],
            ['Qafa', 42.6598, 21.1622],
            ['Veternik', 42.6320, 21.1707],
            ['Kalabria', 42.6469, 21.1350],
            ['Velania', 42.6738, 21.1741],
            ['Taslixhe', 42.6690, 21.1520],
            ['Kodra e Trimave', 42.6772, 21.1662],
        ];
    }
}
