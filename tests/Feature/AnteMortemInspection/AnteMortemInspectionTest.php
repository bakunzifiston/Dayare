<?php

namespace Tests\Feature\AnteMortemInspection;

use App\Models\AnimalIntake;
use App\Models\AnimalIntakeItem;
use App\Models\AnteMortemInspection;
use App\Models\AnteMortemInspectionItem;
use App\Models\Business;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\MobileApiToken;
use App\Models\SlaughterPlan;
use App\Models\Species;
use App\Models\User;
use App\Support\AnteMortemChecklist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class AnteMortemInspectionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Business $business;

    private Facility $facility;

    private Inspector $inspector;

    private AnimalIntake $intake;

    private SlaughterPlan $plan;

    /** @var Collection<int, AnimalIntakeItem> */
    private Collection $assignedItems;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureConfiguredSpecies();

        $this->user = User::factory()->create();
        $this->business = Business::create([
            'user_id' => $this->user->id,
            'business_name' => 'Ante-Mortem Test Co',
            'registration_number' => 'REG-AM-'.uniqid(),
            'contact_phone' => '+250788000100',
            'email' => 'am-'.uniqid().'@test.com',
            'status' => 'active',
        ]);
        $this->facility = Facility::create([
            'business_id' => $this->business->id,
            'facility_name' => 'AM Test Slaughterhouse',
            'facility_type' => 'slaughterhouse',
            'status' => 'active',
        ]);
        $this->inspector = Inspector::create([
            'facility_id' => $this->facility->id,
            'first_name' => 'Insp',
            'last_name' => 'Ante',
            'national_id' => (string) random_int(100000000000, 999999999999),
            'phone_number' => '+250788'.random_int(100000, 999999),
            'email' => 'insp-am-'.uniqid().'@test.com',
            'dob' => '1988-01-01',
            'nationality' => 'Rwandan',
            'country' => 'Rwanda',
            'district' => 'Kigali',
            'sector' => 'Gasabo',
            'authorization_number' => 'AUTH-AM-'.uniqid(),
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
                'ear_tag' => 'AM-C-'.$this->intake->id.'-'.$i,
                'species' => AnimalIntake::SPECIES_CATTLE,
                'sex' => AnimalIntake::SEX_MALE,
                'unit_price' => 100000,
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
     * @return array<string, array{value: string, notes: null}>
     */
    private function validObservationsPayload(bool $hasAssignedAnimals = true): array
    {
        $items = AnteMortemChecklist::itemsForInspection('Cattle', $hasAssignedAnimals);

        return collect($items)->mapWithKeys(function (array $meta, string $itemKey): array {
            $allowed = AnteMortemChecklist::allowedValuesForItem('Cattle', $itemKey);
            $value = in_array('normal', $allowed, true)
                ? 'normal'
                : (in_array('approved', $allowed, true) ? 'approved' : '');

            return [$itemKey => ['value' => $value, 'notes' => null]];
        })->all();
    }

    /**
     * @return list<array{animal_intake_item_id: int, outcome: string, observations: array<string, array{value: string, notes: null}>}>
     */
    private function validItemOutcomesPayload(?callable $outcomeForItem = null): array
    {
        $observations = $this->validObservationsPayload();

        return $this->assignedItems->map(function (AnimalIntakeItem $item, int $index) use ($outcomeForItem, $observations) {
            $outcome = $outcomeForItem !== null
                ? $outcomeForItem($item, $index)
                : AnteMortemInspectionItem::OUTCOME_APPROVED;

            return [
                'animal_intake_item_id' => $item->id,
                'outcome' => $outcome,
                'observations' => $observations,
            ];
        })->values()->all();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validStorePayload(array $overrides = []): array
    {
        return array_merge([
            'slaughter_plan_id' => $this->plan->id,
            'inspector_id' => $this->inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_examined' => 5,
            'number_approved' => 5,
            'number_rejected' => 0,
            'inspection_date' => today()->toDateString(),
            'notes' => null,
            'observations' => [],
            'item_outcomes' => $this->validItemOutcomesPayload(),
        ], $overrides);
    }

    /**
     * @return array<string, string>
     */
    private function mobileAuthHeaders(): array
    {
        $plainToken = 'mobile-test-'.uniqid();
        MobileApiToken::create([
            'user_id' => $this->user->id,
            'name' => 'test',
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDay(),
        ]);

        return ['Authorization' => 'Bearer '.$plainToken];
    }

    public function test_store_creates_inspection_with_observations(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('ante-mortem-inspections.store'), $this->validStorePayload());

        $response->assertRedirect(route('ante-mortem-inspections.index'));
        $this->assertDatabaseCount('ante_mortem_inspections', 1);
        $this->assertDatabaseCount('ante_mortem_observations', 45);
        $this->assertDatabaseHas('ante_mortem_inspections', [
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_examined' => 5,
            'examined_count_source' => AnteMortemInspection::SOURCE_ITEMS,
        ]);
        $this->assertDatabaseCount('ante_mortem_inspection_items', 5);
    }

    public function test_store_fails_without_item_outcomes_when_animals_assigned(): void
    {
        $payload = $this->validStorePayload();
        unset($payload['item_outcomes']);

        $response = $this->actingAs($this->user)
            ->post(route('ante-mortem-inspections.store'), $payload);

        $response->assertSessionHasErrors('item_outcomes');
        $this->assertDatabaseCount('ante_mortem_inspections', 0);
    }

    public function test_store_fails_when_counts_invalid(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('ante-mortem-inspections.store'), $this->validStorePayload([
                'number_examined' => 4,
                'number_approved' => 3,
                'number_rejected' => 2,
            ]));

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('ante_mortem_inspections', 0);
    }

    public function test_store_fails_when_inspector_facility_mismatch(): void
    {
        $otherFacility = Facility::create([
            'business_id' => $this->business->id,
            'facility_name' => 'Other Facility',
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

        $response = $this->actingAs($this->user)
            ->post(route('ante-mortem-inspections.store'), $this->validStorePayload([
                'inspector_id' => $otherInspector->id,
            ]));

        $response->assertSessionHasErrors('inspector_id');
        $this->assertDatabaseCount('ante_mortem_inspections', 0);
    }

    public function test_store_fails_when_checklist_incomplete(): void
    {
        $itemOutcomes = $this->validItemOutcomesPayload();
        unset($itemOutcomes[0]['observations']['locomotion']);

        $response = $this->actingAs($this->user)
            ->post(route('ante-mortem-inspections.store'), $this->validStorePayload([
                'item_outcomes' => $itemOutcomes,
            ]));

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('ante_mortem_inspections', 0);
    }

    public function test_update_works_correctly(): void
    {
        $inspection = AnteMortemInspection::factory()
            ->withObservations()
            ->create([
                'slaughter_plan_id' => $this->plan->id,
                'inspector_id' => $this->inspector->id,
                'species' => AnimalIntake::SPECIES_CATTLE,
            ]);

        $response = $this->actingAs($this->user)
            ->put(route('ante-mortem-inspections.update', $inspection), $this->validStorePayload([
                'item_outcomes' => $this->validItemOutcomesPayload(
                    fn (AnimalIntakeItem $item, int $index) => $index === 4
                        ? AnteMortemInspectionItem::OUTCOME_DEFERRED
                        : AnteMortemInspectionItem::OUTCOME_APPROVED,
                ),
            ]));

        $response->assertRedirect(route('ante-mortem-inspections.index'));
        $this->assertDatabaseHas('ante_mortem_inspections', [
            'id' => $inspection->id,
            'number_examined' => 5,
            'number_approved' => 4,
            'number_rejected' => 0,
            'examined_count_source' => AnteMortemInspection::SOURCE_ITEMS,
        ]);
        $this->assertDatabaseCount('ante_mortem_observations', 45);
    }

    public function test_decision_checklist_item_not_required_when_animals_assigned(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('ante-mortem-inspections.store'), $this->validStorePayload());

        $response->assertRedirect(route('ante-mortem-inspections.index'));
        $this->assertDatabaseMissing('ante_mortem_observations', ['item' => 'decision']);
    }

    public function test_update_replaces_inspection_items_transactionally(): void
    {
        $inspection = AnteMortemInspection::factory()->create([
            'slaughter_plan_id' => $this->plan->id,
            'inspector_id' => $this->inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
        ]);

        $item0 = $this->assignedItems[0];
        $item1 = $this->assignedItems[1];
        $item2 = $this->assignedItems[2];

        foreach ([
            [$item0, AnteMortemInspectionItem::OUTCOME_APPROVED],
            [$item1, AnteMortemInspectionItem::OUTCOME_APPROVED],
            [$item2, AnteMortemInspectionItem::OUTCOME_REJECTED],
        ] as [$item, $outcome]) {
            AnteMortemInspectionItem::factory()->create([
                'ante_mortem_inspection_id' => $inspection->id,
                'animal_intake_item_id' => $item->id,
                'outcome' => $outcome,
            ]);
        }

        $item2->update(['health_status' => AnimalIntakeItem::HEALTH_REJECTED]);

        $response = $this->actingAs($this->user)
            ->put(route('ante-mortem-inspections.update', $inspection), $this->validStorePayload([
                'item_outcomes' => $this->validItemOutcomesPayload(),
            ]));

        $response->assertRedirect(route('ante-mortem-inspections.index'));
        $this->assertDatabaseCount('ante_mortem_inspection_items', 5);
        $this->assertDatabaseHas('ante_mortem_inspection_items', [
            'animal_intake_item_id' => $item2->id,
            'outcome' => AnteMortemInspectionItem::OUTCOME_APPROVED,
        ]);
        $this->assertSame(
            AnimalIntakeItem::HEALTH_OBSERVATION,
            AnimalIntakeItem::find($item2->id)?->health_status,
        );
    }

    public function test_store_with_per_animal_outcomes_creates_inspection_items(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('ante-mortem-inspections.store'), $this->validStorePayload());

        $response->assertRedirect(route('ante-mortem-inspections.index'));
        $this->assertDatabaseCount('ante_mortem_inspection_items', 5);
        $this->assertDatabaseHas('ante_mortem_inspections', [
            'examined_count_source' => AnteMortemInspection::SOURCE_ITEMS,
            'number_examined' => 5,
        ]);
    }

    public function test_rejected_animal_updates_intake_item_health_status(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('ante-mortem-inspections.store'), $this->validStorePayload([
                'item_outcomes' => $this->validItemOutcomesPayload(
                    fn (AnimalIntakeItem $item, int $index) => $index === 4
                        ? AnteMortemInspectionItem::OUTCOME_REJECTED
                        : AnteMortemInspectionItem::OUTCOME_APPROVED,
                ),
            ]));

        $response->assertRedirect(route('ante-mortem-inspections.index'));

        $rejectedItem = $this->assignedItems[4];
        $this->assertSame(
            AnimalIntakeItem::HEALTH_REJECTED,
            AnimalIntakeItem::find($rejectedItem->id)?->health_status,
        );

        foreach ($this->assignedItems->take(4) as $item) {
            $this->assertSame(
                AnimalIntakeItem::HEALTH_HEALTHY,
                AnimalIntakeItem::find($item->id)?->health_status,
            );
        }
    }

    public function test_examined_count_cannot_exceed_assigned_items(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('ante-mortem-inspections.store'), $this->validStorePayload([
                'number_examined' => 7,
                'number_approved' => 7,
            ]));

        $response->assertSessionHasErrors('number_examined');
        $this->assertDatabaseCount('ante_mortem_inspections', 0);
    }

    public function test_species_must_match_plan_species(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('ante-mortem-inspections.store'), $this->validStorePayload([
                'species' => AnimalIntake::SPECIES_GOAT,
            ]));

        $response->assertSessionHasErrors('species');
        $this->assertDatabaseCount('ante_mortem_inspections', 0);
    }

    public function test_legacy_plan_bypasses_count_cross_check(): void
    {
        $legacyIntake = AnimalIntake::create([
            'facility_id' => $this->facility->id,
            'intake_date' => now(),
            'supplier_firstname' => 'Legacy',
            'supplier_lastname' => 'Intake',
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals' => 5,
            'status' => AnimalIntake::STATUS_APPROVED,
            'is_draft' => false,
        ]);

        $legacyPlan = SlaughterPlan::create([
            'slaughter_date' => now()->addDays(2)->toDateString(),
            'facility_id' => $this->facility->id,
            'animal_intake_id' => $legacyIntake->id,
            'inspector_id' => $this->inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals_scheduled' => 5,
            'status' => SlaughterPlan::STATUS_APPROVED,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('ante-mortem-inspections.store'), $this->validStorePayload([
                'slaughter_plan_id' => $legacyPlan->id,
                'number_examined' => 5,
                'number_approved' => 5,
                'item_outcomes' => [],
                'observations' => $this->validObservationsPayload(hasAssignedAnimals: false),
            ]));

        $response->assertRedirect(route('ante-mortem-inspections.index'));
        $this->assertDatabaseCount('ante_mortem_inspections', 1);
        $this->assertDatabaseCount('ante_mortem_inspection_items', 0);
    }

    public function test_index_scoped_to_accessible_facilities(): void
    {
        $otherUser = User::factory()->create();
        $otherBusiness = Business::create([
            'user_id' => $otherUser->id,
            'business_name' => 'Other AM Co',
            'registration_number' => 'REG-OTHER-'.uniqid(),
            'contact_phone' => '+250788000200',
            'email' => 'other-am-'.uniqid().'@test.com',
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
            'last_name' => 'Inspector',
            'national_id' => (string) random_int(100000000000, 999999999999),
            'phone_number' => '+250788'.random_int(100000, 999999),
            'email' => 'other-fac-'.uniqid().'@test.com',
            'dob' => '1988-01-01',
            'nationality' => 'Rwandan',
            'country' => 'Rwanda',
            'district' => 'Kigali',
            'sector' => 'Gasabo',
            'authorization_number' => 'AUTH-OF-'.uniqid(),
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
        $otherInspection = AnteMortemInspection::factory()->create([
            'slaughter_plan_id' => $otherPlan->id,
            'inspector_id' => $otherInspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('ante-mortem-inspections.index'));

        $response->assertOk();
        $response->assertDontSee('Other Slaughterhouse', false);
        $response->assertDontSee('Session #'.$otherInspection->slaughter_plan_id, false);
    }

    public function test_mobile_store_creates_inspection(): void
    {
        $response = $this->withHeaders($this->mobileAuthHeaders())
            ->postJson('/api/v1/ante-mortem-inspections', $this->validStorePayload());

        $response->assertCreated();
        $this->assertDatabaseCount('ante_mortem_inspections', 1);
        $this->assertDatabaseCount('ante_mortem_observations', 45);
        $this->assertDatabaseCount('ante_mortem_inspection_items', 5);
    }

    public function test_update_restores_health_status_for_removed_rejection(): void
    {
        $item0 = $this->assignedItems[0];

        $inspection = AnteMortemInspection::factory()->create([
            'slaughter_plan_id' => $this->plan->id,
            'inspector_id' => $this->inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
        ]);

        AnteMortemInspectionItem::factory()->create([
            'ante_mortem_inspection_id' => $inspection->id,
            'animal_intake_item_id' => $item0->id,
            'outcome' => AnteMortemInspectionItem::OUTCOME_REJECTED,
        ]);
        $item0->update(['health_status' => AnimalIntakeItem::HEALTH_REJECTED]);

        $response = $this->actingAs($this->user)
            ->put(route('ante-mortem-inspections.update', $inspection), $this->validStorePayload([
                'item_outcomes' => $this->validItemOutcomesPayload(),
            ]));

        $response->assertRedirect(route('ante-mortem-inspections.index'));
        $this->assertDatabaseHas('ante_mortem_inspection_items', [
            'animal_intake_item_id' => $item0->id,
            'outcome' => AnteMortemInspectionItem::OUTCOME_APPROVED,
        ]);
        $this->assertSame(
            AnimalIntakeItem::HEALTH_OBSERVATION,
            AnimalIntakeItem::find($item0->id)?->health_status,
        );
    }
}
