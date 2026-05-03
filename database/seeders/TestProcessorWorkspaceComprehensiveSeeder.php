<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AdministrativeDivision;
use App\Models\AnimalIntake;
use App\Models\AnteMortemInspection;
use App\Models\AnteMortemObservation;
use App\Models\Batch;
use App\Models\Business;
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
use App\Models\PostMortemInspection;
use App\Models\PostMortemObservation;
use App\Models\SlaughterExecution;
use App\Models\SlaughterPlan;
use App\Models\Species;
use App\Models\Supplier;
use App\Models\SupplyRequest;
use App\Models\TemperatureLog;
use App\Models\TransportTrip;
use App\Models\Unit;
use App\Models\User;
use App\Models\WarehouseStorage;
use Carbon\Carbon;
use Database\Seeders\Support\ProcessorFinanceSync;
use Database\Seeders\Support\RwandaSeederHelper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Full processor-workspace demo for test@example.com (REG-TEST-001 / REG-TEST-002):
 * intake → slaughter → inspections → certificates → cold storage → transport → delivery → CRM → finance sync.
 * Chronological data from 2022-01-01 through 2026-05-03 (Rwanda context, RWF).
 *
 * Idempotent: skips if intakes with health certificate prefix AHC-TEST-COMP already exist.
 */
class TestProcessorWorkspaceComprehensiveSeeder extends Seeder
{
    private const RANGE_START = '2022-01-01';

    private const RANGE_END = '2026-05-03';

    private const HEALTH_CERT_PREFIX = 'AHC-TEST-COMP';

    private const CERT_NUMBER_PREFIX = 'CERT-TEST-COMP';

    /** @var list<string> */
    private const REGISTRATIONS = ['REG-TEST-001', 'REG-TEST-002'];

    private int $certCounter = 0;

    private int $demandSeq = 0;

    public function run(): void
    {
        $user = User::query()->where('email', 'test@example.com')->first();
        if (! $user) {
            $this->command?->warn('test@example.com not found. Run TestLoginSeeder / TestDataSeeder first.');

            return;
        }

        if (AnimalIntake::query()->where('animal_health_certificate_number', 'like', self::HEALTH_CERT_PREFIX.'%')->exists()) {
            $this->command?->info('Test processor comprehensive workspace already seeded (AHC-TEST-COMP* intakes). Skipping.');

            return;
        }

        $country = AdministrativeDivision::ofType(AdministrativeDivision::TYPE_COUNTRY)->first();
        if (! $country) {
            $this->command?->error('Administrative divisions missing. Skipping test processor workspace seed.');

            return;
        }

        $businesses = Business::query()
            ->whereIn('registration_number', self::REGISTRATIONS)
            ->where('user_id', $user->id)
            ->where('type', Business::TYPE_PROCESSOR)
            ->orderBy('registration_number')
            ->get();

        if ($businesses->isEmpty()) {
            $this->command?->warn('No REG-TEST-001/002 processor businesses for test@example.com. Run TestDataSeeder first.');

            return;
        }

        $rangeStart = Carbon::parse(self::RANGE_START)->startOfDay();
        $rangeEnd = Carbon::parse(self::RANGE_END)->endOfDay();
        $kgUnit = Unit::query()->where('code', 'kg')->value('code') ?: 'kg';

        $provinces = AdministrativeDivision::byParent($country->id)->get();
        if ($provinces->isEmpty()) {
            $this->command?->error('No provinces for Rwanda country. Skipping.');

            return;
        }

        foreach ($businesses as $business) {
            $ctx = $this->prepareBusinessContext($business, $country, $provinces);
            $intakeCount = $business->registration_number === 'REG-TEST-001' ? 88 : 56;
            $this->seedIntakePipelineForBusiness(
                $business,
                $ctx,
                $country,
                $provinces,
                $rangeStart,
                $rangeEnd,
                $kgUnit,
                $intakeCount,
            );
            $this->seedDemandsForBusiness($business, $ctx, $rangeStart, $rangeEnd, $kgUnit);
            $this->seedClientActivitiesForBusiness($business, $user, $rangeStart, $rangeEnd);
        }

        $this->seedWarehouseColdChainAndLogistics($businesses, $rangeStart, $rangeEnd, $kgUnit);
        $this->seedSupplyRequestsForTestProcessors($businesses, $rangeStart, $rangeEnd);

        ProcessorFinanceSync::sync($businesses->pluck('id'));

        $this->command?->info('Test processor workspace: comprehensive Rwanda data '.$rangeStart->toDateString().' → '.$rangeEnd->toDateString().' for '.self::REGISTRATIONS[0].' & '.self::REGISTRATIONS[1].'.');
    }

