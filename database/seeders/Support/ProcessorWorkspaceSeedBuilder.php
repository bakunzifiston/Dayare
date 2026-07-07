<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use App\Enums\MeatExportDocumentType;
use App\Models\AdministrativeDivision;
use App\Models\AnimalIntake;
use App\Models\AnimalIntakeItem;
use App\Models\AnteMortemInspection;
use App\Models\AnteMortemInspectionItem;
use App\Models\AnteMortemObservation;
use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Certificate;
use App\Models\CertificateQr;
use App\Models\Client;
use App\Models\ClientActivity;
use App\Models\ColdRoom;
use App\Models\ColdRoomStandard;
use App\Models\Contract;
use App\Models\DeliveryConfirmation;
use App\Models\Demand;
use App\Models\Employee;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\MeatExportDocument;
use App\Models\PostMortemInspection;
use App\Models\PostMortemInspectionItem;
use App\Models\PostMortemObservation;
use App\Models\RicaMonthlyInspectionReport;
use App\Models\SlaughterExecution;
use App\Models\SlaughterExecutionItem;
use App\Models\SlaughterPlan;
use App\Models\Species;
use App\Models\Supplier;
use App\Models\TemperatureLog;
use App\Models\TransportTrip;
use App\Models\Unit;
use App\Models\User;
use App\Models\WarehouseStorage;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Builds Rwanda-based processor workspace demo data for {@see \Database\Seeders\ProcessorWorkspaceSeeder}.
 */
class ProcessorWorkspaceSeedBuilder
{
    public const REG_PREFIX = 'PWS-RDB-';

    public const HEALTH_CERT_PREFIX = 'PWS-AHC';

    public const CERT_NUMBER_PREFIX = 'PWS-CERT';

    private int $certCounter = 0;

    private int $demandSeq = 0;

    private readonly ProcessorWorkspaceSeedProfile $profile;

    /**
     * @param  list<array{
     *     name: string,
     *     province: string,
     *     team_size: int,
     *     intake_count?: int,
     *     registration_number?: string,
     *     owner_name?: string,
     *     owner_email?: string,
     *     business_email?: string,
     *     tax_id?: string
     * }>  $businessCatalog
     */
    public function __construct(
        private readonly string $password,
        private readonly Carbon $rangeStart,
        private readonly Carbon $rangeEnd,
        private readonly array $businessCatalog,
        ?ProcessorWorkspaceSeedProfile $profile = null,
    ) {
        $this->profile = $profile ?? ProcessorWorkspaceSeedProfile::processorWorkspace();
    }

    /**
     * @return list<Business>
     */
    public function seedAll(AdministrativeDivision $country, Collection $provinces): array
    {
        $kgUnit = Unit::query()->where('code', 'kg')->value('code') ?: 'kg';
        $businesses = [];

        DB::transaction(function () use ($country, $provinces, $kgUnit, &$businesses): void {
            foreach ($this->businessCatalog as $index => $catalog) {
                $businesses[] = $this->seedBusiness($index + 1, $catalog, $country, $provinces, $kgUnit);
            }

            $this->seedColdChainAndLogistics(collect($businesses), $kgUnit);
            if ($this->profile->seedMonthlyReports) {
                $this->seedMonthlyInspectionReports(collect($businesses));
            }
            ProcessorFinanceSync::sync(collect($businesses)->pluck('id'));
        });

        return $businesses;
    }

    /**
     * @param  array{name: string, province: string, team_size: int}  $catalog
     */
    private function seedBusiness(
        int $number,
        array $catalog,
        AdministrativeDivision $country,
        Collection $provinces,
        string $kgUnit,
    ): Business {
        $reg = $catalog['registration_number']
            ?? $this->profile->regPrefix.str_pad((string) $number, 3, '0', STR_PAD_LEFT);
        $province = $provinces->firstWhere('name', $catalog['province']) ?? $provinces->get($number % $provinces->count());
        $loc = $this->randomDivisionChain($province);
        $ownerName = $catalog['owner_name'] ?? RwandaSeederHelper::fullName(500 + $number);
        $ownerParts = explode(' ', $ownerName);

        $owner = User::query()->updateOrCreate(
            ['email' => $this->profile->ownerEmail($number, $catalog['owner_email'] ?? null)],
            [
                'name' => $ownerName,
                'password' => $this->password,
                'email_verified_at' => now(),
                'is_super_admin' => false,
            ]
        );

        $business = Business::query()->create([
            'user_id' => $owner->id,
            'type' => Business::TYPE_PROCESSOR,
            'business_name' => $catalog['name'],
            'registration_number' => $reg,
            'tax_id' => $catalog['tax_id'] ?? '1'.str_pad((string) (200000000 + $number), 8, '0', STR_PAD_LEFT),
            'contact_phone' => RwandaSeederHelper::phone(3000 + $number * 17),
            'email' => $catalog['business_email'] ?? Str::slug($catalog['name']).'@'.$this->profile->businessEmailDomain,
            'status' => Business::STATUS_ACTIVE,
            'owner_first_name' => $ownerParts[0] ?? 'Jean',
            'owner_last_name' => $ownerParts[1] ?? 'Mukamana',
            'owner_phone' => RwandaSeederHelper::phone(3100 + $number * 17),
            'ownership_type' => $number % 3 === 0 ? 'cooperative' : 'company',
            'business_size' => 'medium',
            'baseline_revenue' => Business::BASELINE_REVENUE_BRACKET_2M_20M,
            'pathway_status' => Business::PATHWAY_STATUSES[0],
            'country_id' => $country->id,
            'province_id' => $loc['province']->id,
            'district_id' => $loc['district']->id,
            'sector_id' => $loc['sector']->id,
            'cell_id' => $loc['cell']?->id,
            'village_id' => $loc['village']?->id,
        ]);

        $this->attachRole($owner, $business, BusinessUser::ROLE_ORG_ADMIN);
        $this->seedTeamUsers($number, $business, $catalog['team_size']);

        $slaughter = $this->makeFacility($business, $catalog['name'].' — Abattoir', Facility::TYPE_SLAUGHTERHOUSE, $loc, 120);
        $storage = $this->makeFacility($business, $catalog['name'].' — Cold store', Facility::TYPE_STORAGE, $loc, 450);
        $butchery = $this->profile->seedButchery
            ? $this->makeFacility($business, $catalog['name'].' — Butchery', Facility::TYPE_BUTCHERY, $loc, 220)
            : null;

        $inspectors = collect();
        $inspectorCount = $catalog['inspector_count'] ?? $this->profile->defaultInspectorCount;
        for ($i = 1; $i <= $inspectorCount; $i++) {
            $inspectors->push($this->makeInspector($slaughter, $number, $i));
        }

        $supplierCount = $catalog['supplier_count'] ?? $this->profile->defaultSupplierCount;
        $clientCount = $catalog['client_count'] ?? $this->profile->defaultClientCount;
        $employeeCount = $catalog['employee_count'] ?? $this->profile->defaultEmployeeCount;
        $intakeCount = $catalog['intake_count'] ?? $this->profile->defaultIntakeCount;

        $suppliers = $this->seedSuppliers($business, $country, $provinces, $supplierCount);
        $clients = $this->seedClients(
            $business,
            $country,
            $provinces,
            $butchery ?? $slaughter,
            $clientCount,
        );
        $this->seedEmployees($business, $slaughter, $storage, $employeeCount, $butchery);
        $this->seedContracts($business, $suppliers, $clients);

        $ctx = [
            'slaughter' => $slaughter,
            'storage' => $storage,
            'butchery' => $butchery,
            'inspectors' => $inspectors,
            'suppliers' => $suppliers,
            'clients' => $clients,
            'owner' => $owner,
        ];

        $this->seedIntakePipeline($business, $ctx, $country, $provinces, $kgUnit, $intakeCount);
        $this->seedDemands($business, $butchery ?? $slaughter, $kgUnit, $catalog['demand_count'] ?? $this->profile->defaultDemandCount);
        $this->seedClientActivities($business, $owner, $catalog['activity_count'] ?? $this->profile->defaultActivityCount);

        return $business;
    }

