<?php

namespace Database\Seeders;

use App\Models\Animal;
use App\Models\AnimalCertificate;
use App\Models\AnimalCertificateLog;
use App\Models\AnimalCertificateTemplate;
use App\Models\AnimalHealthRecord;
use App\Models\AnimalOwnershipTransfer;
use App\Models\Business;
use App\Models\Buyer;
use App\Models\DiseaseRecord;
use App\Models\Farm;
use App\Models\FeedingRecord;
use App\Models\FeedingSchedule;
use App\Models\FeedInventory;
use App\Models\FeedSupplier;
use App\Models\FeedType;
use App\Models\Livestock;
use App\Models\LivestockEvent;
use App\Models\MortalityRecord;
use App\Models\MovementHistory;
use App\Models\MovementLog;
use App\Models\MovementPermit;
use App\Models\MovementPermitAnimal;
use App\Models\MovementTransport;
use App\Models\MovementVeterinaryApproval;
use App\Models\PermitRequest;
use App\Models\PermitRequestAnimal;
use App\Models\Sale;
use App\Models\SaleAnimal;
use App\Models\SaleLog;
use App\Models\SalePayment;
use App\Models\Treatment;
use App\Models\User;
use App\Models\Vaccination;
use App\Models\VeterinaryVisit;
use App\Support\FarmerAnimalType;
use Carbon\Carbon;
use Database\Seeders\Support\RwandaSeederHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Connected demo data for owner.farmer@demo.rw — one operational farm with traceability across modules.
 */
class FarmerWorkspaceDemoSeeder extends Seeder
{
    private const OWNER_EMAIL = 'owner.farmer@demo.rw';

    private const BUSINESS_REG = 'SEED-MT-FA-001';

    private const FARM_REGISTRATION = 'SEED-FA-KAGARAMA-001';

    private Carbon $rangeStart;

    private Carbon $rangeEnd;

    private User $owner;

    private Business $business;

    private Farm $farm;

    /** @var Collection<int, Livestock> */
    private Collection $groups;

    /** @var Collection<int, Animal> */
    private Collection $animals;

    public function run(): void
    {
        $this->rangeStart = Carbon::now()->subMonths(14)->startOfDay();
        $this->rangeEnd = Carbon::now()->endOfDay();

        $this->owner = User::query()->where('email', self::OWNER_EMAIL)->firstOrFail();
        $this->business = Business::query()
            ->where('registration_number', self::BUSINESS_REG)
            ->where('type', Business::TYPE_FARMER)
            ->firstOrFail();

        $this->farm = $this->ensureMainFarm();

        if ($this->farmAlreadyPopulated()) {
            if ($this->movementModuleNeedsSeed()) {
                $this->command?->info('Farmer workspace exists — seeding movement module (requests, permits, history)…');
                $this->loadFarmAnimals();
                DB::transaction(function (): void {
                    $this->purgeMovementModuleData();
                    $this->seedMovementModule();
                });
                $this->command?->info('Movement module demo data updated.');

                return;
            }

            $this->command?->warn('Farmer workspace demo already populated for '.self::FARM_REGISTRATION.'. Skipping.');

            return;
        }

        $this->command?->info('Seeding farmer workspace demo for '.self::OWNER_EMAIL.'…');

        DB::transaction(function (): void {
            $this->purgeFarmModuleData();
            $this->seedLivestockGroups();
            $this->seedAnimals();
            $this->syncLivestockQuantities();
            $this->seedHealthRecords();
            $this->seedFeeding();
            $this->seedCertificates();
            $this->seedMovementModule();
            $this->seedBuyersAndSales();
            $this->seedFarmHealthSnapshots();
            $this->seedOperationalEvents();
        });

        $this->command?->info('Farmer workspace demo ready: '.$this->farm->name.' ('.$this->animals->count().' animals).');
    }