    /**
     * @return array{
     *   slaughter: Facility,
     *   butchery: Facility,
     *   storage: Facility,
     *   inspectors: Collection<int, Inspector>,
     *   suppliers: Collection<int, Supplier>,
     *   clients: Collection<int, Client>,
     * }
     */
    private function prepareBusinessContext(Business $business, AdministrativeDivision $country, Collection $provinces): array
    {
        $sh = $business->facilities()->where('facility_type', Facility::TYPE_SLAUGHTERHOUSE)->firstOrFail();

        $butchery = $business->facilities()->where('facility_type', Facility::TYPE_BUTCHERY)->first();
        if (! $butchery) {
            $butchery = $this->createFacilityLike($business, $sh, 'Butchery — '.$business->business_name, Facility::TYPE_BUTCHERY, 220);
        }

        $storage = $business->facilities()->where('facility_type', Facility::TYPE_STORAGE)->first();
        if (! $storage) {
            $storage = $this->createFacilityLike($business, $sh, 'Cold storage — '.$business->business_name, Facility::TYPE_STORAGE, 400);
        }

        $inspectors = Inspector::query()->where('facility_id', $sh->id)->orderBy('id')->get();
        if ($inspectors->count() < 2) {
            $fn = RwandaSeederHelper::fullName(400 + $business->id);
            $parts = explode(' ', $fn);
            Inspector::query()->create([
                'facility_id' => $sh->id,
                'first_name' => $parts[0] ?? 'Emmanuel',
                'last_name' => $parts[1] ?? 'Nkunda',
                'national_id' => 'NI-RW-COMP-'.$sh->id.'-SEC',
                'phone_number' => RwandaSeederHelper::phone(7000 + $business->id),
                'email' => 'inspector.comp.'.$business->id.'@demo.rw',
                'dob' => now()->subYears(34)->toDateString(),
                'nationality' => 'Rwandan',
                'country' => 'Rwanda',
                'district' => $sh->district ?: 'Rwanda',
                'sector' => $sh->sector ?: '',
                'cell' => $sh->cell?->name,
                'village' => $sh->village?->name,
                'authorization_number' => 'AUTH-COMP-'.Str::upper(Str::random(5)),
                'authorization_issue_date' => now()->subYear(),
                'authorization_expiry_date' => now()->addYear(),
                'species_allowed' => 'Cattle, Goat, Sheep, Pig',
                'daily_capacity' => 120,
                'stamp_serial_number' => 'STAMP-'.random_int(2000, 9999),
                'status' => Inspector::STATUS_ACTIVE,
            ]);
            $inspectors = Inspector::query()->where('facility_id', $sh->id)->orderBy('id')->get();
        }

        $this->expandSuppliersAndClients($business, $country, $provinces, $butchery);
        $this->expandEmployees($business, $sh, $storage, $butchery);

        $suppliers = Supplier::query()->where('business_id', $business->id)->where('is_active', true)->get();
        $clients = Client::query()->where('business_id', $business->id)->where('is_active', true)->get();

        foreach ($suppliers as $supplier) {
            Contract::query()->firstOrCreate(
                [
                    'business_id' => $business->id,
                    'contract_category' => Contract::CATEGORY_SUPPLIER,
                    'supplier_id' => $supplier->id,
                ],
                [
                    'contract_number' => 'C-SUP-TW-'.$business->id.'-'.$supplier->id,
                    'title' => __('Livestock supply — :name', ['name' => $supplier->first_name.' '.$supplier->last_name]),
                    'type' => Contract::TYPE_LIVESTOCK_SUPPLY,
                    'start_date' => Carbon::parse(self::RANGE_START)->toDateString(),
                    'end_date' => Carbon::parse(self::RANGE_END)->addYear()->toDateString(),
                    'status' => Contract::STATUS_ACTIVE,
                    'amount' => random_int(4_000_000, 24_000_000),
                ]
            );
        }
        foreach ($clients->take(12) as $client) {
            Contract::query()->firstOrCreate(
                [
                    'business_id' => $business->id,
                    'contract_category' => Contract::CATEGORY_CUSTOMER,
                    'client_id' => $client->id,
                ],
                [
                    'contract_number' => 'C-CUST-TW-'.$business->id.'-'.$client->id,
                    'title' => __('Meat sales — :name', ['name' => $client->name]),
                    'type' => Contract::TYPE_SALE_AGREEMENT,
                    'start_date' => Carbon::parse(self::RANGE_START)->toDateString(),
                    'end_date' => Carbon::parse(self::RANGE_END)->addYear()->toDateString(),
                    'status' => Contract::STATUS_ACTIVE,
                    'amount' => random_int(2_000_000, 18_000_000),
                ]
            );
        }

        return [
            'slaughter' => $sh,
            'butchery' => $butchery,
            'storage' => $storage,
            'inspectors' => $inspectors,
            'suppliers' => $suppliers,
            'clients' => $clients,
        ];
    }