    private function seedTeamUsers(int $businessNumber, Business $business, int $teamSize): void
    {
        $pool = [
            BusinessUser::ROLE_OPERATIONS_MANAGER,
            BusinessUser::ROLE_COMPLIANCE_OFFICER,
            BusinessUser::ROLE_INSPECTOR,
            BusinessUser::ROLE_TRANSPORT_MANAGER,
            BusinessUser::ROLE_ACCOUNTANT,
        ];

        for ($i = 0; $i < max(0, min(4, $teamSize - 1)); $i++) {
            $role = $pool[$i % count($pool)];
            $user = User::query()->updateOrCreate(
                ['email' => $this->profile->teamEmail($businessNumber, $i)],
                [
                    'name' => RwandaSeederHelper::fullName(700 + $businessNumber * 10 + $i).' ('.$role.')',
                    'password' => $this->password,
                    'email_verified_at' => now(),
                    'is_super_admin' => false,
                ]
            );
            $this->attachRole($user, $business, $role);
        }
    }

    /**
     * @return Collection<int, Supplier>
     */
    private function seedSuppliers(Business $business, AdministrativeDivision $country, Collection $provinces, int $count): Collection
    {
        $rows = collect();
        for ($n = 0; $n < $count; $n++) {
            $seed = 2000 + $business->id * 20 + $n;
            $name = RwandaSeederHelper::fullName($seed);
            $parts = explode(' ', $name);
            $chain = $this->randomDivisionChain($provinces->random());

            $rows->push(Supplier::query()->create([
                'business_id' => $business->id,
                'first_name' => $parts[0] ?? 'Emmanuel',
                'last_name' => $parts[1] ?? 'Habimana',
                'date_of_birth' => $this->rangeStart->copy()->subYears(38)->addMonths($n)->toDateString(),
                'nationality' => 'Rwandan',
                'registration_number' => ($this->profile->useRealisticContractPrefixes ? 'RDB/FARM/' : 'SUP-PWS-')
                    .$business->id.'-'.str_pad((string) $n, 3, '0', STR_PAD_LEFT),
                'type' => 'livestock_supply',
                'phone' => RwandaSeederHelper::phone($seed),
                'email' => ($this->profile->useRealisticContractPrefixes
                    ? Str::slug($parts[0].'.'.$parts[1])
                    : "supplier.pws.{$business->id}.{$n}").'@livestock.rw',
                'address_line_1' => $chain['district']->name.', Rwanda',
                'country_id' => $country->id,
                'province_id' => $chain['province']->id,
                'district_id' => $chain['district']->id,
                'sector_id' => $chain['sector']->id,
                'is_active' => true,
                'supplier_status' => Supplier::STATUS_APPROVED,
            ]));
        }

        return $rows;
    }

