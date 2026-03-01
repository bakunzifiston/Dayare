<?php

namespace Database\Seeders;

use App\Models\AdministrativeDivision;
use App\Models\Business;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class AdministrativeDivisionSeeder extends Seeder
{
    /**
     * Rwanda administrative divisions from official structure.
     * Fetches from: Rwanda Provinces, Districts, Sectors, Cells, Villages (GitHub)
     */
    private const RWANDA_JSON_URL = 'https://raw.githubusercontent.com/ShejaEddy/Rwanda-Provinces-Districts-Sectors-Cell-Villages/main/data.json';

    private const PROVINCE_NAMES = [
        'East' => 'Eastern Province',
        'Kigali' => 'City of Kigali',
        'North' => 'Northern Province',
        'South' => 'Southern Province',
        'West' => 'Western Province',
    ];

    public function run(): void
    {
        $this->command?->info('Fetching Rwanda administrative data...');

        $response = Http::timeout(30)->get(self::RWANDA_JSON_URL);

        if (!$response->successful()) {
            $this->command?->error('Could not fetch Rwanda data. Seeding minimal structure (country + provinces + districts only).');
            $this->seedMinimalRwanda();
            return;
        }

        $data = $response->json();
        $items = $data['items'] ?? [];

        if (empty($items)) {
            $this->seedMinimalRwanda();
            return;
        }

        // Clear existing divisions and null business FKs to avoid constraint errors
        Business::query()->update([
            'country_id' => null,
            'province_id' => null,
            'district_id' => null,
            'sector_id' => null,
            'cell_id' => null,
            'village_id' => null,
        ]);
        AdministrativeDivision::query()->delete();

        $country = AdministrativeDivision::create([
            'parent_id' => null,
            'name' => 'Rwanda',
            'type' => AdministrativeDivision::TYPE_COUNTRY,
            'code' => 'RW',
        ]);

        foreach ($items as $provinceData) {
            if (($provinceData['type'] ?? '') !== 'PROVINCE') {
                continue;
            }

            $provinceName = self::PROVINCE_NAMES[$provinceData['name'] ?? ''] ?? $provinceData['name'];

            $province = AdministrativeDivision::create([
                'parent_id' => $country->id,
                'name' => $provinceName,
                'type' => AdministrativeDivision::TYPE_PROVINCE,
                'code' => null,
            ]);

            foreach ($provinceData['districts'] ?? [] as $districtData) {
                if (($districtData['type'] ?? '') !== 'DISTRICT') {
                    continue;
                }

                $district = AdministrativeDivision::create([
                    'parent_id' => $province->id,
                    'name' => $districtData['name'],
                    'type' => AdministrativeDivision::TYPE_DISTRICT,
                    'code' => null,
                ]);

                foreach ($districtData['sectors'] ?? [] as $sectorData) {
                    if (($sectorData['type'] ?? '') !== 'SECTORS') {
                        continue;
                    }

                    $sector = AdministrativeDivision::create([
                        'parent_id' => $district->id,
                        'name' => $sectorData['name'],
                        'type' => AdministrativeDivision::TYPE_SECTOR,
                        'code' => null,
                    ]);

                    foreach ($sectorData['cells'] ?? [] as $cellData) {
                        if (($cellData['type'] ?? '') !== 'CELLS') {
                            continue;
                        }

                        $cell = AdministrativeDivision::create([
                            'parent_id' => $sector->id,
                            'name' => $cellData['name'],
                            'type' => AdministrativeDivision::TYPE_CELL,
                            'code' => null,
                        ]);

                        foreach ($cellData['villages'] ?? [] as $villageName) {
                            AdministrativeDivision::create([
                                'parent_id' => $cell->id,
                                'name' => is_string($villageName) ? $villageName : (string) $villageName,
                                'type' => AdministrativeDivision::TYPE_VILLAGE,
                                'code' => null,
                            ]);
                        }
                    }
                }
            }
        }

        $count = AdministrativeDivision::count();
        $this->command?->info("Rwanda administrative divisions seeded: {$count} records.");
    }

    /**
     * Fallback: minimal Rwanda structure (country + 5 provinces + real 30 districts, no sectors/cells/villages).
     */
    private function seedMinimalRwanda(): void
    {
        Business::query()->update([
            'country_id' => null,
            'province_id' => null,
            'district_id' => null,
            'sector_id' => null,
            'cell_id' => null,
            'village_id' => null,
        ]);
        AdministrativeDivision::query()->delete();

        $country = AdministrativeDivision::create([
            'parent_id' => null,
            'name' => 'Rwanda',
            'type' => AdministrativeDivision::TYPE_COUNTRY,
            'code' => 'RW',
        ]);

        $structure = [
            'City of Kigali' => ['Gasabo', 'Kicukiro', 'Nyarugenge'],
            'Eastern Province' => ['Bugesera', 'Gatsibo', 'Kayonza', 'Kirehe', 'Ngoma', 'Nyagatare', 'Rwamagana'],
            'Northern Province' => ['Burera', 'Gakenke', 'Gicumbi', 'Musanze', 'Rulindo'],
            'Southern Province' => ['Gisagara', 'Huye', 'Kamonyi', 'Muhanga', 'Nyamagabe', 'Nyanza', 'Nyaruguru', 'Ruhango'],
            'Western Province' => ['Karongi', 'Ngororero', 'Nyabihu', 'Nyamasheke', 'Rubavu', 'Rusizi', 'Rutsiro'],
        ];

        foreach ($structure as $provinceName => $districts) {
            $province = AdministrativeDivision::create([
                'parent_id' => $country->id,
                'name' => $provinceName,
                'type' => AdministrativeDivision::TYPE_PROVINCE,
                'code' => null,
            ]);
            foreach ($districts as $districtName) {
                AdministrativeDivision::create([
                    'parent_id' => $province->id,
                    'name' => $districtName,
                    'type' => AdministrativeDivision::TYPE_DISTRICT,
                    'code' => null,
                ]);
            }
        }

        $this->command?->info('Minimal Rwanda structure seeded (country + provinces + 30 districts).');
    }
}