    private function createFacilityLike(Business $business, Facility $template, string $name, string $type, int $capacity): Facility
    {
        return Facility::query()->create([
            'business_id' => $business->id,
            'facility_name' => $name,
            'facility_type' => $type,
            'district' => $template->district ?: $template->districtDivision?->name ?? 'Rwanda',
            'sector' => $template->sector ?: $template->sectorDivision?->name ?? '',
            'country_id' => $template->country_id,
            'province_id' => $template->province_id,
            'district_id' => $template->district_id,
            'sector_id' => $template->sector_id,
            'cell_id' => $template->cell_id,
            'village_id' => $template->village_id,
            'license_number' => 'LIC-COMP-'.Str::upper(Str::random(6)),
            'license_issue_date' => now()->subYear(),
            'license_expiry_date' => now()->addYear(),
            'daily_capacity' => $capacity,
            'status' => Facility::STATUS_ACTIVE,
        ]);
    }

    private function expandSuppliersAndClients(Business $business, AdministrativeDivision $country, Collection $provinces, Facility $butchery): void
    {
        $targets = max(0, 14 - Supplier::query()->where('business_id', $business->id)->count());
        for ($n = 0; $n < $targets; $n++) {
            $seed = 8000 + $business->id * 100 + $n;
            $name = RwandaSeederHelper::fullName($seed);
            $parts = explode(' ', $name);
            $chain = $this->randomDivisionChain($provinces->random());
            Supplier::query()->create([
                'business_id' => $business->id,
                'first_name' => $parts[0] ?? 'Théogène',
                'last_name' => $parts[1] ?? 'Rutagarama',
                'date_of_birth' => Carbon::parse(self::RANGE_START)->addMonths($n * 3)->toDateString(),
                'nationality' => 'Rwandan',
                'registration_number' => 'SUP-TW-'.$business->id.'-'.str_pad((string) $n, 4, '0', STR_PAD_LEFT),
                'type' => 'livestock_supply',
                'phone' => RwandaSeederHelper::phone($seed),
                'email' => 'supplier.tw.'.$business->id.'.'.$n.'@seed.rw',
                'address_line_1' => $chain['district']->name.', Rwanda',
                'country_id' => $country->id,
                'province_id' => $chain['province']->id,
                'district_id' => $chain['district']->id,
                'sector_id' => $chain['sector']->id,
                'is_active' => true,
                'supplier_status' => Supplier::STATUS_APPROVED,
            ]);
        }

        $clientLabels = [
            'Gisozi Butchery', 'Remera Market Hall', 'Rubavu Fish & Meat', 'Huye University Canteen',
            'Rusizi Port Distributor', 'Nyanza Cooperative Kitchen', 'Musanze Hotel Supply', 'Bugesera Processing Hub',
        ];
        $needClients = max(0, 10 - Client::query()->where('business_id', $business->id)->count());
        for ($c = 0; $c < $needClients; $c++) {
            $label = $clientLabels[$c % count($clientLabels)].' #'.$business->id.'-'.$c;
            $chain = $this->randomDivisionChain($provinces->random());
            Client::query()->create([
                'business_id' => $business->id,
                'name' => $label,
                'contact_person' => RwandaSeederHelper::fullName(9000 + $c + $business->id),
                'email' => 'client.tw.'.$business->id.'.'.$c.'@market.rw',
                'phone' => RwandaSeederHelper::phone(6000 + $c + $business->id * 10),
                'country' => 'Rwanda',
                'country_id' => $country->id,
                'province_id' => $chain['province']->id,
                'district_id' => $chain['district']->id,
                'business_type' => $c % 4 === 0 ? Client::BUSINESS_TYPE_RESTAURANT : Client::BUSINESS_TYPE_BUTCHERY,
                'address_line_1' => $chain['district']->name,
                'preferred_facility_id' => $butchery->id,
                'preferred_species' => 'Cattle',
                'is_active' => true,
            ]);
        }
    }

