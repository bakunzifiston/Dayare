<?php

namespace Tests\Feature\SlaughterExecution;

use App\Models\AnimalIntake;
use App\Models\AnimalIntakeItem;
use App\Models\AnteMortemInspection;
use App\Models\AnteMortemInspectionItem;
use App\Models\Business;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\SlaughterExecution;
use App\Models\SlaughterExecutionItem;
use App\Models\SlaughterPlan;
use App\Models\Species;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class SlaughterExecutionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Business $business;

    private Facility $facility;

    private Inspector $inspector;

    private AnimalIntake $intake;

    private SlaughterPlan $plan;

    private AnteMortemInspection $amInspection;

    /** @var Collection<int, AnimalIntakeItem> */
    private Collection $assignedItems;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureConfiguredSpecies();

        $this->user = User::factory()->create();
        $this->business = Business::create([
            'user_id' => $this->user->id,
            'business_name' => 'Slaughter Execution Test Co',
            'registration_number' => 'REG-SE-'.uniqid(),
            'contact_phone' => '+250788000300',
            'email' => 'se-'.uniqid().'@test.com',
            'status' => 'active',
        ]);
        $this->facility = Facility::create([
            'business_id' => $this->business->id,
            'facility_name' => 'SE Test Slaughterhouse',
            'facility_type' => 'slaughterhouse',
            'status' => 'active',
        ]);
        $this->inspector = Inspector::create([
            'facility_id' => $this->facility->id,
            'first_name' => 'Insp',
            'last_name' => 'Exec',
            'national_id' => (string) random_int(100000000000, 999999999999),
            'phone_number' => '+250788'.random_int(100000, 999999),
            'email' => 'insp-se-'.uniqid().'@test.com',
            'dob' => '1988-01-01',
            'nationality' => 'Rwandan',
            'country' => 'Rwanda',
            'district' => 'Kigali',
            'sector' => 'Gasabo',
            'authorization_number' => 'AUTH-SE-'.uniqid(),
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

        $this->assignedItems = collect();
        for ($i = 1; $i <= 5; $i++) {
            $item = AnimalIntakeItem::create([
                'animal_intake_id' => $this->intake->id,
                'ear_tag' => 'SE-C-'.$this->intake->id.'-'.$i,
                'species' => AnimalIntake::SPECIES_CATTLE,
                'sex' => AnimalIntake::SEX_MALE,
                'unit_price' => 100000,
                'live_weight_kg' => 200 + $i,
                'health_status' => AnimalIntakeItem::HEALTH_HEALTHY,
            ]);
            $this->assignedItems->push($item);
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
            ->whereIn('id', $this->assignedItems->pluck('id'))
            ->update(['slaughter_plan_id' => $this->plan->id]);
        $this->assignedItems = AnimalIntakeItem::query()
            ->whereIn('id', $this->assignedItems->pluck('id'))
            ->orderBy('id')
            ->get();

        $this->amInspection = AnteMortemInspection::create([
            'slaughter_plan_id' => $this->plan->id,
            'inspector_id' => $this->inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_examined' => 5,
            'number_approved' => 5,
            'number_rejected' => 0,
            'inspection_date' => today(),
            'examined_count_source' => AnteMortemInspection::SOURCE_ITEMS,
        ]);

        foreach ($this->assignedItems as $assignedItem) {
            AnteMortemInspectionItem::create([
                'ante_mortem_inspection_id' => $this->amInspection->id,
                'animal_intake_item_id' => $assignedItem->id,
                'outcome' => AnteMortemInspectionItem::OUTCOME_APPROVED,
            ]);
        }
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

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validStorePayload(array $overrides = []): array
    {
        return array_merge([
            'slaughter_plan_id' => $this->plan->id,
            'actual_animals_slaughtered' => 5,
            'slaughter_time' => today()->format('Y-m-d').' 10:00:00',
            'status' => 'completed',
        ], $overrides);
    }

    /**
     * @param  list<float>|null  $quantities
     * @return list<array<string, mixed>>
     */
    private function itemSlaughtersPayload(?array $quantities = null): array
    {
        return $this->assignedItems->values()->map(function (AnimalIntakeItem $item, int $index) use ($quantities) {
            return [
                'animal_intake_item_id' => $item->id,
                'meat_quantity_kg' => $quantities[$index] ?? 100.00,
                'notes' => null,
            ];
        })->all();
    }

    public function test_store_creates_execution(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('slaughter-executions.store'), $this->validStorePayload());

        $response->assertRedirect(route('slaughter-executions.hub'));
        $this->assertDatabaseCount('slaughter_executions', 1);
        $this->assertDatabaseHas('slaughter_executions', [
            'slaughter_plan_id' => $this->plan->id,
            'actual_animals_slaughtered' => 5,
            'status' => SlaughterExecution::STATUS_COMPLETED,
        ]);
    }

    public function test_store_blocked_without_ante_mortem(): void
    {
        $planWithoutAm = SlaughterPlan::create([
            'slaughter_date' => now()->addDays(2)->toDateString(),
            'facility_id' => $this->facility->id,
            'animal_intake_id' => $this->intake->id,
            'inspector_id' => $this->inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals_scheduled' => 3,
            'status' => SlaughterPlan::STATUS_APPROVED,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('slaughter-executions.store'), $this->validStorePayload([
                'slaughter_plan_id' => $planWithoutAm->id,
                'actual_animals_slaughtered' => 3,
            ]));

        $response->assertSessionHasErrors('slaughter_plan_id');
        $this->assertDatabaseCount('slaughter_executions', 0);
    }

    public function test_store_succeeds_when_beyond_24_hours_and_flags_report(): void
    {
        $this->amInspection->update(['inspection_date' => today()->subDay()]);

        $response = $this->actingAs($this->user)
            ->post(route('slaughter-executions.store'), $this->validStorePayload([
                'slaughter_time' => today()->addDay()->format('Y-m-d').' 10:00:00',
            ]));

        $response->assertRedirect(route('slaughter-executions.hub'));
        $execution = SlaughterExecution::query()->first();
        $this->assertNotNull($execution);
        $execution->load('slaughterPlan.anteMortemInspections');
        $this->assertTrue($execution->exceedsAnteMortemWindow());
    }

    public function test_store_within_24_hours_succeeds(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('slaughter-executions.store'), $this->validStorePayload([
                'slaughter_time' => today()->format('Y-m-d').' 10:00:00',
            ]));

        $response->assertRedirect(route('slaughter-executions.hub'));
        $this->assertDatabaseCount('slaughter_executions', 1);
        $execution = SlaughterExecution::query()->first();
        $execution->load('slaughterPlan.anteMortemInspections');
        $this->assertFalse($execution->exceedsAnteMortemWindow());
    }

    public function test_store_blocked_when_count_exceeds_approved(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('slaughter-executions.store'), $this->validStorePayload([
                'actual_animals_slaughtered' => 7,
            ]));

        $response->assertSessionHasErrors('actual_animals_slaughtered');
        $this->assertDatabaseCount('slaughter_executions', 0);
    }

    public function test_store_with_per_animal_items_creates_execution_items(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('slaughter-executions.store'), $this->validStorePayload([
                'item_slaughters' => $this->itemSlaughtersPayload(),
            ]));

        $response->assertRedirect(route('slaughter-executions.hub'));
        $this->assertDatabaseCount('slaughter_execution_items', 5);
        $this->assertDatabaseHas('slaughter_executions', [
            'slaughter_plan_id' => $this->plan->id,
            'slaughter_count_source' => SlaughterExecution::SOURCE_ITEMS,
            'actual_animals_slaughtered' => 5,
        ]);
    }

    public function test_meat_quantity_recorded_per_animal(): void
    {
        $quantities = [120.50, 135.00, 98.75, 145.25, 110.00];

        $this->actingAs($this->user)
            ->post(route('slaughter-executions.store'), $this->validStorePayload([
                'item_slaughters' => $this->itemSlaughtersPayload($quantities),
            ]))
            ->assertRedirect(route('slaughter-executions.hub'));

        foreach ($this->assignedItems->values() as $index => $item) {
            $this->assertDatabaseHas('slaughter_execution_items', [
                'animal_intake_item_id' => $item->id,
                'meat_quantity_kg' => $quantities[$index],
            ]);
        }
    }

    public function test_update_replaces_execution_items(): void
    {
        $execution = SlaughterExecution::factory()
            ->withExecutionItems()
            ->create(['slaughter_plan_id' => $this->plan->id]);

        $newQuantities = [50.00, 55.00, 60.00, 65.00, 70.00];

        $response = $this->actingAs($this->user)
            ->put(route('slaughter-executions.update', $execution), $this->validStorePayload([
                'item_slaughters' => $this->itemSlaughtersPayload($newQuantities),
            ]));

        $response->assertRedirect(route('slaughter-executions.hub'));
        $this->assertDatabaseCount('slaughter_execution_items', 5);
        foreach ($this->assignedItems->values() as $index => $item) {
            $this->assertDatabaseHas('slaughter_execution_items', [
                'slaughter_execution_id' => $execution->id,
                'animal_intake_item_id' => $item->id,
                'meat_quantity_kg' => $newQuantities[$index],
            ]);
        }
    }

    public function test_legacy_plan_bypasses_am_approved_count_check(): void
    {
        $legacyIntake = AnimalIntake::create([
            'facility_id' => $this->facility->id,
            'intake_date' => now(),
            'supplier_firstname' => 'Leg',
            'supplier_lastname' => 'Acy',
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals' => 5,
            'status' => AnimalIntake::STATUS_APPROVED,
            'is_draft' => false,
        ]);
        $legacyPlan = SlaughterPlan::create([
            'slaughter_date' => now()->addDay()->toDateString(),
            'facility_id' => $this->facility->id,
            'animal_intake_id' => $legacyIntake->id,
            'inspector_id' => $this->inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals_scheduled' => 5,
            'status' => SlaughterPlan::STATUS_APPROVED,
        ]);
        AnteMortemInspection::create([
            'slaughter_plan_id' => $legacyPlan->id,
            'inspector_id' => $this->inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_examined' => 5,
            'number_approved' => 5,
            'number_rejected' => 0,
            'inspection_date' => today(),
            'examined_count_source' => AnteMortemInspection::SOURCE_MANUAL,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('slaughter-executions.store'), $this->validStorePayload([
                'slaughter_plan_id' => $legacyPlan->id,
                'actual_animals_slaughtered' => 5,
            ]));

        $response->assertRedirect(route('slaughter-executions.hub'));
        $this->assertDatabaseHas('slaughter_executions', [
            'slaughter_plan_id' => $legacyPlan->id,
            'actual_animals_slaughtered' => 5,
        ]);
        $this->assertDatabaseCount('slaughter_execution_items', 0);
    }

    public function test_index_scoped_to_accessible_facilities(): void
    {
        $otherUser = User::factory()->create();
        $otherBusiness = Business::create([
            'user_id' => $otherUser->id,
            'business_name' => 'Other SE Co',
            'registration_number' => 'REG-OTHER-SE-'.uniqid(),
            'contact_phone' => '+250788000400',
            'email' => 'other-se-'.uniqid().'@test.com',
            'status' => 'active',
        ]);
        $otherFacility = Facility::create([
            'business_id' => $otherBusiness->id,
            'facility_name' => 'Other SE Slaughterhouse',
            'facility_type' => 'slaughterhouse',
            'status' => 'active',
        ]);
        $otherInspector = Inspector::create([
            'facility_id' => $otherFacility->id,
            'first_name' => 'Other',
            'last_name' => 'Inspector',
            'national_id' => (string) random_int(100000000000, 999999999999),
            'phone_number' => '+250788'.random_int(100000, 999999),
            'email' => 'other-se-insp-'.uniqid().'@test.com',
            'dob' => '1988-01-01',
            'nationality' => 'Rwandan',
            'country' => 'Rwanda',
            'district' => 'Kigali',
            'sector' => 'Gasabo',
            'authorization_number' => 'AUTH-OSE-'.uniqid(),
            'authorization_issue_date' => now()->subYear(),
            'authorization_expiry_date' => now()->addYear(),
            'species_allowed' => 'Cattle',
            'status' => 'active',
        ]);
        $otherIntake = AnimalIntake::create([
            'facility_id' => $otherFacility->id,
            'intake_date' => now(),
            'supplier_firstname' => 'O',
            'supplier_lastname' => 'S',
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals' => 1,
            'status' => AnimalIntake::STATUS_APPROVED,
            'is_draft' => false,
        ]);
        $otherPlan = SlaughterPlan::create([
            'slaughter_date' => now()->addDay()->toDateString(),
            'facility_id' => $otherFacility->id,
            'animal_intake_id' => $otherIntake->id,
            'inspector_id' => $otherInspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals_scheduled' => 1,
            'status' => SlaughterPlan::STATUS_APPROVED,
        ]);
        AnteMortemInspection::create([
            'slaughter_plan_id' => $otherPlan->id,
            'inspector_id' => $otherInspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_examined' => 1,
            'number_approved' => 1,
            'number_rejected' => 0,
            'inspection_date' => today(),
        ]);
        $otherExecution = SlaughterExecution::factory()->create([
            'slaughter_plan_id' => $otherPlan->id,
            'actual_animals_slaughtered' => 1,
            'slaughter_time' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('slaughter-executions.index'));

        $response->assertOk();
        $response->assertDontSee('Other SE Slaughterhouse', false);
        $response->assertDontSee('data-execution-id="'.$otherExecution->id.'"', false);
    }

    public function test_total_meat_quantity_computed_from_items(): void
    {
        $quantities = [120.50, 135.00, 98.75, 145.25, 110.00];
        $execution = SlaughterExecution::factory()->create([
            'slaughter_plan_id' => $this->plan->id,
            'slaughter_count_source' => SlaughterExecution::SOURCE_ITEMS,
            'actual_animals_slaughtered' => 5,
        ]);

        foreach ($this->assignedItems->values() as $index => $item) {
            SlaughterExecutionItem::create([
                'slaughter_execution_id' => $execution->id,
                'animal_intake_item_id' => $item->id,
                'meat_quantity_kg' => $quantities[$index],
            ]);
        }

        $execution->load('executionItems');
        $this->assertSame(array_sum($quantities), $execution->total_meat_quantity_kg);
    }

    public function test_store_blocked_when_slaughter_before_ante_mortem(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('slaughter-executions.store'), $this->validStorePayload([
                'slaughter_time' => today()->subDay()->format('Y-m-d').' 10:00:00',
            ]));

        $response->assertSessionHasErrors('slaughter_time');
        $this->assertDatabaseCount('slaughter_executions', 0);
    }

    public function test_store_single_animal_among_approved_session(): void
    {
        $firstItem = $this->assignedItems->first();

        $response = $this->actingAs($this->user)
            ->post(route('slaughter-executions.store'), $this->validStorePayload([
                'actual_animals_slaughtered' => 1,
                'status' => SlaughterExecution::STATUS_IN_PROGRESS,
                'item_slaughters' => [[
                    'animal_intake_item_id' => $firstItem->id,
                    'meat_quantity_kg' => 102.50,
                ]],
            ]));

        $response->assertRedirect(route('slaughter-executions.hub'));
        $this->assertDatabaseCount('slaughter_execution_items', 1);
        $this->assertDatabaseHas('slaughter_executions', [
            'actual_animals_slaughtered' => 1,
            'slaughter_count_source' => SlaughterExecution::SOURCE_ITEMS,
            'status' => SlaughterExecution::STATUS_IN_PROGRESS,
        ]);
        $this->assertDatabaseHas('slaughter_execution_items', [
            'animal_intake_item_id' => $firstItem->id,
            'meat_quantity_kg' => 102.50,
        ]);
    }

    public function test_store_rejects_already_slaughtered_animal_on_same_session(): void
    {
        $firstItem = $this->assignedItems->first();

        $this->actingAs($this->user)
            ->post(route('slaughter-executions.store'), $this->validStorePayload([
                'actual_animals_slaughtered' => 1,
                'item_slaughters' => [[
                    'animal_intake_item_id' => $firstItem->id,
                    'meat_quantity_kg' => 100.00,
                ]],
            ]))
            ->assertRedirect(route('slaughter-executions.hub'));

        $response = $this->actingAs($this->user)
            ->post(route('slaughter-executions.store'), $this->validStorePayload([
                'actual_animals_slaughtered' => 1,
                'item_slaughters' => [[
                    'animal_intake_item_id' => $firstItem->id,
                    'meat_quantity_kg' => 110.00,
                ]],
            ]));

        $response->assertSessionHasErrors('item_slaughters.0.animal_intake_item_id');
        $this->assertDatabaseCount('slaughter_execution_items', 1);
    }

    public function test_store_rejects_unapproved_item_slaughter(): void
    {
        $otherIntake = AnimalIntake::create([
            'facility_id' => $this->facility->id,
            'intake_date' => now(),
            'supplier_firstname' => 'Bad',
            'supplier_lastname' => 'Item',
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals' => 1,
            'status' => AnimalIntake::STATUS_APPROVED,
            'is_draft' => false,
        ]);
        $unapprovedItem = AnimalIntakeItem::create([
            'animal_intake_id' => $otherIntake->id,
            'slaughter_plan_id' => $this->plan->id,
            'ear_tag' => 'SE-BAD-1',
            'species' => AnimalIntake::SPECIES_CATTLE,
            'sex' => AnimalIntake::SEX_MALE,
            'unit_price' => 100000,
            'health_status' => AnimalIntakeItem::HEALTH_HEALTHY,
        ]);

        $payload = $this->validStorePayload([
            'item_slaughters' => [[
                'animal_intake_item_id' => $unapprovedItem->id,
                'meat_quantity_kg' => 100.00,
            ]],
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('slaughter-executions.store'), $payload);

        $response->assertSessionHasErrors('item_slaughters.0.animal_intake_item_id');
        $this->assertDatabaseCount('slaughter_executions', 0);
    }
}
