<?php

namespace Tests\Feature;

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

class AnimalIntakeSlaughterPlanComplianceTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithFacility(): array
    {
        foreach ([
            ['name' => AnimalIntake::SPECIES_CATTLE, 'code' => 'cattle', 'sort_order' => 1],
            ['name' => AnimalIntake::SPECIES_GOAT, 'code' => 'goat', 'sort_order' => 2],
        ] as $row) {
            Species::updateOrCreate(
                ['code' => $row['code']],
                ['name' => $row['name'], 'sort_order' => $row['sort_order'], 'is_active' => true],
            );
        }

        $user = User::factory()->create();
        $business = Business::create([
            'user_id' => $user->id,
            'business_name' => 'Test Business',
            'registration_number' => 'REG-CPL',
            'contact_phone' => '+250788000005',
            'email' => 'cpl@test.com',
            'status' => 'active',
        ]);
        $facility = Facility::create([
            'business_id' => $business->id,
            'facility_name' => 'Test Slaughterhouse',
            'facility_type' => 'slaughterhouse',
            'status' => 'active',
        ]);
        $inspector = Inspector::create([
            'facility_id' => $facility->id,
            'first_name' => 'Insp',
            'last_name' => 'One',
            'national_id' => '111222333444',
            'phone_number' => '+250788333333',
            'email' => 'insp1@test.com',
            'dob' => '1988-01-01',
            'nationality' => 'Rwandan',
            'country' => 'Rwanda',
            'district' => 'Kigali',
            'sector' => 'Gasabo',
            'authorization_number' => 'AUTH-003',
            'authorization_issue_date' => now()->subYear(),
            'authorization_expiry_date' => now()->addYear(),
            'species_allowed' => 'Cattle',
            'status' => 'active',
        ]);

        return [$user, $facility, $inspector];
    }

    public function test_slaughter_plan_store_succeeds_when_health_certificate_expired(): void
    {
        [$user, $facility, $inspector] = $this->createUserWithFacility();
        $intake = AnimalIntake::create([
            'facility_id' => $facility->id,
            'intake_date' => now(),
            'supplier_firstname' => 'S',
            'supplier_lastname' => 'N',
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals' => 5,
            'status' => AnimalIntake::STATUS_APPROVED,
            'health_certificate_expiry_date' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->post(route('slaughter-plans.store'), [
            'slaughter_date' => now()->addDays(2)->toDateString(),
            'facility_id' => $facility->id,
            'animal_intake_id' => $intake->id,
            'inspector_id' => $inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals_scheduled' => 2,
            'status' => SlaughterPlan::STATUS_PLANNED,
        ]);

        $response->assertRedirect(route('slaughter-plans.hub'));
    }

    public function test_slaughter_plan_store_fails_when_scheduled_exceeds_remaining_animals(): void
    {
        [$user, $facility, $inspector] = $this->createUserWithFacility();
        $intake = AnimalIntake::create([
            'facility_id' => $facility->id,
            'intake_date' => now(),
            'supplier_firstname' => 'S',
            'supplier_lastname' => 'N',
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals' => 3,
            'status' => AnimalIntake::STATUS_APPROVED,
            'health_certificate_expiry_date' => now()->addMonth(),
        ]);

        $response = $this->actingAs($user)->post(route('slaughter-plans.store'), [
            'slaughter_date' => now()->addDays(2)->toDateString(),
            'facility_id' => $facility->id,
            'animal_intake_id' => $intake->id,
            'inspector_id' => $inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals_scheduled' => 10,
            'status' => SlaughterPlan::STATUS_PLANNED,
        ]);

        $response->assertSessionHasErrors('number_of_animals_scheduled');
    }

    public function test_item_based_capacity_prevents_overbooking_across_concurrent_plans(): void
    {
        [$user, $facility, $inspector] = $this->createUserWithFacility();
        $intake = AnimalIntake::create([
            'facility_id' => $facility->id,
            'intake_date' => now(),
            'supplier_firstname' => 'S',
            'supplier_lastname' => 'N',
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals' => 6,
            'status' => AnimalIntake::STATUS_APPROVED,
            'is_draft' => false,
            'health_certificate_expiry_date' => now()->addMonth(),
        ]);

        for ($i = 1; $i <= 6; $i++) {
            AnimalIntakeItem::create([
                'animal_intake_id' => $intake->id,
                'ear_tag' => 'CPL-C-'.$intake->id.'-'.$i,
                'species' => AnimalIntake::SPECIES_CATTLE,
                'sex' => AnimalIntake::SEX_MALE,
                'unit_price' => 100000,
                'health_status' => AnimalIntakeItem::HEALTH_HEALTHY,
            ]);
        }

        $payload = [
            'slaughter_date' => now()->addDays(2)->toDateString(),
            'facility_id' => $facility->id,
            'animal_intake_id' => $intake->id,
            'inspector_id' => $inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'status' => SlaughterPlan::STATUS_PLANNED,
        ];

        $this->actingAs($user)->post(route('slaughter-plans.store'), array_merge($payload, [
            'number_of_animals_scheduled' => 4,
        ]))->assertRedirect(route('slaughter-plans.hub'));

        $planA = SlaughterPlan::firstOrFail();
        $this->assertSame(
            4,
            AnimalIntakeItem::where('animal_intake_id', $intake->id)->whereNotNull('slaughter_plan_id')->count(),
        );
        $this->assertSame(4, AnimalIntakeItem::where('slaughter_plan_id', $planA->id)->count());

        $response = $this->actingAs($user)->post(route('slaughter-plans.store'), array_merge($payload, [
            'number_of_animals_scheduled' => 3,
        ]));

        $response->assertSessionHasErrors('number_of_animals_scheduled');
        $this->assertSame(1, SlaughterPlan::count());
        $this->assertSame(
            4,
            AnimalIntakeItem::where('animal_intake_id', $intake->id)->whereNotNull('slaughter_plan_id')->count(),
        );
    }
}