    private function expandEmployees(Business $business, Facility $sh, Facility $storage, Facility $butchery): void
    {
        $empTitles = array_keys(Employee::JOB_TITLES);
        $current = Employee::query()->where('business_id', $business->id)->count();
        $want = 18;
        for ($e = $current; $e < $want; $e++) {
            $title = $empTitles[$e % count($empTitles)];
            $f = $e % 3 === 0 ? $sh : ($e % 3 === 1 ? $butchery : $storage);
            $fn = RwandaSeederHelper::fullName(1200 + $e + $business->id);
            $parts = explode(' ', $fn);
            Employee::query()->create([
                'business_id' => $business->id,
                'facility_id' => $f->id,
                'first_name' => $parts[0] ?? 'Jean',
                'last_name' => $parts[1] ?? 'Bizimana',
                'national_id' => 'NI-TW-'.$business->id.'-'.$e,
                'date_of_birth' => Carbon::parse(self::RANGE_START)->subYears(22)->subMonths($e % 60)->toDateString(),
                'nationality' => 'Rwandan',
                'work_email' => 'emp.tw.'.$business->id.'.'.$e.'@demo-employee.rw',
                'phone' => RwandaSeederHelper::phone(11000 + $e + $business->id),
                'job_title' => $title,
                'employment_type' => 'full_time',
                'hire_date' => Carbon::parse(self::RANGE_START)->addMonths($e % 48)->toDateString(),
                'status' => 'active',
            ]);
        }
    }

