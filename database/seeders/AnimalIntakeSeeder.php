<?php

namespace Database\Seeders;

use App\Models\AdministrativeDivision;
use App\Models\AnimalIntake;
use App\Models\Facility;
use Illuminate\Database\Seeder;

/**
 * Seed animal intakes for slaughterhouse facilities — Rwanda suppliers, farms, locations.
 */
class AnimalIntakeSeeder extends Seeder
{
    private const RWANDA_SUPPLIERS = [
        ['first' => 'Jean Baptiste', 'last' => 'Habyarimana', 'farm' => 'Ubworozi bwa Gikondo'],
        ['first' => 'Marie Claire', 'last' => 'Murekatete', 'farm' => 'Icyerekezo Farm'],
        ['first' => 'Patrick', 'last' => 'Niyonsenga', 'farm' => 'Ingenzi Livestock Co-op'],
        ['first' => 'Uwera', 'last' => 'Mukandori', 'farm' => 'Nyagatare Cattle Co.'],
        ['first' => 'Emmanuel', 'last' => 'Nsengimana', 'farm' => 'Rwanda Beef Suppliers'],
    ];

    public function run(): void
    {
        $slaughterhouses = Facility::where('facility_type', Facility::TYPE_SLAUGHTER_HOUSE)->get();
        if ($slaughterhouses->isEmpty()) {
            $this->command?->warn('No slaughterhouse facilities. Run TestDataSeeder first.');
            return;
        }

        $country = AdministrativeDivision::ofType(AdministrativeDivision::TYPE_COUNTRY)->first();
        $provinces = $country ? AdministrativeDivision::byParent($country->id)->orderBy('name')->get() : collect();
        $province = $provinces->first();
        $district = $province ? AdministrativeDivision::byParent($province->id)->orderBy('name')->first() : null;
        $sector = $district ? AdministrativeDivision::byParent($district->id)->orderBy('name')->first() : null;
        $cell = $sector ? AdministrativeDivision::byParent($sector->id)->orderBy('name')->first() : null;
        $village = $cell ? AdministrativeDivision::byParent($cell->id)->orderBy('name')->first() : null;

        $species = [AnimalIntake::SPECIES_CATTLE, AnimalIntake::SPECIES_GOAT, AnimalIntake::SPECIES_SHEEP];
        $index = 0;
        foreach ($slaughterhouses as $facility) {
            $businessId = $facility->business_id;
            $supplier = \App\Models\Supplier::where('business_id', $businessId)->where('supplier_status', \App\Models\Supplier::STATUS_APPROVED)->inRandomOrder()->first();
            $contract = \App\Models\Contract::where('business_id', $businessId)->where('contract_category', \App\Models\Contract::CATEGORY_SUPPLIER)->where('status', \App\Models\Contract::STATUS_ACTIVE)->inRandomOrder()->first();

            for ($i = 0; $i < 2; $i++) {
                $supplierRow = self::RWANDA_SUPPLIERS[$index % count(self::RWANDA_SUPPLIERS)];
                $index++;
                $intakeDate = now()->subDays(rand(5, 25));
                $certIssue = $intakeDate->copy()->subDays(10);
                $certExpiry = $intakeDate->copy()->addMonths(3);
                $base = [
                    'facility_id' => $facility->id,
                    'intake_date' => $intakeDate->format('Y-m-d'),
                    'supplier_firstname' => $supplierRow['first'],
                    'supplier_lastname' => $supplierRow['last'],
                    'farm_name' => $supplierRow['farm'],
                ];
                $attrs = [
                    'supplier_id' => $supplier?->id,
                    'contract_id' => $contract?->id,
                    'supplier_contact' => '+250788' . random_int(100000, 999999),
                    'farm_registration_number' => 'FARM-RW-' . strtoupper(substr(uniqid(), -6)),
                    'country_id' => $country?->id,
                    'province_id' => $province?->id,
                    'district_id' => $district?->id,
                    'sector_id' => $sector?->id,
                    'cell_id' => $cell?->id,
                    'village_id' => $village?->id,
                    'species' => $species[$index % count($species)],
                    'number_of_animals' => rand(8, 25),
                    'unit_price' => rand(200, 450) * 1000,
                    'total_price' => null,
                    'transport_vehicle_plate' => 'RAB ' . rand(100, 999) . ' ' . chr(65 + rand(0, 25)),
                    'driver_name' => 'Driver ' . ['Mugisha', 'Niyonzima', 'Habimana', 'Uwera'][$index % 4],
                    'animal_health_certificate_number' => 'AHC-RW-' . strtoupper(substr(uniqid(), -8)),
                    'health_certificate_issue_date' => $certIssue,
                    'health_certificate_expiry_date' => $certExpiry,
                    'status' => AnimalIntake::STATUS_APPROVED,
                ];
                AnimalIntake::firstOrCreate($base, $attrs);
            }
        }

        $this->command?->info('Animal intakes seeded (Rwanda suppliers and farms).');
    }
}
