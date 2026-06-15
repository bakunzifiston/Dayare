<?php

namespace Tests\Feature\ColdRoom;

use App\Models\AnimalIntake;
use App\Models\AnimalIntakeItem;
use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Business;
use App\Models\Certificate;
use App\Models\ColdRoom;
use App\Models\ColdRoomStandard;
use App\Models\ColdRoomViolation;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\PostMortemInspection;
use App\Models\PostMortemInspectionItem;
use App\Models\SlaughterExecution;
use App\Models\SlaughterExecutionItem;
use App\Models\SlaughterPlan;
use App\Models\TemperatureLog;
use App\Models\Unit;
use App\Models\User;
use App\Models\WarehouseStorage;
use App\Services\ColdRoomMonitoringService;
use App\Support\StorablePostMortemMeat;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ColdRoomMonitoringTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Business $business;

    private Facility $slaughterFacility;

    private Facility $facility;

    private Inspector $inspector;

    private ColdRoomStandard $standard;

    private ColdRoom $coldRoom;

    private Batch $batch;

    private Certificate $certificate;

    private WarehouseStorage $storage;

    private ColdRoomMonitoringService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->business = Business::create([
            'user_id' => $this->user->id,
            'business_name' => 'Cold Room Test Co',
            'registration_number' => 'REG-CR-'.uniqid(),
            'contact_phone' => '+250788000500',
            'email' => 'cold-room-'.uniqid().'@test.com',
            'status' => 'active',
        ]);
        $this->ensureConfiguredUnits();

        $this->slaughterFacility = Facility::create([
            'business_id' => $this->business->id,
            'facility_name' => 'Cold Room Test Slaughterhouse',
            'facility_type' => Facility::TYPE_SLAUGHTERHOUSE,
            'status' => Facility::STATUS_ACTIVE,
        ]);
        $this->facility = Facility::create([
            'business_id' => $this->business->id,
            'facility_name' => 'Cold Room Test Storage',
            'facility_type' => Facility::TYPE_STORAGE,
            'status' => Facility::STATUS_ACTIVE,
        ]);
        $this->inspector = Inspector::create([
            'facility_id' => $this->slaughterFacility->id,
            'first_name' => 'Insp',
            'last_name' => 'Cold',
            'national_id' => (string) random_int(100000000000, 999999999999),
            'phone_number' => '+250788'.random_int(100000, 999999),
            'email' => 'insp-cr-'.uniqid().'@test.com',
            'dob' => '1988-01-01',
            'nationality' => 'Rwandan',
            'country' => 'Rwanda',
            'district' => 'Kigali',
            'sector' => 'Gasabo',
            'authorization_number' => 'AUTH-CR-'.uniqid(),
            'authorization_issue_date' => now()->subYear(),
            'authorization_expiry_date' => now()->addYear(),
            'species_allowed' => 'Cattle, Goat',
            'status' => 'active',
        ]);

        [$this->batch, $this->certificate] = $this->createBatchAndCertificate();

        $this->standard = ColdRoomStandard::create([
            'name' => 'Test chiller standard',
            'type' => ColdRoomStandard::TYPE_CHILLER,
            'min_temperature' => 0.0,
            'max_temperature' => 4.0,
            'tolerance_minutes' => 30,
        ]);

        $this->coldRoom = ColdRoom::create([
            'facility_id' => $this->facility->id,
            'name' => 'Test Chiller 1',
            'type' => ColdRoom::TYPE_CHILLER,
            'standard_id' => $this->standard->id,
        ]);

        $this->storage = WarehouseStorage::create([
            'warehouse_facility_id' => $this->facility->id,
            'cold_room_id' => $this->coldRoom->id,
            'batch_id' => $this->batch->id,
            'certificate_id' => $this->certificate->id,
            'entry_date' => today(),
            'quantity_stored' => 100,
            'quantity_unit' => 'kg',
            'status' => WarehouseStorage::STATUS_IN_STORAGE,
        ]);

        $this->service = app(ColdRoomMonitoringService::class);
    }

    public function test_in_range_reading_creates_no_violation(): void
    {
        $this->logTemp(2.0);

        $this->assertDatabaseCount('cold_room_violations', 0);
    }

    public function test_out_of_range_reading_opens_violation(): void
    {
        $this->logTemp(8.0);

        $this->assertDatabaseCount('cold_room_violations', 1);
        $this->assertDatabaseHas('cold_room_violations', [
            'cold_room_id' => $this->coldRoom->id,
            'status' => ColdRoomViolation::STATUS_OPEN,
        ]);
    }

    public function test_second_out_of_range_reading_updates_duration(): void
    {
        $this->logTemp(8.0, now());
        $this->logTemp(8.0, now()->addMinutes(45));

        $this->assertDatabaseCount('cold_room_violations', 1);
        $this->assertGreaterThanOrEqual(45, ColdRoomViolation::first()->duration_minutes);
    }

    public function test_escalates_batch_to_at_risk_after_tolerance(): void
    {
        $this->logTemp(8.0, now());
        $this->logTemp(8.0, now()->addMinutes(31));

        $this->assertSame(Batch::COLD_CHAIN_AT_RISK, $this->batch->fresh()->cold_chain_status);
    }

    public function test_escalates_batch_to_compromised_after_double_tolerance(): void
    {
        $this->logTemp(8.0, now());
        $this->logTemp(8.0, now()->addMinutes(61));

        $this->assertSame(Batch::COLD_CHAIN_COMPROMISED, $this->batch->fresh()->cold_chain_status);
    }

    public function test_in_range_reading_closes_open_violation(): void
    {
        $this->logTemp(8.0);

        $this->logTemp(2.0);

        $this->assertDatabaseHas('cold_room_violations', [
            'status' => ColdRoomViolation::STATUS_CLOSED,
        ]);
        $this->assertNotNull(ColdRoomViolation::first()->end_time);
    }

    public function test_batch_cold_chain_resets_to_ok_when_violation_closes(): void
    {
        $this->logTemp(8.0, now());
        $this->logTemp(8.0, now()->addMinutes(35));
        $this->assertSame(Batch::COLD_CHAIN_AT_RISK, $this->batch->fresh()->cold_chain_status);

        $this->logTemp(2.0, now()->addMinutes(70));

        $this->assertSame(Batch::COLD_CHAIN_OK, $this->batch->fresh()->cold_chain_status);
    }

    public function test_bridge_creates_cold_room_log_when_storage_has_room(): void
    {
        $this->actingAs($this->user)
            ->post(route('warehouse-storages.temperature-logs.store', $this->storage), [
                'recorded_temperature' => 3.5,
                'recorded_at' => now()->toDateTimeString(),
                'status' => TemperatureLog::STATUS_NORMAL,
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('cold_room_temperature_logs', 1);
    }

    public function test_bridge_skipped_when_storage_has_no_room(): void
    {
        [$batch, $certificate] = $this->createBatchAndCertificate();

        $storageNoRoom = WarehouseStorage::create([
            'warehouse_facility_id' => $this->facility->id,
            'cold_room_id' => null,
            'batch_id' => $batch->id,
            'certificate_id' => $certificate->id,
            'entry_date' => today(),
            'quantity_stored' => 50,
            'quantity_unit' => 'kg',
            'status' => WarehouseStorage::STATUS_IN_STORAGE,
        ]);

        $this->actingAs($this->user)
            ->post(route('warehouse-storages.temperature-logs.store', $storageNoRoom), [
                'recorded_temperature' => 3.5,
                'recorded_at' => now()->toDateTimeString(),
                'status' => TemperatureLog::STATUS_NORMAL,
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('cold_room_temperature_logs', 0);
    }

    public function test_storage_create_blocked_when_facility_has_rooms_and_none_selected(): void
    {
        [$batch, , $pmItem] = $this->createBatchWithApprovedPostMortemMeat();

        $response = $this->actingAs($this->user)->post(route('warehouse-storages.store'), [
            'warehouse_facility_id' => $this->facility->id,
            'post_mortem_inspection_item_ids' => [$pmItem->id],
            'entry_date' => today()->toDateString(),
            'quantity_unit' => 'kg',
        ]);

        $response->assertSessionHasErrors('cold_room_id');
        $this->assertDatabaseMissing('warehouse_storages', [
            'batch_id' => $batch->id,
            'status' => WarehouseStorage::STATUS_IN_STORAGE,
        ]);
    }

    public function test_storage_create_from_approved_post_mortem_meat(): void
    {
        [$batch, , $pmItem] = $this->createBatchWithApprovedPostMortemMeat(carcassKg: 88.25);

        $response = $this->actingAs($this->user)->post(route('warehouse-storages.store'), [
            'warehouse_facility_id' => $this->facility->id,
            'cold_room_id' => $this->coldRoom->id,
            'post_mortem_inspection_item_ids' => [$pmItem->id],
            'entry_date' => today()->toDateString(),
            'quantity_unit' => 'kg',
        ]);

        $response->assertRedirect(route('warehouse-storages.index'));
        $this->assertDatabaseHas('warehouse_storages', [
            'batch_id' => $batch->id,
            'animal_intake_item_id' => $pmItem->animal_intake_item_id,
            'post_mortem_inspection_item_id' => $pmItem->id,
            'quantity_stored' => 88.25,
            'status' => WarehouseStorage::STATUS_IN_STORAGE,
        ]);
    }

    public function test_storage_create_multiple_animals_at_once(): void
    {
        [$batch, , $pmItemOne] = $this->createBatchWithApprovedPostMortemMeat(carcassKg: 80.00);
        [, , $pmItemTwo] = $this->createBatchWithApprovedPostMortemMeat(carcassKg: 92.50);

        $response = $this->actingAs($this->user)->post(route('warehouse-storages.store'), [
            'warehouse_facility_id' => $this->facility->id,
            'cold_room_id' => $this->coldRoom->id,
            'post_mortem_inspection_item_ids' => [$pmItemOne->id, $pmItemTwo->id],
            'entry_date' => today()->toDateString(),
            'quantity_unit' => 'kg',
        ]);

        $response->assertRedirect(route('warehouse-storages.index'));
        $this->assertDatabaseHas('warehouse_storages', [
            'post_mortem_inspection_item_id' => $pmItemOne->id,
            'quantity_stored' => 80.00,
            'status' => WarehouseStorage::STATUS_IN_STORAGE,
        ]);
        $this->assertDatabaseHas('warehouse_storages', [
            'post_mortem_inspection_item_id' => $pmItemTwo->id,
            'quantity_stored' => 92.50,
            'status' => WarehouseStorage::STATUS_IN_STORAGE,
        ]);
        $this->assertSame(
            2,
            WarehouseStorage::query()
                ->whereIn('post_mortem_inspection_item_id', [$pmItemOne->id, $pmItemTwo->id])
                ->where('status', WarehouseStorage::STATUS_IN_STORAGE)
                ->count()
        );
    }

    public function test_stored_animals_excluded_from_create_selection(): void
    {
        [, , $storedPmItem] = $this->createBatchWithApprovedPostMortemMeat(carcassKg: 75.00);
        [, , $availablePmItem] = $this->createBatchWithApprovedPostMortemMeat(carcassKg: 81.00);

        $this->actingAs($this->user)->post(route('warehouse-storages.store'), [
            'warehouse_facility_id' => $this->facility->id,
            'cold_room_id' => $this->coldRoom->id,
            'post_mortem_inspection_item_ids' => [$storedPmItem->id],
            'entry_date' => today()->toDateString(),
            'quantity_unit' => 'kg',
        ])->assertRedirect();

        $request = Request::create('/warehouse-storages/create', 'GET');
        $request->setUserResolver(fn () => $this->user);

        $optionIds = StorablePostMortemMeat::optionsFor($request)->pluck('id')->map(fn ($id) => (int) $id);

        $this->assertNotContains($storedPmItem->id, $optionIds->all());
        $this->assertContains($availablePmItem->id, $optionIds->all());

        $storedPmItem->load('intakeItem');
        $availablePmItem->load('intakeItem');

        $page = $this->actingAs($this->user)->get(route('warehouse-storages.create'));
        $page->assertOk();
        $page->assertDontSee($storedPmItem->intakeItem->ear_tag);
        $page->assertSee($availablePmItem->intakeItem->ear_tag);
    }

    public function test_storage_create_rejects_condemned_post_mortem_meat(): void
    {
        [, , $pmItem] = $this->createBatchWithApprovedPostMortemMeat(outcome: PostMortemInspectionItem::OUTCOME_CONDEMNED);

        $response = $this->actingAs($this->user)->post(route('warehouse-storages.store'), [
            'warehouse_facility_id' => $this->facility->id,
            'cold_room_id' => $this->coldRoom->id,
            'post_mortem_inspection_item_ids' => [$pmItem->id],
            'entry_date' => today()->toDateString(),
            'quantity_unit' => 'kg',
        ]);

        $response->assertSessionHasErrors('post_mortem_inspection_item_ids');
    }

    public function test_storage_records_visible_on_cold_room_hub_and_index(): void
    {
        [, , $pmItem] = $this->createBatchWithApprovedPostMortemMeat(carcassKg: 70.00);

        $this->actingAs($this->user)->post(route('warehouse-storages.store'), [
            'warehouse_facility_id' => $this->facility->id,
            'cold_room_id' => $this->coldRoom->id,
            'post_mortem_inspection_item_ids' => [$pmItem->id],
            'entry_date' => today()->toDateString(),
            'quantity_unit' => 'kg',
        ])->assertRedirect();

        $pmItem->load('intakeItem');

        $hub = $this->actingAs($this->user)->get(route('cold-rooms.hub'));
        $hub->assertOk();
        $hub->assertSee(__('Storage records'));
        $hub->assertSee($pmItem->intakeItem->ear_tag);

        $index = $this->actingAs($this->user)->get(route('warehouse-storages.index'));
        $index->assertOk();
        $index->assertSee($pmItem->intakeItem->ear_tag);
    }

    public function test_storage_record_can_be_deleted(): void
    {
        [, , $pmItem] = $this->createBatchWithApprovedPostMortemMeat(carcassKg: 66.00);

        $this->actingAs($this->user)->post(route('warehouse-storages.store'), [
            'warehouse_facility_id' => $this->facility->id,
            'cold_room_id' => $this->coldRoom->id,
            'post_mortem_inspection_item_ids' => [$pmItem->id],
            'entry_date' => today()->toDateString(),
            'quantity_unit' => 'kg',
        ])->assertRedirect();

        $storage = WarehouseStorage::query()->where('post_mortem_inspection_item_id', $pmItem->id)->firstOrFail();

        $this->actingAs($this->user)
            ->delete(route('warehouse-storages.destroy', $storage))
            ->assertRedirect(route('warehouse-storages.index'));

        $this->assertDatabaseMissing('warehouse_storages', ['id' => $storage->id]);

        $request = Request::create('/warehouse-storages/create', 'GET');
        $request->setUserResolver(fn () => $this->user);
        $optionIds = StorablePostMortemMeat::optionsFor($request)->pluck('id')->map(fn ($id) => (int) $id);
        $this->assertContains($pmItem->id, $optionIds->all());
    }

    public function test_hub_shows_open_violations(): void
    {
        $this->logTemp(8.0);

        $response = $this->actingAs($this->user)->get(route('cold-rooms.hub'));

        $response->assertOk();
        $response->assertSee($this->coldRoom->name);
    }

    /**
     * Record a temperature reading on the setUp cold room via the monitoring service.
     */
    private function logTemp(float $celsius, ?Carbon $at = null): void
    {
        $this->service->recordTemperature(
            $this->coldRoom,
            $celsius,
            $at ?? now()
        );
    }

    /**
     * @return array{0: Batch, 1: Certificate|null, 2: PostMortemInspectionItem}
     */
    private function createBatchWithApprovedPostMortemMeat(
        float $carcassKg = 95.5,
        string $outcome = PostMortemInspectionItem::OUTCOME_APPROVED,
    ): array {
        [$batch, $certificate] = $this->createBatchAndCertificate();

        $execution = SlaughterExecution::query()->where('id', $batch->slaughter_execution_id)->firstOrFail();
        $plan = SlaughterPlan::query()->where('id', $execution->slaughter_plan_id)->firstOrFail();

        $intakeItem = AnimalIntakeItem::create([
            'animal_intake_id' => $plan->animal_intake_id,
            'ear_tag' => 'CR-PM-'.uniqid(),
            'species' => AnimalIntake::SPECIES_CATTLE,
            'sex' => AnimalIntake::SEX_MALE,
            'unit_price' => 100000,
            'live_weight_kg' => 250.00,
            'health_status' => AnimalIntakeItem::HEALTH_HEALTHY,
        ]);

        $executionItem = SlaughterExecutionItem::create([
            'slaughter_execution_id' => $execution->id,
            'animal_intake_item_id' => $intakeItem->id,
            'meat_quantity_kg' => 125.00,
        ]);

        $batchItem = BatchItem::create([
            'batch_id' => $batch->id,
            'slaughter_execution_item_id' => $executionItem->id,
            'animal_intake_item_id' => $intakeItem->id,
            'meat_quantity_kg' => 125.00,
        ]);

        $inspection = PostMortemInspection::create([
            'batch_id' => $batch->id,
            'inspector_id' => $this->inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'total_examined' => 125.00,
            'approved_quantity' => $outcome === PostMortemInspectionItem::OUTCOME_APPROVED ? $carcassKg : 0,
            'condemned_quantity' => $outcome === PostMortemInspectionItem::OUTCOME_CONDEMNED ? 125.00 : 0,
            'inspection_date' => today(),
            'result' => PostMortemInspection::RESULT_APPROVED,
        ]);

        $pmItem = PostMortemInspectionItem::create([
            'post_mortem_inspection_id' => $inspection->id,
            'batch_item_id' => $batchItem->id,
            'animal_intake_item_id' => $intakeItem->id,
            'outcome' => $outcome,
            'carcass_weight_kg' => $carcassKg,
        ]);

        return [$batch, $certificate, $pmItem];
    }

    /**
     * @return array{0: Batch, 1: Certificate|null}
     */
    private function createBatchAndCertificate(bool $skipCertificate = false): array
    {
        $intake = AnimalIntake::create([
            'facility_id' => $this->slaughterFacility->id,
            'intake_date' => now(),
            'supplier_firstname' => 'Sup',
            'supplier_lastname' => 'Plier',
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals' => 1,
            'status' => AnimalIntake::STATUS_APPROVED,
            'is_draft' => false,
        ]);

        $plan = SlaughterPlan::create([
            'slaughter_date' => now()->toDateString(),
            'facility_id' => $this->slaughterFacility->id,
            'animal_intake_id' => $intake->id,
            'inspector_id' => $this->inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals_scheduled' => 1,
            'status' => SlaughterPlan::STATUS_APPROVED,
        ]);

        $execution = SlaughterExecution::create([
            'slaughter_plan_id' => $plan->id,
            'actual_animals_slaughtered' => 1,
            'slaughter_time' => now(),
            'status' => SlaughterExecution::STATUS_COMPLETED,
        ]);

        $batch = Batch::create([
            'slaughter_execution_id' => $execution->id,
            'inspector_id' => $this->inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'quantity' => 5,
            'quantity_unit' => 'kg',
            'batch_code' => 'BAT-CR-'.uniqid(),
            'status' => Batch::STATUS_APPROVED,
            'cold_chain_status' => Batch::COLD_CHAIN_OK,
        ]);

        if ($skipCertificate) {
            return [$batch, null];
        }

        $certificate = Certificate::create([
            'batch_id' => $batch->id,
            'inspector_id' => $this->inspector->id,
            'facility_id' => $this->slaughterFacility->id,
            'certificate_number' => 'CERT-CR-'.uniqid(),
            'issued_at' => now(),
            'expiry_date' => now()->addMonth(),
            'status' => Certificate::STATUS_ACTIVE,
        ]);

        return [$batch, $certificate];
    }

    private function ensureConfiguredUnits(): void
    {
        $unit = Unit::updateOrCreate(
            ['code' => 'kg'],
            ['name' => 'Kilogram', 'sort_order' => 1, 'is_active' => true],
        );

        $this->business->configuredUnits()->syncWithoutDetaching([$unit->id]);
    }
}