    /**
     * @return Collection<int, Client>
     */
    private function seedClients(
        Business $business,
        AdministrativeDivision $country,
        Collection $provinces,
        Facility $preferred,
        int $count,
    ): Collection {
        $labels = [
            'Remera Wholesale Market', 'Gisozi Premium Butchery', 'Rubavu Lakeside Hotel',
            'Huye University Kitchen', 'Musanze Mountain Lodge', 'Kayonza School Feeding',
            'Kicukiro Industrial Canteen', 'Rusizi Border Distributor', 'Nyagatare Town Butchery',
            'Rwamagana District Hospital Kitchen', 'Kigali Convention Centre Catering',
            'Gisenyi Port Meat Traders', 'Muhanga Central Market', 'Nyanza Cooperative Kitchen',
            'Bugesera Export Packers', 'Karongi Lakeside Lodge', 'Ngoma District Prison Supply',
            'Rulindo Agri-Food Hub', 'Kirehe Border Wholesale', 'Gatsibo Livestock Traders',
            'Nyamagabe Highland Restaurant', 'Rutsiro Fish & Meat Supply', 'Ngororero Butchery Chain',
            'Burera Mountain Hotel',
        ];
        $rows = collect();

        for ($c = 0; $c < $count; $c++) {
            $chain = $this->randomDivisionChain($provinces->random());
            $clientName = $labels[$c % count($labels)] ?? 'Client';
            if ($this->profile->appendBusinessIdToClientNames) {
                $clientName .= ' — '.$business->id;
            }

            $rows->push(Client::query()->create([
                'business_id' => $business->id,
                'name' => $clientName,
                'contact_person' => RwandaSeederHelper::fullName(4000 + $c + $business->id),
                'email' => ($this->profile->useRealisticContractPrefixes
                    ? Str::slug($clientName).'@clients.rw'
                    : "client.pws.{$business->id}.{$c}@market.rw"),
                'phone' => RwandaSeederHelper::phone(5000 + $c + $business->id * 3),
                'country' => 'Rwanda',
                'country_id' => $country->id,
                'province_id' => $chain['province']->id,
                'district_id' => $chain['district']->id,
                'business_type' => $c % 3 === 0 ? Client::BUSINESS_TYPE_RESTAURANT : Client::BUSINESS_TYPE_BUTCHERY,
                'address_line_1' => $chain['sector']->name.', '.$chain['district']->name,
                'preferred_facility_id' => $preferred->id,
                'preferred_species' => 'Cattle',
                'is_active' => true,
            ]));
        }

        return $rows;
    }

    private function seedEmployees(Business $business, Facility $slaughter, Facility $storage, int $count, ?Facility $butchery = null): void
    {
        $titles = array_keys(Employee::JOB_TITLES);

        for ($e = 0; $e < $count; $e++) {
            $fn = RwandaSeederHelper::fullName(8000 + $business->id * 10 + $e);
            $parts = explode(' ', $fn);
            $facility = match ($e % 3) {
                0 => $slaughter,
                1 => $butchery ?? $storage,
                default => $storage,
            };

            Employee::query()->create([
                'business_id' => $business->id,
                'facility_id' => $facility->id,
                'first_name' => $parts[0] ?? 'Jean',
                'last_name' => $parts[1] ?? 'Uwimana',
                'national_id' => '11980'.str_pad((string) ($business->id * 100 + $e), 7, '0', STR_PAD_LEFT),
                'date_of_birth' => $this->rangeStart->copy()->subYears(28)->subMonths($e * 2)->toDateString(),
                'nationality' => 'Rwandan',
                'work_email' => ($this->profile->useRealisticContractPrefixes
                    ? Str::slug(($parts[0] ?? 'jean').'.'.($parts[1] ?? 'uwimana'))
                    : "employee.pws.{$business->id}.{$e}").'@staff.rw',
                'phone' => RwandaSeederHelper::phone(9000 + $business->id + $e),
                'job_title' => $titles[$e % count($titles)],
                'employment_type' => 'full_time',
                'hire_date' => $this->rangeStart->copy()->addMonths($e)->toDateString(),
                'status' => 'active',
            ]);
        }
    }

    /**
     * @param  Collection<int, Supplier>  $suppliers
     * @param  Collection<int, Client>  $clients
     */
    private function seedContracts(Business $business, Collection $suppliers, Collection $clients): void
    {
        $contractPrefix = $this->profile->useRealisticContractPrefixes ? 'NPM' : 'PWS';

        foreach ($suppliers as $supplier) {
            Contract::query()->create([
                'business_id' => $business->id,
                'contract_category' => Contract::CATEGORY_SUPPLIER,
                'supplier_id' => $supplier->id,
                'contract_number' => 'C-SUP-'.$contractPrefix.'-'.$business->id.'-'.$supplier->id,
                'title' => __('Livestock supply — :name', ['name' => $supplier->first_name.' '.$supplier->last_name]),
                'type' => Contract::TYPE_LIVESTOCK_SUPPLY,
                'start_date' => $this->rangeStart->toDateString(),
                'end_date' => $this->rangeEnd->copy()->addYear()->toDateString(),
                'status' => Contract::STATUS_ACTIVE,
                'amount' => random_int(5_000_000, 28_000_000),
            ]);
        }

        foreach ($clients->take(min(12, $clients->count())) as $client) {
            Contract::query()->create([
                'business_id' => $business->id,
                'contract_category' => Contract::CATEGORY_CUSTOMER,
                'client_id' => $client->id,
                'contract_number' => 'C-CUST-'.$contractPrefix.'-'.$business->id.'-'.$client->id,
                'title' => __('Meat sales — :name', ['name' => $client->name]),
                'type' => Contract::TYPE_SALE_AGREEMENT,
                'start_date' => $this->rangeStart->toDateString(),
                'end_date' => $this->rangeEnd->copy()->addYear()->toDateString(),
                'status' => Contract::STATUS_ACTIVE,
                'amount' => random_int(3_000_000, 22_000_000),
            ]);
        }
    }

