<?php

namespace Database\Seeders;

use App\Models\AdministrativeDivision;
use App\Models\AnimalIntake;
use App\Models\AnteMortemInspection;
use App\Models\AnteMortemObservation;
use App\Models\Batch;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Certificate;
use App\Models\CertificateQr;
use App\Models\Client;
use App\Models\ClientActivity;
use App\Models\Contract;
use App\Models\DeliveryConfirmation;
use App\Models\Demand;
use App\Models\Employee;
use App\Models\Facility;
use App\Models\Farm;
use App\Models\Inspector;
use App\Models\Livestock;
use App\Models\Location;
use App\Models\LogisticsCompany;
use App\Models\LogisticsDriver;
use App\Models\LogisticsOrder;
use App\Models\LogisticsTrackingLog;
use App\Models\LogisticsTrip;
use App\Models\LogisticsVehicle;
use App\Models\PostMortemInspection;
use App\Models\PostMortemObservation;
use App\Models\SlaughterExecution;
use App\Models\SlaughterPlan;
use App\Models\Supplier;
use App\Models\SupplyRequest;
use App\Models\TemperatureLog;
use App\Models\TransportTrip;
use App\Models\Unit;
use App\Models\User;
use App\Models\WarehouseStorage;
use App\Support\FarmerAnimalType;
use Carbon\Carbon;
use Database\Seeders\Support\ProcessorFinanceSync;
use Database\Seeders\Support\RwandaSeederHelper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Full multi-tenant Rwanda demo: 1 farmer, 1 logistics, 5 processor tenant owners, RBAC on business_user,
 * ~200+ rows per major operational module, dates spread over the past year.
 */
class ComprehensiveRwandaSeeder extends Seeder
{
    private const REG_PREFIX = 'SEED-MT-';

    private int $certCounter = 0;

    public function run(): void
    {
        $this->call(TestLoginSeeder::class);

        $password = 'password';

        if (Business::query()->where('registration_number', self::REG_PREFIX.'PR-001')->exists()) {
            $this->command?->warn('Comprehensive Rwanda data already present (SEED-MT-PR-001). Skipping bulk demo re-seed.');
            $this->backfillMissingRegistrationFieldsOnSeededBusinesses();
            ProcessorFinanceSync::sync();

            return;
        }

        $country = AdministrativeDivision::ofType(AdministrativeDivision::TYPE_COUNTRY)->first();
        if (! $country) {
            $this->command?->error('Run AdministrativeDivisionSeeder first.');

            return;
        }

        $rangeStart = Carbon::now()->subYear();
        $rangeEnd = Carbon::now();
        $kgUnit = Unit::query()->where('code', 'kg')->value('code') ?: 'kg';

        $this->command?->info('Seeding tenant owner accounts and demo data (Rwanda multi-tenant)…');
        $ctx = $this->createTenantShells($country, $password, $rangeStart, $rangeEnd);

        $this->command?->info('Seeding processor CRM, employees, and slaughter pipeline (~220 intakes)…');
        $this->seedProcessorVerticals($ctx, $country, $password, $kgUnit, $rangeStart, $rangeEnd);

        $this->command?->info('Seeding high-volume demands, client activities, temperature logs…');
        $this->seedVolumeCrmAndColdChain($ctx, $kgUnit, $rangeStart, $rangeEnd);

        $this->command?->info('Seeding farmer farms, livestock, and supply requests…');
        $this->seedFarmerEcosystem($ctx, $rangeStart, $rangeEnd);

        $this->command?->info('Seeding logistics company, orders, trips, and tracking…');
        $this->seedLogisticsOperations($ctx, $country, $rangeStart, $rangeEnd);
        $this->command?->info('Deriving finance records from processor workflow data…');
        ProcessorFinanceSync::sync(collect($ctx['processors'])->pluck('business.id'));

        $this->printCredentials();
    }

