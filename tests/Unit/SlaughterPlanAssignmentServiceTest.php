<?php

namespace Tests\Unit;

use App\Exceptions\InsufficientAnimalsException;
use App\Models\AnimalIntake;
use App\Models\AnimalIntakeItem;
use App\Models\Business;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\SlaughterPlan;
use App\Models\User;
use App\Services\Processor\SlaughterPlanAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlaughterPlanAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private function createIntakeWithItems(int $cattleCount, int $goatCount = 0): array
    {
        $user = User::factory()->create();
        $business = Business::create([
            'user_id' => $user->id,
            'business_name' => 'Assign Test Co',
            'registration_number' => 'REG-ASN',
            'contact_phone' => '+250788000099',
            'email' => 'assign@test.com',
            'status' => 'active',
        ]);
        $facility = Facility::create([
            'business_id' => $business->id,
            'facility_name' => 'Assign Slaughterhouse',
            'facility_type' => 'slaughterhouse',
            'status' => 'active',
        ]);
        $inspector = Inspector::create([
            'facility_id' => $facility->id,
            'first_name' => 'Insp',
            'last_name' => 'Assign',
            'national_id' => '999888777666',
            'phone_number' => '+250788999999',
            'email' => 'insp-assign@test.com',
            'dob' => '1988-01-01',
            'nationality' => 'Rwandan',
            'country' => 'Rwanda',
            'district' => 'Kigali',
            'sector' => 'Gasabo',
            'authorization_number' => 'AUTH-ASN',
            'authorization_issue_date' => now()->subYear(),
            'authorization_expiry_date' => now()->addYear(),
            'species_allowed' => 'Cattle',
            'status' => 'active',
        ]);
        $intake = AnimalIntake::create([
            'facility_id' => $facility->id,
            'intake_date' => now(),
            'supplier_firstname' => 'Sup',
            'supplier_lastname' => 'Plier',
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals' => $cattleCount + $goatCount,
            'status' => AnimalIntake::STATUS_APPROVED,
            'health_certificate_expiry_date' => now()->addMonth(),
            'is_draft' => false,
        ]);

        for ($i = 1; $i <= $cattleCount; $i++) {
            AnimalIntakeItem::create([
                'animal_intake_id' => $intake->id,
                'ear_tag' => 'CAT-'.$intake->id.'-'.$i,
                'species' => AnimalIntake::SPECIES_CATTLE,
                'sex' => AnimalIntake::SEX_MALE,
                'unit_price' => 100000,
                'health_status' => AnimalIntakeItem::HEALTH_HEALTHY,
            ]);
        }

        for ($i = 1; $i <= $goatCount; $i++) {
            AnimalIntakeItem::create([
                'animal_intake_id' => $intake->id,
                'ear_tag' => 'GOAT-'.$intake->id.'-'.$i,
                'species' => AnimalIntake::SPECIES_GOAT,
                'sex' => AnimalIntake::SEX_FEMALE,
                'unit_price' => 50000,
                'health_status' => AnimalIntakeItem::HEALTH_HEALTHY,
            ]);
        }

        return [$intake, $facility, $inspector];
    }

    public function test_assign_items_to_plan_sets_slaughter_plan_id(): void
    {
        [$intake, $facility, $inspector] = $this->createIntakeWithItems(5);
        $service = app(SlaughterPlanAssignmentService::class);

        $plan = SlaughterPlan::create([
            'slaughter_date' => now()->addDay(),
            'facility_id' => $facility->id,
            'animal_intake_id' => $intake->id,
            'inspector_id' => $inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals_scheduled' => 3,
            'status' => SlaughterPlan::STATUS_PLANNED,
        ]);

        $assigned = $service->assignItemsToPlan($plan, 3);

        $this->assertCount(3, $assigned);
        $this->assertSame(3, AnimalIntakeItem::where('slaughter_plan_id', $plan->id)->count());
        $this->assertSame(2, $service->availableCountForSpecies($intake->fresh(), AnimalIntake::SPECIES_CATTLE));
    }

    public function test_assign_throws_when_insufficient_animals(): void
    {
        [$intake, $facility, $inspector] = $this->createIntakeWithItems(2);
        $service = app(SlaughterPlanAssignmentService::class);

        $plan = SlaughterPlan::create([
            'slaughter_date' => now()->addDay(),
            'facility_id' => $facility->id,
            'animal_intake_id' => $intake->id,
            'inspector_id' => $inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals_scheduled' => 4,
            'status' => SlaughterPlan::STATUS_PLANNED,
        ]);

        try {
            $service->assignItemsToPlan($plan, 4);
            $this->fail('Expected InsufficientAnimalsException');
        } catch (InsufficientAnimalsException $e) {
            $this->assertSame(2, $e->available);
            $this->assertSame(4, $e->requested);
            $this->assertSame(AnimalIntake::SPECIES_CATTLE, $e->species);
        }

        $this->assertSame(0, AnimalIntakeItem::where('slaughter_plan_id', $plan->id)->count());
    }

    public function test_release_items_from_plan_clears_fk(): void
    {
        [$intake, $facility, $inspector] = $this->createIntakeWithItems(3);
        $service = app(SlaughterPlanAssignmentService::class);

        $plan = SlaughterPlan::create([
            'slaughter_date' => now()->addDay(),
            'facility_id' => $facility->id,
            'animal_intake_id' => $intake->id,
            'inspector_id' => $inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals_scheduled' => 2,
            'status' => SlaughterPlan::STATUS_PLANNED,
        ]);

        $service->assignItemsToPlan($plan, 2);
        $service->releaseItemsFromPlan($plan);

        $this->assertSame(0, AnimalIntakeItem::where('slaughter_plan_id', $plan->id)->count());
        $this->assertSame(3, $service->availableCountForSpecies($intake->fresh(), AnimalIntake::SPECIES_CATTLE));
    }

    public function test_rebalance_plan_changes_assigned_items(): void
    {
        [$intake, $facility, $inspector] = $this->createIntakeWithItems(6, 2);
        $service = app(SlaughterPlanAssignmentService::class);

        $plan = SlaughterPlan::create([
            'slaughter_date' => now()->addDay(),
            'facility_id' => $facility->id,
            'animal_intake_id' => $intake->id,
            'inspector_id' => $inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals_scheduled' => 3,
            'status' => SlaughterPlan::STATUS_PLANNED,
        ]);

        $service->assignItemsToPlan($plan, 3);
        $service->rebalancePlan($plan, 5, AnimalIntake::SPECIES_CATTLE);

        $this->assertSame(5, AnimalIntakeItem::where('slaughter_plan_id', $plan->id)->count());
        $this->assertSame(1, $service->availableCountForSpecies($intake->fresh(), AnimalIntake::SPECIES_CATTLE));
        $this->assertSame(2, $service->availableCountForSpecies($intake->fresh(), AnimalIntake::SPECIES_GOAT));
    }

    public function test_available_count_for_species_is_species_specific(): void
    {
        [$intake] = $this->createIntakeWithItems(6, 4);
        $service = app(SlaughterPlanAssignmentService::class);

        $this->assertSame(6, $service->availableCountForSpecies($intake, AnimalIntake::SPECIES_CATTLE));
        $this->assertSame(4, $service->availableCountForSpecies($intake, AnimalIntake::SPECIES_GOAT));
    }
}
