<?php

namespace Tests\Feature;

use App\Models\AnimalIntake;
use App\Models\Business;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\SlaughterPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnimalIntakeSlaughterPlanComplianceTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithFacility(): array
    {
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

    public function test_slaughter_plan_store_fails_when_health_certificate_expired(): void
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

        $response->assertSessionHasErrors('animal_intake_id');
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
}