    private function ensureMainFarm(): Farm
    {
        $existing = Farm::query()
            ->where('business_id', $this->business->id)
            ->where('registration_number', self::FARM_REGISTRATION)
            ->first();

        if ($existing) {
            $existing->update([
                'name' => 'Kagarama Prime Livestock Farm',
                'registration_number' => self::FARM_REGISTRATION,
                'gps_latitude' => -1.2925,
                'gps_longitude' => 30.4568,
                'farm_size_hectares' => 84.2,
                'land_ownership_type' => Farm::LAND_OWNERSHIP_OWNED,
                'registration_date' => Carbon::parse('2021-03-15'),
                'animal_types' => [FarmerAnimalType::CATTLE, FarmerAnimalType::GOAT],
                'status' => Farm::STATUS_ACTIVE,
            ]);

            return $existing->fresh();
        }

        $template = Farm::query()
            ->where('business_id', $this->business->id)
            ->orderBy('id')
            ->first();

        if ($template) {
            $template->update([
                'name' => 'Kagarama Prime Livestock Farm',
                'registration_number' => self::FARM_REGISTRATION,
                'gps_latitude' => -1.2925,
                'gps_longitude' => 30.4568,
                'farm_size_hectares' => 84.2,
                'land_ownership_type' => Farm::LAND_OWNERSHIP_OWNED,
                'registration_date' => Carbon::parse('2021-03-15'),
                'animal_types' => [FarmerAnimalType::CATTLE, FarmerAnimalType::GOAT],
                'status' => Farm::STATUS_ACTIVE,
            ]);

            return $template->fresh();
        }

        $country = \App\Models\AdministrativeDivision::ofType(\App\Models\AdministrativeDivision::TYPE_COUNTRY)->first();

        return Farm::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Kagarama Prime Livestock Farm',
            'registration_number' => self::FARM_REGISTRATION,
            'country_id' => $country?->id,
            'gps_latitude' => -1.2925,
            'gps_longitude' => 30.4568,
            'farm_size_hectares' => 84.2,
            'land_ownership_type' => Farm::LAND_OWNERSHIP_OWNED,
            'registration_date' => Carbon::parse('2021-03-15'),
            'animal_types' => [FarmerAnimalType::CATTLE, FarmerAnimalType::GOAT],
            'status' => Farm::STATUS_ACTIVE,
        ]);
    }

    private function farmAlreadyPopulated(): bool
    {
        return Animal::query()
            ->whereHas('livestock', fn (Builder $query) => $query->where('farm_id', $this->farm->id))
            ->count() >= 30;
    }

    private function movementModuleNeedsSeed(): bool
    {
        return PermitRequest::query()->where('farm_id', $this->farm->id)->doesntExist();
    }

    private function loadFarmAnimals(): void
    {
        $this->animals = Animal::query()
            ->whereHas('livestock', fn (Builder $query) => $query->where('farm_id', $this->farm->id))
            ->orderBy('id')
            ->get();

        if ($this->animals->count() < 30) {
            throw new \RuntimeException(
                'Expected at least 30 animals on '.self::FARM_REGISTRATION.' before seeding movement data.',
            );
        }
    }

    private function purgeMovementModuleData(): void
    {
        $farmId = $this->farm->id;
        $permitIds = MovementPermit::withTrashed()->where('source_farm_id', $farmId)->pluck('id');

        MovementHistory::query()->whereIn('movement_permit_id', $permitIds)->delete();
        MovementLog::query()->whereIn('movement_permit_id', $permitIds)->delete();
        MovementPermitAnimal::query()->whereIn('movement_permit_id', $permitIds)->delete();
        MovementTransport::query()->whereIn('movement_permit_id', $permitIds)->delete();
        MovementVeterinaryApproval::query()->whereIn('movement_permit_id', $permitIds)->delete();
        MovementPermit::withTrashed()->whereIn('id', $permitIds)->forceDelete();

        $requestIds = PermitRequest::withTrashed()->where('farm_id', $farmId)->pluck('id');
        PermitRequestAnimal::query()->whereIn('permit_request_id', $requestIds)->delete();
        PermitRequest::withTrashed()->whereIn('id', $requestIds)->forceDelete();
    }

    private function purgeFarmModuleData(): void
    {
        $farmId = $this->farm->id;
        $businessId = $this->business->id;
        $animalIds = Animal::query()
            ->whereHas('livestock', fn (Builder $query) => $query->where('farm_id', $farmId))
            ->pluck('id');
        $livestockIds = Livestock::withTrashed()->where('farm_id', $farmId)->pluck('id');
        $saleIds = Sale::withTrashed()->where('farm_id', $farmId)->pluck('id');
        $permitIds = MovementPermit::withTrashed()->where('source_farm_id', $farmId)->pluck('id');
        $certificateIds = AnimalCertificate::withTrashed()->whereIn('animal_id', $animalIds)->pluck('id');

        SaleLog::query()->whereIn('sale_id', $saleIds)->delete();
        SalePayment::query()->whereIn('sale_id', $saleIds)->delete();
        SaleAnimal::query()->whereIn('sale_id', $saleIds)->delete();
        Sale::withTrashed()->whereIn('id', $saleIds)->forceDelete();

        $this->purgeMovementModuleData();

        AnimalCertificateLog::query()->whereIn('animal_certificate_id', $certificateIds)->delete();
        AnimalCertificate::withTrashed()->whereIn('id', $certificateIds)->forceDelete();
        AnimalOwnershipTransfer::withTrashed()->whereIn('animal_id', $animalIds)->forceDelete();
        AnimalCertificateTemplate::withTrashed()->where('business_id', $businessId)->forceDelete();

        FeedingRecord::query()->where(function (Builder $query) use ($animalIds, $livestockIds): void {
            $query->whereIn('animal_id', $animalIds)->orWhereIn('livestock_id', $livestockIds);
        })->delete();
        FeedingSchedule::query()->where('business_id', $businessId)->delete();
        FeedInventory::withTrashed()->whereIn('feed_type_id', FeedType::withTrashed()->where('business_id', $businessId)->pluck('id'))->forceDelete();
        FeedSupplier::withTrashed()->where('business_id', $businessId)->forceDelete();
        FeedType::withTrashed()->where('business_id', $businessId)->forceDelete();

        Vaccination::withTrashed()->whereIn('animal_id', $animalIds)->forceDelete();
        Treatment::withTrashed()->whereIn('animal_id', $animalIds)->forceDelete();
        DiseaseRecord::withTrashed()->whereIn('animal_id', $animalIds)->forceDelete();
        VeterinaryVisit::withTrashed()->whereIn('animal_id', $animalIds)->forceDelete();
        MortalityRecord::withTrashed()->whereIn('animal_id', $animalIds)->forceDelete();

        AnimalHealthRecord::query()->where('farm_id', $farmId)->delete();
        LivestockEvent::query()->where('farm_id', $farmId)->delete();

        Animal::withTrashed()->whereIn('id', $animalIds)->forceDelete();
        Livestock::withTrashed()->where('farm_id', $farmId)->forceDelete();
        Buyer::withTrashed()->where('business_id', $businessId)->forceDelete();
    }

    private function seedLivestockGroups(): void
    {
        $definitions = [
            [
                'livestock_name' => 'Beef Cattle Group A',
                'livestock_code' => 'KAG-LS-A-001',
                'breed' => 'Ankole',
                'housing_location' => 'North paddock — Block A',
                'production_purpose' => 'beef',
                'feeding_method' => 'pasture_rotation',
                'farming_method' => 'semi_intensive',
                'acquisition_date' => '2022-06-10',
                'acquisition_source' => 'Cooperative herd expansion purchase',
            ],
            [
                'livestock_name' => 'Young Bulls Batch',
                'livestock_code' => 'KAG-LS-YB-002',
                'breed' => 'Ankole x Boran',
                'housing_location' => 'Fattening pens — Block B',
                'production_purpose' => 'beef',
                'feeding_method' => 'supplemental_feed',
                'farming_method' => 'feedlot',
                'acquisition_date' => '2023-02-18',
                'acquisition_source' => 'Raised from calving cohort 2022',
            ],
            [
                'livestock_name' => 'Premium Fattening Group',
                'livestock_code' => 'KAG-LS-PF-003',
                'breed' => 'Boran',
                'housing_location' => 'Finishing unit — Block C',
                'production_purpose' => 'beef',
                'feeding_method' => 'total_mixed_ration',
                'farming_method' => 'intensive',
                'acquisition_date' => '2023-09-05',
                'acquisition_source' => 'Market purchase — Nyagatare traders',
            ],
            [
                'livestock_name' => 'Breeding Group',
                'livestock_code' => 'KAG-LS-BR-004',
                'breed' => 'Ankole',
                'housing_location' => 'Breeding paddock — Block D',
                'production_purpose' => 'breeding',
                'feeding_method' => 'pasture_plus_minerals',
                'farming_method' => 'semi_intensive',
                'acquisition_date' => '2021-11-22',
                'acquisition_source' => 'Foundation breeding stock',
            ],
        ];

        $this->groups = collect($definitions)->map(function (array $definition) {
            return Livestock::query()->create([
                'farm_id' => $this->farm->id,
                'livestock_name' => $definition['livestock_name'],
                'livestock_code' => $definition['livestock_code'],
                'type' => FarmerAnimalType::CATTLE,
                'livestock_type' => FarmerAnimalType::CATTLE,
                'breed' => $definition['breed'],
                'production_purpose' => $definition['production_purpose'],
                'feeding_type' => Livestock::FEEDING_MIXED,
                'feeding_method' => $definition['feeding_method'],
                'farming_method' => $definition['farming_method'],
                'water_source' => 'borehole_and_troughs',
                'housing_location' => $definition['housing_location'],
                'acquisition_date' => $definition['acquisition_date'],
                'acquisition_source' => $definition['acquisition_source'],
                'base_price' => '385000',
                'quality_band' => Livestock::QUALITY_GOOD,
                'health_status' => Livestock::HEALTH_HEALTHY,
                'lifecycle_status' => Livestock::LIFECYCLE_ACTIVE,
                'status' => Livestock::STATUS_ACTIVE,
                'created_by' => $this->owner->id,
                'total_count' => 0,
                'total_quantity' => 0,
                'available_quantity' => 0,
                'healthy_quantity' => 0,
                'sick_quantity' => 0,
            ]);
        });
    }

    private function seedAnimals(): void
    {
        $plans = [
            ['group' => 0, 'prefix' => 'A', 'count' => 12, 'base_weight' => 360],
            ['group' => 1, 'prefix' => 'YB', 'count' => 8, 'base_weight' => 290],
            ['group' => 2, 'prefix' => 'PF', 'count' => 10, 'base_weight' => 410],
            ['group' => 3, 'prefix' => 'BR', 'count' => 6, 'base_weight' => 340],
        ];

        $this->animals = collect();
        $index = 0;

        foreach ($plans as $plan) {
            $group = $this->groups[$plan['group']];

            for ($n = 1; $n <= $plan['count']; $n++) {
                $index++;
                $tag = sprintf('RW-KAG-%s-%03d', $plan['prefix'], $n);
                $code = sprintf('KAG-%s-%03d', $plan['prefix'], $n);
                $birthDate = RwandaSeederHelper::dateInRange(
                    Carbon::parse('2021-01-01'),
                    Carbon::parse('2024-06-01'),
                    $index,
                    36,
                )->toDateString();

                $lifecycle = Animal::LIFECYCLE_ACTIVE;
                $health = Animal::HEALTH_HEALTHY;
                $production = Animal::PRODUCTION_GROWING;

                if ($index === 4) {
                    $lifecycle = Animal::LIFECYCLE_SOLD;
                    $production = Animal::PRODUCTION_READY_FOR_SALE;
                } elseif ($index === 5) {
                    $lifecycle = Animal::LIFECYCLE_SOLD;
                    $production = Animal::PRODUCTION_READY_FOR_SALE;
                } elseif ($index === 6) {
                    $lifecycle = Animal::LIFECYCLE_DEAD;
                } elseif ($index === 7) {
                    $health = Animal::HEALTH_QUARANTINED;
                    $production = Animal::PRODUCTION_DRY;
                } elseif ($index === 8) {
                    $health = Animal::HEALTH_SICK;
                    $production = Animal::PRODUCTION_GROWING;
                } elseif ($index === 9) {
                    $lifecycle = Animal::LIFECYCLE_TRANSFERRED;
                } elseif ($index === 10) {
                    $health = Animal::HEALTH_UNDER_TREATMENT;
                } elseif ($index >= 34) {
                    $production = Animal::PRODUCTION_READY_FOR_SALE;
                } elseif ($plan['group'] === 3 && $n <= 2) {
                    $production = Animal::PRODUCTION_BREEDING;
                }

                $this->animals->push(Animal::query()->create([
                    'livestock_id' => $group->id,
                    'animal_code' => $code,
                    'tag_number' => $tag,
                    'qr_code' => 'ANIMAL:'.$tag,
                    'public_verification_token' => Str::lower(Str::slug($tag, '')),
                    'animal_name' => $this->animalName($plan['prefix'], $n),
                    'gender' => $n % 3 === 0 ? Animal::GENDER_FEMALE : Animal::GENDER_MALE,
                    'birth_date' => $birthDate,
                    'age' => round(Carbon::parse($birthDate)->diffInMonths(now()) / 12, 1),
                    'weight' => $plan['base_weight'] + ($n * 6),
                    'color_markings' => $n % 2 === 0 ? 'Red-brown with white forehead' : 'Dark brown with horn tips',
                    'acquisition_type' => $plan['group'] === 3 ? 'breeding_stock' : 'farm_raised',
                    'acquisition_date' => $group->acquisition_date,
                    'source' => $group->acquisition_source,
                    'health_status' => $health,
                    'production_status' => $production,
                    'lifecycle_status' => $lifecycle,
                    'current_condition' => $health === Animal::HEALTH_HEALTHY ? Animal::CURRENT_CONDITION_RIZIMA : Animal::CURRENT_CONDITION_RIRWAYE,
                    'notes' => 'Registered on Kagarama herd book.',
                    'created_by' => $this->owner->id,
                    'created_at' => RwandaSeederHelper::dateInRange($this->rangeStart, $this->rangeEnd, $index, 40),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    private function animalName(string $prefix, int $number): string
    {
        $names = [
            'A' => ['Imena', 'Kigali', 'Ubwiza', 'Inyana', 'Gisabo', 'Mutara', 'Karama', 'Nyagatare', 'Ubutwari', 'Ishyaka', 'Muhoza', 'Karame'],
            'YB' => ['Impano', 'Kundwa', 'Hirwa', 'Mugabo', 'Keza', 'Nshuti', 'Bosco', 'Manzi'],
            'PF' => ['Urugero', 'Ishami', 'Ganza', 'Murekezi', 'Niyonzima', 'Habimana', 'Uwimana', 'Mugisha', 'Nkurunziza', 'Uwase'],
            'BR' => ['Nyiramajyambere', 'Uwineza', 'Mukamana', 'Kabera', 'Bizimana', 'Mukiza'],
        ];

        return $names[$prefix][$number - 1] ?? ('Animal '.$prefix.'-'.$number);
    }

    private function syncLivestockQuantities(): void
    {
        foreach ($this->groups as $group) {
            $groupAnimals = $this->animals->where('livestock_id', $group->id);
            $active = $groupAnimals->where('lifecycle_status', Animal::LIFECYCLE_ACTIVE);
            $healthy = $active->whereIn('health_status', [Animal::HEALTH_HEALTHY, Animal::HEALTH_PREGNANT]);
            $sick = $active->whereNotIn('health_status', [Animal::HEALTH_HEALTHY, Animal::HEALTH_PREGNANT]);
            $total = $groupAnimals->whereIn('lifecycle_status', [Animal::LIFECYCLE_ACTIVE, Animal::LIFECYCLE_TRANSFERRED])->count();

            $group->update([
                'total_count' => $total,
                'total_quantity' => $total,
                'available_quantity' => max(0, $active->count() - 1),
                'healthy_quantity' => $healthy->count(),
                'sick_quantity' => $sick->count(),
                'male_count' => $groupAnimals->where('gender', Animal::GENDER_MALE)->count(),
                'female_count' => $groupAnimals->where('gender', Animal::GENDER_FEMALE)->count(),
                'young_count' => $groupAnimals->filter(fn (Animal $animal) => (float) $animal->age < 2)->count(),
                'health_status' => $sick->isNotEmpty() ? Livestock::HEALTH_UNDER_OBSERVATION : Livestock::HEALTH_HEALTHY,
            ]);
        }
    }

    private function seedHealthRecords(): void
    {
        $vet = 'Dr. Claudine Mukamana — Kagarama Veterinary Services';
        $clinic = 'Kagarama Vet Clinic, Nyagatare';

        foreach ($this->animals->take(18) as $i => $animal) {
            Vaccination::query()->create([
                'animal_id' => $animal->id,
                'vaccination_code' => sprintf('VAC-KAG-%03d', $i + 1),
                'vaccine_name' => $i % 2 === 0 ? 'FMD Trivalent Vaccine' : 'Lumpy Skin Disease Vaccine',
                'vaccine_type' => $i % 2 === 0 ? 'fmd' : 'lsd',
                'manufacturer' => 'KEVEVAPI',
                'batch_number' => 'BATCH-2025-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'dosage' => '2 ml IM',
                'administration_method' => 'intramuscular',
                'vaccination_date' => Carbon::now()->subMonths(4 - ($i % 3))->subDays($i),
                'next_due_date' => $i < 4
                    ? Carbon::now()->subDays(5 + $i)
                    : Carbon::now()->addMonths(2 + ($i % 4)),
                'veterinarian_name' => $vet,
                'veterinary_clinic' => $clinic,
                'administered_by' => RwandaSeederHelper::fullName(20 + $i),
                'status' => $i < 4 ? Vaccination::STATUS_SCHEDULED : Vaccination::STATUS_COMPLETED,
                'notes' => 'Routine herd vaccination programme.',
                'created_by' => $this->owner->id,
            ]);
        }

        $sickAnimal = $this->animals->firstWhere('health_status', Animal::HEALTH_SICK);
        if ($sickAnimal) {
            Treatment::query()->create([
                'animal_id' => $sickAnimal->id,
                'treatment_code' => 'TRT-KAG-001',
                'disease_name' => 'Tick-borne fever',
                'symptoms' => 'Fever, reduced appetite, nasal discharge',
                'diagnosis' => 'Suspected anaplasmosis',
                'medicine_name' => 'Oxytetracycline LA',
                'dosage' => '20 mg/kg',
                'treatment_method' => 'injectable',
                'treatment_start_date' => Carbon::now()->subDays(6),
                'treatment_end_date' => Carbon::now()->addDays(4),
                'veterinarian_name' => $vet,
                'response_to_treatment' => 'improving',
                'follow_up_date' => Carbon::now()->addDays(2),
                'status' => Treatment::STATUS_FOLLOW_UP_NEEDED,
                'notes' => 'Isolate from finishing unit until follow-up clears.',
                'created_by' => $this->owner->id,
            ]);
        }

        $quarantined = $this->animals->firstWhere('health_status', Animal::HEALTH_QUARANTINED);
        if ($quarantined) {
            DiseaseRecord::query()->create([
                'animal_id' => $quarantined->id,
                'disease_code' => 'DIS-KAG-001',
                'disease_name' => 'Contagious Bovine Pleuropneumonia (suspected)',
                'symptoms' => 'Laboured breathing, cough, reduced grazing',
                'severity_level' => 'high',
                'diagnosis_date' => Carbon::now()->subDays(9),
                'quarantine_required' => true,
                'contagious_status' => DiseaseRecord::CONTAGIOUS_YES,
                'recovery_status' => DiseaseRecord::RECOVERY_RECOVERING,
                'veterinarian_name' => $vet,
                'notes' => 'Quarantine pen D isolated. Awaiting district lab confirmation.',
                'created_by' => $this->owner->id,
            ]);
        }

        foreach ($this->animals->take(6) as $i => $animal) {
            VeterinaryVisit::query()->create([
                'animal_id' => $animal->id,
                'visit_code' => sprintf('VET-KAG-%03d', $i + 1),
                'visit_date' => Carbon::now()->subMonths(2)->addDays($i * 5),
                'purpose_of_visit' => $i % 2 === 0 ? 'Routine herd inspection' : 'Pre-movement inspection',
                'veterinarian_name' => $vet,
                'clinic_name' => $clinic,
                'findings' => 'Body condition score 3.5/5. No lameness observed.',
                'recommendations' => 'Continue mineral supplementation and schedule deworming.',
                'follow_up_required' => $i % 3 === 0,
                'follow_up_date' => Carbon::now()->addDays(14 + $i),
                'created_by' => $this->owner->id,
            ]);
        }

        $dead = $this->animals->firstWhere('lifecycle_status', Animal::LIFECYCLE_DEAD);
        if ($dead) {
            MortalityRecord::query()->create([
                'animal_id' => $dead->id,
                'mortality_code' => 'MOR-KAG-001',
                'death_date' => Carbon::now()->subMonths(2)->subDays(3),
                'cause_of_death' => 'Acute bloat after lush pasture access',
                'reported_by' => RwandaSeederHelper::fullName(88),
                'veterinarian_name' => $vet,
                'disposal_method' => 'Burial with district notification',
                'notes' => 'Post-mortem completed on farm.',
                'created_by' => $this->owner->id,
            ]);
        }
    }

    private function seedFeeding(): void
    {
        $feedTypes = collect([
            ['name' => 'Beef Finisher Pellets', 'code' => 'FT-KAG-BF-01', 'category' => FeedType::CATEGORY_FINISHER, 'form' => FeedType::FORM_PELLETS, 'unit' => 'kg'],
            ['name' => 'Dairy Mineral Supplement', 'code' => 'FT-KAG-MS-02', 'category' => FeedType::CATEGORY_MINERALS, 'form' => FeedType::FORM_DRY, 'unit' => 'kg'],
            ['name' => 'Rhodes Grass Hay', 'code' => 'FT-KAG-HY-03', 'category' => FeedType::CATEGORY_HAY, 'form' => FeedType::FORM_DRY, 'unit' => 'bale'],
            ['name' => 'Maize Silage', 'code' => 'FT-KAG-SG-04', 'category' => FeedType::CATEGORY_SILAGE, 'form' => FeedType::FORM_DRY, 'unit' => 'ton'],
        ])->map(fn (array $row) => FeedType::query()->create([
            'business_id' => $this->business->id,
            'feed_name' => $row['name'],
            'feed_code' => $row['code'],
            'feed_category' => $row['category'],
            'feed_form' => $row['form'],
            'unit' => $row['unit'],
            'protein_percentage' => 14,
            'energy_value' => 11.5,
            'status' => FeedType::STATUS_ACTIVE,
            'created_by' => $this->owner->id,
        ]));

        $suppliers = collect([
            ['name' => 'Nyagatare Feeds Ltd', 'code' => 'SUP-KAG-01'],
            ['name' => 'AgriSupply Rwanda', 'code' => 'SUP-KAG-02'],
        ])->map(fn (array $row) => FeedSupplier::query()->create([
            'business_id' => $this->business->id,
            'supplier_name' => $row['name'],
            'supplier_code' => $row['code'],
            'contact_person' => RwandaSeederHelper::fullName(40),
            'phone' => RwandaSeederHelper::phone(40),
            'email' => strtolower(str_replace(' ', '.', $row['name'])).'@supplier.rw',
            'status' => 'active',
            'created_by' => $this->owner->id,
        ]));

        $inventories = collect([
            ['feed' => 0, 'supplier' => 0, 'code' => 'INV-KAG-001', 'received' => 1200, 'remaining' => 180, 'status' => FeedInventory::STATUS_LOW_STOCK, 'expiry' => 45],
            ['feed' => 1, 'supplier' => 1, 'code' => 'INV-KAG-002', 'received' => 300, 'remaining' => 95, 'status' => FeedInventory::STATUS_AVAILABLE, 'expiry' => 120],
            ['feed' => 2, 'supplier' => 0, 'code' => 'INV-KAG-003', 'received' => 220, 'remaining' => 40, 'status' => FeedInventory::STATUS_LOW_STOCK, 'expiry' => 18],
            ['feed' => 3, 'supplier' => 1, 'code' => 'INV-KAG-004', 'received' => 18, 'remaining' => 6, 'status' => FeedInventory::STATUS_AVAILABLE, 'expiry' => 8],
        ])->map(function (array $row) use ($feedTypes, $suppliers) {
            $received = (float) $row['received'];
            $remaining = (float) $row['remaining'];

            return FeedInventory::query()->create([
                'feed_type_id' => $feedTypes[$row['feed']]->id,
                'inventory_code' => $row['code'],
                'supplier_id' => $suppliers[$row['supplier']]->id,
                'quantity_received' => $received,
                'quantity_remaining' => $remaining,
                'unit_cost' => 420,
                'total_cost' => $received * 420,
                'purchase_date' => Carbon::now()->subMonths(2),
                'expiry_date' => Carbon::now()->addDays($row['expiry']),
                'storage_location' => 'Main feed store — Kagarama',
                'reorder_level' => 200,
                'batch_number' => 'BATCH-'.strtoupper(Str::random(6)),
                'status' => $row['status'],
                'created_by' => $this->owner->id,
            ]);
        });

        foreach ($this->groups->take(3) as $i => $group) {
            FeedingSchedule::query()->create([
                'business_id' => $this->business->id,
                'schedule_name' => $group->livestock_name.' morning ration',
                'livestock_id' => $group->id,
                'feed_type_id' => $feedTypes[$i % $feedTypes->count()]->id,
                'feeding_time' => '06:30:00',
                'feeding_frequency' => FeedingSchedule::FREQUENCY_DAILY,
                'quantity' => 2.5 + $i,
                'status' => FeedingSchedule::STATUS_ACTIVE,
                'created_by' => $this->owner->id,
            ]);
        }

        FeedingSchedule::query()->create([
            'business_id' => $this->business->id,
            'schedule_name' => 'Quarantine pen mineral top-up',
            'animal_id' => $this->animals->firstWhere('health_status', Animal::HEALTH_QUARANTINED)?->id,
            'feed_type_id' => $feedTypes[1]->id,
            'feeding_time' => '16:00:00',
            'feeding_frequency' => FeedingSchedule::FREQUENCY_DAILY,
            'quantity' => 0.5,
            'status' => FeedingSchedule::STATUS_ACTIVE,
            'created_by' => $this->owner->id,
        ]);

        foreach (range(1, 24) as $i) {
            $animal = $this->animals[($i - 1) % $this->animals->count()];
            $inventory = $inventories[$i % $inventories->count()];

            FeedingRecord::query()->create([
                'feeding_code' => sprintf('FDR-KAG-%03d', $i),
                'animal_id' => $i % 4 === 0 ? null : $animal->id,
                'livestock_id' => $i % 4 === 0 ? $this->groups[$i % $this->groups->count()]->id : $animal->livestock_id,
                'feed_type_id' => $inventory->feed_type_id,
                'feed_inventory_id' => $inventory->id,
                'quantity' => 1.5 + ($i % 3),
                'feeding_date' => Carbon::now()->subDays(30 - $i),
                'feeding_time' => '07:00:00',
                'fed_by' => RwandaSeederHelper::fullName(60 + $i),
                'notes' => 'Daily ration logged by farm attendant.',
                'created_by' => $this->owner->id,
            ]);
        }
    }

    private function seedCertificates(): void
    {
        $templates = collect([
            ['name' => 'Kagarama Traceability Passport', 'type' => AnimalCertificate::TYPE_TRACEABILITY],
            ['name' => 'Kagarama Health Clearance', 'type' => AnimalCertificate::TYPE_HEALTH],
        ])->map(fn (array $row) => AnimalCertificateTemplate::query()->create([
            'business_id' => $this->business->id,
            'template_name' => $row['name'],
            'certificate_type' => $row['type'],
            'title_template' => $row['name'],
            'header_note' => 'Issued under Ubworozi Imbuto cooperative compliance programme.',
            'footer_note' => 'Scan QR to verify on BuchaPro.',
            'is_default' => true,
            'status' => 'active',
            'created_by' => $this->owner->id,
        ]));

        $certificateRows = [
            ['animal' => 0, 'type' => AnimalCertificate::TYPE_TRACEABILITY, 'status' => AnimalCertificate::STATUS_ACTIVE, 'expiry' => 180, 'template' => 0],
            ['animal' => 1, 'type' => AnimalCertificate::TYPE_HEALTH, 'status' => AnimalCertificate::STATUS_ACTIVE, 'expiry' => 90, 'template' => 1],
            ['animal' => 2, 'type' => AnimalCertificate::TYPE_OWNERSHIP, 'status' => AnimalCertificate::STATUS_ACTIVE, 'expiry' => 365, 'template' => null],
            ['animal' => 11, 'type' => AnimalCertificate::TYPE_TRACEABILITY, 'status' => AnimalCertificate::STATUS_ACTIVE, 'expiry' => 120, 'template' => 0],
            ['animal' => 15, 'type' => AnimalCertificate::TYPE_HEALTH, 'status' => AnimalCertificate::STATUS_EXPIRED, 'expiry' => -20, 'template' => 1],
            ['animal' => 20, 'type' => AnimalCertificate::TYPE_TRACEABILITY, 'status' => AnimalCertificate::STATUS_ACTIVE, 'expiry' => 60, 'template' => 0],
        ];

        foreach ($certificateRows as $i => $row) {
            $animal = $this->animals[$row['animal']];
            $token = 'cert-kag-'.Str::lower(Str::random(16));
            $certificate = AnimalCertificate::query()->create([
                'animal_id' => $animal->id,
                'template_id' => $row['template'] !== null ? $templates[$row['template']]->id : null,
                'certificate_number' => sprintf('ACERT-KAG-%03d', $i + 1),
                'certificate_type' => $row['type'],
                'certificate_title' => ucfirst(str_replace('_', ' ', $row['type'])).' certificate — '.$animal->displayIdentifier(),
                'issue_date' => Carbon::now()->subMonths(3),
                'expiry_date' => Carbon::now()->addDays($row['expiry']),
                'issued_by' => $this->owner->name,
                'veterinarian_name' => 'Dr. Claudine Mukamana',
                'verification_token' => $token,
                'qr_code' => route('animal.verify', ['token' => $token]),
                'certificate_status' => $row['status'],
                'remarks' => 'Demo certificate for traceability verification.',
                'created_by' => $this->owner->id,
            ]);

            foreach ([AnimalCertificateLog::ACTION_CREATED, AnimalCertificateLog::ACTION_VERIFIED, AnimalCertificateLog::ACTION_DOWNLOADED] as $j => $action) {
                AnimalCertificateLog::query()->create([
                    'animal_certificate_id' => $certificate->id,
                    'action_type' => $action,
                    'action_by' => $this->owner->id,
                    'action_date' => Carbon::now()->subDays(20 - ($i * 2) - $j),
                    'ip_address' => '196.19.0.'.(10 + $i),
                    'notes' => 'Demo '.$action.' event.',
                ]);
            }
        }

        $transferAnimal = $this->animals[3];
        AnimalOwnershipTransfer::query()->create([
            'animal_id' => $transferAnimal->id,
            'previous_owner' => 'Ubworozi Imbuto Farmers Cooperative',
            'new_owner' => 'Kagarama Prime Livestock Farm',
            'transfer_date' => Carbon::parse('2022-08-01'),
            'transfer_reason' => 'Internal cooperative allocation to flagship farm',
            'approved_by' => $this->owner->name,
            'notes' => 'Ownership consolidated for passport issuance.',
            'created_by' => $this->owner->id,
        ]);
    }

    private function seedMovementModule(): void
    {
        $ownerName = $this->business->ownerIndividualDisplayName();
        $ownerNid = '1198980187193037';

        $requests = $this->seedPermitRequests($ownerName);
        $this->seedMovementPermits($requests, $ownerName, $ownerNid);
    }

    /**
     * @return array<string, PermitRequest>
     */
    private function seedPermitRequests(string $ownerName): array
    {
        $definitions = [
            'completed_slaughter' => [
                'number' => 'PR-KAG-2026-0001',
                'status' => PermitRequest::STATUS_COMPLETED,
                'purpose' => PermitRequest::PURPOSE_SLAUGHTER,
                'destination_type' => PermitRequest::DESTINATION_ABATTOIR,
                'destination_name' => 'Nyagatare Municipal Abattoir',
                'destination_district' => 'Nyagatare',
                'destination_sector' => 'Nyagatare',
                'departure' => -14,
                'arrival' => -12,
                'animals' => [0, 1],
                'reviewed' => true,
            ],
            'approved_market' => [
                'number' => 'PR-KAG-2026-0002',
                'status' => PermitRequest::STATUS_APPROVED,
                'purpose' => PermitRequest::PURPOSE_SALE,
                'destination_type' => PermitRequest::DESTINATION_MARKET,
                'destination_name' => 'Nyagatare Livestock Market',
                'destination_district' => 'Nyagatare',
                'destination_sector' => 'Rwimiyaga',
                'departure' => 3,
                'arrival' => 4,
                'animals' => [12, 13],
                'reviewed' => true,
            ],
            'submitted_transfer' => [
                'number' => 'PR-KAG-2026-0003',
                'status' => PermitRequest::STATUS_SUBMITTED,
                'purpose' => PermitRequest::PURPOSE_TRANSFER,
                'destination_type' => PermitRequest::DESTINATION_FARM,
                'destination_name' => 'Gatsibo Cooperative Farm',
                'destination_district' => 'Gatsibo',
                'destination_sector' => 'Kageyo',
                'departure' => 7,
                'arrival' => 8,
                'animals' => [20, 21],
                'reviewed' => false,
            ],
            'under_review_vet' => [
                'number' => 'PR-KAG-2026-0004',
                'status' => PermitRequest::STATUS_UNDER_REVIEW,
                'purpose' => PermitRequest::PURPOSE_VACCINATION,
                'destination_type' => PermitRequest::DESTINATION_FARM,
                'destination_name' => 'Kagarama Veterinary Clinic',
                'destination_district' => 'Nyagatare',
                'destination_sector' => 'Kagarama',
                'departure' => -2,
                'arrival' => 0,
                'animals' => [6],
                'reviewed' => true,
            ],
            'draft_breeding' => [
                'number' => 'PR-KAG-2026-0005',
                'status' => PermitRequest::STATUS_DRAFT,
                'purpose' => PermitRequest::PURPOSE_BREEDING,
                'destination_type' => PermitRequest::DESTINATION_FARM,
                'destination_name' => 'Musanze Breeding Centre',
                'destination_district' => 'Musanze',
                'destination_sector' => 'Muhoza',
                'departure' => 14,
                'arrival' => 15,
                'animals' => [8, 9],
                'reviewed' => false,
            ],
            'rejected_exhibition' => [
                'number' => 'PR-KAG-2026-0006',
                'status' => PermitRequest::STATUS_REJECTED,
                'purpose' => PermitRequest::PURPOSE_EXHIBITION,
                'destination_type' => PermitRequest::DESTINATION_MARKET,
                'destination_name' => 'Kigali Agriculture Show',
                'destination_district' => 'Gasabo',
                'destination_sector' => 'Kimironko',
                'departure' => 20,
                'arrival' => 22,
                'animals' => [15],
                'reviewed' => true,
                'rejection_reason' => 'Vaccination records incomplete for exhibition movement.',
            ],
        ];

        $created = [];
        $eligibility = app(\App\Services\Farmer\PermitRequestEligibilityService::class);

        foreach ($definitions as $key => $row) {
            $request = PermitRequest::query()->create([
                'request_number' => $row['number'],
                'request_date' => Carbon::now()->subDays(max(1, abs($row['departure']) + 3)),
                'applicant_id' => $this->owner->id,
                'farm_id' => $this->farm->id,
                'farmer_id' => $this->business->id,
                'movement_purpose' => $row['purpose'],
                'destination_type' => $row['destination_type'],
                'destination_name' => $row['destination_name'],
                'destination_district' => $row['destination_district'],
                'destination_sector' => $row['destination_sector'],
                'destination_cell' => $row['destination_sector'] ?? null,
                'destination_village' => null,
                'transport_method' => PermitRequest::TRANSPORT_VEHICLE,
                'vehicle_plate_number' => match ($key) {
                    'completed_slaughter' => 'RAC481Z',
                    'approved_market' => 'RAC502Z',
                    'submitted_transfer' => 'RAC515Z',
                    'under_review_vet' => 'RAC474Z',
                    'draft_breeding' => 'RAC520Z',
                    default => 'RAC499Z',
                },
                'proposed_departure_date' => Carbon::now()->addDays($row['departure']),
                'expected_arrival_date' => Carbon::now()->addDays($row['arrival']),
                'remarks' => __('Demo permit request for :owner.', ['owner' => $ownerName]),
                'status' => $row['status'],
                'reviewed_by' => ($row['reviewed'] ?? false) ? $this->owner->id : null,
                'review_date' => ($row['reviewed'] ?? false) ? Carbon::now()->subDays(2) : null,
                'rejection_reason' => $row['rejection_reason'] ?? null,
            ]);

            foreach ($row['animals'] as $animalIndex) {
                $animal = $this->animals[$animalIndex];
                $check = $eligibility->evaluate($animal);
                PermitRequestAnimal::query()->create([
                    'permit_request_id' => $request->id,
                    'animal_id' => $animal->id,
                    'livestock_id' => $animal->livestock_id,
                    'animal_identifier' => $animal->displayIdentifier(),
                    'quantity' => 1,
                    'eligibility_passed' => $check['passed'],
                    'eligibility_issues' => $check['issues'],
                ]);
            }

            $created[$key] = $request;
        }

        return $created;
    }

    /**
     * @param  array<string, PermitRequest>  $requests
     */
    private function seedMovementPermits(array $requests, string $ownerName, string $ownerNid): void
    {
        $permits = [
            [
                'request_key' => 'completed_slaughter',
                'number' => 'MP-KAG-2026-001',
                'rab' => false,
                'type' => MovementPermit::TYPE_SLAUGHTER_TRANSPORT,
                'status' => MovementPermit::STATUS_USED,
                'movement' => MovementPermit::MOVEMENT_ARRIVED,
                'vet' => MovementPermit::VET_CLEARED,
                'movement_reason' => 'Slaughter transport — abattoir delivery',
                'departure' => -12,
                'arrival' => -11,
                'issue' => -14,
                'expiry' => -10,
                'animals' => [0, 1],
                'destination' => 'Nyagatare Municipal Abattoir',
                'dest_district' => 'Nyagatare',
                'dest_sector' => 'Nyagatare',
                'history' => MovementHistory::STATUS_COMPLETED,
            ],
            [
                'request_key' => 'under_review_vet',
                'number' => 'MP-KAG-2026-003',
                'rab' => false,
                'type' => MovementPermit::TYPE_VETERINARY_REFERRAL,
                'status' => MovementPermit::STATUS_ACTIVE,
                'movement' => MovementPermit::MOVEMENT_IN_TRANSIT,
                'vet' => MovementPermit::VET_CLEARED,
                'movement_reason' => 'Veterinary referral — clinical follow-up',
                'departure' => -1,
                'arrival' => 1,
                'issue' => -2,
                'expiry' => 20,
                'animals' => [6],
                'destination' => 'Kagarama Veterinary Clinic',
                'dest_district' => 'Nyagatare',
                'dest_sector' => 'Kagarama',
                'history' => MovementHistory::STATUS_IN_TRANSIT,
            ],
            [
                'request_key' => null,
                'number' => 'MP-KAG-2025-014',
                'rab' => false,
                'type' => MovementPermit::TYPE_FARM_TRANSFER,
                'status' => MovementPermit::STATUS_EXPIRED,
                'movement' => MovementPermit::MOVEMENT_CANCELLED,
                'vet' => MovementPermit::VET_CLEARED,
                'movement_reason' => 'Farm-to-farm transfer (expired)',
                'departure' => -120,
                'arrival' => -119,
                'issue' => -125,
                'expiry' => -90,
                'animals' => [20],
                'destination' => 'Gatsibo Cooperative Farm',
                'dest_district' => 'Gatsibo',
                'dest_sector' => 'Kageyo',
                'history' => null,
            ],
            [
                'request_key' => null,
                'number' => 'B260210141531XLJK',
                'rab' => true,
                'type' => MovementPermit::TYPE_BREEDING_TRANSFER,
                'status' => MovementPermit::STATUS_ISSUED,
                'movement' => MovementPermit::MOVEMENT_PENDING,
                'vet' => MovementPermit::VET_CLEARED,
                'movement_reason' => 'Ubworozi bw\'inka',
                'departure' => -5,
                'arrival' => -2,
                'issue' => -5,
                'expiry' => -2,
                'animals' => [8, 9],
                'destination' => 'Rusororo, Kabuga',
                'dest_district' => 'Gasabo',
                'dest_sector' => 'Kabuga',
                'dest_village' => 'Rusororo',
                'history' => MovementHistory::STATUS_PLANNED,
                'issued_by' => 'GASHIRABAKE Isidore',
                'vehicle' => 'RAC474Z',
            ],
        ];

        foreach ($permits as $row) {
            $linkedRequest = $row['request_key'] ? ($requests[$row['request_key']] ?? null) : null;
            $token = 'move-kag-'.Str::lower(Str::random(14));
            $verificationCode = Str::upper(Str::random(8));
            $issueDate = Carbon::now()->addDays($row['issue']);
            $expiryDate = Carbon::now()->addDays($row['expiry']);

            $permit = MovementPermit::query()->create([
                'permit_request_id' => $linkedRequest?->id,
                'permit_number' => $row['number'],
                'permit_type' => $row['type'],
                'movement_reason' => $row['movement_reason'],
                'livestock_type' => 'Inka',
                'owner_name' => $ownerName,
                'owner_national_id' => $ownerNid,
                'owner_identification_number' => $ownerNid,
                'owner_address' => 'Ryamatebura, Shangasha, Gicumbi',
                'farmer_id' => $this->business->id,
                'source_farm_id' => $this->farm->id,
                'origin_location' => 'Kagarama Prime Livestock Farm, Nyagatare',
                'source_district' => 'Gicumbi',
                'source_sector' => 'Shangasha',
                'source_cell' => 'Shangasha',
                'source_village' => 'Ryamatebura',
                'destination_location' => $row['destination'],
                'destination_district' => $row['dest_district'] ?? null,
                'destination_sector' => $row['dest_sector'] ?? null,
                'destination_cell' => $row['dest_cell'] ?? null,
                'destination_village' => $row['dest_village'] ?? null,
                'departure_date' => Carbon::now()->addDays($row['departure']),
                'expected_arrival_date' => Carbon::now()->addDays($row['arrival']),
                'issue_date' => $issueDate,
                'expiry_date' => $expiryDate,
                'transport_mode' => ($row['rab'] ?? false) ? 'Imodoka' : 'Cattle truck',
                'vehicle_plate' => $row['vehicle'] ?? 'RAB 482 K',
                'issued_by' => $row['issued_by'] ?? $this->owner->name,
                'issuing_authority' => 'RAB — Rwanda Agriculture and Animal Resources Development Board',
                'permit_status' => $row['status'],
                'veterinary_status' => $row['vet'],
                'movement_status' => $row['movement'],
                'verification_token' => $token,
                'verification_code' => $verificationCode,
                'qr_code' => route('movement.verify', ['token' => $token]),
                'file_path' => 'movement-permits/demo/'.$row['number'].'.pdf',
                'pdf_path' => 'movement-permits/demo/'.$row['number'].'.pdf',
                'imported_from_pdf' => (bool) ($row['rab'] ?? false),
                'created_by' => $this->owner->id,
            ]);

            if ($linkedRequest) {
                $linkedRequest->update([
                    'status' => $row['history'] === MovementHistory::STATUS_COMPLETED
                        ? PermitRequest::STATUS_COMPLETED
                        : PermitRequest::STATUS_PERMIT_ISSUED,
                ]);
            }

            foreach ($row['animals'] as $animalIndex) {
                $animal = $this->animals[$animalIndex];
                MovementPermitAnimal::query()->create([
                    'movement_permit_id' => $permit->id,
                    'animal_id' => $animal->id,
                    'livestock_id' => $animal->livestock_id,
                    'animal_identifier' => $animal->tag_number ?: $animal->animal_code,
                    'species' => 'Inka',
                    'breed' => 'CROSS',
                    'sex' => $animal->gender === Animal::GENDER_MALE ? 'male' : 'female',
                    'age_description' => '1',
                    'quantity' => 1,
                    'movement_condition' => MovementPermitAnimal::CONDITION_HEALTHY,
                    'inspection_notes' => $row['rab'] ?? false ? 'UMUSENGO · KORORA' : 'Fit for transport at loading.',
                ]);
            }

            MovementTransport::query()->create([
                'movement_permit_id' => $permit->id,
                'vehicle_type' => $row['rab'] ?? false ? 'Imodoka' : 'Cattle truck',
                'vehicle_number' => $row['vehicle'] ?? 'RAB 482 K',
                'driver_name' => RwandaSeederHelper::fullName(77),
                'driver_phone' => RwandaSeederHelper::phone(77),
                'transporter_company' => 'Kagarama Haulage',
                'route_information' => $permit->sourceLocationLabel().' → '.$permit->destination_location,
            ]);

            if (! ($row['rab'] ?? false)) {
                MovementVeterinaryApproval::query()->create([
                    'movement_permit_id' => $permit->id,
                    'veterinarian_name' => 'Dr. Claudine Mukamana',
                    'inspection_date' => Carbon::now()->subDays(2),
                    'inspection_result' => $row['vet'] === MovementPermit::VET_CLEARED ? 'cleared' : 'pending',
                    'health_clearance' => $row['vet'] === MovementPermit::VET_CLEARED,
                    'disease_check' => true,
                    'quarantine_check' => true,
                    'approval_status' => $row['vet'] === MovementPermit::VET_CLEARED ? 'approved' : 'pending',
                ]);
            }

            foreach ([MovementLog::ACTION_CREATED, MovementLog::ACTION_APPROVED] as $action) {
                MovementLog::query()->create([
                    'movement_permit_id' => $permit->id,
                    'action_type' => $action,
                    'action_by' => $this->owner->id,
                    'action_date' => Carbon::now()->subDays(4),
                    'ip_address' => '196.19.0.20',
                    'notes' => ($row['rab'] ?? false)
                        ? __('Imported from Rwanda movement permit PDF (demo).')
                        : __('Demo movement workflow event.'),
                ]);
            }

            if ($row['history'] !== null) {
                $this->seedMovementHistoriesForPermit($permit, $row['history'], $row['movement_reason']);
            }
        }
    }

    private function seedMovementHistoriesForPermit(MovementPermit $permit, string $status, string $purpose): void
    {
        $permit->load('animals');

        foreach ($permit->animals as $line) {
            if (! $line->animal_id) {
                continue;
            }

            MovementHistory::query()->create([
                'animal_id' => $line->animal_id,
                'movement_permit_id' => $permit->id,
                'movement_date' => $permit->departure_date ?? $permit->issue_date ?? now(),
                'source_farm_id' => $permit->source_farm_id,
                'source_location' => $permit->sourceLocationLabel(),
                'destination_location' => $permit->destinationLocationLabel() ?: $permit->destination_location,
                'movement_purpose' => $purpose,
                'transport_method' => $permit->transport_mode,
                'vehicle_plate_number' => $permit->vehicle_plate,
                'status' => $status,
                'recorded_by' => $this->owner->id,
                'remarks' => __('Seeded movement history record.'),
            ]);
        }
    }

    private function seedBuyersAndSales(): void
    {
        $buyers = collect([
            ['code' => 'BUY-KAG-01', 'name' => 'Nyagatare Prime Butchery', 'type' => Buyer::TYPE_BUTCHERY],
            ['code' => 'BUY-KAG-02', 'name' => 'Eastern Meat Packers', 'type' => Buyer::TYPE_SLAUGHTERHOUSE],
            ['code' => 'BUY-KAG-03', 'name' => 'Rwamagana Livestock Traders', 'type' => Buyer::TYPE_MARKET_TRADER],
            ['code' => 'BUY-KAG-04', 'name' => 'Kirehe Export Livestock Ltd', 'type' => Buyer::TYPE_EXPORTER],
            ['code' => 'BUY-KAG-05', 'name' => 'Gatsibo Cooperative Farm', 'type' => Buyer::TYPE_FARM],
            ['code' => 'BUY-KAG-06', 'name' => 'Kigali City Meat Distributors', 'type' => Buyer::TYPE_DISTRIBUTOR],
        ])->map(fn (array $row) => Buyer::query()->create([
            'business_id' => $this->business->id,
            'buyer_code' => $row['code'],
            'buyer_name' => $row['name'],
            'buyer_type' => $row['type'],
            'contact_person' => RwandaSeederHelper::fullName(90),
            'phone' => RwandaSeederHelper::phone(90),
            'email' => strtolower(str_replace(' ', '.', $row['name'])).'@buyer.rw',
            'district' => 'Nyagatare',
            'preferred_payment_method' => Sale::METHOD_MOBILE_MONEY,
            'trust_level' => Buyer::TRUST_VERIFIED,
            'status' => Buyer::STATUS_ACTIVE,
            'created_by' => $this->owner->id,
        ]));

        $soldAnimals = $this->animals->where('lifecycle_status', Animal::LIFECYCLE_SOLD)->values();
        $completedPermit = MovementPermit::query()->where('permit_number', 'MP-KAG-2026-001')->first();

        $sales = [
            ['number' => 'SALE-KAG-001', 'buyer' => 0, 'status' => Sale::STATUS_COMPLETED, 'payment' => Sale::PAYMENT_PAID, 'days' => -45, 'animals' => [3]],
            ['number' => 'SALE-KAG-002', 'buyer' => 1, 'status' => Sale::STATUS_COMPLETED, 'payment' => Sale::PAYMENT_PARTIAL, 'days' => -28, 'animals' => [4]],
            ['number' => 'SALE-KAG-003', 'buyer' => 2, 'status' => Sale::STATUS_CONFIRMED, 'payment' => Sale::PAYMENT_PENDING, 'days' => -7, 'animals' => [33]],
            ['number' => 'SALE-KAG-004', 'buyer' => 3, 'status' => Sale::STATUS_PENDING, 'payment' => Sale::PAYMENT_OVERDUE, 'days' => -35, 'animals' => [34]],
            ['number' => 'SALE-KAG-005', 'buyer' => 4, 'status' => Sale::STATUS_DRAFT, 'payment' => Sale::PAYMENT_PENDING, 'days' => -2, 'animals' => [35]],
        ];

        foreach ($sales as $i => $row) {
            $linePrice = 680000 + ($i * 45000);
            $sale = Sale::query()->create([
                'farm_id' => $this->farm->id,
                'sale_number' => $row['number'],
                'buyer_id' => $buyers[$row['buyer']]->id,
                'sale_type' => Sale::TYPE_INDIVIDUAL,
                'sale_date' => Carbon::now()->addDays($row['days']),
                'sale_status' => $row['status'],
                'payment_status' => $row['payment'],
                'payment_method' => Sale::METHOD_MOBILE_MONEY,
                'subtotal_amount' => $linePrice,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'total_amount' => $linePrice,
                'currency' => 'RWF',
                'delivery_method' => 'Farm gate pickup',
                'destination' => 'Buyer collection point',
                'movement_permit_id' => $i === 0 ? $completedPermit?->id : null,
                'certificate_status' => Sale::CERT_VERIFIED,
                'created_by' => $this->owner->id,
            ]);

            foreach ($row['animals'] as $animalIndex) {
                $animal = $this->animals[$animalIndex];
                SaleAnimal::query()->create([
                    'sale_id' => $sale->id,
                    'animal_id' => $animal->id,
                    'livestock_id' => $animal->livestock_id,
                    'sale_price' => $linePrice,
                    'live_weight' => $animal->weight,
                    'price_per_kg' => round($linePrice / max(1, (float) $animal->weight), 2),
                    'animal_condition' => 'healthy',
                    'certificate_verified' => true,
                    'movement_permit_verified' => $i === 0,
                ]);
            }

            if (in_array($row['payment'], [Sale::PAYMENT_PAID, Sale::PAYMENT_PARTIAL, Sale::PAYMENT_OVERDUE], true)) {
                $paid = $row['payment'] === Sale::PAYMENT_PAID ? $linePrice : ($row['payment'] === Sale::PAYMENT_PARTIAL ? $linePrice * 0.6 : 0);
                SalePayment::query()->create([
                    'sale_id' => $sale->id,
                    'payment_reference' => 'PAY-'.$row['number'],
                    'payment_date' => Carbon::now()->addDays($row['days'] + 2),
                    'payment_method' => Sale::METHOD_MOBILE_MONEY,
                    'amount_paid' => $paid,
                    'remaining_balance' => max(0, $linePrice - $paid),
                    'transaction_reference' => 'MM-'.strtoupper(Str::random(8)),
                    'payment_status' => $row['payment'] === Sale::PAYMENT_PAID
                        ? SalePayment::STATUS_PAID
                        : ($row['payment'] === Sale::PAYMENT_PARTIAL ? SalePayment::STATUS_PARTIAL : SalePayment::STATUS_PENDING),
                    'received_by' => $this->owner->id,
                ]);
            }

            SaleLog::query()->create([
                'sale_id' => $sale->id,
                'action_type' => SaleLog::ACTION_CREATED,
                'action_by' => $this->owner->id,
                'action_date' => Carbon::now()->addDays($row['days']),
                'notes' => 'Demo sale created.',
            ]);
        }

        if ($soldAnimals->isNotEmpty()) {
            LivestockEvent::query()->create([
                'farm_id' => $this->farm->id,
                'livestock_id' => $soldAnimals->first()->livestock_id,
                'event_type' => LivestockEvent::TYPE_SUPPLY_FULFILLMENT,
                'quantity' => $soldAnimals->count(),
                'event_date' => Carbon::now()->subDays(40),
                'notes' => 'Market sale fulfilment recorded for dashboard growth metrics.',
            ]);
        }
    }

    private function seedFarmHealthSnapshots(): void
    {
        foreach (range(0, 7) as $i) {
            AnimalHealthRecord::query()->create([
                'farm_id' => $this->farm->id,
                'livestock_id' => $this->groups[$i % $this->groups->count()]->id,
                'batch_reference' => 'HERD-CHECK-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'record_date' => Carbon::now()->subDays(21 - ($i * 2)),
                'event_type' => AnimalHealthRecord::EVENT_VACCINATION,
                'condition' => $i % 5 === 0 ? AnimalHealthRecord::CONDITION_SICK : AnimalHealthRecord::CONDITION_HEALTHY,
                'notes' => 'Routine herd observation logged for dashboard recent health panel.',
            ]);
        }
    }

    private function seedOperationalEvents(): void
    {
        LivestockEvent::query()->create([
            'farm_id' => $this->farm->id,
            'livestock_id' => $this->groups->first()->id,
            'event_type' => 'mortality',
            'quantity' => 1,
            'event_date' => Carbon::now()->subMonths(2),
            'notes' => 'Mortality event linked to dashboard growth metrics.',
        ]);

        LivestockEvent::query()->create([
            'farm_id' => $this->farm->id,
            'livestock_id' => $this->groups[1]->id,
            'event_type' => LivestockEvent::TYPE_MOVEMENT,
            'quantity' => 2,
            'event_date' => Carbon::now()->subDays(11),
            'movement_permit_id' => MovementPermit::query()->where('permit_number', 'MP-KAG-2026-001')->value('id'),
            'notes' => 'Completed slaughter transport.',
        ]);
    }
}
