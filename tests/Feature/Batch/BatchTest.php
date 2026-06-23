<?php

namespace Tests\Feature\Batch;

use App\Models\AnimalIntake;
use App\Models\AnimalIntakeItem;
use App\Models\AnteMortemInspection;
use App\Models\AnteMortemInspectionItem;
use App\Models\Batch;
use App\Models\Business;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\SlaughterExecution;
use App\Models\SlaughterExecutionItem;
use App\Models\SlaughterPlan;
use App\Models\Species;
use App\Models\Unit;
use App\Models\User;
use App\Support\PostMortemChecklist;
use App\Support\PostMortemMeatTotals;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class BatchTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Business $business;

    private Facility $facility;

    private Inspector $inspector;

    private AnimalIntake $intake;

    private SlaughterPlan $plan;

    private SlaughterExecution $execution;

    /** @var Collection<int, SlaughterExecutionItem> */
    private Collection $executionItems;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureConfiguredSpecies();

        $this->user = User::factory()->create();
        $this->business = Business::create([
            'user_id' => $this->user->id,
            'business_name' => 'Batch Test Co',
            'registration_number' => 'REG-BT-'.uniqid(),
            'contact_phone' => '+250788000400',
            'email' => 'batch-'.uniqid().'@test.com',
            'status' => 'active',
        ]);
        $this->ensureConfiguredUnits();
        $this->facility = Facility::create([
            'business_id' => $this->business->id,
            'facility_name' => 'Batch Test Slaughterhouse',
            'facility_type' => 'slaughterhouse',
            'status' => 'active',
        ]);
        $this->inspector = Inspector::create([
            'facility_id' => $this->facility->id,
            'first_name' => 'Insp',
            'last_name' => 'Batch',
            'national_id' => (string) random_int(100000000000, 999999999999),
            'phone_number' => '+250788'.random_int(100000, 999999),
            'email' => 'insp-batch-'.uniqid().'@test.com',
            'dob' => '1988-01-01',
            'nationality' => 'Rwandan',
            'country' => 'Rwanda',
            'district' => 'Kigali',
            'sector' => 'Gasabo',
            'authorization_number' => 'AUTH-BT-'.uniqid(),
            'authorization_issue_date' => now()->subYear(),
            'authorization_expiry_date' => now()->addYear(),
            'species_allowed' => 'Cattle, Goat',
            'status' => 'active',
        ]);

        $this->intake = AnimalIntake::create([
            'facility_id' => $this->facility->id,
            'intake_date' => now(),
            'supplier_firstname' => 'Sup',
            'supplier_lastname' => 'Plier',
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals' => 5,
            'status' => AnimalIntake::STATUS_APPROVED,
            'is_draft' => false,
        ]);

        $assignedItems = collect();
        for ($i = 1; $i <= 5; $i++) {
            $assignedItems->push(AnimalIntakeItem::create([
                'animal_intake_id' => $this->intake->id,
                'ear_tag' => 'BT-C-'.$this->intake->id.'-'.$i,
                'species' => AnimalIntake::SPECIES_CATTLE,
                'sex' => AnimalIntake::SEX_MALE,
                'unit_price' => 100000,
                'live_weight_kg' => 250.00,
                'health_status' => AnimalIntakeItem::HEALTH_HEALTHY,
            ]));
        }

        $this->plan = SlaughterPlan::create([
            'slaughter_date' => now()->addDay()->toDateString(),
            'facility_id' => $this->facility->id,
            'animal_intake_id' => $this->intake->id,
            'inspector_id' => $this->inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals_scheduled' => 5,
            'status' => SlaughterPlan::STATUS_APPROVED,
        ]);

        AnimalIntakeItem::query()
            ->whereIn('id', $assignedItems->pluck('id'))
            ->update(['slaughter_plan_id' => $this->plan->id]);

        $amInspection = AnteMortemInspection::create([
            'slaughter_plan_id' => $this->plan->id,
            'inspector_id' => $this->inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_examined' => 5,
            'number_approved' => 5,
            'number_rejected' => 0,
            'inspection_date' => today(),
            'examined_count_source' => AnteMortemInspection::SOURCE_ITEMS,
        ]);

        foreach ($assignedItems as $assignedItem) {
            AnteMortemInspectionItem::create([
                'ante_mortem_inspection_id' => $amInspection->id,
                'animal_intake_item_id' => $assignedItem->id,
                'outcome' => AnteMortemInspectionItem::OUTCOME_APPROVED,
            ]);
        }

        $this->execution = SlaughterExecution::create([
            'slaughter_plan_id' => $this->plan->id,
            'actual_animals_slaughtered' => 5,
            'slaughter_time' => today()->format('Y-m-d').' 10:00:00',
            'status' => SlaughterExecution::STATUS_COMPLETED,
            'slaughter_count_source' => SlaughterExecution::SOURCE_ITEMS,
        ]);

        foreach ($assignedItems as $assignedItem) {
            SlaughterExecutionItem::create([
                'slaughter_execution_id' => $this->execution->id,
                'animal_intake_item_id' => $assignedItem->id,
                'meat_quantity_kg' => 125.00,
                'notes' => null,
            ]);
        }

        $this->executionItems = SlaughterExecutionItem::query()
            ->where('slaughter_execution_id', $this->execution->id)
            ->orderBy('id')
            ->get();
    }

    private function ensureConfiguredSpecies(): void
    {
        foreach ([
            ['name' => AnimalIntake::SPECIES_CATTLE, 'code' => 'cattle', 'sort_order' => 1],
            ['name' => AnimalIntake::SPECIES_GOAT, 'code' => 'goat', 'sort_order' => 2],
            ['name' => AnimalIntake::SPECIES_SHEEP, 'code' => 'sheep', 'sort_order' => 3],
            ['name' => AnimalIntake::SPECIES_PIG, 'code' => 'pig', 'sort_order' => 4],
            ['name' => AnimalIntake::SPECIES_OTHER, 'code' => 'other', 'sort_order' => 5],
        ] as $row) {
            Species::updateOrCreate(
                ['code' => $row['code']],
                ['name' => $row['name'], 'sort_order' => $row['sort_order'], 'is_active' => true],
            );
        }
    }

    private function ensureConfiguredUnits(): void
    {
        $unit = Unit::updateOrCreate(
            ['code' => 'kg'],
            ['name' => 'Kilogram', 'sort_order' => 1, 'is_active' => true],
        );

        $this->business->configuredUnits()->syncWithoutDetaching([$unit->id]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validStorePayload(array $overrides = []): array
    {
        return array_merge([
            'slaughter_execution_id' => $this->execution->id,
            'facility_id' => $this->facility->id,
            'slaughter_date' => $this->execution->slaughter_time->toDateString(),
            'inspector_id' => $this->inspector->id,
            'quantity' => 625.00,
            'quantity_unit' => 'kg',
            'status' => Batch::STATUS_PENDING,
            'selected_animal_ids' => $this->executionItems->pluck('animal_intake_item_id')->toArray(),
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validUpdatePayload(Batch $batch, array $overrides = []): array
    {
        return array_merge([
            'slaughter_execution_id' => $batch->slaughter_execution_id,
            'inspector_id' => $batch->inspector_id,
            'species' => $batch->species,
            'quantity' => $batch->quantity,
            'quantity_unit' => $batch->quantity_unit,
            'status' => $batch->status,
        ], $overrides);
    }

    /**
     * @return array<string, array{value: string, notes: null}>
     */
    private function validPmObservationsPayload(): array
    {
        $items = PostMortemChecklist::itemsForSpecies('Cattle');

        return collect($items)->mapWithKeys(function (array $meta, string $itemKey): array {
            $allowed = PostMortemChecklist::allowedValuesForItem('Cattle', $itemKey);

            if (($meta['type'] ?? '') === 'free_text') {
                $value = 'OK';
            } elseif (in_array('normal', $allowed, true)) {
                $value = 'normal';
            } elseif (in_array('approved', $allowed, true)) {
                $value = 'approved';
            } else {
                $value = $allowed[0] ?? '';
            }

            return [$itemKey => ['value' => $value, 'notes' => null]];
        })->all();
    }

    /**
     * @return array<string, array{value: string, notes: null}>
     */
    private function validPerAnimalPmObservationsPayload(): array
    {
        $items = \App\Support\PostMortemChecklist::itemsForInspection('Cattle', true);

        return collect($items)->mapWithKeys(function (array $meta, string $itemKey): array {
            $allowed = \App\Support\PostMortemChecklist::allowedValuesForItem('Cattle', $itemKey);

            if (($meta['type'] ?? '') === 'free_text') {
                $value = 'OK';
            } elseif (in_array('normal', $allowed, true)) {
                $value = 'normal';
            } elseif (in_array('approved', $allowed, true)) {
                $value = 'approved';
            } else {
                $value = $allowed[0] ?? '';
            }

            return [$itemKey => ['value' => $value, 'notes' => null]];
        })->all();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPmPayload(Batch $batch, array $overrides = []): array
    {
        $batch->load('items');
        $animals = $batch->inspectableAnimalsForPostMortem();
        $itemOutcomes = $batch->items->map(fn ($bi) => [
            'batch_item_id' => $bi->id,
            'animal_intake_item_id' => $bi->animal_intake_item_id,
            'outcome' => 'approved',
            'carcass_weight_kg' => 110.00,
            'outcome_notes' => null,
            'observations' => $this->validPerAnimalPmObservationsPayload(),
        ])->values()->toArray();

        return $this->validPmPayloadFromInspectableAnimals($batch, array_merge(
            PostMortemMeatTotals::fromItemOutcomes($itemOutcomes, $animals->keyBy('animal_intake_item_id')),
            ['item_outcomes' => $itemOutcomes],
            $overrides,
        ));
    }

    /**
     * Build PM payload from inspectable animals (batch items or execution fallback).
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPmPayloadFromInspectableAnimals(Batch $batch, array $overrides = []): array
    {
        $perAnimalObservations = $this->validPerAnimalPmObservationsPayload();
        $animals = $batch->inspectableAnimalsForPostMortem();

        $itemOutcomes = $animals->map(fn (array $animal) => [
            'batch_item_id' => $animal['batch_item_id'],
            'animal_intake_item_id' => $animal['animal_intake_item_id'],
            'outcome' => 'approved',
            'carcass_weight_kg' => 110.00,
            'outcome_notes' => null,
            'observations' => $perAnimalObservations,
        ])->values()->toArray();

        $meatTotals = PostMortemMeatTotals::fromItemOutcomes(
            $itemOutcomes,
            $animals->keyBy('animal_intake_item_id'),
        );

        return array_merge([
            'batch_id' => $batch->id,
            'inspector_id' => $this->inspector->id,
            'species' => 'Cattle',
            'notes' => null,
            'inspection_date' => today()->toDateString(),
            'item_outcomes' => $itemOutcomes,
        ], $meatTotals, $overrides);
    }

    public function test_store_creates_batch_with_all_animals(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('batches.store'), $this->validStorePayload());

        $response->assertRedirect(route('batches.hub'));
        $this->assertDatabaseCount('batches', 1);
        $this->assertDatabaseCount('batch_items', 5);

        foreach ($this->executionItems as $execItem) {
            $this->assertDatabaseHas('batch_items', [
                'animal_intake_item_id' => $execItem->animal_intake_item_id,
            ]);
        }
    }

    public function test_store_creates_partial_batch(): void
    {
        $selectedIds = $this->executionItems->take(3)->pluck('animal_intake_item_id')->toArray();

        $this->actingAs($this->user)
            ->post(route('batches.store'), $this->validStorePayload([
                'selected_animal_ids' => $selectedIds,
                'quantity' => 375.00,
            ]));

        $this->assertDatabaseCount('batches', 1);
        $this->assertDatabaseCount('batch_items', 3);

        $remaining = $this->executionItems->skip(3);
        foreach ($remaining as $execItem) {
            $this->assertDatabaseMissing('batch_items', [
                'slaughter_execution_item_id' => $execItem->id,
            ]);
        }
    }

    public function test_store_blocked_when_execution_not_completed(): void
    {
        $this->execution->update(['status' => SlaughterExecution::STATUS_IN_PROGRESS]);

        $response = $this->actingAs($this->user)
            ->post(route('batches.store'), $this->validStorePayload());

        $response->assertSessionHasErrors('slaughter_execution_id');
        $this->assertDatabaseCount('batches', 0);
    }

    public function test_store_quantity_capped_against_execution(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('batches.store'), $this->validStorePayload(['quantity' => 700.00]));

        $response->assertSessionHasErrors('quantity');
        $this->assertDatabaseCount('batches', 0);
    }

    public function test_store_creates_batch_from_multiple_same_day_executions(): void
    {
        $secondIntake = AnimalIntake::create([
            'facility_id' => $this->facility->id,
            'intake_date' => now(),
            'supplier_firstname' => 'Second',
            'supplier_lastname' => 'Supplier',
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals' => 2,
            'status' => AnimalIntake::STATUS_APPROVED,
            'is_draft' => false,
        ]);

        $secondItems = collect();
        for ($i = 1; $i <= 2; $i++) {
            $secondItems->push(AnimalIntakeItem::create([
                'animal_intake_id' => $secondIntake->id,
                'ear_tag' => 'BT-C2-'.$secondIntake->id.'-'.$i,
                'species' => AnimalIntake::SPECIES_CATTLE,
                'sex' => AnimalIntake::SEX_MALE,
                'unit_price' => 100000,
                'live_weight_kg' => 240.00,
                'health_status' => AnimalIntakeItem::HEALTH_HEALTHY,
            ]));
        }

        $secondPlan = SlaughterPlan::create([
            'slaughter_date' => now()->addDay()->toDateString(),
            'facility_id' => $this->facility->id,
            'animal_intake_id' => $secondIntake->id,
            'inspector_id' => $this->inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals_scheduled' => 2,
            'status' => SlaughterPlan::STATUS_APPROVED,
        ]);

        AnimalIntakeItem::query()
            ->whereIn('id', $secondItems->pluck('id'))
            ->update(['slaughter_plan_id' => $secondPlan->id]);

        $secondExecution = SlaughterExecution::create([
            'slaughter_plan_id' => $secondPlan->id,
            'actual_animals_slaughtered' => 2,
            'slaughter_time' => $this->execution->slaughter_time->copy()->addHours(2),
            'status' => SlaughterExecution::STATUS_COMPLETED,
            'slaughter_count_source' => SlaughterExecution::SOURCE_ITEMS,
        ]);

        foreach ($secondItems as $item) {
            SlaughterExecutionItem::create([
                'slaughter_execution_id' => $secondExecution->id,
                'animal_intake_item_id' => $item->id,
                'meat_quantity_kg' => 120.00,
                'notes' => null,
            ]);
        }

        $selectedIds = $this->executionItems->take(2)->pluck('animal_intake_item_id')
            ->merge($secondItems->pluck('id'))
            ->values()
            ->all();

        $this->actingAs($this->user)
            ->post(route('batches.store'), $this->validStorePayload([
                'selected_animal_ids' => $selectedIds,
                'quantity' => 370.00,
            ]));

        $this->assertDatabaseCount('batches', 1);
        $this->assertDatabaseCount('batch_items', 4);
    }

    public function test_animal_already_batched_cannot_be_in_second_batch(): void
    {
        $selectedIds = $this->executionItems->take(3)->pluck('animal_intake_item_id')->toArray();

        $this->actingAs($this->user)
            ->post(route('batches.store'), $this->validStorePayload([
                'selected_animal_ids' => $selectedIds,
                'quantity' => 375.00,
            ]));

        $response = $this->actingAs($this->user)
            ->post(route('batches.store'), $this->validStorePayload([
                'selected_animal_ids' => $selectedIds,
                'quantity' => 375.00,
            ]));

        $response->assertSessionHasErrors('selected_animal_ids');
        $errorMessage = session('errors')->get('selected_animal_ids')[0] ?? '';
        $firstEarTag = $this->executionItems->first()->intakeItem->ear_tag;
        $this->assertStringContainsString($firstEarTag, $errorMessage);
        $this->assertStringContainsString('batch', strtolower($errorMessage));
        $this->assertDatabaseCount('batches', 1);
    }

    public function test_update_blocked_approved_without_pm(): void
    {
        $batch = Batch::factory()->create([
            'slaughter_execution_id' => $this->execution->id,
            'inspector_id' => $this->inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'quantity' => 625.00,
            'quantity_unit' => 'kg',
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('batches.update', $batch), $this->validUpdatePayload($batch, [
                'status' => Batch::STATUS_APPROVED,
            ]));

        $response->assertSessionHasErrors('status');
        $this->assertDatabaseHas('batches', [
            'id' => $batch->id,
            'status' => Batch::STATUS_PENDING,
        ]);
    }

    public function test_update_allows_approved_with_pm(): void
    {
        $batch = Batch::factory()
            ->withPostMortem()
            ->create([
                'slaughter_execution_id' => $this->execution->id,
                'inspector_id' => $this->inspector->id,
                'species' => AnimalIntake::SPECIES_CATTLE,
                'quantity' => 625.00,
                'quantity_unit' => 'kg',
            ]);

        $response = $this->actingAs($this->user)
            ->put(route('batches.update', $batch), $this->validUpdatePayload($batch, [
                'status' => Batch::STATUS_APPROVED,
            ]));

        $response->assertRedirect();
        $this->assertDatabaseHas('batches', [
            'id' => $batch->id,
            'status' => Batch::STATUS_APPROVED,
        ]);
    }

    public function test_post_mortem_creates_inspection_items(): void
    {
        $this->actingAs($this->user)
            ->post(route('batches.store'), $this->validStorePayload());

        $batch = Batch::with('items')->firstOrFail();

        $this->actingAs($this->user)
            ->post(route('post-mortem-inspections.store'), $this->validPmPayload($batch));

        $this->assertDatabaseCount('post_mortem_inspection_items', 5);
        $this->assertDatabaseHas('post_mortem_inspections', [
            'batch_id' => $batch->id,
            'approved_quantity' => 550.00,
            'condemned_quantity' => 0,
            'total_examined' => 625.00,
        ]);
    }

    public function test_post_mortem_outcome_and_carcass_weight_correct(): void
    {
        $this->actingAs($this->user)
            ->post(route('batches.store'), $this->validStorePayload());

        $batch = Batch::with('items')->firstOrFail();
        $items = $batch->items->values();

        $perAnimalObservations = $this->validPerAnimalPmObservationsPayload();
        $itemOutcomes = $batch->items->map(function ($bi, $index) use ($perAnimalObservations) {
            return [
                'batch_item_id' => $bi->id,
                'animal_intake_item_id' => $bi->animal_intake_item_id,
                'outcome' => $index === 0 ? 'condemned' : 'approved',
                'carcass_weight_kg' => $index === 0 ? 98.50 : 110.00,
                'outcome_notes' => null,
                'observations' => $perAnimalObservations,
            ];
        })->values()->toArray();

        $animals = $batch->inspectableAnimalsForPostMortem();
        $meatTotals = PostMortemMeatTotals::fromItemOutcomes($itemOutcomes, $animals->keyBy('animal_intake_item_id'));

        $this->actingAs($this->user)
            ->post(route('post-mortem-inspections.store'), $this->validPmPayload($batch, array_merge(
                ['item_outcomes' => $itemOutcomes],
                $meatTotals,
            )));

        $this->assertDatabaseHas('post_mortem_inspection_items', [
            'animal_intake_item_id' => $items[0]->animal_intake_item_id,
            'outcome' => 'condemned',
            'carcass_weight_kg' => 98.50,
        ]);
        $this->assertDatabaseHas('post_mortem_inspections', [
            'batch_id' => $batch->id,
            'approved_quantity' => 440.00,
            'condemned_quantity' => 125.00,
            'total_examined' => 625.00,
        ]);
    }

    public function test_index_scoped_to_accessible_facilities(): void
    {
        $ownBatch = Batch::factory()->create([
            'slaughter_execution_id' => $this->execution->id,
            'inspector_id' => $this->inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'quantity' => 100.00,
            'quantity_unit' => 'kg',
        ]);

        $otherUser = User::factory()->create();
        $otherBusiness = Business::create([
            'user_id' => $otherUser->id,
            'business_name' => 'Other Batch Co',
            'registration_number' => 'REG-OTHER-'.uniqid(),
            'contact_phone' => '+250788000500',
            'email' => 'other-batch-'.uniqid().'@test.com',
            'status' => 'active',
        ]);
        $otherFacility = Facility::create([
            'business_id' => $otherBusiness->id,
            'facility_name' => 'Other Slaughterhouse',
            'facility_type' => 'slaughterhouse',
            'status' => 'active',
        ]);
        $otherInspector = Inspector::create([
            'facility_id' => $otherFacility->id,
            'first_name' => 'Other',
            'last_name' => 'Insp',
            'national_id' => (string) random_int(100000000000, 999999999999),
            'phone_number' => '+250788'.random_int(100000, 999999),
            'email' => 'other-insp-'.uniqid().'@test.com',
            'dob' => '1988-01-01',
            'nationality' => 'Rwandan',
            'country' => 'Rwanda',
            'district' => 'Kigali',
            'sector' => 'Gasabo',
            'authorization_number' => 'AUTH-OTHER-'.uniqid(),
            'authorization_issue_date' => now()->subYear(),
            'authorization_expiry_date' => now()->addYear(),
            'species_allowed' => 'Cattle',
            'status' => 'active',
        ]);
        $otherIntake = AnimalIntake::create([
            'facility_id' => $otherFacility->id,
            'intake_date' => now(),
            'supplier_firstname' => 'X',
            'supplier_lastname' => 'Y',
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals' => 1,
            'status' => AnimalIntake::STATUS_APPROVED,
            'is_draft' => false,
        ]);
        $otherPlan = SlaughterPlan::create([
            'slaughter_date' => now()->toDateString(),
            'facility_id' => $otherFacility->id,
            'animal_intake_id' => $otherIntake->id,
            'inspector_id' => $otherInspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals_scheduled' => 1,
            'status' => SlaughterPlan::STATUS_APPROVED,
        ]);
        $otherExecution = SlaughterExecution::create([
            'slaughter_plan_id' => $otherPlan->id,
            'actual_animals_slaughtered' => 1,
            'slaughter_time' => today()->format('Y-m-d').' 09:00:00',
            'status' => SlaughterExecution::STATUS_COMPLETED,
        ]);
        $otherBatch = Batch::factory()->create([
            'slaughter_execution_id' => $otherExecution->id,
            'inspector_id' => $otherInspector->id,
            'batch_code' => 'BAT-OTHER-SECRET',
            'species' => AnimalIntake::SPECIES_CATTLE,
            'quantity' => 50.00,
            'quantity_unit' => 'kg',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('batches.hub'));

        $response->assertOk();
        $response->assertSee($ownBatch->batch_code);
        $response->assertDontSee($otherBatch->batch_code);
    }

    public function test_animal_count_and_total_meat_correct(): void
    {
        $this->actingAs($this->user)
            ->post(route('batches.store'), $this->validStorePayload());

        $batch = Batch::with('items')->firstOrFail();

        $this->assertSame(5, $batch->fresh()->animal_count);
        $this->assertTrue(abs($batch->fresh()->total_meat_quantity_kg - 625.0) < 0.01);
    }

    public function test_post_mortem_complete_when_all_items_have_outcome(): void
    {
        $this->actingAs($this->user)
            ->post(route('batches.store'), $this->validStorePayload());

        $batch = Batch::with('items')->firstOrFail();

        $this->actingAs($this->user)
            ->post(route('post-mortem-inspections.store'), $this->validPmPayload($batch));

        $this->assertTrue($batch->fresh()->isPostMortemComplete());
        $this->assertDatabaseCount('post_mortem_inspection_items', 5);
    }

    public function test_post_mortem_uses_execution_fallback_when_batch_has_no_items(): void
    {
        $batch = Batch::factory()->create([
            'slaughter_execution_id' => $this->execution->id,
            'inspector_id' => $this->inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'quantity' => 625.00,
            'quantity_unit' => 'kg',
        ]);

        $this->assertDatabaseCount('batch_items', 0);
        $this->assertFalse($batch->hasPerAnimalData());

        $animals = $batch->inspectableAnimalsForPostMortem();
        $this->assertCount(5, $animals);
        $this->assertTrue($animals->every(fn (array $animal) => $animal['source'] === 'execution'));
        $this->assertTrue($animals->every(fn (array $animal) => $animal['batch_item_id'] === null));

        $response = $this->actingAs($this->user)
            ->post(route('post-mortem-inspections.store'), $this->validPmPayloadFromInspectableAnimals($batch));

        $response->assertRedirect(route('post-mortem-inspections.hub'));

        $this->assertDatabaseCount('batch_items', 5);
        $this->assertDatabaseCount('post_mortem_inspection_items', 5);
        $this->assertDatabaseHas('post_mortem_inspections', [
            'batch_id' => $batch->id,
            'approved_quantity' => 550.00,
            'condemned_quantity' => 0,
            'total_examined' => 625.00,
        ]);

        foreach ($this->executionItems as $execItem) {
            $this->assertDatabaseHas('batch_items', [
                'batch_id' => $batch->id,
                'animal_intake_item_id' => $execItem->animal_intake_item_id,
                'slaughter_execution_item_id' => $execItem->id,
            ]);
            $this->assertDatabaseHas('post_mortem_inspection_items', [
                'animal_intake_item_id' => $execItem->animal_intake_item_id,
                'outcome' => 'approved',
            ]);
            $this->assertDatabaseHas('post_mortem_observations', [
                'animal_intake_item_id' => $execItem->animal_intake_item_id,
            ]);
        }

        $this->assertTrue($batch->fresh()->isPostMortemComplete());
    }
}