    /**
     * @param  array{
     *   slaughter: Facility,
     *   butchery: Facility,
     *   storage: Facility,
     *   inspectors: Collection<int, Inspector>,
     *   suppliers: Collection<int, Supplier>,
     *   clients: Collection<int, Client>,
     * }  $ctx
     */
    private function seedIntakePipelineForBusiness(
        Business $business,
        array $ctx,
        AdministrativeDivision $country,
        Collection $provinces,
        Carbon $rangeStart,
        Carbon $rangeEnd,
        string $kgUnit,
        int $intakeCount,
    ): void {
        $speciesRot = [AnimalIntake::SPECIES_CATTLE, AnimalIntake::SPECIES_GOAT, AnimalIntake::SPECIES_SHEEP, AnimalIntake::SPECIES_PIG];
        $sh = $ctx['slaughter'];
        $inspectors = $ctx['inspectors'];
        $suppliers = $ctx['suppliers'];

        for ($k = 0; $k < $intakeCount; $k++) {
            $global = $k + $business->id * 10_000;
            $intakeTime = $this->orderedTimestampInRange($rangeStart, $rangeEnd, $k, $intakeCount);
            $species = $speciesRot[$global % count($speciesRot)];
            $nAnimals = random_int(8, 32);
            $supplier = $suppliers->random();
            $contract = Contract::query()
                ->where('business_id', $business->id)
                ->where('contract_category', Contract::CATEGORY_SUPPLIER)
                ->where('supplier_id', $supplier->id)
                ->first();
            $chain = $this->randomDivisionChain($provinces->random());
            $healthNo = self::HEALTH_CERT_PREFIX.'-'.$business->id.'-'.str_pad((string) $global, 6, '0', STR_PAD_LEFT);

            $unitPrice = (float) (random_int(220, 520) * 1000);
            $inspPick = $inspectors->random();
            $intake = AnimalIntake::query()->create([
                'facility_id' => $sh->id,
                'source_type' => AnimalIntake::SOURCE_TYPE_SUPPLIER,
                'supplier_id' => $supplier->id,
                'contract_id' => $contract?->id,
                'intake_date' => $intakeTime->toDateString(),
                'supplier_firstname' => $supplier->first_name,
                'supplier_lastname' => $supplier->last_name,
                'supplier_contact' => $supplier->phone,
                'farm_name' => __('Cooperative lot :n — :district', ['n' => $k + 1, 'district' => $chain['district']->name]),
                'country_id' => $country->id,
                'province_id' => $chain['province']->id,
                'district_id' => $chain['district']->id,
                'sector_id' => $chain['sector']->id,
                'cell_id' => $chain['cell']?->id,
                'village_id' => $chain['village']?->id,
                'species' => $species,
                'number_of_animals' => $nAnimals,
                'unit_price' => $unitPrice,
                'total_price' => round($unitPrice * $nAnimals, 2),
                'status' => AnimalIntake::STATUS_APPROVED,
                'transport_vehicle_plate' => 'RAB '.random_int(100, 899).' '.chr(65 + ($k % 26)),
                'driver_name' => RwandaSeederHelper::fullName($global),
                'animal_health_certificate_number' => $healthNo,
                'health_certificate_issue_date' => $intakeTime->copy()->subDays(12),
                'health_certificate_expiry_date' => $intakeTime->copy()->addMonths(5),
                'meat_inspector_name' => $inspPick->first_name.' '.$inspPick->last_name,
            ]);

            $inspector = $inspectors->random();
            $slaughterDay = $intakeTime->copy()->addDays(random_int(1, 6))->setTime(6, 30, 0);
            if ($slaughterDay->lessThan($intakeTime->copy()->startOfDay())) {
                $slaughterDay = $intakeTime->copy()->addDay()->setTime(6, 30, 0);
            }
            if ($slaughterDay->greaterThan($rangeEnd)) {
                $slaughterDay = $rangeEnd->copy()->subDay()->setTime(6, 30, 0);
            }
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
                'notes' => __('Ante-mortem — :farm', ['farm' => (string) $intake->farm_name]),
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
                'notes' => __('Post-mortem — batch :id', ['id' => $batch->id]),
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
                'certificate_number' => self::CERT_NUMBER_PREFIX.'-'.str_pad((string) $this->certCounter, 6, '0', STR_PAD_LEFT),
                'issued_at' => $slaughterDay->copy()->addDay()->toDateString(),
                'expiry_date' => $slaughterDay->copy()->addMonths(6)->toDateString(),
                'status' => Certificate::STATUS_ACTIVE,
            ]);
            $cert->certificateQr()->create(['slug' => CertificateQr::generateSlug()]);
        }
    }

    private function orderedTimestampInRange(Carbon $start, Carbon $end, int $index, int $total): Carbon
    {
        $total = max(1, $total);
        $t0 = $start->timestamp;
        $t1 = $end->timestamp;
        $p = ($index + 1) / ($total + 1);
        $base = (int) ($t0 + ($t1 - $t0) * $p);

        return Carbon::createFromTimestamp($base)->seconds(0)->addHours(6 + ($index % 7));
    }

    /**
     * @param  array{butchery: Facility}  $ctx
     */
    private function seedDemandsForBusiness(
        Business $business,
        array $ctx,
        Carbon $rangeStart,
        Carbon $rangeEnd,
        string $kgUnit,
    ): void {
        $species = Species::query()->active()->pluck('name')->toArray() ?: ['Cattle', 'Goat'];
        $clientIds = Client::query()->where('business_id', $business->id)->pluck('id');
        $year = (int) date('Y');

        for ($i = 0; $i < 36; $i++) {
            $this->demandSeq++;
            $num = 70000 + $this->demandSeq;
            $statuses = [Demand::STATUS_DRAFT, Demand::STATUS_CONFIRMED, Demand::STATUS_IN_PROGRESS, Demand::STATUS_FULFILLED, Demand::STATUS_CANCELLED];
            $st = $statuses[$this->demandSeq % count($statuses)];
            $deliveryDate = $this->orderedTimestampInRange($rangeStart, $rangeEnd, $i, 36)->addDays(random_int(1, 20))->toDateString();

            Demand::query()->create([
                'business_id' => $business->id,
                'demand_number' => 'DEM-TW-'.$business->id.'-'.$year.'-'.str_pad((string) $num, 5, '0', STR_PAD_LEFT),
                'title' => __('Wholesale — :biz (:n)', ['biz' => $business->business_name, 'n' => $this->demandSeq]),
                'destination_facility_id' => $ctx['butchery']->id,
                'client_id' => $clientIds->isNotEmpty() ? $clientIds->random() : null,
                'species' => $species[array_rand($species)],
                'product_description' => __('Fresh meat — RWF / kg (seeded workspace)'),
                'quantity' => (string) random_int(80, 900),
                'quantity_unit' => $kgUnit,
                'requested_delivery_date' => $deliveryDate,
                'status' => $st,
                'notes' => __('Nyagatare–Kigali corridor — processor test workspace'),
            ]);
        }
    }

    private function seedClientActivitiesForBusiness(Business $business, User $owner, Carbon $rangeStart, Carbon $rangeEnd): void
    {
        $types = [ClientActivity::TYPE_CALL, ClientActivity::TYPE_EMAIL, ClientActivity::TYPE_MEETING, ClientActivity::TYPE_NOTE];
        $n = 0;
        foreach (Client::query()->where('business_id', $business->id)->where('is_active', true)->orderBy('id')->get() as $client) {
            if ($n >= 48) {
                break;
            }
            for ($a = 0; $a < 2 && $n < 48; $a++) {
                $n++;
                $t = $types[$n % count($types)];
                ClientActivity::query()->create([
                    'business_id' => $business->id,
                    'client_id' => $client->id,
                    'activity_type' => $t,
                    'subject' => Str::ucfirst($t).' — '.$client->name.' ('.$n.')',
                    'notes' => __('Kigali / Eastern Province follow-up (RWF pricing).'),
                    'occurred_at' => $this->orderedTimestampInRange($rangeStart, $rangeEnd, $n, 50),
                    'user_id' => $owner->id,
                ]);
            }
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Business>  $businesses
     */
    private function seedWarehouseColdChainAndLogistics(Collection $businesses, Carbon $rangeStart, Carbon $rangeEnd, string $kgUnit): void
    {
        $certQuery = Certificate::query()
            ->with(['batch', 'facility.business'])
            ->where('certificate_number', 'like', self::CERT_NUMBER_PREFIX.'%')
            ->orderBy('id');

        $storages = collect();
        foreach ($certQuery->cursor() as $cert) {
            if (! $cert->batch || ! $cert->facility?->business) {
                continue;
            }
            $biz = $cert->facility->business;
            if (! $businesses->pluck('id')->contains($biz->id)) {
                continue;
            }
            $wh = $biz->facilities()->where('facility_type', Facility::TYPE_STORAGE)->first();
            if (! $wh) {
                continue;
            }
            $existingWs = WarehouseStorage::query()->where('certificate_id', $cert->id)->first();
            if ($existingWs !== null) {
                $storages->push($existingWs);

                continue;
            }
            $en = Carbon::parse((string) $cert->issued_at);
            $storages->push(WarehouseStorage::query()->create([
                'warehouse_facility_id' => $wh->id,
                'batch_id' => $cert->batch_id,
                'certificate_id' => $cert->id,
                'entry_date' => $en->toDateString(),
                'storage_location' => 'Cold room '.chr(65 + ($storages->count() % 3)),
                'temperature_at_entry' => -19.0 + (random_int(0, 8) / 10),
                'quantity_stored' => (float) $cert->batch->quantity,
                'quantity_unit' => $kgUnit,
                'status' => WarehouseStorage::STATUS_IN_STORAGE,
            ]));
        }

        if ($storages->isNotEmpty()) {
            for ($i = 0; $i < 180; $i++) {
                $ws = $storages->get($i % $storages->count());
                if (! $ws) {
                    break;
                }
                $t = (float) (-18.0 + random_int(-5, 5) / 10.0);
                $st = $t >= -15.0
                    ? ($t >= -12.0 ? TemperatureLog::STATUS_CRITICAL : TemperatureLog::STATUS_WARNING)
                    : TemperatureLog::STATUS_NORMAL;
                TemperatureLog::query()->create([
                    'warehouse_storage_id' => $ws->id,
                    'recorded_at' => $this->orderedTimestampInRange($rangeStart, $rangeEnd, $i, 180),
                    'recorded_temperature' => round($t, 2),
                    'recorded_by' => __('Cold chain monitor (seed)'),
                    'status' => $st,
                ]);
            }
        }

        $allFac = Facility::query()
            ->whereIn('business_id', $businesses->pluck('id'))
            ->whereIn('facility_type', [Facility::TYPE_BUTCHERY, Facility::TYPE_SLAUGHTERHOUSE])
            ->get();

        $certs = Certificate::query()
            ->with(['batch', 'facility'])
            ->where('certificate_number', 'like', self::CERT_NUMBER_PREFIX.'%')
            ->orderBy('id')
            ->get();

        foreach ($certs as $i => $cert) {
            if (! $cert->batch || ! $cert->facility || $allFac->count() < 2) {
                continue;
            }
            $dest = $allFac->where('id', '!=', $cert->facility_id)->random();
            $departure = $this->orderedTimestampInRange($rangeStart, $rangeEnd, $i % 120, 120);
            TransportTrip::query()->firstOrCreate(
                [
                    'certificate_id' => $cert->id,
                    'origin_facility_id' => $cert->facility_id,
                ],
                [
                    'batch_id' => $cert->batch_id,
                    'destination_facility_id' => $dest->id,
                    'vehicle_plate_number' => 'RAB '.random_int(200, 899).' '.chr(65 + ($i % 26)),
                    'driver_name' => RwandaSeederHelper::fullName(15000 + $i),
                    'driver_phone' => RwandaSeederHelper::phone(25000 + $i),
                    'departure_date' => $departure,
                    'arrival_date' => $departure->copy()->addHours(4),
                    'status' => TransportTrip::STATUS_ARRIVED,
                ]
            );
        }

        $trips = TransportTrip::query()
            ->whereHas('certificate', fn ($q) => $q->where('certificate_number', 'like', self::CERT_NUMBER_PREFIX.'%'))
            ->where('status', TransportTrip::STATUS_ARRIVED)
            ->limit(140)
            ->get();

        foreach ($trips as $i => $trip) {
            $origBiz = $trip->originFacility?->business_id;
            $c = $origBiz
                ? Client::query()->where('business_id', $origBiz)->inRandomOrder()->first()
                : null;
            if (DeliveryConfirmation::query()->where('transport_trip_id', $trip->id)->exists()) {
                continue;
            }
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
                'received_quantity' => (string) random_int(120, 520),
                'received_date' => $trip->arrival_date,
                'receiver_name' => RwandaSeederHelper::fullName(18000 + $i),
                'receiver_country' => 'Rwanda',
                'receiver_address' => __('Kigali / Musanze corridor (seed)'),
                'confirmation_status' => DeliveryConfirmation::STATUS_CONFIRMED,
            ]);
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Business>  $businesses
     */
    private function seedSupplyRequestsForTestProcessors(Collection $businesses, Carbon $rangeStart, Carbon $rangeEnd): void
    {
        $farmerBiz = Business::query()->where('registration_number', 'SEED-MT-FA-001')->first();
        if (! $farmerBiz) {
            return;
        }
        $farm = Farm::query()->where('business_id', $farmerBiz->id)->orderBy('id')->first();
        if (! $farm) {
            return;
        }
        $live = Livestock::query()->where('farm_id', $farm->id)->orderBy('id')->first();
        if (! $live) {
            return;
        }

        $statuses = [
            SupplyRequest::STATUS_PENDING,
            SupplyRequest::STATUS_ACCEPTED,
            SupplyRequest::STATUS_FULFILLED,
            SupplyRequest::STATUS_REJECTED,
        ];

        foreach ($businesses as $biz) {
            $dest = $biz->facilities()->where('facility_type', Facility::TYPE_SLAUGHTERHOUSE)->first();
            if (! $dest) {
                continue;
            }
            for ($i = 0; $i < 32; $i++) {
                SupplyRequest::query()->create([
                    'processor_id' => $biz->id,
                    'farmer_id' => $farmerBiz->id,
                    'destination_facility_id' => $dest->id,
                    'animal_type' => $live->type,
                    'quantity_requested' => random_int(3, 18),
                    'required_breed' => $live->breed,
                    'required_weight' => (string) random_int(200, 480).' kg',
                    'healthy_stock_required' => true,
                    'certification_required' => (bool) random_int(0, 1),
                    'required_certification_type' => 'RBS / local authorities',
                    'preferred_date' => $this->orderedTimestampInRange($rangeStart, $rangeEnd, $i, 32)->addDays(5)->toDateString(),
                    'status' => $statuses[$i % count($statuses)],
                    'source_farm_id' => $farm->id,
                    'requested_livestock_id' => $live->id,
                ]);
            }
        }
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