    /**
     * @param  array{
     *   slaughter: Facility,
     *   storage: Facility,
     *   butchery: ?Facility,
     *   inspectors: Collection<int, Inspector>,
     *   suppliers: Collection<int, Supplier>,
     *   clients: Collection<int, Client>,
     *   owner: User,
     * }  $ctx
     */
    private function seedIntakePipeline(
        Business $business,
        array $ctx,
        AdministrativeDivision $country,
        Collection $provinces,
        string $kgUnit,
        int $intakeCount,
    ): void {
        $speciesRot = [AnimalIntake::SPECIES_CATTLE, AnimalIntake::SPECIES_GOAT, AnimalIntake::SPECIES_SHEEP, AnimalIntake::SPECIES_PIG];
        $sh = $ctx['slaughter'];
        $inspectors = $ctx['inspectors'];
        $suppliers = $ctx['suppliers'];
        $clients = $ctx['clients'];
        $openIntakeSlots = $this->profile->openIntakeSlots;
        $pipelineCount = max(0, $intakeCount - $openIntakeSlots);

        for ($k = 0; $k < $intakeCount; $k++) {
            $global = $k + $business->id * 1000;
            $intakeTime = $this->orderedTimestamp($k, $intakeCount);
            $species = $speciesRot[$global % count($speciesRot)];
            $nAnimals = random_int(6, 18);
            $useClientSource = $this->profile->mixClientIntakes && $k % 3 === 0 && $clients->isNotEmpty();
            $client = $useClientSource ? $clients->random() : null;
            $supplier = $useClientSource ? null : $suppliers->random();
            $contract = $useClientSource
                ? Contract::query()
                    ->where('business_id', $business->id)
                    ->where('contract_category', Contract::CATEGORY_CUSTOMER)
                    ->where('client_id', $client?->id)
                    ->first()
                : Contract::query()
                    ->where('business_id', $business->id)
                    ->where('contract_category', Contract::CATEGORY_SUPPLIER)
                    ->where('supplier_id', $supplier?->id)
                    ->first();
            $chain = $this->randomDivisionChain($provinces->random());
            $healthNo = $this->profile->healthCertPrefix.'-'.$business->id.'-'.str_pad((string) $global, 5, '0', STR_PAD_LEFT);
            $unitPrice = (float) (random_int(240, 480) * 1000);
            $inspPick = $inspectors->random();

            $intakeData = [
                'facility_id' => $sh->id,
                'intake_date' => $intakeTime,
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
                'is_draft' => false,
                'submitted_at' => $intakeTime,
                'transport_vehicle_plate' => 'RAD '.random_int(100, 899).' '.chr(65 + ($k % 26)),
                'driver_name' => RwandaSeederHelper::fullName($global),
                'animal_health_certificate_number' => $k % 4 === 0 ? null : $healthNo,
                'health_certificate_issue_date' => $k % 4 === 0 ? null : $intakeTime->copy()->subDays(10),
                'health_certificate_expiry_date' => $k % 4 === 0 ? null : $intakeTime->copy()->addMonths(4),
                'meat_inspector_name' => $inspPick->first_name.' '.$inspPick->last_name,
            ];

            if ($useClientSource && $client) {
                $nameParts = explode(' ', (string) $client->contact_person);
                $intakeData = array_merge($intakeData, [
                    'source_type' => AnimalIntake::SOURCE_TYPE_CLIENT,
                    'client_id' => $client->id,
                    'supplier_id' => null,
                    'contract_id' => $contract?->id,
                    'supplier_firstname' => $nameParts[0] ?? 'Client',
                    'supplier_lastname' => $nameParts[1] ?? 'Contact',
                    'supplier_contact' => $client->phone,
                    'farm_name' => __('Client lot :n — :sector', ['n' => $k + 1, 'sector' => $chain['sector']->name]),
                ]);
            } else {
                $intakeData = array_merge($intakeData, [
                    'source_type' => AnimalIntake::SOURCE_TYPE_SUPPLIER,
                    'supplier_id' => $supplier?->id,
                    'client_id' => null,
                    'contract_id' => $contract?->id,
                    'supplier_firstname' => $supplier?->first_name,
                    'supplier_lastname' => $supplier?->last_name,
                    'supplier_contact' => $supplier?->phone,
                    'farm_name' => __('Lot :n — :sector', ['n' => $k + 1, 'sector' => $chain['sector']->name]),
                ]);
            }

            $intake = AnimalIntake::query()->create($intakeData);

            $items = $this->createIntakeItems($intake, $nAnimals, $species, $unitPrice, $global, $intakeTime);
            $totalPrice = round($items->sum(fn (AnimalIntakeItem $item) => (float) $item->unit_price + (float) $item->service_fee), 2);
            $intake->update([
                'number_of_animals' => $items->count(),
                'total_price' => $totalPrice,
                'unit_price' => $items->count() > 0 ? round($totalPrice / $items->count(), 2) : $unitPrice,
            ]);

            if ($k >= $pipelineCount) {
                continue;
            }

            $inspector = $inspectors->random();
            $slaughterDay = $intakeTime->copy()->addDays(random_int(1, 4))->setTime(6, 0, 0);
            if ($slaughterDay->greaterThan($this->rangeEnd)) {
                $slaughterDay = $this->rangeEnd->copy()->subDay()->setTime(6, 0, 0);
            }
            $slaughterTime = $slaughterDay->copy()->addHours(2);

            $rejectedAnteItemId = ($global % 11 === 0 && $items->count() > 3)
                ? $items->last()->id
                : null;
            $slaughterItems = $rejectedAnteItemId
                ? $items->where('id', '!=', $rejectedAnteItemId)
                : $items;
            $slaughterCount = $slaughterItems->count();

            $plan = SlaughterPlan::query()->create([
                'slaughter_date' => $slaughterDay->toDateString(),
                'facility_id' => $sh->id,
                'animal_intake_id' => $intake->id,
                'inspector_id' => $inspector->id,
                'species' => $species,
                'number_of_animals_scheduled' => $slaughterCount,
                'status' => SlaughterPlan::STATUS_APPROVED,
            ]);

            AnimalIntakeItem::query()->whereIn('id', $slaughterItems->pluck('id'))->update(['slaughter_plan_id' => $plan->id]);

            $anteRejected = $rejectedAnteItemId ? 1 : 0;
            $ante = AnteMortemInspection::query()->create([
                'slaughter_plan_id' => $plan->id,
                'inspector_id' => $inspector->id,
                'species' => $species,
                'number_examined' => $items->count(),
                'number_approved' => $items->count() - $anteRejected,
                'number_rejected' => $anteRejected,
                'notes' => __('Ante-mortem clearance — :farm', ['farm' => (string) $intake->farm_name]),
                'inspection_date' => $slaughterDay->toDateString(),
                'examined_count_source' => AnteMortemInspection::SOURCE_ITEMS,
            ]);

            foreach ($items as $itemIndex => $item) {
                $isRejected = $item->id === $rejectedAnteItemId;
                AnteMortemInspectionItem::query()->create([
                    'ante_mortem_inspection_id' => $ante->id,
                    'animal_intake_item_id' => $item->id,
                    'outcome' => $isRejected
                        ? AnteMortemInspectionItem::OUTCOME_REJECTED
                        : AnteMortemInspectionItem::OUTCOME_APPROVED,
                    'conditions' => $isRejected ? __('Lameness and dehydration after transport') : null,
                    'action_taken' => $isRejected ? __('Held in isolation pen; returned to supplier') : null,
                ]);

                if ($isRejected) {
                    AnteMortemObservation::query()->create([
                        'ante_mortem_inspection_id' => $ante->id,
                        'animal_intake_item_id' => $item->id,
                        'item' => 'locomotion',
                        'value' => 'abnormal',
                        'notes' => __('Non-weight-bearing on left hind limb'),
                    ]);
                }
            }

            foreach (RwandaSeederHelper::anteMortemObservationPayload($species) as $row) {
                AnteMortemObservation::query()->create(array_merge($row, ['ante_mortem_inspection_id' => $ante->id]));
            }

            $exec = SlaughterExecution::query()->create([
                'slaughter_plan_id' => $plan->id,
                'actual_animals_slaughtered' => $slaughterCount,
                'slaughter_time' => $slaughterTime,
                'status' => SlaughterExecution::STATUS_COMPLETED,
                'slaughter_count_source' => SlaughterExecution::SOURCE_ITEMS,
            ]);

            $totalMeatKg = 0.0;
            $executionItems = collect();

            foreach ($slaughterItems as $item) {
                $liveWeight = (float) ($item->live_weight_kg ?? 0);
                $meatQuantity = $liveWeight > 0 ? round($liveWeight * 0.52, 2) : round(random_int(200, 320) / 10, 2);
                $totalMeatKg += $meatQuantity;
                $executionItems->push(SlaughterExecutionItem::query()->create([
                    'slaughter_execution_id' => $exec->id,
                    'animal_intake_item_id' => $item->id,
                    'meat_quantity_kg' => $meatQuantity,
                ]));
            }

            $batch = Batch::query()->create([
                'slaughter_execution_id' => $exec->id,
                'inspector_id' => $inspector->id,
                'species' => $species,
                'quantity' => $totalMeatKg,
                'quantity_unit' => $kgUnit,
                'status' => Batch::STATUS_APPROVED,
            ]);

            $batchItems = collect();

            foreach ($executionItems as $executionItem) {
                $batchItems->push(BatchItem::query()->create([
                    'batch_id' => $batch->id,
                    'slaughter_execution_item_id' => $executionItem->id,
                    'animal_intake_item_id' => $executionItem->animal_intake_item_id,
                    'meat_quantity_kg' => $executionItem->meat_quantity_kg,
                ]));
            }

            $condemnedBatchItemId = ($global % 8 === 0 && $batchItems->count() > 1)
                ? $batchItems->last()->id
                : null;
            $condemnedQtyKg = 0.0;

            $pm = PostMortemInspection::query()->create([
                'batch_id' => $batch->id,
                'inspector_id' => $inspector->id,
                'species' => $species,
                'total_examined' => $slaughterCount,
                'approved_quantity' => $condemnedBatchItemId ? $slaughterCount - 1 : $slaughterCount,
                'condemned_quantity' => $condemnedBatchItemId ? 1 : 0,
                'notes' => __('Post-mortem — :batch', ['batch' => $batch->batch_code ?? ('#'.$batch->id)]),
                'inspection_date' => $slaughterDay->toDateString(),
                'result' => PostMortemInspection::RESULT_APPROVED,
            ]);

            foreach ($batchItems as $batchItem) {
                $isCondemned = $batchItem->id === $condemnedBatchItemId;
                if ($isCondemned) {
                    $condemnedQtyKg = (float) $batchItem->meat_quantity_kg;
                }

                PostMortemInspectionItem::query()->create([
                    'post_mortem_inspection_id' => $pm->id,
                    'batch_item_id' => $batchItem->id,
                    'animal_intake_item_id' => $batchItem->animal_intake_item_id,
                    'outcome' => $isCondemned
                        ? PostMortemInspectionItem::OUTCOME_CONDEMNED
                        : PostMortemInspectionItem::OUTCOME_APPROVED,
                    'carcass_weight_kg' => $batchItem->meat_quantity_kg,
                    'seized_part' => $isCondemned ? __('Liver, kidneys') : null,
                    'reason' => $isCondemned ? __('Multifocal abscesses; unfit for human consumption') : null,
                ]);

                if ($isCondemned) {
                    PostMortemObservation::query()->create([
                        'post_mortem_inspection_id' => $pm->id,
                        'animal_intake_item_id' => $batchItem->animal_intake_item_id,
                        'category' => 'organ',
                        'item' => 'liver',
                        'value' => 'abnormal',
                        'notes' => __('Enlarged liver with abscesses'),
                    ]);
                    PostMortemObservation::query()->create([
                        'post_mortem_inspection_id' => $pm->id,
                        'animal_intake_item_id' => $batchItem->animal_intake_item_id,
                        'category' => 'general',
                        'item' => 'comment',
                        'value' => __('Condemned — septic lesions'),
                        'notes' => null,
                    ]);
                }
            }

            foreach (RwandaSeederHelper::postMortemObservationPayload($species) as $row) {
                PostMortemObservation::query()->create(array_merge($row, ['post_mortem_inspection_id' => $pm->id]));
            }

            $this->certCounter++;
            $cert = Certificate::query()->create([
                'batch_id' => $batch->id,
                'inspector_id' => $inspector->id,
                'facility_id' => $sh->id,
                'certificate_number' => $this->profile->certNumberPrefix.'-'.$business->id.'-'.str_pad((string) $this->certCounter, 5, '0', STR_PAD_LEFT),
                'issued_at' => $slaughterDay->copy()->addDay()->toDateString(),
                'expiry_date' => $slaughterDay->copy()->addMonths(6)->toDateString(),
                'status' => Certificate::STATUS_ACTIVE,
            ]);
            $cert->certificateQr()->create(['slug' => CertificateQr::generateSlug()]);
        }
    }