    /**
     * @return array{
     *   farmers: list<array{user: User, business: Business, farms: \Illuminate\Support\Collection}>,
     *   logistics: array{user: User, business: Business},
     *   processors: list<array{
     *     user: User,
     *     business: Business,
     *     slaughter: Facility,
     *     butchery: Facility,
     *     storage: Facility,
     *     team: \Illuminate\Support\Collection<int, User>
     *   }>
     * }
     */
    private function createTenantShells(AdministrativeDivision $country, string $password, Carbon $rangeStart, Carbon $rangeEnd): array
    {
        $provinces = AdministrativeDivision::byParent($country->id)->get();
        if ($provinces->isEmpty()) {
            throw new \RuntimeException('No provinces in administrative_divisions.');
        }

        $processors = [];
        for ($i = 1; $i <= 5; $i++) {
            $email = "owner.processor.{$i}@demo.rw";
            $owner = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => 'Owner — '.RwandaSeederHelper::fullName(100 + $i),
                    'password' => $password,
                    'email_verified_at' => now(),
                    'is_super_admin' => false,
                ]
            );
            $loc = $this->randomDivisionChain($provinces->random());
            $biz = $this->createBusiness(
                $owner,
                self::REG_PREFIX."PR-{$i}",
                'Nyagatare Prime Meats '.$i,
                Business::TYPE_PROCESSOR,
                $loc,
            );
            $this->attachRole($owner, $biz, BusinessUser::ROLE_ORG_ADMIN);
            $sh = $this->makeFacility($biz, 'Slaughterhouse — '.$i, Facility::TYPE_SLAUGHTERHOUSE, $loc);
            $bt = $this->makeFacility($biz, 'Butchery — '.$i, Facility::TYPE_BUTCHERY, $loc);
            $st = $this->makeFacility($biz, 'Cold store — '.$i, Facility::TYPE_STORAGE, $loc, 300);
            $inst = $this->makeInspector($sh, $i, 1);
            $inst2 = $this->makeInspector($sh, $i, 2);
            for ($e = 0; $e < 4; $e++) {
                $u = $this->makeProcessorTeamUser($i, $e, $password, $e);
                $this->assignProcessorRole($u, $biz, $e);
            }
            $team = User::query()->where('email', 'like', "team.p{$i}.%@demo.rw")->get();
            $processors[] = [
                'user' => $owner,
                'business' => $biz,
                'slaughter' => $sh,
                'butchery' => $bt,
                'storage' => $st,
                'inspectors' => collect([$inst, $inst2]),
                'team' => $team,
            ];
        }

        $farmerOwner = User::query()->updateOrCreate(
            ['email' => 'owner.farmer@demo.rw'],
            [
                'name' => 'Ubworozi Imbuto Co-op (Owner)',
                'password' => $password,
                'email_verified_at' => now(),
                'is_super_admin' => false,
            ]
        );
        $farmerLoc = $this->randomDivisionChain($provinces->random());
        $farmerBiz = $this->createBusiness(
            $farmerOwner,
            self::REG_PREFIX.'FA-001',
            'Ubworozi Imbuto Farmers Cooperative',
            Business::TYPE_FARMER,
            $farmerLoc,
        );
        $this->attachRole($farmerOwner, $farmerBiz, BusinessUser::ROLE_ORG_ADMIN);
        $this->makeFarmerTeamUser(0, $password, $farmerBiz);
        $this->makeFarmerTeamUser(1, $password, $farmerBiz);

        $farms = collect();
        for ($f = 1; $f <= 20; $f++) {
            $chain = $this->randomDivisionChain($provinces->random());
            $farms->push(Farm::query()->create([
                'business_id' => $farmerBiz->id,
                'name' => 'Farm Gate '.$f.' — '.($chain['cell']?->name ?? 'Cell'),
                'country_id' => $country->id,
                'province_id' => $chain['province']->id,
                'district_id' => $chain['district']->id,
                'sector_id' => $chain['sector']->id,
                'cell_id' => $chain['cell']?->id,
                'village_id' => $chain['village']?->id,
                'animal_types' => [FarmerAnimalType::CATTLE, FarmerAnimalType::GOAT],
                'status' => Farm::STATUS_ACTIVE,
            ]));
        }

        $logisticsOwner = User::query()->updateOrCreate(
            ['email' => 'owner.logistics@demo.rw'],
            [
                'name' => 'Kigali Cold Chain — Owner',
                'password' => $password,
                'email_verified_at' => now(),
                'is_super_admin' => false,
            ]
        );
        $loLoc = $this->randomDivisionChain($provinces->firstWhere('name', 'City of Kigali') ?? $provinces->first());
        $logisticsBiz = $this->createBusiness(
            $logisticsOwner,
            self::REG_PREFIX.'LG-001',
            'Kigali Cold Chain Transport Ltd',
            Business::TYPE_LOGISTICS,
            $loLoc,
        );
        $this->attachRole($logisticsOwner, $logisticsBiz, BusinessUser::ROLE_ORG_ADMIN);
        $this->makeLogisticsTeamUser(0, $password, $logisticsBiz);
        $this->makeLogisticsTeamUser(1, $password, $logisticsBiz);

        return [
            'farmers' => [
                [
                    'user' => $farmerOwner,
                    'business' => $farmerBiz,
                    'farms' => $farms,
                ],
            ],
            'logistics' => ['user' => $logisticsOwner, 'business' => $logisticsBiz],
            'processors' => $processors,
        ];
    }

    private function makeProcessorTeamUser(int $p, int $e, string $password, int $idx): User
    {
        $roles = [BusinessUser::ROLE_OPERATIONS_MANAGER, BusinessUser::ROLE_COMPLIANCE_OFFICER, BusinessUser::ROLE_INSPECTOR, BusinessUser::ROLE_TRANSPORT_MANAGER];
        $role = $roles[$idx % count($roles)];

        $email = "team.p{$p}.e{$e}.i{$idx}@demo.rw";

        return User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $role.' — P'.$p,
                'password' => $password,
                'email_verified_at' => now(),
                'is_super_admin' => false,
            ]
        );
    }

    private function assignProcessorRole(User $user, Business $biz, int $idx): void
    {
        $roles = [BusinessUser::ROLE_OPERATIONS_MANAGER, BusinessUser::ROLE_COMPLIANCE_OFFICER, BusinessUser::ROLE_INSPECTOR, BusinessUser::ROLE_TRANSPORT_MANAGER];
        $role = $roles[$idx % count($roles)];
        $this->attachRole($user, $biz, $role);
    }

    private function makeFarmerTeamUser(int $n, string $password, Business $biz): void
    {
        $u = User::query()->updateOrCreate(
            ['email' => "team.farmer.{$n}@demo.rw"],
            [
                'name' => 'Herd lead '.$n,
                'password' => $password,
                'email_verified_at' => now(),
                'is_super_admin' => false,
            ]
        );
        $this->attachRole($u, $biz, BusinessUser::ROLE_OPERATIONS_MANAGER);
    }

    private function makeLogisticsTeamUser(int $n, string $password, Business $biz): void
    {
        $u = User::query()->updateOrCreate(
            ['email' => "team.logistics.{$n}@demo.rw"],
            [
                'name' => 'Dispatch '.$n,
                'password' => $password,
                'email_verified_at' => now(),
                'is_super_admin' => false,
            ]
        );
        $this->attachRole($u, $biz, BusinessUser::ROLE_TRANSPORT_MANAGER);
    }

    /**
     * @param  array{farmers: mixed, logistics: mixed, processors: list}  $ctx
     */
    private function seedProcessorVerticals(
        array $ctx,
        AdministrativeDivision $country,
        string $password,
        string $kgUnit,
        Carbon $rangeStart,
        Carbon $rangeEnd,
    ): void {
        $unitPriceBase = 350_000;
        $speciesRot = [AnimalIntake::SPECIES_CATTLE, AnimalIntake::SPECIES_GOAT, AnimalIntake::SPECIES_SHEEP, AnimalIntake::SPECIES_PIG];

        foreach ($ctx['processors'] as $pIdx => $proc) {
            $biz = $proc['business'];
            $sh = $proc['slaughter'];
            $suppliers = collect();
            $clients = collect();
            for ($s = 0; $s < 32; $s++) {
                $suppliers->push($this->makeSupplier($biz, $s + $pIdx * 100, $country));
            }
            for ($c = 0; $c < 32; $c++) {
                $clients->push($this->makeClient($biz, $c + $pIdx * 100, $proc['butchery'], $country));
            }
            foreach ($suppliers as $s) {
                $this->makeContractSupplier($biz, $s, $unitPriceBase + random_int(0, 2_000_000));
            }
            foreach ($clients->take(20) as $c) {
                $this->makeContractCustomer($biz, $c, random_int(1_000_000, 8_000_000));
            }
            $empTitles = array_keys(Employee::JOB_TITLES);
            for ($e = 0; $e < 40; $e++) {
                $title = $empTitles[$e % count($empTitles)];
                $f = $e % 3 === 0 ? $sh : ($e % 3 === 1 ? $proc['butchery'] : $proc['storage']);
                Employee::query()->create([
                    'business_id' => $biz->id,
                    'facility_id' => $f->id,
                    'first_name' => explode(' ', RwandaSeederHelper::fullName($e + $pIdx * 50))[0] ?? 'Jean',
                    'last_name' => explode(' ', RwandaSeederHelper::fullName($e + $pIdx * 50))[1] ?? 'Bizimana',
                    'national_id' => 'SEED-NI-PR'.$pIdx.'-'.$e,
                    'date_of_birth' => $rangeStart->copy()->addMonths(random_int(240, 600)),
                    'nationality' => 'Rwandan',
                    'work_email' => "emp.{$pIdx}.{$e}@demo-employee.rw",
                    'phone' => RwandaSeederHelper::phone(5000 + $e + $pIdx * 100),
                    'job_title' => $title,
                    'employment_type' => 'full_time',
                    'hire_date' => RwandaSeederHelper::dateInRange($rangeStart, $rangeEnd, $e, 40)->toDateString(),
                    'status' => 'active',
                ]);
            }
            for ($k = 0; $k < 44; $k++) {
                $global = $k + $pIdx * 44;
                $intakeTime = RwandaSeederHelper::dateInRange($rangeStart, $rangeEnd, $global, 44 * 5);
                $species = $speciesRot[$global % count($speciesRot)];
                $nAnimals = random_int(12, 36);
                $supplier = $suppliers->random();
                $contract = Contract::query()
                    ->where('business_id', $biz->id)
                    ->where('contract_category', Contract::CATEGORY_SUPPLIER)
                    ->where('supplier_id', $supplier->id)
                    ->first();
                $chain = $this->randomDivisionChain(
                    AdministrativeDivision::byParent($country->id)->inRandomOrder()->first()
                );
                $intake = AnimalIntake::query()->create([
                    'facility_id' => $sh->id,
                    'source_type' => AnimalIntake::SOURCE_TYPE_SUPPLIER,
                    'supplier_id' => $supplier->id,
                    'contract_id' => $contract?->id,
                    'intake_date' => $intakeTime->toDateString(),
                    'supplier_firstname' => $supplier->first_name,
                    'supplier_lastname' => $supplier->last_name,
                    'supplier_contact' => $supplier->phone,
                    'farm_name' => 'Ubworozi co-op lot '.($k + 1),
                    'country_id' => $country->id,
                    'province_id' => $chain['province']->id,
                    'district_id' => $chain['district']->id,
                    'sector_id' => $chain['sector']->id,
                    'cell_id' => $chain['cell']?->id,
                    'village_id' => $chain['village']?->id,
                    'species' => $species,
                    'number_of_animals' => $nAnimals,
                    'unit_price' => (float) (random_int(220, 480) * 1000),
                    'status' => AnimalIntake::STATUS_APPROVED,
                    'transport_vehicle_plate' => 'RAB '.random_int(100, 900).' '.chr(65 + ($k % 26)),
                    'driver_name' => RwandaSeederHelper::fullName($global),
                    'animal_health_certificate_number' => 'AHC-SEED-'.str_pad((string) $global, 5, '0', STR_PAD_LEFT),
                    'health_certificate_issue_date' => $intakeTime->copy()->subDays(10),
                    'health_certificate_expiry_date' => $intakeTime->copy()->addMonths(4),
                ]);
                $inspector = $proc['inspectors']->random();
                $slaughterDay = $intakeTime->copy()->addDays(random_int(1, 5))->setTime(6, 0, 0);
                $slaughterTime = $slaughterDay->copy()->addHours(2);
                $plan = SlaughterPlan::query()->create([
                    'slaughter_date' => $slaughterDay->toDateString(),
                    'facility_id' => $sh->id,
                    'animal_intake_id' => $intake->id,
                    'inspector_id' => $inspector->id,
                    'species' => $species,
                    'number_of_animals_scheduled' => $nAnimals,
                    'status' => SlaughterPlan::STATUS_APPROVED,
                ]);
                $ante = AnteMortemInspection::query()->create([
                    'slaughter_plan_id' => $plan->id,
                    'inspector_id' => $inspector->id,
                    'species' => $species,
                    'number_examined' => $nAnimals,
                    'number_approved' => $nAnimals,
                    'number_rejected' => 0,
                    'notes' => 'Ante-mortem seed — '.$intake->farm_name,
                    'inspection_date' => $slaughterDay->toDateString(),
                ]);
                foreach (RwandaSeederHelper::anteMortemObservationPayload($species) as $row) {
                    AnteMortemObservation::query()->create(array_merge($row, [
                        'ante_mortem_inspection_id' => $ante->id,
                    ]));
                }
                $exec = SlaughterExecution::query()->create([
                    'slaughter_plan_id' => $plan->id,
                    'actual_animals_slaughtered' => $nAnimals,
                    'slaughter_time' => $slaughterTime,
                    'status' => SlaughterExecution::STATUS_COMPLETED,
                ]);
                $batch = Batch::query()->create([
                    'slaughter_execution_id' => $exec->id,
                    'inspector_id' => $inspector->id,
                    'species' => $species,
                    'quantity' => $nAnimals,
                    'quantity_unit' => $kgUnit,
                    'status' => Batch::STATUS_APPROVED,
                ]);
                $pm = PostMortemInspection::query()->create([
                    'batch_id' => $batch->id,
                    'inspector_id' => $inspector->id,
                    'species' => $species,
                    'total_examined' => $nAnimals,
                    'approved_quantity' => $nAnimals,
                    'condemned_quantity' => 0,
                    'notes' => 'Post-mortem seed',
                    'inspection_date' => $slaughterDay->toDateString(),
                    'result' => PostMortemInspection::RESULT_APPROVED,
                ]);
                foreach (RwandaSeederHelper::postMortemObservationPayload($species) as $row) {
                    PostMortemObservation::query()->create(array_merge($row, [
                        'post_mortem_inspection_id' => $pm->id,
                    ]));
                }
                $this->certCounter++;
                $cert = Certificate::query()->create([
                    'batch_id' => $batch->id,
                    'inspector_id' => $inspector->id,
                    'facility_id' => $sh->id,
                    'certificate_number' => 'CERT-SEED-'.str_pad((string) $this->certCounter, 5, '0', STR_PAD_LEFT),
                    'issued_at' => $slaughterDay->copy()->addDay()->toDateString(),
                    'expiry_date' => $slaughterDay->copy()->addMonths(6)->toDateString(),
                    'status' => Certificate::STATUS_ACTIVE,
                ]);
                $cert->certificateQr()->create(['slug' => CertificateQr::generateSlug()]);
            }
        }
    }

    /**
     * @param  array{processors: list}  $ctx
     */
    private function seedVolumeCrmAndColdChain(array $ctx, string $kgUnit, Carbon $rangeStart, Carbon $rangeEnd): void
    {
        $year = (int) date('Y');
        $species = \App\Models\Species::query()->active()->pluck('name')->toArray() ?: ['Cattle', 'Goat'];
        $n = 0;
        foreach ($ctx['processors'] as $pIdx => $proc) {
            $biz = $proc['business'];
            $dest = $proc['butchery'];
            $clientIds = Client::query()->where('business_id', $biz->id)->pluck('id');
            for ($i = 0; $i < 40; $i++) {
                $n++;
                $num = 50000 + $n;
                $statuses = [Demand::STATUS_DRAFT, Demand::STATUS_CONFIRMED, Demand::STATUS_IN_PROGRESS, Demand::STATUS_FULFILLED, Demand::STATUS_CANCELLED];
                $st = $statuses[$n % count($statuses)];
                Demand::query()->create([
                    'business_id' => $biz->id,
                    'demand_number' => "DEM-SEED-{$year}-".str_pad((string) $num, 5, '0', STR_PAD_LEFT),
                    'title' => 'Wholesale order '.$n.' — '.$biz->business_name,
                    'destination_facility_id' => $dest->id,
                    'client_id' => $clientIds->isNotEmpty() ? $clientIds->random() : null,
                    'species' => $species[array_rand($species)],
                    'product_description' => 'Fresh meat — Rwanda (seeded)',
                    'quantity' => (string) random_int(40, 800),
                    'quantity_unit' => $kgUnit,
                    'requested_delivery_date' => RwandaSeederHelper::dateInRange($rangeStart, $rangeEnd, $n, 200)->addDays(random_int(1, 30))->toDateString(),
                    'status' => $st,
                    'notes' => 'RWF / kg — seeded volume demand',
                ]);
            }
        }

        $n = 0;
        $types = [ClientActivity::TYPE_CALL, ClientActivity::TYPE_EMAIL, ClientActivity::TYPE_MEETING, ClientActivity::TYPE_NOTE];
        foreach (Client::query()->where('is_active', true)->get() as $client) {
            if ($n >= 200) {
                break;
            }
            $ownerUser = Business::query()->find($client->business_id)?->user_id;
            $u = $ownerUser ? User::query()->find($ownerUser) : User::query()->inRandomOrder()->first();
            for ($a = 0; $a < 2 && $n < 200; $a++) {
                $n++;
                $t = $types[array_rand($types)];
                ClientActivity::query()->create([
                    'business_id' => $client->business_id,
                    'client_id' => $client->id,
                    'activity_type' => $t,
                    'subject' => Str::ucfirst($t).' follow-up '.$n,
                    'notes' => 'Nyagatare / Kigali corridor — customer relationship (seeded).',
                    'occurred_at' => RwandaSeederHelper::dateInRange($rangeStart, $rangeEnd, $n, 200),
                    'user_id' => $u?->id,
                ]);
            }
        }

        $wIdx = 0;
        $storages = collect();
        foreach (Certificate::query()->with(['batch', 'facility.business'])->orderBy('id')->limit(300)->get() as $cert) {
            if (! $cert->batch) {
                continue;
            }
            $biz = $cert->facility?->business;
            if (! $biz) {
                continue;
            }
            $wh = $biz->facilities()->where('facility_type', Facility::TYPE_STORAGE)->first();
            if (! $wh) {
                continue;
            }
            if (WarehouseStorage::query()->where('certificate_id', $cert->id)->exists()) {
                $storages->push(WarehouseStorage::query()->where('certificate_id', $cert->id)->first());

                continue;
            }
            $wIdx++;
            $en = Carbon::parse((string) $cert->issued_at);
            $storages->push(WarehouseStorage::query()->create([
                'warehouse_facility_id' => $wh->id,
                'batch_id' => $cert->batch_id,
                'certificate_id' => $cert->id,
                'entry_date' => $en->toDateString(),
                'storage_location' => 'Cold room '.chr(64 + ($wIdx % 3) + 1),
                'temperature_at_entry' => -19 + (random_int(0, 8) / 10),
                'quantity_stored' => (float) $cert->batch->quantity,
                'quantity_unit' => $kgUnit,
                'status' => WarehouseStorage::STATUS_IN_STORAGE,
            ]));
        }
        for ($i = 0; $i < 200; $i++) {
            $ws = $storages->get($i % max(1, $storages->count()));
            if (! $ws) {
                break;
            }
            $t = (float) (-18.0 + random_int(-5, 5) / 10.0);
            $st = $t >= -15.0
                ? ($t >= -12.0 ? TemperatureLog::STATUS_CRITICAL : TemperatureLog::STATUS_WARNING)
                : TemperatureLog::STATUS_NORMAL;
            TemperatureLog::query()->create([
                'warehouse_storage_id' => $ws->id,
                'recorded_at' => RwandaSeederHelper::dateInRange($rangeStart, $rangeEnd, $i, 200),
                'recorded_temperature' => round($t, 2),
                'recorded_by' => 'IoT / manual (seed)',
                'status' => $st,
            ]);
        }

        $certs = Certificate::query()->with(['batch', 'facility'])->orderBy('id')->limit(200)->get();
        $allFac = Facility::query()->whereIn('facility_type', [Facility::TYPE_BUTCHERY, Facility::TYPE_SLAUGHTERHOUSE])->get();
        foreach ($certs as $i => $cert) {
            if (! $cert->batch || ! $cert->facility) {
                continue;
            }
            $dest = $allFac->where('id', '!=', $cert->facility_id)->random();
            TransportTrip::query()->firstOrCreate(
                [
                    'certificate_id' => $cert->id,
                    'origin_facility_id' => $cert->facility_id,
                ],
                [
                    'batch_id' => $cert->batch_id,
                    'destination_facility_id' => $dest->id,
                    'vehicle_plate_number' => 'RAB '.random_int(200, 899).' '.chr(65 + ($i % 26)),
                    'driver_name' => RwandaSeederHelper::fullName(800 + $i),
                    'driver_phone' => RwandaSeederHelper::phone(10000 + $i),
                    'departure_date' => RwandaSeederHelper::dateInRange($rangeStart, $rangeEnd, $i, 200),
                    'arrival_date' => RwandaSeederHelper::dateInRange($rangeStart, $rangeEnd, $i, 200)->addHours(3),
                    'status' => TransportTrip::STATUS_ARRIVED,
                ]
            );
        }
        $trips = TransportTrip::query()->where('status', TransportTrip::STATUS_ARRIVED)->limit(120)->get();
        foreach ($trips as $i => $trip) {
            $origBiz = $trip->originFacility?->business_id;
            $c = $origBiz
                ? Client::query()->where('business_id', $origBiz)->inRandomOrder()->first()
                : null;
            DeliveryConfirmation::query()->create([
                'transport_trip_id' => $trip->id,
                'receiving_facility_id' => $trip->destination_facility_id,
                'client_id' => $c?->id,
                'contract_id' => $c && $origBiz
                    ? Contract::query()
                        ->where('business_id', $origBiz)
                        ->where('client_id', $c->id)
                        ->value('id')
                    : null,
                'received_quantity' => (string) (random_int(100, 400)),
                'received_date' => $trip->arrival_date,
                'receiver_name' => 'Receiver '.$i,
                'receiver_country' => 'Rwanda',
                'receiver_address' => 'Kigali / Eastern Province (seeded)',
                'confirmation_status' => DeliveryConfirmation::STATUS_CONFIRMED,
            ]);
        }
    }

    /**
     * @param  array{farmers: list, processors: list}  $ctx
     */
    private function seedFarmerEcosystem(array $ctx, Carbon $rangeStart, Carbon $rangeEnd): void
    {
        $farmer = $ctx['farmers'][0];
        $biz = $farmer['business'];
        $farms = $farmer['farms'];
        foreach ($farms as $idx => $farm) {
            for ($h = 0; $h < 10; $h++) {
                $type = $h % 2 === 0 ? FarmerAnimalType::CATTLE : FarmerAnimalType::GOAT;
                $breed = $h % 2 === 0
                    ? 'Ankole herd '.($farm->id).'-'.$h
                    : 'East African herd '.($farm->id).'-'.$h;
                Livestock::query()->create([
                    'farm_id' => $farm->id,
                    'type' => $type,
                    'breed' => $breed,
                    'feeding_type' => Livestock::FEEDING_PASTURE,
                    'total_quantity' => random_int(4, 40),
                    'available_quantity' => random_int(2, 30),
                    'base_price' => (string) random_int(250_000, 420_000),
                    'health_status' => Livestock::HEALTH_GOOD,
                    'healthy_quantity' => random_int(2, 30),
                    'sick_quantity' => 0,
                ]);
            }
        }

        $processors = collect($ctx['processors']);
        $lives = Livestock::query()->whereIn('farm_id', $farms->pluck('id'))->get();
        for ($i = 0; $i < 200; $i++) {
            $proc = $processors->random();
            $dest = $proc['slaughter'];
            $farm = $farms->random();
            $live = $lives->where('farm_id', $farm->id)->random();
            $st = [SupplyRequest::STATUS_PENDING, SupplyRequest::STATUS_ACCEPTED, SupplyRequest::STATUS_FULFILLED, SupplyRequest::STATUS_REJECTED];
            SupplyRequest::query()->create([
                'processor_id' => $proc['business']->id,
                'farmer_id' => $biz->id,
                'destination_facility_id' => $dest->id,
                'animal_type' => $live->type,
                'quantity_requested' => random_int(2, 15),
                'required_breed' => $live->breed,
                'required_weight' => (string) random_int(180, 480).' kg',
                'healthy_stock_required' => true,
                'certification_required' => (bool) random_int(0, 1),
                'required_certification_type' => 'Local authorities',
                'preferred_date' => RwandaSeederHelper::dateInRange($rangeStart, $rangeEnd, $i, 200)->addDays(5)->toDateString(),
                'status' => $st[$i % count($st)],
                'source_farm_id' => $farm->id,
                'requested_livestock_id' => $live->id,
            ]);
        }
    }

    /**
     * @param  array{logistics: array, processors: list}  $ctx
     */
    private function seedLogisticsOperations(array $ctx, AdministrativeDivision $country, Carbon $rangeStart, Carbon $rangeEnd): void
    {
        $logisticsBiz = $ctx['logistics']['business'];
        $company = LogisticsCompany::query()->create([
            'business_id' => $logisticsBiz->id,
            'company_type' => LogisticsCompany::TYPE_SHARED_COMPANY,
            'name' => 'Kigali Cold Chain Fleet',
            'registration_number' => 'LGC-RW-SEED-001',
            'tax_id' => 'TIN-SEED-LOG-001',
            'license_type' => 'inter_provincial',
            'license_expiry_date' => now()->addYear(),
            'operating_regions' => ['Kigali', 'Eastern', 'Northern'],
            'contact_person' => 'Dispatch — '.RwandaSeederHelper::fullName(44),
            'country_id' => $country->id,
        ]);
        for ($v = 0; $v < 6; $v++) {
            LogisticsVehicle::query()->create([
                'company_id' => $company->id,
                'plate_number' => 'RSE '.str_pad((string) (10 + $v), 3, '0', STR_PAD_LEFT).' '.chr(65 + $v),
                'type' => LogisticsVehicle::TYPE_REFRIGERATED_TRUCK,
                'max_weight' => 12000,
                'max_units' => 1,
                'capacity_value' => 12,
                'capacity_unit' => LogisticsVehicle::CAPACITY_UNIT_TONS,
                'vehicle_features' => [LogisticsVehicle::FEATURE_TEMPERATURE_CONTROL, LogisticsVehicle::FEATURE_GPS_TRACKING],
                'status' => LogisticsVehicle::STATUS_AVAILABLE,
            ]);
        }
        for ($d = 0; $d < 6; $d++) {
            LogisticsDriver::query()->create([
                'company_id' => $company->id,
                'name' => RwandaSeederHelper::fullName(200 + $d),
                'first_name' => explode(' ', RwandaSeederHelper::fullName(200 + $d))[0] ?? 'Jean',
                'last_name' => explode(' ', RwandaSeederHelper::fullName(200 + $d))[1] ?? 'Habimana',
                'license_number' => 'LIC-DRV-SEED-'.str_pad((string) $d, 4, '0', STR_PAD_LEFT),
                'license_expiry' => now()->addYear(),
                'status' => LogisticsDriver::STATUS_AVAILABLE,
                'phone_number' => RwandaSeederHelper::phone(300 + $d),
            ]);
        }
        $locs = collect([
            Location::query()->create(['name' => 'Nyagatare pickup yard', 'address' => 'Eastern Province']),
            Location::query()->create(['name' => 'Kigali cold distribution', 'address' => 'Kigali']),
            Location::query()->create(['name' => 'Muhanga weighbridge', 'address' => 'Southern Province check']),
        ]);
        $vehicles = $company->vehicles;
        $drivers = $company->drivers;
        for ($i = 0; $i < 50; $i++) {
            $order = new LogisticsOrder([
                'company_id' => $company->id,
                'service_type' => LogisticsOrder::SERVICE_TYPE_LOCAL,
                'transport_mode' => LogisticsOrder::TRANSPORT_MODE_ROAD,
                'status' => LogisticsOrder::STATUS_CONFIRMED,
                'pickup_location' => 'Farmer / processor handoff '.$i,
                'delivery_location' => 'Kigali DC '.$i,
                'total_weight' => (string) random_int(800, 4000),
                'total_volume' => '0',
                'special_instructions' => 'Temperature monitored — Rwanda seed',
            ]);
            $order->save();
            $tStart = RwandaSeederHelper::dateInRange($rangeStart, $rangeEnd, $i, 50);
            $trip = LogisticsTrip::query()->create([
                'company_id' => $company->id,
                'order_id' => $order->id,
                'vehicle_id' => $vehicles->get($i % $vehicles->count())->id,
                'driver_id' => $drivers->get($i % $drivers->count())->id,
                'origin_location_id' => $locs[0]->id,
                'destination_location_id' => $locs[1]->id,
                'allocated_weight_kg' => (int) round((float) $order->total_weight),
                'delivered_weight_kg' => (int) round((float) $order->total_weight * 0.99),
                'loss_weight_kg' => (int) round((float) $order->total_weight * 0.01),
                'planned_departure' => $tStart,
                'planned_arrival' => $tStart->copy()->addHours(4),
                'actual_departure' => $tStart,
                'actual_arrival' => $tStart->copy()->addHours(4),
                'status' => LogisticsTrip::STATUS_COMPLETED,
            ]);
            for ($g = 0; $g < 4; $g++) {
                LogisticsTrackingLog::query()->create([
                    'trip_id' => $trip->id,
                    'location_id' => $g % 2 === 0 ? $locs[0]->id : $locs[2]->id,
                    'latitude' => (string) (-1.95 + (random_int(0, 20) / 1000)),
                    'longitude' => (string) (30.05 + (random_int(0, 20) / 1000)),
                    'status' => $g < 2 ? LogisticsTrackingLog::STATUS_IN_TRANSIT : LogisticsTrackingLog::STATUS_ARRIVED,
                    'event_time' => $tStart->copy()->addHours($g),
                    'notes' => 'Checkpoint / GPS ping (seed)',
                ]);
            }
        }
    }

    /**
     * If the DB was partially re-seeded (e.g. AdministrativeDivisionSeeder cleared FKs) or older runs omitted fields, repair what we can safely.
     */
    private function backfillMissingRegistrationFieldsOnSeededBusinesses(): void
    {
        foreach ([
            Business::TYPE_PROCESSOR => [Business::BASELINE_REVENUE_BRACKET_2M_20M, 'medium'],
            Business::TYPE_LOGISTICS => [Business::BASELINE_REVENUE_BRACKET_2M_20M, 'medium'],
            Business::TYPE_FARMER => [Business::BASELINE_REVENUE_BRACKET_LT_2M, 'small'],
        ] as $type => [$bracket, $size]) {
            Business::query()
                ->where('registration_number', 'like', self::REG_PREFIX.'%')
                ->where('type', $type)
                ->where(function ($w) {
                    $w->whereNull('baseline_revenue')->orWhere('baseline_revenue', '');
                })
                ->update(['baseline_revenue' => $bracket, 'business_size' => $size]);
        }
    }

    private function printCredentials(): void
    {
        $this->command?->newLine();
        $this->command?->info('Comprehensive Rwanda seed complete.');
        $this->command?->info('Tenant demo accounts: password: password');
        $this->command?->info('  Super Admin:  superadmin@dayare.me  — password: superadmin');
        $this->command?->info('  Farmers:      owner.farmer@demo.rw + team.farmer.0-1@demo.rw');
        $this->command?->info('  Logistics:   owner.logistics@demo.rw + team.logistics.0-1@demo.rw');
        $this->command?->info('  Processors:  owner.processor.1-5@demo.rw + team.p{n}.e*.i*@demo.rw (ops/compliance/inspector/transport)');
    }

    private function createBusiness(
        User $owner,
        string $reg,
        string $name,
        string $type,
        array $loc,
    ): Business {
        return Business::query()->create([
            'user_id' => $owner->id,
            'type' => $type,
            'business_name' => $name,
            'registration_number' => $reg,
            'tax_id' => 'TIN-'.str_replace('-', '', $reg),
            'contact_phone' => RwandaSeederHelper::phone(random_int(1, 9999)),
            'email' => Str::lower(str_replace(' ', '', $name)).'@demo-business.rw',
            'status' => Business::STATUS_ACTIVE,
            'owner_first_name' => explode(' ', $name)[0] ?? 'Owner',
            'owner_last_name' => 'Representative',
            'ownership_type' => 'cooperative',
            'business_size' => match ($type) {
                Business::TYPE_PROCESSOR, Business::TYPE_LOGISTICS => 'medium',
                default => 'small',
            },
            'baseline_revenue' => match ($type) {
                Business::TYPE_PROCESSOR => Business::BASELINE_REVENUE_BRACKET_2M_20M,
                Business::TYPE_LOGISTICS => Business::BASELINE_REVENUE_BRACKET_2M_20M,
                default => Business::BASELINE_REVENUE_BRACKET_LT_2M,
            },
            'pathway_status' => Business::PATHWAY_STATUSES[0] ?? 'active',
            'country_id' => $loc['country']?->id,
            'province_id' => $loc['province']->id,
            'district_id' => $loc['district']->id,
            'sector_id' => $loc['sector']->id,
            'cell_id' => $loc['cell']?->id,
            'village_id' => $loc['village']?->id,
        ]);
    }

    private function makeFacility(Business $biz, string $name, string $type, array $loc, ?int $cap = null): Facility
    {
        return Facility::query()->create([
            'business_id' => $biz->id,
            'facility_name' => $name,
            'facility_type' => $type,
            'district' => $loc['district']->name,
            'sector' => $loc['sector']->name,
            'country_id' => $loc['country']?->id,
            'province_id' => $loc['province']->id,
            'district_id' => $loc['district']->id,
            'sector_id' => $loc['sector']->id,
            'cell_id' => $loc['cell']?->id,
            'village_id' => $loc['village']?->id,
            'license_number' => 'LIC-SEED-'.Str::upper(Str::random(6)),
            'license_issue_date' => now()->subYear(),
            'license_expiry_date' => now()->addYear(),
            'daily_capacity' => $cap ?? ($type === Facility::TYPE_SLAUGHTERHOUSE ? 60 : 200),
            'status' => Facility::STATUS_ACTIVE,
        ]);
    }

    private function makeInspector(Facility $f, int $p, int $n): Inspector
    {
        $f->load(['districtDivision', 'sectorDivision', 'cell', 'village']);
        $fn = RwandaSeederHelper::fullName(50 * $p + $n);

        return Inspector::query()->create([
            'facility_id' => $f->id,
            'first_name' => explode(' ', $fn)[0] ?? 'Inspector',
            'last_name' => explode(' ', $fn)[1] ?? 'Mbanje',
            'national_id' => 'NI-SEED-INS-'.$f->id.'-'.$n,
            'phone_number' => RwandaSeederHelper::phone(600 + $n + $p * 5),
            'email' => "inspector.p{$p}.{$n}@demo.rw",
            'dob' => now()->subYears(32)->toDateString(),
            'nationality' => 'Rwandan',
            'country' => 'Rwanda',
            'district' => $f->district ?: $f->districtDivision?->name ?? 'Rwanda',
            'sector' => $f->sector ?: $f->sectorDivision?->name ?? '',
            'cell' => $f->cell?->name,
            'village' => $f->village?->name,
            'authorization_number' => 'AUTH-SEED-'.Str::upper(Str::random(5)),
            'authorization_issue_date' => now()->subYear(),
            'authorization_expiry_date' => now()->addYear(),
            'species_allowed' => 'Cattle, Goat, Sheep, Pig',
            'daily_capacity' => 150,
            'stamp_serial_number' => 'STAMP-'.random_int(1000, 9999),
            'status' => Inspector::STATUS_ACTIVE,
        ]);
    }

    private function makeSupplier(Business $biz, int $seed, AdministrativeDivision $country): Supplier
    {
        $name = RwandaSeederHelper::fullName($seed);
        $parts = explode(' ', $name);

        $chain = $this->randomDivisionChain(AdministrativeDivision::byParent($country->id)->inRandomOrder()->first());

        return Supplier::query()->create([
            'business_id' => $biz->id,
            'first_name' => $parts[0] ?? 'Sup',
            'last_name' => $parts[1] ?? 'Plier',
            'date_of_birth' => now()->subYears(38)->toDateString(),
            'nationality' => 'Rwandan',
            'registration_number' => 'SUP-SEED-'.str_pad((string) $seed, 5, '0', STR_PAD_LEFT),
            'type' => 'livestock_supply',
            'phone' => RwandaSeederHelper::phone(7000 + $seed),
            'email' => 'supplier.'.$seed.'@seed.rw',
            'address_line_1' => $chain['district']->name.', Rwanda',
            'country_id' => $country->id,
            'province_id' => $chain['province']->id,
            'district_id' => $chain['district']->id,
            'sector_id' => $chain['sector']->id,
            'is_active' => true,
            'supplier_status' => Supplier::STATUS_APPROVED,
        ]);
    }

    private function makeClient(Business $biz, int $seed, ?Facility $pref, AdministrativeDivision $country): Client
    {
        $names = ['Umuriro Butchery', 'Chez John Restaurant', 'Musanze Market', 'Eastern Canteen', 'Huye Hotel', 'Gisenyi Distributor', 'Kirehe School'];
        $label = $names[$seed % count($names)].' #'.$seed;
        $chain = $this->randomDivisionChain(AdministrativeDivision::byParent($country->id)->inRandomOrder()->first());

        return Client::query()->create([
            'business_id' => $biz->id,
            'name' => $label,
            'contact_person' => RwandaSeederHelper::fullName(900 + $seed),
            'email' => 'client-'.$seed.'@market.rw',
            'phone' => RwandaSeederHelper::phone(4000 + $seed),
            'country' => 'Rwanda',
            'country_id' => $country->id,
            'province_id' => $chain['province']->id,
            'district_id' => $chain['district']->id,
            'business_type' => Client::BUSINESS_TYPE_BUTCHERY,
            'address_line_1' => $chain['district']->name,
            'preferred_facility_id' => $pref?->id,
            'preferred_species' => 'Cattle',
            'is_active' => true,
        ]);
    }

    private function makeContractSupplier(Business $biz, Supplier $s, int $amount): Contract
    {
        $num = 'C-S-'.$biz->id.'-'.$s->id;

        return Contract::query()->create([
            'business_id' => $biz->id,
            'contract_category' => Contract::CATEGORY_SUPPLIER,
            'supplier_id' => $s->id,
            'contract_number' => $num,
            'title' => 'Livestock supply',
            'type' => Contract::TYPE_LIVESTOCK_SUPPLY,
            'start_date' => now()->subYear(),
            'end_date' => now()->addYear(),
            'status' => Contract::STATUS_ACTIVE,
            'amount' => $amount,
        ]);
    }

    private function makeContractCustomer(Business $biz, Client $c, int $amount): Contract
    {
        $num = 'C-C-'.$biz->id.'-'.$c->id;

        return Contract::query()->create([
            'business_id' => $biz->id,
            'contract_category' => Contract::CATEGORY_CUSTOMER,
            'client_id' => $c->id,
            'contract_number' => $num,
            'title' => 'Meat sales',
            'type' => Contract::TYPE_SALE_AGREEMENT,
            'start_date' => now()->subYear(),
            'end_date' => now()->addYear(),
            'status' => Contract::STATUS_ACTIVE,
            'amount' => $amount,
        ]);
    }

    private function attachRole(User $user, Business $biz, string $role): void
    {
        BusinessUser::query()->updateOrCreate(
            ['business_id' => $biz->id, 'user_id' => $user->id],
            ['role' => $role]
        );
    }

    /**
     * @return array{country: ?AdministrativeDivision, province: AdministrativeDivision, district: AdministrativeDivision, sector: AdministrativeDivision, cell: ?AdministrativeDivision, village: ?AdministrativeDivision}
     */
    private function randomDivisionChain(AdministrativeDivision $province): array
    {
        $country = $province->parent_id
            ? AdministrativeDivision::query()->find($province->parent_id)
            : null;
        $districts = AdministrativeDivision::byParent($province->id)->inRandomOrder()->limit(4)->get();
        $district = $districts->isNotEmpty() ? $districts->random() : $province;
        $sectors = AdministrativeDivision::byParent($district->id)->inRandomOrder()->limit(4)->get();
        $sector = $sectors->isNotEmpty() ? $sectors->random() : $district;
        $cells = AdministrativeDivision::byParent($sector->id)->inRandomOrder()->limit(4)->get();
        $cell = $cells->isNotEmpty() ? $cells->random() : null;
        $villages = $cell
            ? AdministrativeDivision::byParent($cell->id)->inRandomOrder()->limit(4)->get()
            : collect();
        $village = $villages->isNotEmpty() ? $villages->random() : null;

        return [
            'country' => $country,
            'province' => $province,
            'district' => $district,
            'sector' => $sector,
            'cell' => $cell,
            'village' => $village,
        ];
    }
}
