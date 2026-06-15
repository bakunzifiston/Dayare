<?php

namespace Tests\Feature\SlaughterPlan;

use App\Models\AnimalIntake;
use App\Models\AnimalIntakeItem;
use App\Models\Business;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\SlaughterPlan;
use App\Models\Species;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlaughterPlanAssignmentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Business, 2: Facility, 3: Inspector}
     */
    private function createProcessorContext(): array
    {
        $this->ensureConfiguredSpecies();

        $user = User::factory()->create();
        $business = Business::create([
            'user_id' => $user->id,
            'business_name' => 'Assignment Test Co',
            'registration_number' => 'REG-ASN-'.uniqid(),
            'contact_phone' => '+250788000001',
            'email' => 'assign-'.uniqid().'@test.com',
            'status' => 'active',
        ]);
        $facility = Facility::create([
            'business_id' => $business->id,
            'facility_name' => 'Assignment Slaughterhouse',
            'facility_type' => 'slaughterhouse',
            'status' => 'active',
        ]);
        $inspector = Inspector::create([
            'facility_id' => $facility->id,
            'first_name' => 'Insp',
            'last_name' => 'Assign',
            'national_id' => (string) random_int(100000000000, 999999999999),
            'phone_number' => '+250788'.random_int(100000, 999999),
            'email' => 'insp-'.uniqid().'@test.com',
            'dob' => '1988-01-01',
            'nationality' => 'Rwandan',
            'country' => 'Rwanda',
            'district' => 'Kigali',
            'sector' => 'Gasabo',
            'authorization_number' => 'AUTH-'.uniqid(),
            'authorization_issue_date' => now()->subYear(),
            'authorization_expiry_date' => now()->addYear(),
            'species_allowed' => 'Cattle, Goat',
            'status' => 'active',
        ]);

        return [$user, $business, $facility, $inspector];
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

    private function createApprovedIntake(Facility $facility, array $overrides = []): AnimalIntake
    {
        return AnimalIntake::create(array_merge([
            'facility_id' => $facility->id,
            'intake_date' => now(),
            'supplier_firstname' => 'Sup',
            'supplier_lastname' => 'Plier',
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals' => 10,
            'status' => AnimalIntake::STATUS_APPROVED,
            'is_draft' => false,
            'health_certificate_expiry_date' => now()->addMonth(),
        ], $overrides));
    }

    /**
     * @return list<int>
     */
    private function createItems(
        AnimalIntake $intake,
        string $species,
        int $count,
        string $healthStatus = AnimalIntakeItem::HEALTH_HEALTHY,
        string $prefix = 'TAG',
    ): array {
        $ids = [];

        for ($i = 1; $i <= $count; $i++) {
            $item = AnimalIntakeItem::create([
                'animal_intake_id' => $intake->id,
                'ear_tag' => $prefix.'-'.$intake->id.'-'.$i,
                'species' => $species,
                'sex' => AnimalIntake::SEX_MALE,
                'unit_price' => 100000,
                'health_status' => $healthStatus,
            ]);
            $ids[] = $item->id;
        }

        return $ids;
    }

    /**
     * @return array<string, mixed>
     */
    private function planStorePayload(
        Facility $facility,
        AnimalIntake $intake,
        Inspector $inspector,
        array $overrides = [],
    ): array {
        return array_merge([
            'slaughter_date' => now()->addDays(2)->toDateString(),
            'facility_id' => $facility->id,
            'animal_intake_id' => $intake->id,
            'inspector_id' => $inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals_scheduled' => 1,
            'status' => SlaughterPlan::STATUS_PLANNED,
        ], $overrides);
    }

    public function test_creating_plan_assigns_correct_items(): void
    {
        [$user, , $facility, $inspector] = $this->createProcessorContext();
        $intake = $this->createApprovedIntake($facility);
        $itemIds = $this->createItems($intake, AnimalIntake::SPECIES_CATTLE, 5);

        $response = $this->actingAs($user)->post(
            route('slaughter-plans.store'),
            $this->planStorePayload($facility, $intake, $inspector, [
                'number_of_animals_scheduled' => 3,
            ]),
        );

        $response->assertRedirect(route('slaughter-plans.hub'));
        $this->assertSame(1, SlaughterPlan::count());

        $plan = SlaughterPlan::firstOrFail();
        $this->assertSame(3, AnimalIntakeItem::where('slaughter_plan_id', $plan->id)->count());
        $this->assertSame(2, AnimalIntakeItem::whereNull('slaughter_plan_id')->count());

        $assignedIds = AnimalIntakeItem::query()
            ->where('slaughter_plan_id', $plan->id)
            ->orderBy('id')
            ->pluck('id')
            ->all();
        $this->assertSame(array_slice($itemIds, 0, 3), $assignedIds);
    }

    public function test_overbooking_is_blocked_by_validation(): void
    {
        [$user, , $facility, $inspector] = $this->createProcessorContext();
        $intake = $this->createApprovedIntake($facility);
        $this->createItems($intake, AnimalIntake::SPECIES_CATTLE, 4);

        $response = $this->actingAs($user)->post(
            route('slaughter-plans.store'),
            $this->planStorePayload($facility, $intake, $inspector, [
                'number_of_animals_scheduled' => 6,
            ]),
        );

        $response->assertSessionHasErrors('number_of_animals_scheduled');
        $this->assertSame(0, SlaughterPlan::count());
        $this->assertSame(4, AnimalIntakeItem::whereNull('slaughter_plan_id')->count());
    }

    public function test_species_mismatch_is_blocked(): void
    {
        [$user, , $facility, $inspector] = $this->createProcessorContext();
        $intake = $this->createApprovedIntake($facility);
        $this->createItems($intake, AnimalIntake::SPECIES_CATTLE, 5);

        $response = $this->actingAs($user)->post(
            route('slaughter-plans.store'),
            $this->planStorePayload($facility, $intake, $inspector, [
                'species' => AnimalIntake::SPECIES_GOAT,
                'number_of_animals_scheduled' => 3,
            ]),
        );

        $response->assertSessionHasErrors('species');
        $this->assertSame(0, SlaughterPlan::count());
        $this->assertSame(5, AnimalIntakeItem::whereNull('slaughter_plan_id')->count());
    }

    public function test_mixed_intake_supports_concurrent_plans(): void
    {
        [$user, , $facility, $inspector] = $this->createProcessorContext();
        $intake = $this->createApprovedIntake($facility, ['number_of_animals' => 10]);
        $this->createItems($intake, AnimalIntake::SPECIES_CATTLE, 6, AnimalIntakeItem::HEALTH_HEALTHY, 'C');
        $this->createItems($intake, AnimalIntake::SPECIES_GOAT, 4, AnimalIntakeItem::HEALTH_HEALTHY, 'G');

        $this->actingAs($user)->post(
            route('slaughter-plans.store'),
            $this->planStorePayload($facility, $intake, $inspector, [
                'number_of_animals_scheduled' => 4,
            ]),
        )->assertRedirect(route('slaughter-plans.hub'));

        $this->actingAs($user)->post(
            route('slaughter-plans.store'),
            $this->planStorePayload($facility, $intake, $inspector, [
                'species' => AnimalIntake::SPECIES_GOAT,
                'number_of_animals_scheduled' => 3,
            ]),
        )->assertRedirect(route('slaughter-plans.hub'));

        $this->assertSame(2, SlaughterPlan::count());

        $cattlePlan = SlaughterPlan::where('species', AnimalIntake::SPECIES_CATTLE)->firstOrFail();
        $goatPlan = SlaughterPlan::where('species', AnimalIntake::SPECIES_GOAT)->firstOrFail();

        $this->assertSame(
            4,
            AnimalIntakeItem::where('slaughter_plan_id', $cattlePlan->id)
                ->where('species', AnimalIntake::SPECIES_CATTLE)
                ->count(),
        );
        $this->assertSame(
            3,
            AnimalIntakeItem::where('slaughter_plan_id', $goatPlan->id)
                ->where('species', AnimalIntake::SPECIES_GOAT)
                ->count(),
        );
        $this->assertSame(3, AnimalIntakeItem::whereNull('slaughter_plan_id')->count());
        $this->assertSame(
            2,
            AnimalIntakeItem::whereNull('slaughter_plan_id')
                ->where('species', AnimalIntake::SPECIES_CATTLE)
                ->count(),
        );
        $this->assertSame(
            1,
            AnimalIntakeItem::whereNull('slaughter_plan_id')
                ->where('species', AnimalIntake::SPECIES_GOAT)
                ->count(),
        );
        $this->assertSame(7, AnimalIntakeItem::whereNotNull('slaughter_plan_id')->count());
    }

    public function test_updating_count_rebalances_assignment(): void
    {
        [$user, , $facility, $inspector] = $this->createProcessorContext();
        $intake = $this->createApprovedIntake($facility);
        $this->createItems($intake, AnimalIntake::SPECIES_CATTLE, 8);

        $this->actingAs($user)->post(
            route('slaughter-plans.store'),
            $this->planStorePayload($facility, $intake, $inspector, [
                'number_of_animals_scheduled' => 3,
            ]),
        )->assertRedirect(route('slaughter-plans.hub'));

        $plan = SlaughterPlan::firstOrFail();

        $response = $this->actingAs($user)->put(
            route('slaughter-plans.update', $plan),
            $this->planStorePayload($facility, $intake, $inspector, [
                'number_of_animals_scheduled' => 5,
            ]),
        );

        $response->assertRedirect(route('slaughter-plans.hub'));
        $this->assertSame(5, AnimalIntakeItem::where('slaughter_plan_id', $plan->id)->count());
        $this->assertSame(3, AnimalIntakeItem::whereNull('slaughter_plan_id')->count());
        $this->assertSame(
            5,
            AnimalIntakeItem::where('slaughter_plan_id', $plan->id)->pluck('id')->unique()->count(),
        );
    }

    public function test_deleting_plan_releases_all_items(): void
    {
        [$user, , $facility, $inspector] = $this->createProcessorContext();
        $intake = $this->createApprovedIntake($facility);
        $this->createItems($intake, AnimalIntake::SPECIES_CATTLE, 5);

        $this->actingAs($user)->post(
            route('slaughter-plans.store'),
            $this->planStorePayload($facility, $intake, $inspector, [
                'number_of_animals_scheduled' => 3,
            ]),
        )->assertRedirect(route('slaughter-plans.hub'));

        $plan = SlaughterPlan::firstOrFail();

        $response = $this->actingAs($user)->delete(route('slaughter-plans.destroy', $plan));

        $response->assertRedirect(route('slaughter-plans.hub'));
        $this->assertSame(0, SlaughterPlan::count());
        $this->assertSame(5, AnimalIntakeItem::whereNull('slaughter_plan_id')->count());
    }

    public function test_received_submitted_intake_can_be_planned(): void
    {
        [$user, , $facility, $inspector] = $this->createProcessorContext();
        $intake = $this->createApprovedIntake($facility, [
            'status' => AnimalIntake::STATUS_RECEIVED,
            'submitted_at' => now(),
        ]);
        $this->createItems($intake, AnimalIntake::SPECIES_CATTLE, 3);

        $response = $this->actingAs($user)->post(
            route('slaughter-plans.store'),
            $this->planStorePayload($facility, $intake, $inspector, [
                'number_of_animals_scheduled' => 2,
            ]),
        );

        $response->assertRedirect(route('slaughter-plans.hub'));
        $this->assertSame(1, SlaughterPlan::count());
        $this->assertSame(2, AnimalIntakeItem::whereNotNull('slaughter_plan_id')->count());
    }

    public function test_draft_intake_is_blocked(): void
    {
        [$user, , $facility, $inspector] = $this->createProcessorContext();
        $intake = $this->createApprovedIntake($facility, [
            'status' => AnimalIntake::STATUS_RECEIVED,
            'is_draft' => true,
            'submitted_at' => null,
        ]);
        $this->createItems($intake, AnimalIntake::SPECIES_CATTLE, 3);

        $response = $this->actingAs($user)->post(
            route('slaughter-plans.store'),
            $this->planStorePayload($facility, $intake, $inspector, [
                'number_of_animals_scheduled' => 2,
            ]),
        );

        $response->assertSessionHasErrors('animal_intake_id');
        $this->assertSame(0, SlaughterPlan::count());
    }

    public function test_under_observation_animals_are_assignable(): void
    {
        [$user, , $facility, $inspector] = $this->createProcessorContext();
        $intake = $this->createApprovedIntake($facility);
        $this->createItems($intake, AnimalIntake::SPECIES_CATTLE, 3, AnimalIntakeItem::HEALTH_HEALTHY, 'H');
        $this->createItems($intake, AnimalIntake::SPECIES_CATTLE, 2, AnimalIntakeItem::HEALTH_OBSERVATION, 'O');

        $response = $this->actingAs($user)->post(
            route('slaughter-plans.store'),
            $this->planStorePayload($facility, $intake, $inspector, [
                'number_of_animals_scheduled' => 4,
            ]),
        );

        $response->assertRedirect(route('slaughter-plans.hub'));
        $plan = SlaughterPlan::firstOrFail();
        $this->assertSame(4, AnimalIntakeItem::where('slaughter_plan_id', $plan->id)->count());
        $this->assertGreaterThanOrEqual(
            1,
            AnimalIntakeItem::where('slaughter_plan_id', $plan->id)
                ->where('health_status', AnimalIntakeItem::HEALTH_OBSERVATION)
                ->count(),
        );
        $this->assertSame(1, AnimalIntakeItem::whereNull('slaughter_plan_id')->count());
    }

    public function test_rejected_animals_are_never_assigned(): void
    {
        [$user, , $facility, $inspector] = $this->createProcessorContext();
        $intake = $this->createApprovedIntake($facility);
        $this->createItems($intake, AnimalIntake::SPECIES_CATTLE, 3, AnimalIntakeItem::HEALTH_HEALTHY, 'H');
        $this->createItems($intake, AnimalIntake::SPECIES_CATTLE, 2, AnimalIntakeItem::HEALTH_REJECTED, 'R');

        $response = $this->actingAs($user)->post(
            route('slaughter-plans.store'),
            $this->planStorePayload($facility, $intake, $inspector, [
                'number_of_animals_scheduled' => 3,
            ]),
        );

        $response->assertRedirect(route('slaughter-plans.hub'));
        $plan = SlaughterPlan::firstOrFail();
        $assigned = AnimalIntakeItem::where('slaughter_plan_id', $plan->id)->get();
        $this->assertCount(3, $assigned);
        $this->assertTrue($assigned->every(fn (AnimalIntakeItem $item) => $item->health_status === AnimalIntakeItem::HEALTH_HEALTHY));
        $this->assertSame(
            2,
            AnimalIntakeItem::where('health_status', AnimalIntakeItem::HEALTH_REJECTED)
                ->whereNull('slaughter_plan_id')
                ->count(),
        );
    }

    public function test_legacy_intake_without_items_uses_aggregate_capacity(): void
    {
        [$user, , $facility, $inspector] = $this->createProcessorContext();
        $intake = $this->createApprovedIntake($facility, [
            'number_of_animals' => 5,
            'species' => AnimalIntake::SPECIES_CATTLE,
        ]);

        $firstResponse = $this->actingAs($user)->post(
            route('slaughter-plans.store'),
            $this->planStorePayload($facility, $intake, $inspector, [
                'number_of_animals_scheduled' => 4,
            ]),
        );

        $firstResponse->assertRedirect(route('slaughter-plans.hub'));
        $this->assertSame(1, SlaughterPlan::count());
        $this->assertSame(0, AnimalIntakeItem::count());

        $secondResponse = $this->actingAs($user)->post(
            route('slaughter-plans.store'),
            $this->planStorePayload($facility, $intake, $inspector, [
                'number_of_animals_scheduled' => 6,
            ]),
        );

        $secondResponse->assertSessionHasErrors('number_of_animals_scheduled');
        $this->assertSame(0, AnimalIntakeItem::count());
    }
}