    /**
     * @return Collection<int, AnimalIntakeItem>
     */
    private function createIntakeItems(
        AnimalIntake $intake,
        int $count,
        string $species,
        float $unitPrice,
        int $globalSeed,
        Carbon $timestamp,
    ): Collection {
        $sexes = [AnimalIntake::SEX_MALE, AnimalIntake::SEX_FEMALE];
        $bodyConditions = ['fair', 'good', 'excellent'];
        $rows = [];

        for ($n = 1; $n <= $count; $n++) {
            $liveWeight = match ($species) {
                AnimalIntake::SPECIES_CATTLE => random_int(380, 520),
                AnimalIntake::SPECIES_PIG => random_int(85, 115),
                default => random_int(28, 42),
            };
            $serviceFee = (float) (random_int(15, 45) * 100);
            $rows[] = [
                'animal_intake_id' => $intake->id,
                'ear_tag' => sprintf('%s-%d-%04d', $this->profile->earTagPrefix, $intake->id, $n),
                'species' => $species,
                'sex' => $sexes[($globalSeed + $n) % 2],
                'age_months' => random_int(14, 52),
                'live_weight_kg' => $liveWeight,
                'body_condition_score' => $bodyConditions[($globalSeed + $n) % 3],
                'unit_price' => $unitPrice,
                'service_fee' => $serviceFee,
                'health_status' => AnimalIntakeItem::HEALTH_HEALTHY,
                'notes' => null,
                'slaughter_plan_id' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        DB::table('animal_intake_items')->insert($rows);

        return AnimalIntakeItem::query()->where('animal_intake_id', $intake->id)->orderBy('id')->get();
    }

    private function seedDemands(Business $business, Facility $destination, string $kgUnit, int $count): void
    {
        $species = Species::query()->active()->pluck('name')->toArray() ?: ['Cattle', 'Goat'];
        $clientIds = Client::query()->where('business_id', $business->id)->pluck('id');
        $year = (int) date('Y');

        for ($i = 0; $i < $count; $i++) {
            $this->demandSeq++;
            $statuses = [Demand::STATUS_CONFIRMED, Demand::STATUS_IN_PROGRESS, Demand::STATUS_FULFILLED, Demand::STATUS_DRAFT];

            Demand::query()->create([
                'business_id' => $business->id,
                'demand_number' => 'DEM-'.($this->profile->useRealisticContractPrefixes ? 'NPM' : 'PWS').'-'.$business->id.'-'.$year.'-'.str_pad((string) $this->demandSeq, 4, '0', STR_PAD_LEFT),
                'title' => __('Wholesale order — :name', ['name' => $business->business_name]),
                'destination_facility_id' => $destination->id,
                'client_id' => $clientIds->isNotEmpty() ? $clientIds->random() : null,
                'species' => $species[array_rand($species)],
                'product_description' => __('Fresh chilled meat — Rwanda corridor'),
                'quantity' => (string) random_int(100, 800),
                'quantity_unit' => $kgUnit,
                'requested_delivery_date' => $this->orderedTimestamp($i, $count)->addDays(random_int(2, 18))->toDateString(),
                'status' => $statuses[$i % count($statuses)],
                'notes' => __('Scheduled wholesale delivery — Eastern Province corridor'),
            ]);
        }
    }

    private function seedClientActivities(Business $business, User $owner, int $count): void
    {
        $types = [ClientActivity::TYPE_CALL, ClientActivity::TYPE_EMAIL, ClientActivity::TYPE_MEETING, ClientActivity::TYPE_NOTE];
        $n = 0;

        foreach (Client::query()->where('business_id', $business->id)->orderBy('id')->get() as $client) {
            if ($n >= $count) {
                break;
            }

            ClientActivity::query()->create([
                'business_id' => $business->id,
                'client_id' => $client->id,
                'activity_type' => $types[$n % count($types)],
                'subject' => __('Follow-up — :client', ['client' => $client->name]),
                'notes' => __('Pricing in RWF — Kigali / Eastern Province route.'),
                'occurred_at' => $this->orderedTimestamp($n, $count),
                'user_id' => $owner->id,
            ]);
            $n++;
        }
    }

    /**
     * @param  Collection<int, Business>  $businesses
     */
    private function seedColdChainAndLogistics(Collection $businesses, string $kgUnit): void
    {
        $freezerStandard = ColdRoomStandard::query()->where('type', ColdRoomStandard::TYPE_FREEZER)->first();
        $chillerStandard = ColdRoomStandard::query()->where('type', ColdRoomStandard::TYPE_CHILLER)->first();

        foreach ($businesses as $business) {
            $storage = $business->facilities()->where('facility_type', Facility::TYPE_STORAGE)->first();
            if (! $storage || ! $freezerStandard || ColdRoom::query()->where('facility_id', $storage->id)->exists()) {
                continue;
            }

            ColdRoom::query()->create([
                'facility_id' => $storage->id,
                'name' => 'Freezer A',
                'type' => ColdRoom::TYPE_FREEZER,
                'capacity' => 320,
                'standard_id' => $freezerStandard->id,
            ]);

            if ($chillerStandard) {
                ColdRoom::query()->create([
                    'facility_id' => $storage->id,
                    'name' => 'Chiller B',
                    'type' => ColdRoom::TYPE_CHILLER,
                    'capacity' => 180,
                    'standard_id' => $chillerStandard->id,
                ]);
            }
        }

        $certs = Certificate::query()
            ->with(['batch', 'facility.business'])
            ->where('certificate_number', 'like', $this->profile->certNumberPrefix.'%')
            ->orderBy('id')
            ->get();

        $storages = collect();

        foreach ($certs as $cert) {
            $biz = $cert->facility?->business;
            if (! $biz || ! $businesses->pluck('id')->contains($biz->id) || ! $cert->batch) {
                continue;
            }

            $wh = $biz->facilities()->where('facility_type', Facility::TYPE_STORAGE)->first();
            if (! $wh) {
                continue;
            }

            $storages->push(WarehouseStorage::query()->create([
                'warehouse_facility_id' => $wh->id,
                'batch_id' => $cert->batch_id,
                'certificate_id' => $cert->id,
                'entry_date' => Carbon::parse((string) $cert->issued_at)->toDateString(),
                'storage_location' => 'Bay '.chr(65 + ($storages->count() % 4)),
                'temperature_at_entry' => -19.2,
                'quantity_stored' => (float) $cert->batch->quantity,
                'quantity_unit' => $kgUnit,
                'status' => WarehouseStorage::STATUS_IN_STORAGE,
            ]));
        }

        foreach ($storages->take(60) as $i => $ws) {
            TemperatureLog::query()->create([
                'warehouse_storage_id' => $ws->id,
                'recorded_at' => $this->orderedTimestamp($i, 60),
                'recorded_temperature' => round(-18.5 + (random_int(-4, 4) / 10), 2),
                'recorded_by' => __('Cold chain monitor'),
                'status' => TemperatureLog::STATUS_NORMAL,
            ]);
        }

        $clientsByBusiness = Client::query()
            ->whereIn('business_id', $businesses->pluck('id'))
            ->get()
            ->groupBy('business_id');

        foreach ($certs as $i => $cert) {
            $biz = $cert->facility?->business;
            $bizId = $biz?->id;
            if (! $bizId) {
                continue;
            }

            $client = $clientsByBusiness->get($bizId)?->random();
            $client?->loadMissing(['districtDivision', 'sectorDivision', 'cell']);
            $destName = $client?->name ?? 'Kigali Distribution Hub';
            $isExport = $i % 11 === 0;
            $destCountry = $isExport ? 'KE' : 'RW';
            $departure = $this->orderedTimestamp($i % 80, 80);
            $destAddress = $isExport
                ? 'Nairobi, Industrial Area'
                : collect([
                    $client?->districtDivision?->name,
                    $client?->sectorDivision?->name,
                    $client?->cell?->name,
                ])->filter(fn (?string $part) => $part !== null && $part !== '')->implode(', ');

            if ($destAddress === '') {
                $destAddress = 'Kigali, Gasabo, Kimihurura';
            }

            $trip = TransportTrip::query()->create([
                'certificate_id' => $cert->id,
                'batch_id' => $cert->batch_id,
                'origin_facility_id' => $cert->facility_id,
                'destination_facility_id' => null,
                'destination_name' => $destName,
                'destination_country' => $destCountry,
                'destination_address' => $destAddress,
                'vehicle_plate_number' => 'RAB '.random_int(200, 899).' '.chr(65 + ($i % 26)),
                'driver_name' => RwandaSeederHelper::fullName(12000 + $i),
                'driver_phone' => RwandaSeederHelper::phone(22000 + $i),
                'departure_date' => $departure,
                'arrival_date' => $departure->copy()->addHours(5),
                'status' => TransportTrip::STATUS_ARRIVED,
            ]);

            $confirmation = DeliveryConfirmation::query()->create([
                'transport_trip_id' => $trip->id,
                'receiving_facility_id' => null,
                'client_id' => $client?->id,
                'contract_id' => $client
                    ? Contract::query()->where('business_id', $bizId)->where('client_id', $client->id)->value('id')
                    : null,
                'received_quantity' => max(50, (int) round((float) ($cert->batch?->quantity ?? 200))),
                'received_unit' => $kgUnit,
                'received_date' => $trip->arrival_date,
                'receiver_name' => $destName,
                'receiver_country' => $destCountry,
                'receiver_address' => $trip->destination_address,
                'confirmation_status' => DeliveryConfirmation::STATUS_CONFIRMED,
            ]);

            if ($isExport && $biz?->user_id) {
                foreach (MeatExportDocumentType::REQUIRED_TYPES as $docType) {
                    MeatExportDocument::query()->create([
                        'delivery_confirmation_id' => $confirmation->id,
                        'document_type' => $docType,
                        'document_number' => 'EXP-'.($this->profile->useRealisticContractPrefixes ? 'NPM' : 'PWS').'-'.strtoupper(substr($docType, 0, 3)).'-'.$confirmation->id,
                        'issuing_authority' => __('RICA / RDB export desk'),
                        'issued_date' => $trip->departure_date,
                        'expiry_date' => $trip->departure_date->copy()->addMonths(3),
                        'status' => MeatExportDocument::STATUS_ISSUED,
                        'notes' => __('Cross-border meat export documentation bundle'),
                        'created_by' => $biz->user_id,
                        'updated_by' => $biz->user_id,
                    ]);
                }
            }
        }
    }

    private function orderedTimestamp(int $index, int $total): Carbon
    {
        $total = max(1, $total);
        $t0 = $this->rangeStart->timestamp;
        $t1 = $this->rangeEnd->timestamp;
        $linear = ($index + 1) / ($total + 1);
        $p = $this->profile->growthWeightedDates
            ? pow($linear, $this->profile->growthExponent)
            : $linear;
        $base = (int) ($t0 + ($t1 - $t0) * $p);

        return Carbon::createFromTimestamp($base)->seconds(0)->addHours(7 + ($index % 6));
    }

    /**
     * @param  Collection<int, Business>  $businesses
     */
    private function seedMonthlyInspectionReports(Collection $businesses): void
    {
        $challenges = [
            'Seasonal livestock supply fluctuations in Eastern Province during dry months.',
            'Increased ante-mortem observation cases linked to long-distance transport.',
            'Cold room compressor maintenance required twice this month.',
            'Higher goat intake volumes from Kayonza and Gatsibo cooperatives.',
            'Staff training completed on updated RICA FPU/FRM/018 reporting.',
        ];
        $recommendations = [
            'Continue farmer outreach in Nyagatare and Rwimiyaga sectors.',
            'Schedule preventive maintenance for chiller units before peak season.',
            'Expand client intake documentation for movement permits.',
            'Coordinate with district veterinary officers on vaccination campaigns.',
            'Review transport cold-chain compliance for Rubavu export routes.',
        ];

        foreach ($businesses as $business) {
            $slaughter = $business->facilities()->where('facility_type', Facility::TYPE_SLAUGHTERHOUSE)->first();
            if (! $slaughter) {
                continue;
            }

            $owner = User::query()->find($business->user_id);
            $inspectors = Inspector::query()->where('facility_id', $slaughter->id)->orderBy('id')->get();
            $operatorName = trim(($business->owner_first_name ?? '').' '.($business->owner_last_name ?? ''));

            $cursor = $this->rangeStart->copy()->startOfMonth();
            $endMonth = $this->rangeEnd->copy()->startOfMonth();

            while ($cursor->lessThanOrEqualTo($endMonth)) {
                $hasActivity = SlaughterPlan::query()
                    ->where('facility_id', $slaughter->id)
                    ->whereYear('slaughter_date', $cursor->year)
                    ->whereMonth('slaughter_date', $cursor->month)
                    ->exists();

                if (! $hasActivity) {
                    $cursor->addMonth();

                    continue;
                }

                $inspector = $inspectors->random();
                $monthIndex = (int) $cursor->format('Ym');
                $isSubmitted = $monthIndex % 5 !== 0;

                RicaMonthlyInspectionReport::query()->updateOrCreate(
                    [
                        'facility_id' => $slaughter->id,
                        'period_year' => (int) $cursor->year,
                        'period_month' => (int) $cursor->month,
                    ],
                    [
                        'challenges' => $challenges[$monthIndex % count($challenges)],
                        'recommendations' => $recommendations[$monthIndex % count($recommendations)],
                        'inspector_signatures' => [
                            [
                                'name' => trim($inspector->first_name.' '.$inspector->last_name),
                                'signed_at' => $cursor->copy()->endOfMonth()->setTime(16, 0)->toIso8601String(),
                            ],
                        ],
                        'operator_name' => $operatorName !== '' ? $operatorName : 'Operations Manager',
                        'operator_signed_at' => $isSubmitted ? $cursor->copy()->endOfMonth()->setTime(17, 0) : null,
                        'stamp_acknowledged' => $isSubmitted,
                        'status' => $isSubmitted
                            ? RicaMonthlyInspectionReport::STATUS_SUBMITTED
                            : RicaMonthlyInspectionReport::STATUS_DRAFT,
                        'submitted_at' => $isSubmitted ? $cursor->copy()->endOfMonth()->setTime(17, 30) : null,
                        'submitted_by_user_id' => $isSubmitted ? $owner?->id : null,
                    ],
                );

                $cursor->addMonth();
            }
        }
    }

    private function makeFacility(Business $biz, string $name, string $type, array $loc, int $capacity): Facility
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
            'license_number' => ($this->profile->useRealisticContractPrefixes ? 'RICA/LIC/' : 'LIC-PWS-').Str::upper(Str::random(6)),
            'license_issue_date' => $this->rangeStart->copy()->subMonths(6),
            'license_expiry_date' => $this->rangeEnd->copy()->addYear(),
            'daily_capacity' => $capacity,
            'status' => Facility::STATUS_ACTIVE,
        ]);
    }

    private function makeInspector(Facility $facility, int $businessNumber, int $n): Inspector
    {
        $fn = RwandaSeederHelper::fullName(100 * $businessNumber + $n);
        $parts = explode(' ', $fn);

        return Inspector::query()->create([
            'facility_id' => $facility->id,
            'first_name' => $parts[0] ?? 'Inspector',
            'last_name' => $parts[1] ?? 'Mbanje',
            'national_id' => '11987'.str_pad((string) ($facility->id * 10 + $n), 7, '0', STR_PAD_LEFT),
            'phone_number' => RwandaSeederHelper::phone(1500 + $facility->id + $n),
            'email' => ($this->profile->useRealisticContractPrefixes
                ? Str::slug(($parts[0] ?? 'inspector').'.'.($parts[1] ?? 'mbanje'))
                : "inspector.pws.{$businessNumber}.{$n}").'@'.$this->profile->ownerEmailDomain,
            'dob' => $this->rangeStart->copy()->subYears(36)->toDateString(),
            'nationality' => 'Rwandan',
            'country' => 'Rwanda',
            'district' => $facility->district ?: 'Rwanda',
            'sector' => $facility->sector ?: '',
            'authorization_number' => ($this->profile->useRealisticContractPrefixes ? 'RICA/AUTH/' : 'AUTH-PWS-').Str::upper(Str::random(5)),
            'authorization_issue_date' => $this->rangeStart->copy()->subYear(),
            'authorization_expiry_date' => $this->rangeEnd->copy()->addYear(),
            'species_allowed' => 'Cattle, Goat, Sheep, Pig',
            'daily_capacity' => 100,
            'stamp_serial_number' => ($this->profile->useRealisticContractPrefixes ? 'RICA-ST-' : 'STAMP-PWS-').random_int(1000, 9999),
            'status' => Inspector::STATUS_ACTIVE,
        ]);
    }

    private function attachRole(User $user, Business $business, string $role): void
    {
        BusinessUser::query()->updateOrCreate(
            ['business_id' => $business->id, 'user_id' => $user->id],
            ['role' => $role]
        );
    }

    /**
     * @return array{
     *     country: ?AdministrativeDivision,
     *     province: AdministrativeDivision,
     *     district: AdministrativeDivision,
     *     sector: AdministrativeDivision,
     *     cell: ?AdministrativeDivision,
     *     village: ?AdministrativeDivision
     * }
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
